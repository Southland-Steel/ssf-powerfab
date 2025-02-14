<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Movement Data</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
            --accent-color: #3498db;
            --light-gray: #f5f6fa;
            --border-color: #dcdde1;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.4;
            margin: 0;
            padding: 10px;
            background-color: var(--light-gray);
            font-size: 12px;
        }

        .layout {
            display: flex;
            gap: 10px;
        }

        .sidebar {
            width: 200px;
            flex-shrink: 0;
            background: white;
            padding: 15px;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .main-content {
            flex-grow: 1;
        }

        .container {
            background: white;
            padding: 15px;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .controls {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            flex-wrap: wrap;
            align-items: center;
        }

        .date-range {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        input[type="text"] {
            padding: 4px 8px;
            border: 1px solid var(--border-color);
            border-radius: 3px;
            font-size: 12px;
        }

        .btn {
            padding: 4px 12px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
            transition: background-color 0.2s;
        }

        .btn-primary {
            background-color: var(--accent-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: #2980b9;
        }

        .btn-secondary {
            background-color: var(--secondary-color);
            color: white;
        }

        .btn-secondary:hover {
            background-color: #2c3e50;
        }

        .preset-buttons {
            display: flex;
            gap: 8px;
        }

        .filter-section {
            margin-bottom: 15px;
        }

        .filter-section h3 {
            margin: 0 0 8px 0;
            font-size: 13px;
            color: var(--primary-color);
        }

        .checkbox-group {
            max-height: 250px;
            overflow-y: auto;
            font-size: 12px;
        }

        .checkbox-group label {
            display: block;
            margin-bottom: 3px;
            cursor: pointer;
        }

        .checkbox-group input[type="checkbox"] {
            margin-right: 5px;
        }

        .summary-stats {
            background: var(--primary-color);
            color: white;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            display: flex;
            gap: 15px;
        }

        .stat-item {
            flex: 1;
        }

        .stat-item h4 {
            margin: 0;
            font-size: 12px;
            opacity: 0.9;
        }

        .stat-item p {
            margin: 3px 0 0 0;
            font-size: 18px;
            font-weight: 600;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 12px;
        }

        .data-table th,
        .data-table td {
            padding: 4px 8px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
            line-height: 1.2;
            white-space: nowrap;
            max-width: 120px;
            overflow-x: clip;
        }

        .data-table th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 500;
            padding: 6px 8px;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .data-table tbody tr:nth-child(even) {
            background-color: var(--light-gray);
        }

        .data-table tbody tr:hover {
            background-color: rgba(52, 152, 219, 0.1);
        }

        th.sortable {
            cursor: pointer;
            user-select: none;
        }

        th.sortable:hover {
            background-color: var(--secondary-color);
        }

        th.sorted-asc::after {
            content: " ↑";
            font-size: 10px;
        }

        th.sorted-desc::after {
            content: " ↓";
            font-size: 10px;
        }

        .table-responsive {
            overflow-x: auto;
            max-height: calc(100vh - 250px);
            overflow-y: auto;
        }

        .loading {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            display: none;
        }

        .loading.active {
            display: flex;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .modal.active {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 4px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-color);
        }

        .modal-title {
            font-size: 16px;
            font-weight: 600;
            margin: 0;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            padding: 5px;
        }

        .modal-body {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
        }

        .detail-group {
            margin-bottom: 10px;
        }

        .detail-label {
            font-weight: 500;
            color: var(--primary-color);
            margin-bottom: 3px;
        }

        .detail-value {
            color: var(--secondary-color);
        }

        /* Make table rows clickable */
        .data-table tbody tr {
            cursor: pointer;
        }

        .data-table tbody tr:hover {
            background-color: rgba(52, 152, 219, 0.2);
        }

        .loading::after {
            content: "Loading...";
            font-size: 14px;
            color: var(--primary-color);
        }

        @media (max-width: 768px) {
            .layout {
                flex-direction: column;
            }

            .sidebar {
                width: auto;
            }

            .controls {
                flex-direction: column;
                align-items: stretch;
            }

            .date-range {
                flex-wrap: wrap;
            }

            .preset-buttons {
                flex-wrap: wrap;
            }
        }
        .type-filter {
            display: flex;
            gap: 8px;
            margin-bottom: 15px;
            flex-wrap: wrap;
            align-items: center;
        }

        .btn-type {
            background-color: var(--secondary-color);
            color: white;
            opacity: 0.7;
            transition: opacity 0.2s;
            text-transform: uppercase;
        }

        .btn-type:hover {
            opacity: 0.9;
        }

        .btn-type.active {
            opacity: 1;
            background-color: var(--accent-color);
        }

        .type-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        /* Help button styles */
        .help-button {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            padding: 8px 16px;
            background-color: var(--accent-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .help-button:hover {
            background-color: #2980b9;
        }

        /* Documentation modal styles */
        .docs-modal .modal-content {
            width: 90%;
            max-width: 1200px;
            padding: 30px;
        }

        .docs-modal .modal-body {
            display: block;
            padding: 20px;
            overflow-y: auto;
            max-height: 70vh;
        }

        .markdown-body {
            font-size: 14px;
            line-height: 1.6;
        }

        .markdown-body h1 {
            padding-bottom: 0.3em;
            border-bottom: 1px solid #eaecef;
        }

        .markdown-body h2 {
            padding-bottom: 0.3em;
            border-bottom: 1px solid #eaecef;
        }
    </style>
</head>
<body>
<button onclick="showDocumentation()" class="help-button">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10"></circle>
        <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
        <line x1="12" y1="17" x2="12.01" y2="17"></line>
    </svg>
    Help
</button>
<div id="loading" class="loading"></div>
<div class="layout">
    <aside class="sidebar">
        <div class="filter-section">
            <h3>Shapes</h3>
            <div class="checkbox-group" id="shapes-filter"></div>
        </div>

        <div class="filter-section">
            <h3>PO Numbers</h3>
            <div class="checkbox-group" id="po-filter"></div>
        </div>

        <div class="filter-section">
            <h3>Vendors</h3>
            <div class="checkbox-group" id="vendor-filter"></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="container">
            <div class="controls">
                <div class="date-range">
                    <label for="start_date">From:</label>
                    <input type="text" id="start_date" value="<?php echo date('Y-m-d', strtotime('-1 day')); ?>" class="date-picker">

                    <label for="end_date">To:</label>
                    <input type="text" id="end_date" value="<?php echo date('Y-m-d'); ?>" class="date-picker">

                    <button type="button" id="apply-date" class="btn btn-primary">Apply Filter</button>
                </div>

                <div class="preset-buttons">
                    <button type="button" class="btn btn-secondary" data-range="today">Today</button>
                    <button type="button" class="btn btn-secondary" data-range="yesterday">Yesterday</button>
                    <button type="button" class="btn btn-secondary" data-range="week">This Week</button>
                    <button type="button" class="btn btn-secondary" data-range="month">This Month</button>
                    <button type="button" class="btn btn-secondary" data-range="last-month">Last Month</button>
                </div>

                <button id="export-csv" class="btn btn-primary">Export CSV</button>
            </div>
            <div class="type-filter">
                <button type="button" class="btn btn-type active" data-type="all">Show All Types</button>
                <div id="type-buttons" class="type-buttons"></div>
            </div>

            <div class="summary-stats">
                <div class="stat-item">
                    <h4>Total Line Items</h4>
                    <p id="total-items">0</p>
                </div>
                <div class="stat-item">
                    <h4>Summed Quantity</h4>
                    <p id="total-quantity">0</p>
                </div>
                <div class="stat-item">
                    <h4>Summed Valuation</h4>
                    <p id="total-valuation">$0.00</p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                    <tr>
                        <th class="sortable" data-sort="trans_date">Date</th>
                        <th class="sortable" data-sort="trans_type">Type</th>
                        <th class="sortable" data-sort="tablesource">Source</th>
                        <th class="sortable" data-sort="ItemID">Item ID</th>
                        <th class="sortable" data-sort="qty">Quantity</th>
                        <th class="sortable" data-sort="Shape">Shape</th>
                        <th class="sortable" data-sort="Grade">Grade</th>
                        <th class="sortable" data-sort="DimensionSizesImperial">Dimensions</th>
                        <th class="sortable" data-sort="LengthIn">Length</th>
                        <th class="sortable" data-sort="Location">Location</th>
                        <th class="sortable" data-sort="Job">Job</th>
                        <th class="sortable" data-sort="PONumber">PO Number</th>
                        <th class="sortable" data-sort="Supplier">Supplier</th>
                        <th class="sortable" data-sort="Valuation">Valuation</th>
                        <th class="sortable" data-sort="BillOfLadingNo">BOL</th>
                        <th class="sortable" data-sort="Category">Category</th>
                        <th class="sortable" data-sort="SubCategory">SubCategory</th>
                        <th class="sortable" data-sort="TFSDate">TFS Date</th>
                        <th class="sortable" data-sort="TFSJob">TFS Job</th>
                    </tr>
                    </thead>
                    <tbody id="data-body"></tbody>
                </table>
            </div>
        </div>
    </main>
</div>
<div id="docs-modal" class="modal docs-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Documentation</h3>
            <button class="modal-close" onclick="closeDocumentation()">&times;</button>
        </div>
        <div class="modal-body">
            <div id="markdown-content" class="markdown-body">
                <!-- Markdown content will be inserted here -->
            </div>
        </div>
    </div>
</div>


<!-- Modal -->
<div id="detail-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Item Details</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body" id="modal-body">
            <!-- Content will be dynamically inserted here -->
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/github-markdown-css/5.2.0/github-markdown.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/marked/9.1.2/marked.min.js"></script>
<script>
    // Initialize date pickers
    const datePickers = flatpickr('.date-picker', {
        dateFormat: 'Y-m-d'
    });

    // State management
    let fullData = [];
    let activeFilters = {
        shapes: [],
        poNumbers: [],
        vendors: [],
        type: 'all'
    };
    let currentSort = {
        column: 'trans_date',
        direction: 'desc'
    };

    // Loading state management
    function showLoading() {
        document.getElementById('loading').classList.add('active');
    }

    function hideLoading() {
        document.getElementById('loading').classList.remove('active');
    }

    // Error handling
    function showError(message) {
        alert(message);
    }

    // Fetch and render data
    async function fetchData() {
        showLoading();
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;

        try {
            const response = await fetch(`ajax_get_received.php?start_date=${startDate}&end_date=${endDate}`);
            const result = await response.json();

            fullData = result.data;
            renderFilters(result.summary.filters);
            renderTypeButtons(result.data);  // Add this line
            applyFiltersAndRender();

        } catch (error) {
            console.error('Error fetching data:', error);
            showError('Failed to load data. Please try again.');
        } finally {
            hideLoading();
        }
    }

    // Render filter checkboxes
    function renderFilters(filters) {
        const renderCheckboxGroup = (items, containerId, filterKey) => {
            const container = document.getElementById(containerId);
            container.innerHTML = items.map(item => `
                    <label>
                        <input type="checkbox" value="${item}"
                            ${activeFilters[filterKey].includes(item) ? 'checked' : ''}>
                        ${item}
                    </label>
                `).join('');

            // Add event listeners
            container.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                checkbox.addEventListener('change', (e) => {
                    const value = e.target.value;
                    if (e.target.checked) {
                        activeFilters[filterKey].push(value);
                    } else {
                        activeFilters[filterKey] = activeFilters[filterKey].filter(v => v !== value);
                    }
                    applyFiltersAndRender();
                });
            });
        };

        renderCheckboxGroup(filters.shapes, 'shapes-filter', 'shapes');
        renderCheckboxGroup(filters.po_numbers, 'po-filter', 'poNumbers');
        renderCheckboxGroup(filters.vendors, 'vendor-filter', 'vendors');
    }

    // Apply filters and sorting
    function applyFiltersAndRender() {
        let filteredData = fullData;

        // Apply filters
        if (activeFilters.shapes.length) {
            filteredData = filteredData.filter(item => activeFilters.shapes.includes(item.Shape));
        }
        if (activeFilters.poNumbers.length) {
            filteredData = filteredData.filter(item => activeFilters.poNumbers.includes(item.PONumber));
        }
        if (activeFilters.vendors.length) {
            filteredData = filteredData.filter(item => activeFilters.vendors.includes(item.Supplier));
        }
        if (activeFilters.type !== 'all') {
            filteredData = filteredData.filter(item => item.trans_type === activeFilters.type);
        }

        // Apply sorting
        filteredData.sort((a, b) => {
            const aVal = a[currentSort.column];
            const bVal = b[currentSort.column];

            if (currentSort.direction === 'asc') {
                return aVal > bVal ? 1 : -1;
            } else {
                return aVal < bVal ? 1 : -1;
            }
        });

        renderTable(filteredData);
        updateSummary(filteredData);
    }

    // Render table data
    function renderTable(data) {
        const tbody = document.getElementById('data-body');
        tbody.innerHTML = data.map(row => `
                <tr onclick="showDetails(${JSON.stringify(row).replace(/"/g, '&quot;')})">
                    <td>${row.trans_date || ''}</td>
                    <td>${row.trans_type || ''}</td>
                    <td>${row.tablesource || ''}</td>
                    <td>${row.ItemID || ''}</td>
                    <td>${row.qty || ''}</td>
                    <td>${row.Shape || ''}</td>
                    <td>${row.Grade || ''}</td>
                    <td>${row.DimensionSizesImperial || ''}</td>
                    <td>${row.LengthIn || ''}</td>
                    <td>${row.Location || ''}</td>
                    <td>${row.Job || ''}</td>
                    <td>${row.PONumber || ''}</td>
                    <td>${row.Supplier || ''}</td>
                    <td>${row.Valuation || ''}</td>
                    <td>${row.BillOfLadingNo || ''}</td>
                    <td>${row.Category || ''}</td>
                    <td>${row.SubCategory || ''}</td>
                    <td>${row.TFSDate || ''}</td>
                    <td>${row.TFSJob || ''}</td>
                </tr>
            `).join('');
    }

    // Update summary statistics
    function updateSummary(data) {
        const totalItems = data.length;

        const {totalQuantity, totalValuation} = data.reduce((acc, row) => {
            const qty = parseFloat(row.qty) || 0;
            const multiplier = qty > 0 ? 1 : qty < 0 ? -1 : 0;
            const val = parseFloat(row.Valuation ? row.Valuation.replace(/[$,]/g, '') : 0);
            let valuation = (isNaN(val) ? 0 : val) * multiplier;

            // Round to 2 decimal places and convert -0.00 to 0.00
            valuation = Number(valuation.toFixed(2));
            if (valuation === 0 || valuation === -0) valuation = 0;

            return {
                totalQuantity: acc.totalQuantity + qty,
                totalValuation: acc.totalValuation + valuation
            };
        }, {totalQuantity: 0, totalValuation: 0});

        document.getElementById('total-items').textContent = totalItems.toLocaleString();
        document.getElementById('total-quantity').textContent = totalQuantity.toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
        document.getElementById('total-valuation').textContent = totalValuation.toLocaleString('en-US', {
            style: 'currency',
            currency: 'USD'
        });
    }

    function renderTypeButtons(data) {
        // Get unique types
        const types = [...new Set(data.map(item => item.trans_type).filter(Boolean))].sort();

        const typeButtonsContainer = document.getElementById('type-buttons');
        typeButtonsContainer.innerHTML = types.map(type => `
        <button type="button" class="btn btn-type" data-type="${type}">${type}</button>
    `).join('');

        // Add event listeners to all type buttons
        document.querySelectorAll('.btn-type').forEach(button => {
            button.addEventListener('click', (e) => {
                // Remove active class from all buttons
                document.querySelectorAll('.btn-type').forEach(btn => btn.classList.remove('active'));
                // Add active class to clicked button
                e.target.classList.add('active');
                // Update filter
                activeFilters.type = e.target.dataset.type;
                applyFiltersAndRender();
            });
        });
    }

    // Handle sorting
    document.querySelectorAll('th.sortable').forEach(th => {
        th.addEventListener('click', () => {
            const column = th.dataset.sort;
            if (currentSort.column === column) {
                currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
            } else {
                currentSort.column = column;
                currentSort.direction = 'asc';
            }

            // Update sort indicators
            document.querySelectorAll('th.sortable').forEach(header => {
                header.classList.remove('sorted-asc', 'sorted-desc');
            });
            th.classList.add(`sorted-${currentSort.direction}`);

            applyFiltersAndRender();
        });
    });

    // Reset filters function
    function resetFilters() {
        activeFilters = {
            shapes: [],
            poNumbers: [],
            vendors: [],
            type: 'all'
        };
        // Reset active state of type buttons
        document.querySelectorAll('.btn-type').forEach(btn => btn.classList.remove('active'));
        document.querySelector('.btn-type[data-type="all"]').classList.add('active');
    }

    // Handle date range buttons
    document.querySelectorAll('[data-range]').forEach(button => {
        button.addEventListener('click', () => {
            const range = button.dataset.range;
            const today = new Date();
            let start = new Date();
            let end = new Date();

            switch(range) {
                case 'today':
                    break;
                case 'yesterday':
                    start.setDate(start.getDate() - 1);
                    end = new Date(start);
                    break;
                case 'week':
                    start.setDate(start.getDate() - start.getDay());
                    break;
                case 'month':
                    start.setDate(1);
                    break;
                case 'last-month':
                    start.setMonth(start.getMonth() - 1);
                    start.setDate(1);
                    end = new Date(start.getFullYear(), start.getMonth() + 1, 0);
                    break;
            }

            datePickers[0].setDate(start);
            datePickers[1].setDate(end);
            resetFilters();  // Reset filters before fetching new data
            fetchData();
        });
    });

    // Handle date filter application
    document.getElementById('apply-date').addEventListener('click', () => {
        resetFilters();  // Reset filters before fetching new data
        fetchData();
    });

    // Handle CSV export
    document.getElementById('export-csv').addEventListener('click', () => {
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;
        window.location.href = `ajax_get_received.php?start_date=${startDate}&end_date=${endDate}&export=csv`;
    });

    // Modal functionality
    const modal = document.getElementById('detail-modal');
    const modalClose = document.querySelector('.modal-close');

    function showDetails(row) {
        const modalBody = document.getElementById('modal-body');
        const fields = {
            'Item ID': row.ItemID,
            'Date': row.trans_date,
            'Type': row.trans_type,
            'Quantity': row.qty,
            'Shape': row.Shape,
            'Grade': row.Grade,
            'Dimensions': row.DimensionSizesImperial,
            'Length (in)': row.LengthIn,
            'Weight (lb)': row.WeightLb,
            'Location': row.Location,
            'Secondary Location': row.SecondaryLocation,
            'Job': row.Job,
            'Job Reserve': row.JobReserve,
            'Previous Job': row.PreviousJob,
            'Original Job': row.OriginalJob,
            'PO Number': row.PONumber,
            'Supplier': row.Supplier,
            'Heat Number': row.HeatNo,
            'BOL Number': row.BillOfLadingNo,
            'Valuation': row.Valuation,
            'Current Price': row.CurrentPrice,
            'Current Price Units': row.CurrentPriceUnits,
            'Original Price': row.OriginalPrice,
            'Original Price Units': row.OriginalPriceUnits,
            'Original Date': row.OriginalDate,
            'Category': row.Category,
            'SubCategory': row.SubCategory,
            'TFS Job': row.TFSJob,
            'TFS Date': row.TFSDate,
            'Source': row.tablesource,
            'Remarks': row.Remarks,
            'Reference Number': row.ReferenceNumber,
            'Part Number': row.PartNumber,
            'Serial Number': row.SerialNumber,
            'Serial Number Quantity': row.SerialNumberQuantity
        };

        modalBody.innerHTML = Object.entries(fields)
            .filter(([key, value]) => value !== null && value !== undefined && value !== '')
            .map(([key, value]) => `
                    <div class="detail-group">
                        <div class="detail-label">${key}</div>
                        <div class="detail-value">${value}</div>
                    </div>
                `).join('');

        modal.classList.add('active');
    }

    modalClose.addEventListener('click', () => {
        modal.classList.remove('active');
    });

    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.remove('active');
        }
    });

    async function showDocumentation() {
        try {
            const response = await fetch('received-docs.md');
            if (!response.ok) {
                throw new Error('Failed to load documentation');
            }
            const markdown = await response.text();
            document.getElementById('markdown-content').innerHTML = marked.parse(markdown);
            document.getElementById('docs-modal').classList.add('active');
        } catch (error) {
            console.error('Error loading documentation:', error);
            alert('Failed to load documentation. Please try again later.');
        }
    }

    function closeDocumentation() {
        document.getElementById('docs-modal').classList.remove('active');
    }

    // Close documentation modal when clicking outside
    document.getElementById('docs-modal').addEventListener('click', (e) => {
        if (e.target === document.getElementById('docs-modal')) {
            closeDocumentation();
        }
    });

    // Initial data load
    fetchData();
</script>
</body>
</html>