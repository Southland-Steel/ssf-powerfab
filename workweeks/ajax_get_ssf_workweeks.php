<?php
// ajax_get_ssf_workweeks2.php

require_once '../config_ssf_db.php';

$workweek = $_GET['workweek'];

$sql = "SELECT 
    pcj.JobNumber,
	REPLACE(pcseq.Description, CHAR(1), '') as SequenceDescription,
    REPLACE(pcseq.LotNumber, CHAR(1), '') as LotNumber,
    wp.WorkPackageNumber,
    wp.Group2 as WorkWeek,
    wp.Group1 as Bay,
    ws.Description as WorkshopDescription,
    REPLACE(pci.MainMark,CHAR(1),'') AS MainMark,
    REPLACE(pci.PieceMark,CHAR(1),'') AS PieceMark,
    shapes.Shape,
    pci.DimensionString,
    rt.Route as RouteName,
    stations.Description as StationDescription,
    pciss.QuantityCompleted StationQuantityCompleted,
    pciss.TotalQuantity as StationTotalQuantity,
    pciss.LastDateCompleted,
    ROUND(pci.Length / 25.4,3) as LengthInches,
    pciseq.Quantity as SequenceMainMarkQuantity,
    ROUND(pca.AssemblyWeightEach*2.20462,3) as NetAssemblyWeightEach,
    ROUND(pca.AssemblyManHoursEach,3) as AssemblyManHoursEach,
    pci.MainPieceQuantity,
    wp.Group1 as Bay,
    wp.ReleasedToFab,
    wp.OnHold,
    wp.ProductionControlID,
    pcseq.SequenceID,
    pciseq.ProductionControlItemSequenceID,
    wp.WorkPackageID,
	wp.WorkshopID,
    pciseq.ProductionControlAssemblyID,
    pca.ProductionControlAssemblyID,
    pca.MainPieceProductionControlItemID,
    pci.ProductionControlItemID,
    pciss.ProductionControlItemStationSummaryID,
    pciss.StationID,
    pccat.Description as Category
 FROM workpackages as wp 
	INNER JOIN productioncontroljobs as pcj ON pcj.ProductionControlID = wp.ProductionControlID
    INNER JOIN workshops as ws ON ws.WorkshopID = wp.WorkShopID
    INNER JOIN productioncontrolsequences as pcseq ON pcseq.WorkPackageID = wp.WorkPackageID AND pcseq.AssemblyQuantity > 0
    INNER JOIN productioncontrolitemsequences as pciseq ON pciseq.SequenceID = pcseq.SequenceID AND pciseq.Quantity > 0
    INNER JOIN productioncontrolassemblies as pca ON pca.ProductionControlAssemblyID = pciseq.ProductionControlAssemblyID
    INNER JOIN productioncontrolitems as pci ON pci.ProductionControlAssemblyID = pca.ProductionControlAssemblyID
    INNER JOIN shapes ON shapes.ShapeID = pci.ShapeID AND shapes.Shape NOT IN ('HS','NU','WA')
    INNER JOIN routes as rt on rt.RouteID = pci.RouteID
    INNER JOIN routestations on routestations.RouteID = rt.RouteID
    LEFT JOIN productioncontrolcategories as pccat on pccat.CategoryID = pci.CategoryID
    INNER JOIN productioncontrolitemstationsummary as pciss ON pciss.ProductionControlItemID = pci.ProductionControlItemID AND pciss.SequenceID = pcseq.SequenceID AND pciss.ProductionControlID = pcseq.ProductionControlID and routestations.StationID = pciss.StationID
    INNER JOIN stations ON stations.StationID = pciss.StationID AND stations.Description not in ('IFA','IFF','CUT','NESTED')
 WHERE wp.Group2 = ${workweek} AND pcseq.AssemblyQuantity > 0 AND pci.MainPiece = 1 and wp.WorkshopID = 1";

$result = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$grouped_data = [];
$stations = [];

foreach ($result as $row) {
    $pciseq_id = $row['ProductionControlItemSequenceID'];

    if (!isset($stations[$row['StationDescription']])) {
        $stations[$row['StationDescription']] = 0;
    }
    $stations[$row['StationDescription']] += 1;

    // If this is the first time we're seeing this ID, initialize the main record
    if (!isset($grouped_data[$pciseq_id])) {
        $grouped_data[$pciseq_id] = [
            'JobNumber' => $row['JobNumber'],
            'SequenceDescription' => $row['SequenceDescription'],
            'LotNumber' => $row['LotNumber'],
            'WorkPackageNumber' => $row['WorkPackageNumber'],
            'WorkWeek' => $row['WorkWeek'],
            'Bay' => $row['Bay'],
            'WorkshopDescription' => $row['WorkshopDescription'],
            'MainMark' => $row['MainMark'],
            'Category' => $row['Category'],
            'PieceMark' => $row['PieceMark'],
            'Shape' => $row['Shape'],
            'DimensionString' => $row['DimensionString'],
            'RouteName' => $row['RouteName'],
            'LengthInches' => $row['LengthInches'],
            'SequenceMainMarkQuantity' => $row['SequenceMainMarkQuantity'],
            'NetAssemblyWeightEach' => $row['NetAssemblyWeightEach'],
            'TotalNetWeight' => $row['NetAssemblyWeightEach'] * $row['SequenceMainMarkQuantity'],
            'AssemblyManHoursEach' => $row['AssemblyManHoursEach'],
            'TotalEstimatedManHours' => $row['AssemblyManHoursEach'] * $row['SequenceMainMarkQuantity'],
            'MainPieceQuantity' => $row['MainPieceQuantity'],
            'ReleasedToFab' => $row['ReleasedToFab'],
            'OnHold' => $row['OnHold'],
            'ProductionControlID' => $row['ProductionControlID'],
            'SequenceID' => $row['SequenceID'],
            'ProductionControlItemSequenceID' => $row['ProductionControlItemSequenceID'],
            'WorkPackageID' => $row['WorkPackageID'],
            'WorkshopID' => $row['WorkshopID'],
            'ProductionControlAssemblyID' => $row['ProductionControlAssemblyID'],
            'MainPieceProductionControlItemID' => $row['MainPieceProductionControlItemID'],
            'ProductionControlItemID' => $row['ProductionControlItemID'],
            'ProductionControlAssemblyID' => $row['ProductionControlAssemblyID'],
            'isCompleted' => false, // is completed if the final qc station is completed
            'Stations' => []  // Initialize empty array for station data
        ];
    }

    // Add station data to the stations array
    $grouped_data[$pciseq_id]['Stations'][] = [
        'StationDescription' => $row['StationDescription'],
        'MainMark' => $row['MainMark'],
        'PieceMark' => $row['PieceMark'],
        'StationQuantityCompleted' => $row['StationQuantityCompleted'],
        'LastDateCompleted' => $row['LastDateCompleted'],
        'StationTotalQuantity' => $row['StationTotalQuantity'],
        'ProductionControlItemStationSummaryID' => $row['ProductionControlItemStationSummaryID'],
        'StationID' => $row['StationID']
    ];

    if ($row['StationDescription'] === 'FINAL QC' && $row['StationQuantityCompleted'] >= $row['StationTotalQuantity']) {
        $grouped_data[$pciseq_id]['isCompleted'] = true;
    }
}

// Convert to array and remove keys
$final_result = array_values($grouped_data);

$return_data['stations'] = $stations;
$return_data['items'] = $final_result;


header('Content-Type: application/json');
echo json_encode($return_data, JSON_PRETTY_PRINT);