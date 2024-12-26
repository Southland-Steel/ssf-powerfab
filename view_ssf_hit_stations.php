<?php
$userTimezone = 'America/Chicago'; // Replace with the actual user's timezone
date_default_timezone_set($userTimezone);

// Get the current timestamp in the user's timezone
$lastUpdated = date("Y-m-d H:i:s");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Production Control Monitor</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 5px;
            line-height: 1.6;
        }
        .container{
            min-width: 100%;
            position: relative;
        }
        .main-content {
            padding:20px;
            width: 100%;
            box-sizing: border-box;
        }
        .monitor-header, .monitor-guide {
            width: 100%;
            box-sizing: border-box;
            margin-bottom: 20px;
            padding: 5px;
            background-color: #f0f0f0;
            border-radius: 5px;
        }
        .monitor-header h1 {
            color: #333;
            margin-bottom: 10px;
        }
        .monitor-description {
            font-size: 1.1em;
            color: #555;
        }
        .datepicker-container {
            margin-bottom: 20px;
            text-align: right;
        }
        #datepicker {
            padding: 5px;
            font-size: 16px;
        }
        table {
            width: 100%;
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
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .station-cell {
            text-align: center;
            position: relative;
            border: 1px solid transparent;
            transition: border-color 0.3s ease;
        }
        .station-status {
            font-weight: bold;
            cursor: pointer;
        }
        .station-quantity {
            font-size: 0.8em;
        }
        .center {
            text-align: center;
        }

        .status-complete, .Complete {
            background-color: #d4edda;
            color: #155724;
        }

        .status-inprogress, .Partial {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-notstarted, .Not-Started {
            background-color: #d1ecf1;
            color: #0c5460;
        }


        .NA {
            background-color: #e9ecef;
        }
        .station-cell.station-hit::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            border-style: solid;
            border-width: 0 12px 12px 0;
            border-color: transparent #ff0000 transparent transparent;
        }
        .secondlook {
            border: 2px solid rgba(255, 69, 0, 0.6);
            box-shadow: 0 0 5px rgba(255, 69, 0, 0.5);
            font-weight: bold;
            color: #000;
            position: relative;
            overflow: hidden;
        }
        .highlightlook {
            background-color: #ffff00;
            border: 2px solid #ff4500;
            box-shadow: 0 0 5px rgba(255, 69, 0, 0.5);
            font-weight: bold;
            font-size: 10px;
            color: #000;
            position: relative;
            overflow: hidden;
        }
        .monitor-guide h2 {
            color: #333;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .monitor-guide ul {
            padding-left: 20px;
        }
        .monitor-guide li {
            margin-bottom: 10px;
        }
        .inspection-boxes {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
        }
        .inspection-box {
            width: 48%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-align: center;
        }
        .inspection-box p {
            margin: 0;
        }
        .inspection-box .count {
            font-size: 1.2em;
            font-weight: bold;
        }
        .passed-box.active {
            background-color: #d4edda;
            color: #155724;
        }
        .failed-box.active {
            background-color: #f8d7da;
            color: #721c24;
        }
        .fix-now-text {
            text-align: center;
            font-size: 14px;
            display: block;
            padding: 0 5px;
            margin: 0 5px;
            font-weight: bold;
            color: red; /* Optional for styling */
        }
        #filterButton {
            margin-bottom: 10px;
            padding: 5px 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        #filterButton:hover {
            background-color: #0056b3;
        }

        @media print {
            .monitor-guide,
            #filterButton,
            .noprint {
                display: none;
            }

            #productionControlTable {
                width: 100%;
            }

            body {
                font-size: 10pt;
            }

            .monitor-header {
                background-color: white;
                color: black;
            }
        }
        .last-updated {
            position: absolute;
            top: 10px;
            right: 320px;
            font-size: 0.9em;
            color: #666;
            background-color: rgba(255, 255, 255, 0.8); /* Semi-transparent background */
            padding: 5px 10px;
            border-radius: 5px;
            z-index: 1000; /* Ensure it's above other elements */
        }
        @media print {
            .last-updated {
                position: static;
                text-align: right;
                margin-bottom: 10px;
            }
        }

    </style>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<div class="container">

    <header class="monitor-header">
        <h1>Production Control Monitor</h1>
        <p class="monitor-description">This system tracks station activity across production lines, providing real-time insights into job progress and potential issues.</p>
        <p class="last-updated">Last Updated: <?php echo $lastUpdated; ?></p>
    </header>

    <div class="datepicker-container">
        <label for="datepicker">Select Date: </label>
        <input type="date" id="datepicker">
    </div>

    <button id="filterButton">Show Only Rows that need to be Fixed</button>
    <table id="productionControlTable">
        <thead>
        <!-- Table header will be dynamically populated -->
        </thead>
        <tbody>
        <!-- Table body will be dynamically populated -->
        </tbody>
    </table>

    <br>
    <hr>
    <br>
    <h1 class="noprint">Instructions</h1>
    <section class="monitor-guide">
        <h2>How is this data derived?</h2>
        <p>The system pulls production data for any items that passed through the Fitting (29), Welding (91), or Final Quality Control (92) stations on a specific date, showing you a complete view of work completed at these key manufacturing checkpoints.</p>
        <h2>Key Features and Functionality</h2>
        <ul>
            <li><strong>Daily Activity Tracking:</strong> The monitor creates a row for each Job/MainMark with any activity on the selected date.
                <ul>
                    <li>Provides instant visibility of active Job/MainMarks</li>
                    <li>Enables real-time progress monitoring and issue detection</li>
                    <li>Facilitates efficient resource allocation and scheduling optimization</li>
                    <li>Supports performance analysis and trend identification over time</li>
                </ul>
            </li>
            <li><strong>Date Selection:</strong> Users can choose specific dates to view historical data.</li>
            <li><strong>Comprehensive Job Information:</strong> Each row displays Route, Number, Job Description, Sequence, Lot, and Main Mark.</li>
            <li><strong>Station Status Tracking:</strong> For each station associated with a job:
                <ul>
                    <li><span class="status complete">Complete</span>: All instances recorded for the piece at this station.</li>
                    <li><span class="status inprogress">In-Progress</span>: Started but not complete.</li>
                    <li><span class="status notstarted">Not-Started</span>: Not started.</li>
                    <li><span class="status na">N/A</span>: Station not part of the selected route for this Job/MainMark.</li>
                </ul>
            </li>
            <li><strong>Visual Indicators:</strong>
                <ul>
                    <li><span class="indicator hit-indicator"></span> Red corner clip: Station had a "hit" (activity) on the selected day.</li>
                    <li><span class="indicator sequence-issue"></span> Yellow highlight with red border: More completions than the previous station, indicating a possible sequence issue.</li>
                </ul>
            </li>
        </ul>
    </section>

    <section class="monitor-guide">
        <h2>Using the Inspection Data Modal</h2>
        <p>The Inspection Data Modal provides detailed information about inspections performed at each station. To access and use this feature:</p>
        <ul>
            <li><strong>Opening the Modal:</strong> Click on any station's status (Complete or In-Progress) to view inspection details.</li>
            <li><strong>Modal Content:</strong> The modal displays a table with the following information:
                <ul>
                    <li>Inspection ID: Unique identifier for each inspection</li>
                    <li>Inspector: Name of the person who performed the inspection</li>
                    <li>Time: Date and time when the inspection was conducted</li>
                    <li>Quantity: Number of items inspected</li>
                    <li>Test Result: Outcome of the inspection (Pass/Fail)</li>
                    <li>Description: Additional details or notes about the inspection</li>
                </ul>
            </li>
            <li><strong>Data Organization:</strong> Inspection records are sorted by date and time, with the most recent inspections appearing first.</li>
            <li><strong>Using Inspection Data:</strong>
                <ul>
                    <li>Identify potential quality issues by reviewing failed inspections</li>
                    <li>Track inspector performance and workload distribution</li>
                    <li>Analyze inspection frequency and timing to optimize quality control processes</li>
                    <li>Correlate inspection data with production issues for root cause analysis</li>
                </ul>
            </li>
        </ul>
        <p>By leveraging the Inspection Data Modal in conjunction with the main monitor view, users can gain deep insights into both production progress and quality control measures, enabling data-driven decision-making and continuous process improvement.</p>
    </section>
