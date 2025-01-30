<?php
require_once 'config_ssf_db.php';

$testRecordId = isset($_GET['testRecordId']) ? intval($_GET['testRecordId']) : 0;

if (!$testRecordId) {
    die(json_encode(['error' => 'Invalid test record ID']));
}

$query = "SELECT 
    itstring.String as TestType,
    itfstring.String as TestFieldString,
    itrvalstring.String as TestValue,
    itrf.IndicatesFailure
FROM fabrication.inspectiontestrecordfields as itrf
inner join inspectiontestfields as itf on itf.InspectionTestFieldID = itrf.InspectionTestFieldID
inner join inspectionteststrings as itfstring on itfstring.InspectionTestStringID = itf.FieldTitleStringID
inner join inspectionteststrings as itrvalstring on itrvalstring.InspectionTestStringID = itrf.ValueStringID
inner join inspectiontests ON inspectiontests.InspectionTestID = itf.InspectionTestID
inner join inspectionteststrings as itstring ON itstring.InspectionTestStringID = inspectiontests.TitleStringID
where itrf.InspectionTestRecordID = :testRecordId";

try {
    $stmt = $db->prepare($query);
    $stmt->execute([':testRecordId' => $testRecordId]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($results);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error occurred']);
}