<?php
/**
 * AJAX Endpoint: Get Machine Status
 * Returns current status and recent activity for a specific machine
 */

require_once '../includes/db_config.php';

// Set JSON header
header('Content-Type: application/json');

// Get and validate parameters
$machine = $_GET['machine'] ?? '';

if (empty($machine)) {
    http_response_code(400);
    echo json_encode(['error' => 'Machine parameter is required']);
    exit;
}

try {
    // Get current/latest activity
    $currentActivitySql = "
        SELECT TOP 1
            f.FFR_CNC AS Machine,
            f.FFR_BON AS Batch_Number,
            f.FFR_CBR AS Bar_ID,
            f.FFR_NEST AS Nest_Program,
            f.FFR_NT AS Nest_Description,
            f.FFR_TYP AS Type,
            CASE
                WHEN f.FFR_TYP = 'N' THEN 'Loading Bar'
                WHEN f.FFR_TYP = 'P' THEN 'Cutting Parts'
                WHEN f.FFR_TYP = 'A' THEN 'Machine Stopped'
                ELSE 'Unknown'
            END AS Status_Text,
            f.FFR_COM AS Job_Number,
            f.FFR_DWG AS Drawing,
            f.FFR_RP AS Part_Mark,
            f.FFR_RS AS Sub_Mark,
            f.FFR_PRF AS Profile,
            f.FFR_MAT AS Material,
            f.FFR_LEN AS Length,
            f.FFR_WDT AS Width,
            f.FFR_THK AS Thickness,
            f.FFR_NBP AS Parts_Complete,
            f.FFR_NP AS Total_Parts,
            f.FFR_OPE AS Operator,
            f.FFR_DATD AS Start_Date,
            f.FFR_TIMD AS Start_Time,
            f.FFR_DAT AS End_Date,
            f.FFR_TIM AS End_Time,
            f.FFR_RMT1 AS Total_Time,
            f.FFR_RMT7 AS Production_Time,
            f.DATM AS Last_Update,
            CASE 
                WHEN f.FFR_DATD IS NOT NULL AND f.FFR_TIMD IS NOT NULL 
                    AND ISDATE(f.FFR_DATD + ' ' + f.FFR_TIMD) = 1
                THEN DATEDIFF(minute, 
                    TRY_CONVERT(datetime, f.FFR_DATD + ' ' + f.FFR_TIMD), 
                    GETDATE()
                )
                ELSE NULL
            END AS Minutes_Running
        FROM FEEDBACK_FBK_RAW f
        WHERE f.FFR_CNC = ?
        ORDER BY f.DATM DESC";

    $currentActivity = $db->queryRow($currentActivitySql, [$machine]);

    if (!$currentActivity) {
        echo json_encode([
            'machine' => $machine,
            'status' => 'No Data',
            'message' => 'No activity records found for this machine'
        ]);
        exit;
    }

    // Get today's statistics
    $todayStatsSql = "
        SELECT 
            COUNT(DISTINCT CASE WHEN FFR_TYP = 'P' THEN FFR_ID END) AS Parts_Cut_Today,
            COUNT(DISTINCT CASE WHEN FFR_TYP = 'N' THEN FFR_CBR END) AS Bars_Loaded_Today,
            COUNT(CASE WHEN FFR_TYP = 'A' THEN 1 END) AS Stops_Today,
            SUM(CASE WHEN FFR_TYP = 'P' THEN ISNULL(FFR_RMT7, 0) ELSE 0 END) AS Production_Time_Today,
            SUM(ISNULL(FFR_RMT1, 0)) AS Total_Time_Today
        FROM FEEDBACK_FBK_RAW
        WHERE FFR_CNC = ?
            AND CAST(DATM AS DATE) = CAST(GETDATE() AS DATE)";

    $todayStats = $db->queryRow($todayStatsSql, [$machine]);

    // Get recent activities (last 5)
    $recentActivitiesSql = "
        SELECT TOP 5
            FFR_TYP AS Type,
            CASE
                WHEN FFR_TYP = 'N' THEN 'Bar Loaded'
                WHEN FFR_TYP = 'P' THEN 'Part Cut'
                WHEN FFR_TYP = 'A' THEN 'Stopped'
                ELSE 'Unknown'
            END AS Activity,
            FFR_NEST AS Nest_Program,
            FFR_RP AS Part_Mark,
            FFR_PRF AS Profile,
            FFR_CBR AS Bar_ID,
            FFR_DATD AS Date,
            FFR_TIMD AS Time,
            DATM AS Timestamp
        FROM FEEDBACK_FBK_RAW
        WHERE FFR_CNC = ?
        ORDER BY DATM DESC";

    $recentActivities = $db->query($recentActivitiesSql, [$machine]);

    // Calculate efficiency
    $efficiency = 0;
    if ($todayStats && $todayStats['Total_Time_Today'] > 0) {
        $efficiency = round(($todayStats['Production_Time_Today'] / $todayStats['Total_Time_Today']) * 100, 1);
    }

    // Determine machine status
    $machineStatus = 'stopped';
    $minutesSinceUpdate = 999; // Default to large number

    try {
        if (!empty($currentActivity['Last_Update'])) {
            $lastUpdate = new DateTime($currentActivity['Last_Update']);
            $now = new DateTime();
            $diff = $now->diff($lastUpdate);
            $minutesSinceUpdate = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
        }
    } catch (Exception $e) {
        // If date parsing fails, keep default
    }

    if ($currentActivity['Type'] == 'P' && $minutesSinceUpdate < 5) {
        $machineStatus = 'active';
    } elseif ($currentActivity['Type'] == 'N' && $minutesSinceUpdate < 10) {
        $machineStatus = 'active';
    } elseif ($minutesSinceUpdate < 30) {
        $machineStatus = 'idle';
    }

    // Build response
    $response = [
        'success' => true,
        'machine' => $machine,
        'machine_status' => $machineStatus,
        'current_activity' => [
            'type' => $currentActivity['Type'],
            'status_text' => $currentActivity['Status_Text'],
            'nest' => $currentActivity['Nest_Program'],
            'nest_description' => $currentActivity['Nest_Description'],
            'job' => $currentActivity['Job_Number'],
            'drawing' => $currentActivity['Drawing'],
            'part_mark' => trim($currentActivity['Part_Mark'] . ($currentActivity['Sub_Mark'] ? '/' . $currentActivity['Sub_Mark'] : '')),
            'profile' => $currentActivity['Profile'],
            'material' => $currentActivity['Material'],
            'dimensions' => $currentActivity['Length'] . ' x ' . $currentActivity['Width'] . ' x ' . $currentActivity['Thickness'] . ' mm',
            'parts_complete' => (int)$currentActivity['Parts_Complete'],
            'total_parts' => (int)$currentActivity['Total_Parts'],
            'progress' => $currentActivity['Total_Parts'] > 0 ? round(($currentActivity['Parts_Complete'] / $currentActivity['Total_Parts']) * 100, 1) : 0,
            'operator' => $currentActivity['Operator'],
            'start_time' => $currentActivity['Start_Date'] . ' ' . $currentActivity['Start_Time'],
            'minutes_running' => $currentActivity['Minutes_Running'] !== null ? (int)$currentActivity['Minutes_Running'] : null,
            'last_update' => $currentActivity['Last_Update']
        ],
        'today_stats' => [
            'parts_cut' => (int)$todayStats['Parts_Cut_Today'],
            'bars_loaded' => (int)$todayStats['Bars_Loaded_Today'],
            'stops' => (int)$todayStats['Stops_Today'],
            'efficiency' => $efficiency
        ],
        'recent_activities' => $recentActivities
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