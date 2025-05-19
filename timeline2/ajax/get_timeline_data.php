<?php
/**
 * File: ajax/get_timeline_data.php (Modified)
 * Endpoint for retrieving main Gantt chart data
 * Improved to ensure correct date range when filtering by job
 */
require_once('../../config_ssf_db.php');
header('Content-Type: application/json');

// Get filter from request
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Initialize response data structure
$ganttData = [
    'dateRange' => [
        'start' => date('Y-m-d', strtotime('-5 days')),
        'end' => date('Y-m-d', strtotime('+5 days'))
    ],
    'sequences' => []
];

// Parse filter to extract project filter
$projectFilter = $filter;
$categoryFilter = null;

// Check if filter contains category component
if (is_string($filter) && strpos($filter, ':') !== false) {
    $filterParts = explode(':', $filter);
    $projectFilter = $filterParts[0];
    $categoryFilter = isset($filterParts[1]) ? $filterParts[1] : null;
}

// Build the base query to get relevant RowGroupIDs
$baseQueryParams = [];
$sql_base = "SELECT DISTINCT CONCAT(p.JobNumber, ':', sbdeval.Description) as RowGroupID
FROM scheduletasks as st
INNER JOIN scheduledescriptions as sd ON sd.ScheduleDescriptionID = st.ScheduleDescriptionID
INNER JOIN projects as p on p.ProjectID = st.ProjectID
INNER JOIN schedulebaselines as sb on sb.ScheduleBaselineID = st.ScheduleBaselineID
INNER JOIN schedulebreakdownelements as sbde ON sbde.ScheduleBreakdownElementID = st.ScheduleBreakdownElementID
INNER JOIN scheduledescriptions as sbdeval ON sbdeval.ScheduleDescriptionID = sbde.ScheduleBreakdownValueID
INNER JOIN resources ON resources.ResourceID = st.ResourceID
INNER JOIN jobstatuses as js ON js.JobStatusID = p.JobStatusID
WHERE sb.IsCurrent = 1 
AND js.Purpose = 0 
AND st.PercentCompleted < 0.99
AND resources.Description = 'Fabrication' 
AND sbdeval.Description IS NOT NULL";

// Apply project filter if not 'all'
if ($projectFilter !== 'all' && preg_match('/^[A-Za-z0-9\-]+$/', $projectFilter)) {
    $sql_base .= " AND p.JobNumber = ?";
    $baseQueryParams[] = $projectFilter;
}

$sql_base .= " ORDER BY sbdeval.Description";

$base_stmt = $db->prepare($sql_base);
$base_stmt->execute($baseQueryParams);
$row_group_ids = [];
while ($row = $base_stmt->fetch(PDO::FETCH_ASSOC)) {
    $row_group_ids[] = "'" . $row['RowGroupID'] . "'";
}

if (empty($row_group_ids)) {
    echo json_encode(['dateRange' => ['start' => '', 'end' => ''], 'sequences' => []]);
    exit;
}

// Main query to get sequence data
$sql = "SELECT 
   st.ScheduleTaskID,
   CASE 
    WHEN resources.Description = 'Procurement' 
        THEN CONCAT(p.JobNumber, ':', sbdepval.Description)
        ELSE CONCAT(p.JobNumber, ':', sbdeval.Description)
    END as RowGroupID,
   p.JobDescription,
   p.JobNumber as ProjectNumber,
   CASE WHEN resources.Description = 'Procurement' THEN sbdepval.Description ELSE sbdeval.Description END as SequenceNumber,
   sbdeval.Description as BreakdownElementValue,
   p.GroupName as ProjectManager,
   sd.Description as TaskDescription,
   st.ActualStartDate,
   st.ActualEndDate,
   ROUND(st.PercentCompleted *100, 2) as PercentCompleted,
   CASE
        WHEN EXISTS (
            SELECT 1
            FROM productioncontroljobs pcj
                     INNER JOIN workpackages wp ON wp.ProductionControlID = pcj.ProductionControlID
            WHERE pcj.ProjectID = p.ProjectID
              AND wp.Group2 IS NOT NULL
              AND wp.Group2 != ''
        ) THEN 1
        ELSE 0
        END as HasWP,
   st.OriginalEstimate as PlannedHours,
   resources.Description as ResourceDescription,
   CASE 
       WHEN resources.Description = 'Document Control' AND sd.Description = 'Issued For Fabrication' THEN 'iff'
       WHEN resources.Description = 'Procurement' AND sd.Description IN ('Material Purchased', 'Material Received') THEN 'nsi'
       WHEN resources.Description = 'CNC' AND sd.Description = 'Part Categorization' THEN 'categorize'
       ELSE 'fabrication'
   END as TaskType
