/**
 * Machine Details Page JavaScript
 * Handles production data display and interactions
 */

// Global variables
let currentData = null;
let currentPeriod = 'today';
let chartInstances = {};

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

    // Update activity timeline
    updateActivityTimeline(data.activities);

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

    // TODO: Add trend indicators based on previous period comparison
}

/**
 * Update activity timeline
 */
function updateActivityTimeline(activities) {
    const timeline = document.getElementById('activityTimeline');

    if (!activities || activities.length === 0) {
        timeline.innerHTML = '<p class="text-muted text-center">No activities found for this period</p>';
        return;
    }

    // Limit to last 50 activities for performance
    const limitedActivities = activities.slice(0, 50);

    timeline.innerHTML = limitedActivities.map(activity => {
        const icon = getActivityIcon(activity.Type);
        const time = formatDateTime(activity.Timestamp);

        return `
            <div class="timeline-item">
                <div class="timeline-icon type-${activity.Type}">
                    ${icon}
                </div>
                <div class="timeline-content">
                    <div class="timeline-time">${time}</div>
                    <div class="timeline-title">${activity.Activity_Text}</div>
                    <div class="timeline-details">
                        ${activity.Nest ? `Nest: ${activity.Nest}` : ''}
                        ${activity.Part_Mark ? ` | Part: ${activity.Part_Mark}` : ''}
                        ${activity.Profile ? ` | Profile: ${activity.Profile}` : ''}
                        ${activity.Operator ? ` | Operator: ${activity.Operator}` : ''}
                    </div>
                </div>
            </div>
        `;
    }).join('');
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
 * Get bar color by index
 */
function getBarColor(index) {
    const colors = ['#28a745', '#ffc107', '#17a2b8', '#dc3545', '#6c757d'];
    return colors[index % colors.length];
}

/**
 * Helper Functions
 */

function getActivityIcon(type) {
    switch (type) {
        case 'N':
            return '<i class="fas fa-download"></i>';
        case 'P':
            return '<i class="fas fa-cut"></i>';
        case 'A':
            return '<i class="fas fa-stop"></i>';
        default:
            return '<i class="fas fa-question"></i>';
    }
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