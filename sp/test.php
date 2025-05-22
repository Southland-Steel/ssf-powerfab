<?php
require_once 'Database.php';

// Test connection
$db = new Database();

if ($db->isConnected()) {
    echo "Connected successfully!\n";

    // Test query
    $result = $db->queryValue("SELECT COUNT(*) FROM FEEDBACK_FBK_RAW");
    echo "Total records: " . $result;
} else {
    echo "Connection failed: " . $db->getError();
}
?>