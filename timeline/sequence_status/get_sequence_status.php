<?php
require_once '../../config_ssf_db.php';

$sql = "select 
   pcj.JobNumber,
   REPLACE(pcseq.Description, CHAR(1), '') as SequenceName,
   REPLACE(pcseq.LotNumber, CHAR(1), '') as LotNumber,
   wp.WorkPackageNumber,
   REPLACE(pciseq.MainMark, CHAR(1),'') as MainMark,
   stations.Description as StationName,
   pciss.TotalQuantity,
   pciss.QuantityCompleted,
   pcidest.QuantityShipped,
   pciseq.ProductionControlItemSequenceID,
   pcat.Description as Category,
   psubcat.Description as SubCategory,
   pca.GrossAssemblyWeightEach
from productioncontroljobs pcj 
inner join productioncontrolsequences pcseq on pcseq.ProductionControlID = pcj.ProductionControlID
left join workpackages wp ON wp.WorkPackageID = pcseq.WorkPackageID
inner join productioncontrolitemsequences pciseq ON pciseq.SequenceID = pcseq.SequenceID
inner join productioncontrolassemblies pca ON pca.ProductionControlAssemblyID = pciseq.ProductionControlAssemblyID
inner join productioncontrolitems pcia on pcia.ProductionControlItemID = pca.MainPieceProductionControlItemID
left join productioncontrolcategories as pcat on pcat.CategoryID = pcia.CategoryID
    left join productioncontrolsubcategories as psubcat on psubcat.SubCategoryID = pcia.SubCategoryID
inner join productioncontrolitemdestinations pcidest on pcidest.SequenceID = pcseq.SequenceID and pcidest.ProductionControlItemID = pca.MainPieceProductionControlItemID and pcidest.PositionInRoute = 2
    and pcidest.ProductionControlItemID = pca.MainPieceProductionControlItemID 
    and pcidest.PositionInRoute = 2
left join (
   productioncontrolitemstationsummary pciss 
   inner join stations on stations.StationID = pciss.StationID
   and stations.Description IN ('NESTED','CUT','FIT','WELD','FINAL QC')
) ON pciss.ProductionControlItemID = pca.MainPieceProductionControlItemID and pcseq.SequenceID
where pcj.JobNumber = :jobNumber 
and pcseq.AssemblyQuantity > 0
and REPLACE(pcseq.Description, CHAR(1), '') = :sequenceName";

$stmt = $db->prepare($sql);
$stmt->execute(['jobNumber' => $_GET['jobNumber'], 'sequenceName' => $_GET['sequenceName']]);

$assemblies = [];
while ($row = $stmt->fetch()) {
    if (!isset($assemblies[$row['ProductionControlItemSequenceID']])) {
        $assemblies[$row['ProductionControlItemSequenceID']] = [
            'Category' => $row['Category'],
            'SubCategory' => $row['SubCategory'],
            'JobNumber' => $row['JobNumber'],
            'SequenceName' => $row['SequenceName'],
            'LotNumber' => $row['LotNumber'],
            'WorkPackageNumber' => $row['WorkPackageNumber'],
            'MainMark' => $row['MainMark'],
            'QuantityShipped' => $row['QuantityShipped'],
            'GrossAssemblyWeightEach' => Round($row['GrossAssemblyWeightEach'] * 2.20462,1),
            'Stations' => []
        ];
    }
    if ($row['StationName']) {
        $assemblies[$row['ProductionControlItemSequenceID']]['Stations'][$row['StationName']] = [
            'Completed' => $row['QuantityCompleted'],
            'Total' => $row['TotalQuantity']
        ];
    }
}

// Add shipping station to each assembly
foreach ($assemblies as &$assembly) {
    $assembly['Stations']['SHIPPING'] = [
        'Completed' => $assembly['QuantityShipped'],
        'Total' => $assembly['Stations']['FINAL QC']['Total']
    ];
    unset($assembly['QuantityShipped']);
}

header('Content-Type: application/json');
echo json_encode(array_values($assemblies));
?>