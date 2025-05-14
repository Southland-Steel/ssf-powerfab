<?php
// get_sequence_data.php
header('Content-Type: application/json');
require_once '../config_ssf_db.php';

$sequence_id = $_GET['sequence_id'] ?? null;

$query = "
SELECT
    pciseq.ProductionControlItemSequenceID,
    REPLACE(pciseq.MainMark, CHAR(1), '') AS MainMark,
    pciseq.Quantity,
    ROUND(pca.AssemblyManHoursEach,3) as AssemblyManHoursEach,
    ROUND(pca.GrossAssemblyWeightEach * 2.20462,2) AS GrossAssemblyWeightEach
FROM productioncontrolitemsequences pciseq
INNER JOIN productioncontrolassemblies pca 
    ON pca.ProductionControlAssemblyID = pciseq.ProductionControlAssemblyID
WHERE pciseq.SequenceID = :sequence_id
ORDER BY pciseq.MainMark";

try {
    $stmt = $db->prepare($query);
    $stmt->execute([':sequence_id' => $sequence_id]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}