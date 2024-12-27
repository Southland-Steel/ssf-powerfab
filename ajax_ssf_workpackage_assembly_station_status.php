<?php
require_once 'config_ssf_db.php';
require_once 'converters.php';

$workPackageID = $_GET['workpackageid'] ?? 37;

// Main query to fetch project data
$sql = "SELECT STRAIGHT_JOIN
    SQL_CALC_FOUND_ROWS
    p.ProjectID,
    p.JobNumber AS ProjectJobNumber,
    pcj.ProductionControlID,
    pcj.JobNumber AS PCJJobNumber,
    p.JobDescription AS ProjectDescription,
    REPLACE(pca.MainMark, CHAR(1), '') AS MainMark,
    routes.Route AS RouteName,
    pca.MainPieceProductionControlItemID,
    pca.AssemblyQuantity AS AssemblyTotalQuantity,
    pciseq.Quantity AS SequenceQuantity,
    pciseq.ProductionControlItemSequenceID,
    pcseq.SequenceID,
    pca.AssemblyWeightEach AS EachNetWeight,
    pca.GrossAssemblyWeightEach AS GrossAssemblyWeightEach,
    (pca.AssemblyWeightEach * pciseq.Quantity) AS TotalNetWeight,
    (pca.GrossAssemblyWeightEach * pciseq.Quantity) AS TotalGrossWeight,
    pca.AssemblyManHoursEach AS AssemblyManHoursEach,
    (pca.AssemblyManHoursEach * pciseq.Quantity) AS TotalEstimatedManHours,
    s.StationNumber,
    s.Description AS StationDescription,
    pciss.PositionInRoute,
    pciss.TotalQuantity AS StationTotalQuantity,
    pciss.QuantityCompleted AS StationQuantityCompleted,
    (pciss.QuantityCompleted * pca.AssemblyWeightEach) AS StationCompletedNetWeight,
    (pciss.QuantityCompleted * pca.GrossAssemblyWeightEach) AS StationCompletedGrossWeight,
    pciss.Hours AS StationActualHours,
    REPLACE(pcseq.Description, CHAR(1), '') AS SequenceDescription,
    REPLACE(pcseq.LotNumber, CHAR(1), '') AS LotNumber,
    workpackages.WorkPackageNumber,
    workpackages.ReleasedToFab,
    workpackages.Group1 as BayName
FROM
    /* Start with the filtering table to reduce initial dataset */
    workpackages 
    INNER JOIN productioncontrolsequences pcseq 
        ON workpackages.WorkPackageID = pcseq.WorkPackageID 
        AND pcseq.AssemblyQuantity > 0
    INNER JOIN productioncontroljobs pcj 
        ON pcseq.ProductionControlID = pcj.ProductionControlID
    INNER JOIN fabrication.projects p 
        ON pcj.ProjectID = p.ProjectID 
    LEFT JOIN productioncontrolitemsequences pciseq 
        ON pcseq.SequenceID = pciseq.SequenceID
    LEFT JOIN productioncontrolassemblies pca 
        ON pciseq.ProductionControlAssemblyID = pca.ProductionControlAssemblyID
    LEFT JOIN productioncontrolitems pci 
        ON pca.MainPieceProductionControlItemID = pci.ProductionControlItemID
    LEFT JOIN productioncontrolitemstationsummary pciss 
        ON pci.ProductionControlItemID = pciss.ProductionControlItemID 
        AND pciseq.SequenceID = pciss.SequenceID
    LEFT JOIN routes 
        ON pci.RouteID = routes.RouteID
    LEFT JOIN stations s 
        ON pciss.StationID = s.StationID
where pcseq.WorkPackageID = {$workPackageID}
ORDER BY
    workpackages.ReleasedToFab DESC,
    pcj.JobNumber,
    pcseq.Description,
    pca.MainMark,
    pciss.PositionInRoute,
    s.StationNumber
LIMIT 5000
";

$result = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Process the results to create the desired structure
$restructured = [];
$current_assembly = null;

foreach ($result as $row) {
    // If this is a new assembly or the first row
    if ($current_assembly === null || $current_assembly['ProductionControlItemID'] !== $row['MainPieceProductionControlItemID']) {
        // If there was a previous assembly, add it to the restructured array
        if ($current_assembly !== null) {
            $restructured[] = $current_assembly;
        }

        // Start a new assembly
        $current_assembly = [
            'ProjectID' => $row['ProjectID'],
            'ProductionControlID' => $row['ProductionControlID'],
            'JobNumber' => $row['ProjectJobNumber'],
            'RouteName' => $row['RouteName'],
            'ProductionControlItemID' => $row['MainPieceProductionControlItemID'],
            'ProjectDescription' => $row['ProjectDescription'],
            'MainMark' => $row['MainMark'],
            'AssemblyQuantity' => $row['AssemblyTotalQuantity'],
            'ProductionControlItemSequenceID' => $row['ProductionControlItemSequenceID'],
            'SequenceID' => $row['SequenceID'],
            'SequenceQuantity' => $row['SequenceQuantity'],
            'NetAssemblyWeightEach' => $row['EachNetWeight'] * 2.20462262,
            'GrossAssemblyWeightEach' => $row['GrossAssemblyWeightEach'] * 2.20462262,
            'TotalNetWeight' => $row['TotalNetWeight'] * 2.20462262,
            'TotalGrossWeight' => $row['TotalGrossWeight'] * 2.20462262,
            'AssemblyManHoursEach' => $row['AssemblyManHoursEach'],
            'TotalEstimatedManHours' => $row['TotalEstimatedManHours'],
            'SequenceDescription' => $row['SequenceDescription'],
            'LotNumber' => $row['LotNumber'],
            'WorkPackageNumber' => $row['WorkPackageNumber'],
            'ReleasedToFab' => $row['ReleasedToFab'],
            'BayName' => $row['BayName'],
            'Stations' => []
        ];
    }

    // Add station data if it exists
    if ($row['StationNumber'] !== null) {
        $current_assembly['Stations'][] = [
            'StationNumber' => $row['StationNumber'],
            'StationDescription' => $row['StationDescription'],
            'PositionInRoute' => $row['PositionInRoute'],
            'StationTotalQuantity' => $row['StationTotalQuantity'],
            'StationQuantityCompleted' => $row['StationQuantityCompleted'],
            'StationCompletedNetWeight' => $row['StationCompletedNetWeight'] * 2.20462262,
            'StationCompletedGrossWeight' => $row['StationCompletedGrossWeight'] * 2.20462262,
            'StationActualHours' => $row['StationActualHours']
        ];
    }
}

// Add the last assembly if it exists
if ($current_assembly !== null) {
    $restructured[] = $current_assembly;
}

// Output the result as JSON
header('Content-Type: application/json');
echo json_encode($restructured, JSON_PRETTY_PRINT);