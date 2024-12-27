<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Station Items Data Viewer</title>

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/css/jquery.dataTables.min.css">

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        #loadingMessage {
            text-align: center;
            margin: 20px 0;
            color: #666;
        }
        .error {
            color: red;
            margin: 20px 0;
            padding: 10px;
            background-color: #ffebee;
            border-radius: 4px;
            display: none;
        }
        #dataTable {
            width: 100%;
            background-color: white;
        }
        .refresh-button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-bottom: 20px;
        }
        .refresh-button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Station Items Data Viewer</h2>
    <button onclick="loadData()" class="refresh-button">Refresh Data</button>
    <div id="loadingMessage">Loading data...</div>
    <div id="error" class="error"></div>
    <table id="dataTable" class="display">
        <thead>
        <tr>
            <th>Username</th>
            <th>Job Number</th>
            <th>Job Description</th>
            <th>Sequence Name</th>
            <th>Work Package Name</th>
            <th>Work Week</th>
            <th>Assembly Main Mark</th>
            <th>Sequence Quantity</th>
            <th>Assembly Man Hours Each</th>
            <th>Station Day Sequence Quantity Instance</th>
            <th>Route</th>
            <th>Station Name</th>
            <th>Date Completed</th>
            <th>Total Hours</th>
            <th>Calculated Hours</th>
            <th>Station Percentage</th>
        </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<!-- Required JavaScript -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/js/jquery.dataTables.min.js"></script>

<script>
    let dataTable;

    function showError(message) {
        const errorDiv = document.getElementById('error');
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
    }

    function hideError() {
        document.getElementById('error').style.display = 'none';
    }

    function showLoading() {
        document.getElementById('loadingMessage').style.display = 'block';
    }

    function hideLoading() {
        document.getElementById('loadingMessage').style.display = 'none';
    }

    function loadData() {
        showLoading();
        hideError();

        // If DataTable already exists, destroy it
        if (dataTable) {
            dataTable.destroy();
        }

        // Fetch data from the PHP endpoint
        $.ajax({
            url: 'ajax_ssf_stationitems.php',
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                hideLoading();

                // Initialize DataTable with fetched data
                dataTable = $('#dataTable').DataTable({
                    data: data,
                    columns: [
                        { data: 'Username' },
                        { data: 'JobNumber' },
                        { data: 'JobDescription' },
                        { data: 'SequenceName' },
                        { data: 'WorkPackageName' },
                        { data: 'WorkWeek' },
                        { data: 'AssemblyMainMark' },
                        { data: 'SequenceQuantity' },
                        { data: 'AssemblyManHoursEach' },
                        { data: 'StationDaySequenceQuantityInstance' },
                        { data: 'Route' },
                        { data: 'StationName' },
                        { data: 'DateCompleted' },
                        { data: 'TotalHours' },
                        { data: 'CalculatedHours' },
                        { data: 'StationPercentage' }
                    ],
                    scrollX: true,
                    pageLength: 25,
                    order: [[12, 'desc']], // Sort by DateCompleted by default
                    dom: 'Bfrtip'
                });
            },
            error: function(jqXHR, textStatus, errorThrown) {
                hideLoading();
                showError('Error loading data: ' + (errorThrown || textStatus));
            }
        });
    }

    // Load data automatically when page loads
    $(document).ready(function() {
        loadData();
    });
</script>
</body>
</html>