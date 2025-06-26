<?php
require_once '../config_ssf_db.php';



$query = "with basic_summary as (
    select
        itr.InspectionTestRecordID,
        itr.InspectionTestID,
        itr.PassedChildInspectionTestRecordID,
        itr.TestFailed,
        itrf.InspectionTestRecordFieldID,
        infs.String as Title,
        abv.String as Abbreviation,
        itrf.ValueStringID,
        itvf.String as ValueString,
        itr.InspectorFirmContactID as InspectorID,
        itr.ParentInspectionTestRecordID
    from fabrication.inspectiontestrecords as itr
    inner join fabrication.inspectiontestrecordfields as itrf on itrf.InspectionTestRecordID = itr.InspectionTestRecordID
    inner join fabrication.inspectiontestfields as inf on inf.InspectionTestFieldID = itrf.InspectionTestFieldID
    inner join fabrication.inspectionteststrings as infs on infs.InspectionTestStringID = inf.FieldTitleStringID
    inner join fabrication.inspectionteststrings as abv on abv.InspectionTestStringID = inf.FieldAbbreviationStringID
    inner join fabrication.inspectionteststrings as itvf on itvf.InspectionTestStringID = itrf.ValueStringID
    where abv.String in ('JOBNUM','MAINMARK','NCRTYPE','INITDATE','NCRDESC')
    )
    
    select 
    basic_summary.InspectionTestRecordID as RecordNum,
    basic_summary.ParentInspectionTestRecordID as ParentInspection,
    MAX(case when basic_summary.Abbreviation = 'INITDATE' then basic_summary.ValueString end) as InitialDate,
    MAX(case when basic_summary.Abbreviation = 'JOBNUM' then basic_summary.ValueString end) as JobNum,
    MAX(case when basic_summary.Abbreviation = 'MAINMARK' then basic_summary.ValueString end) as MainMark,
    MAX(case when basic_summary.Abbreviation = 'NCRTYPE' then basic_summary.ValueString end) as NCRType,
    left(MAX(case when basic_summary.Abbreviation = 'NCRDESC' then basic_summary.ValueString end), 100) as Descrip,
    fc.name as InspectorName,
    itr.ChildInspectionTestRecordID as ChildInspection
    from basic_summary
    inner join fabrication.inspectiontests as it on it.InspectionTestID = basic_summary.InspectionTestID
    inner join fabrication.inspectiontestrecords as itr on itr.InspectionTestID = it.InspectionTestID
    inner join fabrication.firmcontacts as fc on fc.FirmContactID = basic_summary.InspectorID
    where it.InspectionTestID = 29 and basic_summary.TestFailed = 1  and basic_summary.PassedChildInspectionTestRecordID is null
    group by basic_summary.InspectionTestRecordID";

try{
    $stmt = $db->prepare($query);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    header('Content-Type: application/json');
    echo json_encode($results);
} catch (PDOException $e){
    http_response_code(500);
    echo json_encode(['error' => 'Database error occurred']);
}
?>
