<?php

$error_message = '';
$cut_data = [];
$fit_data = [];
$finalqc_data = [];
$all_dates = [];
$cut_avg = 0;
$fit_avg = 0;
$finalqc_avg = 0;
$total_avg = 0;
$cut_6day_avg = 0;
$fit_6day_avg = 0;
$finalqc_6day_avg = 0;
$total_6day_avg = 0;
$export_data = [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Production Overview</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        .bg-gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        }

        .table-container-custom {
            max-height: 74.5vh;
            overflow-y: auto;
        }

        .table th {
            position: sticky;
            top: 0;
            z-index: 10;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%) !important;
        }

        .table tbody tr:hover {
            outline: 1px solid black !important;
        }

        .clickable-hours {
            text-decoration: none;
            padding: 5px 8px;
            border-radius: 0.25rem;
            display: inline-block;
        }

        .clickable-hours:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .non-clickable-hours{
            text-decoration: none;
            padding: 5px 8px;
            border-radius: 0.25rem;
            display: inline-block;
        }

        .text-cut { color: #ff6b6b !important; }
        .text-fit { color: #4ecdc4 !important; }
        .text-finalqc { color: #4CAF50 !important; }
        .text-date { color: #667eea !important; }
        .text-total { color: darkorange !important; }

        .cut-hours .clickable-hours:hover {
            background-color: #ff6b6b !important;
            color: white !important;
        }

        .fit-hours .clickable-hours:hover {
            background-color: #4ecdc4 !important;
            color: white !important;
        }

        .finalqc-hours .clickable-hours:hover {
            background-color: #4CAF50 !important;
            color: white !important;
        }

        .avg-label {
            font-size: 0.75rem;
            color: #6c757d;
            font-weight: bold;
        }

        .info-button {
            position: absolute;
            top: 4px;
            left: 4px;
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .info-button:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }

        .tooltip-content {
            display: none;
            margin-top: 4px;
        }

        .action-buttons {
            margin-top: 4px;
            margin-bottom: 4px;
        }

        .btn-color {
            background-color: #667eea;
            color: black;
            border: 1px solid #667eea;
            margin-right: 10px;
        }

        .btn-color:hover {
            background-color: #5a6fd8;
            color: black;
            border: 1px solid #5a6fd8;
        }

        .btn-job-export {
            background-color: #28a745;
            color: white;
            border: 1px solid #28a745;
            margin-right: 10px;
        }

        .btn-job-export:hover {
            background-color: #218838;
            color: white;
            border: 1px solid #1e7e34;
        }

        .loading-spinner {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .date-filter-section {
            background: #f8f9fa;
            border-radius: 0.375rem;
            padding: 4px;
            margin-bottom: 4px;
            border: 1px solid #dee2e6;
        }

        .date-filter-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 4px;
        }
    </style>
</head>
<body class="bg-light">
<div class="container-fluid px-1 py-1">
    <div class="card border-0 shadow">
        <div class="card-header bg-gradient-primary text-white text-center py-2 px-2" style="position: relative;">
            <button class="info-button" onclick="toggleTooltip()">i</button>
            <h1 class="h3 mb-1">SSF Earned Man Hours Overview</h1>
            <p class="small mb-1 opacity-75">Complete Earned Man Hour Tracking Across Stations</p>
            <p class="small mb-0 opacity-75">Displaying <span id="dayCount">Loading...</span> Days</p>
        </div>

        <div class="card-body p-2">
            <div id="tooltipContent" class="tooltip-content">
                <div class="alert alert-info py-2 px-2 mb-2 border-start border-4 border-primary">
                    <div class="d-flex align-items-center">
                        <span class="me-2">💡</span>
                        <small>Click on any Man Hours value to view detailed breakdown for that date and station</small>
                    </div>
                </div>
            </div>
            <div class="date-filter-section">
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label class="date-filter-label">Start Date</label>
                        <input type="text" id="startDatePicker" class="form-control" placeholder="Select start date">
                    </div>
                    <div class="col-md-4">
                        <label class="date-filter-label">End Date</label>
                        <input type="text" id="endDatePicker" class="form-control" placeholder="Select end date">
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-primary" id="applyDateFilter">Apply Filter</button>
                        <button class="btn btn-secondary" id="resetDateFilter">Reset to Default</button>
                    </div>
                </div>
            </div>

            <div class="action-buttons d-flex justify-content-start">
                <button class="btn btn-color btn-sm" id="exportJSON">Export Weekly Summary (JSON)</button>
                <button class="btn btn-color btn-sm" id="exportCSV" >Export Weekly Summary (CSV)</button>
                <button class="btn btn-job-export btn-sm" id="exportJobDataCSV">Export Job Data (CSV)</button>
            </div>

            <div class="loading-spinner" id="loadingSpinner">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p>Loading production data...</p>
            </div>

            <div id="errorMessage" class="alert alert-danger py-2 px-2 mb-2 text-center" style="display: none;">
                <strong>Error loading data:</strong>
                <span id="errorText"></span>
            </div>

            <div id="noDataMessage" class="alert alert-info py-2 px-2 mb-2 text-center" style="display: none;">
                <strong>No Data Available:</strong>
                No production data available
            </div>

            <div id="dataTable" class="table-container-custom border rounded shadow-sm" style="display: none;">
                <table class="table table-striped table-hover table-sm mb-0">
                    <thead class="table-light">
                    <tr>
                        <th class="text-center fw-bold text-uppercase small py-2 px-1">Date Completed</th>
                        <th class="text-center fw-bold text-uppercase small py-2 px-1">
                            Man Hours Cut
                            <br><span class="avg-label">Daily Avg: <span id="cutAvg">0.00</span></span>
                            <br><span class="avg-label">6-Day Avg: <span id="cut6dayAvg">0.00</span></span>
                        </th>
                        <th class="text-center fw-bold text-uppercase small py-2 px-1">
                            Man Hours Fit
                            <br><span class="avg-label">Daily Avg: <span id="fitAvg">0.00</span></span>
                            <br><span class="avg-label">6-Day Avg: <span id="fit6dayAvg">0.00</span></span>
                        </th>
                        <th class="text-center fw-bold text-uppercase small py-2 px-1">
                            Man Hours Final QC
                            <br><span class="avg-label">Daily Avg: <span id="finalqcAvg">0.00</span></span>
                            <br><span class="avg-label">6-Day Avg: <span id="finalqc6dayAvg">0.00</span></span>
                        </th>
                        <th class="text-center fw-bold text-uppercase small py-2 px-1">
                            Total Man Hours
                            <br><span class="avg-label">Daily Avg: <span id="totalAvg">0.00</span></span>
                            <br><span class="avg-label">6-Day Avg: <span id="total6dayAvg">0.00</span></span>
                        </th>
                    </tr>
                    </thead>
                    <tbody id="tableBodyContent">
                    <!-- Table rows will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="view_weekly_earnedhours.js"></script>
<script src="view_hours_by_jobNum.js"></script>

<script>
    let startDatePicker, endDatePicker;
    let startDate = null;
    let endDate = null;
    const weeklyHours = new WeeklyHours();
    const jobNumberWeeklyHours = new JobNumberWeeklyHours();

    document.addEventListener('DOMContentLoaded', function() {
        const today = new Date();
        const defaultStartDate = new Date(today);
        defaultStartDate.setDate(today.getDate() - 30);

        startDatePicker = flatpickr("#startDatePicker", {
            dateFormat: "Y-m-d",
            defaultDate: defaultStartDate,
            maxDate: today,
            onChange: function(selectedDates, dateStr, instance) {
                startDate = dateStr;
                // Update end date picker's minDate
                if (endDatePicker) {
                    endDatePicker.set('minDate', dateStr);
                }
            }
        });

        endDatePicker = flatpickr("#endDatePicker", {
            dateFormat: "Y-m-d",
            defaultDate: today,
            maxDate: today,
            onChange: function(selectedDates, dateStr, instance) {
                endDate = dateStr;
                // Update start date picker's maxDate
                if (startDatePicker) {
                    startDatePicker.set('maxDate', dateStr);
                }
            }
        });

        startDate = startDatePicker.formatDate(defaultStartDate, "Y-m-d");
        endDate = endDatePicker.formatDate(today, "Y-m-d");

        loadStationData();
    });

    document.getElementById('applyDateFilter').addEventListener('click', function() {
        if (startDate && endDate) {
            loadStationData();
        } else {
            alert('Please select both start and end dates');
        }
    });

    document.getElementById('resetDateFilter').addEventListener('click', function() {
        const today = new Date();
        const defaultStartDate = new Date(today);
        defaultStartDate.setDate(today.getDate() - 30);

        startDatePicker.setDate(defaultStartDate);
        endDatePicker.setDate(today);
        
        startDate = startDatePicker.formatDate(defaultStartDate, "Y-m-d");
        endDate = endDatePicker.formatDate(today, "Y-m-d");
        
        loadStationData();
    });

    function loadStationData() {
        showLoading();

        let url = 'ajax_station_data.php';
        if (startDate && endDate) {
            url += `?start_date=${encodeURIComponent(startDate)}&end_date=${encodeURIComponent(endDate)}`;
        }

        fetch(url)
            .then(response => response.json())
            .then(data => {
                hideLoading();
                
                if (data.success) {
                    populateTable(data.data);
                    weeklyHours.setData(convertDataForWeeklyHours(data.data.export_data));
                } else {
                    showError(data.message || 'Unknown error occurred');
                }
            })
            .catch(error => {
                hideLoading();
                showError('Network error: ' + error.message);
                console.error('Fetch error:', error);
            });
    }

    function showLoading() {
        document.getElementById('loadingSpinner').style.display = 'block';
        document.getElementById('dataTable').style.display = 'none';
        document.getElementById('errorMessage').style.display = 'none';
        document.getElementById('noDataMessage').style.display = 'none';
    }

    function hideLoading() {
        document.getElementById('loadingSpinner').style.display = 'none';
    }

    function showError(message) {
        document.getElementById('errorText').textContent = message;
        document.getElementById('errorMessage').style.display = 'block';
        document.getElementById('dataTable').style.display = 'none';
        document.getElementById('noDataMessage').style.display = 'none';
    }

    function populateTable(data) {
        const tableBody = document.getElementById('tableBodyContent');
        
        if (!data.all_dates || data.all_dates.length === 0) {
            document.getElementById('noDataMessage').style.display = 'block';
            document.getElementById('dataTable').style.display = 'none';
            return;
        }

        const dayNames = ['SUN', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT'];

        function getDayName(dateString) {
            const [year, month, day] = dateString.split('-').map(Number);
            const date = new Date(year, month - 1, day); // month is 0-indexed
            return dayNames[date.getDay()];
        }

        document.getElementById('dayCount').textContent = data.all_dates.length;

        document.getElementById('cutAvg').textContent = parseFloat(data.statistics.cut_avg || 0).toFixed(2);
        document.getElementById('fitAvg').textContent = parseFloat(data.statistics.fit_avg || 0).toFixed(2);
        document.getElementById('finalqcAvg').textContent = parseFloat(data.statistics.finalqc_avg || 0).toFixed(2);
        document.getElementById('totalAvg').textContent = parseFloat(data.statistics.total_avg || 0).toFixed(2);
        document.getElementById('cut6dayAvg').textContent = parseFloat(data.statistics.cut_6day_avg || 0).toFixed(2);
        document.getElementById('fit6dayAvg').textContent = parseFloat(data.statistics.fit_6day_avg || 0).toFixed(2);
        document.getElementById('finalqc6dayAvg').textContent = parseFloat(data.statistics.finalqc_6day_avg || 0).toFixed(2);
        document.getElementById('total6dayAvg').textContent = parseFloat(data.statistics.total_6day_avg || 0).toFixed(2);

        tableBody.innerHTML = '';

        data.all_dates.forEach(date => {
            const row = document.createElement('tr');
            
            const cutHours = parseFloat(data.cut_data[date] || 0);
            const fitHours = parseFloat(data.fit_data[date] || 0);
            const finalqcHours = parseFloat(data.finalqc_data[date] || 0);
            const totalHours = cutHours + fitHours + finalqcHours;

            const dayName = getDayName(date);

            row.innerHTML = `
                <td class="text-center py-1 px-1 fw-bold text-date">${date} (${dayName})</td>
                <td class="text-center py-1 px-1 cut-hours">
                    ${cutHours > 0 ? 
                        `<a href="view_cut.php?query_date=${date}" class="clickable-hours text-cut fw-bold">${cutHours.toFixed(2)}</a>` : 
                        `<span class="non-clickable-hours text-muted">-</span>`
                    }
                </td>
                <td class="text-center py-1 px-1 fit-hours">
                    ${fitHours > 0 ? 
                        `<a href="view_fit.php?query_date=${date}" class="clickable-hours text-fit fw-bold">${fitHours.toFixed(2)}</a>` : 
                        `<span class="non-clickable-hours text-muted">-</span>`
                    }
                </td>
                <td class="text-center py-1 px-1 finalqc-hours">
                    ${finalqcHours > 0 ? 
                        `<a href="view_finalQC.php?query_date=${date}" class="clickable-hours text-finalqc fw-bold">${finalqcHours.toFixed(2)}</a>` : 
                        `<span class="non-clickable-hours text-muted">-</span>`
                    }
                </td>
                <td class="text-center py-1 px-1 fw-bold text-total">${totalHours > 0 ? totalHours.toFixed(2) : '-'}</td>
            `;
            
            tableBody.appendChild(row);
        });

        document.getElementById('dataTable').style.display = 'block';
    }

    function convertDataForWeeklyHours(exportData) {
        return exportData || [];
    }

    function toggleTooltip() {
        const tooltip = document.getElementById('tooltipContent');
        tooltip.style.display = tooltip.style.display === 'none' ? 'block' : 'none';
    }

    document.getElementById('exportJSON').addEventListener('click', () => {
        weeklyHours.downloadWeeklySummary('json');
    });

    document.getElementById('exportCSV').addEventListener('click', () => {
        weeklyHours.downloadWeeklySummary('csv');
    });

    document.getElementById('exportJobDataCSV').addEventListener('click', () => {
        let url = 'jobNumber_data.php';
        if (startDate && endDate) {
            url += `?start_date=${encodeURIComponent(startDate)}&end_date=${encodeURIComponent(endDate)}`;
        }
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    jobNumberWeeklyHours.setData(data.data);
                    jobNumberWeeklyHours.downloadJobNumberCSV();
                } else {
                    alert('Error loading job data: ' + data.message);
                }
            })
            .catch(error => {
                alert('Network error loading job data: ' + error.message);
            });
    });
</script>
</body>
</html>