<?php
require_once 'config_ssf_db.php';
require_once 'inc_station_distributions.php';

// Set default dates
$defaultBeginDate = date('Y-m-d', strtotime('-30 days'));
$defaultEndDate = date('Y-m-d');

// Get begin and end dates from GET parameters, or use defaults
$beginDate = $_GET['begin_date'] ?? $defaultBeginDate;
$endDate = $_GET['end_date'] ?? $defaultEndDate;

// Validate date format
$dateFormat = 'Y-m-d';
$beginDateTime = DateTime::createFromFormat($dateFormat, $beginDate);
$endDateTime = DateTime::createFromFormat($dateFormat, $endDate);

if (!$beginDateTime || !$endDateTime || $beginDateTime > $endDateTime) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid date range provided']);
    exit;
}

$sql = "SELECT pcis.ProductionControlItemStationID,
    pcis.ProductionControlID,
    pcj.JobNumber,
    pcj.JobDescription,
    REPLACE(pcseq.Description, CHAR(1), '') as SequenceName,
    workpackages.WorkPackageNumber AS WorkPackageName,
    workpackages.Group2 as WorkWeek,
    REPLACE(pca.MainMark, CHAR(1), '') AS AssemblyMainMark,
    pciseq.Quantity as SequenceQuantity,
    pcis.Quantity as StationDaySequenceQuantityInstance,
    routes.Route,
    stations.Description as StationName,
    pcis.DateCompleted,
    pca.AssemblyManHoursEach
FROM fabrication.productioncontrolitemstations as pcis
INNER JOIN productioncontroljobs as pcj ON pcj.ProductionControlID = pcis.ProductionControlID
INNER JOIN productioncontrolsequences as pcseq ON pcseq.SequenceID = pcis.SequenceID
LEFT JOIN workpackages ON workpackages.WorkPackageID = pcseq.WorkPackageID
INNER JOIN productioncontrolitemsequences as pciseq ON pciseq.SequenceID = pcis.SequenceID AND pcis.MainMark = pciseq.MainMark
INNER JOIN stations ON stations.StationID = pcis.StationID
INNER JOIN productioncontrolassemblies as pca ON pca.ProductionControlAssemblyID = pciseq.ProductionControlAssemblyID
INNER JOIN productioncontrolitems as pci ON pci.ProductionControlItemID = pca.MainPieceProductionControlItemID
INNER JOIN routes ON routes.RouteID = pci.RouteID AND routes.IsAssemblyRoute = 1
WHERE pcis.DateCompleted BETWEEN :beginDate AND :endDate AND stations.Description NOT IN ('NDE', 'Cut')";

// First prepare the statement
$stmt = $db->prepare($sql);

// Then execute with parameters
$stmt->execute([
    ':beginDate' => $beginDate,
    ':endDate' => $endDate
]);

// Finally fetch the results
$tkdata = $stmt->fetchAll();

$processedData = [];

foreach ($tkdata as $row) {
    $route = $row['Route'];
    $totalHours = $row['AssemblyManHoursEach'] * $row['StationDaySequenceQuantityInstance'];
    $stationHours = calculateStationHours($route, $totalHours);

    $stationPercentage = 0;
    if (isset($stationHours[$row['StationName']]) && $totalHours > 0) {
        $stationPercentage = ($stationHours[$row['StationName']] / $totalHours) * 100;
    }

    $processedRow = [
        'JobNumber' => $row['JobNumber'],
        'JobDescription' => $row['JobDescription'],
        'SequenceName' => $row['SequenceName'],
        'WorkPackageName' => $row['WorkPackageName'],
        'WorkWeek' => $row['WorkWeek'],
        'AssemblyMainMark' => $row['AssemblyMainMark'],
        'SequenceQuantity' => $row['SequenceQuantity'],
        'AssemblyManHoursEach' => $row['AssemblyManHoursEach'],
        'StationDaySequenceQuantityInstance' => $row['StationDaySequenceQuantityInstance'],
        'Route' => $route,
        'StationName' => $row['StationName'],
        'DateCompleted' => $row['DateCompleted'],
        'TotalHours' => $totalHours,
        'CalculatedHours' => $stationHours[$row['StationName']] ?? 0,
        'StationPercentage' => round($stationPercentage, 2)
    ];

    $processedData[] = $processedRow;
}

header('Content-Type: application/json');
echo json_encode($processedData, JSON_PRETTY_PRINT);