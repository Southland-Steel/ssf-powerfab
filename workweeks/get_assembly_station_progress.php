<?php
// get_station_progress.php
header('Content-Type: application/json');
require_once '../config_ssf_db.php';

$assembly_id = $_GET['assembly_id'] ?? null;

$query = "SELECT 
    s.StationName,
    COALESCE(sp.Status, 'Not Started') as Status,
    COALESCE(sp.Progress, 0) as Progress
FROM stations s
LEFT JOIN station_progress sp ON sp.StationID = s.StationID 
    AND sp.ProductionControlAssemblyID = :assembly_id
WHERE s.Active = 1
ORDER BY s.StationOrder";

try {
    $stmt = $db->prepare($query);
    $stmt->execute([':assembly_id' => $assembly_id]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}