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
            max-height: calc(100vh - 250px);
            overflow-y: auto;
            padding-right: 10px;
        }

        .select2-container .select2-selection--multiple {
            min-height: 100px;
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
            <select id="location-filter" class="form-select" multiple>
                <!-- Locations will be populated here -->
            </select>
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
                <th class="sortable" data-sort="PartNumber">PNUM</th>
                <th class="sortable" data-sort="SerialNumbers">Serial</th>
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
                <th></th>
                <th></th>
                <th><input type="text" class="column-filter" data-column="Serial" placeholder="Serial #"></th>
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
                                    <td class="modal-label">Serial Numbers:</td>
                                    <td id="modal-serial-numbers"></td>
                                </tr>
                            </table>
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

<div class="modal fade" id="valuationPasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Enter Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger d-none" id="password-error"></div>
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
        let selectedLocations = [];
        let selectedShapes = [];

        function fetchInventory() {
            $.ajax({
                url: 'ajax_get_inventory.php',
                method: 'GET',
                data: {
                    locations: selectedLocations.length ? JSON.stringify(selectedLocations) : null,
                    onOrder: $('input[name="orderStatus"]:checked').val()
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        inventoryData = response.inventory;
                        if (!selectedLocations.length) {
                            populateLocationFilters(response.locations);
                            populateShapeFilter();
                        }
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

        function populateLocationFilters(locations) {
            const locationSelect = $('#location-filter');
            locationSelect.empty();

            locations.forEach(location => {
                if (location) {
                    locationSelect.append(`<option value="${location}">${location}</option>`);
                }
            });

            // Initialize Select2 for locations
            locationSelect.select2({
                placeholder: 'Select Locations',
                allowClear: true,
                width: '100%'
            });
        }

        function populateShapeFilter() {
            const shapes = [...new Set(inventoryData.map(item => item.Shape).filter(Boolean))];
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
                        const itemValue = column === 'Serial' ?
                            (item.SerialNumbers || '').toLowerCase() :
                            (item[column] || '').toString().toLowerCase();

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
                else if (!isNaN(aVal) && !isNaN(bVal)) {
                    aVal = parseFloat(aVal) || 0;
                    bVal = parseFloat(bVal) || 0;
                } else {
                    // Handle string values
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

        function renderTable() {
            const tbody = $('#inventory-body');
            tbody.empty();
            const hasAccess = localStorage.getItem('valuationAccess') === 'true';

            filteredData.forEach((item, index) => {
                const rowClass = `${item.IsReserved ? 'reserved' : ''} inventory-row`;
                const valuationCell = hasAccess ?
                    `<td>$${item.Valuation || '-'}</td>` :
                    `<td>****</td>`;

                const row = `
                        <tr class="${rowClass}" data-index="${index}" title="ItemID: ${item.ItemID}">
                            <td class="nowrap">${item.OriginalDate || '-'}</td>
                            <td>${item.Shape || '-'}</td>
                            <td>${(item.DimensionString?.length > 15 ? item.DimensionString.slice(0, 15) + '...' : item.DimensionString) || '-'}</td>
                            <td title="${item.LengthInches || 0}">${item.LengthInches ? (item.LengthInches % 1 < 0.003 ? Math.round(item.LengthInches) : item.LengthInches) + "&quot;" : '-'}</td>
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
                            <td class="nowrap">${item.PartNumber || '-'}</td>
                            <td>${item.SerialNumbers || '-'}</td>
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
            const totalLineItems = filteredData.length; // Add this line

            $('#total-items').text(numberWithCommas(totalItems));
            $('#total-weight').text(numberWithCommas(totalWeight.toFixed(2)));
            $('#total-value').html(hasAccess ?
                '$' + numberWithCommas(totalValue.toFixed(2)) :
                '<button class="btn btn-sm btn-outline-secondary" onclick="showPasswordModal()">Click to View Valuation</button>');
            $('#total-line-items').text(numberWithCommas(totalLineItems));

        }

        function numberWithCommas(x) {
            return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
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
            selectedLocations = $(this).val() || [];
            fetchInventory();
        });

        $(document).on('change', '.shape-checkbox', function() {
            selectedShapes = $('.shape-checkbox:checked')
                .map(function() { return $(this).val(); })
                .get();
            applyFilters();
        });

        $('.column-filter').on('input', function() {
            applyFilters();
        });

        $(document).on('click', '.inventory-row', function() {
            const index = $(this).data('index');
            const item = filteredData[index];
            const hasAccess = localStorage.getItem('valuationAccess') === 'true';

            // Populate modal with all item details
            $('#modal-shape').text(item.Shape || '-');
            $('#modal-dimensions').text(item.DimensionString || '-');
            $('#modal-imperial-size').text(item.DimensionSizesImperial || '-');
            $('#modal-length').text(item.LengthInches ? `${item.LengthInches} inches` : '-');
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
            if(hasAccess){
                $('#modal-valuation').text(item.Valuation ? `$${item.Valuation}` : '-');
            }
            $('#modal-serial-numbers').text(item.SerialNumbers || '-');
            $('#modal-originaldate').text(item.OriginalDate ? `${item.OriginalDate}` : '-');

            new bootstrap.Modal(document.getElementById('detailModal')).show();
        });

        // Export functions
        function exportToCSV() {
            const headers = [
                'Original','Shape', 'Dimensions', 'Length', 'Quantity', 'Weight',
                'Location', 'Secondary Location', 'Heat #', 'Reserved For',
                'PO #', 'Supplier', 'Cost Code', 'Valuation','Serial Numbers'
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
                    item.SerialNumbers || ''
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
                serialNumbers: item.SerialNumbers
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
            // Clear location selections
            $('#location-filter').val(null).trigger('change');

            // Clear shape checkboxes
            $('.shape-checkbox').prop('checked', false);
            selectedShapes = [];

            $('#locationhead').text(($('input[name="orderStatus"]:checked').val() == 1) ? 'Due' : 'Location');

            // Clear column filters
            $('.column-filter').val('');

            // Reset arrays
            selectedLocations = [];

            // Fetch new data
            fetchInventory();
        });

        // Initial load
        fetchInventory();
    });
    window.showPasswordModal = () => {
        $('#password-error').addClass('d-none');
        $('#valuation-password').val('');
        const modal = new bootstrap.Modal('#valuationPasswordModal');
        modal.show();
    };

    $('#submit-password').click(() => {
        const password = $('#valuation-password').val();
        if (password === 'admin123') {
            localStorage.setItem('valuationAccess', 'true');
            location.reload();
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