FROM scheduletasks as st
INNER JOIN scheduledescriptions as sd ON sd.ScheduleDescriptionID = st.ScheduleDescriptionID
INNER JOIN projects as p on p.ProjectID = st.ProjectID
INNER JOIN schedulebaselines as sb on sb.ScheduleBaselineID = st.ScheduleBaselineID
INNER JOIN schedulebreakdownelements as sbde ON sbde.ScheduleBreakdownElementID = st.ScheduleBreakdownElementID
INNER JOIN scheduledescriptions as sbdeval ON sbdeval.ScheduleDescriptionID = sbde.ScheduleBreakdownValueID
LEFT JOIN schedulebreakdownelements as sbdep on sbdep.ScheduleBreakdownElementID = sbde.ParentScheduleBreakdownElementID
LEFT JOIN scheduledescriptions as sbdepval on sbdepval.ScheduleDescriptionID = sbdep.ScheduleBreakdownValueID
INNER JOIN resources ON resources.ResourceID = st.ResourceID
INNER JOIN jobstatuses as js ON js.JobStatusID = p.JobStatusID
WHERE sb.IsCurrent = 1 
   AND js.Purpose = 0 
   AND CONCAT(p.JobNumber, ':', sbdeval.Description) IN (" . implode(',', $row_group_ids) . ")
   AND (
       (resources.Description = 'Document Control' AND sd.Description = 'Issued For Fabrication')
       OR (resources.Description = 'Procurement' AND sd.Description = 'Material Purchased')
       OR (resources.Description = 'Procurement' AND sd.Description = 'Material Received')
       OR (resources.Description = 'CNC' AND sd.Description = 'Part Categorization')
       OR (resources.Description = 'Fabrication')
   )
   AND sbdeval.Description IS NOT NULL
   ORDER BY sbdeval.Description";

$sql_results = $db->query($sql);

$earliestDate = null;
$latestDate = null;

$sequences = [];
while ($row = $sql_results->fetch(PDO::FETCH_ASSOC)) {
    $sequenceKey = $row['ProjectNumber'] . ':' . $row['SequenceNumber'];

    if (!isset($sequences[$sequenceKey])) {
        $sequences[$sequenceKey] = [
            'project' => $row['ProjectNumber'],
            'sequence' => $row['SequenceNumber'],
            'pm' => $row['ProjectManager'],
            'iff' => ['start' => date('Y-m-d'), 'percentage' => -1],
            'nsi' => ['start' => date('Y-m-d'), 'percentage' => -1],
            'categorize' => ['start' => date('Y-m-d'), 'percentage' => -1],
            'fabrication' => [
                'start' => date('Y-m-d'),
                'end' => date('Y-m-d', strtotime('+30 days')),
                'percentage' => -1,
                'hours' => 0,
                'description' => '',
                'id' => ''
            ],
            'hasWorkPackage' => false,
            'hasWP' => $row['HasWP'],
            'wp' => ['start' => date('Y-m-d'), 'end' => date('Y-m-d', strtotime('+30 days'))]
        ];
    }

    if ($row['TaskType'] === 'fabrication') {
        $sequences[$sequenceKey]['fabrication'] = [
            'description' => $row['TaskDescription'],
            'start' => $row['ActualStartDate'],
            'end' => $row['ActualEndDate'],
            'percentage' => $row['PercentCompleted'],
            'hours' => $row['PlannedHours'],
            'id' => $row['ScheduleTaskID']
        ];
    } else {
        $sequences[$sequenceKey][$row['TaskType']]['start'] = $row['ActualStartDate'];
        $sequences[$sequenceKey][$row['TaskType']]['percentage'] = $row['PercentCompleted'];
    }

    // Track hasWP property
    $sequences[$sequenceKey]['hasWP'] = $row['HasWP'];
}

