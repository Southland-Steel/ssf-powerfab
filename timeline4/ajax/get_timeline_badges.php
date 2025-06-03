<?php
/**
 * File: ajax/get_timeline_badges.php
 * Endpoint for retrieving badge data using the same filter approach as timeline
 */
require_once('../../config_ssf_db.php');
header('Content-Type: application/json');

// Get filter from request - same as timeline endpoint
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Parse filter to extract project filter
$projectFilter = null;
if ($filter !== 'all' && preg_match('/^[A-Za-z0-9\-]+$/', $filter)) {
    $projectFilter = $filter;
}

// Define the resource/task combinations we want to track
$resourceTaskCombinations = [
    ['Customer', 'Client Approval', 'ClientApproval'],
    ['Customer', 'IFC Drawings Received', 'IFCDrawingsReceived']
];

// Convert combinations to SQL for use in query
$resourceTaskSql = "";
foreach ($resourceTaskCombinations as $i => $combination) {
    if ($i > 0) $resourceTaskSql .= "\nUNION ALL\n";
    $resourceTaskSql .= "SELECT '{$combination[0]}' AS ResourceName, '{$combination[1]}' AS TaskDescription, '{$combination[2]}' AS OutputColumnName";
}

// Build the query - same base CTE as timeline, then add badge calculations
$sql = "
WITH ActiveFabricationProjects AS (
    SELECT DISTINCT 
        p.JobNumber,
        CASE 
            WHEN sbde.Level = 1 THEN sbdeval.Description -- Level 1: Description is SequenceName
            WHEN sbde.Level = 2 THEN 
                -- Level 2: Extract parent description as SequenceName
                (SELECT parent_desc.Description 
                 FROM fabrication.schedulebreakdownelements parent
                 INNER JOIN scheduledescriptions parent_desc 
                     ON parent_desc.ScheduleDescriptionID = parent.ScheduleBreakdownValueID
                 WHERE parent.ScheduleBreakdownElementID = sbde.ParentScheduleBreakdownElementID)
        END AS SequenceName,
        CASE 
            WHEN sbde.Level = 1 THEN NULL -- Level 1: No LotNumber
            WHEN sbde.Level = 2 THEN sbdeval.Description -- Level 2: Current description is LotNumber
        END AS LotNumber,
        sbde.Level, -- Include level to distinguish between level 1 and level 2 records
        -- Include RowGroupID as it appears in your original query
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
),

-- Define all resource/task combinations we want to track
ResourceTaskCombinations AS (
    $resourceTaskSql
),

-- Resource/task percentages
ResourceTaskPercentages AS (
    SELECT 
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
        END AS RowGroupID,
        rtc.OutputColumnName,
        AVG(sts.PercentCompleted) * 100 AS PercentageComplete
    FROM fabrication.schedulebreakdownelements AS sbde
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
        " . ($projectFilter !== null ? "AND p.JobNumber = ?" : "") . "
    GROUP BY 
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
        END,
        rtc.OutputColumnName
),

-- Production control summary
SequenceLevelSummary AS (
    SELECT 
        afp.RowGroupID,
        ROUND(SUM(CASE WHEN aps.ApprovalStatus = 'IFF' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(*), 0), 2) AS PercentageIFF,
        ROUND(SUM(CASE WHEN aps.ApprovalStatus = 'IFA' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(*), 0), 2) AS PercentageIFA,
        ROUND(SUM(CASE WHEN pci.CategoryID IS NOT NULL THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(*), 0), 2) AS PercentageCategorized,
        CASE WHEN MAX(CASE WHEN pcseq.WorkPackageID IS NOT NULL THEN 1 ELSE 0 END) = 1 THEN 'Yes' ELSE 'No' END AS HasWorkpackages,
        COUNT(*) AS TotalItems,
        CASE WHEN COUNT(pci.ProductionControlItemID) > 0 THEN 1 ELSE 0 END AS HasPCI
    FROM ActiveFabricationProjects afp
    LEFT JOIN productioncontrolsequences AS pcseq 
        ON REPLACE(pcseq.Description, CHAR(1), '') = afp.SequenceName
    LEFT JOIN productioncontrolitemsequences as pciseq 
        ON pciseq.SequenceID = pcseq.SequenceID
    LEFT JOIN productioncontrolassemblies as pca 
        ON pca.ProductionControlAssemblyID = pciseq.ProductionControlAssemblyID
    LEFT JOIN productioncontrolitems as pci 
        ON pci.ProductionControlAssemblyID = pca.ProductionControlAssemblyID
    LEFT JOIN productioncontroljobs as pcj 
        ON pcj.ProductionControlID = pcseq.ProductionControlID
    LEFT JOIN projects as p 
        ON p.ProjectID = pcj.ProjectID AND p.JobNumber = afp.JobNumber
    LEFT JOIN approvalstatuses as aps 
        ON aps.ApprovalStatusID = pci.ApprovalStatusID
    LEFT JOIN workpackages as wp 
        ON wp.WorkPackageID = pcseq.WorkPackageID
    WHERE 
        afp.Level = 1
    GROUP BY 
        afp.RowGroupID
),

