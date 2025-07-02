<?php

require_once '../../config_ssf_db.php';

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
    routes.Route,
    group_concat(distinct category.Description separator ', ') as Category,
    GROUP_CONCAT(DISTINCT REPLACE(SUBSTRING(wp.WorkPackageNumber, 1, 4), '-', '') SEPARATOR ', ') AS JobNumbers,
    sum(pca.AssemblyManHoursEach*pciss.QuantityCompleted)*.4 as Fit,
    sum(pca.AssemblyManHoursEach*pciss.TotalQuantity)*.4 as FitTotal,
    sum(pciss.TotalQuantity - pciss.QuantityCompleted) as QuantityRemaining
    FROM workpackages wp
    INNER JOIN productioncontrolsequences pcseq ON pcseq.WorkPackageID = wp.WorkPackageID
    INNER JOIN productioncontrolitemsequences pciseq ON pciseq.SequenceID = pcseq.SequenceID
    INNER JOIN productioncontrolassemblies pca ON pciseq.ProductionControlAssemblyID = pca.ProductionControlAssemblyID
    INNER JOIN productioncontrolitems pci ON pci.ProductionControlItemID = pca.MainPieceProductionControlItemID
    inner join productioncontrolcategories category on category.CategoryID = pci.CategoryID
    inner join routes on routes.RouteID = pci.RouteID
    inner join shapes on shapes.ShapeID = pci.ShapeID
    inner join routestations on routestations.RouteID = routes.RouteID
    INNER JOIN productioncontrolitemstationsummary pciss ON pci.ProductionControlItemID = pciss.ProductionControlItemID AND pciss.SequenceID = pcseq.SequenceID and routestations.StationID = pciss.StationID
    INNER JOIN stations ON pciss.StationID = stations.StationID
    inner join fabrication.productioncontrolcategories as cat on cat.CategoryID = pci.CategoryID
    where wp.Group2 = :workweek and stations.Description = 'FIT' AND wp.WorkshopID = 1 and pciss.TotalQuantity <> pciss.QuantityCompleted
    group by wp.Group2, shapes.Shape, routes.Route
    order by shapes.Shape desc, routes.Route, category.Description";

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