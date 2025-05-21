<?php
/**
 * get_sequence_detail.php
 *
 * Retrieves detailed information about a sequence/lot combination including
 * production status across all stations
 *
 * Parameters:
 * - jobNumber: The job number
 * - sequenceName: The sequence name
 * - lotNumber: Optional lot number to filter on
 */

require_once '../../config_ssf_db.php';
header('Content-Type: application/json');

// Get parameters from request
$jobNumber = isset($_GET['jobNumber']) ? $_GET['jobNumber'] : null;
$sequenceName = isset($_GET['sequenceName']) ? $_GET['sequenceName'] : null;
$lotNumber = isset($_GET['lotNumber']) ? $_GET['lotNumber'] : null;

// Validate required parameters
if (!$jobNumber || !$sequenceName) {
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

// Base SQL query
$sql = "SELECT 
   pcj.JobNumber,
   REPLACE(pcseq.Description, CHAR(1), '') as SequenceName,
   REPLACE(pcseq.LotNumber, CHAR(1), '') as LotNumber,
   wp.WorkPackageNumber,
   REPLACE(pciseq.MainMark, CHAR(1),'') as MainMark,
   stations.Description as StationName,
   pciss.TotalQuantity,
   pciss.QuantityCompleted,
   pcidest.QuantityShipped,
   pciseq.ProductionControlItemSequenceID,
   pcat.Description as Category,
   psubcat.Description as SubCategory,
   pca.GrossAssemblyWeightEach,
   pca.DrawingNumber,
   pca.ReportNumber,
   pcia.MaterialGrade,
   pcia.ApprovalStatusID,
   appstat.ApprovalStatus
FROM productioncontroljobs pcj 
INNER JOIN productioncontrolsequences pcseq ON pcseq.ProductionControlID = pcj.ProductionControlID
LEFT JOIN workpackages wp ON wp.WorkPackageID = pcseq.WorkPackageID
INNER JOIN productioncontrolitemsequences pciseq ON pciseq.SequenceID = pcseq.SequenceID
INNER JOIN productioncontrolassemblies pca ON pca.ProductionControlAssemblyID = pciseq.ProductionControlAssemblyID
INNER JOIN productioncontrolitems pcia ON pcia.ProductionControlItemID = pca.MainPieceProductionControlItemID
LEFT JOIN productioncontrolcategories AS pcat ON pcat.CategoryID = pcia.CategoryID
LEFT JOIN productioncontrolsubcategories AS psubcat ON psubcat.SubCategoryID = pcia.SubCategoryID
LEFT JOIN approvalstatuses AS appstat ON appstat.ApprovalStatusID = pcia.ApprovalStatusID
INNER JOIN productioncontrolitemdestinations pcidest ON pcidest.SequenceID = pcseq.SequenceID 
    AND pcidest.ProductionControlItemID = pca.MainPieceProductionControlItemID 
    AND pcidest.PositionInRoute = 2
LEFT JOIN (
   productioncontrolitemstationsummary pciss 
   INNER JOIN stations ON stations.StationID = pciss.StationID
   AND stations.Description IN ('NESTED','CUT','FIT','WELD','FINAL QC')
) ON pciss.ProductionControlItemID = pca.MainPieceProductionControlItemID AND pcseq.SequenceID
WHERE pcj.JobNumber = :jobNumber 
AND pcseq.AssemblyQuantity > 0
AND REPLACE(pcseq.Description, CHAR(1), '') = :sequenceName";

// Add lot number condition if provided
if ($lotNumber) {
    $sql .= " AND REPLACE(pcseq.LotNumber, CHAR(1), '') = :lotNumber";
}

// Prepare and execute the query
$stmt = $db->prepare($sql);
$params = [
    'jobNumber' => $jobNumber,
    'sequenceName' => $sequenceName
];

if ($lotNumber) {
    $params['lotNumber'] = $lotNumber;
}

$stmt->execute($params);

// Process the results
$assemblies = [];
while ($row = $stmt->fetch()) {
    if (!isset($assemblies[$row['ProductionControlItemSequenceID']])) {
        $assemblies[$row['ProductionControlItemSequenceID']] = [
            'Category' => $row['Category'],
            'SubCategory' => $row['SubCategory'],
            'JobNumber' => $row['JobNumber'],
            'SequenceName' => $row['SequenceName'],
            'LotNumber' => $row['LotNumber'],
            'WorkPackageNumber' => $row['WorkPackageNumber'],
            'MainMark' => $row['MainMark'],
            'QuantityShipped' => $row['QuantityShipped'],
            'GrossAssemblyWeightEach' => Round($row['GrossAssemblyWeightEach'] * 2.20462, 1), // Convert to lbs
            'DrawingNumber' => $row['DrawingNumber'],
            'ReportNumber' => $row['ReportNumber'],
            'MaterialGrade' => $row['MaterialGrade'],
            'ApprovalStatusID' => $row['ApprovalStatusID'],
            'ApprovalStatus' => $row['ApprovalStatus'],
            'Stations' => []
        ];
    }

    if ($row['StationName']) {
        $assemblies[$row['ProductionControlItemSequenceID']]['Stations'][$row['StationName']] = [
            'Completed' => $row['QuantityCompleted'],
            'Total' => $row['TotalQuantity']
        ];
    }
}

// Add shipping station to each assembly
foreach ($assemblies as &$assembly) {
    if (isset($assembly['Stations']['FINAL QC'])) {
        $assembly['Stations']['SHIPPING'] = [
            'Completed' => $assembly['QuantityShipped'],
            'Total' => $assembly['Stations']['FINAL QC']['Total']
        ];
    } else {
        // If there's no Final QC data, create a placeholder with zeros
        $assembly['Stations']['SHIPPING'] = [
            'Completed' => $assembly['QuantityShipped'],
            'Total' => 0
        ];
    }
    unset($assembly['QuantityShipped']);
}

// Return the results
echo json_encode(array_values($assemblies));
?>