<?php
header('Content-Type: application/json');
require_once '../config_ssf_db.php';

$pcis_id = $_GET['pcis_id'] ?? null;

$query = "WITH BaseData AS (
    SELECT DISTINCT
        pci.ProductionControlItemID,
        REPLACE(pci.MainMark,CHAR(1),'') AS MainMark,
        REPLACE(pci.PieceMark,CHAR(1),'') AS PieceMark,
        pci.MainPiece as IsMainPiece,
        shapes.Shape,
        pci.DimensionString,
        ROUND(pci.Length / 25.4,3) as InchLength,
        pclbc.ExternalNestID,
        externalnests.ExternalNestExtra1 as CutListCode,
        externalnests.ExternalNestExtra2 as CutListBarCode,
        externalnestparts.Quantity as NestQuantity,
        pci.QuantityCutList,
        pci.QuantityLinkedInventoryInStock,
        pci.QuantityLinkedInventoryOnOrder,
        pci.QuantityTFS,
        pccli.DateTimeValidated,
        pccli.OnOrder
    FROM productioncontrolitemsequences AS pciseq
    INNER JOIN productioncontrolassemblies as pca 
        ON pca.ProductionControlAssemblyID = pciseq.ProductionControlAssemblyID
    INNER JOIN productioncontrolitems as pci 
        ON pci.ProductionControlAssemblyID = pca.ProductionControlAssemblyID
    LEFT JOIN productioncontrolcutlistbarcodepatterns AS pclbcp 
        ON pclbcp.ProductionControlID = pci.ProductionControlID 
        AND pclbcp.MainMark = pci.MainMark 
        AND pclbcp.PieceMark = pci.PieceMark
    LEFT JOIN productioncontrolcutlistbarcodes as pclbc 
        ON pclbc.ProductionControlCutListBarcodeID = pclbcp.ProductionControlCutListBarcodeID
    LEFT JOIN shapes ON shapes.ShapeID = pci.ShapeID
    LEFT JOIN externalnests 
        ON externalnests.ExternalNestID = pclbc.ExternalNestID 
        AND externalnests.InUse = 1
    LEFT JOIN externalnestparts 
        ON externalnestparts.ExternalNestID = externalnests.ExternalNestID 
        AND REPLACE(pclbcp.PieceMark,CHAR(1),'') = externalnestparts.PieceMark
    LEFT JOIN productioncontrolcutlistitemparts as pcclip 
        ON pcclip.ProductionControlCutListBarcodePatternID = pclbcp.ProductionControlCutListBarcodePatternID
    LEFT JOIN productioncontrolcutlistitems as pccli 
        ON pccli.ProductionControlCutListItemID = pcclip.ProductionControlCutListItemID
    WHERE pciseq.ProductionControlItemSequenceID = :pcis_id
)
SELECT *
FROM BaseData
ORDER BY IsMainPiece DESC, PieceMark, ExternalNestID";

try {
    $stmt = $db->prepare($query);
    $stmt->execute([':pcis_id' => $pcis_id]);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group by piecemark
    $grouped = [];
    foreach ($result as $row) {
        $piecemarkKey = $row['PieceMark'];

        if (!isset($grouped[$piecemarkKey])) {
            $grouped[$piecemarkKey] = [
                'piecemark' => $row['PieceMark'],
                'isMainPiece' => $row['IsMainPiece'],
                'shape' => $row['Shape'],
                'dimensionString' => $row['DimensionString'],
                'inchLength' => $row['InchLength'],
                'quantityCutList' => $row['QuantityCutList'],
                'quantityInStock' => $row['QuantityLinkedInventoryInStock'],
                'quantityOnOrder' => $row['QuantityLinkedInventoryOnOrder'],
                'quantityTFS' => $row['QuantityTFS'],
                'cutLists' => []
            ];
        }

        // Only add to cutLists if there's an ExternalNestID
        if ($row['ExternalNestID']) {
            $grouped[$piecemarkKey]['cutLists'][] = [
                'externalNestId' => $row['ExternalNestID'],
                'cutListCode' => $row['CutListCode'],
                'barCode' => $row['CutListBarCode'],
                'quantity' => $row['NestQuantity'],
                'dateValidated' => $row['DateTimeValidated'],
                'onOrder' => $row['OnOrder']
            ];
        }
    }

    echo json_encode(array_values($grouped));
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}