LotLevelSummary AS (
    SELECT 
        afp.RowGroupID,
        ROUND(SUM(CASE WHEN aps.ApprovalStatus = 'IFF' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(*), 0), 2) AS PercentageIFF,
        ROUND(SUM(CASE WHEN aps.ApprovalStatus = 'IFA' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(*), 0), 2) AS PercentageIFA,
        ROUND(SUM(CASE WHEN pci.CategoryID IS NOT NULL THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(*), 0), 2) AS PercentageCategorized,
        CASE WHEN MAX(CASE WHEN pcseq.WorkPackageID IS NOT NULL THEN 1 ELSE 0 END) = 1 THEN 'Yes' ELSE 'No' END AS HasWorkpackages,
        COUNT(*) AS TotalItems,
        CASE WHEN COUNT(pci.ProductionControlItemID) > 0 THEN 1 ELSE 0 END AS HasPCI
    FROM ActiveFabricationProjects afp
    LEFT JOIN productioncontrolsequences AS pcseq 
        ON REPLACE(pcseq.Description, CHAR(1), '') = afp.SequenceName
    LEFT JOIN productioncontrolitemsequences as pciseq 
        ON pciseq.SequenceID = pcseq.SequenceID AND 
           (afp.LotNumber IS NULL OR REPLACE(pcseq.LotNumber, CHAR(1), '') = afp.LotNumber)
    LEFT JOIN productioncontrolassemblies as pca 
        ON pca.ProductionControlAssemblyID = pciseq.ProductionControlAssemblyID
    LEFT JOIN productioncontrolitems as pci 
        ON pci.ProductionControlAssemblyID = pca.ProductionControlAssemblyID
    LEFT JOIN productioncontroljobs as pcj 
        ON pcj.ProductionControlID = pcseq.ProductionControlID
    LEFT JOIN projects as p 
        ON p.ProjectID = pcj.ProjectID AND p.JobNumber = afp.JobNumber
    LEFT JOIN approvalstatuses as aps 
        ON aps.ApprovalStatusID = pci.ApprovalStatusID
    LEFT JOIN workpackages as wp 
        ON wp.WorkPackageID = pcseq.WorkPackageID
    WHERE 
        afp.Level = 2
    GROUP BY 
        afp.RowGroupID
)

-- Final results
SELECT 
    afp.RowGroupID,
    COALESCE(s.PercentageIFF, 0) AS PercentageIFF,
    COALESCE(s.PercentageIFA, 0) AS PercentageIFA,
    COALESCE(s.PercentageCategorized, 0) AS PercentageCategorized,
    COALESCE(s.HasWorkpackages, 'No') AS HasWorkpackages,
    COALESCE(s.TotalItems, 0) AS TotalItems,
    COALESCE(s.HasPCI, 0) AS HasPCI,
    ROUND(MAX(CASE WHEN rtp.OutputColumnName = 'ClientApproval' THEN rtp.PercentageComplete ELSE 0 END), 2) AS ClientApprovalPercentComplete,
    ROUND(MAX(CASE WHEN rtp.OutputColumnName = 'IFCDrawingsReceived' THEN rtp.PercentageComplete ELSE 0 END), 2) AS IFCDrawingsReceivedPercentComplete,
FROM ActiveFabricationProjects afp
LEFT JOIN (
    SELECT * FROM SequenceLevelSummary
    UNION ALL
    SELECT * FROM LotLevelSummary
) s ON afp.RowGroupID = s.RowGroupID
LEFT JOIN ResourceTaskPercentages rtp ON afp.RowGroupID = rtp.RowGroupID
GROUP BY
    afp.RowGroupID,
    COALESCE(s.PercentageIFF, 0),
    COALESCE(s.PercentageIFA, 0),
    COALESCE(s.PercentageCategorized, 0),
    COALESCE(s.HasWorkpackages, 'No'),
    COALESCE(s.TotalItems, 0),
    COALESCE(s.HasPCI, 0)
ORDER BY afp.RowGroupID
";

// Prepare parameter array - much simpler now
$params = [];
if ($projectFilter !== null) {
    // Add the parameter three times since it's used in three CTEs
    $params[] = $projectFilter; // ActiveFabricationProjects
    $params[] = $projectFilter; // ResourceTaskPercentages
}

try {
    // Prepare and execute query
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format response as key-value pairs keyed by RowGroupID
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
            'IFCDrawingsReceivedPercentComplete' => floatval($row['IFCDrawingsReceivedPercentComplete'])
        ];
    }

    echo json_encode($badgeData, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    error_log('Badge data query error: ' . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred: ' . $e->getMessage()]);
}
?>