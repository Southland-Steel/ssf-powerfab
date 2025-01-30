<?php

require_once 'config_ssf_db.php';

$sequenceId = $_GET['sequenceId'] ?? '';
$mainMark = $_GET['mainMark'] ?? '';
$pieceMark = $_GET['pieceMark'] ?? '';
$type = $_GET['type'] ?? 'galv';

if (empty($sequenceId) || empty($mainMark)) {
    echo json_encode(['error' => 'Required parameters missing']);
    exit;
}

// Base query
$query = "SELECT 
    pcit.ProductionControlItemTruckID,
    pcit.TruckID,
    pct.TruckNumber,
    pcj.JobNumber,
    REPLACE(pcseq.Description, CHAR(1),'') as Sequence,
    REPLACE(pcseq.LotNumber, CHAR(1),'') as LotNumber,
    wp.WorkPackageNumber as WorkPackageNumber,
    REPLACE(pcit.MainMark, CHAR(1),'') AS MainMark,
    REPLACE(pcit.PieceMark, CHAR(1),'') AS PieceMark,
    pcit.QuantityLoaded,
    pcit.QuantityReturned,
    pct.ShippedDate,
    ROUND(pct.LoadedGrossWeight * 2.2046, 0) as LoadedGrossWeight,
    pct.ShippedToFirmID,
    stfirm.Name as FirmName
FROM productioncontrolitemtrucks AS pcit
INNER JOIN productioncontroljobs AS pcj ON pcj.ProductionControlID = pcit.ProductionControlID
INNER JOIN productioncontrolsequences AS pcseq ON pcseq.SequenceID = pcit.SequenceID
INNER JOIN workpackages AS wp ON wp.WorkPackageID = pcseq.WorkPackageID
INNER JOIN productioncontroltrucks AS pct ON pct.TruckID = pcit.TruckID
INNER JOIN firms AS stfirm ON stfirm.FirmID = pct.ShippedToFirmID
WHERE pcit.SequenceID = :sequenceId 
    AND REPLACE(pcit.MainMark, CHAR(1),'') = :mainMark
    AND pct.Shipped = 1";

// Adjust query if pieceMark is explicitly 'null'
if ($pieceMark === 'null') {
    $query .= " AND pcit.PieceMark IS NULL";
    $params = [
        ':sequenceId' => $sequenceId,
        ':mainMark' => $mainMark
    ];
} else {
    $params = [
        ':sequenceId' => $sequenceId,
        ':mainMark' => $mainMark
    ];
}

try {
    // Prepare the statement
    $stmt = $db->prepare($query);

    // Execute with parameters
    $stmt->execute($params);

    // Fetch all results
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($results, JSON_PRETTY_PRINT);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
