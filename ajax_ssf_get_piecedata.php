<?php
require_once 'config_ssf_db.php';
header('Content-Type: application/json');

// Get workweek from request
$workweek = isset($_POST['workweek']) ? $_POST['workweek'] : '2503';

$query = "SELECT
    pcj.JobNumber,
    wp.WorkPackageNumber,
    REPLACE(pcseq.Description, CHAR(1), '') AS SequenceName,
    REPLACE(pcseq.LotNumber, CHAR(1), '') AS LotNumber,
    REPLACE(pciseq.MainMark, CHAR(1), '') AS MainMark,
    REPLACE(pci.PieceMark, CHAR(1), '') AS PieceMark,
    shapes.Shape,
    pci.DimensionString,
    ROUND(pci.Length / 25.4,3) AS InchLength,
    pciseq.Quantity as SequenceAssemblyQuantity,
    ROUND(pci.Quantity / pci.MainPieceQuantity) as PiecesPerAssembly,
    ROUND(pci.Quantity / pci.MainPieceQuantity * pciseq.Quantity) as PiecesPerSequence,
    pccbp.Quantity as NestQuantity,
    xtn.ExternalNestExtra1 as NestGroup,
    xtn.ExternalNestExtra2 as NestNumber
    FROM workpackages as wp 
    INNER JOIN productioncontroljobs as pcj ON pcj.ProductionControlID = wp.ProductionControlID AND pcj.JobStatusID IN (1,6)
    INNER JOIN productioncontrolsequences as pcseq ON pcseq.WorkPackageID = wp.WorkPackageID
    INNER JOIN productioncontrolitemsequences as pciseq ON pciseq.SequenceID = pcseq.SequenceID
    INNER JOIN productioncontrolassemblies as pca ON pca.ProductionControlAssemblyID = pciseq.ProductionControlAssemblyID
    INNER JOIN productioncontrolitems as pci ON pci.ProductionControlAssemblyID = pca.ProductionControlAssemblyID
    INNER JOIN shapes ON shapes.ShapeID = pci.ShapeID
    LEFT JOIN productioncontrolcutlistbarcodepatterns as pccbp ON pci.ProductionControlID = pccbp.ProductionControlID AND pci.MainMark = pccbp.MainMark AND pci.PieceMark = pccbp.PieceMark
    LEFT JOIN productioncontrolcutlistbarcodes as pccb ON pccb.ProductionControlCutListBarcodeID = pccbp.ProductionControlCutListBarcodeID
    LEFT JOIN externalnests as xtn ON xtn.ExternalNestID = pccb.ExternalNestID
    WHERE wp.Group2 = :workweek 
    AND wp.OnHold = 0 
    AND wp.Completed = 0 
    AND wp.WorkshopID = 1 
    AND shapes.Shape NOT IN ('HS','NU','WA')
    AND (xtn.InUse = 1 OR xtn.InUse is null)
    ";

try {
    $stmt = $db->prepare($query);
    $stmt->execute([':workweek' => $workweek]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($results);
} catch(PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>