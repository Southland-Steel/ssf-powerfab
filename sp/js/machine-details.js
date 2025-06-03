/**
 * Machine Details Page JavaScript
 * Handles production data display and interactions
 */

// Global variables
let currentData = null;
let currentPeriod = 'today';
let chartInstances = {};
let activityDataTable = null;
let barDetailsModal = null;

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    initializePage();
});

/**
 * Initialize the page
 */
function initializePage() {
    // Get initial values
    const machineName = document.getElementById('machineName').value;
    const initialPeriod = document.getElementById('currentPeriod').value || 'today';

    // Initialize modal
    const modalElement = document.getElementById('barDetailsModal');
    if (modalElement) {
        barDetailsModal = new bootstrap.Modal(modalElement);
    }

    // Set up event listeners
    setupEventListeners();

    // Load initial data
    changePeriod(initialPeriod);
}

/**
 * Set up event listeners
 */
function setupEventListeners() {
    // Period selector buttons
    document.querySelectorAll('.period-selector .btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const period = this.getAttribute('data-period');
            changePeriod(period);
        });
    });

    // Search functionality
    const searchInput = document.getElementById('searchNests');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            filterNestsTable(this.value);
        });
    }

    // Custom date range
    const customDateInputs = document.querySelectorAll('#startDate, #endDate');
    customDateInputs.forEach(input => {
        input.addEventListener('change', validateDateRange);
    });
}

/**
 * Change the selected period
 */
function changePeriod(period) {
    currentPeriod = period;

    // Update button states
    document.querySelectorAll('.period-selector .btn').forEach(btn => {
        btn.classList.remove('active');
        if (btn.getAttribute('data-period') === period) {
            btn.classList.add('active');
        }
    });

    // Show/hide custom date range
    const customDateRange = document.getElementById('customDateRange');
    if (period === 'custom') {
        customDateRange.style.display = 'block';
        // Set default dates if empty
        if (!document.getElementById('startDate').value) {
            const now = new Date();
            const yesterday = new Date(now);
            yesterday.setDate(yesterday.getDate() - 1);
            document.getElementById('startDate').value = yesterday.toISOString().slice(0, 16);
            document.getElementById('endDate').value = now.toISOString().slice(0, 16);
        }
    } else {
        customDateRange.style.display = 'none';
        loadProductionData(period);
    }
}

/**
 * Apply custom date range
 */
function applyCustomRange() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;

    if (!startDate || !endDate) {
        alert('Please select both start and end dates');
        return;
    }

    if (new Date(startDate) > new Date(endDate)) {
        alert('Start date must be before end date');
        return;
    }

    loadProductionData('custom', startDate, endDate);
}

/**
 * Load production data from server
 */
async function loadProductionData(period, startDate = '', endDate = '') {
    const machineName = document.getElementById('machineName').value;

    // Show loading state
    showLoading(true);

    try {
        let url = `ajax/get_production_data.php?machine=${encodeURIComponent(machineName)}&period=${period}`;

        if (period === 'custom' && startDate && endDate) {
            url += `&start_date=${encodeURIComponent(startDate)}&end_date=${encodeURIComponent(endDate)}`;
        }

        const response = await fetch(url);

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (data.error) {
            showError(data.error);
        } else {
            currentData = data;
            updateDisplay(data);
        }

    } catch (error) {
        console.error('Error loading production data:', error);
        showError('Failed to load production data. Please try again.');
    }
}

/**
 * Update the display with new data
 */
function updateDisplay(data) {
    showLoading(false);

    // Update summary cards
    updateSummaryCards(data.summary);

    // Update activity DataTable
    updateActivityTable(data.activities);

    // Update nests table
    updateNestsTable(data.nests);

    // Update stops table
    updateStopsTable(data.stops);

    // Update charts
    updateCharts(data);

    // Show content
    document.getElementById('productionContent').style.display = 'block';
}

/**
 * Update summary cards
 */
function updateSummaryCards(summary) {
    // Total Parts
    document.getElementById('totalParts').textContent = summary.parts_quantity || 0;

    // Nests Completed
    document.getElementById('nestsCompleted').textContent = summary.total_nests || 0;

    // Production Time
    const hours = Math.floor(summary.production_time_hours);
    const minutes = Math.round((summary.production_time_hours - hours) * 60);
    document.getElementById('productionTime').textContent = `${hours}h ${minutes}m`;

    // Efficiency
    document.getElementById('efficiency').textContent = `${summary.efficiency || 0}%`;
}

/**
 * Update activity DataTable
 */
