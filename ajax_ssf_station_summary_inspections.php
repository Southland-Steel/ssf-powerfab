<?php

require_once 'medoo_ssf_db.php';
require_once 'converters.php';

// Check if summaryId is provided
if (!isset($_GET['summaryId'])) {
    http_response_code(400);
    echo json_encode(['error' => 'summaryId is required']);
    exit;
}

$summaryID = intval($_GET['summaryId']);

$sql = "SELECT
	isr.InspectionTestRecordID,
	isr.Quantity,
    COALESCE(isr.TestUpdatedDateTime, isr.TestDateTime) AS LastUpdatedTime,
    firmcontacts.Name,
     CASE 
        WHEN isr.TestFailed = 0 THEN 'Pass'
        WHEN isr.TestFailed = 1 THEN 'Failed'
        ELSE 'Unknown'  -- This handles any unexpected values
    END AS TestResult,
    inspectionteststrings.String
FROM inspectiontestrecords as isr
LEFT JOIN inspectiontests ON isr.InspectionTestID = inspectiontests.InspectionTestID
LEFT JOIN inspectionteststrings ON inspectiontests.TitleStringID = inspectionteststrings.InspectionTestStringID
LEFT JOIN firmcontacts ON firmcontacts.FirmContactID = isr.InspectorFirmContactID
WHERE ProductionControlItemStationID IN (
	SELECT pcs.ProductionControlItemStationID
	FROM fabrication.productioncontrolitemstations pcs
	WHERE (pcs.MainMark, pcs.ProductionControlID) IN (
		SELECT pci.MainMark, pci.ProductionControlID
		FROM fabrication.productioncontrolitems pci
		JOIN fabrication.productioncontrolitemstationsummary pciss
		  ON pci.ProductionControlItemID = pciss.ProductionControlItemID
		WHERE pciss.ProductionControlItemStationSummaryID = {$summaryID} AND pcs.StationID = pciss.StationID AND pci.MainPiece = 1
	))
ORDER BY 
    COALESCE(isr.TestUpdatedDateTime, isr.TestDateTime) DESC
        ";

$tkdata = $tkdb->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Output the result as JSON
header('Content-Type: application/json');
echo json_encode($tkdata, JSON_PRETTY_PRINT);