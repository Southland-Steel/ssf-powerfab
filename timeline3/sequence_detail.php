<!DOCTYPE html>
<html>
<head>
    <title>Sequence Detail</title>
    <link rel="stylesheet" href="sequence_detail.css">
</head>
<body>
<?php
$jobNumber = isset($_GET['jobNumber']) ? htmlspecialchars($_GET['jobNumber']) : '';
$sequenceName = isset($_GET['sequenceName']) ? htmlspecialchars($_GET['sequenceName']) : '';
$lotNumber = isset($_GET['lotNumber']) ? htmlspecialchars($_GET['lotNumber']) : '';

$titleText = "Production Detail for Job $jobNumber - Sequence $sequenceName";
if (!empty($lotNumber)) {
    $titleText .= " - Lot $lotNumber";
}
?>
<div class="header">
    <h2><?= $titleText ?></h2>
    <p class="subtitle">Detailed breakdown of assembly status and metrics</p>
</div>

<div class="controls">
    <div class="filter-buttons" id="filter-container">
        <!-- Filter buttons will be added here -->
    </div>
    <button id="toggle-complete" class="toggle-button">Show/Hide Completed</button>
    <button id="back-button" class="back-button" onclick="history.back()">← Back to List</button>
</div>

<div id="summary-section">
    <div class="panel">
        <h3>Sequence Summary</h3>
        <div id="sequence-summary" class="summary-content">
            <div class="loading">Loading...</div>
        </div>
    </div>
    <div class="panel">
        <h3>Progress by Station</h3>
        <div id="station-progress" class="summary-content">
            <div class="loading">Loading...</div>
        </div>
    </div>
</div>

<div id="detail-table-container">
    <div class="loading">Loading assembly details...</div>
</div>

