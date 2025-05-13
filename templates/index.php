<?php
// index.php

// Set page-specific variables
$page_title = 'Home';
$show_workweeks = false;

// Include header
include_once 'includes/header.php';
?>

    <!-- Main page content -->
    <div class="row">
        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-header bg-ssf-primary text-white">
                    <h5 class="card-title mb-0">SSF Production Resources</h5>
                </div>
                <div class="card-body">
                    <a href="workweeks.php" class="btn btn-ssf-primary w-100 mb-2">View SSF Workweeks</a>
                    <a href="/postfab" class="btn btn-secondary w-100 mb-2">Post Fabrication Status</a>
                    <a href="/cutlists" class="btn btn-ssf-accent w-100 mb-2">Cut Lists</a>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-header bg-ssf-primary text-white">
                    <h5 class="card-title mb-0">Production <-> PM</h5>
                </div>
                <div class="card-body">
                    <a href="/timeline" class="btn btn-ssf-primary w-100 mb-2">Project Timeline</a>
                    <a href="/resources" class="btn btn-ssf-primary w-100 mb-2">Project Resources</a>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-header bg-ssf-primary text-white">
                    <h5 class="card-title mb-0">Inventory</h5>
                </div>
                <div class="card-body">
                    <a href="/inventory2" class="btn btn-ssf-accent w-100 mb-2">Inventory</a>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-header bg-ssf-primary text-white">
                    <h5 class="card-title mb-0">Transition</h5>
                </div>
                <div class="card-body">
                    <a href="view_ssf_earned_hours.php" class="btn btn-ssf-accent w-100 mb-2">Earned Hours</a>
                    <a href="view_ssf_hit_stations.php" class="btn btn-ssf-primary w-100 mb-2">View SSF Production Monitor</a>
                    <a href="view_ssf_stations.php" class="btn btn-ssf-primary w-100 mb-2">View SSF Stations</a>
                    <a href="view_ssf_route_removal.php" class="btn btn-ssf-primary w-100 mb-2">Route Removal Tool</a>
                </div>
            </div>
        </div>
    </div>

<?php
// Include footer
include_once 'includes/footer.php';
?>