function updateActivityTable(activities) {
    const tableBody = document.getElementById('activityTableBody');

    // Destroy existing DataTable if it exists
    if (activityDataTable) {
        activityDataTable.destroy();
    }

    if (!activities || activities.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="10" class="text-center text-muted">No activities found for this period</td></tr>';
        return;
    }

    // Build table HTML
    tableBody.innerHTML = activities.map(activity => {
        const dateTime = formatDateTime(activity.Timestamp);
        const type = getActivityTypeLabel(activity.Type);
        const typeClass = getActivityTypeClass(activity.Type);

        return `
            <tr data-nest="${activity.Nest || ''}" data-type="${activity.Type}">
                <td>${dateTime}</td>
                <td><span class="badge ${typeClass}">${type}</span></td>
                <td>${activity.Piece_Mark || '-'}</td>
                <td>${activity.Size || '-'}</td>
                <td>${activity.Grade || '-'}</td>
                <td>${activity.Job || '-'}</td>
                <td>${activity.Sequence || '-'}</td>
                <td>${activity.Nest || '-'}</td>
                <td>${activity.Weight ? parseFloat(activity.Weight).toFixed(2) : '-'}</td>
                <td>
                    ${activity.Nest ? `
                        <button class="btn btn-sm btn-outline-primary" onclick="viewBarDetails('${activity.Nest}', '${activity.Batch || ''}', '${activity.NES_ID || ''}', '${activity.BAR_ID || ''}')">
                            <i class="fas fa-eye"></i>
                        </button>
                    ` : '-'}
                </td>
            </tr>
        `;
    }).join('');

    // Initialize DataTable
    activityDataTable = $('#activityTable').DataTable({
        pageLength: 25,
        order: [[0, 'asc']], // Sort by date/time descending
        responsive: true,
        language: {
            search: "Filter activities:",
            lengthMenu: "Show _MENU_ activities per page"
        }
    });
}

/**
 * View bar details
 */
async function viewBarDetails(nest, batch, nesId, barId) {
    if (!nest) return;

    // Show modal with loading state
    barDetailsModal.show();
    showBarDetailsLoading();

    try {
        const machine = document.getElementById('machineName').value;
        const url = `ajax/get_bar_details.php?machine=${encodeURIComponent(machine)}&nest=${encodeURIComponent(nest)}&batch=${encodeURIComponent(batch || '')}&nes_id=${encodeURIComponent(nesId || '')}&bar_id=${encodeURIComponent(barId || '')}`;
        const response = await fetch(url);

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (data.error) {
            showBarDetailsError(data.error);
        } else {
            displayBarDetails(data);
        }

    } catch (error) {
        console.error('Error loading bar details:', error);
        showBarDetailsError('Failed to load bar details. Please try again.');
    }
}

/**
 * Display bar details in modal
 */
