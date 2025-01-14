<?php
require_once 'config_ssf_db.php';

$workweek = $_GET['workweek'];

$sql = "WITH QuantityCalcs AS (
    SELECT 
        pciseq.ProductionControlItemSequenceID,
        MAX(CASE WHEN stations.Description = 'NESTED' THEN pcilink.QuantityCutList ELSE 0 END) as QtyNested,
        MAX(CASE WHEN stations.Description = 'CUT' THEN pciss.QuantityCompleted ELSE 0 END) as QtyCut,
        ROUND(pci.Quantity / pca.AssemblyQuantity) as AssemblyEachQuantity,
        ROUND(pci.Quantity / pca.AssemblyQuantity * pciseq.Quantity) as TotalPieceMarkQuantityNeeded
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
    INNER JOIN productioncontrolitemlinks as pcilink ON pcilink.ProductionControlItemID = pci.ProductionControlItemID
    INNER JOIN productioncontrolitemstationsummary as pciss
        ON pci.ProductionControlItemID = pciss.ProductionControlItemID
        AND pcj.ProductionControlID = pciss.ProductionControlID
        AND pciss.SequenceID = pciseq.SequenceID
    INNER JOIN stations
        ON pciss.StationID = stations.StationID
        AND stations.Description IN ('NESTED','CUT')
    GROUP BY 
        pciseq.ProductionControlItemSequenceID,
        pci.Quantity,
        pca.AssemblyQuantity,
        pciseq.Quantity
)
SELECT 
    ProductionControlItemSequenceID,
    QtyNested,
    QtyCut,
    AssemblyEachQuantity,
    TotalPieceMarkQuantityNeeded,
    FLOOR(GREATEST(QtyNested, QtyCut) / AssemblyEachQuantity) as CompletedAssemblies
FROM QuantityCalcs";

$stmt = $db->prepare($sql);
$stmt->bindParam(':workweek', $workweek, PDO::PARAM_STR);
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

$return_data['items'] = $result;

header('Content-Type: application/json');
echo json_encode($return_data, JSON_PRETTY_PRINT);