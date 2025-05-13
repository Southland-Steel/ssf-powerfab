<?php
// checkups/ajax/get_pattern_info.php

// Include database connection - use __DIR__ for more reliable path resolution
require_once __DIR__ . '/../../includes/db_connection.php';

// Make sure utility functions are included
require_once __DIR__ . '/../../includes/functions/utility_functions.php';

// Set header to return JSON
header('Content-Type: application/json');

// Get the barcode ID parameter
$barcodeId = isset($_GET['barcodeId']) ? intval($_GET['barcodeId']) : 0;

if (!$barcodeId) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Missing or invalid barcode ID'
    ]);
    exit;
}

try {
    // The SQL query to get pattern information for the selected cutlist item
    $sql = "SELECT
        pcj.JobNumber,
        REPLACE(pcseq.Description, CHAR(1), '') AS SequenceName,
        REPLACE(pcseq.LotNumber, CHAR(1), '') AS LotNumber,
        wp.WorkPackageNumber,
        wp.Group2 as WorkWeek,
        REPLACE(pcclbp.MainMark, CHAR(1), '') AS MainMark,
        REPLACE(pcclbp.PieceMark, CHAR(1), '') AS PieceMark,
        pcclbp.Quantity as PieceMarkQuantity,
        Round(pcclbp.Length / 25.4, 3) as LengthInches
    FROM fabrication.productioncontrolcutlistbarcodepatterns as pcclbp
    INNER JOIN productioncontroljobs as pcj ON pcj.ProductionControlID = pcclbp.ProductionControlID
    INNER JOIN productioncontrolsequences as pcseq ON pcseq.SequenceID = pcclbp.SequenceID
    LEFT JOIN workpackages as wp ON wp.WorkPackageID = pcseq.WorkPackageID
    WHERE pcclbp.ProductionControlCutListBarcodeID = :barcodeId";

    // Prepare and execute the query
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':barcodeId', $barcodeId, PDO::PARAM_INT);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format work week if present
    foreach ($results as &$item) {
        if (!empty($item['WorkWeek'])) {
            $item['WorkWeekFormatted'] = formatWorkWeek($item['WorkWeek']);
        } else {
            $item['WorkWeekFormatted'] = 'N/A';
        }

        // Format length in feet and inches
        if (!empty($item['LengthInches'])) {
            $item['LengthFormatted'] = inchesToFeetAndInches($item['LengthInches']);
        } else {
            $item['LengthFormatted'] = 'N/A';
        }
    }

    // Return data as JSON
    echo json_encode([
        'success' => true,
        'data' => $results,
        'count' => count($results)
    ]);

} catch (PDOException $e) {
    // Log the error
    error_log("Database Error in get_pattern_info.php: " . $e->getMessage());

    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    // Log general errors
    error_log("Error in get_pattern_info.php: " . $e->getMessage());

    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred: ' . $e->getMessage()
    ]);
}