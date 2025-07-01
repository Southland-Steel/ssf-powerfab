<?php
require_once '../config_ssf_db.php';

$workweek = $_GET['workweek'];
$batchSize = 5000;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

$sql = "SELECT
pciseq.ProductionControlItemSequenceID,
REPLACE(pci.PieceMark,CHAR(1),'') AS PieceMark,
REPLACE(pca.MainMark, CHAR(1),'') AS AssemblyMark,
pciss.QuantityCompleted as QtyCut,
ROUND(pci.Quantity / pca.AssemblyQuantity) as AssemblyEachQuantity,
pciseq.Quantity as SequenceQuantity,
ROUND((pci.Quantity / pca.AssemblyQuantity) * pciseq.Quantity) as TotalPieceMarkQuantityNeeded,
pci.MainPiece as isMainPiece,
pci.ManHours / pci.Quantity as ManHoursEach,
shapes.Shape
FROM workpackages wp
INNER JOIN productioncontrolsequences pcseq ON pcseq.WorkPackageID = wp.WorkPackageID
INNER JOIN productioncontrolitemsequences pciseq ON pciseq.SequenceID = pcseq.SequenceID
INNER JOIN productioncontrolassemblies pca ON pciseq.ProductionControlAssemblyID = pca.ProductionControlAssemblyID
INNER JOIN productioncontrolitems pci ON pci.ProductionControlAssemblyID = pca.ProductionControlAssemblyID
INNER JOIN shapes ON shapes.ShapeID = pci.ShapeID
INNER JOIN productioncontrolitemstationsummary pciss ON pci.ProductionControlItemID = pciss.ProductionControlItemID AND pciss.SequenceID = pcseq.SequenceID
INNER JOIN stations ON pciss.StationID = stations.StationID
WHERE wp.Group2 = :workweek and wp.WorkshopID = 1
AND stations.Description = 'CUT'
 and shapes.Shape NOT IN ('WA','NU','HS','MB','WS')
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