<?php
/**
 * AJAX Endpoint: Get Bar Details
 * Returns detailed bar information for a specific nest
 */

require_once '../includes/db_config.php';

// Set JSON header
header('Content-Type: application/json');

// Get and validate parameters
$machine = $_GET['machine'] ?? '';
$nesId = $_GET['nes_id'] ?? '';
$barId = $_GET['bar_id'] ?? '';

if (empty($nesId)) {
    http_response_code(400);
    echo json_encode(['error' => 'NES_ID parameter is required']);
    exit;
}

try {
    // Get nest information using NES_ID
    $nestInfoSql = "
        SELECT 
            n.NES_ID,
            n.NES_NAME AS Nest_Name,
            n.NES_DES AS Nest_Description,
            n.NES_QTE AS Total_Quantity,
            n.NES_QTEP AS Quantity_Produced,
            n.NES_MKE AS Machine_Key
        FROM NESTING n
        WHERE n.NES_ID = ?";

    $nestInfo = $db->queryRow($nestInfoSql, [$nesId]);

    if (!$nestInfo) {
        echo json_encode([
            'success' => false,
            'error' => 'Nest not found in database',
            'nes_id' => $nesId
        ]);
        exit;
    }

    // Get bar details from NESTBAR table
    $barsSql = "
        SELECT 
            nb.BAR_ID,
            nb.BAR_IDT AS Bar_Number,
            nb.BAR_PRF AS Profile,
            nb.BAR_THK AS Thickness,
            nb.BAR_LEN AS Length,
            nb.BAR_PDS AS Weight,
            nb.BAR_CHU AS Waste,
            nb.BAR_XID AS XML_Data,
            nb.BAR_COM AS Comment,
            nb.BAR_QTE AS Quantity,
            nb.BAR_MAT AS Material,
            nb.BAR_GRD AS Grade
        FROM NESTBAR nb
        WHERE nb.NES_ID = ?";

    // If we have a specific BAR_ID, filter for it
    if (!empty($barId)) {
        $barsSql .= " AND nb.BAR_ID = ?";
        $bars = $db->query($barsSql, [$nesId, $barId]);
    } else {
        $barsSql .= " ORDER BY nb.BAR_IDT";
        $bars = $db->query($barsSql, [$nesId]);
    }

    // Process bars to extract CutListItemID from XML
    $processedBars = [];
    $totalBarWeight = 0;

    if ($bars) {
        foreach ($bars as $bar) {
            $cutListItemId = null;

            // Extract CutListItemID from BAR_XID XML field
            if (!empty($bar['XML_Data'])) {
                // Simple XML parsing for <CutListItemID>
                if (preg_match('/<CutListItemID>(\d+)<\/CutListItemID>/', $bar['XML_Data'], $matches)) {
                    $cutListItemId = $matches[1];
                }
            }

            // Calculate total weight
            if (!empty($bar['Weight'])) {
                $totalBarWeight += floatval($bar['Weight']);
            }

            $processedBars[] = array_merge($bar, [
                'Cut_List_Item_ID' => $cutListItemId
            ]);
        }
    }

    // Build response
    $response = [
        'success' => true,
        'nest_id' => $nestInfo['NES_ID'],
        'nest_name' => $nestInfo['Nest_Name'],
        'nest_description' => $nestInfo['Nest_Description'],
        'nest_quantity' => (int)$nestInfo['Total_Quantity'],
        'nest_quantity_produced' => (int)$nestInfo['Quantity_Produced'],
        'bars' => $processedBars,
        'total_bar_weight' => $totalBarWeight
    ];

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred',
        'message' => $e->getMessage()
    ]);
}
?>