<?php
// ajax_get_ssf_workweek_nested.php
require_once '../config_ssf_db.php';

$workweek = $_GET['workweek'];
$batchSize = 5000;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

$sql = "SELECT 
    pciseq.ProductionControlItemSequenceID,
    REPLACE(pci.PieceMark,CHAR(1),'') AS PieceMark,
    REPLACE(pca.MainMark, CHAR(1),'') AS AssemblyMark,
    -- Total nested = Currently on cutlist + Already cut
    -- But ensure it's at least the total quantity needed (since everything that needs cutting must be nested)
    GREATEST(
        COALESCE(pcilink.QuantityCutList, 0) + COALESCE(cut_summary.QuantityCut, 0),
        ROUND((pci.Quantity / pca.AssemblyQuantity) * pciseq.Quantity)
    ) as QtyNested,
    ROUND(pci.Quantity / pca.AssemblyQuantity) as AssemblyEachQuantity,
    pciseq.Quantity as SequenceQuantity,
    ROUND((pci.Quantity / pca.AssemblyQuantity) * pciseq.Quantity) as TotalPieceMarkQuantityNeeded,
    pci.ManHours as ManHoursEach,
    shapes.Shape
FROM workpackages wp
INNER JOIN productioncontrolsequences pcseq ON pcseq.WorkPackageID = wp.WorkPackageID
INNER JOIN productioncontrolitemsequences pciseq ON pciseq.SequenceID = pcseq.SequenceID
INNER JOIN productioncontrolassemblies pca ON pciseq.ProductionControlAssemblyID = pca.ProductionControlAssemblyID
INNER JOIN productioncontrolitems pci ON pci.ProductionControlAssemblyID = pca.ProductionControlAssemblyID
INNER JOIN shapes ON shapes.ShapeID = pci.ShapeID
-- LEFT JOIN to get items currently on cutlist
LEFT JOIN productioncontrolitemlinks pcilink ON pcilink.ProductionControlItemID = pci.ProductionControlItemID
-- LEFT JOIN to get items that have been cut (they were nested before being cut)
LEFT JOIN (
    SELECT 
        pciss.ProductionControlItemID,
        pciss.SequenceID,
        pciss.QuantityCompleted as QuantityCut
    FROM productioncontrolitemstationsummary pciss
    INNER JOIN stations ON pciss.StationID = stations.StationID
    WHERE stations.Description = 'CUT'
) cut_summary ON cut_summary.ProductionControlItemID = pci.ProductionControlItemID 
    AND cut_summary.SequenceID = pcseq.SequenceID
WHERE wp.Group2 = :workweek and wp.WorkshopID = 1
    AND shapes.Shape NOT IN ('WA','NU','HS','MB')
    -- Include all items in the workweek (remove the filter that excluded non-nested items)
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