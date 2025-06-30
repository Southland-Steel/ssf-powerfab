<?php
// view_ssf_workweeks.php

$currentYear = substr(date('o'), -2);
$currentWeek = date('W'); // Gets the week number (01-53)
$currentWorkweek = intval($currentYear . str_pad($currentWeek, 2, '0', STR_PAD_LEFT));

$workweek = $_GET['workweek'] ?? $currentWorkweek;

require_once '../config_ssf_db.php';

// Query the database using Medoo to fetch distinct WorkPackageNumber
$resources = $db->query("
    SELECT DISTINCT Group2 as WorkWeeks FROM workpackages INNER JOIN productioncontroljobs as pcj ON pcj.productionControlID = workpackages.productionControlID WHERE Completed = 0 AND OnHold = 0 ORDER BY WorkWeeks ASC;
")->fetchAll(PDO::FETCH_ASSOC);

$weeks = array_filter(array_column($resources, 'WorkWeeks'), function($week) {
    return $week !== null && $week !== '';
});

sort($weeks);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Work Package Monitor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="css/styles.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
</head>
<body>
<div id="big-text">2442</div>

<div id="activeFabWorkpackages" class="container-fluid">
    <!-- Active fabrication jobs will be inserted here -->
</div>

<div id="projectData" class="container-fluid mt-3">
    <h2 class="mb-4" style="margin-bottom:0 !important; font-size: 1.5rem;">Workweek Details</h2>
    <img src="../images/ssf-horiz.png" alt="Southland Steel" class="toplogo" height="40px">
    <div class="row mb-4" style="margin-bottom:5px !important;">
        <div class="col-lg-9">
            <div id="projectSummary" class="card btn-ssf">
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
                <?php
                function getAdjustedWorkWeek($workweek, $offset) {
                    $adjustedWeek = $workweek + $offset;
                    return $adjustedWeek;
                }
                ?>
                <tbody>
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
                <tr style="position: relative">
                    <td>Weld &amp; Final QC</td>
                    <td><?= getAdjustedWorkWeek($workweek, 0); ?></td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="filters-wrapper">
        <div class="filter-group" id="bayFilter">
            <!-- Bay filters -->
        </div>
        <div class="filter-group" id="wpFilter">
            <!-- Work package filters -->
        </div>
        <div class="filter-group" id="routeFilter">
            <!-- Route filters -->
        </div>
        <div class="filter-group" id="categoryFilter">
            <!-- Category filters -->
        </div>
        <div class="filter-group" id="sequenceFilter">
            <!-- Sequence filters -->
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
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- JSON Details Modal -->
<div class="modal fade" id="jsonModal" tabindex="-1" aria-labelledby="jsonModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="jsonModalLabel">Project Data Details</h5>
                <div class="ms-3">
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="app.jsonViewer.expandAll()">Expand All</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="app.jsonViewer.collapseAll()">Collapse All</button>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="jsonContent" style="max-height: 70vh; overflow-y: auto;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="app.jsonViewer.copyToClipboard()">Copy to Clipboard</button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript Dependencies -->
<script>
    // Pass PHP data to JavaScript
    const PHP_DATA = {
        currentWeek: <?= $workweek ?>,
        weeks: <?= json_encode($weeks); ?>
    };
</script>

<!-- Load JavaScript Classes -->
<script src="js/constants.js"></script>
<script src="js/utils/formatter.js"></script>
<script src="js/utils/calculator.js"></script>
<script src="js/data/dataManager.js"></script>
<script src="js/data/filterManager.js"></script>
<script src="js/ui/tableRenderer.js"></script>
<script src="js/ui/filterRenderer.js"></script>
<script src="js/ui/summaryRenderer.js"></script>
<script src="js/ui/modalManager.js"></script>
<script src="js/ui/jsonViewer.js"></script>
<script src="js/ui/loadingOverlay.js"></script>
<script src="js/app.js"></script>

</body>
</html>