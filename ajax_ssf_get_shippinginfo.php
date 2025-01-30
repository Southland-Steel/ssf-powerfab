<?php
require_once 'config_ssf_db.php';

$query = "
        SELECT 
        pcj.JobNumber,
        pcseq.SequenceID,
        REPLACE(pcseq.Description,CHAR(1),'') as SequenceName,
        REPLACE(pcseq.LotNumber,CHAR(1),'') as LotNumber,
        REPLACE(pci.MainMark, CHAR(1),'') as Mainmark,
        REPLACE(pci.PieceMark, CHAR(1),'') as PieceMark,
        ROUND(SUM((pci.Quantity / pca.AssemblyQuantity) * pciseq.Quantity),0) as RequiredQuantity,
        SUM(pciss.QuantityCompleted) as FabCompleted,
        SUM(pcidestp1.QuantityShipped) as QuantityLoaded,
        SUM(pcidestp1.QuantityReturned) as QuantityReturned,
        SUM(pcidestp2.FailedInspectionTestQuantity) as FailedInspectionTestQuantity,
        SUM(pcidestp2.QuantityShipped) as QuantityShipped,
        ROUND(pca.GrossAssemblyWeightEach,4) as GrossAssemblyWeightEach,
        CASE WHEN pcsub.Description = 'TC' THEN 'TC' ELSE '' END as ToeCrack,
        (
            SELECT COALESCE(SUM(itr.Quantity), 0)
            FROM fabrication.inspectiontestsubtypes itsubtype
            INNER JOIN inspectiontestrecords itr ON itr.InspectionTestSubTypeID = itsubtype.InspectionTestSubTypeID
            INNER JOIN inspectiontests itests ON itests.InspectionTestID = itr.InspectionTestID
            INNER JOIN inspectionteststrings teststring ON teststring.InspectionTestStringID = itests.TitleStringID
            WHERE itsubtype.SequenceID = pcseq.SequenceID
            AND REPLACE(itsubtype.MainMark, CHAR(1),'') = REPLACE(pci.MainMark, CHAR(1),'')
            AND REPLACE(itsubtype.PieceMark, CHAR(1),'') = REPLACE(pci.PieceMark, CHAR(1),'')
            AND itr.ChildInspectionTestRecordID IS NULL
            AND NOT itr.TestFailed
            AND teststring.String = 'TOE CRACK INSPECTION'
        ) as ToeCrackPass,
        (
    SELECT COALESCE(SUM(itr.Quantity), 0)
    FROM fabrication.inspectiontestsubtypes itsubtype
    INNER JOIN inspectiontestrecords itr ON itr.InspectionTestSubTypeID = itsubtype.InspectionTestSubTypeID
    INNER JOIN inspectiontests itests ON itests.InspectionTestID = itr.InspectionTestID
    INNER JOIN inspectionteststrings teststring ON teststring.InspectionTestStringID = itests.TitleStringID
    WHERE itsubtype.SequenceID = pcseq.SequenceID
    AND REPLACE(itsubtype.MainMark, CHAR(1),'') = REPLACE(pci.MainMark, CHAR(1),'')
    AND REPLACE(itsubtype.PieceMark, CHAR(1),'') = REPLACE(pci.PieceMark, CHAR(1),'')
    AND itr.ChildInspectionTestRecordID IS NULL
    AND itr.TestFailed
    AND teststring.String = 'TOE CRACK INSPECTION'
    AND NOT EXISTS (
        SELECT 1 
        FROM inspectiontestrecords itr2
        WHERE itr2.ParentInspectionTestRecordID = itr.InspectionTestRecordID
        AND NOT itr2.TestFailed
    )
) as ToeCrackFail,
        pci.ProductionControlItemID,
        pci.ProductionControlID,
        pcseq.SequenceID,
        wp.Group2 as WorkWeek,
        CASE WHEN (pcidestp1.QuantityShipped > pcidestp2.QuantityShipped && pciss.QuantityCompleted < pcidestp1.QuantityShipped) THEN 'warning' ELSE '' END as Warning
    FROM productioncontrolitemstationsummary as pciss
    INNER JOIN productioncontrolsequences as pcseq ON pcseq.SequenceID = pciss.SequenceID
    INNER JOIN workpackages as wp on wp.workPackageID = pcseq.WorkPackageID
    INNER JOIN productioncontrolitems as pci ON pci.ProductionControlItemID = pciss.ProductionControlItemID
    INNER JOIN productioncontrolassemblies as pca ON pca.ProductionControlAssemblyID = pci.ProductionControlAssemblyID
    INNER JOIN productioncontrolitemsequences as pciseq ON pciseq.ProductionControlAssemblyID = pca.ProductionControlAssemblyID 
        AND pciseq.SequenceID = pcseq.SequenceID
    INNER JOIN productioncontroljobs as pcj ON pcj.ProductionControlID = pciss.ProductionControlID
    LEFT JOIN productioncontrolitemdestinations as pcidestp1 ON pcidestp1.ProductionControlItemID = pciss.ProductionControlItemID 
        AND pcidestp1.PositionInRoute = 1 and pcidestp1.SequenceID = pcseq.SequenceID
    LEFT JOIN productioncontrolitemdestinations as pcidestp2 ON pcidestp2.ProductionControlItemID = pciss.ProductionControlItemID 
        AND pcidestp2.PositionInRoute = 2 and pcidestp2.SequenceID = pcseq.SequenceID
    left join productioncontrolsubcategories as pcsub ON pcsub.SubCategoryID = pci.SubCategoryID
    WHERE pcj.JobStatusID = 6 
        AND pciss.StationID = 29 
        AND pci.MainPiece = 1
        AND wp.WorkShopID = 1
        AND ((pciss.QuantityCompleted > pcidestp2.QuantityShipped) OR (pcidestp1.QuantityShipped > pcidestp2.QuantityShipped && pciss.QuantityCompleted < pcidestp1.QuantityShipped))
    GROUP BY pcj.JobNumber, pcseq.SequenceID, pci.MainMark, pci.PieceMark
    ORDER BY pcseq.SequenceID, pcseq.LotNumber, pci.MainMark, pci.PieceMark
    ";

$items = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($items, JSON_PRETTY_PRINT);