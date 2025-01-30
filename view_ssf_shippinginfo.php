<!DOCTYPE html>
<html>
<head>
    <title>Shipping Information SSF</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .sticky-header thead {
            position: sticky;
            top: 0;
            z-index: 1;
            background-color: #f8f9fa;
        }
        .table-container {
            max-height: calc(100vh - 2rem);
            overflow-y: auto;
        }
        .number-cell {
            text-align: right;
        }
        .weight-total {
            font-size: 0.8em;
            color: #666;
            display: block;
        }
        .header-complete .weight-total {
            color: #e2e2e2;
        }
        .header-failed .weight-total {
            color: #ffe0e0;
        }
        .complete {
            background-color: #d4edda !important;
        }
        .failed {
            background-color: #f8d7da !important;
        }
        .header-complete {
            background-color: #198754 !important;
            color: white !important;
        }
        .header-failed {
            background-color: #dc3545 !important;
            color: white !important;
        }
        .summary-stats {
            padding: 0.75rem;
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            font-size: 0.9rem;
        }
        .stat-item {
            display: inline-block;
            margin-right: 2rem;
            font-weight: 500;
        }
        .stat-value {
            font-weight: 600;
            color: #99332b;
        }
        .warning td{
            background-color: #dc3545 !important;
        }
        .highlight-record {
            background-color: #0d6efd !important;
            color: white !important;
            font-weight: bold;
            box-shadow: 0 0 0 2px #0a58ca !important;
            transition: all 0.3s ease;
        }

        .highlight-record td {
            background-color: #0d6efd !important;
            color: white !important;
        }

        /* Make sure links in highlighted rows remain visible */
        .highlight-record a {
            color: white !important;
            text-decoration: underline;
        }
        .markdown-body {
            box-sizing: border-box;
            min-width: 200px;
            max-width: 980px;
            margin: 0 auto;
            padding: 45px;
        }
        @media (max-width: 767px) {
            .markdown-body {
                padding: 15px;
            }
        }
        .details-row {
            transition: all 0.3s ease;
        }
        .details-row td {
            border-top: none;
        }
    </style>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/github-markdown-css/5.2.0/github-markdown.min.css">
    <style>
        .card-header {
            background-color: #99332b !important;
            color: white !important;
        }
        .btn-outline-custom {
            color: #99332b;
            border-color: #99332b;
        }
        .btn-outline-custom:hover {
            background-color: #99332b;
            color: white;
        }
    </style>
