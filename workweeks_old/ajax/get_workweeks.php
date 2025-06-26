<?php
// workweeks/ajax/get_workweeks.php

require_once '../../config_ssf_db.php';

// Query the database to fetch distinct WorkPackageNumber
$query = "
    SELECT DISTINCT Group2 as WorkWeeks 
    FROM workpackages 
    INNER JOIN productioncontroljobs as pcj ON pcj.productionControlID = workpackages.productionControlID 
    WHERE Completed = 0 AND OnHold = 0 
    ORDER BY WorkWeeks ASC;
";

$result = $db->query($query);
$resources = $result->fetchAll(PDO::FETCH_ASSOC);

$weeks = array_filter(array_column($resources, 'WorkWeeks'), function($week) {
    return $week !== null && $week !== '';
});

sort($weeks);

$response = [
    'weeks' => $weeks
];

header('Content-Type: application/json');
echo json_encode($response);