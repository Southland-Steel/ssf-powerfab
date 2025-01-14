<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Earned Hours Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        .filter-btn.active {
            background-color: #007bff;
            color: white;
        }
        .station-cell {
            text-align: center;
        }
        .hours {
            font-weight: bold;
        }
        .count {
            font-size: 0.8em;
            color: #6c757d;
        }
        th {
            text-align: center;
        }
        .summary-row {
            font-weight: bold;
            background-color: #f8f9fa;
        }
        .column-stats {
            font-size: 0.8em;
            border-top: 1px solid #dee2e6;
            padding-top: 0.3rem;
            line-height: 1.2;
        }

        #earned-hours-table tbody td.station-cell {
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        #earned-hours-table tbody td.station-cell:hover {
            background-color: #f0f0f0;
        }
        #earned-hours-table tbody td:nth-child(5),
        #earned-hours-table thead th:nth-child(5) {
            background-color: #ffebcc; /* Light highlight background */
            font-weight: bold;
            border-left: 3px solid #ff9800; /* Add a bold orange border */
        }
        /* Remove hover and pointer only for the Total column */
        #earned-hours-table tbody td:nth-child(7),
        #earned-hours-table thead th:nth-child(7) {
            cursor: default; /* No pointer cursor */
            background-color: #c3dffd; /* Light highlight background */
            transition: none; /* Disable transition effect */
            border-left: 3px solid #258afd; /* Add a bold orange border */
        }

        /* Specifically disable hover effect for the Total column */
        #earned-hours-table tbody td:nth-child(7):hover {
            background-color: transparent; /* No highlight on hover */
        }

        /* Hover effect to further emphasize the Weld column */
        #earned-hours-table tbody td:nth-child(5):hover {
            background-color: #ffe0b3; /* Slightly darker highlight on hover */
        }
        .modal-body table {
            width: 100%;
        }
        .modal-body th, .modal-body td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .modal-body th {
            background-color: #f2f2f2;
        }
        #dateRangePicker {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1000;
            background-color: white;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .flatpickr-input {
            background-color: white !important;
        }
        .flatpickr-calendar {
            font-size: 14px;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <h1 class="mb-4">SSF Earned Estimated Hours</h1>
    <div id="dateRangePicker" class="mb-3">
        <input type="text" class="form-control mb-2" id="startDate" placeholder="Start Date">
        <input type="text" class="form-control mb-2" id="endDate" placeholder="End Date">
        <button id="applyDateFilter" class="btn btn-primary btn-sm me-2">Apply Filter</button>
        <button id="clearDateFilter" class="btn btn-secondary btn-sm">Clear Filter</button>
    </div>
    <div id="weekly-hours-container"></div>
    <table class="table table-striped" id="earned-hours-table">
        <thead>
        <tr>
            <th>Date</th>
            <th>
                Fit
                <div class="column-stats" id="stats-fit"></div>
            </th>
            <th>
                Weld
                <div class="column-stats" id="stats-weld"></div>
            </th>
            <th>
                Final QC
                <div class="column-stats" id="stats-final-qc"></div>
            </th>
            <th>
                Total
                <div class="column-stats" id="stats-total"></div>
            </th>
        </tr>
        </thead>
        <tbody>
        </tbody>
        <tfoot>
        <tr class="summary-row">
            <td>Summary</td>
            <td colspan="6" id="summary-data"></td>
        </tr>
        </tfoot>
    </table>
</div>
<div class="modal fade" id="cellDetailsModal" tabindex="-1" aria-labelledby="cellDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cellDetailsModalLabel">Cell Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="modalCellInfo"></div>
                <table id="modalDataTable" class="table table-striped">
                    <thead>
                    <tr>
                        <th>Route</th>
                        <th>Job Number</th>
                        <th>Main Mark</th>
                        <th>Sequence Quantity</th>
                        <th>Station Day Sequence Quantity Instance</th>
                        <th>Assembly Hrs Each</th>
                        <th>Station Hour Credit</th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="view_ssf_earned_hours_WeeklyHours.js"></script>
<script>
    $(document).ready(function() {
        let allData = [];
        let currentWeek = 'all';
        let startDate = null;
        let endDate = null;
        const stations = ['FIT', 'WELD', 'FINAL QC'];
        const rollingAverageDays = 4; // Set the number of days for rolling average

        window.weeklyHoursComponent = new WeeklyHoursComponent('weekly-hours-container');

        flatpickr("#startDate", {
            dateFormat: "Y-m-d",
            onChange: function(selectedDates, dateStr) {
                startDate = dateStr;
            }
        });

        flatpickr("#endDate", {
            dateFormat: "Y-m-d",
            onChange: function(selectedDates, dateStr) {
                endDate = dateStr;
            }
        });


        function fetchData() {
            let ajaxData = {};
            if (startDate) ajaxData.begin_date = startDate;
            if (endDate) ajaxData.end_date = endDate;
            $.ajax({
                url: 'ajax_ssf_get_earned_hours.php',
                method: 'GET',
                data: ajaxData,
                dataType: 'json',
                success: function(data) {
                    console.log("Fetched data:", data);
                    allData = data;
                    processData(allData);
                    //updateFilterButtons(data);
                    setupCellClickHandlers(); // Add this line
                    window.weeklyHoursComponent.setData(data);
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching data:", error);
                }
            });
        }

        $('#applyDateFilter').click(function() {
            startDate = $('#startDate').val();
            endDate = $('#endDate').val();
            fetchData();
        });

        $('#clearDateFilter').click(function() {
            $('#startDate')[0]._flatpickr.clear();
            $('#endDate')[0]._flatpickr.clear();
            startDate = null;
            endDate = null;
            fetchData();
        });

        function processData(data) {
            console.log("Processing data for week:", currentWeek);
            let dailyTotals = {};
            let filteredCount = 0;

            data.forEach(item => {
                // Changed this line to pass only the item
                if (item && shouldIncludeItem(item)) {
                    filteredCount++;
                    let date = item.DateCompleted.split(' ')[0];
                    if (!dailyTotals[date]) {
                        dailyTotals[date] = {};
                        stations.forEach(station => {
                            dailyTotals[date][station] = { hours: 0, quantity: 0 };
                        });
                    }

                    if (stations.includes(item.StationName)) {
                        let hours = parseFloat(item.CalculatedHours) || 0;
                        let quantity = parseInt(item.StationDaySequenceQuantityInstance) || 0;
                        dailyTotals[date][item.StationName].hours += hours;
                        dailyTotals[date][item.StationName].quantity += quantity;
                    }
                }
            });

            console.log("Filtered items count:", filteredCount);
            console.log("Daily totals:", dailyTotals);
            updateTable(dailyTotals);
        }

        function shouldIncludeItem(item) {
            if (!item || !item.DateCompleted || !isValidDate(item.DateCompleted)) {
                console.log("Invalid item or date:", item);
                return false;
            }

            let itemDate = new Date(item.DateCompleted.split(' ')[0]);

            if (startDate && itemDate < new Date(startDate)) {
                console.log("Item date is before start date");
                return false;
            }

            if (endDate && itemDate > new Date(endDate)) {
                console.log("Item date is after end date");
                return false;
            }

            // Use the global currentWeek variable here
            if (currentWeek === 'all') return true;
            if (currentWeek === 'unknown') return !item.WorkWeek || String(item.WorkWeek).trim() === '';
            return String(item.WorkWeek).trim() === String(currentWeek).trim();
        }


        function updateTable(dailyTotals) {
            let tableBody = $('#earned-hours-table tbody');
            tableBody.empty();

            let rowCount = 0;
            let totalHours = 0;
            let totalQuantity = 0;
            let columnTotals = {};
            stations.forEach(station => {
                columnTotals[station] = { hours: 0, quantity: 0 };
            });
            columnTotals['Total'] = { hours: 0, quantity: 0 };

            let dates = Object.keys(dailyTotals).sort((a, b) => new Date(b) - new Date(a));

            dates.forEach((date, index) => {
                let stationData = dailyTotals[date];
                let dailyTotalHours = 0;
                let dailyTotalQuantity = 0;
                let stationCells = '';

                // Parse the date string manually (assuming it's in "YYYY-MM-DD" format)
                let dateParts = date.split('-'); // Split the date into year, month, and day
                let dateObj = new Date(dateParts[0], dateParts[1] - 1, dateParts[2]); // Month is 0-based
                let dayAbbreviation = dateObj.toLocaleString('en-US', { weekday: 'short' }); // e.g., "Mon", "Tue"

                stations.forEach(station => {
                    let hours = stationData[station]?.hours || 0;
                    let quantity = stationData[station]?.quantity || 0;
                    dailyTotalHours += hours;
                    dailyTotalQuantity += quantity;
                    columnTotals[station].hours += hours;
                    columnTotals[station].quantity += quantity;
                    stationCells += `
            <td class="station-cell" data-date="${date}" data-station="${station}">
                <span class="hours">${formatNumber(hours)} hrs</span>
                <span class="quantity">(${quantity})</span>
            </td>
        `;
                });

                totalHours += dailyTotalHours;
                totalQuantity += dailyTotalQuantity;
                columnTotals['Total'].hours += dailyTotalHours;
                columnTotals['Total'].quantity += dailyTotalQuantity;

                // Append the day abbreviation next to the date
                let row = `<tr>
            <td>${date} (${dayAbbreviation})</td> <!-- Show date with day abbreviation -->
            ${stationCells}
            <td class="station-cell">
                <span class="hours">${formatNumber(dailyTotalHours)} hrs</span>
                <span class="quantity">(${dailyTotalQuantity})</span>
            </td>
        </tr>`;
                tableBody.append(row);
                rowCount++;
            });

            console.log("Updated table with", rowCount, "rows");

            if (rowCount === 0) {
                tableBody.append('<tr><td colspan="7" class="text-center">No data available for the selected filter.</td></tr>');
            }

            updateSummary(totalHours, totalQuantity, rowCount, dates);
            updateColumnStats(columnTotals, rowCount, dates);
        }


        function updateSummary(totalHours, totalQuantity, rowCount, dates) {
            let overallAverage = rowCount > 0 ? totalHours / rowCount : 0;
            let rollingAverage = calculateRollingAverage(dates, rollingAverageDays, 'Total');

            $('#summary-data').html(`
                Overall Total: ${formatNumber(totalHours)} hrs (${totalQuantity})
                | Overall Average: ${formatNumber(overallAverage)} hrs/day
                | ${rollingAverageDays}-day Rolling Average: ${formatNumber(rollingAverage)} hrs/day
            `);
        }

        function updateColumnStats(columnTotals, rowCount, dates) {
            [...stations, 'Total'].forEach(station => {
                let total = columnTotals[station].hours;
                let average = rowCount > 0 ? total / rowCount : 0;
                let rollingAverage = calculateRollingAverage(dates, rollingAverageDays, station);

                $(`#stats-${station.toLowerCase().replace(' ', '-')}`).html(`
            <div>Sum: ${Math.round(total)} hrs</div>
            <div>Avg: ${formatNumber(average)} hrs/day</div>
            <div>${rollingAverageDays}-day Avg: ${formatNumber(rollingAverage)} hrs/day</div>
        `);
            });
        }

        function calculateRollingAverage(dates, days, station) {
            if (dates.length < days) return 0;
            let sum = 0;
            for (let i = 0; i < days; i++) {
                let cellIndex = station === 'Total' ? 6 : stations.indexOf(station) + 1;
                sum += parseFloat($(`#earned-hours-table tbody tr:nth-child(${i + 1}) td:nth-child(${cellIndex + 1}) .hours`).text()) || 0;
            }
            return sum / days;
        }

        function formatNumber(num) {
            let parsed = parseFloat(num);
            return isNaN(parsed) ? '0.00' : parsed.toFixed(2);
        }

        $(document).on('click', '.filter-btn', function(e) {
            e.preventDefault();
            $('.filter-btn').removeClass('active');
            $(this).addClass('active');
            currentWeek = $(this).data('week');
            console.log("Filter clicked:", currentWeek);
            processData(allData);  // Re-process the data with the new week filter
        });

        function setupCellClickHandlers() {
            $(document).on('click', '.station-cell', function() {
                // Check if the clicked cell is in the "Total" column (7th column)
                const isTotalColumn = $(this).index() === 6;
                if (isTotalColumn) {
                    return; // Prevent modal from opening for the "Total" column
                }

                let date = $(this).data('date');
                let station = $(this).data('station');
                let cellData = allData.filter(item =>
                    item.DateCompleted.split(' ')[0] === date &&
                    item.StationName === station
                );

                let modalContent = `<h4>${station} - ${date}</h4>`;
                $('#modalCellInfo').html(modalContent);

                let tableBody = $('#modalDataTable tbody');
                tableBody.empty();

                cellData.forEach(item => {
                    tableBody.append(`
                <tr>
                    <td>${item.Route}</td>
                    <td>${item.JobNumber}</td>
                    <td>${item.AssemblyMainMark}</td>
                    <td>${item.SequenceQuantity}</td>
                    <td>${item.StationDaySequenceQuantityInstance}</td>
                    <td>${formatNumber(item.AssemblyManHoursEach)}</td>
                    <td>${formatNumber(item.CalculatedHours)} (${item.StationPercentage}%)</td>
                </tr>
            `);
                });

                $('#cellDetailsModal').modal('show');
            });
        }


        function isValidDate(dateString) {
            const date = new Date(dateString);
            return !isNaN(date.getTime());
        }

        function formatDate(date) {
            if (date instanceof Date) {
                return date.toISOString().split('T')[0];
            } else if (typeof date === 'string') {
                return date.split('T')[0]; // Assuming the string is in ISO format or YYYY-MM-DD
            }
            return '';
        }

        fetchData();



    });
</script>
</body>
</html>