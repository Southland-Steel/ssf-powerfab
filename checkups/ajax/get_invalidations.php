<?php
// checkups/ajax/get_invalidations.php

// Include database connection - use __DIR__ for more reliable path resolution
require_once __DIR__ . '/../../includes/db_connection.php';

// Make sure utility functions are included
require_once __DIR__ . '/../../includes/functions/utility_functions.php';

// Set header to return JSON
header('Content-Type: application/json');

// Check if we just need to return the count
$count_only = isset($_GET['count_only']) && $_GET['count_only'] == 1;

try {
    // The SQL query to get invalidated cutlist items
    $sql = "SELECT 
                pcli.ProductionControlCutListID, 
                pcli.ProductionControlCutListItemID, 
                pcli.ProductionControlCutListBarcodeID, 
                pcl.DateTimeCreated, 
                pcli.DateTimeInvalidated, 
                pcl.Description as CutlistDescription, 
                machines.Name as MachineName, 
                workshops.Name as WorkshopName, 
                pcl.InvalidatedItems as CutlistInvalidatedItemCount, 
                pcl.CompletedItems as CutlistCompletedItems, 
                pcl.RemainingItems as CutlistRemainingItems, 
                xtn.ExternalNestExtra1 as CutlistNumber1, 
                xtn.ExternalNestExtra2 as CutlistNumber2, 
                shapes.Shape as ShapeName, 
                grades.Grade, 
                sizes.DimensionSizesImperial, 
                ROUND(pcclb.Length / 25.4, 3) as LengthInches 
            FROM 
                fabrication.productioncontrolcutlistitems as pcli 
                INNER JOIN productioncontrolcutlists as pcl ON pcl.ProductionControlCutListID = pcli.ProductionControlCutListID 
                LEFT JOIN machines ON machines.MachineID = pcl.MachineID 
                LEFT JOIN workshops ON workshops.WorkshopID = pcl.WorkshopID 
                LEFT JOIN productioncontrolcutlistbarcodes as pcclb ON pcclb.ProductionControlCutListBarcodeID = pcli.ProductionControlCutListBarcodeID 
                LEFT JOIN externalnests as xtn ON xtn.ExternalNestID = pcclb.ExternalNestID 
                LEFT JOIN shapes ON shapes.shapeID = pcclb.ShapeID 
                LEFT JOIN grades ON grades.gradeID = pcclb.GradeID 
                LEFT JOIN sizes ON sizes.SizeID = pcclb.SizeID 
            WHERE 
                pcli.DateTimeInvalidated IS NOT NULL 
                AND pcli.DateTimeCut IS NULL 
                AND pcl.IsCompleted = 0";

    if ($count_only) {
        // Modify the query to just get the count
        $countSql = "SELECT COUNT(*) as count FROM ($sql) as subquery";
        $stmt = $db->prepare($countSql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode(['count' => (int)$result['count']]);
        exit;
    }

    // Execute the full query
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format dates
    foreach ($results as &$item) {
        // Format dates
        if (!empty($item['DateTimeCreated'])) {
            $created = new DateTime($item['DateTimeCreated']);
            $item['DateTimeCreatedFormatted'] = $created->format('Y-m-d H:i:s');
            $item['DateTimeCreatedRelative'] = getRelativeTime($created);
        } else {
            $item['DateTimeCreatedFormatted'] = 'N/A';
            $item['DateTimeCreatedRelative'] = 'N/A';
        }

        if (!empty($item['DateTimeInvalidated'])) {
            $invalidated = new DateTime($item['DateTimeInvalidated']);
            $item['DateTimeInvalidatedFormatted'] = $invalidated->format('Y-m-d H:i:s');
            $item['DateTimeInvalidatedRelative'] = getRelativeTime($invalidated);
        } else {
            $item['DateTimeInvalidatedFormatted'] = 'N/A';
            $item['DateTimeInvalidatedRelative'] = 'N/A';
        }
    }

    // Return data as JSON
    echo json_encode([
        'success' => true,
        'data' => $results,
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (PDOException $e) {
    // Log the error
    error_log("Database Error in get_invalidations.php: " . $e->getMessage());

    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    // Log general errors
    error_log("Error in get_invalidations.php: " . $e->getMessage());

    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred: ' . $e->getMessage()
    ]);
}

/**
 * Get relative time string (e.g. "2 hours ago")
 *
 * @param DateTime $dateTime The date/time to format
 * @return string The formatted relative time
 */
function getRelativeTime($dateTime) {
    $now = new DateTime();
    $diff = $now->diff($dateTime);

    if ($diff->y > 0) {
        return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    }
    if ($diff->m > 0) {
        return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    }
    if ($diff->d > 0) {
        return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    }
    if ($diff->h > 0) {
        return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    }
    if ($diff->i > 0) {
        return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    }

    return 'Just now';
}