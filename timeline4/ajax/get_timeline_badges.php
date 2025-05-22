<?php
/**
 * File: ajax/get_timeline_badges.php
 * Endpoint for retrieving badge data for specific RowGroupIDs
 */
require_once('../../config_ssf_db.php');
header('Content-Type: application/json');

// Get parameters from request
$rowGroupIdsJson = isset($_POST['rowGroupIds']) ? $_POST['rowGroupIds'] : (isset($_GET['rowGroupIds']) ? $_GET['rowGroupIds'] : null);
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Validate input
if (empty($rowGroupIdsJson)) {
    echo json_encode(['error' => 'rowGroupIds parameter is required']);
    exit;
}

// Parse the RowGroupIDs
$rowGroupIds = json_decode($rowGroupIdsJson, true);
if (!is_array($rowGroupIds) || empty($rowGroupIds)) {
    echo json_encode(['error' => 'Invalid rowGroupIds format']);
    exit;
}

// Sanitize RowGroupIDs for SQL (basic validation)
$sanitizedIds = [];
foreach ($rowGroupIds as $id) {
    if (is_string($id) && preg_match('/^[A-Za-z0-9\.\-_]+$/', $id)) {
        $sanitizedIds[] = $id;
    }
}

if (empty($sanitizedIds)) {
    echo json_encode(['error' => 'No valid rowGroupIds provided']);
    exit;
}

// Parse filter to extract project filter
$projectFilter = null;
if ($filter !== 'all' && preg_match('/^[A-Za-z0-9\-]+$/', $filter)) {
    $projectFilter = $filter;
}

// Define the resource/task combinations we want to track
$resourceTaskCombinations = [
    ['Customer', 'Client Approval', 'ClientApproval'],
    ['Customer', 'IFC Drawings Received', 'IFCDrawingsReceived'],
    ['Detailing', 'Issued For Fabrication', 'DetailingIFF']
];

// Convert combinations to SQL for use in query
$resourceTaskSql = "";
foreach ($resourceTaskCombinations as $i => $combination) {
    if ($i > 0) $resourceTaskSql .= "\nUNION ALL\n";
    $resourceTaskSql .= "SELECT '{$combination[0]}' AS ResourceName, '{$combination[1]}' AS TaskDescription, '{$combination[2]}' AS OutputColumnName";
}

// Create placeholders for the IN clause
$placeholders = str_repeat('?,', count($sanitizedIds) - 1) . '?';

// Build the query
$sql = "
WITH TargetRowGroups AS (
    SELECT DISTINCT 
        p.JobNumber,
        CASE 
            WHEN sbde.Level = 1 THEN sbdeval.Description
            WHEN sbde.Level = 2 THEN 
                (SELECT parent_desc.Description 
                 FROM fabrication.schedulebreakdownelements parent
                 INNER JOIN scheduledescriptions parent_desc 
                     ON parent_desc.ScheduleDescriptionID = parent.ScheduleBreakdownValueID
                 WHERE parent.ScheduleBreakdownElementID = sbde.ParentScheduleBreakdownElementID)
        END AS SequenceName,
        CASE 
            WHEN sbde.Level = 1 THEN NULL
            WHEN sbde.Level = 2 THEN sbdeval.Description
        END AS LotNumber,
        sbde.Level,
        CASE 
            WHEN sbde.Level = 1 THEN CONCAT(p.JobNumber,'.',sbdeval.Description)
            WHEN sbde.Level = 2 THEN 
                CONCAT(p.JobNumber,'.',
                    (SELECT parent_desc.Description 
                     FROM fabrication.schedulebreakdownelements parent
                     INNER JOIN scheduledescriptions parent_desc ON parent_desc.ScheduleDescriptionID = parent.ScheduleBreakdownValueID
                     WHERE parent.ScheduleBreakdownElementID = sbde.ParentScheduleBreakdownElementID),
                    '.',
                    sbdeval.Description
                )
            ELSE sbdeval.Description
        END AS RowGroupID
    FROM fabrication.schedulebreakdownelements AS sbde
    INNER JOIN schedulebaselines as sbl ON sbl.ScheduleBaselineID = sbde.ScheduleBaselineID AND sbl.IsCurrent = 1
    INNER JOIN scheduledescriptions AS sbdeval ON sbdeval.ScheduleDescriptionID = sbde.ScheduleBreakdownValueID
    INNER JOIN scheduletasks as sts ON sts.ScheduleBreakdownElementID = sbde.ScheduleBreakdownElementID
    INNER JOIN resources ON resources.ResourceID = sts.ResourceID
    INNER JOIN projects as p ON p.ProjectID = sts.ProjectID
    INNER JOIN scheduledescriptions as sd ON sd.ScheduleDescriptionID = sts.ScheduleDescriptionID
    WHERE 
        p.JobStatusID IN (1,6)
        AND sbde.Level < 3
        AND sts.PercentCompleted < 0.99
        AND resources.Description = 'Fabrication'
        AND sd.Description = 'Fabrication'
        AND sbdeval.Description IS NOT NULL
        AND sts.ActualStartDate IS NOT NULL
        AND sts.ActualEndDate IS NOT NULL
        " . ($projectFilter !== null ? "AND p.JobNumber = ?" : "") . "
        AND CASE 
            WHEN sbde.Level = 1 THEN CONCAT(p.JobNumber,'.',sbdeval.Description)
            WHEN sbde.Level = 2 THEN 
                CONCAT(p.JobNumber,'.',
                    (SELECT parent_desc.Description 
                     FROM fabrication.schedulebreakdownelements parent
                     INNER JOIN scheduledescriptions parent_desc ON parent_desc.ScheduleDescriptionID = parent.ScheduleBreakdownValueID
                     WHERE parent.ScheduleBreakdownElementID = sbde.ParentScheduleBreakdownElementID),
                    '.',
                    sbdeval.Description
                )
            ELSE sbdeval.Description
        END IN ($placeholders)
),

