<?php

require_once 'config_ssf_db.php';
require_once 'converters.php';
$stationIds = '29,91,92';
$defaultDate = date('Y-m-d');
$date = isset($_GET['date']) ? $_GET['date'] : $defaultDate;

// Raw SQL query
$innerQuery = "SELECT 
    DISTINCT pci.ProductionControlItemID
    FROM fabrication.productioncontrolitemstations as pcis
    LEFT JOIN productioncontrolitems as pci ON pcis.ProductionControlID = pci.ProductionControlID
            AND pcis.MainMark = pci.MainMark
            AND pci.MainPiece = 1
    WHERE 
        DateCompleted = '$date'
        AND pcis.StationID IN ($stationIds)
        ";

$sql = "SELECT
        pciss.ProductionControlItemStationSummaryID,
        pciss.ProductionControlItemID,
        pci.ProductionControlID,
        pciss.SequenceID,
        pciss.StationID,
        pcj.JobNumber,
        pcj.JobDescription,
        routes.Route,
        REPLACE(pcs.Description, CHAR(1), '') as SequenceDescription,
        REPLACE(pcs.LotNumber, CHAR(1), '') as LotNumber,
        stations.Description as StationName,
        REPLACE(pci.MainMark, CHAR(1), '') as MainMark,
        REPLACE(pci.PieceMark, CHAR(1), '') as PieceMark,
        pciss.TotalQuantity,
        pciss.QuantityCompleted,
        pciss.FailedInspectionTestQuantity,
        CASE 
            WHEN EXISTS (
                SELECT 1
                FROM productioncontrolitemstations pcis_hit
                WHERE pcis_hit.ProductionControlID = pci.ProductionControlID
                    AND pcis_hit.MainMark = pci.MainMark
                    AND pcis_hit.StationID = pciss.StationID
                    AND pcis_hit.DateCompleted = '{$date}'
            ) THEN 1 
            ELSE 0 
        END as IsHit
    FROM fabrication.productioncontrolitemstationsummary as pciss
    LEFT JOIN productioncontrolitems as pci ON pci.ProductionControlItemID = pciss.ProductionControlItemID
    LEFT JOIN productioncontroljobs as pcj ON pcj.ProductionControlID = pci.ProductionControlID
    LEFT JOIN productioncontrolsequences as pcs ON pcs.SequenceID = pciss.SequenceID
    LEFT JOIN stations ON stations.stationID = pciss.StationID
    LEFT JOIN routes ON routes.RouteID = pci.RouteID
    LEFT JOIN productioncontrolitemstations as pcis ON pcis.ProductionControlID = pci.ProductionControlID
        AND pcis.MainMark = pci.MainMark
        AND pcis.StationID = pciss.StationID
    WHERE 
        pciss.ProductionControlItemID IN ($innerQuery)
        AND pciss.StationID IN ($stationIds)
        ";

$stmt = $db->prepare($sql);
$stmt->execute();
$tkdata = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Define the desired station order
$stationOrder = ['FIT', 'WELD', 'FINAL QC'];

// Collect unique stations
$uniqueStations = array();
foreach ($tkdata as $item) {
    if (!in_array($item['StationName'], $uniqueStations)) {
        $uniqueStations[] = $item['StationName'];
    }
}

// Function to get the order index of a station
function getStationOrderIndex($stationName, $stationOrder) {
    $index = array_search($stationName, $stationOrder);
    return $index !== false ? $index : count($stationOrder); // Put unknown stations at the end
}

// Sort the unique stations based on the defined order
usort($uniqueStations, function($a, $b) use ($stationOrder) {
    return getStationOrderIndex($a, $stationOrder) - getStationOrderIndex($b, $stationOrder);
});

function createStationSummary($item) {
    return [
        'ProductionControlItemStationSummaryID' => $item['ProductionControlItemStationSummaryID'],
        'StationID' => $item['StationID'],
        'StationName' => $item['StationName'],
        'TotalQuantity' => $item['TotalQuantity'],
        'QuantityCompleted' => $item['QuantityCompleted'],
        'FailedInspectionTestQuantity' => $item['FailedInspectionTestQuantity'],
        'IsHit' => $item['IsHit']
    ];
}

function compareJobNumbers($a, $b) {
    return strnatcmp($a['JobNumber'], $b['JobNumber']);
}

// Group the data by ProductionControlItemID
$groupedData = [];
foreach ($tkdata as $item) {
    $productionControlItemId = $item['ProductionControlItemID'];

    if (!isset($groupedData[$productionControlItemId])) {
        $groupedData[$productionControlItemId] = [
            'ProductionControlItemID' => $productionControlItemId,
            'ProductionControlID' => $item['ProductionControlID'],
            'SequenceID' => $item['SequenceID'],
            'JobNumber' => $item['JobNumber'],
            'JobDescription' => $item['JobDescription'],
            'SequenceDescription' => $item['SequenceDescription'],
            'LotNumber' => $item['LotNumber'],
            'MainMark' => $item['MainMark'],
            'PieceMark' => $item['PieceMark'],
            'Route' => $item['Route'],
            'Stations' => []
        ];
    }

    $groupedData[$productionControlItemId]['Stations'][] = createStationSummary($item);
}

uasort($groupedData, 'compareJobNumbers');

// Prepare the final result
$result = [
    'stations' => $uniqueStations,
    'data' => array_values($groupedData)
];

// Output the result as JSON
header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT);