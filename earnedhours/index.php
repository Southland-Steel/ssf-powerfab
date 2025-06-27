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
    <style>
        .bg-gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        }

        .table-container-custom {
            max-height: 78.5vh;
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
                            <br><span class="avg-label">Daily Avg: <span id="cutAvg">0</span></span>
                            <br><span class="avg-label">6-Day Avg: <span id="cut6dayAvg">0</span></span>
                        </th>
                        <th class="text-center fw-bold text-uppercase small py-2 px-1">
                            Man Hours Fit
                            <br><span class="avg-label">Daily Avg: <span id="fitAvg">0</span></span>
                            <br><span class="avg-label">6-Day Avg: <span id="fit6dayAvg">0</span></span>
                        </th>
                        <th class="text-center fw-bold text-uppercase small py-2 px-1">
                            Man Hours Final QC
                            <br><span class="avg-label">Daily Avg: <span id="finalqcAvg">0</span></span>
                            <br><span class="avg-label">6-Day Avg: <span id="finalqc6dayAvg">0</span></span>
                        </th>
                        <th class="text-center fw-bold text-uppercase small py-2 px-1">
                            Total Earned Man Hours
                            <br><span class="avg-label">Daily Avg: <span id="totalAvg">0</span></span>
                            <br><span class="avg-label">6-Day Avg: <span id="total6dayAvg">0</span></span>
                        </th>
                    </tr>
                    </thead>
                    <tbody id="tableBody">
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="view_weekly_earnedhours.js"></script>
<script src="view_hours_by_jobNum.js"></script>
<script>
    let stationData = null;
    let jobNumberExporter = null;

    function toggleTooltip() {
        const tooltip = document.getElementById('tooltipContent');
        if (tooltip.style.display === 'none' || tooltip.style.display === '') {
            tooltip.style.display = 'block';
        } else {
            tooltip.style.display = 'none';
        }
    }

    function showError(message) {
        document.getElementById('errorText').textContent = message;
        document.getElementById('errorMessage').style.display = 'block';
    }

    function showNoData() {
        document.getElementById('noDataMessage').style.display = 'block';
    }

    function showData() {
        document.getElementById('dataTable').style.display = 'block';
    }

    function updateStatistics(stats) {
        document.getElementById('cutAvg').textContent = Math.round(stats.cut_avg * 100) / 100;
        document.getElementById('fitAvg').textContent = Math.round(stats.fit_avg * 100) / 100;
        document.getElementById('finalqcAvg').textContent = Math.round(stats.finalqc_avg * 100) / 100;
        document.getElementById('totalAvg').textContent = Math.round(stats.total_avg * 100) / 100;
        document.getElementById('cut6dayAvg').textContent = Math.round(stats.cut_6day_avg * 100) / 100;
        document.getElementById('fit6dayAvg').textContent = Math.round(stats.fit_6day_avg * 100) / 100;
        document.getElementById('finalqc6dayAvg').textContent = Math.round(stats.finalqc_6day_avg * 100) / 100;
        document.getElementById('total6dayAvg').textContent = Math.round(stats.total_6day_avg * 100) / 100;
    }

    async function exportJobDataToCSV() {
    try {
        console.log('Starting job data export...');
        
        const response = await fetch('jobNumber_data.php');
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const text = await response.text();
        console.log('Raw response:', text);
        
        let data;
        try {
            data = JSON.parse(text);
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            console.error('Response text:', text);
            throw new Error('Invalid JSON response from server');
        }
        
        if (!data.success) {
            throw new Error(data.message || 'Server returned error');
        }

        const jobHours = new JobNumberWeeklyHours();
        jobHours.setData(data.data);
        jobHours.downloadJobNumberWeeklySummary('csv');
        
    } catch (error) {
        console.error('Export error:', error);
        alert('Error exporting job data: ' + error.message);
    }
}

    function loadStationData() {
        document.getElementById('loadingSpinner').style.display = 'block';

        fetch('ajax_station_data.php')
            .then(response => response.json())
            .then(data => {
                document.getElementById('loadingSpinner').style.display = 'none';

                if (data.success && data.data) {
                    if (data.data.all_dates && Array.isArray(data.data.all_dates)) {
                        stationData = data.data;
                        document.getElementById('dayCount').textContent = data.data.all_dates.length;

                        if (data.data.statistics) {
                            updateStatistics(data.data.statistics);
                        }

                        populateTable(data.data);

                        if (data.data.all_dates.length === 0) {
                            showNoData();
                        } else {
                            showData();
                        }
                    } else {
                        showError('Invalid data structure: all_dates is missing or not an array');
                    }
                } else {
                    showError(data.message || 'Unknown error occurred');
                }
            })
            .catch(error => {
                document.getElementById('loadingSpinner').style.display = 'none';
                showError('Failed to load data: ' + error.message);
            });
    }

    function populateTable(data) {
        const tableBody = document.getElementById('tableBody');
        tableBody.innerHTML = '';

        if (!data.all_dates || !Array.isArray(data.all_dates)) {
            console.error('Invalid data structure: all_dates is missing or not an array');
            return;
        }

        data.all_dates.forEach(date => {
            const cutHours = (data.cut_data && data.cut_data[date]) || 0;
            const fitHours = (data.fit_data && data.fit_data[date]) || 0;
            const finalqcHours = (data.finalqc_data && data.finalqc_data[date]) || 0;
            const totalHours = cutHours + fitHours + finalqcHours;

            const dateObj = new Date(date);
            const dayNames = ['MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT'];
            const dayOfWeek = dayNames[dateObj.getDay()];
            const formattedDate = `${date} (${dayOfWeek})`;

            const row = document.createElement('tr');
            row.innerHTML = `
        <td class="text-center fw-bold text-date py-1 px-1">${formattedDate}</td>
        <td class="text-center py-1 px-1 cut-hours">
            ${(data.cut_data && data.cut_data[date]) ?
                `<a href="view_cut.php?query_date=${encodeURIComponent(date)}" class="clickable-hours text-cut fw-bold">${Math.round(data.cut_data[date] * 100) / 100} hrs</a>` :
                '<span class="text-muted fst-italic">-</span>'
            }
        </td>
        <td class="text-center py-1 px-1 fit-hours">
            ${(data.fit_data && data.fit_data[date]) ?
                `<a href="view_fit.php?query_date=${encodeURIComponent(date)}" class="clickable-hours text-fit fw-bold">${Math.round(data.fit_data[date] * 100) / 100} hrs</a>` :
                '<span class="text-muted fst-italic">-</span>'
            }
        </td>
        <td class="text-center py-1 px-1 finalqc-hours">
            ${(data.finalqc_data && data.finalqc_data[date]) ?
                `<a href="view_finalQC.php?query_date=${encodeURIComponent(date)}" class="clickable-hours text-finalqc fw-bold">${Math.round(data.finalqc_data[date] * 100) / 100} hrs</a>` :
                '<span class="text-muted fst-italic">-</span>'
            }
        </td>
        <td class="text-center fw-bold text-success py-1 px-1">
            <span class="text-total fw-bold non-clickable-hours">
                ${totalHours > 0 ? Math.round(totalHours * 100) / 100 + ' hrs' : '<span class="text-muted fst-italic">-</span>'}
            </span>
        </td>
    `;
            tableBody.appendChild(row);
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        jobNumberExporter = new JobNumberWeeklyHours();

        loadStationData();

        document.getElementById('exportCSV').addEventListener('click', function() {
            if (stationData && stationData.export_data) {
                const weeklyHours = new WeeklyHours();
                weeklyHours.setData(stationData.export_data);
                weeklyHours.downloadWeeklySummary('csv');
            }
        });

        document.getElementById('exportJSON').addEventListener('click', function() {
            if (stationData && stationData.export_data) {
                const weeklyHours = new WeeklyHours();
                weeklyHours.setData(stationData.export_data);
                weeklyHours.downloadWeeklySummary('json');
            }
        });

        document.getElementById('exportJobDataCSV').addEventListener('click', async function() {
    try {
        const response = await fetch('jobNumber_data.php');
        const data = await response.json();
        
        if (data.success && data.data) {
            const jobNumberHours = new JobNumberWeeklyHours();
            jobNumberHours.setData(data.data);
            jobNumberHours.downloadJobNumberWeeklySummary('csv');
        } else {
            throw new Error(data.message || 'Failed to load job data');
        }
    } catch (error) {
        console.error('Export error:', error);
        alert('Error exporting job data: ' + error.message);
    }
});
    });
</script>
</body>
</html>