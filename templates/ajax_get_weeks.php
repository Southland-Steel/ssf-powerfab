<?php
// templates/ajax_get_weeks.php

// Include database connection - use __DIR__ for more reliable path resolution
require_once __DIR__ . '/../includes/db_connection.php';

// Make sure utility functions are included
require_once __DIR__ . '/../includes/functions/utility_functions.php';

try {
    // Get current week using utility function
    $currentWorkweek = getCurrentWorkWeek();

    // Query the database to fetch distinct WorkPackageNumber
    $result = $db->query("
        SELECT DISTINCT Group2 as WorkWeeks 
        FROM workpackages 
        INNER JOIN productioncontroljobs as pcj 
            ON pcj.productionControlID = workpackages.productionControlID 
        WHERE Completed = 0 AND OnHold = 0 
        ORDER BY WorkWeeks ASC;
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Filter and process the weeks
    $weeks = array_filter(array_column($result, 'WorkWeeks'), function($week) {
        return $week !== null && $week !== '';
    });

    sort($weeks);

    // If a specific week is requested, use that; otherwise use current week
    $selectedWeek = $_GET['workweek'] ?? $currentWorkweek;

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'weeks' => array_values($weeks),
        'currentWeek' => $currentWorkweek,
        'selectedWeek' => $selectedWeek
    ]);

} catch(PDOException $e) {
    // Log the error
    error_log("Database Error in ajax_get_weeks.php: " . $e->getMessage());

    // Return error response
    http_response_code(500);
    echo json_encode(['error' => 'Could not fetch work weeks: ' . $e->getMessage()]);
} catch(Exception $e) {
    // Log general errors
    error_log("Error in ajax_get_weeks.php: " . $e->getMessage());

    // Return error response
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred: ' . $e->getMessage()]);
}