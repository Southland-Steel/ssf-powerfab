<?php
/**
 * File: ajax/get_timeline_workweeks.php
 * Endpoint for retrieving workweek data for specific RowGroupIDs
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

// Create placeholders for the IN clause
$placeholders = str_repeat('?,', count($sanitizedIds) - 1) . '?';

// Build the workweek query
$sql = "
SELECT 
    CASE 
        WHEN REPLACE(pcseq.LotNumber, CHAR(1), '') = '' OR REPLACE(pcseq.LotNumber, CHAR(1), '') IS NULL THEN 
            CONCAT(p.JobNumber, '.', REPLACE(pcseq.Description, CHAR(1), ''))
        ELSE 
            CONCAT(p.JobNumber, '.', REPLACE(pcseq.Description, CHAR(1), ''), '.', REPLACE(pcseq.LotNumber, CHAR(1), ''))
    END AS RowGroupID,
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
    
FROM productioncontrolsequences AS pcseq
INNER JOIN productioncontroljobs AS pcj ON pcj.ProductionControlID = pcseq.ProductionControlID
INNER JOIN projects AS p ON p.ProjectID = pcj.ProjectID
INNER JOIN workpackages AS wp ON wp.WorkPackageID = pcseq.WorkPackageID
WHERE 
    pcseq.AssemblyQuantity > 0
    AND wp.Completed = 0  -- Exclude completed workweeks as specified
    AND wp.Group2 IS NOT NULL
    AND wp.WorkshopID = 1
    " . ($projectFilter !== null ? "AND p.JobNumber = ?" : "") . "
    AND CASE 
        WHEN REPLACE(pcseq.LotNumber, CHAR(1), '') = '' OR REPLACE(pcseq.LotNumber, CHAR(1), '') IS NULL THEN 
            CONCAT(p.JobNumber, '.', REPLACE(pcseq.Description, CHAR(1), ''))
        ELSE 
            CONCAT(p.JobNumber, '.', REPLACE(pcseq.Description, CHAR(1), ''), '.', REPLACE(pcseq.LotNumber, CHAR(1), ''))
    END IN ($placeholders)
GROUP BY 
    CASE 
        WHEN REPLACE(pcseq.LotNumber, CHAR(1), '') = '' OR REPLACE(pcseq.LotNumber, CHAR(1), '') IS NULL THEN 
            CONCAT(p.JobNumber, '.', REPLACE(pcseq.Description, CHAR(1), ''))
        ELSE 
            CONCAT(p.JobNumber, '.', REPLACE(pcseq.Description, CHAR(1), ''), '.', REPLACE(pcseq.LotNumber, CHAR(1), ''))
    END
ORDER BY RowGroupID
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
    echo json_encode(['error' => 'Database error occurred']);
}
?>