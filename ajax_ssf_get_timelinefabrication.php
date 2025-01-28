<?php
require_once('config_ssf_db.php');
header('Content-Type: application/json');

$ganttData = [
    'dateRange' => [
        'start' => date('Y-m-d', strtotime('-5 days')),
        'end' => date('Y-m-d', strtotime('+5 days'))
    ],
    'sequences' => []
];

// First query to get relevant RowGroupIDs
$sql_base = "SELECT DISTINCT CONCAT(p.JobNumber, ':', sbdeval.Description) as RowGroupID
FROM scheduletasks as st
inner join scheduledescriptions as sd ON sd.ScheduleDescriptionID = st.ScheduleDescriptionID
inner join projects as p on p.ProjectID = st.ProjectID
inner join schedulebaselines as sb on sb.ScheduleBaselineID = st.ScheduleBaselineID
inner join schedulebreakdownelements as sbde ON sbde.ScheduleBreakdownElementID = st.ScheduleBreakdownElementID
inner join scheduledescriptions as sbdeval ON sbdeval.ScheduleDescriptionID = sbde.ScheduleBreakdownValueID
inner join resources ON resources.ResourceID = st.ResourceID
inner join jobstatuses as js ON js.JobStatusID = p.JobStatusID
WHERE sb.IsCurrent = 1 
AND js.Purpose = 0 
AND st.PercentCompleted < 0.99
AND resources.Description = 'Fabrication' 
AND sd.Description = 'Fabrication'
AND sbdeval.Description IS NOT NULL
ORDER BY sbdeval.Description
";

$base_results = $db->query($sql_base);
$row_group_ids = [];
while ($row = $base_results->fetch(PDO::FETCH_ASSOC)) {
    $row_group_ids[] = "'" . $row['RowGroupID'] . "'";
}

if (empty($row_group_ids)) {
    echo json_encode(['dateRange' => ['start' => '', 'end' => ''], 'sequences' => []]);
    exit;
}

// Main query
$sql = "select 
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
   st.PlannedHours as PlannedHours,
   resources.Description as ResourceDescription,
   CASE 
       WHEN resources.Description = 'Document Control' AND sd.Description = 'Issued For Fabrication' THEN 'iff'
       WHEN resources.Description = 'Procurement' AND sd.Description IN ('Material Purchased', 'Material Received') THEN 'nsi'
       WHEN resources.Description = 'CNC' AND sd.Description = 'Part Categorization' THEN 'categorize'
       ELSE 'fabrication'
   END as TaskType
from scheduletasks as st
inner join scheduledescriptions as sd ON sd.ScheduleDescriptionID = st.ScheduleDescriptionID
inner join projects as p on p.ProjectID = st.ProjectID
inner join schedulebaselines as sb on sb.ScheduleBaselineID = st.ScheduleBaselineID
inner join schedulebreakdownelements as sbde ON sbde.ScheduleBreakdownElementID = st.ScheduleBreakdownElementID
inner join scheduledescriptions as sbdeval ON sbdeval.ScheduleDescriptionID = sbde.ScheduleBreakdownValueID
left join schedulebreakdownelements as sbdep on sbdep.ScheduleBreakdownElementID = sbde.ParentScheduleBreakdownElementID
left join scheduledescriptions as sbdepval on sbdepval.ScheduleDescriptionID = sbdep.ScheduleBreakdownValueID
inner join resources ON resources.ResourceID = st.ResourceID
inner join jobstatuses as js ON js.JobStatusID = p.JobStatusID
where sb.IsCurrent = 1 
   and js.Purpose = 0 
   and CONCAT(p.JobNumber, ':', sbdeval.Description) IN (" . implode(',', $row_group_ids) . ")
   and (
       (resources.Description = 'Document Control' and sd.Description = 'Issued For Fabrication')
       or (resources.Description = 'Procurement' and sd.Description = 'Material Purchased')
       or (resources.Description = 'Procurement' and sd.Description = 'Material Received')
       or (resources.Description = 'CNC' and sd.Description = 'Part Categorization')
       or (resources.Description = 'Fabrication' and sd.Description = 'Fabrication')
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
}

$ganttData['sequences'] = array_values($sequences);

// Find absolute earliest and latest dates across all sequences and all date fields
foreach ($ganttData['sequences'] as $sequence) {
    $dates = [];
    if (isset($sequence['fabrication'])) {
        $dates[] = $sequence['fabrication']['start'];
        $dates[] = $sequence['fabrication']['end'];
    }
    $dates[] = $sequence['iff']['start'];
    $dates[] = $sequence['nsi']['start'];
    $dates[] = $sequence['categorize']['start'];

    if ($sequence['hasWorkPackage']) {
        $dates[] = $sequence['wp']['start'];
        $dates[] = $sequence['wp']['end'];
    }

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
}

$today = date('Y-m-d');
$fiveDaysBeforeToday = date('Y-m-d', strtotime('-5 days'));
$fiveDaysAfterToday = date('Y-m-d', strtotime('+5 days'));

$ganttData['dateRange']['start'] = (strtotime($earliestDate) > strtotime($today)) ? $fiveDaysBeforeToday : $earliestDate;
$ganttData['dateRange']['end'] = (strtotime($latestDate) < strtotime($today)) ? $fiveDaysAfterToday : $latestDate;

foreach ($ganttData['sequences'] as &$sequence) {
    if ($sequence['iff']['percentage'] == -1) {
        $sequence['iff']['start'] = $ganttData['dateRange']['start'];
    }
    if ($sequence['nsi']['percentage'] == -1) {
        $sequence['nsi']['start'] = $ganttData['dateRange']['start'];
    }
}

$ganttData['sequences'] = array_values(array_filter($ganttData['sequences'], function($sequence) {
    return $sequence['sequence'] !== null;
}));

echo json_encode($ganttData);