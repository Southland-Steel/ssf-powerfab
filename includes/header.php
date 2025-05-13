<?php
// includes/header.php

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once __DIR__ . '/db_connection.php';

// Include utility functions
require_once __DIR__ . '/functions/utility_functions.php';

// Define site variables
$site_title = 'SSF Production Management';
$version = '1.0.0';

// Get current page
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' | ' . $site_title : $site_title; ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Main CSS -->
    <link rel="stylesheet" href="<?php echo getAssetUrl('css/styles.css'); ?>">

    <?php if (isset($extra_css)): ?>
    <?php echo $extra_css; ?>
    <?php endif; ?>
</head>
<body class="bg-light">
    <!-- Loading overlay -->
    <div id="loading-overlay" style="display: none;">
        <div class="loading-spinner">
            <div class="spinner-border text-primary" role="status"></div>
            <span class="ms-2">Loading...</span>
        </div>
    </div>

    <!-- Navbar with Dropdowns -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-ssf-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo getUrl(); ?>">
                <img src="<?php echo getAssetUrl('images/ssf-logo.png'); ?>" alt="SSF Logo" height="30" class="d-inline-block align-text-top me-2">
                SSF Production
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <!-- Home Link -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>" href="<?php echo getUrl(); ?>">
                            <i class="bi bi-house-door"></i> Home
                        </a>
                    </li>

                    <!-- Production Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo in_array($current_page, ['workweeks.php', 'view_ssf_stations.php', 'view_ssf_hit_stations.php']) ? 'active' : ''; ?>"
                           href="#" id="productionDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-gear"></i> Production
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="productionDropdown">
                            <li><a class="dropdown-item <?php echo $current_page == 'workweeks.php' ? 'active' : ''; ?>"
                                   href="<?php echo getUrl('workweeks.php'); ?>">Work Weeks</a></li>
                            <li><a class="dropdown-item <?php echo $current_page == 'view_ssf_stations.php' ? 'active' : ''; ?>"
                                   href="<?php echo getUrl('view_ssf_stations.php'); ?>">Stations</a></li>
                            <li><a class="dropdown-item <?php echo $current_page == 'view_ssf_hit_stations.php' ? 'active' : ''; ?>"
                                   href="<?php echo getUrl('view_ssf_hit_stations.php'); ?>">Production Monitor</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item <?php echo $current_page == 'view_ssf_route_removal.php' ? 'active' : ''; ?>"
                                   href="<?php echo getUrl('view_ssf_route_removal.php'); ?>">Route Removal Tool</a></li>
                        </ul>
                    </li>

                    <!-- Reporting Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo in_array($current_page, ['view_ssf_earned_hours.php', 'reporting.php']) ? 'active' : ''; ?>"
                           href="#" id="reportingDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-bar-chart"></i> Reporting
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="reportingDropdown">
                            <li><a class="dropdown-item <?php echo $current_page == 'view_ssf_earned_hours.php' ? 'active' : ''; ?>"
                                   href="<?php echo getUrl('view_ssf_earned_hours.php'); ?>">Earned Hours</a></li>
                            <li><a class="dropdown-item" href="<?php echo getUrl('timeline.php'); ?>">Project Timeline</a></li>
                            <li><a class="dropdown-item" href="<?php echo getUrl('resources.php'); ?>">Project Resources</a></li>
                        </ul>
                    </li>

                    <!-- Inventory Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="inventoryDropdown"
                           role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-box-seam"></i> Inventory
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="inventoryDropdown">
                            <li><a class="dropdown-item" href="<?php echo getUrl('inventory2.php'); ?>">Inventory</a></li>
                            <li><a class="dropdown-item" href="<?php echo getUrl('cutlists.php'); ?>">Cut Lists</a></li>
                        </ul>
                    </li>

                    <!-- Post Fabrication -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'postfab.php' ? 'active' : ''; ?>"
                           href="<?php echo getUrl('postfab.php'); ?>">
                            <i class="bi bi-wrench"></i> Post Fabrication
                        </a>
                    </li>
                </ul>

                <!-- Search form removed: only added when specifically needed -->
            </div>
        </div>
    </nav>

    <!-- Main Content Container -->
    <div class="container-fluid py-4">
        <?php if (isset($page_title)): ?>
        <h1 class="mb-4"><?php echo $page_title; ?></h1>
        <?php endif; ?>

        <?php if (isset($show_workweeks) && $show_workweeks): ?>
        <!-- Work Weeks Bar - Only shown on pages that need it -->
        <div id="workweeks" class="mb-4">
            <div id="activeWorkWeeks">
                <!-- Work week buttons will be inserted here via JavaScript -->
                <div class="d-flex align-items-center">
                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                    <span>Loading work weeks...</span>
                </div>
            </div>
        </div>
        <?php endif; ?>