$ganttData['sequences'] = array_values($sequences);

// Find absolute earliest and latest dates across all sequences and all date fields
foreach ($ganttData['sequences'] as &$sequence) {
    // Collect all dates from this sequence for finding min/max
    $dates = [];

    // Add fabrication dates
    if (isset($sequence['fabrication'])) {
        if ($sequence['fabrication']['start']) {
            $dates[] = $sequence['fabrication']['start'];
        }
        if ($sequence['fabrication']['end']) {
            $dates[] = $sequence['fabrication']['end'];
        }
    }

    // Add other milestone dates
    if ($sequence['iff']['percentage'] != -1 && $sequence['iff']['start']) {
        $dates[] = $sequence['iff']['start'];
    }
    if ($sequence['nsi']['percentage'] != -1 && $sequence['nsi']['start']) {
        $dates[] = $sequence['nsi']['start'];
    }
    if ($sequence['categorize']['percentage'] != -1 && $sequence['categorize']['start']) {
        $dates[] = $sequence['categorize']['start'];
    }

    // Add workpackage dates if applicable
    if ($sequence['hasWorkPackage'] || $sequence['hasWP']) {
        $dates[] = $sequence['wp']['start'];
        $dates[] = $sequence['wp']['end'];
    }

    // Update earliest/latest dates
    foreach ($dates as $date) {
        if ($date) {
            $timestamp = strtotime($date);
            if ($timestamp) {
                if (!$earliestDate || $timestamp < strtotime($earliestDate)) {
                    $earliestDate = $date;
                }
                if (!$latestDate || $timestamp > strtotime($latestDate)) {
                    $latestDate = $date;
                }
            }
        }
    }

    // Set default values for any missing dates
    if ($sequence['iff']['percentage'] == -1) {
        $sequence['iff']['start'] = $sequence['fabrication']['start'] ?? date('Y-m-d');
    }
    if ($sequence['nsi']['percentage'] == -1) {
        $sequence['nsi']['start'] = $sequence['fabrication']['start'] ?? date('Y-m-d');
    }
}

// Calculate appropriate date range
$today = date('Y-m-d');

// Add 5 days padding to the beginning and end of the date range
$earliestDate = $earliestDate ? date('Y-m-d', strtotime($earliestDate . ' -5 days')) : date('Y-m-d', strtotime('-5 days'));
$latestDate = $latestDate ? date('Y-m-d', strtotime($latestDate . ' +5 days')) : date('Y-m-d', strtotime('+5 days'));

// Always include today in the range
if (strtotime($today) < strtotime($earliestDate)) {
    $earliestDate = date('Y-m-d', strtotime($today . ' -2 days'));
}
if (strtotime($today) > strtotime($latestDate)) {
    $latestDate = date('Y-m-d', strtotime($today . ' +2 days'));
}

// Set the final date range
$ganttData['dateRange']['start'] = $earliestDate;
$ganttData['dateRange']['end'] = $latestDate;

// Filter out sequences with null sequence
$ganttData['sequences'] = array_values(array_filter($ganttData['sequences'], function($sequence) {
    return $sequence['sequence'] !== null;
}));

echo json_encode($ganttData, JSON_PRETTY_PRINT);
?>