-- Define all resource/task combinations we want to track
ResourceTaskCombinations AS (
    $resourceTaskSql
),

-- Resource/task percentages for target rows only
ResourceTaskPercentages AS (
    SELECT 
        trg.RowGroupID,
        rtc.OutputColumnName,
        AVG(sts.PercentCompleted) * 100 AS PercentageComplete
    FROM TargetRowGroups trg
    INNER JOIN fabrication.schedulebreakdownelements AS sbde ON 
        CASE 
            WHEN trg.Level = 1 THEN CONCAT(trg.JobNumber,'.',trg.SequenceName)
            WHEN trg.Level = 2 THEN CONCAT(trg.JobNumber,'.',trg.SequenceName,'.',trg.LotNumber)
        END = trg.RowGroupID
    INNER JOIN schedulebaselines as sbl ON sbl.ScheduleBaselineID = sbde.ScheduleBaselineID AND sbl.IsCurrent = 1
    INNER JOIN scheduledescriptions AS sbdeval ON sbdeval.ScheduleDescriptionID = sbde.ScheduleBreakdownValueID
    INNER JOIN scheduletasks as sts ON sts.ScheduleBreakdownElementID = sbde.ScheduleBreakdownElementID
    INNER JOIN resources ON resources.ResourceID = sts.ResourceID
    INNER JOIN projects as p ON p.ProjectID = sts.ProjectID
    INNER JOIN scheduledescriptions as sd ON sd.ScheduleDescriptionID = sts.ScheduleDescriptionID
    INNER JOIN ResourceTaskCombinations rtc ON 
        resources.Description = rtc.ResourceName AND
        sd.Description = rtc.TaskDescription
    WHERE 
        p.JobStatusID IN (1,6)
        AND sbde.Level < 3
        AND sbdeval.Description IS NOT NULL
    GROUP BY 
        trg.RowGroupID,
        rtc.OutputColumnName
),

