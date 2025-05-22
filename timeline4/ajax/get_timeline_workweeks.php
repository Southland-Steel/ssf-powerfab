<?php
/**
 * File: ajax/get_timeline_workweeks.php
 * Endpoint for retrieving workweek data using the same filter approach as timeline
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

// Build the workweek query - using the same CTE as timeline for consistent RowGroupIDs
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
        sbde.Level,
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
)

SELECT 
    afp.RowGroupID,
    COUNT(DISTINCT wp.Group2) AS WorkWeekCount,
    GROUP_CONCAT(DISTINCT wp.Group2 ORDER BY wp.Group2 SEPARATOR ',') AS WorkWeeks,
    GROUP_CONCAT(DISTINCT 
        wp.WorkPackageNumber 
        ORDER BY wp.Group2 
        SEPARATOR ','
    ) AS WorkPackageNumbers,
    GROUP_CONCAT(DISTINCT 
        CONCAT('WW', wp.Group2, ' (', 
            DATE_FORMAT(
                STR_TO_DATE(
                    CONCAT(
                        '20', -- For years 2000-2099
                        SUBSTRING(wp.Group2, 1, 2), -- Year
                        SUBSTRING(wp.Group2, 3, 2), -- Week
                        '1' -- First day of week (Monday)
                    ), 
                    '%Y%U%w'
                ), '%m/%d'
            ), '-', 
            DATE_FORMAT(
                DATE_ADD(
                    STR_TO_DATE(
                        CONCAT(
                            '20', -- For years 2000-2099
                            SUBSTRING(wp.Group2, 1, 2), -- Year
                            SUBSTRING(wp.Group2, 3, 2), -- Week
                            '1' -- First day of week (Monday)
                        ), 
                        '%Y%U%w'
                    ),
                    INTERVAL 4 DAY
                ), '%m/%d'
            ), ')'
        )
        ORDER BY wp.Group2 
        SEPARATOR ', '
    ) AS WorkWeekFormatted,
    
    -- JSON string for workweek data - with proper escaping
    CONCAT('[', 
        GROUP_CONCAT(
            DISTINCT CONCAT(
                '{',
                '\"ww\":\"', IFNULL(wp.Group2, ''), '\",',
                '\"wpn\":\"', IFNULL(wp.WorkPackageNumber, ''), '\",',
                '\"released\":', IF(wp.ReleasedToFab IS NULL, '0', wp.ReleasedToFab), ',',
                '\"onhold\":', IF(wp.OnHold IS NULL, '0', wp.OnHold), ',',
                '\"start\":\"', IFNULL(DATE_FORMAT(
                    STR_TO_DATE(
                        CONCAT(
                            '20', -- For years 2000-2099
                            SUBSTRING(wp.Group2, 1, 2), -- Year
                            SUBSTRING(wp.Group2, 3, 2), -- Week
                            '1' -- First day of week (Monday)
                        ), 
                        '%Y%U%w'
                    ), '%Y-%m-%d'
                ), ''), '\",',
                '\"end\":\"', IFNULL(DATE_FORMAT(
                    DATE_ADD(
                        STR_TO_DATE(
                            CONCAT(
                                '20', -- For years 2000-2099
                                SUBSTRING(wp.Group2, 1, 2), -- Year
                                SUBSTRING(wp.Group2, 3, 2), -- Week
                                '1' -- First day of week (Monday)
                            ), 
                            '%Y%U%w'
                        ),
                        INTERVAL 4 DAY
                    ), '%Y-%m-%d'
                ), ''), '\",',
                '\"wednesday\":\"', IFNULL(DATE_FORMAT(
                    DATE_ADD(
                        STR_TO_DATE(
                            CONCAT(
                                '20', -- For years 2000-2099
                                SUBSTRING(wp.Group2, 1, 2), -- Year
                                SUBSTRING(wp.Group2, 3, 2), -- Week
                                '1' -- First day of week (Monday)
                            ), 
                            '%Y%U%w'
                        ),
                        INTERVAL 2 DAY
                    ), '%Y-%m-%d'
                ), ''), '\",',
                '\"display\":\"WW', IFNULL(wp.Group2, ''), ' (', 
                IFNULL(DATE_FORMAT(
                    STR_TO_DATE(
                        CONCAT(
                            '20', -- For years 2000-2099
                            SUBSTRING(wp.Group2, 1, 2), -- Year
                            SUBSTRING(wp.Group2, 3, 2), -- Week
                            '1' -- First day of week (Monday)
                        ), 
                        '%Y%U%w'
                    ), '%m/%d'
                ), ''), '-', 
                IFNULL(DATE_FORMAT(
                    DATE_ADD(
                        STR_TO_DATE(
                            CONCAT(
                                '20', -- For years 2000-2099
                                SUBSTRING(wp.Group2, 1, 2), -- Year
                                SUBSTRING(wp.Group2, 3, 2), -- Week
                                '1' -- First day of week (Monday)
                            ), 
                            '%Y%U%w'
                        ),
                        INTERVAL 4 DAY
                    ), '%m/%d'
                ), ''), ')\"',
                '}'
            )
            ORDER BY wp.Group2
            SEPARATOR ','
        ),
    ']') AS WorkWeekJSON
    
FROM ActiveFabricationProjects afp
INNER JOIN productioncontrolsequences AS pcseq 
    ON REPLACE(pcseq.Description, CHAR(1), '') = afp.SequenceName
    AND (afp.LotNumber IS NULL OR REPLACE(pcseq.LotNumber, CHAR(1), '') = afp.LotNumber)
INNER JOIN productioncontroljobs AS pcj ON pcj.ProductionControlID = pcseq.ProductionControlID
INNER JOIN projects AS p ON p.ProjectID = pcj.ProjectID AND p.JobNumber = afp.JobNumber
INNER JOIN workpackages AS wp ON wp.WorkPackageID = pcseq.WorkPackageID
WHERE 
    pcseq.AssemblyQuantity > 0
    AND wp.Completed = 0  -- Exclude completed workweeks as specified
    AND wp.Group2 IS NOT NULL
    AND wp.WorkshopID = 1
GROUP BY 
    afp.RowGroupID
ORDER BY afp.RowGroupID
";

// Prepare parameter array - much simpler now
$params = [];
if ($projectFilter !== null) {
    $params[] = $projectFilter;
}

try {
    // Prepare and execute query
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format response as key-value pairs keyed by RowGroupID
    $workweekData = [];
    foreach ($rows as $row) {
        // Ensure JSON is valid - if empty, set to empty array
        $workweekJSON = $row['WorkWeekJSON'];
        if (empty($workweekJSON) || $workweekJSON === '[]') {
            $workweekJSON = '[]';
        }

        $workweekData[$row['RowGroupID']] = [
            'WorkWeekCount' => intval($row['WorkWeekCount']),
            'WorkWeeks' => $row['WorkWeeks'],
            'WorkPackageNumbers' => $row['WorkPackageNumbers'],
            'WorkWeekFormatted' => $row['WorkWeekFormatted'],
            'WorkWeekJSON' => $workweekJSON
        ];
    }

    echo json_encode($workweekData, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    error_log('Workweek data query error: ' . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred: ' . $e->getMessage()]);
}
?>