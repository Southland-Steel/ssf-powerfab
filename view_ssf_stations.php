<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Production Status</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bulma/0.9.3/css/bulma.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
        }
        .section {
            padding: 2rem 1.5rem;
        }
        .container {
            max-width: 1200px;
        }
        .title {
            color: #363636;
            font-size: 2.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }
        .field.is-grouped-right {
            justify-content: flex-end;
            align-items: center;
            margin-bottom: 1rem;
        }
        .control.has-icons-right .input {
            padding-right: 2.5em;
        }
        .control.has-icons-right .icon.is-right {
            right: 0.5em;
            pointer-events: none;
        }
        .buttons {
            margin-bottom: 1rem;
        }
        .button {
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }
        .table-container {
            background-color: white;
            border-radius: 6px;
            box-shadow: 0 2px 3px rgba(10, 10, 10, 0.1);
            overflow-x: auto;
        }
        .table {
            font-size: 0.9rem;
        }
        .table th {
            background-color: #f2f2f2;
            color: #363636;
            font-weight: 600;
        }
        .table td, .table th {
            padding: 0.5em 0.75em;
            vertical-align: middle;
        }
        .tight-rows tr, .tight-rows td {
            padding: 0.3rem 0.5rem;
        }
        tr:hover {
            background-color: #f0f8ff !important;
        }
        .clicked {
            background-color: #e6ffe6 !important;
        }
        #total-row {
            font-weight: bold;
            background-color: #f2f2f2;
        }
        .page-instructions {
            background-color: white;
            border-radius: 6px;
            box-shadow: 0 2px 3px rgba(10, 10, 10, 0.1);
            padding: 1.5rem;
            margin-top: 2rem;
        }
        .page-instructions h2 {
            color: #363636;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        .page-instructions h3 {
            color: #4a4a4a;
            font-size: 1.25rem;
            font-weight: 600;
            margin-top: 1rem;
            margin-bottom: 0.5rem;
        }
        .page-instructions ul {
            list-style-type: disc;
            margin-left: 1.5rem;
        }
    </style>
</head>
<body>
<section class="section">
    <div class="container">
        <h1 class="title">Daily Production Information</h1>
        <div class="field is-grouped is-grouped-right">
            <label class="label">Select Date</label>
            <div class="control has-icons-right">
                <input type="text" id="date-picker" class="input is-small" style="width: 150px;">
                <span class="icon is-small is-right"><i class="fas fa-calendar-alt"></i></span>
            </div>
        </div>
        <div class="buttons">
            <button id="refresh-button" class="button is-primary">Refresh for Today's Data</button>
        </div>
        <div class="buttons" id="filter-buttons">
            <!-- Filter buttons will be added here dynamically -->
        </div>
        <div class="buttons" id="job-buttons">
            <!-- Filter buttons for job numbers will be added here dynamically -->
        </div>
        <div id="table-container" class="table-container">
            <table class="table is-striped is-hoverable is-fullwidth">
                <thead>
                <tr>
                    <th>User</th>
                    <th>Route</th>
                    <th>Station Name</th>
                    <th>Main Mark</th>
                    <th>Instance Qty</th>
                    <th>Job Number</th>
                    <th>Job Description</th>
                    <th>Assembly Hrs Each</th>
                    <th>Station Hours</th>
                    <th>Percent</th>
                </tr>
                </thead>
                <tbody id="table-body" class="tight-rows">
                <tr id="sample-row">
                    <td class="user-id"></td>
                    <td class="route"></td>
                    <td class="station-name"></td>
                    <td class="main-mark"></td>
                    <td class="quantity"></td>
                    <td><a class="job-number"></a></td>
                    <td class="job-description"></td>
                    <td class="assemblyeach"></td>
                    <td class="hours"></td>
                    <td class="percent"></td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="page-instructions">
            <h2>How to Use This Production Information Page</h2>

            <h3>1. Date Selection</h3>
            <p>At the top right of the page, you'll find a date picker labeled "Select Date". Use this to choose the specific date for which you want to view production data. This allows you to review historical data or focus on today's production.</p>

            <h3>2. Refresh Data</h3>
            <p>The "Refresh for Today's Data" button pulls the most up-to-date information from our live database. Click this button to ensure you're viewing the latest production data, especially useful when monitoring today's activities.</p>

            <h3>3. Station Filters</h3>
            <p>Below the refresh button, you'll see a row of blue buttons representing different stations (e.g., "All Stations", "Final QC", "Fit", etc.). Click on these to filter the data and show only the activities at specific stations.</p>

            <h3>4. Job Filters</h3>
            <p>The next row of blue buttons allows you to filter by job numbers. Use these to focus on specific jobs or view "All Jobs" at once.</p>

            <h3>5. Data Table</h3>
            <p>The main table displays detailed information about each production activity, including:</p>
            <ul>
                <li>User: The employee responsible for the activity</li>
                <li>Station Name: Where the activity took place</li>
                <li>Main Mark: Identifier for the specific part or component</li>
                <li>Quantity: Number of items processed</li>
                <li>Dimension and Length: Physical specifications of the item</li>
                <li>Job Number and Description: Details about the overall job</li>
                <li>Pounds: Weight of the processed items</li>
            </ul>

            <p>Use the filters and date selection to drill down into the data you need, and refresh as necessary to stay updated on the latest production information.</p>
        </div>
    </div>
