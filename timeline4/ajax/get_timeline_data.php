<?php
/**
 * File: ajax/get_timeline_data.php
 * Lean endpoint for retrieving core Gantt chart timeline data only
 * Badges and workweeks are loaded separately
 */
require_once('../../config_ssf_db.php');
header('Content-Type: application/json');

// Get filter from request
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Parse filter to extract project filter
$projectFilter = null;
if ($filter !== 'all' && preg_match('/^[A-Za-z0-9\-]+$/', $filter)) {
    $projectFilter = $filter;
}

// Build the lean query - fabrication tasks with IFF subtasks
$sql = "
WITH ActiveFabricationProjects AS (
    SELECT DISTINCT 
        p.JobNumber,
        CASE 
            WHEN sbde.Level = 1 THEN sbdeval.Description -- Level 1: Description is SequenceName
            WHEN sbde.Level = 2 THEN 
                -- Level 2: Extract parent description as SequenceName
                (SELECT parent_desc.Description 
                 FROM fabrication.schedulebreakdownelements parent
                 INNER JOIN scheduledescriptions parent_desc 
                     ON parent_desc.ScheduleDescriptionID = parent.ScheduleBreakdownValueID
                 WHERE parent.ScheduleBreakdownElementID = sbde.ParentScheduleBreakdownElementID)
        END AS SequenceName,
        CASE 
            WHEN sbde.Level = 1 THEN NULL -- Level 1: No LotNumber
            WHEN sbde.Level = 2 THEN sbdeval.Description -- Level 2: Current description is LotNumber
        END AS LotNumber,
        sbde.Level, -- Include level to distinguish between level 1 and level 2 records
        -- Include RowGroupID as it appears in your original query
        CASE 
            WHEN sbde.Level = 1 THEN CONCAT(p.JobNumber,'.',sbdeval.Description)
            WHEN sbde.Level = 2 THEN 
                CONCAT(p.JobNumber,'.',
                    (SELECT parent_desc.Description 
                     FROM fabrication.schedulebreakdownelements parent
                     INNER JOIN scheduledescriptions parent_desc ON parent_desc.ScheduleDescriptionID = parent.ScheduleBreakdownValueID
                     WHERE parent.ScheduleBreakdownElementID = sbde.ParentScheduleBreakdownElementID),
                    '.',
                    sbdeval.Description
                )
            ELSE sbdeval.Description
        END AS RowGroupID,
        sbde.ScheduleBreakdownElementID as elementId,
        sbde.ParentScheduleBreakdownElementID as parentId,
        sbde.Priority as priority,
        p.GroupName AS pm,
        sts.ScheduleTaskID as id,
        sd.Description AS taskDescription,
        sbdeval.Description as description,
        sts.ActualStartDate as startDate,
        sts.ActualEndDate as endDate,
        ROUND(sts.PercentCompleted * 100, 2) AS percentage,
        sts.OriginalEstimate AS hours,
        CASE 
            WHEN sts.PercentCompleted > 0 AND sts.PercentCompleted < 1 THEN 'in-progress'
            WHEN sts.PercentCompleted >= 1 THEN 'completed'
            ELSE 'not-started'
        END AS status,
        p.JobNumber as project
    FROM fabrication.schedulebreakdownelements AS sbde
    INNER JOIN schedulebaselines as sbl ON sbl.ScheduleBaselineID = sbde.ScheduleBaselineID AND sbl.IsCurrent = 1
    INNER JOIN scheduledescriptions AS sbdeval ON sbdeval.ScheduleDescriptionID = sbde.ScheduleBreakdownValueID
    INNER JOIN scheduletasks as sts ON sts.ScheduleBreakdownElementID = sbde.ScheduleBreakdownElementID
    INNER JOIN resources ON resources.ResourceID = sts.ResourceID
    INNER JOIN projects as p ON p.ProjectID = sts.ProjectID
    INNER JOIN scheduledescriptions as sd ON sd.ScheduleDescriptionID = sts.ScheduleDescriptionID
    WHERE 
        p.JobStatusID IN (1,6)
        AND sbde.Level < 3
        AND sts.PercentCompleted < 0.99
        AND resources.Description = 'Fabrication'
        AND sd.Description = 'Fabrication'
        AND sbdeval.Description IS NOT NULL
        AND sts.ActualStartDate IS NOT NULL
        AND sts.ActualEndDate IS NOT NULL
        " . ($projectFilter !== null ? "AND p.JobNumber = ?" : "") . "
),

