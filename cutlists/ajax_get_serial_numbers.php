<?php
require_once '../config_ssf_db.php';

try {
    // Get itemid parameter
    $itemid = $_GET['inventoryId'] ?? '';

    if (empty($itemid)) {
        throw new Exception('Inventory ID parameter is required');
    }

    $query = "SELECT SerialNumber FROM fabrication.inventoryitemserialnumbers WHERE ItemID = :itemid";

    $stmt = $db->prepare($query);
    $stmt->execute([':itemid' => $itemid]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($results);

} catch(Exception $e) {
    error_log("Error in ajax_get_serial_numbers.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>