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
    <title>Cut Summary</title>
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

        .clickable-row {
            cursor: pointer;
        }
        
        .clickable-row:hover {
            background-color: #e3f2fd !important;
        }

        .modal-table-container {
            max-height: 60vh;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="monitor-header">
            <h1>Cut Summary Report By Hours</h1>
            <p class="text-muted">Weekly Summary for Main Piece and Piece Mark Cuts by Work Week</p>
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
            <table class="table table-bordered table-striped" id="cutSummaryTable">
                <thead>
                    <tr>
                        <th class="center">Work Week</th>
                        <th class="center">Main Piece Cut<br><h6>Earned Hours</h6></th>
                        <th class="center">Main Piece Cut Target</th>
                        <th class="center">Piece Mark Cut<br><h6>Earned Hours</h6></th>
                        <th class="center">Piece Mark Cut Target</th>
                        <th class="center">Progress</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <tr>
                        <td colspan="6" class="center">
                            <div class="loading-spinner me-2"></div>
                            Loading data...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Drilldown Modal -->
    <div class="modal fade" id="drilldownModal" tabindex="-1" aria-labelledby="drilldownModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="drilldownModalLabel">Cut Details - Work Week <span id="modalWorkWeek"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="modalAlertContainer"></div>
                    <div class="modal-table-container">
                        <table class="table table-bordered table-striped" id="drilldownTable">
                            <thead>
                                <tr>
                                    <th class="center">Work Week</th>
                                    <th class="center">Shape</th>
                                    <th class="center">Work Package Number</th>
                                    <th class="center">Main Piece Cut<br><h6>Earned Hours</h6></th>
                                    <th class="center">Main Piece Cut Target</th>
                                    <th class="center">Piece Mark Cut<br><h6>Earned Hours</h6></th>
                                    <th class="center">Piece Mark Cut Target</th>
                                </tr>
                            </thead>
                            <tbody id="drilldownTableBody">
                                <tr>
                                    <td colspan="7" class="center">
                                        <div class="loading-spinner me-2"></div>
                                        Loading data...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
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

        function formatValue(value) {
            if (value == null || value == undefined || value == '') {
                return '-';
            }
            return value;
        }

        function openDrilldownModal(workWeek) {
            const modal = new bootstrap.Modal(document.getElementById('drilldownModal'));
            const modalWorkWeek = document.getElementById('modalWorkWeek');
            const drilldownTableBody = document.getElementById('drilldownTableBody');
            
            // Set work week in modal title
            modalWorkWeek.textContent = workWeek;
            
            // Clear previous alerts
            document.getElementById('modalAlertContainer').innerHTML = '';
            
            // Clear table body and show loading
            drilldownTableBody.innerHTML = '<tr><td colspan="7" class="center"><div class="loading-spinner me-2"></div>Loading data...</td></tr>';
            
            // Show modal
            modal.show();
            
            // Fetch drilldown data
            fetch(`ajax_cut_drilldown.php?workweek=${workWeek}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    // Clear table body
                    drilldownTableBody.innerHTML = '';
                    
                    if (data && data.length > 0) {
                        // Populate table with data
                        data.forEach(row => {
                            const mcut = row.MCUT !== null && row.MCUT !== undefined && row.MCUT !== '' ? parseFloat(row.MCUT).toFixed(2) : '-';
                            const mcutTotal = row.MCUTtotal !== null && row.MCUTtotal !== undefined && row.MCUTtotal !== '' ? parseFloat(row.MCUTtotal).toFixed(2) : '-';
                            const cut = row.CUT !== null && row.CUT !== undefined && row.CUT !== '' ? parseFloat(row.CUT).toFixed(2) : '-';
                            const cutTotal = row.CUTtotal !== null && row.CUTtotal !== undefined && row.CUTtotal !== '' ? parseFloat(row.CUTtotal).toFixed(2) : '-';
                            
                            const tr = document.createElement('tr');
                            tr.innerHTML = `
                                <td class="center">${formatValue(row.WorkWeek)}</td>
                                <td class="center">${formatValue(row.Shape)}</td>
                                <td class="center">${formatValue(row.WorkPackageNumber)}</td>
                                <td class="center">${mcut}</td>
                                <td class="center">${mcutTotal}</td>
                                <td class="center">${cut}</td>
                                <td class="center">${cutTotal}</td>
                            `;
                            
                            drilldownTableBody.appendChild(tr);
                        });
                        
                        showAlert(`Successfully loaded ${data.length} detail records`, 'success', 'modalAlertContainer');
                    } else {
                        drilldownTableBody.innerHTML = '<tr><td colspan="7" class="center">No detail data found</td></tr>';
                        showAlert('No detail data found for this work week', 'warning', 'modalAlertContainer');
                    }
                })
                .catch(error => {
                    drilldownTableBody.innerHTML = '<tr><td colspan="7" class="center text-danger">Error loading detail data</td></tr>';
                    showAlert('Error loading detail data: ' + error.message, 'danger', 'modalAlertContainer');
                    console.error('Error:', error);
                });
        }

        function loadCutSummary() {
            const loadingSpinner = document.getElementById('loadingSpinner');
            const tableBody = document.getElementById('tableBody');
            const targetWeek = <?= $currentWorkweek + 4?>

            // Show loading spinner
            if (loadingSpinner) {
                loadingSpinner.style.display = 'inline-block';
            }
            
            // Clear previous alerts
            document.getElementById('alertContainer').innerHTML = '';

            // Clear table body
            tableBody.innerHTML = '<tr><td colspan="6" class="center"><div class="loading-spinner me-2"></div>Loading data...</td></tr>';

            fetch('ajax_cut_summary.php')
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
                            const mcut = parseFloat(row.MCUT || 0);
                            const mcutTotal = parseFloat(row.MCUTtotal || 0);
                            const cut = parseFloat(row.CUT || 0);
                            const cutTotal = parseFloat(row.CUTtotal || 0);
                            
                            const totalCompleted = mcut + cut;
                            const totalPlanned = mcutTotal + cutTotal;
                            const progressPercentage = calculateProgress(totalCompleted, totalPlanned);
                            const progressBarClass = getProgressBarClass(progressPercentage);
                            
                            const tr = document.createElement('tr');
                            tr.className = 'clickable-row';
                            tr.onclick = () => openDrilldownModal(row.WorkWeek);
                            tr.innerHTML = `
                                <td class="center">${row.WorkWeek}</td>
                                <td class="center">${mcut.toFixed(2)}</td>
                                <td class="center">${mcutTotal.toFixed(2)}</td>
                                <td class="center">${cut.toFixed(2)}</td>
                                <td class="center">${cutTotal.toFixed(2)}</td>
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
                            
                            if(row.WorkWeek == targetWeek){
                                tr.classList.add('targetweek');
                            }

                            tableBody.appendChild(tr);
                        });

                        showAlert(`Successfully loaded ${data.length} records`, 'success');
                    } else {
                        tableBody.innerHTML = '<tr><td colspan="6" class="center">No data found</td></tr>';
                        showAlert('No data found for the selected criteria', 'warning');
                        
                        // Reset summary stats
                        document.getElementById('totalWeeks').textContent = '0';
                        document.getElementById('totalMCUT').textContent = '0.0';
                        document.getElementById('totalCUT').textContent = '0.0';
                        document.getElementById('overallProgress').textContent = '0%';
                    }
                })
                .catch(error => {
                    // Hide loading spinner
                    if (loadingSpinner) {
                        loadingSpinner.style.display = 'none';
                    }
                    
                    tableBody.innerHTML = '<tr><td colspan="6" class="center text-danger">Error loading data</td></tr>';
                    showAlert('Error loading data: ' + error.message, 'danger');
                    console.error('Error:', error);
                });
        }
        
        // Function to refresh data
        function refreshData() {
            loadCutSummary();
        }
        
        // Load data when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadCutSummary();
        });
    </script>
</body>
</html>