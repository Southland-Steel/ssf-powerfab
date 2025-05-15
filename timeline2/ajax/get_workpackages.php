<?php
/**
 * File: ajax/get_workpackages.php
 * Endpoint for retrieving workpackage data for Gantt chart
 */
require_once('../../config_ssf_db.php');

$query = "WITH SequenceLevelData AS (
    SELECT DISTINCT
        pcj.JobNumber,
        REPLACE(pcseq.Description, char(1),'') as Sequence,
        wp.WorkPackageNumber,
        wp.Group2 as Workweek,
        STR_TO_DATE(CONCAT('20', LEFT(wp.Group2, 2), ' ', RIGHT(wp.Group2, 2), ' 1'), '%Y %u %w') as StartMonday,
        STR_TO_DATE(CONCAT('20', LEFT(wp.Group2, 2), ' ', RIGHT(wp.Group2, 2), ' 5'), '%Y %u %w') as CompletionFriday,
        wp.ReleasedToFab,
        wp.OnHold,
        wp.AssemblyQuantity as WPAssemblyQuantity,
        SUM(pcseq.AssemblyQuantity) as SequenceAssemblyQuantity,
        ROUND(wp.GrossWeight * 2.20462,0) as WPGrossWeight,
        ROUND(wp.Hours,1) as Hours,
        wp.WorkPackageID,
        wp.ProductionControlID
    FROM workpackages AS wp
    INNER JOIN productioncontrolsequences as pcseq 
        ON pcseq.WorkPackageID = wp.WorkPackageID 
        AND pcseq.AssemblyQuantity > 0
    INNER JOIN productioncontroljobs as pcj 
        ON pcj.ProductionControlID = wp.ProductionControlID
    WHERE wp.Completed = 0 
        AND WorkshopID = 1 
        AND pcj.JobStatusID IN (1,6)
    GROUP BY 
        pcj.JobNumber,
        Sequence,
        wp.WorkPackageNumber,
        wp.Group2,
        wp.ReleasedToFab,
        wp.OnHold,
        wp.AssemblyQuantity,
        wp.GrossWeight,
        wp.Hours,
        wp.WorkPackageID,
        wp.ProductionControlID
)
SELECT * FROM SequenceLevelData";

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
        'workPackageNumber' => $row['WorkPackageNumber'],
        'startDate' => $row['StartMonday'],
        'endDate' => $row['CompletionFriday'],
        'completionfriday' => $row['CompletionFriday'],
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

$jobSequences = [];

// Group by job/sequence and track min/max dates
foreach ($workpackages as $wp) {
    $key = $wp['jobNumber'] . '_' . $wp['sequence'];

    if (!isset($jobSequences[$key])) {
        $jobSequences[$key] = [
            'jobNumber' => $wp['jobNumber'],
            'sequence' => $wp['sequence'],
            'startDate' => $wp['startDate'],
            'endDate' => $wp['endDate']
        ];
    } else {
        // Compare dates
        if ($wp['startDate'] < $jobSequences[$key]['startDate']) {
            $jobSequences[$key]['startDate'] = $wp['startDate'];
        }
        if ($wp['endDate'] > $jobSequences[$key]['endDate']) {
            $jobSequences[$key]['endDate'] = $wp['endDate'];
        }
    }
}

header('Content-Type: application/json');
echo json_encode($workpackages, JSON_PRETTY_PRINT);
?>