function displayBarDetails(data) {
    const content = document.getElementById('barDetailsContent');
    const modalTitle = document.getElementById('barDetailsModalLabel');

    // Update modal title
    modalTitle.innerHTML = `
        <i class="fas fa-bars me-2"></i>
        Bar Details - Nest: ${data.nest_name || 'N/A'}
    `;

    // Build content HTML
    let html = `
        <div class="row">
            <div class="col-md-6">
                <h6 class="fw-bold mb-3">Nest Information</h6>
                <table class="table table-sm">
                    <tr>
                        <th width="40%">Nest Name:</th>
                        <td>${data.nest_name || '-'}</td>
                    </tr>
                    <tr>
                        <th>Description:</th>
                        <td>${data.nest_description || '-'}</td>
                    </tr>
                    <tr>
                        <th>Total Quantity:</th>
                        <td>${data.nest_quantity || 0}</td>
                    </tr>
                    <tr>
                        <th>Quantity Produced:</th>
                        <td>${data.nest_quantity_produced || 0}</td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6 class="fw-bold mb-3">Production Summary</h6>
                <table class="table table-sm">
                    <tr>
                        <th width="40%">Total Bars:</th>
                        <td>${data.bars ? data.bars.length : 0}</td>
                    </tr>
                    <tr>
                        <th>Parts Cut:</th>
                        <td>${data.total_parts_cut || 0}</td>
                    </tr>
                    <tr>
                        <th>Total Weight:</th>
                        <td>${data.total_weight ? data.total_weight.toFixed(2) + ' lbs' : '-'}</td>
                    </tr>
                </table>
            </div>
        </div>
    `;

    // Add bars table if available
    if (data.bars && data.bars.length > 0) {
        html += `
            <h6 class="fw-bold mt-4 mb-3">Bar Details</h6>
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>Bar Number</th>
                            <th>Bar ID</th>
                            <th>Profile</th>
                            <th>Thickness</th>
                            <th>Length</th>
                            <th>Weight</th>
                            <th>Waste</th>
                            <th>Cut List Item</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        data.bars.forEach(bar => {
            // Extract CutListItemID from BAR_XID if available
            let cutListItemId = '-';
            if (bar.Cut_List_Item_ID) {
                cutListItemId = bar.Cut_List_Item_ID;
            }

            html += `
                <tr>
                    <td><strong>${bar.Bar_Number || '-'}</strong></td>
                    <td>${bar.Bar_ID || '-'}</td>
                    <td>${bar.Profile || '-'}</td>
                    <td>${bar.Thickness ? bar.Thickness + ' mm' : '-'}</td>
                    <td>${bar.Length ? bar.Length + ' mm' : '-'}</td>
                    <td>${bar.Weight ? parseFloat(bar.Weight).toFixed(2) + ' lbs' : '-'}</td>
                    <td>${bar.Waste ? bar.Waste + ' mm' : '-'}</td>
                    <td>${cutListItemId}</td>
                </tr>
            `;
        });

        html += `
                    </tbody>
                </table>
            </div>
        `;
    } else {
        html += `
            <div class="alert alert-info mt-4">
                <i class="fas fa-info-circle me-2"></i>
                No bar information available for this nest.
            </div>
        `;
    }

    content.innerHTML = html;

    // Hide loading, show content
    document.getElementById('barDetailsLoading').style.display = 'none';
    document.getElementById('barDetailsError').style.display = 'none';
    content.style.display = 'block';
}

/**
 * Show bar details loading state
 */
function showBarDetailsLoading() {
    document.getElementById('barDetailsLoading').style.display = 'block';
    document.getElementById('barDetailsContent').style.display = 'none';
    document.getElementById('barDetailsError').style.display = 'none';
}

/**
 * Show bar details error
 */
function showBarDetailsError(message) {
    document.getElementById('barErrorMessage').textContent = message;
    document.getElementById('barDetailsLoading').style.display = 'none';
    document.getElementById('barDetailsContent').style.display = 'none';
    document.getElementById('barDetailsError').style.display = 'block';
}

/**
 * Update nests table
 */
function updateNestsTable(nests) {
    const tbody = document.getElementById('nestsTableBody');

    if (!nests || nests.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No nests found for this period</td></tr>';
        return;
    }

    tbody.innerHTML = nests.map(nest => {
        const startTime = formatDateTime(nest.Start_Time);
        const endTime = formatDateTime(nest.End_Time);
        const duration = nest.Duration_Minutes || 0;
        const status = determineNestStatus(nest);

        return `
            <tr onclick="viewNestDetails('${nest.Nest}')">
                <td>${startTime} - ${endTime}</td>
                <td><strong>${nest.Nest || 'N/A'}</strong></td>
                <td>${nest.Job || 'N/A'}</td>
                <td>${nest.Drawing || 'N/A'}</td>
                <td>${nest.Total_Quantity || 0} parts</td>
                <td>${formatDuration(duration)}</td>
                <td><span class="status-${status.toLowerCase()}">${status}</span></td>
                <td>
                    <button class="btn btn-sm btn-outline-primary" onclick="viewNestDetails('${nest.Nest}'); event.stopPropagation();">
                        <i class="fas fa-eye"></i>
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}

/**
 * Update stops table
 */
function updateStopsTable(stops) {
    const tbody = document.getElementById('stopsTableBody');

    if (!stops || stops.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No stops found for this period</td></tr>';
        return;
    }

    tbody.innerHTML = stops.map(stop => {
        const stopTime = formatDateTime(stop.Stop_Time);
        const duration = stop.Stop_Duration_Minutes || 0;
        const durationClass = duration > 30 ? 'long' : duration > 10 ? 'medium' : 'short';

        return `
            <tr>
                <td>${stopTime}</td>
                <td><span class="stop-duration ${durationClass}">${formatDuration(duration)}</span></td>
                <td>-</td>
                <td>-</td>
            </tr>
        `;
    }).join('');
}

/**
 * Update charts
 */
function updateCharts(data) {
    // Update time breakdown chart
    updateTimeBreakdownChart(data.summary.time_breakdown);

    // Update production trend chart
    updateProductionTrendChart(data.activities);
}

/**
 * Update time breakdown chart
 */
function updateTimeBreakdownChart(timeData) {
    const canvas = document.getElementById('timeBreakdownChart');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');

    // Destroy existing chart
    if (chartInstances.timeBreakdown) {
        chartInstances.timeBreakdown.destroy();
    }

    // Prepare data
    const labels = ['Production', 'Idle', 'Setup', 'Alarms', 'Material Wait'];
    const values = [
        Math.round(timeData.production / 60),
        Math.round(timeData.idle / 60),
        Math.round(timeData.setup / 60),
        Math.round(timeData.alarm / 60),
        Math.round(timeData.material_wait / 60)
    ];

    // Create simple bar chart using canvas
    drawSimpleBarChart(ctx, labels, values);
}

/**
 * Draw simple bar chart
 */
function drawSimpleBarChart(ctx, labels, values) {
    const canvas = ctx.canvas;
    const width = canvas.width;
    const height = canvas.height;

    // Clear canvas
    ctx.clearRect(0, 0, width, height);

    // Calculate max value
    const maxValue = Math.max(...values, 1);

    // Draw bars
    const barWidth = width / labels.length * 0.8;
    const barGap = width / labels.length * 0.2;

    labels.forEach((label, index) => {
        const x = index * (barWidth + barGap) + barGap / 2;
        const barHeight = (values[index] / maxValue) * (height - 40);
        const y = height - barHeight - 20;

        // Draw bar
        ctx.fillStyle = getBarColor(index);
        ctx.fillRect(x, y, barWidth, barHeight);

        // Draw label
        ctx.fillStyle = '#666';
        ctx.font = '12px Arial';
        ctx.textAlign = 'center';
        ctx.fillText(label, x + barWidth / 2, height - 5);

        // Draw value
        ctx.fillText(`${values[index]}m`, x + barWidth / 2, y - 5);
    });
}

/**
 * Helper Functions
 */

function formatDateTime(dateString) {
    if (!dateString) return '-';

    const date = new Date(dateString);
    if (isNaN(date.getTime())) return dateString;

    const options = {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    };

    return date.toLocaleString('en-US', options);
}

function getActivityTypeLabel(type) {
    switch (type) {
        case 'N': return 'Bar Loading';
        case 'P': return 'Part Cutting';
        case 'A': return 'Machine Stop';
        default: return 'Unknown: ' + type;
    }
}

function getActivityTypeClass(type) {
    switch (type) {
        case 'N': return 'bg-info';
        case 'P': return 'bg-success';
        case 'A': return 'bg-danger';
        default: return 'bg-secondary';
    }
}

function getBarColor(index) {
    const colors = ['#28a745', '#ffc107', '#17a2b8', '#dc3545', '#6c757d'];
    return colors[index % colors.length];
}

function formatDuration(minutes) {
    if (!minutes || minutes < 0) return '0m';

    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;

    if (hours > 0) {
        return `${hours}h ${mins}m`;
    }
    return `${mins}m`;
}

function determineNestStatus(nest) {
    // Simple logic - can be enhanced
    if (nest.Duration_Minutes > 0) {
        return 'Complete';
    }
    return 'In Progress';
}

function filterNestsTable(searchTerm) {
    const rows = document.querySelectorAll('#nestsTableBody tr');
    const term = searchTerm.toLowerCase();

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(term) ? '' : 'none';
    });
}