<script>
    // Store the query parameters
    const params = {
        jobNumber: '<?= $jobNumber ?>',
        sequenceName: '<?= $sequenceName ?>',
        lotNumber: '<?= $lotNumber ?>'
    };

    // Track the active category filter
    let activeCategory = 'all';

    // Track whether to show completed items
    let showCompleted = true;

    // Load the data from the server
    function loadSequenceData() {
        fetch('ajax/get_sequence_detail.php?' + new URLSearchParams(params))
            .then(response => response.json())
            .then(data => {
                if (!data || data.length === 0) {
                    document.getElementById('detail-table-container').innerHTML = '<div class="no-data">No data found for this sequence.</div>';
                    return;
                }

                renderData(data);
            })
            .catch(error => {
                console.error('Error fetching data:', error);
                document.getElementById('detail-table-container').innerHTML = '<div class="error">Error loading data. Please try again.</div>';
            });
    }

    // Process and render the data
    function renderData(data) {
        // Sort data: incomplete items first, then by station completion
        data.sort((a, b) => {
            // Check if both items are fully complete (all stations >= 98%)
            const aComplete = Object.keys(a.Stations).every(station =>
                (a.Stations[station]?.Completed / a.Stations[station]?.Total) >= 0.98
            );
            const bComplete = Object.keys(b.Stations).every(station =>
                (b.Stations[station]?.Completed / b.Stations[station]?.Total) >= 0.98
            );

            // Move completed items to bottom
            if (aComplete && !bComplete) return 1;
            if (!aComplete && bComplete) return -1;

            // Define station order for comparison
            const stationOrder = ['SHIPPING', 'FINAL QC', 'WELD', 'FIT', 'CUT', 'NESTED'];

            // Compare each station in order
            for (const station of stationOrder) {
                const aPercent = a.Stations[station]?.Completed / a.Stations[station]?.Total || 0;
                const bPercent = b.Stations[station]?.Completed / b.Stations[station]?.Total || 0;

                if (aPercent !== bPercent) {
                    // Sort in ascending order (least complete first)
                    return aPercent - bPercent;
                }
            }

            return 0;
        });

        // Generate and render summary data
        renderSummary(data);

        // Generate and render station progress
        renderStationProgress(data);

        // Build category filter buttons
        renderFilterButtons(data);

        // Render the main data table
        renderDetailTable(data);

        // Set up event listeners
        setupEventListeners();
    }

    // Render sequence summary metrics
    function renderSummary(data) {
        let totalWeight = 0;
        let totalAssemblies = data.length;
        let completedAssemblies = 0;
        let inProgressAssemblies = 0;
        let notStartedAssemblies = 0;

        // Category totals
        const categoryTotals = {};

        data.forEach(assembly => {
            // Add to total weight
            totalWeight += parseFloat(assembly.GrossAssemblyWeightEach) || 0;

            // Categorize assembly completion status
            const nestedProgress = assembly.Stations['NESTED']?.Completed / assembly.Stations['NESTED']?.Total || 0;
            const shippingProgress = assembly.Stations['SHIPPING']?.Completed / assembly.Stations['SHIPPING']?.Total || 0;

            if (shippingProgress >= 0.98) {
                completedAssemblies++;
            } else if (nestedProgress > 0) {
                inProgressAssemblies++;
            } else {
                notStartedAssemblies++;
            }

            // Count by category
            const category = assembly.Category || 'Uncategorized';
            if (!categoryTotals[category]) {
                categoryTotals[category] = {
                    count: 0,
                    weight: 0
                };
            }
            categoryTotals[category].count++;
            categoryTotals[category].weight += parseFloat(assembly.GrossAssemblyWeightEach) || 0;
        });

        // Create summary HTML
        let summaryHtml = `
            <div class="summary-metrics">
                <div class="metric">
                    <span class="metric-value">${totalAssemblies}</span>
                    <span class="metric-label">Total Assemblies</span>
                </div>
                <div class="metric">
                    <span class="metric-value">${totalWeight.toFixed(1)}</span>
                    <span class="metric-label">Total Weight (lbs)</span>
                </div>
                <div class="metric">
                    <span class="metric-value">${completedAssemblies}</span>
                    <span class="metric-label">Completed</span>
                </div>
                <div class="metric">
                    <span class="metric-value">${inProgressAssemblies}</span>
                    <span class="metric-label">In Progress</span>
                </div>
                <div class="metric">
                    <span class="metric-value">${notStartedAssemblies}</span>
                    <span class="metric-label">Not Started</span>
                </div>
            </div>

            <div class="category-breakdown">
                <h4>Distribution by Category</h4>
                <table class="summary-table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Count</th>
                            <th>Weight</th>
                            <th>% of Total</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        // Add category rows
        Object.keys(categoryTotals).sort().forEach(category => {
            const percentOfTotal = (categoryTotals[category].count / totalAssemblies * 100).toFixed(1);
            summaryHtml += `
                <tr>
                    <td>${category}</td>
                    <td>${categoryTotals[category].count}</td>
                    <td>${categoryTotals[category].weight.toFixed(1)} lbs</td>
                    <td>${percentOfTotal}%</td>
                </tr>
            `;
        });

        summaryHtml += `
                    </tbody>
                </table>
            </div>
        `;

        document.getElementById('sequence-summary').innerHTML = summaryHtml;
    }

    // Render station progress charts
    function renderStationProgress(data) {
        const stations = ['NESTED', 'CUT', 'FIT', 'WELD', 'FINAL QC', 'SHIPPING'];
        const stationTotals = {};

        // Initialize station totals
        stations.forEach(station => {
            stationTotals[station] = {
                total: 0,
                completed: 0
            };
        });

        // Calculate totals for each station
        data.forEach(assembly => {
            stations.forEach(station => {
                if (assembly.Stations[station]) {
                    stationTotals[station].total += assembly.Stations[station].Total;
                    stationTotals[station].completed += assembly.Stations[station].Completed;
                }
            });
        });

        // Create station progress HTML
        let progressHtml = `
            <div class="station-progress-bars">
        `;

        stations.forEach(station => {
            if (stationTotals[station].total > 0) {
                const percent = (stationTotals[station].completed / stationTotals[station].total * 100).toFixed(1);
                const statusClass = percent >= 90 ? 'complete' :
                    percent >= 50 ? 'partial' :
                        'incomplete';

                progressHtml += `
                    <div class="progress-item">
                        <div class="progress-label">${station}</div>
                        <div class="progress-bar-container">
                            <div class="progress-bar ${statusClass}" style="width: ${percent}%"></div>
                            <div class="progress-text">${stationTotals[station].completed} / ${stationTotals[station].total} (${percent}%)</div>
                        </div>
                    </div>
                `;
            }
        });

        progressHtml += `</div>`;
        document.getElementById('station-progress').innerHTML = progressHtml;
    }

    // Render filter buttons
    function renderFilterButtons(data) {
        // Extract unique categories
        const categories = [...new Set(data.map(assembly => assembly.Category || 'Uncategorized'))].sort();

        let filterHtml = `<button class="filter-button active" data-category="all">All Categories</button>`;
        categories.forEach(category => {
            filterHtml += `<button class="filter-button" data-category="${category}">${category}</button>`;
        });

        document.getElementById('filter-container').innerHTML = filterHtml;
    }

    // Render detail table
    function renderDetailTable(data) {
        let tableHtml = `
            <table id="detail-table">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>SubCategory</th>
                        <th>Main Mark</th>
                        <th>Work Package</th>
                        <th>Weight (lbs)</th>
                        <th>Nested</th>
                        <th>Cut</th>
                        <th>Fit</th>
                        <th>Weld</th>
                        <th>Final QC</th>
                        <th>Shipping</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
        `;

        data.forEach(assembly => {
            const isComplete = Object.keys(assembly.Stations).every(station =>
                (assembly.Stations[station]?.Completed / assembly.Stations[station]?.Total) >= 0.98
            );

            // Determine overall status
            const stationStatuses = ['NESTED', 'CUT', 'FIT', 'WELD', 'FINAL QC', 'SHIPPING'].map(station => {
                if (!assembly.Stations[station]) return 0;
                return assembly.Stations[station].Completed / assembly.Stations[station].Total;
            });

            let overallStatus = 'Not Started';
            let statusClass = 'incomplete';

            if (stationStatuses[5] >= 0.98) { // Shipping
                overallStatus = 'Shipped';
                statusClass = 'complete';
            } else if (stationStatuses[4] >= 0.98) { // Final QC
                overallStatus = 'QC Complete';
                statusClass = 'complete';
            } else if (stationStatuses[3] >= 0.98) { // Weld
                overallStatus = 'Welded';
                statusClass = 'partial';
            } else if (stationStatuses[2] >= 0.98) { // Fit
                overallStatus = 'Fitted';
                statusClass = 'partial';
            } else if (stationStatuses[1] >= 0.98) { // Cut
                overallStatus = 'Cut';
                statusClass = 'partial';
            } else if (stationStatuses[0] >= 0.98) { // Nested
                overallStatus = 'Nested';
                statusClass = 'partial';
            } else if (stationStatuses[0] > 0) {
                overallStatus = 'In Progress';
                statusClass = 'incomplete';
            }

            const rowClass = isComplete ? 'completed' : '';

            tableHtml += `
                <tr class="${rowClass}" data-category="${assembly.Category || 'Uncategorized'}">
                    <td>${assembly.Category || 'Uncategorized'}</td>
                    <td>${assembly.SubCategory || '-'}</td>
                    <td>${assembly.MainMark}</td>
                    <td>${assembly.WorkPackageNumber || '-'}</td>
                    <td>${parseFloat(assembly.GrossAssemblyWeightEach).toFixed(1)}</td>
            `;

            // Add each station cell
            ['NESTED', 'CUT', 'FIT', 'WELD', 'FINAL QC', 'SHIPPING'].forEach(station => {
                if (assembly.Stations[station]) {
                    const percent = (assembly.Stations[station].Completed / assembly.Stations[station].Total) * 100;
                    const cellClass = percent >= 90 ? 'complete' :
                        percent >= 50 ? 'partial' :
                            'incomplete';
                    tableHtml += `
                        <td class="status ${cellClass}">
                            ${assembly.Stations[station].Completed} / ${assembly.Stations[station].Total}
                            <br>(${percent.toFixed(1)}%)
                        </td>
                    `;
                } else {
                    tableHtml += '<td class="na">-</td>';
                }
            });

            // Add overall status
            tableHtml += `
                    <td class="status-label ${statusClass}">${overallStatus}</td>
                </tr>
            `;
        });

        tableHtml += `
                </tbody>
            </table>
        `;

        document.getElementById('detail-table-container').innerHTML = tableHtml;
    }

    // Set up event listeners
    function setupEventListeners() {
        // Filter buttons
        document.querySelectorAll('.filter-button').forEach(button => {
            button.addEventListener('click', function() {
                document.querySelectorAll('.filter-button').forEach(btn =>
                    btn.classList.remove('active'));
                this.classList.add('active');

                activeCategory = this.dataset.category;
                applyFilters();
            });
        });

        // Toggle completed items
        document.getElementById('toggle-complete').addEventListener('click', function() {
            showCompleted = !showCompleted;
            applyFilters();
        });
    }

    // Apply current filters to the table
    function applyFilters() {
        const rows = document.querySelectorAll('#detail-table tbody tr');

        rows.forEach(row => {
            const rowCategory = row.dataset.category;
            const isCompleted = row.classList.contains('completed');

            let visible = true;

            // Apply category filter
            if (activeCategory !== 'all' && rowCategory !== activeCategory) {
                visible = false;
            }

            // Apply completed filter
            if (!showCompleted && isCompleted) {
                visible = false;
            }

            row.style.display = visible ? '' : 'none';
        });
    }

    // Load the data when the page loads
    document.addEventListener('DOMContentLoaded', loadSequenceData);
</script>
</body>
</html>