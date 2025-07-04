<?php
require_once 'medoo_db.php';

$resourceId = $_GET['resourceId'] ?? null;
header('Content-Type: application/json');
if ($resourceId) {
    $tasks = $tkdb->query("
        SELECT 
            sts.ScheduleTaskID,
            p.JobNumber,
            p.JobStatusID,
            p.JobDescription AS ProjectDescription,
            sd.Description AS ScheduleDescription,
            ROUND(sts.PercentCompleted * 100) AS PercentCompleted,
            resources.Description AS ResourceDescription,
            sts.ActualStartDate as StartByDate,
            sts.ActualEndDate as EndByDate,
            sts.ActualDuration,
            sts.StatusLink,
            sts.Level,
            sts.Priority,
            sts.TaskType,
            sts.ParentScheduleTaskID,
            parent_sd.Description AS ParentDescription,
            p.GroupName as PM,
            p.GroupName2 as ProjectStatus,
            sts.ResourceID,
            snt.NoteText,
            sbtn.DateTimeCreated as NoteTime
        FROM
            scheduletasks sts
        JOIN
            projects p ON sts.ProjectID = p.ProjectID
        LEFT JOIN
            scheduledescriptions sd ON sts.ScheduleDescriptionID = sd.ScheduleDescriptionID
        LEFT JOIN
            scheduletasks parent_sts ON sts.ParentScheduleTaskID = parent_sts.ScheduleTaskID
        LEFT JOIN
            scheduledescriptions parent_sd ON parent_sts.ScheduleDescriptionID = parent_sd.ScheduleDescriptionID
        LEFT JOIN
            resources ON sts.ResourceID = resources.ResourceID
        LEFT JOIN schedulebasetasknotes as sbtn ON sbtn.ScheduleBaseTaskID = sts.ScheduleBaseTaskID
        LEFT JOIN schedulenotetexts as snt ON sbtn.ScheduleNoteTextID = snt.ScheduleNoteTextID
        WHERE 
            (p.JobStatusID = 1 OR p.JobStatusID = 10 OR p.JobStatusID = 13)
            AND sts.ResourceID = :resourceId
        ORDER BY 
            sts.ActualEndDate ASC, sts.Level ASC, sts.Priority ASC
    ", [":resourceId" => $resourceId])->fetchAll(PDO::FETCH_ASSOC);

    // Process tasks to group and nest notes
    $processedTasks = [];
    $taskMap = [];

    foreach ($tasks as $task) {
        $scheduleTaskId = $task['ScheduleTaskID'];

        if (!isset($taskMap[$scheduleTaskId])) {
            // This is a new task
            $taskMap[$scheduleTaskId] = $task;
            $taskMap[$scheduleTaskId]['Notes'] = [];
            $taskMap[$scheduleTaskId]['TaskPath'] = ''; // Initialize TaskPath
            if ($task['NoteText'] !== null) {
                $taskMap[$scheduleTaskId]['Notes'][] = [
                    'NoteText' => $task['NoteText'],
                    'time' => $task['NoteTime']
                ];
            }
            unset($taskMap[$scheduleTaskId]['NoteText']);
            $processedTasks[] = &$taskMap[$scheduleTaskId];
        } else {
            // This task already exists, just add the note if it's not null
            if ($task['NoteText'] !== null) {
                $taskMap[$scheduleTaskId]['Notes'][] = [
                    'NoteText' => $task['NoteText'],
                    'time' => $task['NoteTime']
                ];
            }
        }
    }

    // Build task hierarchy
    $rootTasks = [];
    foreach ($processedTasks as &$task) {
        $task['children'] = [];
        $parentId = $task['ParentScheduleTaskID'];
        if ($parentId && isset($taskMap[$parentId])) {
            $taskMap[$parentId]['children'][] = &$task;
        } else {
            $rootTasks[] = &$task;
        }
    }

    // Function to build the task path
    // Function to build the task path
    // Function to build the task path
    function buildTaskPath(&$task, $taskMap) {
        // If Level is 1, just use the task's own description
        if ($task['Level'] == 1) {
            $task['TaskPath'] = $task['ScheduleDescription'];
            return;
        }

        // For Level > 1, use parent description and current description
        if ($task['Level'] > 1) {
            $task['TaskPath'] = $task['ParentDescription'] . ' | <br>' . $task['ScheduleDescription'];
        }
    }

    // Build paths for all processed tasks
    foreach ($processedTasks as &$task) {
        buildTaskPath($task, $taskMap);
    }

    // Function to flatten the tasks
    function flattenTasks($tasks) {
        $result = [];
        foreach ($tasks as $task) {
            $taskCopy = $task;
            unset($taskCopy['children']);
            $result[] = $taskCopy;
            if (!empty($task['children'])) {
                $result = array_merge($result, flattenTasks($task['children']));
            }
        }
        return $result;
    }

    $flattenedTasks = flattenTasks($rootTasks);

    // Sort the flattened tasks based on EndByDate, then Level, then Priority
    usort($flattenedTasks, function($a, $b) {
        $dateComparison = strcmp($a['EndByDate'], $b['EndByDate']);
        if ($dateComparison !== 0) {
            return $dateComparison;
        }
        if ($a['Level'] != $b['Level']) {
            return $a['Level'] - $b['Level'];
        }
        return $b['Priority'] - $a['Priority'];
    });

    echo json_encode($flattenedTasks, JSON_PRETTY_PRINT);
} else {
    echo json_encode(['error' => 'No resource ID provided']);
}