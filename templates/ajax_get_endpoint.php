<?php
// templates/ajax_get_endpoint.php

// Include database connection - use __DIR__ for more reliable path resolution
require_once __DIR__ . '/../includes/db_connection.php';

// Ensure utility functions are explicitly included if needed
require_once __DIR__ . '/../includes/functions/utility_functions.php';

// Get parameter from URL
$workweek = $_GET['workweek'] ?? '';

// Basic validation - check if empty
if (empty($workweek)) {
    http_response_code(400);
    echo json_encode(['error' => 'Work week parameter is required']);
    exit;
}

try {
    // Example query - replace with your actual query
    // This is a placeholder that returns dummy data for demonstration

    // In a real scenario, you would query the database like this:
    /*
    $stmt = $db->prepare("
        SELECT *
        FROM your_table
        WHERE work_week = :workweek
        ORDER BY some_column
    ");

    $stmt->execute([':workweek' => $workweek]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    */

    // For now, we'll just generate some sample data
    $sampleData = [];

    // Generate 10 sample rows
    for ($i = 1; $i <= 10; $i++) {
        $sampleData[] = [
            'field1' => "Item {$i} for Work Week {$workweek}",
            'field2' => rand(1000, 9999)
        ];
    }

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($sampleData);

} catch(PDOException $e) {
    // Log the error
    error_log("Database Error in ajax_get_endpoint.php: " . $e->getMessage());

    // Return error response
    http_response_code(500);
    echo json_encode(['error' => 'Database error occurred: ' . $e->getMessage()]);
} catch(Exception $e) {
    // Log general errors
    error_log("Error in ajax_get_endpoint.php: " . $e->getMessage());

    // Return error response
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred: ' . $e->getMessage()]);
}