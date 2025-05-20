<?php
/**
 * File: ajax/get_timeline_data.php
 * Enhanced endpoint for retrieving Gantt chart data with resource/task percentages
 */
require_once('../../config_ssf_db.php');
header('Content-Type: application/json');

// Get filter from request
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

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
    // Add more combinations as needed
];

// Convert combinations to SQL for use in query
$resourceTaskSql = "";
foreach ($resourceTaskCombinations as $i => $combination) {
    if ($i > 0) $resourceTaskSql .= "\nUNION ALL\n";
    $resourceTaskSql .= "SELECT '{$combination[0]}' AS ResourceName, '{$combination[1]}' AS TaskDescription, '{$combination[2]}' AS OutputColumnName";
}

// Build the comprehensive query with all CTEs
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
        END AS RowGroupID,
        sbde.ScheduleBreakdownElementID,
        sbde.ParentScheduleBreakdownElementID,
        sbde.Priority,
        p.GroupName AS ProjectManager,
        sts.ScheduleTaskID,
        sd.Description AS ScheduleTaskDescription,
        sts.ActualStartDate,
        sts.ActualEndDate,
        ROUND(sts.PercentCompleted * 100, 2) AS PercentCompleted,
        sts.OriginalEstimate AS PlannedHours
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

-- A single CTE that handles all resource/task combinations at once
ResourceTaskPercentages AS (
    SELECT 
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
        -- Calculate average percentage complete for all matching tasks
        AVG(sts.PercentCompleted) * 100 AS PercentageComplete
    FROM fabrication.schedulebreakdownelements AS sbde
    INNER JOIN schedulebaselines as sbl ON sbl.ScheduleBaselineID = sbde.ScheduleBaselineID AND sbl.IsCurrent = 1
    INNER JOIN scheduledescriptions AS sbdeval ON sbdeval.ScheduleDescriptionID = sbde.ScheduleBreakdownValueID
    INNER JOIN scheduletasks as sts ON sts.ScheduleBreakdownElementID = sbde.ScheduleBreakdownElementID
    INNER JOIN resources ON resources.ResourceID = sts.ResourceID
    INNER JOIN projects as p ON p.ProjectID = sts.ProjectID
    INNER JOIN scheduledescriptions as sd ON sd.ScheduleDescriptionID = sts.ScheduleDescriptionID
    -- Join to the resource/task combinations table to filter for specific combinations
    INNER JOIN ResourceTaskCombinations rtc ON 
        resources.Description = rtc.ResourceName AND
        sd.Description = rtc.TaskDescription
    WHERE 
        p.JobStatusID IN (1,6)
        AND sbde.Level < 3
        AND sbdeval.Description IS NOT NULL
        " . ($projectFilter !== null ? "AND p.JobNumber = ?" : "") . "
    GROUP BY 
        p.JobNumber,
        CASE 
            WHEN sbde.Level = 1 THEN sbdeval.Description
            WHEN sbde.Level = 2 THEN 
                (SELECT parent_desc.Description 
                 FROM fabrication.schedulebreakdownelements parent
                 INNER JOIN scheduledescriptions parent_desc 
                     ON parent_desc.ScheduleDescriptionID = parent.ScheduleBreakdownValueID
                 WHERE parent.ScheduleBreakdownElementID = sbde.ParentScheduleBreakdownElementID)
        END,
        CASE 
            WHEN sbde.Level = 1 THEN NULL
            WHEN sbde.Level = 2 THEN sbdeval.Description
        END,
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

-- Keep your existing CTEs for calculating the base metrics
SequenceLevelSummary AS (
    SELECT 
        afp.JobNumber,
        afp.SequenceName,
        afp.LotNumber,
        afp.RowGroupID,
        -- Calculate percentage of items with IFF
        ROUND(SUM(CASE WHEN aps.ApprovalStatus = 'IFF' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(*), 0), 2) AS PercentageIFF,
        -- Calculate percentage of items with IFA
        ROUND(SUM(CASE WHEN aps.ApprovalStatus = 'IFA' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(*), 0), 2) AS PercentageIFA,
        -- Calculate percentage of items that are categorized
        ROUND(SUM(CASE WHEN pci.CategoryID IS NOT NULL THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(*), 0), 2) AS PercentageCategorized,
        -- Check if any workpackages are associated
        CASE WHEN MAX(CASE WHEN pcseq.WorkPackageID IS NOT NULL THEN 1 ELSE 0 END) = 1 THEN 'Yes' ELSE 'No' END AS HasWorkpackages,
        -- Count of total items
        COUNT(*) AS TotalItems,
        -- Binary indicator for whether there are any PCIs
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
        afp.JobNumber,
        afp.SequenceName,
        afp.LotNumber,
        afp.RowGroupID
),

LotLevelSummary AS (
    SELECT 
        afp.JobNumber,
        afp.SequenceName,
        afp.LotNumber,
        afp.RowGroupID,
        -- Calculate percentage of items with IFF
        ROUND(SUM(CASE WHEN aps.ApprovalStatus = 'IFF' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(*), 0), 2) AS PercentageIFF,
        -- Calculate percentage of items with IFA
        ROUND(SUM(CASE WHEN aps.ApprovalStatus = 'IFA' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(*), 0), 2) AS PercentageIFA,
        -- Calculate percentage of items that are categorized
        ROUND(SUM(CASE WHEN pci.CategoryID IS NOT NULL THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(*), 0), 2) AS PercentageCategorized,
        -- Check if any workpackages are associated
        CASE WHEN MAX(CASE WHEN pcseq.WorkPackageID IS NOT NULL THEN 1 ELSE 0 END) = 1 THEN 'Yes' ELSE 'No' END AS HasWorkpackages,
        -- Count of total items
        COUNT(*) AS TotalItems,
        -- Binary indicator for whether there are any PCIs
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
        afp.JobNumber,
        afp.SequenceName,
        afp.LotNumber,
        afp.RowGroupID
),

-- Final combined results
CombinedResults AS (
    SELECT 
        afp.JobNumber,
        afp.SequenceName,
        afp.LotNumber,
        afp.RowGroupID,
        afp.ScheduleBreakdownElementID,
        afp.ParentScheduleBreakdownElementID,
        afp.Level,
        afp.Priority,
        afp.ProjectManager,
        afp.ScheduleTaskID,
        afp.ScheduleTaskDescription,
        afp.ActualStartDate,
        afp.ActualEndDate,
        afp.PercentCompleted,
        afp.PlannedHours,
        COALESCE(s.PercentageIFF, 0) AS PercentageIFF,
        COALESCE(s.PercentageIFA, 0) AS PercentageIFA,
        COALESCE(s.PercentageCategorized, 0) AS PercentageCategorized,
        COALESCE(s.HasWorkpackages, 'No') AS HasWorkpackages,
        COALESCE(s.TotalItems, 0) AS TotalItems,
        COALESCE(s.HasPCI, 0) AS HasPCI,
        MAX(CASE WHEN rtp.OutputColumnName = 'ClientApproval' THEN rtp.PercentageComplete ELSE 0 END) AS ClientApprovalPercentComplete,
        MAX(CASE WHEN rtp.OutputColumnName = 'IFCDrawingsReceived' THEN rtp.PercentageComplete ELSE 0 END) AS IFCDrawingsReceivedPercentComplete,
        MAX(CASE WHEN rtp.OutputColumnName = 'DetailingIFF' THEN rtp.PercentageComplete ELSE 0 END) AS DetailingIFFPercentComplete
    FROM ActiveFabricationProjects afp
    LEFT JOIN (
        SELECT * FROM SequenceLevelSummary
        UNION ALL
        SELECT * FROM LotLevelSummary
    ) s ON afp.RowGroupID = s.RowGroupID
    LEFT JOIN ResourceTaskPercentages rtp ON afp.RowGroupID = rtp.RowGroupID
    GROUP BY
        afp.JobNumber,
        afp.SequenceName,
        afp.LotNumber,
        afp.RowGroupID,
        afp.ScheduleBreakdownElementID,
        afp.ParentScheduleBreakdownElementID,
        afp.Level,
        afp.Priority,
        afp.ProjectManager,
        afp.ScheduleTaskID,
        afp.ScheduleTaskDescription,
        afp.ActualStartDate,
        afp.ActualEndDate,
        afp.PercentCompleted,
        afp.PlannedHours,
        COALESCE(s.PercentageIFF, 0),
        COALESCE(s.PercentageIFA, 0),
        COALESCE(s.PercentageCategorized, 0),
        COALESCE(s.HasWorkpackages, 'No'),
        COALESCE(s.TotalItems, 0),
        COALESCE(s.HasPCI, 0)
)

-- Select from CombinedResults with rounding on percentage values
SELECT 
    JobNumber,
    SequenceName,
    LotNumber,
    RowGroupID,
    ScheduleBreakdownElementID as elementId,
    ParentScheduleBreakdownElementID as parentId,
    Level as level,
    Priority as priority,
    ProjectManager as pm,
    ScheduleTaskID as id,
    ScheduleTaskDescription as taskDescription,
    SequenceName as description,
    ActualStartDate as startDate,
    ActualEndDate as endDate,
    PercentCompleted as percentage,
    PlannedHours as hours,
    PercentageIFF,
    PercentageIFA,
    PercentageCategorized,
    HasWorkpackages,
    TotalItems,
    HasPCI,
    ROUND(ClientApprovalPercentComplete, 2) as ClientApprovalPercentComplete,
    ROUND(IFCDrawingsReceivedPercentComplete, 2) as IFCDrawingsReceivedPercentComplete,
    ROUND(DetailingIFFPercentComplete, 2) as DetailingIFFPercentComplete,
    CASE 
        WHEN PercentCompleted > 0 AND PercentCompleted < 100 THEN 'in-progress'
        WHEN PercentCompleted >= 100 THEN 'completed'
        ELSE 'not-started'
    END AS status,
    JobNumber as project
FROM CombinedResults
ORDER BY JobNumber, SequenceName, LotNumber, ActualStartDate, ActualEndDate
";

// Prepare parameter array for the query
$params = [];
if ($projectFilter !== null) {
    // Add the parameter twice since it's used in both ActiveFabricationProjects and ResourceTaskPercentages CTEs
    $params[] = $projectFilter;
    $params[] = $projectFilter;
}

// Prepare and execute query
$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process the data for the Gantt chart
$ganttData = processQueryResults($rows);

// Output JSON
echo json_encode($ganttData, JSON_PRETTY_PRINT);

/**
 * Process query results into Gantt chart format
 *
 * @param array $rows Database query results
 * @return array Formatted data for Gantt chart
 */
function processQueryResults($rows) {
    $tasks = [];
    $earliestDate = null;
    $latestDate = null;

    foreach ($rows as $row) {
        // Parse dates with error handling
        $startDate = !empty($row['startDate']) ? $row['startDate'] : null;
        $endDate = !empty($row['endDate']) ? $row['endDate'] : null;

        // Skip if we don't have both dates
        if (empty($startDate) || empty($endDate)) {
            continue;
        }

        // Track earliest and latest dates for chart range
        if ($earliestDate === null || strtotime($startDate) < strtotime($earliestDate)) {
            $earliestDate = $startDate;
        }

        if ($latestDate === null || strtotime($endDate) > strtotime($latestDate)) {
            $latestDate = $endDate;
        }

        // Add the row directly to tasks - it already has all the fields we need
        $tasks[] = $row;
    }

    // Ensure we have a valid date range
    if ($earliestDate === null || $latestDate === null) {
        $today = date('Y-m-d');
        $earliestDate = date('Y-m-d', strtotime('-14 days', strtotime($today)));
        $latestDate = date('Y-m-d', strtotime('+14 days', strtotime($today)));
    } else {
        // Add padding to the date range
        $earliestDate = date('Y-m-d', strtotime('-3 days', strtotime($earliestDate)));
        $latestDate = date('Y-m-d', strtotime('+3 days', strtotime($latestDate)));
    }

    // Sort tasks by start date and then end date
    usort($tasks, function($a, $b) {
        // Get timestamps for comparison
        $aStart = strtotime($a['startDate']);
        $bStart = strtotime($b['startDate']);

        // Compare start dates first
        if ($aStart != $bStart) {
            return $aStart - $bStart;
        }

        // If start dates are identical, compare end dates
        $aEnd = strtotime($a['endDate']);
        $bEnd = strtotime($b['endDate']);

        return $aEnd - $bEnd;
    });

    // Format response
    return [
        'dateRange' => [
            'start' => $earliestDate,
            'end' => $latestDate
        ],
        'tasks' => $tasks
    ];
}
?>