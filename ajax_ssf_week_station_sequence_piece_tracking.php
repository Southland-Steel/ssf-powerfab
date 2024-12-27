<?php
// ajax_grid_week_station_sequence_piece_tracking.php

require_once 'medoo_ssf_db.php';
require_once 'inc_station_distributions.php';

$workweek = $_GET['workweek'];

// Your existing SQL query here
$sql = "SELECT STRAIGHT_JOIN
    pcj.JobNumber,
    REPLACE(pcseq.Description, CHAR(1), '') AS Sequence,
    REPLACE(pcseq.LotNumber, CHAR(1), '') AS Lot,
    wp.WorkPackageNumber as WPNumber,
    stations.Description as StationName,
    REPLACE(pca.MainMark, CHAR(1),'') AS AssemblyMark,
    REPLACE(pci.PieceMark, CHAR(1), '') AS PieceMark,
    pciseq.Quantity as AssemblySequenceQuantity,
    pciss.QuantityCompleted,
    ROUND(pci.Quantity / pca.AssemblyQuantity) as AssemblyEachQuantity,
    pciseq.ProductionControlItemSequenceID,
    pcj.productionControlID,
    pcseq.WorkPackageID,
    pcseq.SequenceID,
    ROUND(pci.Quantity / pca.AssemblyQuantity * pciseq.Quantity) as TotalPieceMarkQuantity, 
    pca.AssemblyQuantity as JobAssemblyQuantity,
    pca.MainPieceProductionControlItemID,
    pci.Quantity as JobQuantity,
    pci.ProductionControlItemID,
    pciss.ProductionControlItemStationSummaryID, 
    rt.Route as RouteName
FROM (
         SELECT WorkPackageID
         FROM workpackages
         WHERE Group2 = '{$workweek}'
         AND (Group1 IS NULL OR Group1 != 'CLOSEOUT')
     ) AS filtered_wp
         INNER JOIN productioncontrolsequences as pcseq ON pcseq.WorkPackageID = filtered_wp.WorkPackageID
         INNER JOIN productioncontroljobs AS pcj ON pcj.ProductionControlID = pcseq.ProductionControlID
         INNER JOIN productioncontrolitemsequences as pciseq ON pciseq.SequenceID = pcseq.SequenceID
         INNER JOIN productioncontrolassemblies as pca ON pciseq.ProductionControlAssemblyID = pca.ProductionControlAssemblyID
         INNER JOIN productioncontrolitems as pci ON pci.ProductionControlAssemblyID = pca.ProductionControlAssemblyID
         INNER JOIN productioncontrolitemstationsummary as pciss
                    ON pci.ProductionControlItemID = pciss.ProductionControlItemID
                        AND pcj.ProductionControlID = pciss.ProductionControlID
                        AND pciss.SequenceID = pciseq.SequenceID
         INNER JOIN stations
                    ON pciss.StationID = stations.StationID
                        AND stations.Description IN ('Cut', 'Kit-Up','CNC')
         LEFT JOIN workpackages as wp ON wp.WorkPackageID = pcseq.WorkPackageID
         LEFT JOIN routes as rt ON rt.RouteID = pci.RouteID
LIMIT 5000
;";

$tkdata = $tkdb->query($sql)->fetchAll(PDO::FETCH_ASSOC);

function gcd($a, $b) {
    while ($b != 0) {
        $t = $b;
        $b = $a % $b;
        $a = $t;
    }
    return $a;
}

function lcm($a, $b) {
    return ($a * $b) / gcd($a, $b);
}

function lcmArray($arr) {
    $result = $arr[0];
    for ($i = 1; $i < count($arr); $i++) {
        $result = lcm($result, $arr[$i]);
    }
    return $result;
}

$processedData = [];

