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
from productioncontrolitemtrucks as pcit
inner join productioncontroljobs as pcj on pcj.ProductionControlID = pcit.ProductionControlID
inner join productioncontrolsequences as pcseq ON pcseq.SequenceID = pcit.SequenceID
inner join workpackages as wp ON wp.WorkPackageID = pcseq.WorkPackageID
inner join productioncontroltrucks as pct ON pct.TruckID = pcit.TruckID
inner join firms as stfirm on stfirm.FirmID = pct.ShippedToFirmID
where pcit.SequenceID = :sequenceId 
    and REPLACE(pcit.MainMark, CHAR(1),'') = :mainMark
    and pct.Shipped = 1";

try {
    // Prepare the statement
    $stmt = $db->prepare($query);

    // Execute with parameters
    $stmt->execute([
        ':sequenceId' => $sequenceId,
        ':mainMark' => $mainMark
    ]);

    // Fetch all results
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($results, JSON_PRETTY_PRINT);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}