<?php
/**
 * File: ajax/get_task_details.php
 * Endpoint for retrieving detailed information about a specific task
 */
require_once('../../config_ssf_db.php');
header('Content-Type: application/json');

// Get task ID from request
$taskId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($taskId <= 0) {
    echo json_encode(['error' => 'Invalid task ID']);
    exit;
}

// Prepare query
$sql = "SELECT
    st.ScheduleTaskID,
    p.JobNumber,
    p.JobDescription,
    p.GroupName as ProjectManager,
    sbd.Description as ElementName,
    CASE 
        WHEN sbde.Level = 1 THEN CONCAT(p.JobNumber,'.',sbd.Description)
        WHEN sbde.Level = 2 THEN 
            CONCAT(p.JobNumber,'.',
                (SELECT parent_desc.Description 
                 FROM fabrication.schedulebreakdownelements parent
                 INNER JOIN scheduledescriptions parent_desc ON parent_desc.ScheduleDescriptionID = parent.ScheduleBreakdownValueID
                 WHERE parent.ScheduleBreakdownElementID = sbde.ParentScheduleBreakdownElementID),
                '.',
                sbd.Description
            )
        ELSE sbd.Description
    END AS FullPath,
    sd.Description as TaskDescription,
    ROUND(st.PercentCompleted * 100, 2) as PercentComplete,
    st.ActualStartDate,
    st.ActualEndDate,
    st.OriginalEstimate as EstimatedHours,
    st.BaselineStartDate,
    st.BaselineEndDate,
    resources.Description as ResourceName,
    sbde.Level
FROM
    scheduletasks st
    INNER JOIN schedulebreakdownelements sbde ON sbde.ScheduleBreakdownElementID = st.ScheduleBreakdownElementID
    INNER JOIN scheduledescriptions sbd ON sbd.ScheduleDescriptionID = sbde.ScheduleBreakdownValueID
    INNER JOIN projects p ON p.ProjectID = st.ProjectID
    INNER JOIN scheduledescriptions sd ON sd.ScheduleDescriptionID = st.ScheduleDescriptionID
    INNER JOIN resources ON resources.ResourceID = st.ResourceID
WHERE
    st.ScheduleTaskID = ?";

// Execute query
$stmt = $db->prepare($sql);
$stmt->execute([$taskId]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$task) {
    echo json_encode(['error' => 'Task not found']);
    exit;
}

// Find related tasks in the same breakdown element
$sql = "SELECT
    st.ScheduleTaskID,
    p.JobNumber,
    sd.Description as TaskDescription,
    ROUND(st.PercentCompleted * 100, 2) as PercentComplete,
    st.ActualStartDate,
    st.ActualEndDate,
    resources.Description as ResourceName
FROM
    scheduletasks st
    INNER JOIN schedulebreakdownelements sbde ON sbde.ScheduleBreakdownElementID = st.ScheduleBreakdownElementID
    INNER JOIN projects p ON p.ProjectID = st.ProjectID
    INNER JOIN scheduledescriptions sd ON sd.ScheduleDescriptionID = st.ScheduleDescriptionID
    INNER JOIN resources ON resources.ResourceID = st.ResourceID
WHERE
    sbde.ScheduleBreakdownElementID = ? 
    AND st.ScheduleTaskID != ?
ORDER BY
    st.ActualStartDate";

$stmt = $db->prepare($sql);
$stmt->execute([$task['ScheduleBreakdownElementID'], $taskId]);
$relatedTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format response
$response = [
    'task' => $task,
    'relatedTasks' => $relatedTasks
];

echo json_encode($response, JSON_PRETTY_PRINT);
?>