<?php
require_once '../config_ssf_db.php';

$resourceId = $_GET['resourceId'] ?? null;
header('Content-Type: application/json');
if ($resourceId) {
    $query = "
        SELECT DISTINCT 
        p.JobNumber as JobNumber,
        p.JobStatusID,
        p.JobDescription as ProjectDescription,
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
            WHEN sbde.Level = 1 THEN 0 -- Level 1: No LotNumber
            WHEN sbde.Level = 2 THEN sbdeval.Description -- Level 2: Current description is LotNumber
        END AS LotNumber,
        CASE 
            WHEN sbde.Level = 1 and sts.Level = 3 THEN sd.Description
            WHEN sbde.Level = 1 and sts.Level = 4 THEN concat(pdesc.Description, '->', sd.Description)
            WHEN sbde.Level = 2 and sts.Level = 4 THEN sd.Description
            WHEN sbde.Level = 2 and sts.Level = 5 THEN concat(pdesc.Description, '->', sd.Description)
        END AS TaskPath,
        sbde.Level, -- Include level to distinguish between level 1 and level 2 records
        sbde.ScheduleBreakdownElementID as SBDEelementId,
        sbde.ParentScheduleBreakdownElementID as SBDEparentId,
        sbde.Priority as priority,
        p.GroupName AS PM,
        sts.ScheduleTaskID,
        sd.Description AS taskDescription,
        sbdeval.Description as description,
        sts.ActualStartDate as StartByDate,
        sts.ActualEndDate as EndByDate,
        sts.ActualDuration,
        sts.ParentScheduleTaskID,
        sts.PercentCompleted,
        pdesc.Description as ParentDescription
    FROM fabrication.schedulebreakdownelements AS sbde
    INNER JOIN schedulebaselines as sbl ON sbl.ScheduleBaselineID = sbde.ScheduleBaselineID AND sbl.IsCurrent = 1
    INNER JOIN scheduledescriptions AS sbdeval ON sbdeval.ScheduleDescriptionID = sbde.ScheduleBreakdownValueID
    INNER JOIN scheduletasks as sts ON sts.ScheduleBreakdownElementID = sbde.ScheduleBreakdownElementID
    INNER JOIN resources ON resources.ResourceID = sts.ResourceID
    INNER JOIN projects as p ON p.ProjectID = sts.ProjectID
    INNER JOIN scheduledescriptions as sd ON sd.ScheduleDescriptionID = sts.ScheduleDescriptionID
    INNER JOIN scheduletasks as psts ON psts.ScheduleTaskID = sts.ParentScheduleTaskID
    INNER JOIN scheduledescriptions as pdesc ON pdesc.ScheduleDescriptionID = psts.ScheduleDescriptionID
    WHERE 
        p.JobStatusID IN (1,6)
        AND sbde.Level < 3
        AND sts.PercentCompleted < 0.99
        AND sts.ResourceID = :resourceId
        AND sbdeval.Description IS NOT NULL
        AND sts.ActualStartDate IS NOT NULL
        AND sts.ActualEndDate IS NOT NULL
	ORDER BY sts.ActualEndDate ASC, sbde.Priority
    ";


    $stmt = $db->prepare($query);
    $stmt->execute([
        ':resourceId' => $resourceId
    ]);

    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($tasks, JSON_PRETTY_PRINT);
} else {
    echo json_encode(['error' => 'No resource ID provided']);
}