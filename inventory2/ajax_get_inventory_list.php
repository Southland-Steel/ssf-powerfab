<?php
//ajax_get_inventory_list.php

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

// Convert decimal inches to fractional inches (nearest 1/16)
function convertToFractionalInches($inches) {
    if ($inches <= 0) {
        return 0;
    }

    // Round to nearest 1/16
    $sixteenths = round($inches * 16) / 16;

    return $sixteenths;
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
    // This query only gets the data needed for the main table
    $sql = "SELECT 
    i.ItemID,
    i.DimensionString,
    ROUND(i.Valuation, 2) AS Valuation,
    ROUND(i.Weight / 2.20462, 1) AS WeightLbs,
    i.Quantity,
    shapes.Shape,
    ROUND(i.Length / 25.4, 3) AS LengthInches,
    i.OriginalDate,
    i.DeliveryDate,
    i.Location,
    i.SecondaryLocation,
    i.HeatNo,
    i.JobReserve AS IsReserved,
    i.Job AS JobNumberReserved,
    i.PONumber,
    i.Supplier,
    costcodes.CostCode,
    GROUP_CONCAT(DISTINCT pccl.Description SEPARATOR ', ') AS CutlistName,
    GROUP_CONCAT(DISTINCT pcclb.Barcode SEPARATOR ', ') AS CutlistBarcode,
    m.Name as MachineName,
    i.OnOrder,
    (SELECT EXISTS(SELECT 1 FROM fabrication.inventoryitemserialnumbers WHERE ItemID = i.ItemID)) AS HasSerialNumbers
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
WHERE i.Quantity > 0 
    AND i.OnOrder = " . (isset($_GET['onOrder']) ? $_GET['onOrder'] : '0') . "
GROUP BY 
    i.ItemID,
    i.DimensionString,
    i.Valuation,
    i.Weight,
    i.Quantity,
    shapes.Shape,
    i.Length,
    i.OriginalDate,
    i.DeliveryDate,
    i.Location,
    i.SecondaryLocation,
    i.HeatNo,
    i.JobReserve,
    i.Job,
    i.PONumber,
    i.Supplier,
    costcodes.CostCode,
    i.OnOrder,
    m.MachineID,
    m.Name,
    pcj.ProductionControlID
ORDER BY i.OriginalDate ASC, shapes.Shape, i.DimensionString
";

    // Add location filter if provided
    if (isset($_GET['locations']) && !empty($_GET['locations'])) {
        $locations = json_decode($_GET['locations']);
        if (!empty($locations)) {
            $locationList = implode("','", array_map(function($loc) use ($db) {
                return $db->quote(trim($loc));
            }, $locations));
            $sql .= " AND ("; // Start group
            foreach ($locations as $i => $loc) {
                if ($i > 0) $sql .= " OR ";
                $sql .= "i.Location = " . $db->quote(trim($loc));
            }
            $sql .= ")"; // End group
        }
    }

    error_log('List SQL: ' . $sql); // Debug log

    $inventory = $db->query($sql)->fetchAll();

    // Loop through inventory and add the formatted length
    foreach ($inventory as &$item) {
        // Add the formatted feet-inches-fraction
        $item['LengthFeetInches'] = convertToFeetInchesFraction($item['LengthInches']);

        // Add the fractional inches (to nearest 1/16th)
        $item['LengthInchesExact'] = convertToFractionalInches($item['LengthInches']);
    }
    unset($item); // Unset the reference to avoid issues

    // Get unique locations for filters
    $locationQuery = "SELECT DISTINCT Location FROM fabrication.inventoryitems WHERE Location IS NOT NULL AND Quantity > 0 ORDER BY Location";
    $locations = $db->query($locationQuery)->fetchAll(PDO::FETCH_COLUMN);

    $response = [
        'inventory' => $inventory,
        'locations' => $locations,
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