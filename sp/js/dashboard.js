/**
 * Dashboard JavaScript
 * Handles main dashboard functionality
 */

// Dashboard state
let dashboardState = {
    refreshInterval: null,
    currentFilter: 'all',
    sortColumn: null,
    sortDirection: 'asc'
};

// Initialize dashboard when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    initializeDashboard();
});

/**
 * Initialize dashboard components
 */
function initializeDashboard() {
    // Set up event listeners
    setupEventListeners();

    // Initialize table sorting
    initializeTableSorting();

    // Make table rows clickable
    makeRowsClickable();

    // Optional: Set up auto-refresh (disabled by default)
    // startAutoRefresh(30000); // Refresh every 30 seconds
}

/**
 * Set up event listeners
 */
function setupEventListeners() {
    // Refresh button
    const refreshBtn = document.querySelector('.btn-refresh');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', refreshData);
    }

    // Filter buttons
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            filterMachines(this.textContent.toLowerCase());
        });
    });
}

/**
 * Make table rows clickable
 */
function makeRowsClickable() {
    const rows = document.querySelectorAll('#machineTable tbody tr');
    rows.forEach(row => {
        row.classList.add('clickable-row');
        row.addEventListener('click', function() {
            const machineName = this.querySelector('.machine-name').textContent.trim();
            // Remove the icon if present
            const cleanMachineName = machineName.replace(/^\s*[\u{1F300}-\u{1F9FF}]\s*/u, '').trim();
            showMachineModal(cleanMachineName);
        });
    });
}

/**
 * Show machine modal (placeholder - will be implemented in machine-modal.js)
 */
function showMachineModal(machineName) {
    // This function is now implemented in machine-modal.js
    if (typeof window.showMachineModal === 'function') {
        window.showMachineModal(machineName);
    } else {
        console.error('Machine modal function not loaded');
    }
}

/**
 * Refresh dashboard data
 */
function refreshData() {
    const spinner = document.getElementById('loadingSpinner');
    if (spinner) {
        spinner.classList.add('active');
    }

    // Show loading overlay
    showLoading();

    // Reload the page to fetch fresh data
    setTimeout(() => {
        window.location.reload();
    }, 500);
}

/**
 * Filter machines by status
 */
function filterMachines(filter) {
    const rows = document.querySelectorAll('#machineTable tbody tr');
    const buttons = document.querySelectorAll('.filter-btn');

    // Update active button
    buttons.forEach(btn => {
        btn.classList.remove('active');
        if (btn.textContent.toLowerCase() === filter) {
            btn.classList.add('active');
        }
    });

    // Store current filter
    dashboardState.currentFilter = filter;

    // Filter rows
    let visibleCount = 0;
    rows.forEach(row => {
        const status = row.getAttribute('data-status');
        let shouldShow = false;

        if (filter === 'all') {
            shouldShow = true;
        } else if (filter === 'active') {
            shouldShow = (status === 'active-now' || status === 'active-today');
        } else if (filter === 'inactive') {
            shouldShow = (status === 'inactive' || status === 'active-this-week');
        }

        row.style.display = shouldShow ? '' : 'none';
        if (shouldShow) visibleCount++;
    });

    // Show empty state if no results
    showEmptyState(visibleCount === 0);

    // Update statistics
    updateStatistics();
}

/**
 * Show/hide empty state message
 */
function showEmptyState(show) {
    let emptyState = document.querySelector('.empty-state-filter');

    if (show && !emptyState) {
        const tbody = document.querySelector('#machineTable tbody');
        emptyState = document.createElement('tr');
        emptyState.className = 'empty-state-filter';
        emptyState.innerHTML = `
            <td colspan="4" class="text-center py-4">
                <div class="text-muted">
                    <i class="fas fa-filter mb-2"></i>
                    <p>No machines match the current filter.</p>
                </div>
            </td>
        `;
        tbody.appendChild(emptyState);
    } else if (!show && emptyState) {
        emptyState.remove();
    }
}

/**
 * Update statistics based on visible rows
 */
function updateStatistics() {
    const visibleRows = document.querySelectorAll('#machineTable tbody tr:not([style*="display: none"]):not(.empty-state-filter)');
    let activeCount = 0;
    let inactiveCount = 0;

    visibleRows.forEach(row => {
        const status = row.getAttribute('data-status');
        if (status === 'active-now' || status === 'active-today') {
            activeCount++;
        } else {
            inactiveCount++;
        }
    });

    // Update the displayed counts if filter is active
    if (dashboardState.currentFilter !== 'all') {
        // You could update the stat cards here if desired
        console.log(`Showing ${activeCount} active and ${inactiveCount} inactive machines`);
    }
}

/**
 * Initialize table sorting
 */
function initializeTableSorting() {
    const table = document.getElementById('machineTable');
    if (!table) return;

    const headers = table.querySelectorAll('thead th');

    headers.forEach((header, index) => {
        header.addEventListener('click', () => sortTable(index));
    });
}

/**
 * Sort table by column
 */
function sortTable(columnIndex) {
    const table = document.getElementById('machineTable');
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr:not(.empty-state-filter)'));
    const headers = table.querySelectorAll('thead th');

    // Determine sort direction
    let isAscending = true;
    if (dashboardState.sortColumn === columnIndex) {
        isAscending = dashboardState.sortDirection !== 'asc';
    }

    // Update state
    dashboardState.sortColumn = columnIndex;
    dashboardState.sortDirection = isAscending ? 'asc' : 'desc';

    // Update header classes
    headers.forEach((header, idx) => {
        header.classList.remove('sort-asc', 'sort-desc');
        if (idx === columnIndex) {
            header.classList.add(isAscending ? 'sort-asc' : 'sort-desc');
        }
    });

    // Sort rows
    rows.sort((a, b) => {
        const aValue = a.cells[columnIndex].textContent.trim();
        const bValue = b.cells[columnIndex].textContent.trim();

        // Handle numeric columns (Days Since Activity)
        if (columnIndex === 2) {
            const aNum = parseInt(aValue) || 0;
            const bNum = parseInt(bValue) || 0;
            return isAscending ? aNum - bNum : bNum - aNum;
        }

        // Handle date columns (Last Activity)
        if (columnIndex === 1) {
            const aDate = new Date(aValue);
            const bDate = new Date(bValue);
            return isAscending ? aDate - bDate : bDate - aDate;
        }

        // Text comparison for other columns
        return isAscending ?
            aValue.localeCompare(bValue) :
            bValue.localeCompare(aValue);
    });

    // Re-append sorted rows
    rows.forEach(row => tbody.appendChild(row));

    // Re-apply current filter
    if (dashboardState.currentFilter !== 'all') {
        filterMachines(dashboardState.currentFilter);
    }
}

/**
 * Start auto-refresh
 */
function startAutoRefresh(interval = 30000) {
    stopAutoRefresh(); // Clear any existing interval
    dashboardState.refreshInterval = setInterval(refreshData, interval);
    console.log(`Auto-refresh started: every ${interval/1000} seconds`);
}

/**
 * Stop auto-refresh
 */
function stopAutoRefresh() {
    if (dashboardState.refreshInterval) {
        clearInterval(dashboardState.refreshInterval);
        dashboardState.refreshInterval = null;
        console.log('Auto-refresh stopped');
    }
}

// Export functions for use in other scripts
window.dashboardFunctions = {
    refreshData,
    filterMachines,
    startAutoRefresh,
    stopAutoRefresh
};