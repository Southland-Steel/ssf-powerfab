<?php
// ajax_get_endpoint.php
// Variable $db is the database connection instance
require_once 'config_ssf_db.php';

// Get category from URL parameter
$categoryName = $_GET['categoryName'] ?? '';

// Basic validation - check if empty
if (empty($categoryName)) {
    echo json_encode(['error' => 'Category name is required']);
    exit;
}

// Query example - replace table/column names as needed
$items = $db->query("
    SELECT *
    FROM items as i
    INNER JOIN categorytable as cat on i.categoryid = cat.categoryid
    WHERE cat.name = :categoryName
    ORDER BY cat.name, i.name
", [':categoryName' => $categoryName])->fetchAll(PDO::FETCH_ASSOC);

// Process data before output if needed
$dataExport = [];
foreach ($items as $item) {
    $dataExport[] = [
        'name' => $item['name'],
        'price' => $item['price'],
        'categoryid' => $item['categoryid']
    ];
}

// Output JSON with formatting for browser readability
echo json_encode($dataExport, JSON_PRETTY_PRINT);