<?php

require_once '../config_ssf_db.php';

// Get the work week parameter
$workweek = isset($_GET['workweek']) ? intval($_GET['workweek']) : 0;

if ($workweek <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid work week parameter']);
    exit;
}

$sql = "SELECT
    wp.Group2 as WorkWeek,
    shapes.Shape,
    wp.WorkPackageNumber,
    SUM(CASE when pci.MainPiece = 1 THEN (pciss.QuantityCompleted * (pci.ManHours/pci.Quantity)) END)*0.2 AS MCUT,
    SUM(CASE when pci.MainPiece = 1 THEN (pciss.TotalQuantity * (pci.ManHours/pci.Quantity)) END)*0.2 AS MCUTtotal,
    SUM(CASE when pci.MainPiece = 0 THEN (pciss.QuantityCompleted * (pci.ManHours/pci.Quantity)) END)*0.2 AS CUT,
    SUM(CASE when pci.MainPiece = 0 THEN (pciss.TotalQuantity * (pci.ManHours/pci.Quantity)) END)*0.2 AS CUTtotal
    FROM workpackages wp
    INNER JOIN productioncontrolsequences pcseq ON pcseq.WorkPackageID = wp.WorkPackageID
    INNER JOIN productioncontrolitemsequences pciseq ON pciseq.SequenceID = pcseq.SequenceID
    INNER JOIN productioncontrolassemblies pca ON pciseq.ProductionControlAssemblyID = pca.ProductionControlAssemblyID
    INNER JOIN productioncontrolitems pci ON pci.ProductionControlAssemblyID = pca.ProductionControlAssemblyID
    INNER JOIN shapes ON shapes.ShapeID = pci.ShapeID
    INNER JOIN productioncontrolitemstationsummary pciss ON pci.ProductionControlItemID = pciss.ProductionControlItemID AND pciss.SequenceID = pcseq.SequenceID
    INNER JOIN stations ON pciss.StationID = stations.StationID
    WHERE wp.Group2 = :workweek 
    AND shapes.shape NOT IN('WA','NU','HS','MB','WS') 
    AND stations.Description = 'CUT' 
    AND wp.WorkshopID = 1
    AND pciss.TotalQuantity <> pciss.QuantityCompleted
    GROUP BY wp.Group2, shapes.Shape, wp.WorkPackageNumber
    ORDER BY shapes.Shape DESC, wp.WorkPackageNumber";

try {
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':workweek', $workweek, PDO::PARAM_INT);
    $stmt->execute();
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($data);
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

?>