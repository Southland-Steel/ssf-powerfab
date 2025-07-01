<?php
require_once '../config_ssf_db.php';

$currentYear = substr(date('o'), -2);
$currentWeek = date('W');
$workweek = intval($currentYear . str_pad($currentWeek, 2, '0', STR_PAD_LEFT));
$startweek = $workweek - 3;
$endweek = $workweek + 9;

$sql = "SELECT
    wp.Group2 as WorkWeek,
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
    where (cast(wp.Group2 as UNSIGNED) between :startweek and :endweek) and shapes.shape not in('WA','NU','HS','MB','WS') and stations.Description = 'CUT' AND wp.WorkshopID = 1
    group by wp.Group2
    order by wp.Group2 asc";

$stmt = $db->prepare($sql);
$stmt->bindParam(':startweek', $startweek, PDO::PARAM_INT);
$stmt->bindParam(':endweek', $endweek, PDO::PARAM_INT);
$stmt->execute();

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($data);

?>

