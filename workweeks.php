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
            font-size: small;
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

        #routeFilter {
            margin-bottom: 1rem;
        }
        #routeFilter button {
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .card-body .form-control {
            margin-bottom: 10px;
        }
        .station-summary {
            font-weight: bold;
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
        .export-buttons {
            margin: 10px 0;
            display: none; /* Hide by default */
            gap: 10px;
            position: absolute;
            top: 32px;
            right: 0px;
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
            <div id="bayFilter" class="mb-3 d-flex flex-wrap">
                <!-- Bay filter buttons will be dynamically inserted here -->
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
    <div id="wpFilter" class="mb-3 d-flex flex-wrap">
        <!-- Work Package Number filter buttons will be dynamically inserted here -->
    </div>
    <div id="routeFilter" class="mb-3 d-flex flex-wrap">
        <!-- Route filter buttons will be dynamically inserted here -->
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
    const orderedStations = ['NESTED','CUT','FIT','WELD','SBA','FINAL QC'];
    var currentRouteFilter = 'all'; // Global variable for the selected route filter
    var currentWPFilter = 'all'; // Global variable for the selected work package filter
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
            let matchesRoute = currentRouteFilter === 'all' || item.RouteName === currentRouteFilter;
            let matchesWP = currentWPFilter === 'all' || item.WorkPackageNumber === currentWPFilter;
            let matchesBay = currentBayFilter === 'all' || item.BayName === currentBayFilter;
            return matchesRoute && matchesWP && matchesBay;
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
            })
        ).done(function(response) {
            if (response.error) {
                alert(response.error);
                return;
            }

            projectData = Array.isArray(response.items) ? response.items : [response.items];

            createWPFilter();
            createRouteFilter();
            createBayFilter();

            createTableHeader();

            currentRouteFilter = 'all';
            currentWPFilter = 'all';
            currentBayFilter = 'all';
            filterData();

            $('#jobTitle').text(`Workweek: ${workweek}`);
            $('#big-text').text(`${workweek}`);
        }).fail(function(xhr, status, error) {
            console.error("Error fetching data:", error);
            alert("Error loading project data. Please try again.");
        });
    }

    function createWPFilter() {
        const workPackageNumbers = [...new Set(projectData.map(item => item.WorkPackageNumber).filter(Boolean))];
        let wpFilterHtml = '<button class="btn btn-primary me-2 mb-2" onclick="filterWP(\'all\', this)">All Work Packages</button>';

        // Create buttons for each WorkPackageNumber
        workPackageNumbers.forEach(wp => {
            wpFilterHtml += `<button class="btn btn-secondary me-2 mb-2" onclick="filterWP('${wp}', this)">${wp}</button>`;
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
    }

    function createBayFilter() {
        const bayNames = [...new Set(projectData.map(item => item.BayName).filter(Boolean))];
        let bayFilterHtml = '<button class="btn btn-primary me-2 mb-2" onclick="filterBay(\'all\', this)">All Bays</button>';

        // Create buttons for each WorkPackageNumber
        bayNames.forEach(bay => {
            bayFilterHtml += `<button class="btn btn-secondary me-2 mb-2" onclick="filterBay('${bay}', this)">${bay}</button>`;
        });

        // Insert WorkPackageNumber buttons into #wpFilter
        $('#bayFilter').html(bayFilterHtml);
    }

    function filterBay(bay, button) {
        currentBayFilter = bay;
        filterData();

        // Update button styles
        $('#bayFilter button').removeClass('btn-primary').addClass('btn-secondary');
        $(button).removeClass('btn-secondary').addClass('btn-primary');
    }

    function createRouteFilter() {
        const routes = [...new Set(projectData.map(item => item.RouteName).filter(Boolean))];
        let filterHtml = '<button class="btn btn-primary me-2 mb-2" onclick="filterRoute(\'all\', this)">All Routes</button>';
        routes.forEach(route => {
            filterHtml += `<button class="btn btn-secondary me-2 mb-2" onclick="filterRoute('${route}', this)">${route}</button>`;
        });
        $('#routeFilter').html(filterHtml);
    }

    function filterRoute(route, button) {
        currentRouteFilter = route;
        filterData();

        // Update button styles
        $('#routeFilter button').removeClass('btn-primary').addClass('btn-secondary');
        $(button).removeClass('btn-secondary').addClass('btn-primary');
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

    function populateTable(data) {
        data.sort((a, b) => {
            const aCompleted = checkCompletion(a.Stations);
            const bCompleted = checkCompletion(b.Stations);
            if (aCompleted === bCompleted) return 0;
            return aCompleted ? 1 : -1; // 1 means a goes after b, -1 means a goes before b
        });


        let bodyHtml = '';
        let totalJobHours = 0;
        let totalUsedHours = 0;
        let stationTotals = {};

        orderedStations.forEach(station => {
            stationTotals[station] = { completed: 0, total: 0 };
        });

        // First row for station summaries
        bodyHtml += '<tr class="station-summary"><td colspan="6">Station Totals:</td>';

        // Calculate totals
        data.forEach(assembly => {
            const stationHours = calculateStationHours(assembly.RouteName, assembly.TotalEstimatedManHours);
            orderedStations.forEach(stationName => {
                const station = assembly.Stations.find(s => s.StationDescription === stationName);
                if (station) {
                    const stationTotalHours = stationHours[stationName] || 0;
                    const stationUsedHours = (station.StationQuantityCompleted / station.StationTotalQuantity) * stationTotalHours;
                    stationTotals[stationName].completed += stationUsedHours;
                    stationTotals[stationName].total += stationTotalHours;
                }
            });
        });

        // Add station summary data to the first row
        orderedStations.forEach(station => {
            const completed = stationTotals[station].completed;
            const total = stationTotals[station].total;

            if (total === 0) {
                // Option 1: Dark gray with white text
                bodyHtml += `<td class="col-empty">-</td>`;
            } else {
                const percentage = (completed / total * 100).toFixed(2);
                const isComplete = parseFloat(percentage) === 100;
                bodyHtml += `<td class="sumcell ${isComplete ? 'col-complete' : ''}">${formatNumber(completed)} / ${formatNumber(total)} <br>(${percentage}%)</td>`;


            }
        });
        bodyHtml += '</tr>';

        // Add individual assembly rows
        data.forEach(assembly => {
            const isCompleted = checkCompletion(assembly.Stations);
            const isOnHold = (assembly.ReleasedToFab != 1);
            const stationHours = calculateStationHours(assembly.RouteName, assembly.TotalEstimatedManHours);

            bodyHtml += `
        <tr class="${isCompleted ? 'completed-row' : ''} ${isOnHold ? 'hold-row' : ''}">
            <td title="ProductionControlID: ${assembly.ProductionControlID}">${assembly.JobNumber}<br>${assembly.RouteName}</td>
            <td title="SequenceID: ${assembly.SequenceID}, ProductionControlItemID: ${assembly.ProductionControlItemID}">${assembly.SequenceDescription}-${assembly.LotNumber}<br>${assembly.MainMark}</td>
            <td title="ProductionControlItemSequenceID: ${assembly.ProductionControlItemSequenceID}">${assembly.WorkPackageNumber}</td>
            <td>${assembly.SequenceMainMarkQuantity}</td>
            <td>${formatNumberWithCommas(assembly.NetAssemblyWeightEach)}# / ${formatNumberWithCommas(assembly.TotalNetWeight)}#</td>
            <td>${formatNumber(assembly.AssemblyManHoursEach)} / ${formatNumber(assembly.TotalEstimatedManHours)}</td>
        `;

            totalJobHours += parseFloat(assembly.TotalEstimatedManHours);

            orderedStations.forEach(stationName => {
                const station = assembly.Stations.find(s => s.StationDescription === stationName);
                if (station ) {
                    const statusClass = getStatusClass(station.StationQuantityCompleted, station.StationTotalQuantity);
                    const stationTotalHours = stationHours[stationName] || 0;
                    const stationUsedHours = (station.StationQuantityCompleted / station.StationTotalQuantity) * stationTotalHours;
                    totalUsedHours += stationUsedHours;

                    let cellContent = '';

                    if (['CNC', 'Kit-Up', 'TCNC'].includes(stationName)) {
                        cellContent = `${station.StationQuantityCompleted} / ${station.StationTotalQuantity}`;
                    } else {
                        cellContent = `
                        ${station.StationQuantityCompleted} / ${station.StationTotalQuantity}<br>
                        HRS: ${formatNumber(stationUsedHours)} / ${formatNumber(stationTotalHours)}
                    `;
                    }

                    if (['NESTED', 'CUT'].includes(stationName)) {
                        cellContent = `<a href="#" class="station-details" data-station="${stationName}" data-assembly="${assembly.ProductionControlItemSequenceID}">${cellContent}</a>`;
                    }

                    bodyHtml += `<td class="${statusClass}">${cellContent}</td>`;
                } else {
                    bodyHtml += `<td class="status-notstarted status-na">-</td>`;
                }
            });
            bodyHtml += `</tr>`;
        });

        $('#projectTable tbody').html(bodyHtml);

        // Add click event listener for station details
        $('.station-details').on('click', function(e) {
            e.preventDefault();
            const stationName = $(this).data('station');
            const assemblyId = $(this).data('assembly');
            showPiecemarkDetails(stationName, assemblyId);
        });

        const remainingHours = totalJobHours - totalUsedHours;
        updateDataSummary(data, totalJobHours, totalUsedHours, remainingHours);
    }

    function showPiecemarkDetails(stationName, productionControlItemSequenceId) {
        const assembly = projectData.find(a => a.ProductionControlItemSequenceID === productionControlItemSequenceId);
        if (!assembly) {
            console.warn(`No assembly found for ProductionControlItemSequenceID: ${productionControlItemSequenceId}`);
            return;
        }

        const station = assembly.Stations.find(s => s.StationDescription === stationName);
        if (!station) {
            console.warn(`No station ${stationName} found for assembly with ProductionControlItemSequenceID: ${productionControlItemSequenceId}`);
            return;
        }

        const modalTitle = `${stationName} Details for Assembly ${assembly.MainMark}`;
        $('#piecemarkModalLabel').text(modalTitle);

        let tableBody = '';
        if (station.PieceMarks && station.PieceMarks.length > 0) {
            // Sort the PieceMarks array alphabetically by PieceMark
            const sortedPieceMarks = station.PieceMarks.sort((a, b) => a.PieceMark.localeCompare(b.PieceMark));

            sortedPieceMarks.forEach(piecemark => {
                const isCompleted = piecemark.CompletedQuantity >= piecemark.TotalPieceMarkQuantity;
                tableBody += `
                <tr class="${!isCompleted ? 'uncompleted-piecemark' : ''}">
                    <td>${piecemark.PieceMark}</td>
                    <td>${piecemark.JobQuantity}</td>
                    <td>${piecemark.AssemblyEachQuantity}</td>
                    <td>${piecemark.TotalPieceMarkQuantity}</td>
                    <td>${piecemark.CompletedQuantity}</td>
                </tr>
            `;
            });
        } else {
            tableBody = '<tr><td colspan="5">No piecemark details available for this station.</td></tr>';
        }

        $('#piecemarkTable tbody').html(tableBody);

        const modal = new bootstrap.Modal(document.getElementById('piecemarkModal'));
        modal.show();
    }

    function updateDataSummary(data, totalJobHours, totalUsedHours, remainingHours) {
        if (data.length === 0) {
            $('#dataSummary').html('<strong>No data available</strong>');
            return;
        }

        const totalWeight = calculateTotalWeight(data);
        const completedWeight = calculateCompletedWeight(data);
        const totalTons = totalWeight / 2000; // Convert pounds to tons
        const hoursPerTon = totalTons > 0 ? totalJobHours / totalTons : 0;
        const lbsPerHour = totalJobHours > 0 ? totalWeight / totalJobHours : 0;

        const percentageCompleteByHours = totalJobHours > 0 ? (totalUsedHours / totalJobHours) * 100 : 0;
        const percentageCompleteByWeight = (completedWeight / totalWeight) * 100;

        // Assuming job number and description are the same for all rows
        const jobNumber = data[0].JobNumber;
        const jobDescription = data[0].ProjectDescription || 'N/A';

        // Update job title and description

        // Update hours summary
        $('#hoursSummary').html(`
        Visible Total Hours: ${formatNumberWithCommas(totalJobHours)}<br>
        Visible Hours Complete: ${formatNumberWithCommas(totalUsedHours)} (${percentageCompleteByHours.toFixed(2)}%)<br>
        Visible Hours Remaining: ${formatNumberWithCommas(remainingHours)}<br>
        Visible Hours per Ton: ${hoursPerTon.toFixed(2)}<span style="font-size: 0.8rem; font-weight: bold; color: #3a0202  "> -
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
        if (completed === 0) {
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
        if (!isNaN(number) && number !== null && number !== undefined) {
            return Number(number).toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }
        return number;
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

    function calculateStationHours(route, totalHours) {
        switch (route) {
            case '04: SSF CUT & FAB':
                return {
                    'FIT': totalHours * 0.38,
                    'WELD': totalHours * 0.58,
                    'SBA': totalHours * 0.58,
                    'FINAL QC': totalHours * 0.04,
                    'CUT': totalHours * 0.0001,
                    'IFF': totalHours * 0.0001,
                    'IFA': totalHours * 0.0001
                };
            case '10:  SBA':
                return {
                    'FIT': totalHours * 0.38,
                    'WELD': totalHours * 0.58,
                    'SBA': totalHours * 0.58,
                    'FINAL QC': totalHours * 0.04,
                    'CUT': totalHours * 0.0001,
                    'IFF': totalHours * 0.0001,
                    'IFA': totalHours * 0.0001
                };
            case '00: PLANNED':
                return {
                    'FIT': totalHours * 0.38,
                    'WELD': totalHours * 0.58,
                    'SBA': totalHours * 0.58,
                    'FINAL QC': totalHours * 0.04,
                    'CUT': totalHours * 0.0001,
                    'IFF': totalHours * 0.0001,
                    'IFA': totalHours * 0.0001
                };
            default:
                return {
                    'FIT': 0,
                    'WELD': 0,
                    'FINAL QC': totalHours
                };
        }
    }
    function prepareExportData() {
        const exportData = [];
        const stationTotals = {
            totalJobHours: 0,
            totalUsedHours: 0,
            stations: {}
        };

        // Initialize station totals
        orderedStations.forEach(station => {
            stationTotals.stations[station] = {
                completed: 0,
                total: 0,
                completedHours: 0,
                totalHours: 0
            };
        });

        // Process each assembly
        projectData.forEach(assembly => {
            const stationHours = calculateStationHours(assembly.RouteName, assembly.TotalEstimatedManHours);
            const assemblyData = {
                jobNumber: assembly.JobNumber,
                routeName: assembly.RouteName,
                sequenceDescription: assembly.SequenceDescription,
                lotNumber: assembly.LotNumber,
                mainMark: assembly.MainMark,
                workPackageNumber: assembly.WorkPackageNumber,
                SequenceMainMarkQuantity: assembly.SequenceMainMarkQuantity,
                netWeightEach: assembly.NetAssemblyWeightEach,
                totalNetWeight: assembly.TotalNetWeight,
                hoursEach: assembly.AssemblyManHoursEach,
                totalHours: assembly.TotalEstimatedManHours,
                stations: {}
            };

            // Add total hours to station totals
            stationTotals.totalJobHours += parseFloat(assembly.TotalEstimatedManHours);

            // Process each station
            orderedStations.forEach(stationName => {
                const station = assembly.Stations.find(s => s.StationDescription === stationName);
                if (station) {
                    const stationTotalHours = stationHours[stationName] || 0;
                    const stationUsedHours = (station.StationQuantityCompleted / station.StationTotalQuantity) * stationTotalHours;

                    assemblyData.stations[stationName] = {
                        quantityCompleted: station.StationQuantityCompleted,
                        quantityTotal: station.StationTotalQuantity,
                        hoursUsed: stationUsedHours,
                        hoursTotal: stationTotalHours,
                        piecemarks: station.PieceMarks || []
                    };

                    // Update station totals
                    stationTotals.stations[stationName].completed += station.StationQuantityCompleted;
                    stationTotals.stations[stationName].total += station.StationTotalQuantity;
                    stationTotals.stations[stationName].completedHours += stationUsedHours;
                    stationTotals.stations[stationName].totalHours += stationTotalHours;
                    stationTotals.totalUsedHours += stationUsedHours;
                } else {
                    assemblyData.stations[stationName] = {
                        quantityCompleted: 0,
                        quantityTotal: 0,
                        hoursUsed: 0,
                        hoursTotal: 0,
                        piecemarks: []
                    };
                }
            });

            exportData.push(assemblyData);
        });

        return { exportData, stationTotals };
    }

    function exportToJSON() {
        const { exportData, stationTotals } = prepareExportData();
        const jsonData = {
            assemblies: exportData,
            stationTotals: stationTotals
        };

        const dataStr = JSON.stringify(jsonData, null, 2);
        const blob = new Blob([dataStr], { type: 'application/json' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `workpackage_data_${new Date().toISOString().split('T')[0]}.json`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
    }

    function exportToCSV() {
        const { exportData, stationTotals } = prepareExportData();

        // Prepare CSV headers
        let csvContent = [
            'Job Number,Route Name,Sequence Description,Lot Number,Main Mark,Work Package Number,' +
            'Sequence Quantity,Net Weight Each,Total Net Weight,Hours Each,Total Hours'
        ];

        // Add station headers
        orderedStations.forEach(station => {
            csvContent[0] += `,${station} Completed,${station} Total,${station} Hours Used,${station} Hours Total`;
        });

        // Add assembly data
        exportData.forEach(assembly => {
            let row = [
                assembly.jobNumber,
                assembly.routeName,
                assembly.sequenceDescription,
                assembly.lotNumber,
                assembly.mainMark,
                assembly.workPackageNumber,
                assembly.SequenceMainMarkQuantity,
                assembly.netWeightEach,
                assembly.totalNetWeight,
                assembly.hoursEach,
                assembly.totalHours
            ];

            // Add station data
            orderedStations.forEach(station => {
                const stationData = assembly.stations[station];
                row.push(
                    stationData.quantityCompleted,
                    stationData.quantityTotal,
                    stationData.hoursUsed.toFixed(2),
                    stationData.hoursTotal.toFixed(2)
                );
            });

            csvContent.push(row.join(','));
        });

        // Add station totals
        csvContent.push('\nStation Totals');
        let totalsRow = ['Total Hours', stationTotals.totalJobHours.toFixed(2)];
        csvContent.push(totalsRow.join(','));

        totalsRow = ['Used Hours', stationTotals.totalUsedHours.toFixed(2)];
        csvContent.push(totalsRow.join(','));

        orderedStations.forEach(station => {
            const stationTotal = stationTotals.stations[station];
            const totalsRow = [
                `${station} Totals`,
                `Completed: ${stationTotal.completed}`,
                `Total: ${stationTotal.total}`,
                `Hours Used: ${stationTotal.completedHours.toFixed(2)}`,
                `Hours Total: ${stationTotal.totalHours.toFixed(2)}`
            ];
            csvContent.push(totalsRow.join(','));
        });

        // Create and download CSV file
        const blob = new Blob([csvContent.join('\n')], { type: 'text/csv;charset=utf-8;' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `workpackage_data_${new Date().toISOString().split('T')[0]}.csv`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
    }


</script>
</body>
</html>