<?php
/**
 * Database Configuration and Connection Setup
 * This file is included by all pages that need database access
 */

// Include the Database class
require_once __DIR__ . '/../services/Database.php';

// Create database instance
$db = new Database();

// Check connection
if (!$db->isConnected()) {
    // In production, you might want to log this error and show a friendly message
    $errorMessage = "Database connection failed: " . $db->getError();

    // If it's an AJAX request, return JSON error
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['error' => $errorMessage]);
        exit;
    }

    // Otherwise, show error page
    die("
        <div style='padding: 20px; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 4px; margin: 20px;'>
            <h3>Database Connection Error</h3>
            <p>{$errorMessage}</p>
            <p>Please check your database configuration and try again.</p>
        </div>
    ");
}

// Optional: Set timezone if needed
// date_default_timezone_set('America/Chicago');
?>