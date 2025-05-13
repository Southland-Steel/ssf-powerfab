<?php
//ajax_get_inventory_item.php

require_once '../config_ssf_db.php';

// Add our length conversion functions
function convertToFeetInchesFraction($inches) {
    if ($inches <= 0) {
        return "0\"";
    }

    // Calculate feet and remaining inches
    $feet = floor($inches / 12);
    $remainingInches = $inches - ($feet * 12);

    // Get the whole inches part
    $wholeInches = floor($remainingInches);

    // Calculate the fractional part (to nearest 1/16)
    $fraction = $remainingInches - $wholeInches;
    $sixteenths = round($fraction * 16);

    // Simplify the fraction if possible
    if ($sixteenths == 16) {
        $wholeInches++;
        $sixteenths = 0;
    }

    // Handle case where rounding pushes inches to 12
    if ($wholeInches == 12) {
        $feet++;
        $wholeInches = 0;
    }

    // Format the output string
    $result = "";

    // Only include feet if greater than 0
    if ($feet > 0) {
        $result .= $feet . "' ";
    }

    // Add the inches
    $result .= $wholeInches;

    // Add fraction if not zero
    if ($sixteenths > 0) {
        // Simplify the fraction
        $gcd = gcd($sixteenths, 16);
        $numerator = $sixteenths / $gcd;
        $denominator = 16 / $gcd;

        $result .= " " . $numerator . "/" . $denominator;
    }

    // Add inch symbol
    $result .= "\"";

    return $result;
}

// Helper function to find greatest common divisor
function gcd($a, $b) {
    while ($b != 0) {
        $t = $b;
        $b = $a % $b;
        $a = $t;
    }
    return $a;
}

try {
    // Check if itemId was provided
    if (!isset($_GET['itemId'])) {
        throw new Exception("Item ID is required");
    }

    $itemId = intval($_GET['itemId']);

    // Get complete item details
    $sql = "SELECT 
    i.ItemID,
    i.DimensionString,
    ROUND(i.Valuation, 2) AS Valuation,
    ROUND(i.CurrentPrice, 4) AS CurrentPrice,
    i.CurrentPriceUnits,
    ROUND(i.Weight / 2.20462, 1) AS WeightLbs,
    i.Quantity,
    shapes.Shape,
    sizes.DimensionSizesImperial,
    ROUND(i.Length / 25.4, 3) AS LengthInches,
    i.OriginalDate,
    i.DeliveryDate,
    i.Location,
    i.SecondaryLocation,
    i.HeatNo,
    i.JobReserve AS IsReserved,
    i.Job AS JobNumberReserved,
    i.ReserveDate,
    i.PreviousJob AS WasReservedToEarlier,
    i.BillOfLadingNo,
    i.PONumber,
    i.Supplier,
    i.ReferenceNumber,
    i.PartNumber,
    costcodes.CostCode,
    i.PercentCombined,
    GROUP_CONCAT(DISTINCT pccl.Description SEPARATOR ', ') AS CutlistName,
    GROUP_CONCAT(DISTINCT pcclb.Barcode SEPARATOR ', ') AS CutlistBarcode,
    m.Name as MachineName,
    i.OnOrder
FROM fabrication.inventoryitems AS i 
LEFT JOIN shapes ON shapes.ShapeID = i.ShapeID
LEFT JOIN sizes ON sizes.SizeID = i.SizeID
LEFT JOIN costcodes ON costcodes.CostCodeID = i.CostCodeID
LEFT JOIN inventoryitemcombinationitems AS iici 
    ON iici.ItemID = i.ItemID
LEFT JOIN materiallinks AS ml 
    ON ml.LinkItemID = iici.InventoryItemCombinationItemID 
    AND ml.LinkItemType = 'InventoryCombination'
LEFT JOIN productioncontrolcutlistitems AS pccli 
    ON pccli.ProductionControlCutListItemID = ml.ProductionControlCutListItemID
LEFT JOIN productioncontrolcutlistbarcodes AS pcclb 
    ON pcclb.ProductionControlCutListBarcodeID = pccli.ProductionControlCutListBarcodeID
LEFT JOIN productioncontrolcutlists AS pccl 
    ON pccl.ProductionControlCutListID = pccli.ProductionControlCutListID
LEFT JOIN machines AS m 
    ON m.MachineID = pccl.MachineID
LEFT JOIN productioncontroljobs AS pcj 
    ON pcj.ProductionControlID = pccl.ProductionControlID
WHERE i.ItemID = :itemId
GROUP BY 
    i.ItemID,
    i.DimensionString,
    i.Valuation,
    i.CurrentPrice,
    i.CurrentPriceUnits,
    i.Weight,
    i.Quantity,
    shapes.Shape,
    sizes.DimensionSizesImperial,
    i.Length,
    i.OriginalDate,
    i.DeliveryDate,
    i.Location,
    i.SecondaryLocation,
    i.HeatNo,
    i.JobReserve,
    i.Job,
    i.ReserveDate,
    i.PreviousJob,
    i.BillOfLadingNo,
    i.PONumber,
    i.Supplier,
    i.ReferenceNumber,
    i.PartNumber,
    costcodes.CostCode,
    i.PercentCombined,
    i.OnOrder,
    m.MachineID,
    m.Name,
    pcj.ProductionControlID";

    $stmt = $db->prepare($sql);
    $stmt->bindParam(':itemId', $itemId, PDO::PARAM_INT);
    $stmt->execute();
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        throw new Exception("Item not found");
    }

    // Add formatted length
    $item['LengthFeetInches'] = convertToFeetInchesFraction($item['LengthInches']);

    // Get serial numbers
    $serialQuery = "SELECT SerialNumber FROM fabrication.inventoryitemserialnumbers WHERE ItemID = :itemId";
    $serialStmt = $db->prepare($serialQuery);
    $serialStmt->bindParam(':itemId', $itemId, PDO::PARAM_INT);
    $serialStmt->execute();
    $serialNumbers = $serialStmt->fetchAll(PDO::FETCH_COLUMN);

    $response = [
        'item' => $item,
        'serialNumbers' => $serialNumbers,
        'success' => true
    ];

} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => $e->getMessage()
    ];
    http_response_code(500);
}

header('Content-Type: application/json');
echo json_encode($response);