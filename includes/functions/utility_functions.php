<?php
// includes/functions/utility_functions.php

/**
 * Base path configuration - change this if your application is in a subdirectory
 * For example, if your app is at example.com/myapp/, set this to '/myapp/'
 * If your app is at the root (example.com), keep it as '/'
 */
define('BASE_PATH', '/');

/**
 * Get the base URL of the application (root directory)
 *
 * @param bool $include_protocol Whether to include http/https in the URL
 * @return string Base URL of the application
 */
function getBaseUrl($include_protocol = true) {
    // Get the server protocol (http or https)
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';

    // Get the server host (domain)
    $host = $_SERVER['HTTP_HOST'];

    // Return the constructed URL with the configured base path
    return $include_protocol ? "{$protocol}://{$host}" . BASE_PATH : "{$host}" . BASE_PATH;
}

/**
 * Get a URL relative to the base URL
 *
 * @param string $path The path to append to the base URL
 * @return string The complete URL
 */
function getUrl($path = '') {
    return getBaseUrl() . ltrim($path, '/');
}

/**
 * Get an asset URL (CSS, JS, images)
 *
 * @param string $path The path to the asset relative to the assets directory
 * @return string The complete asset URL
 */
function getAssetUrl($path = '') {
    return getBaseUrl() . 'assets/' . ltrim($path, '/');
}

/**
 * Converts inches to a feet and inches format with fractions
 *
 * @param float $inches Total inches to convert
 * @return string Formatted string in feet and inches (e.g. "5'-6 1/2\"")
 */
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

/**
 * Format work week from YYWW format to a more readable format
 *
 * @param string $workweek Work week in YYWW format
 * @return string Formatted work week (e.g. "WW 22 - 2024")
 */
function formatWorkWeek($workweek) {
    if (strlen($workweek) == 4) {
        $year = '20' . substr($workweek, 0, 2);
        $week = substr($workweek, 2);
        return "WW {$week} - {$year}";
    }
    return $workweek;
}

/**
 * Get current work week in YYWW format
 *
 * @return string Current work week in YYWW format
 */
function getCurrentWorkWeek() {
    $year = substr(date('o'), -2);  // Get last 2 digits of year
    $week = date('W');              // Get week number (01-53)
    return $year . str_pad($week, 2, '0', STR_PAD_LEFT);
}