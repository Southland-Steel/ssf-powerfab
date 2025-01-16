<?php
// view_ssf_workweeks.php

$currentYear = substr(date('o'), -2);
$currentWeek = date('W'); // Gets the week number (01-53)
$currentWorkweek = intval($currentYear . str_pad($currentWeek, 2, '0', STR_PAD_LEFT));

$workweek = $_GET['workweek'] ?? $currentWorkweek;

require_once 'config_ssf_db.php';

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
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 5px;
            line-height: 1.6;
        }
        table {
            width: 100%;
            font-size: 12px;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .dropdown-menu {
            max-height: 400px;
            overflow-y: auto;
        }
        .status-complete {
            background-color: #90EE90 !important;
            color: #155724;
        }
        .status-notstarted {
            background-color: #fff3cd !important;
            /* Changed to blue as per request */
            color: #004085;  /* Adjusted for better contrast with blue */
        }
        .status-na{
            background-color: transparent !important;  /* Changed to blue as per request */
            color: #004085;  /* Adjusted for better contrast with blue */
        }
        .status-partial {
            background-color: #cce5ff !important;
            color: #856404;
        }

        #jobSummary, #dataSummary {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }
        .wpnotreleased {
            color: #ffd700 !important;  /* Yellow text */
            font-style: italic !important;
        }

        .wponhold {
            background-color: #dc3545 !important;  /* Bootstrap danger red */
            border-color: #dc3545 !important;
            color: white !important;
        }

        /* When button is hovered */
        .wponhold:hover {
            background-color: #bb2d3b !important;  /* Slightly darker red on hover */
            border-color: #bb2d3b !important;
        }

        #bayFilter {
            margin-bottom: 0px !important;
            margin-top: 5px !important;
        }
        #bayFilter button {
            margin-bottom: 0px !important;
        }
        #wpFilter {
            margin-bottom: 0px !important;
            margin-top: 5px !important;
        }
        #wpFilter button {
            margin-bottom: 0px !important;
        }
        #routeFilter {
            margin-bottom: 0px !important;
            margin-top: 5px !important;
        }
        #routeFilter button {
            margin-bottom: 0px !important;
        }

        .card-body .form-control {
            margin-bottom: 10px;
        }
        .station-summary {
            font-weight: bold;
            font-size: 12px;
            background-color: #f8f9fa;
        }
        .week-btn {
            padding: 2px 20px;
            margin: 5px;
            background-color: #99332B;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            font-size: 14px;
        }

        /* Hover state - slightly darker */
        .week-btn:hover {
            background-color: #7A2822;
        }

        /* Active state - even darker */
        .week-btn:active {
            background-color: #5C1E1A;
            transform: scale(0.95);
        }

        /* Selected state - inverted colors */
        .week-btn.active {
            background-color: white;
            border: 1px solid #99332B;
            color: #99332B;
            font-weight: bold;
        }
        .btn-ssf{
            background-color: #99332b;
        }

        .btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
            pointer-events: none;
        }
        .hold-row{
            font-family: "Courier New", Courier, monospace;
        }
        .hold-row td:nth-child(1){
            border-left: 2px solid #820041;
            background-color: #fdccd3;
        }
        .uncompleted-piecemark {
            background-color: #ffbbbb; /* Light green background */
        }
        #weekschedule td:nth-child(2){
            text-align: center;
        }
        .completed-row {
            background-color: #90EE90; /* Light green color */
        }
        #big-text {
            position: fixed;
            top: 10px;
            right: 10px;
            font-size: 48px;
            color: rgba(0, 0, 0, 0.3); /* Black with 50% opacity */
            z-index: 1000; /* Ensures it stays on top of other elements */
        }
        #projectTable thead th {
            position: sticky;
            top: 0;
            background-color: #f2f2f2;
            z-index: 10;
        }

        /* Optional: Add a box-shadow to create a separation effect */
        #projectTable thead th::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 100%;
            border-bottom: 1px solid #ddd;
        }
        .col-complete{
            background-color: #006400 !important; /* dark green */
            color: #ffffff !important; /* white text */
        }
        #projectData{
            position:relative;
            display: block;
        }
        .col-empty {
            background-color: #333333 !important;
            color: #ffffff !important;
            text-align: center;
        }
        .toplogo{
            position: absolute;
            left: 500px;
            top:-10px;
        }
        #bayFilter{
            margin-bottom: 0px !important;
            margin-top: 3px !important;
        }
        #bayFilter button{
            margin-bottom: 0px !important;
        }
        #categoryFilter {
            margin-bottom: 0px !important;
            margin-top: 3px !important;
        }
        #categoryFilter button {
            margin-bottom: 0px !important;
        }

        .export-buttons {
            margin: 10px 0;
            display: none; /* Hide by default */
            gap: 10px;
            position: absolute;
            top: 32px;
            right: 0px;
        }

        /* Common styles for all filter containers */
        #bayFilter, #wpFilter, #routeFilter, #categoryFilter {
            flex-wrap: wrap;
            gap: 3px;  /* Replace margins with gap for better spacing */
            padding: 2px 5px;
            margin: 1px 0;
            align-items: center;
        }

        /* Style for the filter buttons */
        #bayFilter button, #wpFilter button, #routeFilter button, #categoryFilter button {
            padding: 2px 8px;  /* Smaller padding */
            font-size: 0.85rem;  /* Slightly smaller font */
            margin: 0;  /* Remove margins, using gap instead */
            height: 26px;  /* Fixed height for consistency */
            line-height: 1;  /* Adjust line height */
            white-space: nowrap;
        }

        /* Container for the table and filters */
        .table-container {
            margin-top: 3px;  /* Tighter spacing to table */
        }

        /* If you want to make filters horizontal but distinct */
        .filters-wrapper {
            flex-direction: column;
            gap:3px;
            align-items: center;
            margin-bottom: 3px;
        }

        /* Optional: add subtle separators between filter groups */
        .filter-group {
            padding-right: 10px;
        }

        .filter-group:last-child {
            border-right: none;
        }

        /* Media query for desktop screens */
        @media (min-width: 961px) {
            .export-buttons {
                display: flex; /* Show on desktops */
            }
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
</head>
<body>
<div id="big-text">2442</div>

<div id="activeFabWorkpackages" class="container-fluid">
    <!-- Active fabrication jobs will be inserted here -->
</div>

<div id="projectData" class="container-fluid mt-4">
    <h2 class="mb-4" style="margin-bottom:0 !important;">Workweek Details</h2>
    <img src="images/ssf-horiz.png" alt="Southland Steel" class="toplogo" height="50px">
    <div class="row mb-4" style="margin-bottom:5px !important;">
        <div class="col-lg-9">
            <div id="projectSummary" class="card btn-ssf">
                <div class="card-header text-white">
                    Project Summary (for what's visible)
                </div>
                <div class="card-body" style="background-color: white;">
                    <div class="row">
                        <div class="col-md-4">
                            <h5 class="card-title" id="jobTitle">Job: </h5>
                            <p class="card-text" id="jobDescription"></p>
                        </div>
                        <div class="col-md-4">
                            <h6>Hours</h6>
                            <p id="hoursSummary"></p>
                        </div>
                        <div class="col-md-4">
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
                    <td><?= getAdjustedWorkWeek($workweek, 5); ?></td>
                </tr>
                <tr>
                    <td>Cut, Kit</td>
                    <td><?= getAdjustedWorkWeek($workweek, 2); ?></td>
                </tr>
                <tr style="position: relative">
                    <td>Fit &amp; Weld &amp; Final QC</td>
                    <td><?= getAdjustedWorkWeek($workweek, 0); ?>
                        <div class="export-buttons" style="display: none;">
                            <button class="btn btn-success" onclick="exportToCSV()">Export to CSV</button>
                            <button class="btn btn-info" onclick="exportToJSON()">Export to JSON</button>
                        </div>
                    </td>
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
        <div class="filter-group" id="categoryFilter">
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

<script>
    const orderedStations = ['NESTED','CUT','PROFIT','ZEMAN','FIT','WELD','FINAL QC'];
    var currentRouteFilter = 'all'; // Global variable for the selected route filter
    var currentWPFilter = 'all'; // Global variable for the selected work package filter
    var currentBayFilter = 'all';
    var currentCategoryFilter = 'all';
    var currentSequenceFilter = 'all';
    var projectData = []; // Global variable to hold the loaded data

    $(document).ready(function() {
        const currentWeek = <?= $workweek ?>;
        loadProjectData(currentWeek);

        let weeks = <?= json_encode($weeks); ?>;
        let weeklist = [];

        weeks.forEach(week => {
            weeklist.push(`
            <button class="week-btn ${week == currentWeek ? 'active' : ''}" onclick="loadProjectData('${week}')">
                ${week}
            </button>`);
        });
        $(document).on('click', '.week-btn', function() {
            $('.week-btn').removeClass('active');
            $(this).addClass('active');
        });

        // Insert the buttons into the container
        $('#activeFabWorkpackages').html(`<strong>Work Weeks:</strong> ${weeklist.join(' ')}`);

        var piecemarkModal = new bootstrap.Modal(document.getElementById('piecemarkModal'), {
            keyboard: false
        });

        var header = document.querySelector("#projectTable thead");
        var sticky = header.offsetTop;

        window.onscroll = function() {
            if (window.pageYOffset > sticky) {
                header.classList.add("sticky");
            } else {
                header.classList.remove("sticky");
            }
        };

    });

    function filterData() {
        let filteredData = projectData.filter(item => {
            let matchesRoute = currentRouteFilter === 'all' ||
                (currentRouteFilter === 'undefined' ? !item.RouteName : item.RouteName === currentRouteFilter);
            let matchesWP = currentWPFilter === 'all' ||
                (currentWPFilter === 'undefined' ? !item.WorkPackageNumber : item.WorkPackageNumber === currentWPFilter);
            let matchesBay = currentBayFilter === 'all' ||
                (currentBayFilter === 'undefined' ? !item.Bay : item.Bay === currentBayFilter);
            let matchesCategory = currentCategoryFilter === 'all' ||
                (currentCategoryFilter === 'undefined' ? !item.Category : item.Category === currentCategoryFilter);
            let matchesSequence = currentSequenceFilter === 'all' ||
                (currentSequenceFilter === 'undefined' ? !item.SequenceDescription : item.SequenceDescription === currentSequenceFilter);

            return matchesRoute && matchesWP && matchesBay && matchesCategory && matchesSequence;
        });

        populateTable(filteredData);
    }

    function loadProjectData(workweek) {
        $.when(
            $.ajax({
                url: 'ajax_get_ssf_workweeks2.php',
                method: 'GET',
                dataType: 'json',
                data: {workweek: workweek}
            }),
            $.ajax({
                url: 'ajax_get_ssf_workweek_piecemarks.php',
                method: 'GET',
                dataType: 'json',
                data: { workweek: workweek }
            })
        ).done(function(workweekResponse, piecemarkResponse) {
            if (workweekResponse[0].error) {
                alert(workweekResponse[0].error);
                return;
            }

            const workweekData = Array.isArray(workweekResponse[0].items) ? workweekResponse[0].items : [workweekResponse[0].items];
            const piecemarkData = piecemarkResponse[0].items;

            // Merge the data using the function we created earlier
            projectData = mergeData(workweekData, piecemarkData);

            createWPFilter();
            createRouteFilter();
            createBayFilter();
            createCategoryFilter();
            updateFilterButtons();

            createTableHeader();

            currentRouteFilter = 'all';
            currentWPFilter = 'all';
            currentBayFilter = 'all';
            currentCategoryFilter = 'all';
            filterData();

            $('#jobTitle').text(`Workweek: ${workweek}`);
            $('#big-text').text(`${workweek}`);
        }).fail(function(xhr, status, error) {
            console.error("Error fetching data:", error);
            alert("Error loading project data. Please try again.");
        });
    }

    function mergeData(workweekData, piecemarkData) {
        // Create a map for faster lookups
        const piecemarkMap = new Map();

        // Group piecemark data by ProductionControlItemSequenceID
        piecemarkData.forEach(piece => {
            if (!piecemarkMap.has(piece.ProductionControlItemSequenceID)) {
                piecemarkMap.set(piece.ProductionControlItemSequenceID, []);
            }
            piecemarkMap.get(piece.ProductionControlItemSequenceID).push(piece);
        });

        return workweekData.map(workweekItem => {
            const pciseqId = workweekItem.ProductionControlItemSequenceID;
            const pieces = piecemarkMap.get(pciseqId);

            if (!pieces) {
                return workweekItem;
            }

            // Update existing NESTED and CUT stations or add new ones
            let updatedStations = workweekItem.Stations || [];

            ['NESTED', 'CUT'].forEach(stationType => {
                const totalAssembliesNeeded = pieces[0].SequenceQuantity;

                if (stationType === 'NESTED') {
                    // For NESTED, we need to subtract pieces already cut
                    const cutCompleted = Math.min(...pieces.map(p => p.QtyCut || 0));
                    const nestedCompleted = Math.min(...pieces.map(p => p.QtyNested || 0));

                    // Total needed is assemblies needed minus what's already been cut
                    const totalNeeded = Math.max(0, totalAssembliesNeeded - cutCompleted);

                    const stationData = {
                        StationDescription: 'NESTED',
                        StationQuantityCompleted: nestedCompleted,
                        StationTotalQuantity: totalNeeded,
                        Pieces: pieces
                    };

                    const stationIndex = updatedStations.findIndex(s => s.StationDescription === 'NESTED');
                    if (stationIndex === -1) {
                        updatedStations.push(stationData);
                    } else {
                        updatedStations[stationIndex] = stationData;
                    }
                } else {
                    // CUT station logic remains the same
                    const cutCompleted = Math.min(...pieces.map(p => p.QtyCut || 0));

                    const stationData = {
                        StationDescription: 'CUT',
                        StationQuantityCompleted: cutCompleted,
                        StationTotalQuantity: totalAssembliesNeeded,
                        Pieces: pieces
                    };

                    const stationIndex = updatedStations.findIndex(s => s.StationDescription === 'CUT');
                    if (stationIndex === -1) {
                        updatedStations.push(stationData);
                    } else {
                        updatedStations[stationIndex] = stationData;
                    }
                }
            });

            // Return merged item
            return {
                ...workweekItem,
                Stations: updatedStations,
                Pieces: pieces,
                AssemblyEachQuantity: pieces[0]?.AssemblyEachQuantity || 0,
                TotalPieceMarkQuantityNeeded: pieces[0]?.TotalPieceMarkQuantityNeeded || 0
            };
        });
    }

    function createWPFilter() {
        const workPackageNumbers = [...new Set(projectData.map(item => item.WorkPackageNumber).filter(Boolean))];
        let wpFilterHtml = '<button class="btn btn-primary me-2 mb-2" onclick="filterWP(\'all\', this)">All Work Packages</button>';

        // Create buttons for each WorkPackageNumber
        workPackageNumbers.forEach(wp => {
            // Find all items for this work package
            const wpItems = projectData.filter(item => item.WorkPackageNumber === wp);

            // Check if any items are not released or on hold
            const isNotReleased = wpItems.some(item => item.ReleasedToFab === 0);
            const isOnHold = wpItems.some(item => item.OnHold === 1);

            // Create tooltip text if needed
            let tooltip = '';
            if (isNotReleased && isOnHold) {
                tooltip = 'Work Package not released and on hold';
            } else if (isNotReleased) {
                tooltip = 'Work Package not released';
            } else if (isOnHold) {
                tooltip = 'Work Package on hold';
            }

            // Add appropriate classes
            const extraClasses = [];
            if (isNotReleased) extraClasses.push('wpnotreleased');
            if (isOnHold) extraClasses.push('wponhold');

            wpFilterHtml += `<button class="btn btn-secondary me-2 mb-2 ${extraClasses.join(' ')}"
            onclick="filterWP('${wp}', this)"
            ${tooltip ? `title="${tooltip}"` : ''}>${wp}</button>`;
        });

        // Insert WorkPackageNumber buttons into #wpFilter
        $('#wpFilter').html(wpFilterHtml);
    }

    function filterWP(workPackage, button) {
        currentWPFilter = workPackage;
        filterData();

        // Update button styles
        $('#wpFilter button').removeClass('btn-primary').addClass('btn-secondary');
        $(button).removeClass('btn-secondary').addClass('btn-primary');

        updateFilterButtons();
    }

    function createBayFilter() {
        const bayNames = [...new Set(projectData.map(item => item.Bay).filter(Boolean))];
        let bayFilterHtml = '<button class="btn btn-primary me-2 mb-2" onclick="filterBay(\'all\', this)">All Bays</button>';
        let hasUndefined = projectData.some(item => !item.Bay);

        // Create buttons for each Bay
        bayNames.forEach(bay => {
            bayFilterHtml += `<button class="btn btn-secondary me-2 mb-2" onclick="filterBay('${bay}', this)">${bay}</button>`;
        });

        // Add Undefined button if there are items without a Bay
        if (hasUndefined) {
            bayFilterHtml += `<button class="btn btn-warning me-2 mb-2" onclick="filterBay('undefined', this)">Undefined</button>`;
        }

        $('#bayFilter').html(bayFilterHtml);
    }

    function filterBay(bay, button) {
        currentBayFilter = bay;
        filterData();

        // Update button styles
        $('#bayFilter button').removeClass('btn-primary').addClass('btn-secondary');
        $(button).removeClass('btn-secondary').addClass('btn-primary');

        updateFilterButtons();
    }

    function createRouteFilter() {
        const routes = [...new Set(projectData.map(item => item.RouteName).filter(Boolean))];
        let filterHtml = '<button class="btn btn-primary me-2 mb-2" onclick="filterRoute(\'all\', this)">All Routes</button>';
        let hasUndefined = projectData.some(item => !item.RouteName);

        routes.forEach(route => {
            filterHtml += `<button class="btn btn-secondary me-2 mb-2" onclick="filterRoute('${route}', this)">${route}</button>`;
        });

        if (hasUndefined) {
            filterHtml += `<button class="btn btn-warning me-2 mb-2" onclick="filterRoute('undefined', this)">Undefined</button>`;
        }

        $('#routeFilter').html(filterHtml);
    }

    function createCategoryFilter() {
        const categories = [...new Set(projectData.map(item => item.Category).filter(Boolean))];
        let categoryFilterHtml = '<button class="btn btn-primary me-2 mb-2" onclick="filterCategory(\'all\', this)">All Asm. Categories</button>';
        let hasUndefined = projectData.some(item => !item.Category);

        categories.forEach(category => {
            categoryFilterHtml += `<button class="btn btn-secondary me-2 mb-2" onclick="filterCategory('${category}', this)">${category}</button>`;
        });

        if (hasUndefined) {
            categoryFilterHtml += `<button class="btn btn-warning me-2 mb-2" onclick="filterCategory('undefined', this)">Undefined</button>`;
        }

        $('#categoryFilter').html(categoryFilterHtml);
    }

    function filterCategory(category, button) {
        currentCategoryFilter = category;
        filterData();

        // Update button styles
        $('#categoryFilter button').removeClass('btn-primary').addClass('btn-secondary');
        $(button).removeClass('btn-secondary').addClass('btn-primary');

        updateFilterButtons();
    }

    function filterRoute(route, button) {
        currentRouteFilter = route;
        filterData();

        // Update button styles
        $('#routeFilter button').removeClass('btn-primary').addClass('btn-secondary');
        $(button).removeClass('btn-secondary').addClass('btn-primary');

        updateFilterButtons();
    }

    function createSequenceFilter() {
        const sequences = [...new Set(projectData.map(item => item.SequenceDescription).filter(Boolean))];
        let sequenceFilterHtml = '<button class="btn btn-primary me-2 mb-2" onclick="filterSequence(\'all\', this)">All Sequences</button>';
        let hasUndefined = projectData.some(item => !item.SequenceDescription);

        sequences.forEach(sequence => {
            sequenceFilterHtml += `<button class="btn btn-secondary me-2 mb-2" onclick="filterSequence('${sequence}', this)">${sequence}</button>`;
        });

        if (hasUndefined) {
            sequenceFilterHtml += `<button class="btn btn-warning me-2 mb-2" onclick="filterSequence('undefined', this)">Undefined</button>`;
        }

        $('#sequenceFilter').html(sequenceFilterHtml);
    }

    function filterSequence(sequence, button) {
        currentSequenceFilter = sequence;
        filterData();

        // Update button styles
        $('#sequenceFilter button').removeClass('btn-primary').addClass('btn-secondary');
        $(button).removeClass('btn-secondary').addClass('btn-primary');

        updateFilterButtons();
    }

    function createTableHeader() {
        let headerHtml = `
                <tr>
                    <th>Job<br>Route</th>
                    <th>SeqLot<br>Main</th>
                    <th>WP</th>
                    <th>Asm. Qty</th>
                    <th>Net # Each / Total</th>
                    <th>Hrs. Each / Total</th>
            `;
        orderedStations.forEach(station => {
            headerHtml += `<th>${station}</th>`;
        });
        headerHtml += `</tr>`;
        $('#projectTable thead').html(headerHtml);
    }

    function calculateStationTotals(data) {
        let stationTotals = {};

        orderedStations.forEach(station => {
            stationTotals[station] = {
                completed: 0,
                total: 0,
                hours: {
                    completed: 0,
                    total: 0
                },
                weight: {
                    completed: 0,
                    total: 0
                }
            };
        });

        data.forEach(assembly => {
            const stationHours = calculateStationHours(assembly.RouteName, assembly.Category, assembly.TotalEstimatedManHours);
            const assemblyWeight = parseFloat(assembly.TotalNetWeight || 0);

            assembly.Stations.forEach(station => {
                const stationName = station.StationDescription;
                if (orderedStations.includes(stationName)) {
                    // Sum up quantities
                    stationTotals[stationName].completed += station.StationQuantityCompleted || 0;
                    stationTotals[stationName].total += station.StationTotalQuantity || 0;

                    // Calculate completion ratio for this station
                    const completionRatio = station.StationQuantityCompleted / station.StationTotalQuantity;

                    // Calculate and sum up hours
                    const stationTotalHours = stationHours[stationName] || 0;
                    const completedHours = stationTotalHours * completionRatio;

                    stationTotals[stationName].hours.completed += completedHours;
                    stationTotals[stationName].hours.total += stationTotalHours;

                    // Calculate and sum up weights
                    stationTotals[stationName].weight.completed += assemblyWeight * completionRatio;
                    stationTotals[stationName].weight.total += assemblyWeight;
                }
            });
        });

        return stationTotals;
    }

    function addStationSummaryRow(stationTotals, data) {
        const totalLineItems = data.length;
        const totalAsmQuantity = data.reduce((sum, item) => sum + (parseInt(item.SequenceMainMarkQuantity) || 0), 0);
        const completedLineItems = data.filter(item => checkCompletion(item.Stations)).length;
        const completedAssemblies = data.reduce((sum, item) => {
            if (checkCompletion(item.Stations)) {
                return sum + (parseInt(item.SequenceMainMarkQuantity) || 0);
            }
            return sum;
        }, 0);

        let bodyHtml = `<tr class="station-summary">
    <td colspan="6">
        Station Totals: (completed of total)<br>
        Line Items: ${completedLineItems} of ${totalLineItems}<br>
        Assemblies: ${completedAssemblies} of ${totalAsmQuantity}
    </td>`;

        orderedStations.forEach(station => {
            const totals = stationTotals[station];
            if (!totals || totals.total === 0) {
                bodyHtml += '<td class="col-empty">-</td>';
            } else {
                const qtyPercentage = safeDivide(totals.completed * 100, totals.total);
                const hoursPercentage = safeDivide(totals.hours.completed * 100, totals.hours.total);
                const weightPercentage = safeDivide(totals.weight.completed * 100, totals.weight.total);
                const isComplete = Math.abs(qtyPercentage - 100) < 0.01;

                if (station === 'NESTED') {
                    bodyHtml += `
            <td class="sumcell ${isComplete ? 'col-complete' : ''}">
                ASMNEED: ${totals.completed} / ${totals.total}<br>
                PCNEED: ${totals.pieces_completed || 0} / ${totals.pieces_total || 0}<br>
                ASMWT: ${formatNumberWithCommas(Math.round(totals.weight.completed))} / ${formatNumberWithCommas(Math.round(totals.weight.total))}
            </td>`;
                } else if (station === 'CUT') {
                    const pcQtyPercentage = safeDivide(totals.pieces_completed * 100, totals.pieces_total);
                    bodyHtml += `
    <td class="sumcell ${isComplete ? 'col-complete' : ''}">
        ASMQTY: ${totals.completed} / ${totals.total} (${qtyPercentage.toFixed(1)}%)<br>
        PCQTY: ${totals.pieces_completed || 0} / ${totals.pieces_total || 0} (${pcQtyPercentage.toFixed(1)}%)<br>
        ASMWT: ${formatNumberWithCommas(Math.round(totals.weight.completed))} / ${formatNumberWithCommas(Math.round(totals.weight.total))} (${weightPercentage.toFixed(1)}%)
    </td>`;
                } else {
                    bodyHtml += `
            <td class="sumcell ${isComplete ? 'col-complete' : ''}">
                QTY: ${totals.completed} / ${totals.total} (${qtyPercentage.toFixed(1)}%)<br>
                HRS: ${formatNumberWithCommas(Math.round(totals.hours.completed))} / ${formatNumberWithCommas(Math.round(totals.hours.total))} (${hoursPercentage.toFixed(1)}%)<br>
                WT: ${formatNumberWithCommas(Math.round(totals.weight.completed))} / ${formatNumberWithCommas(Math.round(totals.weight.total))} (${weightPercentage.toFixed(1)}%)
            </td>`;
                }
            }
        });

        return bodyHtml + '</tr>';
    }

    function calculateTotalUsedHours(data) {
        let totalUsed = 0;

        data.forEach(assembly => {
            if (!assembly || !assembly.Stations) return;

            const totalHours = parseFloat(assembly.TotalEstimatedManHours || 0);
            const stationHours = calculateStationHours(
                assembly.RouteName || 'DEFAULT',
                assembly.Category || 'DEFAULT',
                totalHours
            );

            // For each station, calculate the hours used based on completion percentage
            assembly.Stations.forEach(station => {
                if (!station || !orderedStations.includes(station.StationDescription)) return;

                const stationTotal = parseFloat(station.StationTotalQuantity || 0);
                const stationCompleted = parseFloat(station.StationQuantityCompleted || 0);
                const completionRatio = safeDivide(stationCompleted, stationTotal);
                const stationAllocatedHours = stationHours[station.StationDescription] || 0;

                totalUsed += stationAllocatedHours * completionRatio;
            });
        });

        return totalUsed;
    }

    function populateTable(data) {
        // Sort data to show completed items at the bottom
        data.sort((a, b) => {
            const aCompleted = checkCompletion(a.Stations);
            const bCompleted = checkCompletion(b.Stations);
            if (aCompleted === bCompleted) return 0;
            return aCompleted ? 1 : -1;
        });

        let bodyHtml = '';
        const totalJobHours = calculateTotalHours(data);
        const totalUsedHours = calculateTotalUsedHours(data);
        const remainingHours = totalJobHours - totalUsedHours;
        const stationTotals = calculateStationTotals(data);

        // Add station summary row
        bodyHtml += addStationSummaryRow(stationTotals, data);

        // Add individual assembly rows
        data.forEach(assembly => {
            const isCompleted = checkCompletion(assembly.Stations);
            const isOnHold = (assembly.ReleasedToFab != 1);
            const stationHours = calculateStationHours(assembly.RouteName, assembly.Category, assembly.TotalEstimatedManHours);

            bodyHtml += `
            <tr class="${isCompleted ? 'completed-row' : ''} ${isOnHold ? 'hold-row' : ''}">
                <td title="ProductionControlID: ${assembly.ProductionControlID}">
                    ${assembly.JobNumber}<br>${assembly.RouteName}
                </td>
                <td title="SequenceID: ${assembly.SequenceID}, ProductionControlItemID: ${assembly.ProductionControlItemID}">
                    ${assembly.SequenceDescription} [${assembly.LotNumber}]<br>${assembly.MainMark}<br>${assembly.Category}
                </td>
                <td title="ProductionControlItemSequenceID: ${assembly.ProductionControlItemSequenceID}">
                    ${assembly.WorkPackageNumber}
                </td>
                <td>${assembly.SequenceMainMarkQuantity}</td>
                <td>${formatNumberWithCommas(assembly.NetAssemblyWeightEach)}# / ${formatNumberWithCommas(assembly.TotalNetWeight)}#</td>
                <td>${formatNumber(assembly.AssemblyManHoursEach)} / ${formatNumber(assembly.TotalEstimatedManHours)}</td>
        `;

            // Add cells for each station
            orderedStations.forEach(stationName => {
                const station = assembly.Stations.find(s => s.StationDescription === stationName);
                if (station) {
                    const statusClass = getStatusClass(station.StationQuantityCompleted, station.StationTotalQuantity);
                    const stationTotalHours = stationHours[stationName] || 0;
                    const stationUsedHours = safeDivide(station.StationQuantityCompleted * stationTotalHours, station.StationTotalQuantity);

                    let cellContent = '';

                    if (['NESTED', 'CUT'].includes(stationName)) {
                        const qty = station.StationQuantityCompleted;
                        const totalNeeded = station.StationTotalQuantity;
                        const completedAssemblies = Math.floor(safeDivide(qty, assembly.AssemblyEachQuantity));
                        const totalAssemblies = Math.floor(safeDivide(totalNeeded, assembly.AssemblyEachQuantity));
                        const statusClass = getStatusClass(completedAssemblies, totalAssemblies);

                        // Calculate total pieces (sum of all individual piecemarks)
                        const totalPiecesCompleted = station.Pieces ? station.Pieces.reduce((sum, piece) =>
                            sum + parseInt(stationName === 'NESTED' ? piece.QtyNested || 0 : piece.QtyCut || 0), 0) : 0;
                        const totalPiecesNeeded = station.Pieces ? station.Pieces.reduce((sum, piece) =>
                            sum + parseInt(piece.TotalPieceMarkQuantityNeeded || 0), 0) : 0;

                        bodyHtml += `
                        <td class="${statusClass}">
                            <a href="#" class="station-details" data-station="${stationName}"
                               data-assembly="${assembly.ProductionControlItemSequenceID}">
                                ASM: ${completedAssemblies} / ${totalAssemblies}
                            </a>
                            <br>PCS: ${totalPiecesCompleted} / ${totalPiecesNeeded}
                        </td>`;
                    } else {
                        const completionRatio = safeDivide(station.StationQuantityCompleted, station.StationTotalQuantity);
                        const assemblyWeight = parseFloat(assembly.TotalNetWeight || 0);
                        const stationCompletedWeight = assemblyWeight * completionRatio;

                        cellContent = `
                        ${station.StationQuantityCompleted} / ${station.StationTotalQuantity}<br>
                        HRS: ${formatNumber(stationUsedHours)} / ${formatNumber(stationTotalHours)}<br>
                        WT: ${formatNumberWithCommas(Math.round(stationCompletedWeight))}#
                    `;
                        bodyHtml += `<td class="${statusClass}">${cellContent}</td>`;
                    }
                } else {
                    bodyHtml += `<td class="status-notstarted status-na">-</td>`;
                }
            });
            bodyHtml += `</tr>`;
        });

        $('#projectTable tbody').html(bodyHtml);

        // Add click handlers
        $('#projectTable tbody tr').on('click', function() {
            const pciseqId = $(this).find('td:nth-child(3)').attr('title').split(': ')[1];
            const rowData = projectData.find(item =>
                item.ProductionControlItemSequenceID.toString() === pciseqId
            );
        });

        $('.station-details').on('click', function(e) {
            e.preventDefault();
            const stationName = $(this).data('station');
            const assemblyId = $(this).data('assembly');
            showPiecemarkDetails(stationName, assemblyId);
        });

        // Update summary data
        updateDataSummary(data, totalJobHours, totalUsedHours, remainingHours);
    }

    function showPiecemarkDetails(stationName, productionControlItemSequenceId) {
        const assembly = projectData.find(a => a.ProductionControlItemSequenceID === productionControlItemSequenceId);
        if (!assembly) return;

        const modalTitle = `${stationName} Details for Assembly ${assembly.MainMark} <br>(Total Assemblies Needed: ${assembly.SequenceMainMarkQuantity})`;
        $('#piecemarkModalLabel').html(modalTitle);

        let tableHeader = `
        <tr>
            <th>Piece Mark</th>
            <th>Pieces per Assembly</th>
            <th>Total Piecemarks Needed</th>
            <th>Piecemarks Completed</th>
            <th>Status</th>
        </tr>
    `;

        const station = assembly.Stations.find(s => s.StationDescription === stationName);
        if (!station || !station.Pieces) return;

        let tableBody = '';
        let minCompletedAssemblies = Infinity;

        station.Pieces.forEach(piece => {
            const completed = stationName === 'NESTED' ? piece.QtyNested : piece.QtyCut;
            const needed = piece.TotalPieceMarkQuantityNeeded;
            const assembliesComplete = Math.floor(completed / piece.AssemblyEachQuantity);
            minCompletedAssemblies = Math.min(minCompletedAssemblies, assembliesComplete);

            const status = completed >= needed ? 'Complete' : `${((completed/needed) * 100).toFixed(1)}%`;

            tableBody += `
            <tr class="${completed >= needed ? '' : 'uncompleted-piecemark'}">
                <td>${piece.Shape}-${piece.PieceMark}</td>
                <td>${piece.AssemblyEachQuantity}</td>
                <td>${needed}</td>
                <td>${completed}</td>
                <td>${status}</td>
            </tr>
        `;
        });

        // Add summary row
        tableBody += `
        <tr class="table-info">
            <td colspan="4"><strong>Total Assemblies Complete:</strong></td>
            <td><strong>${minCompletedAssemblies === Infinity ? 0 : minCompletedAssemblies}</strong></td>
        </tr>
    `;

        $('#piecemarkTable thead').html(tableHeader);
        $('#piecemarkTable tbody').html(tableBody);

        const modal = new bootstrap.Modal(document.getElementById('piecemarkModal'));
        modal.show();
    }

    function updateDataSummary(data, totalJobHours, totalUsedHours, remainingHours) {
        if (!data || data.length === 0) {
            $('#dataSummary').html('<strong>No data available</strong>');
            return;
        }

        const totalWeight = calculateTotalWeight(data);
        const completedWeight = calculateCompletedWeight(data);
        const totalTons = safeDivide(totalWeight, 2000);
        const hoursPerTon = safeDivide(totalJobHours, totalTons);
        const lbsPerHour = safeDivide(totalWeight, totalJobHours);

        const percentageCompleteByHours = safeDivide(totalUsedHours * 100, totalJobHours);
        const percentageCompleteByWeight = safeDivide(completedWeight * 100, totalWeight);

        // Update hours summary with safe number formatting
        $('#hoursSummary').html(`
        Visible Total Hours: ${formatNumberWithCommas(totalJobHours)}<br>
        Visible Hours Complete: ${formatNumberWithCommas(totalUsedHours)} (${percentageCompleteByHours.toFixed(2)}%)<br>
        Visible Hours Remaining: ${formatNumberWithCommas(remainingHours)}<br>
        Visible Hours per Ton: ${hoursPerTon.toFixed(2)}<span style="font-size: 0.8rem; font-weight: bold; color: #3a0202"> -
        ${lbsPerHour.toFixed(2)} (lbs/hr)</span>
    `);

        // Update weight summary
        $('#weightSummary').html(`
        Visible Total Weight: ${formatNumberWithCommas(totalWeight)} lbs (${formatNumberWithCommas(totalTons)} tons)<br>
        Visible Green Flag Weight: ${formatNumberWithCommas(completedWeight)} lbs (${percentageCompleteByWeight.toFixed(2)}%)<br>
    `);
    }

    function checkCompletion(stations) {
        const lastRelevantStation = [...stations].reverse().find(station =>
            station.StationDescription === "FINAL QC"
        );

        return lastRelevantStation &&
            lastRelevantStation.StationQuantityCompleted === lastRelevantStation.StationTotalQuantity;
    }

    function formatNumber(value) {
        return parseFloat(value).toFixed(2);
    }

    function getStatusClass(completed, total) {
        if (completed === 0 && total === 0) {
            return 'status-complete';  // Return complete status for 0/0
        } else if (completed === 0) {
            return 'status-notstarted';
        } else if (completed === total) {
            return 'status-complete';
        } else {
            return 'status-partial';
        }
    }

    function calculateTotalWeight(data) {
        return data.reduce((sum, assembly) => sum + parseFloat(assembly.TotalNetWeight || 0), 0);
    }

    function calculateTotalHours(data) {
        return data.reduce((sum, assembly) => sum + parseFloat(assembly.TotalEstimatedManHours || 0), 0);
    }


    function formatNumberWithCommas(number) {
        if (isNaN(number) || number === null || number === undefined) return "0";
        return Number(parseFloat(number).toFixed(0)).toLocaleString();
    }

    function calculateCompletedWeight(data) {
        return data.reduce((sum, assembly) => {
            const assemblyWeight = parseFloat(assembly.TotalNetWeight || 0);
            const lastStation = assembly.Stations
                .filter(station => orderedStations.includes(station.StationDescription))
                .sort((a, b) => orderedStations.indexOf(b.StationDescription) - orderedStations.indexOf(a.StationDescription))[0];

            if (lastStation && lastStation.StationQuantityCompleted === lastStation.StationTotalQuantity) {
                return sum + assemblyWeight;
            }
            return sum;
        }, 0);
    }

    function calculateOverallProgress(data) {
        let totalStations = 0;
        let completedStations = 0;

        data.forEach(assembly => {
            assembly.Stations.forEach(station => {
                if (orderedStations.includes(station.StationDescription)) {
                    totalStations++;
                    if (station.StationQuantityCompleted === station.StationTotalQuantity) {
                        completedStations++;
                    }
                }
            });
        });

        return ((completedStations / totalStations) * 100).toFixed(2);
    }

    function safeDivide(numerator, denominator) {
        if (!denominator || isNaN(denominator)) return 0;
        const result = numerator / denominator;
        return isNaN(result) ? 0 : result;
    }

    function calculateStationTotals(data) {
        let stationTotals = {};

        orderedStations.forEach(station => {
            stationTotals[station] = {
                completed: 0,
                total: 0,
                pieces_completed: 0,
                pieces_total: 0,
                hours: {
                    completed: 0,
                    total: 0
                },
                weight: {
                    completed: 0,
                    total: 0
                }
            };
        });

        data.forEach(assembly => {
            if (!assembly || !assembly.Stations) return;

            const stationHours = calculateStationHours(
                assembly.RouteName || 'DEFAULT',
                assembly.Category || 'DEFAULT',
                parseFloat(assembly.TotalEstimatedManHours || 0)
            );

            const assemblyWeight = parseFloat(assembly.TotalNetWeight || 0);

            assembly.Stations.forEach(station => {
                if (!station) return;

                const stationName = station.StationDescription;
                if (!orderedStations.includes(stationName)) return;

                let completed = parseFloat(station.StationQuantityCompleted || 0);
                let total = parseFloat(station.StationTotalQuantity || 0);

                // Sum up quantities
                stationTotals[stationName].completed += completed;
                stationTotals[stationName].total += total;

                // Calculate hours and weights
                const completionRatio = safeDivide(completed, total);
                const stationTotalHours = stationHours[stationName] || 0;
                const completedHours = stationTotalHours * completionRatio;

                stationTotals[stationName].hours.completed += completedHours;
                stationTotals[stationName].hours.total += stationTotalHours;
                stationTotals[stationName].weight.completed += assemblyWeight * completionRatio;
                stationTotals[stationName].weight.total += assemblyWeight;

                // Calculate piece totals for NESTED and CUT stations
                if (['NESTED', 'CUT'].includes(stationName) && station.Pieces) {
                    station.Pieces.forEach(piece => {
                        if (stationName === 'NESTED') {
                            stationTotals[stationName].pieces_completed += parseInt(piece.QtyNested || 0);
                        } else {
                            stationTotals[stationName].pieces_completed += parseInt(piece.QtyCut || 0);
                        }
                        stationTotals[stationName].pieces_total += parseInt(piece.TotalPieceMarkQuantityNeeded || 0);
                    });
                }
            });
        });

        return stationTotals;
    }

    function updateFilterButtons() {
        // First disable all buttons except 'All' buttons
        $('#bayFilter button, #wpFilter button, #routeFilter button, #categoryFilter button').each(function() {
            const buttonText = $(this).text();
            if (!buttonText.startsWith('All')) {
                $(this).prop('disabled', true);
            }
        });

        // Get filtered data based on current filters
        const filteredData = projectData.filter(item => {
            const matchesRoute = currentRouteFilter === 'all' ||
                (currentRouteFilter === 'undefined' ? !item.RouteName : item.RouteName === currentRouteFilter);
            const matchesWP = currentWPFilter === 'all' ||
                (currentWPFilter === 'undefined' ? !item.WorkPackageNumber : item.WorkPackageNumber === currentWPFilter);
            const matchesBay = currentBayFilter === 'all' ||
                (currentBayFilter === 'undefined' ? !item.Bay : item.Bay === currentBayFilter);
            const matchesCategory = currentCategoryFilter === 'all' ||
                (currentCategoryFilter === 'undefined' ? !item.Category : item.Category === currentCategoryFilter);

            return matchesRoute && matchesWP && matchesBay && matchesCategory;
        });

        // Enable buttons based on filtered data
        filteredData.forEach(item => {
            if (item.Bay) {
                $(`#bayFilter button:contains('${item.Bay}')`).prop('disabled', false);
            } else {
                $(`#bayFilter button:contains('Undefined')`).prop('disabled', false);
            }

            if (item.WorkPackageNumber) {
                $(`#wpFilter button:contains('${item.WorkPackageNumber}')`).prop('disabled', false);
            } else {
                $(`#wpFilter button:contains('Undefined')`).prop('disabled', false);
            }

            if (item.RouteName) {
                $(`#routeFilter button:contains('${item.RouteName}')`).prop('disabled', false);
            } else {
                $(`#routeFilter button:contains('Undefined')`).prop('disabled', false);
            }

            if (item.Category) {
                $(`#categoryFilter button:contains('${item.Category}')`).prop('disabled', false);
            } else {
                $(`#categoryFilter button:contains('Undefined')`).prop('disabled', false);
            }
        });
    }

    function calculateStationHours(route, category, totalHours) {
        // Define the distribution matrix based on route and category
        const distributions = {
            '04: SSF CUT & FAB': {
                'BEAMS': {
                    'NESTED': 0.01,
                    'CUT': 0.06,
                    'FIT': 0.38,
                    'WELD': 0.51,
                    'FINAL QC': 0.04
                },
                'COLUMNS': {
                    'NESTED': 0.01,
                    'CUT': 0.06,
                    'FIT': 0.40,
                    'WELD': 0.49,
                    'FINAL QC': 0.04
                },
                // Add more categories as needed
                'DEFAULT': {
                    'NESTED': 0.01,
                    'CUT': 0.06,
                    'FIT': 0.38,
                    'WELD': 0.51,
                    'FINAL QC': 0.04
                }
            },
            // Add more routes as needed
            'DEFAULT': {
                'DEFAULT': {
                    'NESTED': 0.01,
                    'CUT': 0.06,
                    'FIT': 0.38,
                    'WELD': 0.51,
                    'FINAL QC': 0.04
                }
            }
        };

        // Get the distribution for the specific route and category, or fall back to defaults
        const routeDist = distributions[route] || distributions['DEFAULT'];
        const categoryDist = routeDist[category] || routeDist['DEFAULT'];

        // Calculate hours for each station
        let result = {};
        orderedStations.forEach(station => {
            result[station] = totalHours * (categoryDist[station] || 0);
        });

        return result;
    }

    function analyzeRouteCategoryStations() {
        const combinations = new Set();

        projectData.forEach(item => {
            const route = item.RouteName || 'undefined';
            const category = item.Category || 'undefined';

            // Get current distribution if it exists
            const currentDist = {};
            const hours = calculateStationHours(route, category, 100);  // Use 100 to get percentages directly
            if (hours) {
                Object.entries(hours).forEach(([station, value]) => {
                    currentDist[station] = value;
                });
            }

            item.Stations.forEach(station => {
                const stationName = station.StationDescription;
                const percentage = currentDist[stationName] || '';
                combinations.add(`${route}\t${category}\t${stationName}\t${percentage}`);
            });
        });

        // Create one big string
        const output = 'Route\tCategory\tStation\tPercentage\n' +
            Array.from(combinations)
                .sort()
                .join('\n');

        // Create a temporary textarea to copy from
        const tempTextArea = document.createElement('textarea');
        tempTextArea.value = output;
        document.body.appendChild(tempTextArea);
        tempTextArea.select();
        document.execCommand('copy');
        document.body.removeChild(tempTextArea);

        console.log('Data has been copied to clipboard. Here it is for reference:');
        console.log(output);

        return 'Data copied to clipboard!';
    }

</script>
</body>
</html>