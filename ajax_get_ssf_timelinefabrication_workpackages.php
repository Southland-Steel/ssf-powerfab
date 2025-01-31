<?php
require_once('config_ssf_db.php');

$query = "SELECT
    pcj.JobNumber,
    REPLACE(pcseq.Description, char(1),'') as Sequence,
    REPLACE(pcseq.LotNumber, char(1),'') as LotNumber,
    wp.WorkPackageNumber,
    wp.Group2 as Workweek,
    STR_TO_DATE(CONCAT('20', LEFT(wp.Group2, 2), ' ', RIGHT(wp.Group2, 2), ' 1'), '%Y %u %w') as StartMonday,
    STR_TO_DATE(CONCAT('20', LEFT(wp.Group2, 2), ' ', RIGHT(wp.Group2, 2), ' 5'), '%Y %u %w') as CompletionFriday,
    wp.ReleasedToFab,
    wp.OnHold,
    wp.AssemblyQuantity as WPAssemblyQuantity,
    pcseq.AssemblyQuantity as SequenceAssemblyQuantity,
    ROUND(wp.GrossWeight * 2.20462,0) as WPGrossWeight,
    ROUND(wp.Hours,1) as Hours,
    wp.WorkPackageID,
    wp.ProductionControlID
FROM workpackages AS wp
INNER JOIN productioncontrolsequences as pcseq ON pcseq.WorkPackageID = wp.WorkPackageID and pcseq.AssemblyQuantity > 0
INNER JOIN productioncontroljobs as pcj ON pcj.ProductionControlID = wp.ProductionControlID
WHERE wp.Completed = 0 and WorkshopID = 1 and pcj.JobStatusID IN (1,6)";

$stmt = $db->query($query);
$workpackages = array();

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $workPackageStatus = '';
    if ($row['OnHold'] == 1) {
        $workPackageStatus = 'On Hold';
    } else if ($row['ReleasedToFab'] == 1) {
        $workPackageStatus = 'Released';
    } else {
        $workPackageStatus = 'Not Released';
    }

    $workpackages[] = array(
        'jobNumber' => $row['JobNumber'],
        'sequence' => $row['Sequence'],
        'lotNumber' => $row['LotNumber'],
        'workPackageNumber' => $row['WorkPackageNumber'],
        'startDate' => $row['StartMonday'],
        'endDate' => $row['CompletionFriday'],
        'released' => $row['ReleasedToFab'],
        'onHold' => $row['OnHold'],
        'wpAssemblyQty' => $row['WPAssemblyQuantity'],
        'seqAssemblyQty' => $row['SequenceAssemblyQuantity'],
        'grossWeight' => $row['WPGrossWeight'],
        'hours' => $row['Hours'],
        'workPackageId' => $row['WorkPackageID'],
        'workWeek' => $row['Workweek'],
        'workPackageStatus' => $workPackageStatus
    );
}

header('Content-Type: application/json');
echo json_encode($workpackages, JSON_PRETTY_PRINT);