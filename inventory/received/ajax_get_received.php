<?php
require_once '../../config_ssf_db.php';
header('Content-Type: application/json');

// Get date range from request
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-1 day'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Base query for inventory data
$query = "SELECT 
    DeliveryDate AS trans_date,
    Quantity AS qty,
    OnOrder,
    CASE 
        WHEN Quantity = 0 THEN 'po'
        ELSE 'rec'
    END AS trans_type,
    'inv' as tablesource,
    ItemID,
    TrueShapeID,
    DimensionString,
    ROUND(Valuation,2) AS Valuation,
    ROUND(Weight * 2.20462, 1) as WeightLb,
    shapes.Shape,
    grades.Grade,
    sizes.DimensionSizesImperial,
    ROUND(Length / 25.4, 3) as LengthIn,
    JobReserve,
    Job,
    ReserveDate,
    PreviousJob,
    OriginalJob,
    CurrentPrice,
    CurrentPriceUnits,
    Location,
    SecondaryLocation,
    HeatNo,
    PONumber,
    Supplier,
    BillOfLadingNo,
    NULL as Category,
    NULL as SubCategory,
    NULL as TFSDate,
    NULL as TFSJob
FROM inventoryitems
INNER JOIN shapes ON shapes.ShapeID = inventoryitems.ShapeID
INNER JOIN sizes ON sizes.SizeID = inventoryitems.SizeID
INNER JOIN grades ON grades.GradeID = inventoryitems.GradeID
WHERE DeliveryDate BETWEEN :start_date AND :end_date

UNION ALL

SELECT 
    DeliveryDate AS trans_date,
    Quantity AS qty,
    OnOrder,
    'rec' AS trans_type,
    'ihist' as tablesource,
    ItemID,
    TrueShapeID,
    DimensionString,
    ROUND(Valuation,2) AS Valuation,
    ROUND(Weight * 2.20462, 1) as WeightLb,
    shapes.Shape,
    grades.Grade,
    sizes.DimensionSizesImperial,
    ROUND(Length / 25.4, 3) as LengthIn,
    JobReserve,
    Job,
    ReserveDate,
    PreviousJob,
    OriginalJob,
    CurrentPrice,
    CurrentPriceUnits,
    Location,
    SecondaryLocation,
    HeatNo,
    PONumber,
    Supplier,
    BillOfLadingNo,
    Category,
    SubCategory,
    TFSDate,
    TFSJob
FROM inventoryhistoryitems
INNER JOIN shapes ON shapes.ShapeID = inventoryhistoryitems.ShapeID
INNER JOIN sizes ON sizes.SizeID = inventoryhistoryitems.SizeID
INNER JOIN grades ON grades.GradeID = inventoryhistoryitems.GradeID
WHERE DeliveryDate BETWEEN :start_date AND :end_date

UNION ALL

SELECT 
    TFSDate AS trans_date,
    Quantity * -1 AS qty,
    OnOrder,
    'tfs' AS trans_type,
    'ihist' as tablesource,
    ItemID,
    TrueShapeID,
    DimensionString,
    ROUND(Valuation,2) AS Valuation,
    ROUND(Weight * 2.20462, 1) as WeightLb,
    shapes.Shape,
    grades.Grade,
    sizes.DimensionSizesImperial,
    ROUND(Length / 25.4, 3) as LengthIn,
    JobReserve,
    Job,
    ReserveDate,
    PreviousJob,
    OriginalJob,
    CurrentPrice,
    CurrentPriceUnits,
    Location,
    SecondaryLocation,
    HeatNo,
    PONumber,
    Supplier,
    BillOfLadingNo,
    Category,
    SubCategory,
    TFSDate,
    TFSJob
FROM inventoryhistoryitems
INNER JOIN shapes ON shapes.ShapeID = inventoryhistoryitems.ShapeID
INNER JOIN sizes ON sizes.SizeID = inventoryhistoryitems.SizeID
INNER JOIN grades ON grades.GradeID = inventoryhistoryitems.GradeID
WHERE DeliveryDate BETWEEN :start_date AND :end_date
ORDER BY trans_date DESC";

$stmt = $db->prepare($query);
$stmt->execute([
    ':start_date' => $start_date,
    ':end_date' => $end_date
]);

$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate summary data
$summary = [
    'total_items' => count($results),
    'total_quantity' => array_sum(array_column($results, 'qty')),
    'filters' => [
        'shapes' => array_values(array_unique(array_filter(array_column($results, 'Shape')))),
        'po_numbers' => array_values(array_unique(array_filter(array_column($results, 'PONumber')))),
        'vendors' => array_values(array_unique(array_filter(array_column($results, 'Supplier'))))
    ]
];

// Handle CSV export if requested
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="inventory_movement_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');

    // Write headers
    if (!empty($results)) {
        fputcsv($output, array_keys($results[0]));
    }

    // Write data
    foreach ($results as $row) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit;
} else {
    // Return JSON for normal requests
    echo json_encode([
        'data' => $results,
        'summary' => $summary
    ]);
}