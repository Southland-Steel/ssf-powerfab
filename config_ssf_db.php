<?php
// db_config.php

// Database configuration
$db_config = [
    'host'     => '192.168.80.12',
    'dbname'   => 'fabrication',
    'username' => 'ssf.reporter',
    'password' => 'SSF.reporter251@*',
    'charset'  => 'utf8mb4'
];

//$db_config = [ // Grid Connection
//    'host'     => '192.168.0.10',
//    'dbname'   => 'fabrication',
//    'username' => 'grid.reporter',
//    'password' => 'l!9bI?q&4ogh|[7!',
//    'charset'  => 'utf8mb4'
//];

try {
    // Create PDO connection string
    $dsn = "mysql:host={$db_config['host']};dbname={$db_config['dbname']};charset={$db_config['charset']}";

    // Create PDO instance
    $db = new PDO($dsn, $db_config['username'], $db_config['password'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

} catch(PDOException $e) {
    // Log the error (make sure error log is properly configured)
    error_log("Database Connection Error: " . $e->getMessage());

    // Show generic error message to user
    die("Could not connect to the database. Please contact your administrator.");
}

// Optional: Set timezone if needed
date_default_timezone_set('America/Chicago');