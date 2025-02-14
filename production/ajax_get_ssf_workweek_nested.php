<?php
// ajax_get_ssf_workweek_nested.php
require_once 'config_ssf_db.php';

$workweek = $_GET['workweek'];
$batchSize = 5000;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

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
WHERE wp.Group2 = :workweek and shapes.Shape NOT IN ('WA','NU','HS','MB')
LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($sql);
$stmt->bindParam(':workweek', $workweek, PDO::PARAM_STR);
$stmt->bindParam(':limit', $batchSize, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
$hasMore = count($data) === $batchSize;

$return_data = [
    'items' => $data,
    'hasMore' => $hasMore,
    'nextOffset' => $hasMore ? $offset + $batchSize : null
];

header('Content-Type: application/json');
echo json_encode($return_data);