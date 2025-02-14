<?php
require_once '../config_ssf_db.php';

$sequences = json_decode($_POST['sequences']); // Changed from jobs to sequences
if (!is_array($sequences)) {
    die(json_encode(['error' => 'Invalid input']));
}

$sequenceList = implode(',', $sequences); // Changed from jobList to sequenceList

$query = "SELECT 
    pciss.ProductionControlID,
    pciss.SequenceID,
    pcseq.Description as SequenceName,
    CASE 
        WHEN SUM(pciss.QuantityCompleted) >= SUM(pciss.TotalQuantity) THEN 0
        ELSE SUM(pciss.TotalQuantity) - SUM(pciss.QuantityCompleted)
    END as QuantityShort
FROM fabrication.productioncontrolitemstationsummary pciss
INNER JOIN productioncontrolsequences pcseq ON pcseq.SequenceID = pciss.SequenceID
WHERE pciss.SequenceID IN ($sequenceList)
AND pciss.StationID = 29
GROUP BY pciss.SequenceID, pciss.ProductionControlID, pcseq.Description";

$results = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($results);