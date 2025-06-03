<?php
/**
 * AJAX Endpoint: Get Production Data
 * Returns production metrics and activity data for a specific machine and time period
 */

require_once '../includes/db_config.php';

// Set JSON header
header('Content-Type: application/json');

// Get and validate parameters
$machine = $_GET['machine'] ?? '';
$period = $_GET['period'] ?? 'today';
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';

if (empty($machine)) {
    http_response_code(400);
    echo json_encode(['error' => 'Machine parameter is required']);
    exit;
}

try {
    // Calculate date range based on period
    $dateCondition = '';
    $params = [$machine];

    switch ($period) {
        case 'today':
            $dateCondition = "AND CAST(f.DATM AS DATE) = CAST(GETDATE() AS DATE)";
            break;
        case 'yesterday':
            $dateCondition = "AND CAST(f.DATM AS DATE) = CAST(DATEADD(day, -1, GETDATE()) AS DATE)";
            break;
        case '7days':
            $dateCondition = "AND f.DATM >= DATEADD(day, -7, GETDATE())";
            break;
        case '30days':
            $dateCondition = "AND f.DATM >= DATEADD(day, -30, GETDATE())";
            break;
        case 'custom':
            if (!empty($startDate) && !empty($endDate)) {
                $dateCondition = "AND f.DATM BETWEEN ? AND ?";
                $params[] = $startDate;
                $params[] = $endDate;
            } else {
                $dateCondition = "AND CAST(f.DATM AS DATE) = CAST(GETDATE() AS DATE)";
            }
            break;
    }

    // Get summary statistics
    $summarySql = "
        SELECT 
            COUNT(DISTINCT CASE WHEN FFR_TYP = 'P' THEN FFR_ID END) AS Total_Parts,
            COUNT(DISTINCT FFR_NEST) AS Total_Nests,
            COUNT(DISTINCT CASE WHEN FFR_TYP = 'N' THEN FFR_CBR END) AS Total_Bars,
            COUNT(CASE WHEN FFR_TYP = 'A' THEN 1 END) AS Total_Stops,
            SUM(CASE WHEN FFR_TYP = 'P' THEN ISNULL(FFR_NBP, 0) ELSE 0 END) AS Parts_Quantity,
            SUM(ISNULL(FFR_RMT7, 0)) AS Production_Time_Sec,
            SUM(ISNULL(FFR_RMT1, 0)) AS Total_Time_Sec,
            SUM(ISNULL(FFR_RMT2, 0)) AS Alarm_Time_Sec,
            SUM(ISNULL(FFR_RMT3, 0)) AS Idle_Time_Sec,
            SUM(ISNULL(FFR_RMT8, 0)) AS Setup_Time_Sec,
            SUM(ISNULL(FFR_RMT9, 0)) AS Material_Wait_Sec
        FROM FEEDBACK_FBK_RAW f
        WHERE f.FFR_CNC = ?
        $dateCondition";

    $summary = $db->queryRow($summarySql, $params);

    // Get all activities for the DataTable
    $activitiesSql = "
        SELECT 
            f.FFR_TYP AS Type,
            f.FFR_NEST AS Nest,
            f.FFR_BON AS Batch,
            f.FFR_COM AS Job,
            f.FFR_DWG AS Sequence,
            f.FFR_RP AS Part_Mark,
            f.FFR_RS AS Piece_Mark,
            f.FFR_PRF AS Size,
            f.FFR_MAT AS Grade,
            f.FFR_PDS AS Weight,
            f.FFR_NBP AS Parts_Count,
            f.FFR_OPE AS Operator,
            f.FFR_DATD AS Start_Date,
            f.FFR_TIMD AS Start_Time,
            f.FFR_DAT AS End_Date,
            f.FFR_TIM AS End_Time,
            f.DATM AS Timestamp,
            f.BAR_ID,
            f.NES_ID,
            CASE
                WHEN f.FFR_TYP = 'N' THEN 'Bar Loaded'
                WHEN f.FFR_TYP = 'P' THEN 'Cutting Parts'
                WHEN f.FFR_TYP = 'A' THEN 'Machine Stopped'
                ELSE 'Unknown'
            END AS Activity_Text
        FROM FEEDBACK_FBK_RAW f
        WHERE f.FFR_CNC = ?
        $dateCondition
        ORDER BY f.DATM DESC";

    $activities = $db->query($activitiesSql, $params);

    // Get nest-level summary
    $nestsSql = "
        SELECT 
            FFR_NEST AS Nest,
            FFR_COM AS Job,
            FFR_DWG AS Drawing,
            MIN(DATM) AS Start_Time,
            MAX(DATM) AS End_Time,
            COUNT(DISTINCT CASE WHEN FFR_TYP = 'P' THEN FFR_ID END) AS Parts_Cut,
            SUM(CASE WHEN FFR_TYP = 'P' THEN ISNULL(FFR_NBP, 0) ELSE 0 END) AS Total_Quantity,
            COUNT(DISTINCT FFR_OPE) AS Operators,
            DATEDIFF(minute, MIN(DATM), MAX(DATM)) AS Duration_Minutes
        FROM FEEDBACK_FBK_RAW
        WHERE FFR_CNC = ?
            AND FFR_NEST IS NOT NULL
            $dateCondition
        GROUP BY FFR_NEST, FFR_COM, FFR_DWG
        ORDER BY MIN(DATM) DESC";

    $nests = $db->query($nestsSql, $params);

    // Get stops analysis
    $stopsSql = "
        SELECT TOP 20
            f1.DATM AS Stop_Time,
            f1.FFR_DATD AS Stop_Date,
            f1.FFR_TIMD AS Stop_Start_Time,
            f1.FFR_DAT AS Stop_End_Date,
            f1.FFR_TIM AS Stop_End_Time,
            DATEDIFF(minute, 
                TRY_CONVERT(datetime, f1.FFR_DATD + ' ' + f1.FFR_TIMD), 
                TRY_CONVERT(datetime, f1.FFR_DAT + ' ' + f1.FFR_TIM)
            ) AS Stop_Duration_Minutes
        FROM FEEDBACK_FBK_RAW f1
        WHERE f1.FFR_CNC = ?
            AND f1.FFR_TYP = 'A'
            $dateCondition
        ORDER BY f1.DATM DESC";

    $stops = $db->query($stopsSql, $params);

    // Calculate efficiency
    $efficiency = 0;
    if ($summary && $summary['Total_Time_Sec'] > 0) {
        $efficiency = round(($summary['Production_Time_Sec'] / $summary['Total_Time_Sec']) * 100, 1);
    }

    // Build response
    $response = [
        'success' => true,
        'machine' => $machine,
        'period' => $period,
        'summary' => [
            'total_parts' => (int)$summary['Total_Parts'],
            'parts_quantity' => (int)$summary['Parts_Quantity'],
            'total_nests' => (int)$summary['Total_Nests'],
            'total_bars' => (int)$summary['Total_Bars'],
            'total_stops' => (int)$summary['Total_Stops'],
            'production_time_hours' => round($summary['Production_Time_Sec'] / 3600, 2),
            'total_time_hours' => round($summary['Total_Time_Sec'] / 3600, 2),
            'efficiency' => $efficiency,
            'time_breakdown' => [
                'production' => (int)$summary['Production_Time_Sec'],
                'alarm' => (int)$summary['Alarm_Time_Sec'],
                'idle' => (int)$summary['Idle_Time_Sec'],
                'setup' => (int)$summary['Setup_Time_Sec'],
                'material_wait' => (int)$summary['Material_Wait_Sec']
            ]
        ],
        'activities' => $activities,
        'nests' => $nests,
        'stops' => $stops
    ];

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error occurred',
        'message' => $e->getMessage()
    ]);
}
?>