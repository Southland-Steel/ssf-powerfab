<?php
// db_config.php
function inchesToFeetAndInches($inches) {
    $inches = floatval($inches);
    $feet = floor($inches / 12);
    $remainingInches = fmod($inches, 12);
    $wholeInches = floor($remainingInches);
    $fractionNumerator = round(($remainingInches - $wholeInches) * 16);

    $fractions = [
        16 => '',
        15 => '15/16',
        14 => '7/8',
        13 => '13/16',
        12 => '3/4',
        11 => '11/16',
        10 => '5/8',
        9 => '9/16',
        8 => '1/2',
        7 => '7/16',
        6 => '3/8',
        5 => '5/16',
        4 => '1/4',
        3 => '3/16',
        2 => '1/8',
        1 => '1/16'
    ];

    if ($fractionNumerator == 16) {
        $wholeInches++;
        $fractionStr = '';
    } else {
        $fractionStr = $fractionNumerator > 0 ? ' ' . $fractions[$fractionNumerator] : '';
    }

    return ($feet > 0 ? "$feet'-" : '') . $wholeInches . $fractionStr . '"';
}


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