<?php

require_once 'medoo_grid_db.php';


$resources = $tkdb->query("
    SELECT DISTINCT
        r.ResourceID,
        r.Description AS ResourceDescription,
        p.JobStatusID
    FROM projects as p
    INNER JOIN jobstatuses as js on js.JobStatusID = p.JobStatusID
    INNER JOIN scheduletasks as sts ON p.ProjectID = sts.ProjectID
    INNER JOIN resources as r ON sts.ResourceID = r.ResourceID
    WHERE p.JobStatusID = 1 and r.Description <> 'Fabrication' and js.Purpose = 0
    ORDER BY p.ProjectID, r.ResourceID
")->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($resources);