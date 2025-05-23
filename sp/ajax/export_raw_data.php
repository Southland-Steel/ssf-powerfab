<?php
/**
 * Export Raw Data to CSV
 * Exports FEEDBACK_FBK_RAW data to CSV format
 */

require_once '../includes/db_config.php';

// Get machine parameter
$machine = $_GET['machine'] ?? '';

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="feedback_raw_' .
    ($machine ?: 'all') . '_' . date('Y-m-d') . '.csv"');
header('Pragma: no-cache');
header('Expires: 0');

// Open output stream
$output = fopen('php://output', 'w');

try {
    // Get column names
    $columnsSql = "
    SELECT COLUMN_NAME
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME = 'FEEDBACK_FBK_RAW'
    ORDER BY ORDINAL_POSITION";

    $columns = $db->query($columnsSql);
    $columnNames = array_column($columns, 'COLUMN_NAME');

    // Write CSV header
    fputcsv($output, $columnNames);

    // Build data query
    $sql = "SELECT * FROM FEEDBACK_FBK_RAW";
    $params = [];

    if (!empty($machine)) {
        $sql .= " WHERE FFR_CNC = ?";
        $params[] = $machine;
    }

    $sql .= " ORDER BY DATM DESC";

    // Fetch and write data in chunks to handle large datasets
    $offset = 0;
    $chunkSize = 5000;

    do {
        $chunkSql = $sql . " OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";
        $chunkParams = array_merge($params, [$offset, $chunkSize]);

        $data = $db->query($chunkSql, $chunkParams);

        if (empty($data)) {
            break;
        }

        // Write each row
        foreach ($data as $row) {
            $csvRow = [];
            foreach ($columnNames as $col) {
                $value = $row[$col] ?? '';

                // Handle DateTime objects
                if ($value instanceof DateTime) {
                    $value = $value->format('Y-m-d H:i:s');
                }

                $csvRow[] = $value;
            }
            fputcsv($output, $csvRow);
        }

        $offset += $chunkSize;

        // Flush output buffer periodically
        if ($offset % 10000 == 0) {
            ob_flush();
            flush();
        }

    } while (count($data) == $chunkSize);

} catch (Exception $e) {
    // Write error to output
    fputcsv($output, ['Error: ' . $e->getMessage()]);
}

// Close output stream
fclose($output);
exit;
?>