</div>

<!-- Modal -->
<div class="modal fade" id="inspectionModal" tabindex="-1" aria-labelledby="inspectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="inspectionModalLabel">Inspection Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Inspection ID</th>
                        <th>Inspector</th>
                        <th>Time</th>
                        <th>Quantity</th>
                        <th>Test Result</th>
                        <th>Description</th>
                    </tr>
                    </thead>
                    <tbody id="inspectionDetails">
                    <!-- Inspection details will be populated here -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script src="js/IsDev.js?v=<?php echo time(); ?>"></script>
<script>
    $(document).ready(function() {
        var today = new Date().toISOString().split('T')[0];
        $('#datepicker').val(today);

        function fetchData(selectedDate) {
            $.ajax({
                url: 'ajax_ssf_assembly_hits_with_stations.php',
                method: 'GET',
                dataType: 'json',
                data: { date: selectedDate },
                success: function(response) {
                    createTableHeader(response.stations);
                    populateTable(response.data, response.stations);
                    resetFilterState(); // Reset filter state after populating the table
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching data:", error);
                }
            });
        }

        function createTableHeader(stations) {
            var $thead = $('#productionControlTable thead');
            $thead.empty();
            var $headerRow = $('<tr>');
            $headerRow.append(
                $('<th>').text('Route'),
                $('<th>').text('Number'),
                $('<th>').text('Job'),
                $('<th>').text('Sequence'),
                $('<th>').text('Lot'),
                $('<th>').text('Main Mark')
            );
            stations.forEach(function(station) {
                $headerRow.append($('<th>').addClass('center').text(station));
            });
            $thead.append($headerRow);
        }

        function populateTable(data, stations) {
            var $tbody = $('#productionControlTable tbody');
            $tbody.empty();

            data.forEach(function(item) {
                var $row = $('<tr>');
                $row.append(
                    $('<td>').text(item.Route),
                    $('<td>').text(item.JobNumber),
                    $('<td>').text(item.JobDescription),
                    $('<td>').text(item.SequenceDescription),
                    $('<td>').text(item.LotNumber),
                    $('<td>').text(item.MainMark)
                );

                var lastCompletedQuantity = 0;
                var lastNonNAStationIndex = -1;

                stations.forEach(function(stationName, index) {
                    var station = item.Stations.find(s => s.StationName === stationName);
                    var $cell = $('<td>').addClass('station-cell');

                    if (station) {
                        var status = getStatus(station);
                        var $statusSpan = $('<span>')
                            .addClass('station-status')
                            .attr('data-stationname', station.StationName)
                            .attr('data-summaryid', station.ProductionControlItemStationSummaryID)
                            .attr('data-jobnumber', item.JobNumber)
                            .attr('data-mainmark', item.MainMark)
                            .text((index > 0 && lastNonNAStationIndex !== -1 && station.QuantityCompleted > lastCompletedQuantity) ? 'Over Qty' : status)
                            .attr('title', station.ProductionControlItemStationSummaryID)
                            .css('cursor', 'pointer');
                        var $quantitySpan = $('<span>').addClass('station-quantity')
                            .text(' (' + station.QuantityCompleted + '/' + station.TotalQuantity + ')');
                        $cell.append($statusSpan, $quantitySpan).addClass(getStatusClass(status));

                        // Check for "second look" condition
                        if (index > 0 && lastNonNAStationIndex !== -1 &&
                            station.QuantityCompleted > lastCompletedQuantity) {
                            $cell.addClass('secondlook');

                            // Highlight the previous cell
                            var $prevCell = $row.children().eq($row.children().length - 1); // Get the last added cell
                            $prevCell.addClass('highlightlook');

                            // Append "Fix Now" to the previous cell's status
                            var $prevStatusSpan = $prevCell.find('.station-status');

                            // Append "Fix Now" with custom styling
                            $prevStatusSpan.prepend('<div class="fix-now-text">Fix Now</div>');

                        }

                        if (station.IsHit == 1) {
                            $cell.addClass('station-hit');
                        }

                        lastCompletedQuantity = station.QuantityCompleted;
                        lastNonNAStationIndex = index;
                    } else {
                        $cell.text('N/A');
                        // Don't update lastCompletedQuantity for N/A stations
                    }
                    $row.append($cell);
                });

                $tbody.append($row);
            });
        }

        function getStatus(station) {
            if (station.QuantityCompleted === station.TotalQuantity) {
                return 'Complete';
            } else if (station.QuantityCompleted > 0) {
                return 'In-Progress';
            } else {
                return 'Not-Started';
            }
        }

        function getStatusClass(status) {
            switch (status) {
                case 'Complete':
                    return 'status-complete';
                case 'In-Progress':
                    return 'status-inprogress';
                case 'Not-Started':
                    return 'status-notstarted';
                default:
                    return '';
            }
        }

        fetchData(today);

        $('#datepicker').on('change', function() {
            var selectedDate = $(this).val();
            fetchData(selectedDate);
        });


        $(document).on('click', '.station-status', function() {
            var stationName = $(this).data('stationname');
            var summaryId = $(this).data('summaryid');
            var jobNumber = $(this).data('jobnumber');
            var mainMark = $(this).data('mainmark');

            // Make an AJAX call to fetch the actual data
            $.ajax({
                url: 'ajax_ssf_station_summary_inspections.php',
                method: 'GET',
                data: { summaryId: summaryId },
                dataType: 'json',
                success: function(data) {
                    var detailsHtml = '';
                    data.forEach(function(item) {
                        detailsHtml += `
                <tr>
                    <td>${item.InspectionTestRecordID}</td>
                    <td>${item.Name}</td>
                    <td>${item.LastUpdatedTime}</td>
                    <td>${item.Quantity}</td>
                    <td>${item.TestResult}</td>
                    <td>${item.String}</td>
                </tr>
            `;
                    });

                    $('#inspectionDetails').html(detailsHtml);
                    $('#inspectionModalLabel').text('Inspection Details for ' + jobNumber + ' - ' + mainMark + ' - ' + stationName);
                    $('#inspectionModal').modal('show'); // This line shows the modal
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('AJAX error:', textStatus, errorThrown);
                    $('#inspectionDetails').html('<tr><td colspan="6">Error loading inspection details.</td></tr>');
                    $('#inspectionModal').modal('show'); // Show modal even on error
                }
            });
        });

        function filterSecondLookRows() {
            var $rows = $('#productionControlTable tbody tr');
            $rows.each(function() {
                var $row = $(this);
                if ($row.find('.secondlook').length > 0) {
                    $row.show();
                } else {
                    $row.hide();
                }
            });
        }

        function resetFilter() {
            $('#productionControlTable tbody tr').show();
        }

        function resetFilterState() {
            isFiltered = false;
            resetFilter();
            updateFilterButtonText();
        }

        function updateFilterButtonText() {
            $('#filterButton').text(isFiltered ? 'Show All Rows' : 'Show Only Rows that need to be Fixed');
        }

        $('#filterButton').on('click', function() {
            isFiltered = !isFiltered;
            if (isFiltered) {
                filterSecondLookRows();
            } else {
                resetFilter();
            }
            updateFilterButtonText();
        });

        function setupAutoRefresh() {
            const fifteenMinutes = 5 * 60 * 1000; // 15 minutes in milliseconds
            setTimeout(function() {
                location.reload();
            }, fifteenMinutes);
        }

        // Call the auto-refresh function when the page loads
        setupAutoRefresh();

    });
</script>
</body>
</html>