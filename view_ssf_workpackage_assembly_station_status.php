<?php
// view_grid_workpackage_assembly_station_status.php

$currentYear = date('y');
$currentWeek = date('W');
$currentWorkweek = intval($currentYear . str_pad($currentWeek, 2, '0', STR_PAD_LEFT));

$workweek = $_GET['workweek'] ?? $currentWorkweek;

require_once 'medoo_ssf_db.php';

$resources = $tkdb->query("
    SELECT DISTINCT Group2 as WorkWeeks FROM workpackages 
    INNER JOIN productioncontroljobs as pcj ON pcj.productionControlID = workpackages.productionControlID 
    WHERE Completed = 0 AND OnHold = 0 
    ORDER BY WorkWeeks ASC;
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
    <title>Production Scheduler</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #0d6efd;
            --light-blue: #e7f1ff;
        }

        body {
            background-color: #f8f9fa;
            font-family: system-ui, -apple-system, sans-serif;
        }

        .sticky-header {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: white;
            border-bottom: 1px solid #dee2e6;
            padding: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .week-selector {
            overflow-x: auto;
            white-space: nowrap;
            padding: 1rem 0;
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        .week-selector::-webkit-scrollbar {
            display: none;
        }

        .week-btn {
            min-width: 80px;
            margin: 0 4px;
            transition: all 0.2s;
        }

        .week-btn.active {
            background-color: var(--primary-blue);
            color: white;
            transform: scale(1.05);
        }

        .stats-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            height: 100%;
            transition: all 0.3s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .progress {
            height: 8px;
            border-radius: 4px;
            margin: 0.5rem 0;
        }

        .station-timeline {
            position: relative;
            padding: 1rem;
            border-radius: 8px;
            background: var(--light-blue);
            margin-bottom: 1rem;
        }

        .station-name {
            font-weight: 600;
            color: var(--primary-blue);
            padding: 0.25rem 0;
        }

        .table-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-top: 1rem;
            overflow-x: auto;
        }

        .status-complete {
            background-color: #d4edda !important;
            color: #155724;
        }

        .status-notstarted {
            background-color: #fff3cd !important;
            color: #856404;
        }

        .status-partial {
            background-color: #cce5ff !important;
            color: #004085;
        }

        .fixed-week-display {
            position: fixed;
            top: 1rem;
            right: 1rem;
            background: white;
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            font-size: 2rem;
            font-weight: bold;
            color: #6c757d;
            z-index: 1030;
        }

        .filter-section {
            margin: 1rem 0;
            padding: 0.5rem 0;
            border-top: 1px solid #dee2e6;
        }

        .filter-btn {
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
            transition: all 0.2s;
        }

        .filter-btn:hover {
            transform: translateY(-1px);
        }

        #projectTable {
            margin: 0;
        }

        #projectTable thead th {
            position: sticky;
            top: 0;
            background: #f8f9fa;
            z-index: 1000;
            border-bottom: 2px solid #dee2e6;
        }

        .logo-container {
            text-align: left;
            padding: .2rem 0;
        }

        .logo-container img {
            max-height: 40px;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
</head>
<body>
<div class="container-fluid">
    <div class="sticky-header">
        <div class="row align-items-center">
            <div class="col">
                <div class="logo-container">
                    <img src="images/ssf-horiz.png" alt="Southland Steel Fabricators" class="img-fluid">
                </div>
            </div>
            <div class="col text-end">
                <button class="btn btn-outline-secondary btn-sm" id="toggleFilters">
                    Additional Filters
                </button>
            </div>
        </div>

        <div class="week-selector">
            <div class="d-flex" id="activeFabWorkpackages">
                <!-- Week buttons dynamically inserted here -->
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-4">
                <div class="stats-card">
                    <h6 class="text-muted">Hours</h6>
                    <div id="hoursSummary">
                        <!-- Hours summary dynamically inserted -->
                    </div>
                    <div class="progress">
                        <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <h6 class="text-muted">Weight</h6>
                    <div id="weightSummary">
                        <!-- Weight summary dynamically inserted -->
                    </div>
                    <div class="progress">
                        <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <h6 class="text-muted">Station Schedule</h6>
                    <div class="station-timeline">
                        <div class="station-name">CNC → Week <?= $workweek + 4 ?></div>
                        <div class="station-name">Cut → Week <?= $workweek + 3 ?></div>
                        <div class="station-name">Kit & Press → Week <?= $workweek + 2 ?></div>
                        <div class="station-name">Seam Welding → Week <?= $workweek + 1 ?></div>
                        <div class="station-name">Fit/Weld/QC → Week <?= $workweek ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div id="additionalFilters" style="display: none;">
            <div class="filter-section" id="bayFilter">
                <!-- Bay filter buttons -->
            </div>
            <div class="filter-section" id="wpFilter">
                <!-- Work Package filter buttons -->
            </div>
            <div class="filter-section" id="routeFilter">
                <!-- Route filter buttons -->
            </div>
        </div>
    </div>

    <div class="table-container">
        <table id="projectTable" class="table table-hover">
            <thead>
            <!-- Table header dynamically inserted -->
            </thead>
            <tbody>
            <!-- Table body dynamically inserted -->
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
                    <tr>
                        <th>Piecemark</th>
                        <th>Job Quantity</th>
                        <th>Assembly Each Quantity</th>
                        <th>WP Quantity</th>
                        <th>Completed Quantity</th>
                    </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    const orderedStations = ['CNC', 'Cut', 'Kit-Up', 'Traps Cut', 'Press Break', 'Seam Welder', 'Fit', 'Weld', 'Final QC'];
    var currentRouteFilter = 'all'; // Global variable for the selected route filter
    var currentWPFilter = 'all'; // Global variable for the selected work package filter
    var projectData = []; // Global variable to hold the loaded data

    $(document).ready(function() {
        loadProjectData(<?= $workweek; ?>);

        let weeks = <?= json_encode($weeks); ?>;
        let weeklist = [];

        // Loop through the weeks and create button elements
        weeks.forEach(week => {
            weeklist.push(`
        <button class="btn btn-outline-primary week-btn" onclick="selectWeek('${week}')">
            ${week}
        </button>`);
        });

        // Insert the buttons into the container
        $('#activeFabWorkpackages').html(`${weeklist.join(' ')}`);

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

    function selectWeek(week){
        $('.week-btn').removeClass('active');
        $(`.week-btn:contains(${week})`).addClass('active');
        $('.current-week').text(week);
        loadProjectData(week);
    }

    function loadProjectData(workweek) {
        $.when(
            $.ajax({
                url: 'ajax_ssf_workpackage_assembly_station_status.php',
                method: 'GET',
                dataType: 'json',
                data: {workweek: workweek}
            }),
            $.ajax({
                url: 'ajax_ssf_week_station_sequence_piece_tracking.php',
                method: 'GET',
                dataType: 'json',
                data: {workweek: workweek}
            })
        ).done(function(response1, response2) {
            if (response1[0].error) {
                alert(response1[0].error);
                return;
            }

            projectData = Array.isArray(response1[0]) ? response1[0] : [response1[0]];
            const additionalData = response2[0];

            // Merge the additional data with projectData
            mergeAdditionalData(projectData, additionalData);

            createWPFilter();
            createTableHeader();
            createRouteFilter();
            createBayFilter();
            currentRouteFilter = 'all';
            currentWPFilter = 'all';
            currentBayFilter = 'all';
            filterData();
            document.getElementById('projectData').style.display = 'block';

            $('#jobTitle').text(`Workweek: ${workweek}`);
            $('#big-text').text(`${workweek}`);
        }).fail(function(xhr, status, error) {
            console.error("Error fetching data:", error);
            alert("Error loading project data. Please try again.");
        });
    }

    function mergeAdditionalData(projectData, additionalData) {
        additionalData.forEach(additionalItem => {
            const matchingItem = projectData.find(item =>
                item.ProductionControlItemSequenceID === additionalItem.ProductionControlItemSequenceID
            );

            if (matchingItem) {
                // Add or update CNC, Traps, Cut, and Kit-Up stations to the existing item
                additionalItem.Stations.forEach(station => {
                    if (['CNC', 'Traps Cut', 'Cut', 'Kit-Up'].includes(station.StationName)) {
                        const existingStationIndex = matchingItem.Stations.findIndex(s => s.StationDescription === station.StationName);

                        if (existingStationIndex !== -1) {
                            // Update existing station
                            matchingItem.Stations[existingStationIndex] = {
                                ...matchingItem.Stations[existingStationIndex],
                                StationQuantityCompleted: station.CompletedAssemblies,
                                StationTotalQuantity: additionalItem.AssemblySequenceQuantity,
                                PieceMarks: station.PieceMarks
                            };
                        } else {
                            // Add new station
                            matchingItem.Stations.push({
                                StationDescription: station.StationName,
                                StationQuantityCompleted: station.CompletedAssemblies,
                                StationTotalQuantity: additionalItem.AssemblySequenceQuantity,
                                PieceMarks: station.PieceMarks
                            });
                        }
                    }
                });
            } else {
                console.warn(`No matching item found for ProductionControlItemSequenceID: ${additionalItem.ProductionControlItemSequenceID}`);
            }
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
            <td>${assembly.JobNumber}<br>${assembly.RouteName}</td>
            <td title="SequenceID:${assembly.SequenceID}">${assembly.SequenceDescription}-${assembly.LotNumber}<br>${assembly.MainMark}</td>
            <td title="ProductionControlItemSequenceID:${assembly.ProductionControlItemSequenceID}">${assembly.WorkPackageNumber}</td>
            <td>${assembly.SequenceQuantity}</td>
            <td>${formatNumberWithCommas(assembly.NetAssemblyWeightEach)}# / ${formatNumberWithCommas(assembly.TotalNetWeight)}#</td>
            <td>${formatNumber(assembly.AssemblyManHoursEach)} / ${formatNumber(assembly.TotalEstimatedManHours)}</td>
        `;

            totalJobHours += parseFloat(assembly.TotalEstimatedManHours);

            orderedStations.forEach(stationName => {
                const station = assembly.Stations.find(s => s.StationDescription === stationName);
                if (station && (stationName !== 'Traps Cut' || ['Shaft', 'Shaft HW'].includes(assembly.RouteName))) {
                    const statusClass = getStatusClass(station.StationQuantityCompleted, station.StationTotalQuantity);
                    const stationTotalHours = stationHours[stationName] || 0;
                    const stationUsedHours = (station.StationQuantityCompleted / station.StationTotalQuantity) * stationTotalHours;
                    totalUsedHours += stationUsedHours;

                    let cellContent = '';

                    if (['CNC', 'Kit-Up'].includes(stationName)) {
                        cellContent = `${station.StationQuantityCompleted} / ${station.StationTotalQuantity}`;
                    } else {
                        cellContent = `
                        ${station.StationQuantityCompleted} / ${station.StationTotalQuantity}<br>
                        HRS: ${formatNumber(stationUsedHours)} / ${formatNumber(stationTotalHours)}
                    `;
                    }

                    if (['CNC', 'Traps Cut', 'Cut', 'Kit-Up'].includes(stationName)) {
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
            station.StationDescription === "Final QC"
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


    function calculateTotalWeight(data) {
        return data.reduce((sum, assembly) => sum + parseFloat(assembly.TotalNetWeight || 0), 0);
    }

    function calculateTotalHours(data) {
        return data.reduce((sum, assembly) => sum + parseFloat(assembly.TotalEstimatedManHours || 0), 0);
    }

    function formatNumber(value) {
        return parseFloat(value).toFixed(2);
    }

    function formatNumberWithCommas(number) {
        return number.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
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
        switch(route) {
            case 'Shaft':
                return {
                    'Traps Cut' : totalHours * 0.07,
                    'CNC' : totalHours * 0.001,
                    'Kit-Up' : totalHours * 0.001,
                    'Cut': totalHours * 0.088,
                    'Press Break': totalHours * 0.06,
                    'Seam Welder': totalHours * 0.07,
                    'Fit': totalHours * 0.25,
                    'Weld': totalHours * 0.41,
                    'Final QC': totalHours * 0.05
                };
            case 'Shaft HW':
                return {
                    'Traps Cut': totalHours * 0.07,
                    'Cut': totalHours * 0.088,
                    'CNC' : totalHours * 0.001,
                    'Kit-Up' : totalHours * 0.001,
                    'Press Break': totalHours * 0.06,
                    'Seam Welder': 0,
                    'Fit': totalHours * 0.23,
                    'Weld': totalHours * 0.50,
                    'Final QC': totalHours * 0.05
                };
            case 'Structural':
                return {
                    'Cut': totalHours * 0.078,
                    'Press Break': 0,
                    'Seam Welder': 0,
                    'CNC' : totalHours * 0.001,
                    'Kit-Up' : totalHours * 0.001,
                    'Fit': totalHours * 0.25,
                    'Weld': totalHours * 0.62,
                    'Final QC': totalHours * 0.05
                };
            case 'Ship Loose':
                return {
                    'Cut': totalHours * 0.948,
                    'CNC' : totalHours * 0.001,
                    'Kit-Up' : totalHours * 0.001,
                    'Press Break': 0,
                    'Seam Welder': 0,
                    'Fit': 0,
                    'Weld': 0,
                    'Final QC': totalHours * 0.05
                };
            default:
                return {
                    'Press Break': 0,
                    'Seam Welder': 0,
                    'Fit': 0,
                    'Weld': 0,
                    'Final QC': 0
                };
        }
    }


</script>
</body>
</html>