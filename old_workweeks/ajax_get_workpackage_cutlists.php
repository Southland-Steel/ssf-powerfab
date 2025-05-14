<?php
header('Content-Type: application/json');
require_once '../config_ssf_db.php';

try {
    $workweek = $_GET['workweek'] ?? null;
    $sequenceId = $_GET['sequence_id'] ?? null;

    $whereClause = '';
    $params = [];

    if ($sequenceId) {
        $whereClause = 'pcseq.SequenceID = :sequence_id';
        $params[':sequence_id'] = $sequenceId;
    } elseif ($workweek) {
        $whereClause = 'wp.Group2 = :workweek';
        $params[':workweek'] = $workweek;
    } else {
        throw new Exception('Either workweek or sequence_id parameter is required');
    }

    $query = "SELECT 
        DISTINCT pccl.ProductionControlCutListID,
        wp.WorkPackageNumber,
        wp.WorkPackageID,
        pccl.Description as CutlistDescription,
        mg.Name as MachineGroup,
        m.Name as Machine,
        workshops.Name as WorkShop,
        xtn.ExternalNestExtra1 as NestNumber,
        pccl.TotalItems,
        pccl.RemainingItems,
        pccl.CompletedItems,
        pccl.InvalidatedItems,
        shapes.Shape,
        sizes.DimensionSizesImperial
    FROM workpackages wp
    INNER JOIN productioncontrolsequences pcseq ON wp.WorkPackageID = pcseq.WorkPackageID 
    INNER JOIN productioncontrolcutlistbarcodepatterns pcclbp ON pcseq.SequenceID = pcclbp.SequenceID
    INNER JOIN productioncontrolcutlistbarcodes pcclb ON pcclb.ProductionControlCutListBarcodeID = pcclbp.ProductionControlCutListBarcodeID
    INNER JOIN productioncontrolcutlistitems as pccli ON pccli.ProductionControlCutListBarcodeID = pcclb.ProductionControlCutListBarcodeID
    INNER JOIN productioncontrolcutlists as pccl ON pccl.ProductionControlCutListID = pccli.ProductionControlCutListID
    LEFT JOIN externalnests as xtn ON xtn.ExternalNestID = pcclb.ExternalNestID
    LEFT JOIN shapes on shapes.ShapeID = xtn.ShapeID
    LEFT JOIN sizes on sizes.SizeID = xtn.SizeID
    LEFT JOIN workshops ON workshops.WorkshopID = wp.WorkshopID
    LEFT JOIN machinegroups as mg ON mg.MachineGroupID = pccl.MachineGroupID
    LEFT JOIN machines as m ON m.MachineID = pccl.MachineID
    WHERE $whereClause AND pccl.CompletedItems < pccl.TotalItems
    ORDER BY wp.WorkPackageNumber, shapes.Shape, sizes.SortOrder";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($results, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}