<?php

$currentYear = substr(date('o'), -2);
$currentWeek = date('W');
$currentWorkweek = intval($currentYear . str_pad($currentWeek, 2, '0', STR_PAD_LEFT));

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Production Summary Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
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
        .monitor-header {
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

        .drill-down-buttons {
            margin-bottom: 20px;
        }

        .drill-down-buttons .btn {
            background-color: #007bff;
            border-color: #007bff;
            color: white;
            margin-right: 10px;
            margin-bottom: 10px;
            padding: 8px 16px;
            font-size: 14px;
            width: auto;
            min-width: 150px;
        }

        .drill-down-buttons .btn:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }

        .table-container {
            max-height: 78.5vh;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .cut{
            width: 22.5%;
        }
        .fit{
            width: 22.5%;
        }
        .finalqc{
            width: 22.5%;
        }
        .overall{
            width: 22.5%;
        }
        .wide{
            width: 14.5%;
        }
        .narrow{
            width: 8%;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }

        tr.targetweek{
            border: 3px solid red !important;
        }

        tr.current-week{
            background-color: #f7ff86 !important;
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

        th.work-week-column {
            width: 10%;
        }

        th.progress-column {
            width: 21.25%;
        }
        
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .center {
            text-align: center;
        }

        .cutTarget>td.narrow.cut, .fitTarget>td.narrow.fit, .dummy1>td.narrow.cut, .dummy2>td.narrow.cut{
            border-right: 3px solid red;
        }

        .cutTarget td.cut,.fitTarget td.fit,.finalqcTarget td.finalqc, .overallTarget td.overall{
            border-bottom: 3px solid red !important;
        }
        
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .clickable-row {
            cursor: pointer;
        }
        
        .clickable-row:hover {
            background-color: #e3f2fd !important;
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="monitor-header">
            <h1>Production Summary Dashboard</h1>
            <p class="text-muted">Combined Weekly Production Progress by Work Week</p>
        </header>

        <div class="drill-down-buttons">
            <a href="ww-cut/view_cut_summary.php" class="btn btn-primary">
                View Cut Details
            </a>
            <a href="ww-fit/view_fit_summary.php" class="btn btn-primary">
                View Fit Details
            </a>
            <a href="ww-finalqc/view_finalqc_summary.php" class="btn btn-primary">
                View Final QC Details
            </a>
        </div>

        <div id="alertContainer"></div>
        
        <div class="table-container">
            <table class="table table-bordered table-striped" id="summaryTable">
                <thead>
                    <tr>
                        <th class="center work-week-column">Work Week</th>
                        <th class="center cut wide">Cut Progress</th>
                        <th class="center cut narrow">Cut Rem</th>
                        <th class="center fit wide">Fit Progress</th>
                        <th class="center fit narrow">Fit Rem</th>
                        <th class="center finalqc wide">Final QC Progress</th>
                        <th class="center finalqc narrow">Final QC Rem</th>
                        <th class="center overall wide">Overall Progress</th>
                        <th class="center overall narrow">Overall Rem</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <tr>
                        <td colspan="9" class="center">
                            <div class="loading-spinner me-2"></div>
                            Loading data...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Function to show alerts
        function showAlert(message, type = 'danger', containerId = 'alertContainer') {
            const alertContainer = document.getElementById(containerId);
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            alertContainer.innerHTML = alertHtml;
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                const alert = alertContainer.querySelector('.alert');
                if (alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }
            }, 5000);
        }

        function calculateProgress(completed, total) {
            if (total === 0) return 0;
            return Math.round((completed / total) * 100);
        }

        function getProgressBarClass(percentage) {
            if (percentage >= 80) return 'bg-success';
            if (percentage >= 50) return 'bg-warning';
            return 'bg-danger';
        }

        function createProgressBar(percentage, label = '') {
            const progressBarClass = getProgressBarClass(percentage);
            return `
                <div class="progress" style="height: 20px;">
                    <div class="progress-bar ${progressBarClass}" 
                         role="progressbar" 
                         style="width: ${percentage}%"
                         aria-valuenow="${percentage}" 
                         aria-valuemin="0" 
                         aria-valuemax="100">
                        ${percentage}%
                    </div>
                </div>
            `;
        }

        function loadSummaryData() {
            const tableBody = document.getElementById('tableBody');
            const currentWeek = <?= $currentWorkweek?>;
            const overallTarget = <?= $currentWorkweek?>;
            const cutTarget = <?= $currentWorkweek + 4?>;
            const fitTarget = <?= $currentWorkweek + 1?>;
            const fqcTarget = <?= $currentWorkweek?>;
            const dummy1 = <?= $currentWorkweek + 2?>;
            const dummy2 = <?= $currentWorkweek + 3?>;
            
            // Clear previous alerts
            document.getElementById('alertContainer').innerHTML = '';

            // Clear table body
            tableBody.innerHTML = '<tr><td colspan="9" class="center"><div class="loading-spinner me-2"></div>Loading data...</td></tr>';

            // Fetch all three data sources
            Promise.all([
                fetch('ww-cut/ajax_cut_summary.php'),
                fetch('ww-fit/ajax_fit_summary.php'),
                fetch('ww-finalqc/ajax_finalqc_summary.php')
            ])
            .then(responses => {
                return Promise.all(responses.map(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                }));
            })
            .then(([cutData, fitData, finalQcData]) => {
                // Clear table body
                tableBody.innerHTML = '';
                
                // Create a combined data structure by work week
                const combinedData = {};
                
                // Process cut data
                cutData.forEach(row => {
                    const workWeek = row.WorkWeek;
                    if (!combinedData[workWeek]) {
                        combinedData[workWeek] = { WorkWeek: workWeek };
                    }
                    
                    const mcut = parseFloat(row.MCUT || 0);
                    const mcutTotal = parseFloat(row.MCUTtotal || 0);
                    const cut = parseFloat(row.CUT || 0);
                    const cutTotal = parseFloat(row.CUTtotal || 0);
                    
                    const cutCompleted = mcut + cut;
                    const cutPlanned = mcutTotal + cutTotal;
                    
                    combinedData[workWeek].cutProgress = calculateProgress(cutCompleted, cutPlanned);
                    combinedData[workWeek].cutCompleted = cutCompleted;
                    combinedData[workWeek].cutPlanned = cutPlanned;
                });
                
                // Process fit data
                fitData.forEach(row => {
                    const workWeek = row.WorkWeek;
                    if (!combinedData[workWeek]) {
                        combinedData[workWeek] = { WorkWeek: workWeek };
                    }
                    
                    const fit = parseFloat(row.Fit || 0);
                    const fitTotal = parseFloat(row.FitTotal || 0);
                    
                    combinedData[workWeek].fitProgress = calculateProgress(fit, fitTotal);
                    combinedData[workWeek].fitCompleted = fit;
                    combinedData[workWeek].fitPlanned = fitTotal;
                });
                
                // Process final QC data
                finalQcData.forEach(row => {
                    const workWeek = row.WorkWeek;
                    if (!combinedData[workWeek]) {
                        combinedData[workWeek] = { WorkWeek: workWeek };
                    }
                    
                    const fqcbo = parseFloat(row.FQCBO || 0);
                    const fqcboTotal = parseFloat(row.FQCBOtotal || 0);
                    const fqc = parseFloat(row.FQC || 0);
                    const fqcTotal = parseFloat(row.FQCtotal || 0);
                    
                    const fqcCompleted = fqcbo + fqc;
                    const fqcPlanned = fqcboTotal + fqcTotal;
                    
                    combinedData[workWeek].finalQcProgress = calculateProgress(fqcCompleted, fqcPlanned);
                    combinedData[workWeek].finalQcCompleted = fqcCompleted;
                    combinedData[workWeek].finalQcPlanned = fqcPlanned;
                });
                
                // Sort work weeks and populate table
                const sortedWeeks = Object.keys(combinedData).sort((a, b) => parseInt(a) - parseInt(b));
                
                if (sortedWeeks.length > 0) {
                    sortedWeeks.forEach(workWeek => {
                        const data = combinedData[workWeek];
                        
                        // Calculate overall progress
                        const totalCompleted = (data.cutCompleted || 0) + (data.fitCompleted || 0) + (data.finalQcCompleted || 0);
                        const totalPlanned = (data.cutPlanned || 0) + (data.fitPlanned || 0) + (data.finalQcPlanned || 0);
                        const overallProgress = calculateProgress(totalCompleted, totalPlanned);
                        
                        const tr = document.createElement('tr');
                        tr.className = 'clickable-row';
                        
                        tr.innerHTML = `
                            <td class="center">${workWeek}</td>
                            <td class="center cut wide">${createProgressBar(data.cutProgress || 0)}</td>
                            <td class="center cut narrow">${(data.cutPlanned - data.cutCompleted).toFixed(0)} hrs</td>
                            <td class="center fit wide">${createProgressBar(data.fitProgress || 0)}</td>
                            <td class="center fit narrow">${(data.fitPlanned - data.fitCompleted).toFixed(0)} hrs</td>
                            <td class="center finalqc wide">${createProgressBar(data.finalQcProgress || 0)}</td>
                            <td class="center finalqc narrow">${(data.finalQcPlanned - data.finalQcCompleted).toFixed(0)} hrs</td>
                            <td class="center overall wide">${createProgressBar(overallProgress)}</td>
                            <td class="center overall narrow">${(totalPlanned - totalCompleted).toFixed(0)} hrs</td>
                        `;
                        if(workWeek == currentWeek){
                            tr.classList.add('current-week');
                        }
                        if(workWeek == cutTarget){
                            tr.classList.add('cutTarget');
                        }
                        if(workWeek == fitTarget){
                            tr.classList.add('fitTarget');
                        }
                        if(workWeek == fqcTarget){
                            tr.classList.add('finalqcTarget');
                        }
                        if(workWeek == overallTarget){
                            tr.classList.add('overallTarget');
                        }
                        if(workWeek == dummy1){
                            tr.classList.add('dummy1');
                        }
                        if(workWeek == dummy2){
                            tr.classList.add('dummy2');
                        }

                        tableBody.appendChild(tr);
                    });
                } else {
                    tableBody.innerHTML = '<tr><td colspan="9" class="center">No data found</td></tr>';
                }
            })
            .catch(error => {
                tableBody.innerHTML = '<tr><td colspan="9" class="center text-danger">Error loading data</td></tr>';
                showAlert('Error loading data: ' + error.message, 'danger');
                console.error('Error:', error);
            });
        }
        
        // Load data when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadSummaryData();
        });
    </script>
</body>
</html>