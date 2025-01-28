<?php
require_once 'config_ssf_db.php';

function getNested($workweek) {
    global $db;

    $sql = "SELECT 
        pciseq.ProductionControlItemSequenceID,
        REPLACE(pci.PieceMark,CHAR(1),'') AS PieceMark,
        REPLACE(pca.MainMark, CHAR(1),'') AS AssemblyMark,
        pcilink.QuantityCutList as QtyNested,
        ROUND(pci.Quantity / pca.AssemblyQuantity) as AssemblyEachQuantity,
        pciseq.Quantity as SequenceQuantity,
        ROUND((pci.Quantity / pca.AssemblyQuantity) * pciseq.Quantity) as TotalPieceMarkQuantityNeeded,
        shapes.Shape
    FROM workpackages wp
    INNER JOIN productioncontrolsequences pcseq ON pcseq.WorkPackageID = wp.WorkPackageID
    INNER JOIN productioncontrolitemsequences pciseq ON pciseq.SequenceID = pcseq.SequenceID
    INNER JOIN productioncontrolassemblies pca ON pciseq.ProductionControlAssemblyID = pca.ProductionControlAssemblyID
    INNER JOIN productioncontrolitems pci ON pci.ProductionControlAssemblyID = pca.ProductionControlAssemblyID
    INNER JOIN shapes ON shapes.ShapeID = pci.ShapeID
    INNER JOIN productioncontrolitemlinks pcilink ON pcilink.ProductionControlItemID = pci.ProductionControlItemID
    WHERE wp.Group2 = :workweek";

    $stmt = $db->prepare($sql);
    $stmt->bindParam(':workweek', $workweek, PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCut($workweek) {
    global $db;

    $sql = "SELECT 
        pciseq.ProductionControlItemSequenceID,
        REPLACE(pci.PieceMark,CHAR(1),'') AS PieceMark,
        pciss.QuantityCompleted as QtyCut,
        ROUND(pci.Quantity / pca.AssemblyQuantity) as AssemblyEachQuantity,
        pciseq.Quantity as SequenceQuantity,
        ROUND((pci.Quantity / pca.AssemblyQuantity) * pciseq.Quantity) as TotalPieceMarkQuantityNeeded,
        shapes.Shape
    FROM workpackages wp
    INNER JOIN productioncontrolsequences pcseq ON pcseq.WorkPackageID = wp.WorkPackageID
    INNER JOIN productioncontrolitemsequences pciseq ON pciseq.SequenceID = pcseq.SequenceID
    INNER JOIN productioncontrolassemblies pca ON pciseq.ProductionControlAssemblyID = pca.ProductionControlAssemblyID
    INNER JOIN productioncontrolitems pci ON pci.ProductionControlAssemblyID = pca.ProductionControlAssemblyID
    INNER JOIN shapes ON shapes.ShapeID = pci.ShapeID
    INNER JOIN productioncontrolitemstationsummary pciss 
        ON pci.ProductionControlItemID = pciss.ProductionControlItemID 
        AND pciss.SequenceID = pcseq.SequenceID
    INNER JOIN stations ON pciss.StationID = stations.StationID
    WHERE wp.Group2 = :workweek AND stations.Description = 'CUT'";

    $stmt = $db->prepare($sql);
    $stmt->bindParam(':workweek', $workweek, PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getKit($workweek) {
    global $db;

    $sql = "SELECT 
        pciseq.ProductionControlItemSequenceID,
        REPLACE(pci.PieceMark,CHAR(1),'') AS PieceMark,
        pciss.QuantityCompleted as QtyKitted,
        ROUND(pci.Quantity / pca.AssemblyQuantity) as AssemblyEachQuantity,
        pciseq.Quantity as SequenceQuantity,
        ROUND((pci.Quantity / pca.AssemblyQuantity) * pciseq.Quantity) as TotalPieceMarkQuantityNeeded,
        shapes.Shape
    FROM workpackages wp
    INNER JOIN productioncontrolsequences pcseq ON pcseq.WorkPackageID = wp.WorkPackageID
    INNER JOIN productioncontrolitemsequences pciseq ON pciseq.SequenceID = pcseq.SequenceID
    INNER JOIN productioncontrolassemblies pca ON pciseq.ProductionControlAssemblyID = pca.ProductionControlAssemblyID
    INNER JOIN productioncontrolitems pci ON pci.ProductionControlAssemblyID = pca.ProductionControlAssemblyID
    INNER JOIN shapes ON shapes.ShapeID = pci.ShapeID
    INNER JOIN productioncontrolitemstationsummary pciss 
        ON pci.ProductionControlItemID = pciss.ProductionControlItemID 
        AND pciss.SequenceID = pcseq.SequenceID
    INNER JOIN stations ON pciss.StationID = stations.StationID
    WHERE wp.Group2 = :workweek AND stations.Description = 'KIT'";

    $stmt = $db->prepare($sql);
    $stmt->bindParam(':workweek', $workweek, PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$workweek = $_GET['workweek'];

// Get data from all three queries
$nestedData = getNested($workweek);
$cutData = getCut($workweek);
$kitData = getKit($workweek);

// Create a lookup array for each piece
$piecemarkData = [];

// Process nested data
foreach ($nestedData as $row) {
    $key = $row['ProductionControlItemSequenceID'] . '_' . $row['PieceMark'];
    if (!isset($piecemarkData[$key])) {
        $piecemarkData[$key] = [
            'ProductionControlItemSequenceID' => $row['ProductionControlItemSequenceID'],
            'PieceMark' => $row['PieceMark'],
            'AssemblyEachQuantity' => $row['AssemblyEachQuantity'],
            'SequenceQuantity' => $row['SequenceQuantity'],
            'TotalPieceMarkQuantityNeeded' => $row['TotalPieceMarkQuantityNeeded'],
            'Shape' => $row['Shape'],
            'QtyNested' => $row['QtyNested'],
            'QtyCut' => 0,
            'QtyKitted' => 0
        ];
    }
}

// Process cut data
foreach ($cutData as $row) {
    $key = $row['ProductionControlItemSequenceID'] . '_' . $row['PieceMark'];
    if (isset($piecemarkData[$key])) {
        $piecemarkData[$key]['QtyCut'] = $row['QtyCut'];
    }
}

// Process kit data
foreach ($kitData as $row) {
    $key = $row['ProductionControlItemSequenceID'] . '_' . $row['PieceMark'];
    if (isset($piecemarkData[$key])) {
        $piecemarkData[$key]['QtyKitted'] = $row['QtyKitted'];
    }
}

// Calculate completed assemblies
foreach ($piecemarkData as &$piece) {
    $piece['CompletedAssemblies'] = floor(min(
        $piece['QtyNested'] / $piece['AssemblyEachQuantity'],
        $piece['QtyCut'] / $piece['AssemblyEachQuantity'],
        $piece['QtyKitted'] / $piece['AssemblyEachQuantity']
    ));
}

$return_data['items'] = array_values($piecemarkData);

header('Content-Type: application/json');
echo json_encode($return_data, JSON_PRETTY_PRINT);