-- Get IFF tasks for the same RowGroupIDs
IFFTasks AS (
    SELECT DISTINCT 
        CASE 
            WHEN sbde.Level = 1 THEN CONCAT(p.JobNumber,'.',sbdeval.Description)
            WHEN sbde.Level = 2 THEN 
                CONCAT(p.JobNumber,'.',
                    (SELECT parent_desc.Description 
                     FROM fabrication.schedulebreakdownelements parent
                     INNER JOIN scheduledescriptions parent_desc ON parent_desc.ScheduleDescriptionID = parent.ScheduleBreakdownValueID
                     WHERE parent.ScheduleBreakdownElementID = sbde.ParentScheduleBreakdownElementID),
                    '.',
                    sbdeval.Description
                )
            ELSE sbdeval.Description
        END AS RowGroupID,
        sts.ScheduleTaskID as iffTaskId,
        sd.Description AS iffTaskDescription,
        sts.ActualStartDate as iffMilestoneDate,
        ROUND(sts.PercentCompleted * 100, 2) AS iffPercentage,
        sts.OriginalEstimate AS iffHours,
        CASE 
            WHEN sts.PercentCompleted > 0 AND sts.PercentCompleted < 1 THEN 'in-progress'
            WHEN sts.PercentCompleted >= 1 THEN 'completed'
            ELSE 'not-started'
        END AS iffStatus
    FROM fabrication.schedulebreakdownelements AS sbde
    INNER JOIN schedulebaselines as sbl ON sbl.ScheduleBaselineID = sbde.ScheduleBaselineID AND sbl.IsCurrent = 1
    INNER JOIN scheduledescriptions AS sbdeval ON sbdeval.ScheduleDescriptionID = sbde.ScheduleBreakdownValueID
    INNER JOIN scheduletasks as sts ON sts.ScheduleBreakdownElementID = sbde.ScheduleBreakdownElementID
    INNER JOIN resources ON resources.ResourceID = sts.ResourceID
    INNER JOIN projects as p ON p.ProjectID = sts.ProjectID
    INNER JOIN scheduledescriptions as sd ON sd.ScheduleDescriptionID = sts.ScheduleDescriptionID
    WHERE 
        p.JobStatusID IN (1,6)
        AND sbde.Level < 3
        AND sts.PercentCompleted < 0.99
        AND resources.Description = 'Detailing'
        AND sd.Description = 'Issued for Fabrication'
        AND sbdeval.Description IS NOT NULL
        AND sts.ActualStartDate IS NOT NULL
        " . ($projectFilter !== null ? "AND p.JobNumber = ?" : "") . "
),

-- Get workweek date ranges for the same projects
WorkweekDateRanges AS (
    SELECT 
        MIN(STR_TO_DATE(
            CONCAT(
                '20', -- For years 2000-2099
                SUBSTRING(wp.Group2, 1, 2), -- Year
                SUBSTRING(wp.Group2, 3, 2), -- Week
                '1' -- First day of week (Monday)
            ), 
            '%Y%U%w'
        )) AS EarliestWorkweekStart,
        MAX(DATE_ADD(
            STR_TO_DATE(
                CONCAT(
                    '20', -- For years 2000-2099
                    SUBSTRING(wp.Group2, 1, 2), -- Year
                    SUBSTRING(wp.Group2, 3, 2), -- Week
                    '1' -- First day of week (Monday)
                ), 
                '%Y%U%w'
            ),
            INTERVAL 4 DAY
        )) AS LatestWorkweekEnd
    FROM productioncontrolsequences AS pcseq
    INNER JOIN productioncontroljobs AS pcj ON pcj.ProductionControlID = pcseq.ProductionControlID
    INNER JOIN projects AS p ON p.ProjectID = pcj.ProjectID
    INNER JOIN workpackages AS wp ON wp.WorkPackageID = pcseq.WorkPackageID
    WHERE 
        pcseq.AssemblyQuantity > 0
        AND wp.Completed = 0  -- Exclude completed workweeks
        AND wp.Group2 IS NOT NULL
        AND wp.WorkshopID = 1
        " . ($projectFilter !== null ? "AND p.JobNumber = ?" : "") . "
)

SELECT 
    afp.*,
    iff.iffTaskId,
    iff.iffTaskDescription,
    iff.iffMilestoneDate,
    iff.iffPercentage,
    iff.iffHours,
    iff.iffStatus,
    wdr.EarliestWorkweekStart,
    wdr.LatestWorkweekEnd
FROM ActiveFabricationProjects afp
LEFT JOIN IFFTasks iff ON afp.RowGroupID = iff.RowGroupID
CROSS JOIN WorkweekDateRanges wdr
ORDER BY afp.JobNumber, afp.SequenceName, afp.LotNumber, afp.startDate, afp.endDate
";

// Prepare parameter array for the query
$params = [];
if ($projectFilter !== null) {
    // Add the parameter three times since it's used in three CTEs
    $params[] = $projectFilter; // ActiveFabricationProjects
    $params[] = $projectFilter; // IFFTasks
    $params[] = $projectFilter; // WorkweekDateRanges
}

// Prepare and execute query
$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process the data for the Gantt chart
$ganttData = processQueryResults($rows);

// Output JSON
echo json_encode($ganttData, JSON_PRETTY_PRINT);

