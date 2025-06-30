<?php
require_once '../config_ssf_db.php';

// Get workshopid from parameter, default to 1 if not provided
$workshopid = isset($_GET['workshopid']) ? intval($_GET['workshopid']) : 1;

// Raw SQL query
$sql = "SELECT
    wp.WorkPackageID,
    pcj.ProductionControlID,
    p.JobNumber,
    p.ProjectID,
    wp.WorkPackageNumber,
    wp.Description as WorkPackageDescription,
    wp.Group2 as WorkWeek,
    wp.ReleasedToFab,
    wp.OnHold,
    wp.Weight,
    wp.Hours,
    p.GroupName2 as ReviewStatus,
    wp.Priority,
    wp.Notes
    FROM workpackages AS wp
INNER JOIN productioncontroljobs as pcj ON pcj.ProductionControlID = wp.ProductionControlID
INNER JOIN projects as p ON pcj.ProjectID = p.ProjectID
WHERE wp.Completed = 0 AND wp.WorkshopID = {$workshopid}
ORDER BY p.JobNumber, wp.Group2";

$tkdata = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$processedData = [
    'assigned' => [],
    'unassigned' => [],
    'workWeeks' => [],
    'jobSortData' => []
];

foreach ($tkdata as $row) {
    $jobNumber = $row['JobNumber'];
    $workWeek = $row['WorkWeek'];
    $workPackageNumber = $row['WorkPackageNumber'];
    $weightLbs = $row['Weight'] * 2.20462; // Convert kg to lbs

    $workPackageData = [
        'workPackageNumber' => $workPackageNumber,
        'description' => htmlspecialchars($row['WorkPackageDescription'], ENT_QUOTES, 'UTF-8'),
        'releasedToFab' => $row['ReleasedToFab'],
        'onHold' => $row['OnHold'],
        'weight' => number_format($weightLbs, 0, '.', ','),
        'hours' => Round($row['Hours']),
        'reviewstatus' => $row['ReviewStatus'],
        'priority' => $row['Priority'],
        'notes' => htmlspecialchars($row['Notes'], ENT_QUOTES, 'UTF-8')
    ];

    if ($workWeek === null) {
        if (!isset($processedData['unassigned'][$jobNumber])) {
            $processedData['unassigned'][$jobNumber] = [];
        }
        $processedData['unassigned'][$jobNumber][] = $workPackageData;
    } else {
        if (!isset($processedData['assigned'][$jobNumber])) {
            $processedData['assigned'][$jobNumber] = [];
        }
        if (!isset($processedData['assigned'][$jobNumber][$workWeek])) {
            $processedData['assigned'][$jobNumber][$workWeek] = [];
        }
        $processedData['assigned'][$jobNumber][$workWeek][] = $workPackageData;

        if (!isset($processedData['workWeeks'][$workWeek])) {
            $processedData['workWeeks'][$workWeek] = [
                'name' => $workWeek,
                'hours' => Round($row['Hours']),
                'weight' => Round($weightLbs)
            ];
        } else {
            $processedData['workWeeks'][$workWeek]['hours'] += Round($row['Hours']);
            $processedData['workWeeks'][$workWeek]['weight'] += Round($weightLbs);
        }

        // Update job sort data
        if (!isset($processedData['jobSortData'][$jobNumber])) {
            $processedData['jobSortData'][$jobNumber] = ['earliest' => $workWeek, 'latest' => $workWeek];
        } else {
            $processedData['jobSortData'][$jobNumber]['earliest'] = min($processedData['jobSortData'][$jobNumber]['earliest'], $workWeek);
            $processedData['jobSortData'][$jobNumber]['latest'] = max($processedData['jobSortData'][$jobNumber]['latest'], $workWeek);
        }
    }
}

// Sort work weeks
sort($processedData['workWeeks']);

// Sort jobs by earliest workweek, then by latest workweek within the same earliest workweek group
uksort($processedData['assigned'], function($a, $b) use ($processedData) {
    $earliestDiff = $processedData['jobSortData'][$a]['earliest'] - $processedData['jobSortData'][$b]['earliest'];
    if ($earliestDiff == 0) {
        // If earliest workweeks are the same, sort by latest workweek
        return $processedData['jobSortData'][$a]['latest'] - $processedData['jobSortData'][$b]['latest'];
    }
    return $earliestDiff;
});

foreach ($processedData['assigned'] as $jobNumber => $jobData) {
    $processedData['assignedOrder'][] = $jobNumber;
}

header('Content-Type: application/json');
echo json_encode($processedData, JSON_PRETTY_PRINT);