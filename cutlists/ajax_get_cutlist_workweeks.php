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
        SELECT DISTINCT wp.Group2 as WorkWeeks
        FROM workpackages as wp
        INNER JOIN productioncontroljobs as pcj 
            ON pcj.ProductionControlID = wp.ProductionControlID
        INNER JOIN productioncontrolsequences as pcseq 
            ON pcseq.WorkPackageID = wp.WorkPackageID
        INNER JOIN productioncontrolcutlistbarcodepatterns as pcclbp 
            ON pcclbp.SequenceID = pcseq.SequenceID
        INNER JOIN productioncontrolcutlistbarcodes as pcclb 
            ON pcclb.ProductionControlCutListBarcodeID = pcclbp.ProductionControlCutListBarcodeID
        INNER JOIN productioncontrolcutlistitems as pccli 
            ON pccli.ProductionControlCutListBarcodeID = pcclb.ProductionControlCutListBarcodeID
        WHERE wp.Completed = 0 
            AND pcclbp.Quantity > 0 
            AND pccli.DateTimeCut IS NULL
            AND wp.Group2 is not null
        ORDER BY wp.Group2;
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