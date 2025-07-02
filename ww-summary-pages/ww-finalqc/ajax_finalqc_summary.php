<?php
require_once '../../config_ssf_db.php';

$currentYear = substr(date('o'), -2);
$currentWeek = date('W');
$workweek = intval($currentYear . str_pad($currentWeek, 2, '0', STR_PAD_LEFT));
$startweek = $workweek - 3;
$endweek = $workweek + 9;

$sql = "SELECT
    wp.Group2 as WorkWeek,
    sum(case when routes.Route = 'BO' then pca.AssemblyManHoursEach*pciss.QuantityCompleted end)*0.8 as FQCBO,
    sum(case when routes.Route = 'BO' then pca.AssemblyManHoursEach*pciss.TotalQuantity end)*0.8 as FQCBOtotal,
    sum(case when routes.Route <> 'BO' then pca.AssemblyManHoursEach*pciss.QuantityCompleted end)*0.4 as FQC,
    sum(case when routes.Route <> 'BO' then pca.AssemblyManHoursEach*pciss.TotalQuantity end)*0.4 as FQCtotal
    FROM workpackages wp
    INNER JOIN productioncontrolsequences pcseq ON pcseq.WorkPackageID = wp.WorkPackageID
    INNER JOIN productioncontrolitemsequences pciseq ON pciseq.SequenceID = pcseq.SequenceID
    INNER JOIN productioncontrolassemblies pca ON pciseq.ProductionControlAssemblyID = pca.ProductionControlAssemblyID
    INNER JOIN productioncontrolitems pci ON pci.ProductionControlItemID = pca.MainPieceProductionControlItemID
    inner join routes on routes.RouteID = pci.RouteID
    inner join routestations on routestations.RouteID = routes.RouteID
    INNER JOIN productioncontrolitemstationsummary pciss ON pci.ProductionControlItemID = pciss.ProductionControlItemID AND pciss.SequenceID = pcseq.SequenceID and routestations.StationID = pciss.StationID
    INNER JOIN stations ON pciss.StationID = stations.StationID
    inner join fabrication.productioncontrolcategories as cat on cat.CategoryID = pci.CategoryID
    where (cast(wp.Group2 as UNSIGNED) between :startweek and :endweek) and stations.Description = 'FINAL QC' AND wp.WorkshopID = 1
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


