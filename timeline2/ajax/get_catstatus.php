<?php
/**
 * File: ajax/get_catstatus.php
 * Endpoint for retrieving categorization status for Gantt chart
 */
require_once('../../config_ssf_db.php');
header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['jobSequences']) || !is_array($input['jobSequences']) || empty($input['jobSequences'])) {
        throw new Exception('No job sequences provided.');
    }

    $jobSequences = $input['jobSequences'];

    // Build parameters for IN clause instead of UNION ALL
    $placeholders = [];
    $params = [];
    foreach ($jobSequences as $jobSeq) {
        if (!isset($jobSeq['JobNumber']) || !isset($jobSeq['SequenceName'])) {
            throw new Exception('Invalid job sequence provided.');
        }
        $placeholders[] = "(?, ?)";
        $params[] = $jobSeq['JobNumber'];
        $params[] = $jobSeq['SequenceName'];
    }

    $query = "
    SELECT 
        pcj.JobNumber,
        REPLACE(pcseq.Description, CHAR(1), '') AS SequenceName,
        COUNT(CASE WHEN NULLIF(pccat.Description, '') IS NOT NULL THEN 1 END) AS CategorizedCount,
        COUNT(*) AS TotalItems,
        SUM(CASE WHEN approvalstatuses.ApprovalStatus = 'IFF' THEN 1 ELSE 0 END) AS IFFCount,
        SUM(CASE WHEN approvalstatuses.ApprovalStatus <> 'IFF' 
                 OR approvalstatuses.ApprovalStatus IS NULL THEN 1 ELSE 0 END) AS NotIFFCount
    FROM productioncontrolsequences pcseq
    INNER JOIN productioncontroljobs pcj 
        ON pcj.ProductionControlID = pcseq.ProductionControlID
    INNER JOIN productioncontrolitemsequences pciseq 
        ON pciseq.SequenceID = pcseq.SequenceID
    INNER JOIN productioncontrolassemblies pca 
        ON pca.ProductionControlAssemblyID = pciseq.ProductionControlAssemblyID
    INNER JOIN productioncontrolitems pci 
        ON pci.ProductionControlAssemblyID = pca.ProductionControlAssemblyID
    LEFT JOIN approvalstatuses 
        ON approvalstatuses.ApprovalStatusID = pci.ApprovalStatusID
    LEFT JOIN productioncontrolcategories pccat 
        ON pccat.CategoryID = pci.CategoryID
    WHERE (pcj.JobNumber, REPLACE(pcseq.Description, CHAR(1), '')) IN (" . implode(',', $placeholders) . ")
    GROUP BY 
        pcj.JobNumber,
        REPLACE(pcseq.Description, CHAR(1), '')
    ORDER BY SequenceName";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $ganttData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($ganttData);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>