<?php
require_once 'config_ssf_db.php';

$workweek = $_GET['workweek'];

$sql = "WITH QuantityCalcs AS (
    SELECT 
        pciseq.ProductionControlItemSequenceID,
        REPLACE(pci.PieceMark,CHAR(1),'') AS PieceMark,
        REPLACE(pca.MainMark, CHAR(1),'') AS AssemblyMark,
        MAX(CASE WHEN stations.Description = 'NESTED' THEN pcilink.QuantityCutList ELSE 0 END) as QtyNested,
        MAX(CASE WHEN stations.Description = 'CUT' THEN pciss.QuantityCompleted ELSE 0 END) as QtyCut,
        pciss.TotalQuantity as TotalStationQuantity,
        ROUND(pci.Quantity / pca.AssemblyQuantity) as AssemblyEachQuantity,
        pci.Quantity as PieceQuantity,
        pca.AssemblyQuantity,
        pciseq.Quantity as SequenceQuantity,
        ROUND((pci.Quantity / pca.AssemblyQuantity) * pciseq.Quantity) as TotalPieceMarkQuantityNeeded,
        pcj.JobNumber,
        pcseq.WorkPackageID,
        pcseq.SequenceID,
        pca.MainPieceProductionControlItemID,
        pci.ProductionControlItemID,
        rt.Route as RouteName,
        shapes.Shape
    FROM (
        SELECT WorkPackageID
        FROM workpackages
        WHERE Group2 = :workweek
    ) AS filtered_wp
    INNER JOIN productioncontrolsequences as pcseq ON pcseq.WorkPackageID = filtered_wp.WorkPackageID
    INNER JOIN productioncontroljobs AS pcj ON pcj.ProductionControlID = pcseq.ProductionControlID
    INNER JOIN productioncontrolitemsequences as pciseq ON pciseq.SequenceID = pcseq.SequenceID
    INNER JOIN productioncontrolassemblies as pca ON pciseq.ProductionControlAssemblyID = pca.ProductionControlAssemblyID
    INNER JOIN productioncontrolitems as pci ON pci.ProductionControlAssemblyID = pca.ProductionControlAssemblyID
    INNER JOIN shapes on shapes.ShapeID = pci.ShapeID
    INNER JOIN productioncontrolitemlinks as pcilink ON pcilink.ProductionControlItemID = pci.ProductionControlItemID
    INNER JOIN productioncontrolitemstationsummary as pciss
        ON pci.ProductionControlItemID = pciss.ProductionControlItemID
        AND pcj.ProductionControlID = pciss.ProductionControlID
        AND pciss.SequenceID = pciseq.SequenceID
    INNER JOIN stations
        ON pciss.StationID = stations.StationID
        AND stations.Description IN ('NESTED','CUT')
    LEFT JOIN workpackages as wp ON wp.WorkPackageID = pcseq.WorkPackageID
    LEFT JOIN routes as rt ON rt.RouteID = pci.RouteID
    GROUP BY 
        pciseq.ProductionControlItemSequenceID,
        pci.PieceMark,
        pca.MainMark,
        pci.Quantity,
        pca.AssemblyQuantity,
        pciseq.Quantity,
        pcj.JobNumber,
        pcseq.WorkPackageID,
        pcseq.SequenceID,
        pca.MainPieceProductionControlItemID,
        pci.ProductionControlItemID,
        rt.Description
)
SELECT 
    ProductionControlItemSequenceID,
    PieceMark,
    AssemblyMark,
    QtyNested,
    QtyCut,
    AssemblyEachQuantity,
    PieceQuantity,
    AssemblyQuantity,
    SequenceQuantity,
    TotalPieceMarkQuantityNeeded,
    JobNumber,
    WorkPackageID,
    SequenceID,
    MainPieceProductionControlItemID,
    ProductionControlItemID,
    RouteName,
    Shape,
    FLOOR(LEAST(
        NULLIF(QtyNested / AssemblyEachQuantity, 0),
        NULLIF(QtyCut / AssemblyEachQuantity, 0)
    )) as CompletedAssemblies
FROM QuantityCalcs;";

$stmt = $db->prepare($sql);
$stmt->bindParam(':workweek', $workweek, PDO::PARAM_STR);
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

$return_data['items'] = $result;

header('Content-Type: application/json');
echo json_encode($return_data, JSON_PRETTY_PRINT);