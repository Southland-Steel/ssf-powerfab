<?php
// includes/db_connection.php

// Include utility functions first, as they may be needed for database operations
require_once __DIR__ . '/functions/utility_functions.php';

// Include database configuration file
require_once __DIR__ . '/config/db_config.php';

try {
    // Create PDO connection string
    $dsn = "mysql:host={$db_config['host']};dbname={$db_config['dbname']};charset={$db_config['charset']}";

    // Create PDO instance
    $db = new PDO($dsn, $db_config['username'], $db_config['password'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => true,
    ]);

} catch(PDOException $e) {
    // Log the error (make sure error log is properly configured)
    error_log("Database Connection Error: " . $e->getMessage());

    // Show generic error message to user
    die("Could not connect to the database. Please contact your administrator.");
}

// Set timezone
date_default_timezone_set('America/Chicago');