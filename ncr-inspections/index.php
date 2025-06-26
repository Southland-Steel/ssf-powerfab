<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Open NCR Summary</title>
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
        
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
            position: sticky;
            top: 0;
            z-index: 10;
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
        .failure-type-failed {
            background-color: #8b0000;
            color: white;
            font-weight: bold;
        }
        .failure-type-failed-ftf {
            background-color: #FF0909;
            color: white;
            font-weight: bold;
        }
        .record-link {
            color: #0066cc;
            text-decoration: underline;
            cursor: pointer;
        }
        .record-link:hover {
            color: #004499;
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="monitor-header">
            <h1>Open NCR Summary</h1>
        </header>
        
        <div class="table-container">
            <table id="ncrTable">
                <thead>
                    <tr>
                        <th>Record Number</th>
                        <th>Parent Inspection</th>
                        <th>Initial Date</th>
                        <th>Job Number</th>
                        <th>Main Mark</th>
                        <th>NCR Type</th>
                        <th>Brief Description</th>
                        <th>Inspector Name</th>
                        <th>Failure Type</th>
                    </tr>
                </thead>
                <tbody id="ncrTableBody">
                    <tr>
                        <td colspan="8">Loading...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        fetch('ajax_open_ncr.php')
            .then(response => response.json())
            .then(data => {
                const tableBody = document.getElementById('ncrTableBody');
                tableBody.innerHTML = '';
                
                if (data.length === 0) {
                    tableBody.innerHTML = '<tr><td colspan="8">No data found</td></tr>';
                    return;
                }

                const parentsWithChildren = new Set();
                const childrenWithParents = new Set();
                
                data.forEach(row => {
                    if (row.ParentInspection != null && row.ParentInspection !== '') {
                        parentsWithChildren.add(row.ParentInspection);
                        childrenWithParents.add(row.RecordNum);
                    }
                });

                const parentAndChild = new Set();
                parentsWithChildren.forEach(recordNum => {
                    if (childrenWithParents.has(recordNum)) {
                        parentAndChild.add(recordNum);
                    }
                });

                data.forEach(row => {
                    let failureType;
                    let failureTypeClass = '';
                    
                    if (parentAndChild.has(row.RecordNum)) {
                        failureType = 'Failed (FTF)';
                        failureTypeClass = 'failure-type-failed-ftf';
                    } else if (row.ParentInspection != null && row.ParentInspection !== '') {
                        failureType = 'Failed';
                        failureTypeClass = 'failure-type-failed';
                    } else if (parentsWithChildren.has(row.RecordNum)) {
                        failureType = 'Failed (FTF)';
                        failureTypeClass = 'failure-type-failed-ftf';
                    } else {
                        failureType = 'Failed';
                        failureTypeClass = 'failure-type-failed';
                    }
                    
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td><a href="view_open_ncr_details.php?RecordNum=${row.RecordNum || ''}" class="record-link">${row.RecordNum || ''}</a></td>
                        <td>${row.ParentInspection || ''}</td>
                        <td>${row.InitialDate || ''}</td>
                        <td>${row.JobNum || ''}</td>
                        <td>${row.MainMark || ''}</td>
                        <td>${row.NCRType || ''}</td>
                        <td>${row.Descrip || ''}</td>
                        <td>${row.InspectorName || ''}</td>
                        <td class="${failureTypeClass}">${failureType}</td>
                    `;
                    tableBody.appendChild(tr);
                });
            })
            .catch(error => {
                console.error('Error fetching data:', error);
                document.getElementById('ncrTableBody').innerHTML = 
                    '<tr><td colspan="8">Error loading data</td></tr>';
            });
    </script>
</body>
</html>