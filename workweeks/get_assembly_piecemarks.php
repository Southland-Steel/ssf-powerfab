<?php
header('Content-Type: application/json');
require_once '../config_ssf_db.php';

$pcis_id = $_GET['pcis_id'] ?? null;

$query = "select
    REPLACE(pciseq.MainMark, char(1), '') as MainMark,
    REPLACE(pci.PieceMark, char(1), '') as PieceMark,
    pci.MainPiece as IsMainPiece,
    shapes.Shape,
    pci.DimensionString,
    ROUND(pci.Length / 25.4,3) as InchLength,
    pca.AssemblyManHoursEach,
    ROUND(pci.ManHours / pci.Quantity,2) as PieceMarkManHoursEach,
    ROUND(pci.Quantity / pca.AssemblyQuantity) as PieceMarkAssemblyQuantityEach,
    ROUND(pci.Quantity / pca.AssemblyQuantity * pciseq.Quantity) as SequencePieceMarkQuantityNeeded,
    pciroute.Route,
    pcicategory.Description as CategoryName,
    pcisubcategory.Description as SubCategoryName,
    ROUND(pci.GrossWeight,2) as GrossWeight,
    pci.ProductionControlItemID,
    pci.QuantityCutList,
    pci.QuantityLinkedInventoryOnOrder,
    pci.QuantityLinkedInventoryInStock,
    pci.QuantityTFS,
    cutstation.QuantityCompleted as CutStationCompleted,
    cutstation.LastDateCompleted as CutStationLastCompleted
from productioncontrolitemsequences as pciseq
inner join productioncontrolassemblies as pca on pca.ProductionControlAssemblyID = pciseq.ProductionControlAssemblyID
inner join productioncontrolsequences as pcseq on pcseq.SequenceID = pciseq.SequenceID
inner join productioncontrolitems as pci on pci.ProductionControlAssemblyID = pca.ProductionControlAssemblyID
inner join workpackages as wp on wp.WorkPackageID = pcseq.WorkPackageID
inner join shapes on shapes.ShapeID = pci.ShapeID
inner join routes as pciroute on pciroute.RouteID = pci.RouteID
inner join productioncontrolcategories as pcicategory on pcicategory.CategoryID = pci.CategoryID
inner join productioncontrolsubcategories as pcisubcategory on pcisubcategory.SubCategoryID = pci.SubCategoryID
left join (
    select 
        ProductionControlItemID,
        TotalQuantity,
        QuantityCompleted,
        LastDateCompleted
    from productioncontrolitemstationsummary as pciss
    inner join stations on stations.StationID = pciss.StationID
    where stations.Description = 'CUT'
) as cutstation on cutstation.ProductionControlItemID = pci.ProductionControlItemID
where pciseq.ProductionControlItemSequenceID = :pcis_id
ORDER BY CASE WHEN pci.Length = 0 THEN 1 ELSE 0 END DESC, pci.MainPiece  DESC";

try {
    $stmt = $db->prepare($query);
    $stmt->execute([':pcis_id' => $pcis_id]);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Convert lengths before JSON encoding
    foreach ($result as &$row) {
        $row['FormattedLength'] = inchesToFeetAndInches($row['InchLength']);
    }

    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}