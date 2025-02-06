<?php
//ajax_grid_get_inventory.php

require_once '../config_ssf_db.php';

try {
    $sql = "SELECT 
    i.ItemID,
    i.DimensionString,
    ROUND(i.Valuation,2) as Valuation,
    ROUND(i.CurrentPrice,4) as CurrentPrice,
    i.CurrentPriceUnits,
    ROUND(i.Weight / 2.20462,1) as WeightLbs,
    i.Quantity,
    shapes.Shape,
    sizes.DimensionSizesImperial,
    ROUND(i.Length / 25.4,3) as LengthInches,
    i.OriginalDate,
    i.DeliveryDate,
    i.Location,
    i.SecondaryLocation,
    i.HeatNo,
    i.JobReserve as IsReserved,
    i.Job as JobNumberReserved,
    i.ReserveDate,
    i.PreviousJob as WasReservedToEarlier,
    i.BillOfLadingNo,
    i.PONumber,
    i.Supplier,
    i.DeliveryDate,
    i.ReferenceNumber,
    i.PartNumber,
    costcodes.CostCode,
    i.PercentCombined,
    i.OnOrder,
    GROUP_CONCAT(sn.SerialNumber SEPARATOR ', ') as SerialNumbers
FROM fabrication.inventoryitems as i 
LEFT JOIN shapes ON shapes.ShapeID = i.ShapeID
LEFT JOIN sizes ON sizes.SizeID = i.SizeID
LEFT JOIN costcodes ON costcodes.CostCodeID = i.CostCodeID
LEFT JOIN inventoryitemserialnumbers sn ON i.ItemID = sn.ItemID
WHERE Quantity > 0 AND i.OnOrder = " . (isset($_GET['onOrder']) ? $_GET['onOrder'] : '0') . "
GROUP BY i.ItemID
ORDER BY OriginalDate ASC, Shape, DimensionString";

    // Add location filter if provided
    // Replace the existing location filter with this:
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

    error_log('Final SQL: ' . $sql); // Debug log

    $inventory = $db->query($sql)->fetchAll();

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