<?php

require_once '../config_ssf_db.php';


$resources = $db->query("
    SELECT DISTINCT
        r.ResourceID, r.Description AS ResourceDescription, p.JobStatusID
    FROM projects p
    JOIN scheduletasks sts ON p.ProjectID = sts.ProjectID
    JOIN resources r ON sts.ResourceID = r.ResourceID
WHERE p.JobStatusID IN (1) and sts.ResourceID <> 45
    ORDER BY
        p.ProjectID, r.ResourceID
")->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($resources);