</section>

<script>
    $(document).ready(function() {
        $('#date-picker').datepicker({
            dateFormat: 'yy-mm-dd'
        }).datepicker('setDate', new Date());

        // Clone and remove the sample row
        var sampleRow = $('#sample-row').clone();
        $('#sample-row').remove();

        function fetchProductionData() {
            var selectedDate = $('#date-picker').val();
            $.ajax({
                url: 'ajax_ssf_stationitems.php', // Replace with your API endpoint
                method: 'GET',
                dataType: 'json',
                data: { date: selectedDate }, // Pass the selected date
                success: function(data) {
                    var tableBody = $('#table-body');
                    tableBody.empty(); // Clear existing data
                    data.forEach(function(item) {

                        var newRow = sampleRow.clone(); // Clone the sample row for each item
                        newRow.find('.user-id').text(item.Username);
                        newRow.find('.main-mark').text(item.AssemblyMainMark);
                        newRow.find('.route').text(item.Route);
                        newRow.find('.station-name').text(item.StationName).attr('title', `StationID: ${item.StationID}, Station Number: ${item.StationNumber}`); // Add tooltip;
                        newRow.find('.quantity').text(item.StationDaySequenceQuantityInstance);
                        newRow.find('.job-number').text(item.JobNumber)
                            .attr('title', `ProductionControlID: ${item.ProductionControlID}`)
                            .attr('href', 'view_productioncontrolitems.php?ProductionControlID=' + item.ProductionControlID);
                        newRow.find('.job-description').text(item.JobDescription);
                        newRow.find('.assemblyeach').text(item.AssemblyManHoursEach);
                        newRow.find('.hours').text(item.CalculatedHours);
                        newRow.find('.percent').text(`${item.StationPercentage}%`);
                        tableBody.append(newRow); // Append the new row to the table body
                    });
                    populateFilterButtons(data);
                    populateJobButtons(data);

                    // Calculate and display the total pounds
                    calculateAndDisplayTotalHours();

                    // Sum up the visible pounds values
                    var totalPounds = 0;
                    $('#table-body tr:visible').each(function() {
                        totalPounds += parseFloat($(this).find('.hours').text()) || 0;
                    });

                },
                error: function(error) {
                    console.error('Error fetching production data:', error);
                }
            });
        }
        function calculateAndDisplayTotalHours() {
            var totalHours = 0;

            $('#table-body tr:visible').each(function() {
                totalHours += parseFloat($(this).find('.hours').text()) || 0;
            });

            // Round to the nearest integer
            totalHours = Math.round(totalHours*100)/100;

            // Format with commas
            var formattedTotalHours = totalHours.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");

            // Remove any existing total row
            $('#total-row').remove();

            // Create a new row for the total
            var totalRow = $('<tr id="total-row"><td colspan="8" style="text-align: right; font-weight: bold;">Total Hours</td><td class="total-hours">' + formattedTotalHours + '</td><td>&nbsp;</td></tr>');
            $('#table-body').append(totalRow);
        }


        // Function to populate filter buttons
        function populateFilterButtons(data) {
            var stationCounts = data.reduce((acc, item) => {
                acc[item.StationName] = (acc[item.StationName] || 0) + item.StationDaySequenceQuantityInstance;
                return acc;
            }, {});

            var stationNames = Object.keys(stationCounts);
            stationNames.sort(); // Sort the station names

            var totalStationCount = data.reduce((acc, item) => {
                return acc + item.StationDaySequenceQuantityInstance;
            }, 0); // Calculate total count for all stations

            var filterButtons = $('#filter-buttons');
            filterButtons.empty(); // Clear existing buttons

            // Add "All Stations" button with total count
            var allButton = $(`<button class="button is-info">All Stations (${totalStationCount})</button>`);
            allButton.click(function() {
                $('#table-body tr').show();
                calculateAndDisplayTotalHours(); // Recalculate total pounds after filtering
            });
            filterButtons.append(allButton);

            // Add a button for each station name with count
            stationNames.forEach(function(stationName) {
                var button = $(`<button class="button is-info">${stationName} (${stationCounts[stationName]})</button>`);
                button.click(function() {
                    $('#table-body tr').each(function() {
                        if ($(this).find('.station-name').text() === stationName) {
                            $(this).show();
                        } else {
                            $(this).hide();
                        }
                        calculateAndDisplayTotalHours(); // Recalculate total pounds after filtering
                    });

                });
                filterButtons.append(button);
            });
        }

        // Function to populate filter buttons for job numbers
        function populateJobButtons(data) {
            var jobCounts = data.reduce((acc, item) => {
                acc[item.JobNumber] = (acc[item.JobNumber] || 0) + item.StationDaySequenceQuantityInstance;
                return acc;
            }, {});

            var jobNumbers = Object.keys(jobCounts);
            jobNumbers.sort(); // Sort the job numbers

            var jobButtons = $('#job-buttons');
            jobButtons.empty(); // Clear existing buttons

            // Add "All Jobs" button with total count
            var allButton = $(`<button class="button is-info">All Jobs</button>`);
            allButton.click(function() {
                $('#table-body tr').show();
                calculateAndDisplayTotalHours(); // Recalculate total pounds after filtering
            });
            jobButtons.append(allButton);

            // Add a button for each job number with count
            jobNumbers.forEach(function(jobNumber) {
                var button = $(`<button class="button is-info">${jobNumber} (${jobCounts[jobNumber]})</button>`);
                button.click(function() {
                    $('#table-body tr').each(function() {
                        if ($(this).find('.job-number').text() === jobNumber) {
                            $(this).show();
                        } else {
                            $(this).hide();
                        }
                        calculateAndDisplayTotalHours(); // Recalculate total pounds after filtering
                    });
                });
                jobButtons.append(button);

            });
        }

        // Fetch data on page load
        fetchProductionData();

        function isToday(date) {
            const today = new Date().toISOString().split('T')[0];
            return date === today;
        }

        // Fetch data on refresh button click
        $('#refresh-button').click(function() {
            fetchProductionData();
            var selectedDate = $('#date-picker').val();
            if (isToday(selectedDate)) {
                $('#refresh-button').show();
            } else {
                $('#refresh-button').hide();
            }
            calculateAndDisplayTotalHours(); // Recalculate total pounds after filtering
        });
        $('#date-picker').change(function() {
            $('#refresh-button').click();
        });

    });
</script>
</body>
</html>