-- Production control summary for target rows
ProductionControlSummary AS (
    SELECT 
        trg.RowGroupID,
        ROUND(SUM(CASE WHEN aps.ApprovalStatus = 'IFF' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(*), 0), 2) AS PercentageIFF,
        ROUND(SUM(CASE WHEN aps.ApprovalStatus = 'IFA' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(*), 0), 2) AS PercentageIFA,
        ROUND(SUM(CASE WHEN pci.CategoryID IS NOT NULL THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(*), 0), 2) AS PercentageCategorized,
        CASE WHEN MAX(CASE WHEN pcseq.WorkPackageID IS NOT NULL THEN 1 ELSE 0 END) = 1 THEN 'Yes' ELSE 'No' END AS HasWorkpackages,
        COUNT(*) AS TotalItems,
        CASE WHEN COUNT(pci.ProductionControlItemID) > 0 THEN 1 ELSE 0 END AS HasPCI
    FROM TargetRowGroups trg
    LEFT JOIN productioncontrolsequences AS pcseq 
        ON REPLACE(pcseq.Description, CHAR(1), '') = trg.SequenceName
    LEFT JOIN productioncontrolitemsequences as pciseq 
        ON pciseq.SequenceID = pcseq.SequenceID AND 
           (trg.LotNumber IS NULL OR REPLACE(pcseq.LotNumber, CHAR(1), '') = trg.LotNumber)
    LEFT JOIN productioncontrolassemblies as pca 
        ON pca.ProductionControlAssemblyID = pciseq.ProductionControlAssemblyID
    LEFT JOIN productioncontrolitems as pci 
        ON pci.ProductionControlAssemblyID = pca.ProductionControlAssemblyID
    LEFT JOIN productioncontroljobs as pcj 
        ON pcj.ProductionControlID = pcseq.ProductionControlID
    LEFT JOIN projects as p 
        ON p.ProjectID = pcj.ProjectID AND p.JobNumber = trg.JobNumber
    LEFT JOIN approvalstatuses as aps 
        ON aps.ApprovalStatusID = pci.ApprovalStatusID
    LEFT JOIN workpackages as wp 
        ON wp.WorkPackageID = pcseq.WorkPackageID
    GROUP BY 
        trg.RowGroupID
)

-- Final results
SELECT 
    trg.RowGroupID,
    COALESCE(pcs.PercentageIFF, 0) AS PercentageIFF,
    COALESCE(pcs.PercentageIFA, 0) AS PercentageIFA,
    COALESCE(pcs.PercentageCategorized, 0) AS PercentageCategorized,
    COALESCE(pcs.HasWorkpackages, 'No') AS HasWorkpackages,
    COALESCE(pcs.TotalItems, 0) AS TotalItems,
    COALESCE(pcs.HasPCI, 0) AS HasPCI,
    ROUND(MAX(CASE WHEN rtp.OutputColumnName = 'ClientApproval' THEN rtp.PercentageComplete ELSE 0 END), 2) AS ClientApprovalPercentComplete,
    ROUND(MAX(CASE WHEN rtp.OutputColumnName = 'IFCDrawingsReceived' THEN rtp.PercentageComplete ELSE 0 END), 2) AS IFCDrawingsReceivedPercentComplete,
    ROUND(MAX(CASE WHEN rtp.OutputColumnName = 'DetailingIFF' THEN rtp.PercentageComplete ELSE 0 END), 2) AS DetailingIFFPercentComplete
FROM TargetRowGroups trg
LEFT JOIN ProductionControlSummary pcs ON trg.RowGroupID = pcs.RowGroupID
LEFT JOIN ResourceTaskPercentages rtp ON trg.RowGroupID = rtp.RowGroupID
GROUP BY
    trg.RowGroupID,
    pcs.PercentageIFF,
    pcs.PercentageIFA,
    pcs.PercentageCategorized,
    pcs.HasWorkpackages,
    pcs.TotalItems,
    pcs.HasPCI
ORDER BY trg.RowGroupID
";

// Prepare parameter array
$params = [];
if ($projectFilter !== null) {
    $params[] = $projectFilter;
}
// Add the RowGroupIDs to parameters
$params = array_merge($params, $sanitizedIds);

try {
    // Prepare and execute query
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format response
    $badgeData = [];
    foreach ($rows as $row) {
        $badgeData[$row['RowGroupID']] = [
            'PercentageIFF' => floatval($row['PercentageIFF']),
            'PercentageIFA' => floatval($row['PercentageIFA']),
            'PercentageCategorized' => floatval($row['PercentageCategorized']),
            'HasWorkpackages' => $row['HasWorkpackages'],
            'TotalItems' => intval($row['TotalItems']),
            'HasPCI' => intval($row['HasPCI']),
            'ClientApprovalPercentComplete' => floatval($row['ClientApprovalPercentComplete']),
            'IFCDrawingsReceivedPercentComplete' => floatval($row['IFCDrawingsReceivedPercentComplete']),
            'DetailingIFFPercentComplete' => floatval($row['DetailingIFFPercentComplete'])
        ];
    }

    echo json_encode($badgeData, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    error_log('Badge data query error: ' . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
}
?>