foreach ($tkdata as $row) {
    $sequenceId = $row['ProductionControlItemSequenceID'];
    $stationName = $row['StationName'];
    $pieceMarkId = $row['ProductionControlItemID'];

    if (!isset($processedData[$sequenceId])) {
        $processedData[$sequenceId] = [
            'ProductionControlItemSequenceID' => $sequenceId,
            'JobNumber' => $row['JobNumber'],
            'Sequence' => $row['Sequence'],
            'Lot' => $row['Lot'],
            'WPNumber' => $row['WPNumber'],
            'AssemblyMark' => $row['AssemblyMark'],
            'MainPieceProductionControlItemID' => $row['MainPieceProductionControlItemID'],
            'AssemblySequenceQuantity' => $row['AssemblySequenceQuantity'],
            'JobAssemblyQuantity' => $row['JobAssemblyQuantity'],
            'Stations' => []
        ];
    }

    // Create separate entries for Traps and Cut
    if ($stationName === 'Cut') {
        $trapStationName = 'Traps Cut';
        $cutStationName = 'Cut';

        if (!isset($processedData[$sequenceId]['Stations'][$trapStationName])) {
            $processedData[$sequenceId]['Stations'][$trapStationName] = [
                'StationName' => $trapStationName,
                'QuantityCompleted' => 0,
                'StationComplete' => 0,
                'PieceMarks' => [],
                'CompletedAssemblies' => 0
            ];
        }

        if (!isset($processedData[$sequenceId]['Stations'][$cutStationName])) {
            $processedData[$sequenceId]['Stations'][$cutStationName] = [
                'StationName' => $cutStationName,
                'QuantityCompleted' => 0,
                'StationComplete' => 0,
                'PieceMarks' => [],
                'CompletedAssemblies' => 0
            ];
        }

        // Determine whether this piece mark belongs to Traps or Cut
        $targetStation = in_array($row['RouteName'], ['Shaft', 'Shaft HW', 'Minor Shaft']) ? $trapStationName : $cutStationName;

        if (!isset($processedData[$sequenceId]['Stations'][$targetStation]['PieceMarks'][$pieceMarkId])) {
            $processedData[$sequenceId]['Stations'][$targetStation]['PieceMarks'][$pieceMarkId] = [
                'ProductionControlItemID' => $pieceMarkId,
                'PieceMark' => $row['PieceMark'],
                'AssemblyEachQuantity' => $row['AssemblyEachQuantity'],
                'TotalPieceMarkQuantity' => $row['TotalPieceMarkQuantity'],
                'JobQuantity' => $row['JobQuantity'],
                'Route' => $row['RouteName'],
                'CompletedQuantity' => $row['QuantityCompleted']
            ];
        }

        // Update QuantityCompleted for the target station
        $processedData[$sequenceId]['Stations'][$targetStation]['QuantityCompleted'] += $row['QuantityCompleted'];
    } else {
        // For other stations (CNC, Kit-Up), keep the existing logic
        if (!isset($processedData[$sequenceId]['Stations'][$stationName])) {
            $processedData[$sequenceId]['Stations'][$stationName] = [
                'StationName' => $stationName,
                'QuantityCompleted' => $row['QuantityCompleted'],
                'StationComplete' => 0,
                'PieceMarks' => [],
                'CompletedAssemblies' => 0
            ];
        }

        if (!isset($processedData[$sequenceId]['Stations'][$stationName]['PieceMarks'][$pieceMarkId])) {
            $processedData[$sequenceId]['Stations'][$stationName]['PieceMarks'][$pieceMarkId] = [
                'ProductionControlItemID' => $pieceMarkId,
                'PieceMark' => $row['PieceMark'],
                'AssemblyEachQuantity' => $row['AssemblyEachQuantity'],
                'TotalPieceMarkQuantity' => $row['TotalPieceMarkQuantity'],
                'JobQuantity' => $row['JobQuantity'],
                'Route' => $row['RouteName'],
                'CompletedQuantity' => $row['QuantityCompleted']
            ];
        }
    }
}

// Calculate completed assemblies and StationComplete for each station
foreach ($processedData as &$sequence) {
    foreach ($sequence['Stations'] as &$station) {
        $pieceMarkCompletions = [];
        foreach ($station['PieceMarks'] as $pieceMark) {
            $completedForAssembly = floor($pieceMark['CompletedQuantity'] / $pieceMark['AssemblyEachQuantity']);
            $pieceMarkCompletions[] = $completedForAssembly;
        }
        $station['CompletedAssemblies'] = !empty($pieceMarkCompletions) ? min($pieceMarkCompletions) : 0;

        // Calculate StationComplete
        $station['StationComplete'] = ($station['CompletedAssemblies'] >= $sequence['AssemblySequenceQuantity']) ? 1 : 0;
    }
}

// Convert associative arrays to indexed arrays for JSON encoding
foreach ($processedData as &$sequence) {
    $sequence['Stations'] = array_values($sequence['Stations']);
    foreach ($sequence['Stations'] as &$station) {
        $station['PieceMarks'] = array_values($station['PieceMarks']);
    }
}
$processedData = array_values($processedData);

header('Content-Type: application/json');
echo json_encode($processedData, JSON_PRETTY_PRINT);
?>