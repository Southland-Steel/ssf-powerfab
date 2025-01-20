<?php
header('Content-Type: application/json');
require_once('config_ssf_db.php');

// Validate workweek parameter
if (!isset($_GET['workweek'])) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Missing parameter',
        'message' => 'Workweek parameter is required'
    ]);
    exit;
}

$workweek = $_GET['workweek'];

// Validate workweek format (optional but recommended)
if (!preg_match('/^\d{4}$/', $workweek)) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Invalid parameter',
        'message' => 'Workweek must be in format YYYY-WXX (e.g., 2024-W03)'
    ]);
    exit;
}

try {
    $query = "SELECT
        pcj.JobNumber,
        wp.WorkPackageNumber,
        REPLACE(pcseq.Description, CHAR(1), '') AS SequenceName,
        REPLACE(pcseq.LotNumber, CHAR(1), '') AS LotNumber,
        pcc.Description as CategoryName,
        rt.Route,
        REPLACE(pciseq.MainMark, CHAR(1), '') AS MainMark,
        REPLACE(pci.PieceMark, CHAR(1), '') AS PieceMark,
        shapes.Shape,
        pci.DimensionString,
        ROUND(pci.Length / 25.4,3) AS InchLength,
        pciseq.Quantity as SequenceAssemblyQuantity,
        ROUND(pci.Quantity / pca.AssemblyQuantity) as PiecesPerAssembly,
        ROUND(pci.Quantity / pca.AssemblyQuantity * pciseq.Quantity) as PiecesPerSequence,
        (pci.QuantityCutList + pci.QuantityTFS) as QuantityNested,
        (pci.QuantityTFS) as QuantityCut,
        stations.Description as StationName
        FROM workpackages as wp 
        INNER JOIN productioncontroljobs as pcj ON pcj.ProductionControlID = wp.ProductionControlID AND pcj.JobStatusID IN (1,6)
        INNER JOIN productioncontrolsequences as pcseq ON pcseq.WorkPackageID = wp.WorkPackageID
        INNER JOIN productioncontrolitemsequences as pciseq ON pciseq.SequenceID = pcseq.SequenceID
        INNER JOIN productioncontrolassemblies as pca ON pca.ProductionControlAssemblyID = pciseq.ProductionControlAssemblyID
        INNER JOIN productioncontrolitems as pci ON pci.ProductionControlAssemblyID = pca.ProductionControlAssemblyID
        INNER JOIN productioncontrolitemlinks as pcil ON pcil.ProductionControlItemID = pci.ProductionControlItemID
        INNER JOIN shapes on shapes.ShapeID = pci.ShapeID
        LEFT JOIN productioncontrolitemstationsummary as pciss ON pciss.ProductionControlItemID = pci.ProductionControlItemID AND pciss.SequenceID = pcseq.SequenceID
        LEFT JOIN stations on stations.StationID = pciss.StationID
        LEFT JOIN productioncontrolcategories as pcc ON pcc.CategoryID = pci.CategoryID
        LEFT JOIN routes as rt ON rt.RouteID = pci.RouteID
        WHERE wp.Group2 = :workweek
        AND wp.OnHold = 0 
        AND wp.Completed = 0 
        AND wp.WorkshopID = 1 
        AND shapes.Shape NOT IN ('HS','NU','WA','MB')
        AND stations.Description = 'CUT'
        ORDER BY pci.Length";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':workweek', $workweek, PDO::PARAM_STR);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'data' => $results
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error occurred',
        'message' => $e->getMessage()
    ]);
}
?>