function viewNestDetails(nestName) {
    // Navigate to record details page
    const machine = document.getElementById('machineName').value;
    window.location.href = `machine-records.php?machine=${encodeURIComponent(machine)}&nest=${encodeURIComponent(nestName)}`;
}

function validateDateRange() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;

    if (startDate && endDate && new Date(startDate) > new Date(endDate)) {
        document.getElementById('endDate').setCustomValidity('End date must be after start date');
    } else {
        document.getElementById('endDate').setCustomValidity('');
    }
}

function showLoading(show) {
    document.getElementById('loadingState').style.display = show ? 'block' : 'none';
    document.getElementById('productionContent').style.display = show ? 'none' : 'block';
    document.getElementById('errorState').style.display = 'none';
}

function showError(message) {
    document.getElementById('errorMessage').textContent = message;
    document.getElementById('errorState').style.display = 'block';
    document.getElementById('loadingState').style.display = 'none';
    document.getElementById('productionContent').style.display = 'none';
}

function refreshProductionData() {
    if (currentPeriod === 'custom') {
        applyCustomRange();
    } else {
        loadProductionData(currentPeriod);
    }
}

function exportData() {
    // TODO: Implement export functionality
    alert('Export functionality coming soon!');
}

// Make production trend chart update function a stub for now
function updateProductionTrendChart(activities) {
    // TODO: Implement production trend chart
    // This would show parts cut over time
}