</head>
<body class="bg-light">
<div class="container-fluid">
    <h4 class="mb-0 pt-2 d-flex justify-content-between align-items-center">
        Southland Steel Fabricators Post Fabrication Status
        <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#helpModal">
            <i class="bi bi-question-circle me-1"></i>
            View Guide
        </button>
    </h4>
    <div class="card shadow">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">

            <div id="jobFilter" class="d-flex gap-2 flex-wrap"></div>
        </div>
        <div class="summary-stats d-flex justify-content-between align-items-center">
            <div class="d-flex gap-4">
                <div class="stat-item">
                    Line Items: <span id="totalLineItems" class="stat-value">0</span>
                </div>
                <div class="stat-item">
                    Total Members: <span id="totalMembers" class="stat-value">0</span>
                </div>
            </div>
            <div class="status-filters d-flex gap-2">
                <button class="btn btn-outline-secondary btn-sm" data-status="failed">Failed QC</button>
                <button class="btn btn-outline-secondary btn-sm" data-status="lts">LTS</button>
                <button class="btn btn-outline-secondary btn-sm" data-status="black">BLACK</button>
                <button class="btn btn-outline-secondary btn-sm" data-status="rts">RTS</button>
                <button class="btn btn-outline-secondary btn-sm" id="resetFilters">Reset</button>
            </div>
        </div>
        <div class="workweek-filter p-2 bg-light border-bottom">
            <div id="workWeekFilter" class="d-flex gap-2 flex-wrap"></div>
        </div>
        <div class="card-body p-0">
            <div class="table-container">
                <table id="dataTable" class="table table-striped table-bordered table-hover mb-0 sticky-header">
                    <thead class="table-light">
                    <tr>
                        <th style="width: 80px">Job</th>
                        <th style="width: 120px">Sequence</th>
                        <th style="width: 80px">Lot</th>
                        <th>Main Mark</th>
                        <th class="number-cell">Weight Each</th>
                        <th class="number-cell">Required Qty</th>
                        <th id="headerFailedQC" class="number-cell">Failed QC</th>
                        <th id="headerFinalQC" class="number-cell">Final QC
                            <span id="totalFinalQC" class="weight-total"></span>
                        </th>
                        <th id="headerLoaded" class="number-cell">At Galv
                            <span id="totalLoaded" class="weight-total"></span>
                        </th>
                        <th id="headerReturned" class="number-cell">Galv Complete
                            <span id="totalReturned" class="weight-total"></span>
                        </th>
                        <th class="number-cell">Jobsite
                            <span id="totalShipped" class="weight-total"></span>
                        </th>
                    </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="shippingModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Shipping Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="header-info mb-3 p-3 bg-light border rounded">
                    <div class="row g-2">
                        <div class="col-md-3"><strong>Job Number:</strong> <span id="shipJobNumber"></span></div>
                        <div class="col-md-3"><strong>Main Mark:</strong> <span id="shipMainMark"></span></div>
                        <div class="col-md-3"><strong>Sequence:</strong> <span id="shipSequence"></span></div>
                        <div class="col-md-3"><strong>Lot:</strong> <span id="shipLot"></span></div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                        <tr>
                            <th>Date</th>
                            <th>Truck #</th>
                            <th>Main Mark</th>
                            <th>Piece Mark</th>
                            <th>Work Package</th>
                            <th>Firm Name</th>
                            <th>Loaded Qty</th>
                            <th id="returnedHeader">Returned Qty</th>
                        </tr>
                        </thead>
                        <tbody id="shippingModalBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="qcModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">QC Inspection Details</h5>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="showAllRecords">
                    <label class="form-check-label" for="showAllRecords">Show All Records</label>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="header-info mb-3 p-3 bg-light border rounded">
                    <div class="row g-2">
                        <div class="col-md-3"><strong>Job Number:</strong> <span id="qcJobNumber"></span></div>
                        <div class="col-md-3"><strong>Main Mark:</strong> <span id="qcMainMark"></span></div>
                        <div class="col-md-3"><strong>Piece Mark:</strong> <span id="qcPieceMark"></span></div>
                        <div class="col-md-3"><strong>Sequence:</strong> <span id="qcSequence"></span></div>
                        <div class="col-md-3"><strong>Lot:</strong> <span id="qcLot"></span></div>
                        <div class="col-md-3"><strong>Work Package:</strong> <span id="qcWorkPackage"></span></div>
                        <div class="col-md-3"><strong>Load Number:</strong> <span id="qcLoadNumber"></span></div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                        <tr>
                            <th>Test Record ID</th>
                            <th>P/F</th>
                            <th>Main Mark</th>
                            <th>Piece Mark</th>
                            <th>Date/Time</th>
                            <th>Quantity</th>
                            <th>Inspection Type</th>
                            <th>Inspector</th>
                            <th>Relationships</th>
                        </tr>
                        </thead>
                        <tbody id="qcModalBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="helpModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Grid Structures Post Fabrication Status Guide</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="guideContent" class="markdown-body">
                    <!-- The markdown content will be inserted here -->
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/marked/4.0.2/marked.min.js"></script>

