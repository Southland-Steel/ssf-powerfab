<?php
// workweeks/index.php

// Set page title and other variables for header
$show_workweeks = false; // We're handling workweeks ourselves in this page
$extra_css = '<link rel="stylesheet" href="css/workweeks.css">';
$extra_js = '
<script src="js/workweeks-core.js"></script>
<script src="js/workweeks-stations.js"></script>
<script src="js/workweeks-display.js"></script>
<script src="js/workweeks-filters.js"></script>

';

// Calculate current workweek
require_once __DIR__ . '/../includes/functions/utility_functions.php';
$currentWorkweek = getCurrentWorkWeek();
$workweek = $_GET['workweek'] ?? $currentWorkweek;

// Include the header
include_once __DIR__ . '/../includes/header.php';
?>

    <!-- Big text indicator for current work week -->
    <div id="big-text"><?php echo $workweek; ?></div>

    <!-- Container for work weeks selector -->
    <div id="activeFabWorkpackages" class="container-fluid">
        <!-- Active fabrication jobs will be inserted here via JavaScript -->
    </div>

    <div id="projectData" class="container-fluid mt-3">
        <h2 class="mb-4" style="margin-bottom:0 !important; font-size: 1.5rem;">Workweek Details</h2>
        <img src="../assets/images/ssf-logo.png" alt="Southland Steel" class="toplogo" height="40px">

        <div class="row mb-4" style="margin-bottom:5px !important;">
            <div class="col-lg-9">
                <div id="projectSummary" class="card bg-ssf-primary">
                    <div class="card-header text-white">
                        Project Summary (for what's visible)
                    </div>
                    <div class="card-body" style="background-color: white;">
                        <div class="row">
                            <div class="col-md-4 summary-data">
                                <h6>Line Items</h6>
                                <p id="lineItemSummary"></p>
                            </div>
                            <div class="col-md-4 summary-data">
                                <h6>Hours</h6>
                                <p id="hoursSummary"></p>
                            </div>
                            <div class="col-md-4 summary-data">
                                <h6>Weight</h6>
                                <p id="weightSummary"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
                <table id="weekschedule">
                    <thead>
                    <tr>
                        <th>Workweek for:</th>
                        <th>&nbsp;</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    function getAdjustedWorkWeek($workweek, $offset) {
                        return $workweek + $offset;
                    }
                    ?>
                    <tr>
                        <td>CNC</td>
                        <td><?= getAdjustedWorkWeek($workweek, 6); ?></td>
                    </tr>
                    <tr>
                        <td>Cut</td>
                        <td><?= getAdjustedWorkWeek($workweek, 4); ?></td>
                    </tr>
                    <tr>
                        <td>Kit</td>
                        <td><?= getAdjustedWorkWeek($workweek, 3); ?></td>
                    </tr>
                    <tr>
                        <td>Fit</td>
                        <td><?= getAdjustedWorkWeek($workweek, 1); ?></td>
                    </tr>
                    <tr>
                        <td>Weld &amp; Final QC</td>
                        <td><?= getAdjustedWorkWeek($workweek, 0); ?></td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="filters-wrapper">
            <div class="filter-group" id="bayFilter">
                <!-- Bay filters will be inserted here -->
            </div>
            <div class="filter-group" id="wpFilter">
                <!-- Work package filters will be inserted here -->
            </div>
            <div class="filter-group" id="routeFilter">
                <!-- Route filters will be inserted here -->
            </div>
            <div class="filter-group" id="categoryFilter">
                <!-- Category filters will be inserted here -->
            </div>
            <div class="filter-group" id="sequenceFilter">
                <!-- Sequence filters will be inserted here -->
            </div>
        </div>

        <div class="table-container">
            <table id="projectTable" class="table table-bordered table-striped">
                <thead>
                <!-- Table header will be dynamically populated -->
                </thead>
                <tbody>
                <!-- Table body will be dynamically populated -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Piecemark Details Modal -->
    <div class="modal fade" id="piecemarkModal" tabindex="-1" aria-labelledby="piecemarkModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="piecemarkModalLabel">Piecemark Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-striped" id="piecemarkTable">
                        <thead>
                        <!-- Header data will dynamically be inserted here -->
                        </thead>
                        <tbody>
                        <!-- Body data will dynamically be inserted here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- JSON Data Modal -->
    <div class="modal fade" id="jsonModal" tabindex="-1" aria-labelledby="jsonModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="jsonModalLabel">Project Data Details</h5>
                    <div class="ms-3">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="expandAllJson()">Expand All</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="collapseAllJson()">Collapse All</button>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="jsonContent" style="max-height: 70vh; overflow-y: auto;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="copyJsonToClipboard()">Copy to Clipboard</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize the application when the DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            // Set initial workweek to what's in the URL or the current one
            const workweek = '<?php echo $workweek; ?>';
            initializeWorkweeks(workweek);
        });
    </script>

<?php
// Include the footer
include_once __DIR__ . '/../includes/footer.php';
?>