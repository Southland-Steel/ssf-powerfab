<?php
// ajax_get_weeks.php
require_once '../config_ssf_db.php';

try {
    // Get current week in the format YYWW
    $year = substr(date('o'), -2);  // Get last 2 digits of year
    $week = date('W');              // Get week number (01-53)
    $currentWorkweek = $year . str_pad($week, 2, '0', STR_PAD_LEFT);

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
    echo json_encode(['error' => 'Could not fetch work weeks']);
}