/**
 * Process query results into Gantt chart format
 *
 * @param array $rows Database query results
 * @return array Formatted data for Gantt chart
 */
function processQueryResults($rows) {
    $tasks = [];
    $earliestDate = null;
    $latestDate = null;
    $earliestWorkweekDate = null;
    $latestWorkweekDate = null;

    foreach ($rows as $row) {
        // Parse task dates with error handling
        $startDate = !empty($row['startDate']) ? $row['startDate'] : null;
        $endDate = !empty($row['endDate']) ? $row['endDate'] : null;

        // Skip if we don't have both dates
        if (empty($startDate) || empty($endDate)) {
            continue;
        }

        // Track earliest and latest task dates
        if ($earliestDate === null || strtotime($startDate) < strtotime($earliestDate)) {
            $earliestDate = $startDate;
        }

        if ($latestDate === null || strtotime($endDate) > strtotime($latestDate)) {
            $latestDate = $endDate;
        }

        // Also consider IFF milestone date for date range (could extend timeline start)
        if (!empty($row['iffMilestoneDate'])) {
            $iffDate = strtotime($row['iffMilestoneDate']);
            if ($earliestDate === null || $iffDate < strtotime($earliestDate)) {
                $earliestDate = $row['iffMilestoneDate'];
            }
            // IFF dates could also extend the end date if they're later than fabrication
            if ($latestDate === null || $iffDate > strtotime($latestDate)) {
                $latestDate = $row['iffMilestoneDate'];
            }
        }

        // Track workweek dates (they're the same for all rows since we CROSS JOIN)
        if (!empty($row['EarliestWorkweekStart'])) {
            $earliestWorkweekDate = $row['EarliestWorkweekStart'];
        }
        if (!empty($row['LatestWorkweekEnd'])) {
            $latestWorkweekDate = $row['LatestWorkweekEnd'];
        }

        // Build IFF subtask object if data exists
        $iffSubtask = null;
        if (!empty($row['iffTaskId'])) {
            $iffSubtask = [
                'taskId' => $row['iffTaskId'],
                'taskDescription' => $row['iffTaskDescription'],
                'milestoneDate' => $row['iffMilestoneDate'],
                'percentage' => floatval($row['iffPercentage']),
                'hours' => floatval($row['iffHours']),
                'status' => $row['iffStatus'],
                'formattedMilestoneDate' => !empty($row['iffMilestoneDate']) ? date('M j, Y', strtotime($row['iffMilestoneDate'])) : null
            ];
        }

        // Remove workweek date columns and IFF columns from main task data
        unset($row['EarliestWorkweekStart']);
        unset($row['LatestWorkweekEnd']);
        unset($row['iffTaskId']);
        unset($row['iffTaskDescription']);
        unset($row['iffMilestoneDate']);
        unset($row['iffPercentage']);
        unset($row['iffHours']);
        unset($row['iffStatus']);

        // Add IFF subtask to the main task
        $row['iffSubtask'] = $iffSubtask;

        // Add the row to tasks
        $tasks[] = $row;
    }

    // Determine the actual date range considering both tasks and workweeks
    $finalEarliestDate = $earliestDate;
    $finalLatestDate = $latestDate;

    // Compare with workweek dates if they exist
    if ($earliestWorkweekDate && (empty($finalEarliestDate) || strtotime($earliestWorkweekDate) < strtotime($finalEarliestDate))) {
        $finalEarliestDate = $earliestWorkweekDate;
    }

    if ($latestWorkweekDate && (empty($finalLatestDate) || strtotime($latestWorkweekDate) > strtotime($finalLatestDate))) {
        $finalLatestDate = $latestWorkweekDate;
    }

    // Ensure we have a valid date range
    if ($finalEarliestDate === null || $finalLatestDate === null) {
        $today = date('Y-m-d');
        $finalEarliestDate = date('Y-m-d', strtotime('-14 days', strtotime($today)));
        $finalLatestDate = date('Y-m-d', strtotime('+14 days', strtotime($today)));
    } else {
        // Add padding to the date range
        $finalEarliestDate = date('Y-m-d', strtotime('-3 days', strtotime($finalEarliestDate)));
        $finalLatestDate = date('Y-m-d', strtotime('+3 days', strtotime($finalLatestDate)));
    }

    // Sort tasks by start date and then end date
    usort($tasks, function($a, $b) {
        // Get timestamps for comparison
        $aStart = strtotime($a['startDate']);
        $bStart = strtotime($b['startDate']);

        // Compare start dates first
        if ($aStart != $bStart) {
            return $aStart - $bStart;
        }

        // If start dates are identical, compare end dates
        $aEnd = strtotime($a['endDate']);
        $bEnd = strtotime($b['endDate']);

        return $aEnd - $bEnd;
    });

    // Format response
    return [
        'dateRange' => [
            'start' => $finalEarliestDate,
            'end' => $finalLatestDate
        ],
        'tasks' => $tasks
    ];
}
?>