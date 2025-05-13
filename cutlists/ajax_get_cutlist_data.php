<?php
require_once '../config_ssf_db.php';

try {
    // Get workweek parameter
    $workweek = $_GET['workweek'] ?? '';

    if (empty($workweek)) {
        throw new Exception('Workweek parameter is required');
    }

    $query = "
        SELECT 
            pccli.ProductionControlCutListItemID,
            CONCAT(xtn.ExternalNestExtra1, '-', LPAD(xtn.ExternalNestExtra2, 2, '0')) AS NestNumber,
            wp.WorkPackageNumber,
            wp.Group2 as WorkWeek,
            pcj.JobNumber,
            REPLACE(pcclbp.MainMark, CHAR(1), '') AS MainMark,
            REPLACE(pcclbp.PieceMark, CHAR(1), '') AS PieceMark,
            ROUND(pcclb.Length / 25.4, 3) as Length,
            pcclb.Barcode,
            pcclbp.Quantity,
            pccl.Description as CutlistName,
            mg.Name as MachineGroup,
            m.Name as MachineName,
            cat.Description as Category,
            s.Shape,
            g.Grade,
			CASE 
                WHEN pcclb.DimWidth IS NOT NULL THEN 
                    CASE 
                        WHEN ROUND(pcclb.DimWidth / 25.4, 3) = ROUND(pcclb.DimWidth / 25.4, 0) THEN
                            CONCAT(barcodesize.DimensionSizesImperial,  ROUND(pcclb.DimWidth / 25.4, 0))
                        ELSE
                            CONCAT(barcodesize.DimensionSizesImperial,  ROUND(pcclb.DimWidth / 25.4, 3))
                    END
                ELSE barcodesize.DimensionSizesImperial
            END as DimensionString,
            REPLACE(pcseq.Description, CHAR(1), '') as Sequence,
            inv.Location,
            ROUND(pcclbp.Width / 25.4, 3) as PartWidth,
            ROUND(pcclbp.Length / 25.4, 3) as PartLength,
            ROUND(inv.Weight * 2.20462) as Weight,
            inv.InventoryItemID
        FROM workpackages as wp
        INNER JOIN productioncontroljobs as pcj ON pcj.ProductionControlID = wp.ProductionControlID
        INNER JOIN productioncontrolsequences as pcseq ON pcseq.WorkPackageID = wp.WorkPackageID
        INNER JOIN productioncontrolcutlistbarcodepatterns as pcclbp ON pcclbp.SequenceID = pcseq.SequenceID
        INNER JOIN productioncontrolcutlistbarcodes as pcclb ON pcclb.ProductionControlCutListBarcodeID = pcclbp.ProductionControlCutListBarcodeID
        INNER JOIN productioncontrolcutlistitems as pccli ON pccli.ProductionControlCutListBarcodeID = pcclb.ProductionControlCutListBarcodeID
        INNER JOIN productioncontrolcutlists as pccl ON pccl.ProductionControlCutListID = pccli.ProductionControlCutListID
        INNER JOIN productioncontrolitems as pci ON pci.ProductionControlID = wp.ProductionControlID 
            AND pci.MainMark = pcclbp.MainMark 
            AND pci.PieceMark = pcclbp.PieceMark
        INNER JOIN shapes as s ON s.ShapeID = pci.ShapeID
        INNER JOIN grades as g ON g.GradeID = pci.GradeID
        INNER JOIN machinegroups as mg ON mg.MachineGroupID = pccl.MachineGroupID
        INNER JOIN machines as m ON m.MachineID = pccl.MachineID
        inner join sizes as barcodesize on barcodesize.SizeID = pcclb.SizeID
        LEFT JOIN (
            SELECT 
                ml.ProductionControlCutListItemID,
                i.ItemID as InventoryItemID,
                iici.Length as StockLength,
                i.Location,
                i.Weight
            FROM fabrication.materiallinks as ml
            INNER JOIN inventoryitemcombinationitems as iici ON iici.InventoryItemCombinationItemID = ml.LinkItemID
            INNER JOIN inventoryitems as i ON i.ItemID = iici.ItemID
            GROUP BY ml.ProductionControlCutListItemID
        ) inv ON inv.ProductionControlCutListItemID = pccli.ProductionControlCutListItemID
        LEFT JOIN productioncontrolcategories as cat ON cat.CategoryID = pci.CategoryID
        LEFT JOIN externalnests as xtn ON xtn.ExternalNestID = pcclb.ExternalNestID
        WHERE wp.Completed = 0 
        AND pcclbp.Quantity > 0 
        AND pccli.DateTimeCut IS NULL
        AND wp.Group2 = :workweek
        ORDER BY NestNumber, MainMark, PieceMark";

    $stmt = $db->prepare($query);
    $stmt->execute([':workweek' => $workweek]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Transform the Length field in each result
    foreach ($results as &$row) {
        if (isset($row['Length']) && is_numeric($row['Length'])) {
            // Store original length in a new field
            $row['LengthDecimal'] = $row['Length'];
            // Transform the Length field to feet and inches format
            $row['Length'] = inchesToFeetAndInches($row['Length']);
        }
    }
    unset($row); // Break the reference

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($results);

} catch(Exception $e) {
    error_log("Error in ajax_get_cutlist_data.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}