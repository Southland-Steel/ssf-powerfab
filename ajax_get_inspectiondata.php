<?php
require_once 'config_ssf_db.php';

$sequenceId = $_GET['sequenceId'] ?? '';
$mainMark = $_GET['mainMark'] ?? '';
$pieceMark = $_GET['pieceMark'] ?? '';
$inspectionType = $_GET['inspectiontype'] ?? '';
$showAll = isset($_GET['showall']) ? 1 : 0;

if (empty($sequenceId) || empty($mainMark) || empty($pieceMark)) {
    echo json_encode(['error' => 'Required parameters missing']);
    exit;
}

$query = "SELECT 
    itsubtype.JobNumber,
    REPLACE(itsubtype.MainMark,CHAR(1),'') AS MainMark,
    REPLACE(itsubtype.PieceMark,CHAR(1),'') AS PieceMark,
    REPLACE(itsubtype.Sequence,CHAR(1),'') AS Sequence,
    REPLACE(itsubtype.LotNumber,CHAR(1),'') AS LotNumber,
    itsubtype.WorkPackageNumber,
    itsubtype.LoadNumber,
    itr.InspectionTestRecordID,
    itr.ParentInspectionTestRecordID,
    itr.ChildInspectionTestRecordID,
    itr.PassedChildInspectionTestRecordID,
    itr.Quantity,
    itr.TestDateTime,
    itr.TestFailed,
    fc.Name as InspectorName,
    ittitlestring.String as InspectionType
FROM fabrication.inspectiontestsubtypes as itsubtype
INNER JOIN inspectiontestrecords as itr on itr.InspectionTestSubTypeID = itsubtype.InspectionTestSubTypeID
INNER JOIN inspectiontests as itest on itest.InspectionTestID = itr.InspectionTestID
INNER JOIN inspectionteststrings as ittitlestring ON ittitlestring.InspectionTestStringID = itest.TitleStringID
INNER JOIN firmcontacts as fc on fc.FirmContactID = itr.InspectorFirmContactID
WHERE itsubtype.SequenceID = :sequenceId 
    AND REPLACE(itsubtype.MainMark, CHAR(1),'') = :mainMark 
    AND REPLACE(itsubtype.PieceMark, CHAR(1),'') = :pieceMark
    AND (
        (:showAll1 = 0 AND itr.TestFailed = 1 
            AND itr.ChildInspectionTestRecordID IS NULL 
            AND itr.PassedChildInspectionTestRecordID IS NULL)
        OR :showAll2 = 1
    )";

if (!empty($inspectionType)) {
    $query .= " AND ittitlestring.String = :inspectionType";
}

$query .= " ORDER BY itr.TestDateTime DESC";

$params = [
    ':sequenceId' => $sequenceId,
    ':mainMark' => $mainMark,
    ':pieceMark' => $pieceMark,
    ':showAll1' => $showAll,
    ':showAll2' => $showAll
];

if (!empty($inspectionType)) {
    $params[':inspectionType'] = $inspectionType;
}

try {
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($results, JSON_PRETTY_PRINT);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}