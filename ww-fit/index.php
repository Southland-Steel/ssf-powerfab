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
    <title>Fit Summary</title>
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

        .table-container {
            max-height: 78.5vh;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }

        tr.targetweek{
            border: 3px solid red !important;
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
        .center {
            text-align: center;
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
    </style>
</head>
<body>
    <div class="container">
        <header class="monitor-header">
            <h1>Fit Summary Report By Hours</h1>
            <p class="text-muted">Weekly Summary for Fit Earned Hours by Work Week</p>
        </header>
        
        <div class="row mb-3">
            <div class="col-md-6">
                <button class="btn btn-primary" onclick="refreshData()">
                    <i class="loading-spinner me-2" id="loadingSpinner" style="display: none;"></i>
                    Refresh Data
                </button>
            </div>
        </div>

        <div id="alertContainer"></div>
        
        <div class="table-container">
            <table class="table table-bordered table-striped" id="fitSummaryTable">
                <thead>
                    <tr>
                        <th class="center">Work Week</th>
                        <th class="center">Fit<br><h6>Earned Hours</h6></th>
                        <th class="center">Fit Target</th>
                        <th class="center">Progress</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <tr>
                        <td colspan="4" class="center">
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
        function showAlert(message, type = 'danger') {
            const alertContainer = document.getElementById('alertContainer');
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

        function loadFitSummary() {
            const loadingSpinner = document.getElementById('loadingSpinner');
            const tableBody = document.getElementById('tableBody');
            const targetWeek = <?= $currentWorkweek + 1?>

            // Show loading spinner
            if (loadingSpinner) {
                loadingSpinner.style.display = 'inline-block';
            }
            
            // Clear previous alerts
            document.getElementById('alertContainer').innerHTML = '';

            // Clear table body
            tableBody.innerHTML = '<tr><td colspan="4" class="center"><div class="loading-spinner me-2"></div>Loading data...</td></tr>';

            fetch('ajax_fit_summary.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    // Hide loading spinner
                    if (loadingSpinner) {
                        loadingSpinner.style.display = 'none';
                    }
                    
                    // Clear table body
                    tableBody.innerHTML = '';
                    
                    if (data && data.length > 0) {
                        // Populate table with data
                        data.forEach(row => {
                            const fit = parseFloat(row.Fit || 0);
                            const fitTotal = parseFloat(row.FitTotal || 0);
                            
                            const progressPercentage = calculateProgress(fit, fitTotal);
                            const progressBarClass = getProgressBarClass(progressPercentage);
                            
                            const tr = document.createElement('tr');
                            tr.innerHTML = `
                                <td class="center">${row.WorkWeek}</td>
                                <td class="center">${fit.toFixed(2)}</td>
                                <td class="center">${fitTotal.toFixed(2)}</td>
                                <td class="center">
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar ${progressBarClass}" 
                                             role="progressbar" 
                                             style="width: ${progressPercentage}%"
                                             aria-valuenow="${progressPercentage}" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                            ${progressPercentage}%
                                        </div>
                                    </div>
                                </td>
                            `;
                            console.log(row.WorkWeek);
                            console.log(targetWeek);
                            if(row.WorkWeek == targetWeek){
                                tr.classList.add('targetweek');
                            }

                            tableBody.appendChild(tr);
                        });

                        showAlert(`Successfully loaded ${data.length} records`, 'success');
                    } else {
                        tableBody.innerHTML = '<tr><td colspan="4" class="center">No data found</td></tr>';
                        showAlert('No data found for the selected criteria', 'warning');
                    }
                })
                .catch(error => {
                    // Hide loading spinner
                    if (loadingSpinner) {
                        loadingSpinner.style.display = 'none';
                    }
                    
                    tableBody.innerHTML = '<tr><td colspan="4" class="center text-danger">Error loading data</td></tr>';
                    showAlert('Error loading data: ' + error.message, 'danger');
                    console.error('Error:', error);
                });
        }
        
        // Function to refresh data
        function refreshData() {
            loadFitSummary();
        }
        
        // Load data when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadFitSummary();
        });
    </script>
</body>
</html>