<script>
    let globalData = [];
    const filterState = {
        sequence: '', // Changed from 'job' to 'sequence'
        workWeek: '',
        status: ''
    };

    let buttonStatusCache = new Map();
    let originalSequenceOrder = [];

    function updateSummaryStats() {
        const visibleRows = Array.from(document.querySelectorAll('#dataTable tbody tr'))
            .filter(row => row.style.display !== 'none');

        const totalLineItems = visibleRows.length;
        const totalMembers = visibleRows.reduce((sum, row) => {
            return sum + parseInt(row.dataset.required || 0);
        }, 0);

        document.getElementById('totalLineItems').textContent = totalLineItems.toLocaleString();
        document.getElementById('totalMembers').textContent = totalMembers.toLocaleString();
    }

    function updateFilters(type, value) {
        if (value === filterState[type]) {
            filterState[type] = '';
        } else {
            filterState[type] = value;
        }

        applyAllFilters();
        updateButtonStates();
    }

    function applyAllFilters() {
        const rows = document.querySelectorAll('#dataTable tbody tr');
        rows.forEach(row => {
            // Get the sequence ID directly from the row's dataset
            const sequenceId = row.dataset.sequenceId;

            // Check if the sequence matches
            const sequenceMatch = !filterState.sequence || filterState.sequence === sequenceId;
            const workWeekMatch = !filterState.workWeek || row.dataset.workWeek === filterState.workWeek;

            let statusMatch = true;
            if (filterState.status) {
                switch(filterState.status) {
                    case 'failed':
                        statusMatch = parseInt(row.dataset.failed) > 0;
                        break;
                    case 'lts':
                        statusMatch = parseInt(row.dataset.finalQC) > parseInt(row.dataset.loaded);
                        break;
                    case 'black':
                        statusMatch = parseInt(row.dataset.loaded) > parseInt(row.dataset.returned);
                        break;
                    case 'rts':
                        const returned = parseInt(row.dataset.returned) || 0;
                        const shipped = parseInt(row.dataset.shipped) || 0;
                        statusMatch = returned > shipped;
                        break;
                }
            }

            row.style.display = (sequenceMatch && workWeekMatch && statusMatch) ? '' : 'none';
        });

        updateTotals();
        updateHeaderStatus();
        updateSummaryStats();
    }

    function updateButtonStates() {
        // Update sequence filter buttons (just handle active state, let updateJobButtonStatus handle colors)
        document.querySelectorAll('#jobFilter button').forEach(btn => {
            const isActive = btn.dataset.sequenceKey === filterState.sequence;
            btn.classList.toggle('active', isActive);
            btn.classList.toggle('btn-light', isActive);
        });

        // Rest of your existing updateButtonStates code remains the same
        // Update status filter buttons
        document.querySelectorAll('.status-filters button').forEach(btn => {
            if (btn.id === 'resetFilters') return;

            const status = btn.dataset.status;
            const isActive = status === filterState.status;
            btn.classList.toggle('active', isActive);
            btn.disabled = false;  // Enable all status buttons initially

            // Only check if buttons should be disabled if they're not active
            if (!isActive) {
                const wouldMatch = Array.from(document.querySelectorAll('#dataTable tbody tr'))
                    .some(row => {
                        if (row.style.display === 'none') return false;

                        const data = row.dataset;
                        switch(status) {
                            case 'failed':
                                return parseInt(data.failed) > 0;
                            case 'lts':
                                return parseInt(data.finalQC) > parseInt(data.loaded);
                            case 'black':
                                return parseInt(data.loaded) > parseInt(data.returned);
                            case 'rts':
                                const returned = parseInt(data.returned) || 0;
                                const shipped = parseInt(data.shipped) || 0;
                                return returned > shipped;
                        }
                    });
                btn.disabled = !wouldMatch;
            }
        });

        // Update workweek filter buttons
        document.querySelectorAll('#workWeekFilter button').forEach(btn => {
            const isActive = btn.dataset.workweek === filterState.workWeek;
            btn.classList.toggle('active', isActive);

            if (btn.dataset.workweek === '') return;

            const isAvailable = Array.from(document.querySelectorAll('#dataTable tbody tr'))
                .some(row => {
                    const sequenceMatch = !filterState.sequence ||
                        row.dataset.sequenceId === filterState.sequence;
                    return sequenceMatch && row.dataset.workWeek === btn.dataset.workweek;
                });

            btn.disabled = !isAvailable && !isActive;
            btn.classList.toggle('opacity-50', !isAvailable);
        });
    }

    function getStationOrder(item) {
        if (item.FabCompleted < item.RequiredQuantity) return 1;
        if (item.QuantityLoaded < item.RequiredQuantity) return 2;
        if (item.QuantityReturned < item.RequiredQuantity) return 3;
        if (item.QuantityShipped < item.RequiredQuantity) return 4;
        return 5;
    }

    function formatRatio(current, required, maxPossible) {
        if (!current) current = 0;
        const possibleText = maxPossible < required ? ` [${maxPossible}]` : '';
        return `${current}/${required}${possibleText}`;
    }

    function formatWeight(lbs) {
        return `${Math.round(lbs).toLocaleString()} lbs`;
    }

    function updateHeaderStatus() {
        const visibleRows = Array.from(document.querySelectorAll('#dataTable tbody tr')).filter(row => row.style.display !== 'none');
        const headerFailed = document.getElementById('headerFailedQC');
        const headerLoaded = document.getElementById('headerLoaded');
        const headerReturned = document.getElementById('headerReturned');
        const headerFinalQC = document.getElementById('headerFinalQC');

        let hasFailedQC = false;
        let allLoadedComplete = true;
        let allReturnedComplete = true;
        let allFinalQCComplete = true;

        visibleRows.forEach(row => {
            const data = row.dataset;
            if (parseInt(data.failed) > 0) hasFailedQC = true;
            if (parseInt(data.loaded || 0) < parseInt(data.required)) allLoadedComplete = false;
            if (parseInt(data.returned || 0) < parseInt(data.required)) allReturnedComplete = false;
            if (parseInt(data.finalQC || 0) < parseInt(data.required)) allFinalQCComplete = false;
        });

        headerFailed.className = 'number-cell ' + (hasFailedQC ? 'header-failed' : '');
        headerLoaded.className = 'number-cell ' + (allLoadedComplete ? 'header-complete' : '');
        headerReturned.className = 'number-cell ' + (allReturnedComplete ? 'header-complete' : '');
        headerFinalQC.className = 'number-cell ' + (allFinalQCComplete ? 'header-complete' : '');
    }

    function updateTotals() {
        const visibleRows = Array.from(document.querySelectorAll('#dataTable tbody tr')).filter(row => row.style.display !== 'none');
        let totalFinalQC = 0;
        let totalLoaded = 0;
        let totalReturned = 0;
        let totalShipped = 0;
        let totalFailedQC = 0;

        visibleRows.forEach(row => {
            const data = row.dataset;
            const weight = parseFloat(data.weight);
            const required = parseInt(data.required);

            // Get all relevant quantities
            const values = {
                finalQC: parseInt(data.finalQC || 0),
                loaded: parseInt(data.loaded || 0),
                returned: parseInt(data.returned || 0),
                shipped: parseInt(data.shipped || 0)
            };
            const failed = parseInt(data.failed || 0);

            // Calculate remaining quantities respecting the dependency chain:
            // Final QC -> At Galv -> Galv Complete -> (Toe Crack) -> Jobsite
            const remaining = {
                // Can only do up to required amount
                finalQC: Math.max(0, required - values.finalQC),
                // Can only load what has passed Final QC
                loaded: Math.max(0, Math.min(required, values.finalQC) - values.loaded),
                // Can only return what has been loaded
                returned: Math.max(0, Math.min(required, values.loaded) - values.returned),
                // Can only ship what has completed galv AND toe crack (if required)
                shipped: Math.max(0, Math.min(required, values.shipped))
            };

            // Add to totals
            totalFinalQC += weight * remaining.finalQC;
            totalLoaded += weight * remaining.loaded;
            totalReturned += weight * remaining.returned;
            totalShipped += weight * remaining.shipped;
            totalFailedQC += failed;

        });

        // Update display
        document.getElementById('totalLoaded').innerHTML = formatWeight(totalLoaded) + " left<br>to ship to galv";
        document.getElementById('totalReturned').innerHTML = formatWeight(totalReturned) + " left<br>to galvanize";
        document.getElementById('totalShipped').innerHTML = formatWeight(totalShipped) + " ready<br>to ship to jobsite";
        document.getElementById('headerFailedQC').innerHTML = `Failed QC<span class="weight-total">${totalFailedQC} insp</span>`;
    }

    function populateTable(data) {
        const tbody = document.querySelector('#dataTable tbody');
        tbody.innerHTML = '';

        data.sort((a, b) => getStationOrder(a) - getStationOrder(b));

        data.forEach(item => {
            const row = document.createElement('tr');
            row.classList.add(item.Warning || 'e');
            const reqQty = parseInt(item.RequiredQuantity);
            const weight = parseFloat(item.GrossAssemblyWeightEach);
            const loaded = parseInt(item.QuantityLoaded) || 0;
            const returned = parseInt(item.QuantityReturned) || 0;
            const failed = parseInt(item.FailedInspectionTestQuantity) || 0;
            const shipped = parseInt(item.QuantityShipped) || 0;
            const finalQC = parseInt(item.FabCompleted) || 0;

            // Set dataset properties
            row.dataset.productionControlItemId = item.ProductionControlItemID;
            row.dataset.sequenceId = item.SequenceID;
            row.dataset.required = reqQty;
            row.dataset.loaded = loaded;
            row.dataset.returned = returned;
            row.dataset.shipped = shipped;
            row.dataset.weight = weight;
            row.dataset.failed = failed;
            row.dataset.finalQC = finalQC;
            row.dataset.workWeek = item.WorkWeek;
            row.dataset.productioncontrolid = item.ProductionControlID;
            row.dataset.sequenceId = item.SequenceID;
            row.dataset.mainmark = item.Mainmark; // Use correct case from source
            row.dataset.piecemark = item.PieceMark; // Use correct case from source

            // Generate link content with maximum possible quantities
            const failedContent = failed > 0 ?
                `<a href="#" class="failed-link" data-mainmark="${item.Mainmark}" data-piecemark="${item.PieceMark}">${failed}</a>` :
                '-';

            const finalQCContent = formatRatio(finalQC, reqQty, reqQty);

            const loadedContent = loaded > 0 ?
                `<a href="#" class="loaded-link">${formatRatio(loaded, reqQty, finalQC)}</a>` :
                formatRatio(loaded, reqQty, finalQC);

            const returnedContent = formatRatio(returned, reqQty, loaded);


            const maxPossible = parseInt(item.QuantityReturned || 0);

            // Build row HTML
            row.innerHTML = `
            <td>${item.JobNumber}</td>
            <td>${item.SequenceName}</td>
            <td>${item.LotNumber}</td>
            <td>${item.Mainmark}</td>
            <td class="number-cell">${weight.toFixed(2)}</td>
            <td class="number-cell">${reqQty}</td>
            <td class="number-cell ${failed > 0 ? 'failed' : ''}">${failedContent}</td>
            <td class="number-cell ${finalQC >= reqQty ? 'complete' : ''}">${finalQCContent}</td>
            <td class="number-cell ${loaded >= reqQty ? 'complete' :
                (loaded >= finalQC && finalQC > 0 ? 'table-warning' : '')}">${loadedContent}</td>
            <td class="number-cell ${returned >= reqQty ? 'complete' :
                (returned >= loaded && loaded > 0 ? 'table-warning' : '')}">${returnedContent}</td>
            <td class="number-cell ${(shipped >= reqQty) ? 'complete' : ''}">${formatRatio(shipped, reqQty, maxPossible)}</td>
        `;

            // Add event listeners for links
            const failedLink = row.querySelector('.failed-link');
            if (failedLink) {
                failedLink.addEventListener('click', (e) => {
                    e.preventDefault();
                    const mainmark = e.target.dataset.mainmark;
                    const piecemark = e.target.dataset.piecemark;
                    showQCModal(item.ProductionControlItemID, item.SequenceID, mainmark, piecemark, 'failedQC');
                });
            }

            const loadedLink = row.querySelector('.loaded-link');
            if (loadedLink) {
                loadedLink.addEventListener('click', (e) => {
                    e.preventDefault();
                    showShippingModal(item.ProductionControlItemID, item.SequenceID, 'galv');
                });
            }

            const shippedLink = row.querySelector('.shipped-link');
            if (shippedLink) {
                shippedLink.addEventListener('click', (e) => {
                    e.preventDefault();
                    showShippingModal(item.ProductionControlItemID, item.SequenceID, 'shipped');
                });
            }

            tbody.appendChild(row);
        });

        updateTotals();
        updateHeaderStatus();
        updateSummaryStats();
    }

    function createSequenceButtons(data) {
        const sequenceMap = new Map();

        // First, collect all unique sequences while preserving first occurrence order
        data.forEach(item => {
            const key = item.SequenceID;
            if (!sequenceMap.has(key)) {
                sequenceMap.set(key, {
                    sequence: item.SequenceName,
                    sequenceId: item.SequenceID,
                    productionControlId: item.ProductionControlID,
                    key: key
                });
                // Store order of first appearance
                if (!originalSequenceOrder.includes(key)) {
                    originalSequenceOrder.push(key);
                }
            }
        });

        // Convert to array using original order
        const sequences = originalSequenceOrder
            .map(key => sequenceMap.get(key))
            .filter(Boolean); // Remove any undefined entries

        const filterContainer = document.getElementById('jobFilter');
        filterContainer.innerHTML = '';

        // Add "All Sequences" button
        const allButton = document.createElement('button');
        allButton.className = 'btn btn-outline-light btn-sm';
        allButton.classList.toggle('active', !filterState.sequence);
        allButton.dataset.sequenceKey = '';
        allButton.textContent = 'All Sequences';
        allButton.title = 'Show all sequences';
        filterContainer.appendChild(allButton);

        // Add individual sequence buttons in original order
        sequences.forEach(seq => {
            const button = document.createElement('button');
            button.className = 'btn btn-outline-light btn-sm';
            button.classList.toggle('active', filterState.sequence === seq.key);
            button.dataset.sequenceKey = seq.key;
            button.dataset.productioncontrolid = seq.productionControlId;
            button.textContent = seq.sequence;

            // Apply cached status if available
            const cachedStatus = buttonStatusCache.get(seq.key.toString());
            if (cachedStatus) {
                button.title = `Qty Short: ${cachedStatus.quantityShort}`;
                if (cachedStatus.isComplete) {
                    button.classList.remove('btn-outline-light');
                    button.classList.add('btn-success');
                }
            } else {
                button.title = 'Qty Short: 0';
            }

            filterContainer.appendChild(button);
        });

        filterContainer.addEventListener('click', handleSequenceClick);
    }

    function loadJobStatus() {
        const sequences = [...new Set(globalData.map(item => item.SequenceID))].filter(Boolean);
        const formData = new FormData();
        formData.append('sequences', JSON.stringify(sequences));

        // Only fetch if we don't have cache or forcing refresh
        if (buttonStatusCache.size === 0) {
            fetch('ajax_ssf_get_shippinginfo_buttonstatus.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => updateJobButtonStatus(data))
                .catch(error => console.error('Error:', error));
        }
    }

    function updateJobButtonStatus(data) {
        const sequenceButtons = document.querySelectorAll('#jobFilter button[data-sequence-key]');

        // Update our cache with the new data
        data.forEach(status => {
            buttonStatusCache.set(status.SequenceID.toString(), {
                quantityShort: parseInt(status.QuantityShort),
                isComplete: parseInt(status.QuantityShort) === 0
            });
        });

        sequenceButtons.forEach(btn => {
            const sequenceKey = btn.dataset.sequenceKey;
            if (!sequenceKey) return;

            const cachedStatus = buttonStatusCache.get(sequenceKey);
            if (cachedStatus) {
                btn.title = `Qty Short: ${cachedStatus.quantityShort}`;

                // Remove both classes first
                btn.classList.remove('btn-outline-light', 'btn-success');

                // Add appropriate class based on cached status
                if (cachedStatus.isComplete) {
                    btn.classList.add('btn-success');
                } else {
                    btn.classList.add('btn-outline-light');
                }

                // Keep active state if button is selected
                if (btn.classList.contains('active')) {
                    btn.classList.add('active');
                }
            }
        });
    }

    function handleSequenceClick(e) {
        if (!e.target.matches('button')) return;

        const sequenceKey = e.target.dataset.sequenceKey;
        filterState.sequence = sequenceKey;
        filterState.status = '';

        // Update button states
        document.querySelectorAll('#jobFilter button').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.sequenceKey === sequenceKey);
        });

        applyAllFilters();
    }

    // Add event listeners:
    document.querySelector('.status-filters').addEventListener('click', e => {
        if (!e.target.matches('button')) return;
        if (e.target.id === 'resetFilters') {
            filterState.status = '';
            updateButtonStates();
            applyAllFilters();
            return;
        }
        updateFilters('status', e.target.dataset.status);
    });

    document.getElementById('jobFilter').addEventListener('click', e => {
        if (!e.target.matches('button')) return;

        const sequenceKey = e.target.dataset.sequenceKey;
        filterState.sequence = sequenceKey;  // Now using sequenceID
        filterState.status = '';

        applyAllFilters();
        updateButtonStates();
        loadJobStatus(); // Refresh button status after filter change
    });

    document.getElementById('workWeekFilter').addEventListener('click', e => {
        if (!e.target.matches('button')) return;
        filterState.status = '';
        updateFilters('workWeek', e.target.dataset.workweek);
    });

    function showShippingModal(productionControlItemId, sequenceId, type) {
        const modal = new bootstrap.Modal(document.getElementById('shippingModal'));
        const row = document.querySelector(`tr[data-production-control-item-id="${productionControlItemId}"]`);
        const mainMark = row.dataset.mainmark;
        const pieceMark = row.dataset.piecemark;

        const encodedMainMark = encodeURIComponent(mainMark);
        const encodedPieceMark = encodeURIComponent(pieceMark);

        document.getElementById('returnedHeader').style.display = type === 'galv' ? '' : 'none';

        fetch(`ajax_get_truckloads.php?sequenceId=${sequenceId}&mainMark=${encodedMainMark}&pieceMark=${encodedPieceMark}&type=${type}`)
            .then(response => response.json())
            .then(data => {
                if (data.length > 0) {
                    document.getElementById('shipJobNumber').textContent = data[0].JobNumber;
                    document.getElementById('shipMainMark').textContent = data[0].MainMark;
                    document.getElementById('shipSequence').textContent = data[0].Sequence;
                    document.getElementById('shipLot').textContent = data[0].LotNumber;
                }

                const tbody = document.getElementById('shippingModalBody');
                tbody.innerHTML = data.map(item => `
                <tr>
                    <td>${new Date(item.ShippedDate).toLocaleString()}</td>
                    <td>${item.TruckNumber}</td>
                    <td>${item.MainMark}</td>
                    <td>${item.PieceMark}</td>
                    <td>${item.WorkPackageNumber}</td>
                    <td>${item.FirmName || '-'}</td>
                    <td>${item.QuantityLoaded}</td>
                    ${type === 'galv' ? `<td>${item.QuantityReturned || 0}</td>` : ''}
                </tr>
            `).join('');
                modal.show();
            })
            .catch(error => {
                console.error('Error fetching truck data:', error);
                alert('Error loading truck data');
            });
    }

    function showQCModal(productionControlItemId, sequenceId, mainmark, piecemark, source = 'failedQC') {
        const modal = new bootstrap.Modal(document.getElementById('qcModal'));
        let allRecords = [];
        let highlightedRecordId = null;

        // Function to render table rows
        function renderRows(showAll = false) {
            const tbody = document.getElementById('qcModalBody');
            const recordsToShow = allRecords.filter(item => {
                if (source === 'failedQC') {
                    // For failed QC view
                    if (!showAll) {
                        // Show only true failures (no child records or passed child records)
                        return item.TestFailed === 1
                            && !item.ChildInspectionTestRecordID
                            && !item.PassedChildInspectionTestRecordID;
                    }
                    return true; // Show all when toggled
                }
                return false;
            });

            tbody.innerHTML = recordsToShow.map(item => {
                const relationships = [];
                if (item.ParentInspectionTestRecordID) {
                    relationships.push(`
                <a href="#" class="view-record" data-record-id="${item.ParentInspectionTestRecordID}">
                    Parent: ${item.ParentInspectionTestRecordID}
                </a>`);
                }
                if (item.ChildInspectionTestRecordID) {
                    relationships.push(`
                <a href="#" class="view-record" data-record-id="${item.ChildInspectionTestRecordID}">
                    Child: ${item.ChildInspectionTestRecordID}
                </a>`);
                }
                if (item.PassedChildInspectionTestRecordID) {
                    relationships.push(`
                <a href="#" class="view-record" data-record-id="${item.PassedChildInspectionTestRecordID}">
                    Passed Child: ${item.PassedChildInspectionTestRecordID}
                </a>`);
                }

                const relationshipDisplay = relationships.length ? relationships.join(' / ') : '-';

                // Determine row status
                const hasPassingChild = item.PassedChildInspectionTestRecordID != null;
                const rowClasses = [
                    item.TestFailed ? (hasPassingChild ? 'table-warning' : 'table-danger') : '',
                    item.InspectionTestRecordID === highlightedRecordId ? 'highlight-record' : ''
                ].filter(Boolean).join(' ');

                return `
            <tr class="${rowClasses}">
                <td>
                    <a href="#" class="inspection-details-link" data-record-id="${item.InspectionTestRecordID}">
                        ${item.InspectionTestRecordID}
                    </a>
                </td>
                <td>${item.TestFailed ? 'F' : 'P'}</td>
                <td>${item.MainMark}</td>
                <td>${item.PieceMark}</td>
                <td>${new Date(item.TestDateTime).toLocaleString()}</td>
                <td>${item.Quantity}</td>
                <td>${item.InspectionType}</td>
                <td>${item.InspectorName}</td>
                <td>${relationshipDisplay}</td>
            </tr>
            <tr class="details-row d-none" id="details-${item.InspectionTestRecordID}">
                <td colspan="9">
                    <div class="p-3 bg-light">
                        <h6>Inspection Details</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th>Test Type</th>
                                        <th>Field</th>
                                        <th>Value</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="details-body-${item.InspectionTestRecordID}">
                                    <tr>
                                        <td colspan="4" class="text-center">Click to load details</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </td>
            </tr>
        `;
            }).join('');

            document.querySelectorAll('.inspection-details-link').forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    const recordId = e.target.dataset.recordId;
                    const detailsRow = document.getElementById(`details-${recordId}`);

                    // Toggle visibility of details row
                    if (detailsRow.classList.contains('d-none')) {
                        // Hide any other open details rows
                        document.querySelectorAll('.details-row').forEach(row => {
                            row.classList.add('d-none');
                        });

                        // Show this details row and load data
                        detailsRow.classList.remove('d-none');
                        loadInspectionDetails(recordId);
                    } else {
                        detailsRow.classList.add('d-none');
                    }
                });
            });



            // Add click handlers for parent/child links
            document.querySelectorAll('.view-record').forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    document.querySelectorAll('.highlight-record').forEach(row => {
                        row.classList.remove('highlight-record');
                    });

                    highlightedRecordId = parseInt(e.target.dataset.recordId);
                    const toggle = document.getElementById('showAllRecords');
                    toggle.checked = true;
                    renderRows(true);

                    const highlightedRow = document.querySelector('.highlight-record');
                    if (highlightedRow) {
                        highlightedRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                });
            });
        }

        function loadInspectionDetails(testRecordId) {
            const tbody = document.getElementById(`details-body-${testRecordId}`);
            tbody.innerHTML = '<tr><td colspan="4" class="text-center">Loading...</td></tr>';

            fetch(`ajax_get_inspection_details.php?testRecordId=${testRecordId}`)
                .then(response => response.json())
                .then(data => {
                    tbody.innerHTML = data.map(item => `
                    <tr class="${item.IndicatesFailure ? 'table-danger' : ''}">
                        <td>${item.TestType}</td>
                        <td>${item.TestFieldString}</td>
                        <td>${item.TestValue}</td>
                        <td>
                            ${item.IndicatesFailure ?
                        '<span class="text-danger fw-bold">Failed</span>' :
                        '<span class="text-success">Passed</span>'
                    }
                        </td>
                    </tr>
                `).join('');
                })
                .catch(error => {
                    console.error('Error fetching inspection details:', error);
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center text-danger">Error loading inspection details</td></tr>';
                });
        }

        // Build the URL based on source
        let url = `ajax_get_inspectiondata.php?sequenceId=${sequenceId}&mainMark=${mainmark}&pieceMark=${piecemark}&showall=1`;

        fetch(url)
            .then(response => response.json())
            .then(data => {
                allRecords = data;
                if (data.length > 0) {
                    // Populate header info
                    document.getElementById('qcJobNumber').textContent = data[0].JobNumber;
                    document.getElementById('qcMainMark').textContent = data[0].MainMark;
                    document.getElementById('qcPieceMark').textContent = data[0].PieceMark;
                    document.getElementById('qcSequence').textContent = data[0].Sequence;
                    document.getElementById('qcLot').textContent = data[0].LotNumber;
                    document.getElementById('qcWorkPackage').textContent = data[0].WorkPackageNumber;
                    document.getElementById('qcLoadNumber').textContent = data[0].LoadNumber;
                }

                // Set up the toggle checkbox
                const toggle = document.getElementById('showAllRecords');

                // Update toggle label based on source
                const toggleLabel = toggle.nextElementSibling;
                toggleLabel.textContent = source === 'failedQC' ?
                    'Show All Records' :
                    'Show All Inspection Types';

                // Initial render
                renderRows(toggle.checked);

                // Add toggle event listener
                toggle.addEventListener('change', (e) => {
                    renderRows(e.target.checked);
                });

                modal.show();
            })
            .catch(error => {
                console.error('Error fetching inspection data:', error);
                alert('Error loading inspection data');
            });
    }

    function loadJobStatus() {
        const sequences = [...new Set(globalData.map(item => item.SequenceID))].filter(Boolean);
        const formData = new FormData();
        formData.append('sequences', JSON.stringify(sequences));  // Changed from 'jobs' to 'sequences'

        fetch('ajax_ssf_get_shippinginfo_buttonstatus.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => updateJobButtonStatus(data))
            .catch(error => console.error('Error:', error));
    }

    function initializeSequenceButtons(data) {
        const filterContainer = document.getElementById('jobFilter');
        filterContainer.innerHTML = '';

        // Create "All Sequences" button
        const allButton = document.createElement('button');
        allButton.className = 'btn btn-outline-light btn-sm';
        allButton.dataset.sequenceKey = '';
        allButton.textContent = 'All Sequences';
        allButton.title = 'Show all sequences';
        filterContainer.appendChild(allButton);

        // Create sequence buttons once, maintaining original order from data
        const uniqueSequences = Array.from(new Set(data.map(item => item.SequenceID)))
            .map(sequenceId => {
                const item = data.find(d => d.SequenceID === sequenceId);
                return {
                    sequenceId: sequenceId,
                    sequenceName: item.SequenceName
                };
            });

        uniqueSequences.forEach(seq => {
            const button = document.createElement('button');
            button.className = 'btn btn-outline-light btn-sm';
            button.dataset.sequenceKey = seq.sequenceId;
            button.textContent = seq.sequenceName;
            button.title = 'Qty Short: 0';
            filterContainer.appendChild(button);
        });

        // Add single event listener to container
        filterContainer.addEventListener('click', handleSequenceClick);
    }

    function createWorkWeekButtons(data){
        // Work Week filters
        const workWeeks = [...new Set(data.map(item => item.WorkWeek))]
            .filter(week => week)
            .sort((a, b) => Number(a) - Number(b));
        const workWeekContainer = document.getElementById('workWeekFilter');
        workWeekContainer.innerHTML = '';

        const allWorkWeekButton = document.createElement('button');
        allWorkWeekButton.className = 'btn btn-outline-secondary btn-sm active';
        allWorkWeekButton.setAttribute('data-workweek', '');
        allWorkWeekButton.textContent = 'All Fab Weeks';
        workWeekContainer.appendChild(allWorkWeekButton);

        workWeeks.forEach(week => {
            const button = document.createElement('button');
            button.className = 'btn btn-outline-secondary btn-sm';
            button.setAttribute('data-workweek', week);
            button.textContent = `Week ${week}`;
            workWeekContainer.appendChild(button);
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        fetch('ajax_ssf_get_shippinginfo.php')
            .then(response => response.json())
            .then(data => {
                globalData = data;
                createSequenceButtons(data);
                createWorkWeekButtons(data);
                populateTable(data);
                updateButtonStates();
                loadJobStatus(); // Initial status check
            })
            .catch(error => {
                console.error('Error loading data:', error);
                alert('Error loading data');
            });


        const guideContent = document.getElementById('guideContent');
        if (guideContent) {
            fetch('ssf_post_status_guide.md')
                .then(response => response.text())
                .then(markdown => {
                    guideContent.innerHTML = marked.parse(markdown);
                })
                .catch(error => {
                    console.error('Error loading guide:', error);
                    guideContent.innerHTML = '<div class="alert alert-danger">Error loading guide content</div>';
                });
        }
    });

</script>
</body>
</html>