<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        .table {
            font-size: 12px;
        }
        .table td, .table th {
            padding: 0.1rem 0.5rem;
            text-align: center;
        }
        .table td.description, .table th.description {
            text-align: left !important;
        }
        tr:hover {
            background-color: #e7d7fa !important;
        }
        .reserved {
            background-color: #fff3cd;
        }

        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            padding: 20px;
            background-color: #f8f9fa;
            overflow-y: auto;
            width: 250px;
            z-index: 100;
            border-right: 1px solid #dee2e6;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
        }

        .shape-filter {
            margin-bottom: 8px;
        }

        .shape-filter label {
            margin-left: 8px;
            cursor: pointer;
        }

        .shape-checkbox {
            cursor: pointer;
        }

        #shape-filters {
            max-height: calc(100vh - 400px);
            overflow-y: auto;
            padding-right: 10px;
        }

        .select2-container .select2-selection--multiple {
            min-height: 38px;
        }

        .filter-section {
            padding: 15px;
            background-color: #ffffff;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .filter-group {
            margin-bottom: 15px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .inventory-row {
            cursor: pointer;
        }

        .filter-header input {
            width: 100%;
            padding: 4px;
            font-size: 12px;
        }

        .header-container {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .summary-stats {
            display: flex;
            gap: 20px;
        }

        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            min-width: 100px;
        }

        .stat-label {
            font-size: 12px;
            color: #666;
        }

        .stat-value {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }

        .modal-table {
            font-size: 12px;
            width: 100%;
        }

        .modal-table td, .modal-table th {
            padding: 8px;
            border-bottom: 1px solid #dee2e6;
        }

        .modal-label {
            font-weight: bold;
            color: #666;
        }

        .sortable {
            cursor: pointer;
            position: relative;
            padding-right: 20px !important;
        }
        .sortable:after {
            content: '↕';
            position: absolute;
            right: 5px;
            color: #999;
        }
        .sortable.asc:after {
            content: '↑';
            color: #000;
        }
        .sortable.desc:after {
            content: '↓';
            color: #000;
        }
        .header-container h2 {
            color: #333;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .btn-group .btn {
            padding: 0.25rem 1rem;
            font-size: 0.875rem;
        }

        .btn-check:checked + .btn-outline-primary {
            background-color: #0d6efd;
            color: white;
            border-color: #0d6efd;
        }
        .nowrap{
            white-space: nowrap;
        }
    </style>
</head>
<body>
<!-- Sidebar -->
<div class="sidebar">
    <div class="filter-section">
        <div class="filter-group">
            <label>Location</label>
            <select id="location-filter" class="form-select">
                <option value="">All Locations</option>
                <!-- Locations will be populated here -->
            </select>
        </div>
        <div class="filter-group">
            <label>CutList</label>
            <select id="cutlist-filter" class="form-select">
                <option value="">All CutLists</option>
                <!-- CutLists will be populated here -->
            </select>
        </div>
        <div class="filter-group">
            <label>Machine</label>
            <select id="machine-filter" class="form-select">
                <option value="">All Machines</option>
                <!-- Machines will be populated here -->
            </select>
        </div>
        <div class="filter-group">
            <button id="reset-filters" class="btn btn-outline-secondary btn-sm w-100">
                <i class="fas fa-undo"></i> Reset All Filters
            </button>
        </div>
        <div class="filter-title mt-4">Shapes</div>
        <div id="shape-filters">
            <!-- Shape checkboxes will be populated here -->
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="header-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Current Inventory</h2>
            <div class="btn-group me-3" role="group" aria-label="Inventory Status">
                <input type="radio" class="btn-check" name="orderStatus" id="inStockBtn" value="0" checked>
                <label class="btn btn-outline-primary" for="inStockBtn">In Stock</label>

                <input type="radio" class="btn-check" name="orderStatus" value="1" id="onOrderBtn">
                <label class="btn btn-outline-primary" for="onOrderBtn">On Order</label>
            </div>
            <div class="export-buttons">
                <button class="btn btn-sm btn-outline-secondary me-2" id="exportCSV">
                    <i class="fas fa-file-csv"></i> Export CSV
                </button>
                <button class="btn btn-sm btn-outline-secondary" id="exportJSON">
                    <i class="fas fa-file-code"></i> Export JSON
                </button>
            </div>
        </div>
        <div class="summary-stats">
            <div class="stat-item">
                <span class="stat-label">Line Items</span>
                <span class="stat-value" id="total-line-items">0</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Total Items</span>
                <span class="stat-value" id="total-items">0</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Total Weight (lbs)</span>
                <span class="stat-value" id="total-weight">0</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Total Value</span>
                <span class="stat-value" id="total-value">0</span>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead>
            <tr>
                <th class="sortable" data-sort="Original">Original</th>
                <th class="sortable" data-sort="Shape">Shape</th>
                <th class="sortable" data-sort="DimensionString">Dimensions</th>
                <th class="sortable" data-sort="LengthInches">Length</th>
                <th class="sortable" data-sort="Quantity">Qty</th>
                <th class="sortable" data-sort="WeightLbs">Weight</th>
                <th class="sortable" data-sort="Location" id="locationhead">Location</th>
                <th class="sortable" data-sort="SecondaryLocation">Secondary Loc</th>
                <th class="sortable" data-sort="HeatNo">Heat #</th>
                <th class="sortable" data-sort="JobNumberReserved">Reserved For</th>
                <th class="sortable" data-sort="PONumber">PO #</th>
                <th class="sortable" data-sort="Supplier">Supplier</th>
                <th class="sortable" data-sort="CostCode">Cost Code</th>
                <th class="sortable" data-sort="Valuation">Valuation</th>
                <th class="sortable" data-sort="CutlistName">CutList</th>
                <th class="sortable" data-sort="CutlistBarcode">Bar-Code <span class="text-danger small">*</span></th>
                <th class="sortable" data-sort="MachineName">Machine</th>
            </tr>
            <tr class="filter-header">
                <th></th>
                <th></th>
                <th><input type="text" class="column-filter" data-column="DimensionString" placeholder="^3/4" title="Use a ^ at the beginning to say 'starts with'"></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th><input type="text" class="column-filter" data-column="HeatNo" placeholder="Filter Heat #"></th>
                <th><input type="text" class="column-filter" data-column="JobNumberReserved" placeholder="Filter Reserved"></th>
                <th><input type="text" class="column-filter" data-column="PONumber" placeholder="Filter PO #"></th>
                <th><input type="text" class="column-filter" data-column="Supplier" placeholder="Filter Supplier"></th>
                <th></th>
            </tr>
            </thead>
            <tbody id="inventory-body">
            </tbody>
        </table>
    </div>
</div>

<!-- Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Inventory Item Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Loading indicator -->
                <div id="modal-loading" class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading item details...</p>
                </div>

                <!-- Error message -->
                <div id="modal-error" class="alert alert-danger" style="display: none;"></div>

                <!-- Modal content (initially hidden) -->
                <div id="modal-detail-content" style="display: none;">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="modal-table">
                                    <tr>
                                        <td class="modal-label">Original Date:</td>
                                        <td id="modal-originaldate"></td>
                                    </tr>
                                    <tr>
                                        <td class="modal-label">Shape:</td>
                                        <td id="modal-shape"></td>
                                    </tr>
                                    <tr>
                                        <td class="modal-label">Dimensions:</td>
                                        <td id="modal-dimensions"></td>
                                    </tr>
                                    <tr>
                                        <td class="modal-label">Imperial Size:</td>
                                        <td id="modal-imperial-size"></td>
                                    </tr>
                                    <tr>
                                        <td class="modal-label">Length:</td>
                                        <td id="modal-length"></td>
                                    </tr>
                                    <tr>
                                        <td class="modal-label">Weight:</td>
                                        <td id="modal-weight"></td>
                                    </tr>
                                    <tr>
                                        <td class="modal-label">Quantity:</td>
                                        <td id="modal-quantity"></td>
                                    </tr>
                                    <tr>
                                        <td class="modal-label">Location:</td>
                                        <td id="modal-location"></td>
                                    </tr>
                                    <tr>
                                        <td class="modal-label">Secondary Location:</td>
                                        <td id="modal-secondary-location"></td>
                                    </tr>
                                    <tr>
                                        <td class="modal-label">CutList Name:</td>
                                        <td id="modal-cutlist-name"></td>
                                    </tr>
                                    <tr>
                                        <td class="modal-label">CutList Barcode:</td>
                                        <td id="modal-cutlist-barcode"></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="modal-table">
                                    <tr>
                                        <td class="modal-label">Heat Number:</td>
                                        <td id="modal-heat"></td>
                                    </tr>
                                    <tr>
                                        <td class="modal-label">PO Number:</td>
                                        <td id="modal-po"></td>
                                    </tr>
                                    <tr>
                                        <td class="modal-label">Bill of Lading:</td>
                                        <td id="modal-bol"></td>
                                    </tr>
                                    <tr>
                                        <td class="modal-label">Supplier:</td>
                                        <td id="modal-supplier"></td>
                                    </tr>
                                    <tr>
                                        <td class="modal-label">Reserved For:</td>
                                        <td id="modal-reserved"></td>
                                    </tr>
                                    <tr>
                                        <td class="modal-label">Reserve Date:</td>
                                        <td id="modal-reserve-date"></td>
                                    </tr>
                                    <tr>
                                        <td class="modal-label">Previous Job:</td>
                                        <td id="modal-previous-job"></td>
                                    </tr>
                                    <tr>
                                        <td class="modal-label">Cost Code:</td>
                                        <td id="modal-cost-code"></td>
                                    </tr>
                                    <tr>
                                        <td class="modal-label">Part Number:</td>
                                        <td id="modal-part-number"></td>
                                    </tr>
                                    <tr>
                                        <td class="modal-label">Reference Number:</td>
                                        <td id="modal-reference"></td>
                                    </tr>
                                    <tr>
                                        <td class="modal-label">Delivery Date:</td>
                                        <td id="modal-delivery-date"></td>
                                    </tr>
                                    <tr>
                                        <td class="modal-label">Valuation:</td>
                                        <td id="modal-valuation"></td>
                                    </tr>
                                    <tr>
                                        <td class="modal-label">Machine Name:</td>
                                        <td id="modal-machine-name"></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <!-- Serial Numbers Section -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <h6 class="mb-2">Serial Numbers</h6>
                                <div id="modal-serial-numbers"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Password Modal for Valuation Access -->
<div class="modal fade" id="valuationPasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Enter Password for Valuation Access</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger d-none" id="password-error"></div>
                <p>Enter the password to view valuation data:</p>
                <input type="password" class="form-control" id="valuation-password" placeholder="Enter password">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="submit-password">Submit</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        let inventoryData = [];
        let filteredData = [];
        let currentSortColumn = 'Original';
        let currentSortDirection = 'asc';
        let selectedLocation = '';
        let selectedCutlist = '';
        let selectedMachine = '';
        let selectedShapes = [];

        function fetchInventory() {
            $.ajax({
                url: 'ajax_get_inventory_list.php', // Updated URL
                method: 'GET',
                data: {
                    location: selectedLocation,
                    cutlist: selectedCutlist,
                    machine: selectedMachine,
                    onOrder: $('input[name="orderStatus"]:checked').val()
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        inventoryData = response.inventory;
                        populateFilters();
                        applyFilters();
                    } else {
                        console.error('Server error:', response.error);
                        alert('Error loading inventory data: ' + response.error);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('AJAX Error:', textStatus, errorThrown);
                    console.log('Response Text:', jqXHR.responseText);
                    alert('Error loading inventory data. Check console for details.');
                }
            });
        }

        // REPLACE your current populateFilters() function with this one
        function populateFilters() {
            // Start with the full inventory data
            let filteredForOptions = [...inventoryData];

            // Apply current filters to get the base filtered dataset
            if (selectedLocation) {
                filteredForOptions = filteredForOptions.filter(item => item.Location === selectedLocation);
            }

            if (selectedCutlist) {
                filteredForOptions = filteredForOptions.filter(item => item.CutlistName === selectedCutlist);
            }

            if (selectedMachine) {
                filteredForOptions = filteredForOptions.filter(item => item.MachineName === selectedMachine);
            }

            if (selectedShapes.length) {
                filteredForOptions = filteredForOptions.filter(item => selectedShapes.includes(item.Shape));
            }

            // Apply column filters to the dataset
            $('.column-filter').each(function() {
                const column = $(this).data('column');
                const value = $(this).val().toLowerCase();

                if (value) {
                    filteredForOptions = filteredForOptions.filter(item => {
                        const itemValue = (item[column] || '').toString().toLowerCase();

                        // Check if value starts with '^' to indicate "begins with"
                        if (value.startsWith('^')) {
                            return itemValue.startsWith(value.slice(1));
                        }

                        // Default to contains
                        return itemValue.includes(value);
                    });
                }
            });

            // Populate location filter - show locations from filtered data
            const locationSelect = $('#location-filter');
            const locations = [...new Set(
                // If location is already selected, show all possible locations
                selectedLocation ?
                    inventoryData.map(item => item.Location).filter(Boolean) :
                    filteredForOptions.map(item => item.Location).filter(Boolean)
            )];

            // Keep current selection
            const currentLoc = locationSelect.val();
            locationSelect.find('option:not(:first)').remove();
            locations.sort().forEach(location => {
                locationSelect.append(`<option value="${location}">${location}</option>`);
            });
            if (currentLoc && locations.includes(currentLoc)) {
                locationSelect.val(currentLoc);
            }

            // Populate cutlist filter - show cutlists from filtered data
            const cutlistSelect = $('#cutlist-filter');
            const cutlists = [...new Set(
                // If cutlist is already selected, show all possible cutlists
                selectedCutlist ?
                    inventoryData.map(item => item.CutlistName).filter(Boolean) :
                    filteredForOptions.map(item => item.CutlistName).filter(Boolean)
            )];

            const currentCutlist = cutlistSelect.val();
            cutlistSelect.find('option:not(:first)').remove();
            cutlists.sort().forEach(cutlist => {
                cutlistSelect.append(`<option value="${cutlist}">${cutlist}</option>`);
            });
            if (currentCutlist && cutlists.includes(currentCutlist)) {
                cutlistSelect.val(currentCutlist);
            }

            // Populate machine filter - show machines from filtered data
            const machineSelect = $('#machine-filter');
            const machines = [...new Set(
                // If machine is already selected, show all possible machines
                selectedMachine ?
                    inventoryData.map(item => item.MachineName).filter(Boolean) :
                    filteredForOptions.map(item => item.MachineName).filter(Boolean)
            )];

            const currentMachine = machineSelect.val();
            machineSelect.find('option:not(:first)').remove();
            machines.sort().forEach(machine => {
                machineSelect.append(`<option value="${machine}">${machine}</option>`);
            });
            if (currentMachine && machines.includes(currentMachine)) {
                machineSelect.val(currentMachine);
            }

            // Update shape filter with filtered data
            populateShapeFilter(filteredForOptions);
        }

        function populateShapeFilter(filteredForShapes) {
            // If no data was passed, use the full inventory filtered by current selections
            if (!filteredForShapes) {
                filteredForShapes = [...inventoryData];

                if (selectedLocation) {
                    filteredForShapes = filteredForShapes.filter(item => item.Location === selectedLocation);
                }

                if (selectedCutlist) {
                    filteredForShapes = filteredForShapes.filter(item => item.CutlistName === selectedCutlist);
                }

                if (selectedMachine) {
                    filteredForShapes = filteredForShapes.filter(item => item.MachineName === selectedMachine);
                }
            }

            // Get unique shapes from filtered data
            const shapes = [...new Set(filteredForShapes.map(item => item.Shape).filter(Boolean))];
            const container = $('#shape-filters');
            container.empty();

            shapes.sort().forEach(shape => {
                container.append(`
            <div class="shape-filter">
                <input type="checkbox" id="shape-${shape}"
                       class="shape-checkbox" value="${shape}">
                <label for="shape-${shape}">${shape}</label>
            </div>
        `);
            });
        }

        function applyFilters() {
            // Start with the full inventory data each time
            filteredData = [...inventoryData];

            // Apply location filter
            if (selectedLocation) {
                filteredData = filteredData.filter(item => item.Location === selectedLocation);
            }

            // Apply cutlist filter
            if (selectedCutlist) {
                filteredData = filteredData.filter(item => item.CutlistName === selectedCutlist);
            }

            // Apply machine filter
            if (selectedMachine) {
                filteredData = filteredData.filter(item => item.MachineName === selectedMachine);
            }

            // Apply shape filters
            if (selectedShapes.length) {
                filteredData = filteredData.filter(item => selectedShapes.includes(item.Shape));
            }

            // Apply column filters
            $('.column-filter').each(function() {
                const column = $(this).data('column');
                const value = $(this).val().toLowerCase();

                if (value) {
                    filteredData = filteredData.filter(item => {
                        const itemValue = (item[column] || '').toString().toLowerCase();

                        // Check if value starts with '^' to indicate "begins with"
                        if (value.startsWith('^')) {
                            return itemValue.startsWith(value.slice(1));
                        }

                        // Default to contains
                        return itemValue.includes(value);
                    });
                }
            });

            // After filtering, sort the data
            sortAndRenderData();
        }

        function sortAndRenderData() {
            // Sort the already filtered data
            filteredData.sort((a, b) => {
                let aVal = a[currentSortColumn];
                let bVal = b[currentSortColumn];

                // Special handling for Original date column
                if (currentSortColumn === 'Original' || currentSortColumn === 'Due') {
                    aVal = aVal ? new Date(aVal) : new Date(0);
                    bVal = bVal ? new Date(bVal) : new Date(0);
                }
                // Handle numeric values
                else if (
                    currentSortColumn === 'LengthInches' ||
                    currentSortColumn === 'Quantity' ||
                    currentSortColumn === 'WeightLbs' ||
                    currentSortColumn === 'Valuation'
                ) {
                    aVal = parseFloat(aVal) || 0;
                    bVal = parseFloat(bVal) || 0;
                }
                // Handle all string values including CutlistName, CutlistBarcode, MachineName
                else {
                    aVal = (aVal || '').toString().toLowerCase();
                    bVal = (bVal || '').toString().toLowerCase();
                }

                // Compare the values based on sort direction
                if (aVal === bVal) return 0;

                const comparison = aVal > bVal ? 1 : -1;
                return currentSortDirection === 'asc' ? comparison : -comparison;
            });

            // Render the sorted and filtered data
            renderTable();
            updateSummaryStats();
        }

        // Find this part in your renderTable function:
        function renderTable() {
            const tbody = $('#inventory-body');
            tbody.empty();
            const hasAccess = localStorage.getItem('valuationAccess') === 'true';

            filteredData.forEach((item, index) => {
                const rowClass = `${item.IsReserved ? 'reserved' : ''} inventory-row`;
                const valuationCell = hasAccess ?
                    `<td>$${item.Valuation || '-'}</td>` :
                    `<td><button class="btn btn-sm btn-outline-secondary" onclick="showPasswordModal(event)">****</button></td>`;

                // Handle length display based on shape
                let lengthDisplay = '-';
                if (item.LengthInches) {
                    if (item.Shape === 'CP' || item.Shape === 'PL') {
                        // For CP or PL shapes, just show the LengthInches
                        lengthDisplay = `${(item.LengthInches % 1 < 0.003 ? Math.round(item.LengthInches) : item.LengthInches)}&quot;`;
                    } else {
                        // For other shapes, show LengthFeetInches with LengthInches in parentheses
                        lengthDisplay = `${item.LengthFeetInches} (${(item.LengthInches % 1 < 0.003 ? Math.round(item.LengthInches) : item.LengthInches)}&quot;)`;
                    }
                }

                // Add asterisk to barcode if item has serial numbers
                const barcodeDisplay = item.CutlistBarcode || '-';
                const barcodeWithAsterisk = item.HasSerialNumbers ?
                    `${barcodeDisplay} <span class="text-danger">*</span>` :
                    barcodeDisplay;

                const row = `
        <tr class="${rowClass}" data-id="${item.ItemID}" title="ItemID: ${item.ItemID}">
            <td class="nowrap">${item.OriginalDate || '-'}</td>
            <td>${item.Shape || '-'}</td>
            <td>${(item.DimensionString?.length > 15 ? item.DimensionString.slice(0, 15) + '...' : item.DimensionString) || '-'}</td>
            <td>${lengthDisplay}</td>
            <td>${item.Quantity || '0'}</td>
            <td>${numberWithCommas(item.WeightLbs || 0)}</td>
            <td class="nowrap">${item.OnOrder === 1 ? `${item.DeliveryDate || '-'}` : (item.Location || '-')}</td>
            <td>${item.SecondaryLocation || '-'}</td>
            <td>${item.HeatNo || '-'}</td>
            <td>${item.JobNumberReserved || '-'}</td>
            <td>${item.PONumber || '-'}</td>
            <td>${(item.Supplier?.length > 12 ? item.Supplier.slice(0, 12) + '...' : item.Supplier) || '-'}</td>
            <td>${item.CostCode || '-'}</td>
            ${valuationCell}
            <td class="nowrap">${item.CutlistName || '-'}</td>
            <td class="nowrap">${barcodeWithAsterisk}</td>
            <td class="nowrap">${item.MachineName || '-'}</td>
        </tr>
    `;
                tbody.append(row);
            });
        }

        function updateSummaryStats() {
            const hasAccess = localStorage.getItem('valuationAccess') === 'true';
            const totalItems = filteredData.reduce((sum, item) => sum + parseFloat(item.Quantity || 0), 0);
            const totalWeight = filteredData.reduce((sum, item) => sum + parseFloat(item.WeightLbs || 0), 0);
            const totalValue = filteredData.reduce((sum, item) => sum + parseFloat(item.Valuation || 0), 0);
            const totalLineItems = filteredData.length;

            $('#total-items').text(numberWithCommas(totalItems));
            $('#total-weight').text(numberWithCommas(totalWeight.toFixed(2)));

            // Show valuation only if user has access
            if (hasAccess) {
                $('#total-value').text('$' + numberWithCommas(totalValue.toFixed(2)));
            } else {
                $('#total-value').html('<button class="btn btn-sm btn-outline-secondary" onclick="showPasswordModal(event)">Click to View Valuation</button>');
            }

            $('#total-line-items').text(numberWithCommas(totalLineItems));
        }

        function numberWithCommas(x) {
            return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }

        $('#reset-filters').on('click', resetFilters);

        function resetFilters() {
            // Reset all filter selections
            $('#location-filter').val('');
            $('#cutlist-filter').val('');
            $('#machine-filter').val('');
            $('.column-filter').val('');

            // Reset filter variables
            selectedLocation = '';
            selectedCutlist = '';
            selectedMachine = '';
            selectedShapes = [];

            // Clear shape checkboxes
            $('.shape-checkbox').prop('checked', false);

            // Re-populate all filters with full dataset options
            populateFilters();

            // Apply filters (which will now show all data)
            applyFilters();
        }

        // Event Handlers
        $('.sortable').click(function() {
            const column = $(this).data('sort');
            $('.sortable').removeClass('asc desc');

            if (currentSortColumn === column) {
                currentSortDirection = currentSortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                currentSortColumn = column;
                currentSortDirection = 'asc';
            }

            $(this).addClass(currentSortDirection);

            // First reapply all filters to get fresh filtered data
            applyFilters();
        });

        $('#location-filter').on('change', function() {
            selectedLocation = $(this).val();
            populateFilters();
            applyFilters();
        });

        $('#cutlist-filter').on('change', function() {
            selectedCutlist = $(this).val();
            populateFilters();
            applyFilters();
        });

        $('#machine-filter').on('change', function() {
            selectedMachine = $(this).val();
            populateFilters();
            applyFilters();
        });

        $(document).on('change', '.shape-checkbox', function() {
            selectedShapes = $('.shape-checkbox:checked')
                .map(function() { return $(this).val(); })
                .get();
            populateFilters(); // Update filter options based on selected shapes
            applyFilters();
        });

        $('.column-filter').on('input', function() {
            applyFilters();
        });

        // Add this function near the top of your script section
        function inchesToFeetInches(inches) {
            if (!inches || isNaN(inches)) return '-';

            // Convert to total inches and handle negative values
            const totalInches = Math.abs(parseFloat(inches));
            const feet = Math.floor(totalInches / 12);
            const remainingInches = totalInches % 12;

            // Get the whole inches part
            const wholeInches = Math.floor(remainingInches);

            // Calculate the fraction (in 16ths)
            const fraction = Math.round((remainingInches - wholeInches) * 16);

            // Format the output
            let result = '';

            // Add feet if there are any
            if (feet > 0) {
                result += feet + '\'';
            }

            // Add inches if there are any or if feet is zero
            if (wholeInches > 0 || (feet === 0 && fraction === 0)) {
                if (feet > 0) result += '-';
                result += wholeInches;
            }

            // Add fraction if there is one
            if (fraction > 0) {
                // Simplify fraction
                let numerator = fraction;
                let denominator = 16;

                // Find GCD
                const gcd = (a, b) => b === 0 ? a : gcd(b, a % b);
                const divisor = gcd(numerator, denominator);

                numerator /= divisor;
                denominator /= divisor;

                // Add space if there are whole inches
                if (wholeInches > 0) result += ' ';

                result += numerator + '/' + denominator;
            }

            return inches < 0 ? '-' + result : result;
        }

        $(document).on('click', '.inventory-row', function() {
            const itemId = $(this).data('id');

            // Show loading spinner
            $('#modal-detail-content').hide();
            $('#modal-loading').show();

            // Open the modal immediately with loading state
            new bootstrap.Modal(document.getElementById('detailModal')).show();

            // Fetch the complete item details
            $.ajax({
                url: 'ajax_get_inventory_item.php',
                method: 'GET',
                data: {
                    itemId: itemId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        updateModalContent(response.item, response.serialNumbers);
                    } else {
                        $('#modal-error').text(response.error).show();
                        $('#modal-loading').hide();
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('AJAX Error:', textStatus, errorThrown);
                    $('#modal-error').text('Error loading item details').show();
                    $('#modal-loading').hide();
                }
            });
        });

        function updateModalContent(item, serialNumbers) {
            const hasAccess = localStorage.getItem('valuationAccess') === 'true';

            // Populate modal with item details
            $('#modal-shape').text(item.Shape || '-');
            $('#modal-dimensions').text(item.DimensionString || '-');
            $('#modal-imperial-size').text(item.DimensionSizesImperial || '-');

            // Handle length display based on shape
            if (item.LengthInches) {
                if (item.Shape === 'CP' || item.Shape === 'PL') {
                    // For CP or PL shapes, just show the LengthInches
                    $('#modal-length').html(`${item.LengthInches} inches`);
                } else {
                    // For other shapes, show LengthFeetInches with LengthInches in parentheses
                    $('#modal-length').html(`${item.LengthFeetInches} (${item.LengthInches} inches)`);
                }
            } else {
                $('#modal-length').text('-');
            }

            $('#modal-weight').text(item.WeightLbs ? `${numberWithCommas(item.WeightLbs)} lbs` : '-');
            $('#modal-quantity').text(item.Quantity || '0');
            $('#modal-location').text(item.Location || '-');
            $('#modal-secondary-location').text(item.SecondaryLocation || '-');
            $('#modal-heat').text(item.HeatNo || '-');
            $('#modal-po').text(item.PONumber || '-');
            $('#modal-bol').text(item.BillOfLadingNo || '-');
            $('#modal-supplier').text(item.Supplier || '-');
            $('#modal-reserved').text(item.JobNumberReserved || '-');
            $('#modal-reserve-date').text(item.ReserveDate ? new Date(item.ReserveDate).toLocaleDateString() : '-');
            $('#modal-previous-job').text(item.WasReservedToEarlier || '-');
            $('#modal-cost-code').text(item.CostCode || '-');
            $('#modal-part-number').text(item.PartNumber || '-');
            $('#modal-reference').text(item.ReferenceNumber || '-');
            $('#modal-delivery-date').text(item.DeliveryDate ? new Date(item.DeliveryDate).toLocaleDateString() : '-');
            $('#modal-originaldate').text(item.OriginalDate ? `${item.OriginalDate}` : '-');

            // Add fields
            $('#modal-cutlist-name').text(item.CutlistName || '-');
            $('#modal-cutlist-barcode').text(item.CutlistBarcode || '-');
            $('#modal-machine-name').text(item.MachineName || '-');

            // Handle valuation with password protection
            if (hasAccess) {
                $('#modal-valuation').text(item.Valuation ? `${item.Valuation}` : '-');
            } else {
                $('#modal-valuation').html('<button class="btn btn-sm btn-outline-secondary" onclick="showPasswordModal(event)">Click to View</button>');
            }

            // Display serial numbers
            if (serialNumbers && serialNumbers.length > 0) {
                const serialList = $('<div class="mt-3"></div>');
                serialNumbers.forEach(serialNumber => {
                    serialList.append(`<span class="badge bg-secondary me-1 mb-1">${serialNumber}</span>`);
                });
                $('#modal-serial-numbers').html(serialList);
            } else {
                $('#modal-serial-numbers').html('<p class="text-muted">No serial numbers found</p>');
            }

            // Hide loading indicator and show content
            $('#modal-loading').hide();
            $('#modal-error').hide();
            $('#modal-detail-content').show();
        }

        // Export functions
        function exportToCSV() {
            const headers = [
                'Original','Shape', 'Dimensions', 'Length', 'Quantity', 'Weight',
                'Location', 'Secondary Location', 'Heat #', 'Reserved For',
                'PO #', 'Supplier', 'Cost Code', 'Valuation', 'Part Number',
                'CutList Name', 'CutList Barcode', 'Machine Name'
            ];

            let csvContent = headers.join(',') + '\n';

            filteredData.forEach(item => {
                const row = [
                    item.OriginalDate || '',
                    item.Shape || '',
                    item.DimensionString || '',
                    item.LengthInches || '',
                    item.Quantity || '',
                    item.WeightLbs || '',
                    item.Location || '',
                    item.SecondaryLocation || '',
                    item.HeatNo || '',
                    item.JobNumberReserved || '',
                    item.PONumber || '',
                    item.Supplier || '',
                    item.CostCode || '',
                    item.Valuation || '',
                    item.PartNumber || '',
                    item.CutlistName || '',
                    item.CutlistBarcode || '',
                    item.MachineName || '',
                ].map(field => `"${field}"`).join(',');

                csvContent += row + '\n';
            });

            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            if (link.download !== undefined) {
                const url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', 'inventory_export.csv');
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        }

        function exportToJSON() {
            const exportData = filteredData.map(item => ({
                original: item.OriginalDate,
                shape: item.Shape,
                dimensions: item.DimensionString,
                length: item.LengthInches,
                quantity: item.Quantity,
                weight: item.WeightLbs,
                location: item.Location,
                secondaryLocation: item.SecondaryLocation,
                heatNo: item.HeatNo,
                reservedFor: item.JobNumberReserved,
                poNumber: item.PONumber,
                supplier: item.Supplier,
                costCode: item.CostCode,
                valuation: item.Valuation,
                partNumber: item.PartNumber,
                cutlistName: item.CutlistName,
                cutlistBarcode: item.CutlistBarcode,
                machineName: item.MachineName
            }));

            const blob = new Blob([JSON.stringify(exportData, null, 2)],
                { type: 'application/json;charset=utf-8;' });
            const link = document.createElement('a');
            if (link.download !== undefined) {
                const url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', 'inventory_export.json');
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        }

        // Add event listeners for export buttons
        $('#exportCSV').click(exportToCSV);
        $('#exportJSON').click(exportToJSON);
        $('input[name="orderStatus"]').on('change', function() {
            // Reset all filter selections
            $('#location-filter').val('');
            $('#cutlist-filter').val('');
            $('#machine-filter').val('');

            // Clear shape checkboxes
            $('.shape-checkbox').prop('checked', false);

            // Reset filter variables
            selectedLocation = '';
            selectedCutlist = '';
            selectedMachine = '';
            selectedShapes = [];

            $('#locationhead').text(($('input[name="orderStatus"]:checked').val() == 1) ? 'Due' : 'Location');

            // Clear column filters
            $('.column-filter').val('');

            // Fetch new data
            fetchInventory();
        });

        // Initial load
        fetchInventory();
    });

    window.showPasswordModal = (event) => {
        // Prevent click from propagating to row (which would open the detail modal)
        if (event) {
            event.stopPropagation();
        }

        $('#password-error').addClass('d-none');
        $('#valuation-password').val('');
        const modal = new bootstrap.Modal(document.getElementById('valuationPasswordModal'));
        modal.show();
    };


    $('#submit-password').click(() => {
        const password = $('#valuation-password').val();
        if (password === 'admin123') {
            localStorage.setItem('valuationAccess', 'true');
            // Close the password modal
            const passwordModal = bootstrap.Modal.getInstance(document.getElementById('valuationPasswordModal'));
            if (passwordModal) {
                passwordModal.hide();
            }
            // Refresh the data display without reloading the page
            renderTable();
            updateSummaryStats();
        } else {
            $('#password-error').text('Invalid password').removeClass('d-none');
        }
    });

    $('#valuation-password').on('keypress', (e) => {
        if (e.key === 'Enter') {
            $('#submit-password').click();
        }
    });

    $('#valuationPasswordModal').on('shown.bs.modal', () => {
        $('#valuation-password').focus();
    });

</script>
</body>
</html>