<?php
/**
 * File: ajax/get_timeline_data.php
 * Simplified endpoint for retrieving Gantt chart data
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

// Prepare query parameters
$queryParams = [];

// Build the base query
$sql = "SELECT
    p.JobNumber,
    sbde.ScheduleBreakdownElementID,
    sbdeval.Description,
    sbde.ParentScheduleBreakdownElementID,
    sbde.Level,
    sbde.Priority,
    sts.OriginalEstimate,
    sts.ScheduleTaskID,
    p.JobNumber as ProjectNumber,
    p.GroupName as ProjectManager,
    sd.Description as ScheduleTaskDescription,
    sts.ActualStartDate,
    sts.ActualEndDate,
    ROUND(sts.PercentCompleted *100, 2) as PercentCompleted,
    sts.OriginalEstimate as PlannedHours,
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
    END AS RowGroupID
FROM 
    fabrication.schedulebreakdownelements AS sbde
    INNER JOIN schedulebaselines as sbl on sbl.ScheduleBaselineID = sbde.ScheduleBaselineID and sbl.IsCurrent = 1
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
    AND sbdeval.Description IS NOT NULL
    AND sts.ActualStartDate IS NOT NULL
    AND sts.ActualEndDate IS NOT NULL";

// Apply project filter if not 'all'
if ($projectFilter !== null) {
    $sql .= " AND p.JobNumber = ?";
    $queryParams[] = $projectFilter;
}

// Explicitly do NOT sort in SQL - we'll sort in PHP
// This ensures we have full control over the sorting logic
$sql .= "";

// Prepare and execute query
$stmt = $db->prepare($sql);
$stmt->execute($queryParams);
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

    foreach ($rows as $row) {
        // Parse dates with error handling
        $startDate = !empty($row['ActualStartDate']) ? $row['ActualStartDate'] : null;
        $endDate = !empty($row['ActualEndDate']) ? $row['ActualEndDate'] : null;

        // Skip if we don't have both dates
        if (empty($startDate) || empty($endDate)) {
            continue;
        }

        // Track earliest and latest dates for chart range
        if ($earliestDate === null || strtotime($startDate) < strtotime($earliestDate)) {
            $earliestDate = $startDate;
        }

        if ($latestDate === null || strtotime($endDate) > strtotime($latestDate)) {
            $latestDate = $endDate;
        }

        // Determine task status based on completion percentage
        $status = 'not-started';
        if ($row['PercentCompleted'] > 0 && $row['PercentCompleted'] < 100) {
            $status = 'in-progress';
        } else if ($row['PercentCompleted'] >= 100) {
            $status = 'completed';
        }

        // Build task object
        $task = [
            'id' => $row['ScheduleTaskID'],
            'rowGroupId' => $row['RowGroupID'],
            'project' => $row['JobNumber'],
            'elementId' => $row['ScheduleBreakdownElementID'],
            'description' => $row['Description'],
            'taskDescription' => $row['ScheduleTaskDescription'],
            'parentId' => $row['ParentScheduleBreakdownElementID'],
            'level' => $row['Level'],
            'priority' => $row['Priority'],
            'pm' => $row['ProjectManager'],
            'startDate' => $startDate,
            'endDate' => $endDate,
            'percentage' => $row['PercentCompleted'],
            'hours' => $row['PlannedHours'],
            'status' => $status
        ];

        $tasks[] = $task;
    }

    // Ensure we have a valid date range
    if ($earliestDate === null || $latestDate === null) {
        $today = date('Y-m-d');
        $earliestDate = date('Y-m-d', strtotime('-14 days', strtotime($today)));
        $latestDate = date('Y-m-d', strtotime('+14 days', strtotime($today)));
    } else {
        // Add padding to the date range
        $earliestDate = date('Y-m-d', strtotime('-3 days', strtotime($earliestDate)));
        $latestDate = date('Y-m-d', strtotime('+3 days', strtotime($latestDate)));
    }

    // Get all tasks with valid dates
    $tasks = [];

    foreach ($rows as $row) {
        // Parse dates with error handling
        $startDate = !empty($row['ActualStartDate']) ? $row['ActualStartDate'] : null;
        $endDate = !empty($row['ActualEndDate']) ? $row['ActualEndDate'] : null;

        // Skip if we don't have both dates
        if (empty($startDate) || empty($endDate)) {
            continue;
        }

        // Determine task status based on completion percentage
        $status = 'not-started';
        if ($row['PercentCompleted'] > 0 && $row['PercentCompleted'] < 100) {
            $status = 'in-progress';
        } else if ($row['PercentCompleted'] >= 100) {
            $status = 'completed';
        }

        // Build task object
        $task = [
            'id' => $row['ScheduleTaskID'],
            'rowGroupId' => $row['RowGroupID'],
            'project' => $row['JobNumber'],
            'elementId' => $row['ScheduleBreakdownElementID'],
            'description' => $row['Description'],
            'taskDescription' => $row['ScheduleTaskDescription'],
            'parentId' => $row['ParentScheduleBreakdownElementID'],
            'level' => $row['Level'],
            'priority' => $row['Priority'],
            'pm' => $row['ProjectManager'],
            'startDate' => $startDate,
            'endDate' => $endDate,
            'percentage' => $row['PercentCompleted'],
            'hours' => $row['PlannedHours'],
            'status' => $status
        ];

        $tasks[] = $task;

        // Track earliest and latest dates for chart range
        if ($earliestDate === null || strtotime($startDate) < strtotime($earliestDate)) {
            $earliestDate = $startDate;
        }

        if ($latestDate === null || strtotime($endDate) > strtotime($latestDate)) {
            $latestDate = $endDate;
        }
    }

    // Ensure we have a valid date range
    if ($earliestDate === null || $latestDate === null) {
        $today = date('Y-m-d');
        $earliestDate = date('Y-m-d', strtotime('-14 days', strtotime($today)));
        $latestDate = date('Y-m-d', strtotime('+14 days', strtotime($today)));
    } else {
        // Add padding to the date range
        $earliestDate = date('Y-m-d', strtotime('-3 days', strtotime($earliestDate)));
        $latestDate = date('Y-m-d', strtotime('+3 days', strtotime($latestDate)));
    }

    // Sort tasks purely by start date and then end date - use raw timestamp comparison
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
            'start' => $earliestDate,
            'end' => $latestDate
        ],
        'tasks' => $tasks
    ];
}
?>