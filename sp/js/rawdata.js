/**
 * Raw Data Viewer JavaScript
 * Handles table interactions and data export
 */

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    initializeRawDataViewer();
});

/**
 * Initialize the raw data viewer
 */
function initializeRawDataViewer() {
    // Set up search functionality
    setupTableSearch();

    // Set up column toggles
    setupColumnToggles();

    // Set up touch-friendly scrolling for iPad
    setupTouchScrolling();

    // Initialize tooltips for truncated content
    setupTooltips();
}

/**
 * Set up table search functionality
 */
function setupTableSearch() {
    const searchInput = document.getElementById('tableSearch');
    if (!searchInput) return;

    let searchTimeout;
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            performTableSearch(this.value);
        }, 300);
    });
}

/**
 * Perform search in table
 */
function performTableSearch(searchTerm) {
    const table = document.getElementById('rawDataTable');
    const rows = table.querySelectorAll('tbody tr');
    const term = searchTerm.toLowerCase();

    // Remove previous highlights
    document.querySelectorAll('.highlight').forEach(el => {
        el.classList.remove('highlight');
        el.innerHTML = el.textContent;
    });

    if (!term) {
        rows.forEach(row => row.style.display = '');
        return;
    }

    rows.forEach(row => {
        let found = false;
        const cells = row.querySelectorAll('td');

        cells.forEach(cell => {
            const text = cell.textContent.toLowerCase();
            if (text.includes(term)) {
                found = true;
                // Highlight matching text
                highlightText(cell, searchTerm);
            }
        });

        row.style.display = found ? '' : 'none';
    });
}

/**
 * Highlight text in element
 */
function highlightText(element, searchTerm) {
    const text = element.textContent;
    const regex = new RegExp(`(${searchTerm})`, 'gi');
    const highlighted = text.replace(regex, '<span class="highlight">$1</span>');
    element.innerHTML = highlighted;
}

/**
 * Set up column toggle functionality
 */
function setupColumnToggles() {
    const toggles = document.querySelectorAll('.column-toggle');

    toggles.forEach(toggle => {
        toggle.addEventListener('change', function() {
            const columnName = this.getAttribute('data-column');
            toggleColumn(columnName, this.checked);
        });
    });
}

/**
 * Toggle column visibility
 */
function toggleColumn(columnName, show) {
    const cells = document.querySelectorAll(`.column-${columnName}`);
    cells.forEach(cell => {
        cell.style.display = show ? '' : 'none';
    });

    // Save preference
    saveColumnPreference(columnName, show);
}

/**
 * Toggle columns panel
 */
function toggleColumns() {
    const panel = document.getElementById('columnTogglePanel');
    panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
}

/**
 * Select/deselect all columns
 */
function selectAllColumns(checked) {
    const toggles = document.querySelectorAll('.column-toggle');
    toggles.forEach(toggle => {
        toggle.checked = checked;
        const columnName = toggle.getAttribute('data-column');
        toggleColumn(columnName, checked);
    });
}

/**
 * Save column preference to localStorage
 */
function saveColumnPreference(columnName, show) {
    const prefs = JSON.parse(localStorage.getItem('rawDataColumnPrefs') || '{}');
    prefs[columnName] = show;
    localStorage.setItem('rawDataColumnPrefs', JSON.stringify(prefs));
}

/**
 * Load column preferences
 */
function loadColumnPreferences() {
    const prefs = JSON.parse(localStorage.getItem('rawDataColumnPrefs') || '{}');

    Object.keys(prefs).forEach(columnName => {
        const toggle = document.querySelector(`.column-toggle[data-column="${columnName}"]`);
        if (toggle) {
            toggle.checked = prefs[columnName];
            toggleColumn(columnName, prefs[columnName]);
        }
    });
}

/**
 * Set up touch-friendly scrolling for iPad
 */
function setupTouchScrolling() {
    const tableContainer = document.querySelector('.table-container');
    if (!tableContainer) return;

    let isScrolling = false;
    let startX, startY, scrollLeft, scrollTop;

    tableContainer.addEventListener('touchstart', function(e) {
        isScrolling = true;
        startX = e.touches[0].pageX - tableContainer.offsetLeft;
        startY = e.touches[0].pageY - tableContainer.offsetTop;
        scrollLeft = tableContainer.scrollLeft;
        scrollTop = tableContainer.scrollTop;
    });

    tableContainer.addEventListener('touchmove', function(e) {
        if (!isScrolling) return;
        e.preventDefault();

        const x = e.touches[0].pageX - tableContainer.offsetLeft;
        const y = e.touches[0].pageY - tableContainer.offsetTop;
        const walkX = (x - startX) * 2;
        const walkY = (y - startY) * 2;

        tableContainer.scrollLeft = scrollLeft - walkX;
        tableContainer.scrollTop = scrollTop - walkY;
    });

    tableContainer.addEventListener('touchend', function() {
        isScrolling = false;
    });
}

/**
 * Set up tooltips for truncated content
 */
function setupTooltips() {
    const cells = document.querySelectorAll('#rawDataTable tbody td');

    cells.forEach(cell => {
        if (cell.scrollWidth > cell.offsetWidth) {
            cell.setAttribute('title', cell.textContent);
            cell.style.cursor = 'help';
        }
    });
}

/**
 * Change records per page limit
 */
function changeLimit(newLimit) {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('limit', newLimit);
    urlParams.set('offset', '0'); // Reset to first page
    window.location.search = urlParams.toString();
}

/**
 * Reset all filters
 */
function resetFilters() {
    // Clear search
    document.getElementById('tableSearch').value = '';
    performTableSearch('');

    // Reset all columns to visible
    selectAllColumns(true);

    // Clear URL parameters except machine
    const urlParams = new URLSearchParams(window.location.search);
    const machine = urlParams.get('machine');
    window.location.href = `rawdata.php${machine ? '?machine=' + encodeURIComponent(machine) : ''}`;
}

/**
 * Export table data to CSV
 */
async function exportTableData() {
    const machine = new URLSearchParams(window.location.search).get('machine') || 'all';

    // Show progress
    showExportProgress();

    try {
        // Fetch all data for export
        const response = await fetch(`ajax/export_raw_data.php?machine=${encodeURIComponent(machine)}`);

        if (!response.ok) {
            throw new Error('Export failed');
        }

        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `feedback_raw_${machine}_${new Date().toISOString().split('T')[0]}.csv`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);

        hideExportProgress();

    } catch (error) {
        console.error('Export error:', error);
        alert('Failed to export data. Please try again.');
        hideExportProgress();
    }
}

/**
 * Show export progress
 */
function showExportProgress() {
    const progress = document.createElement('div');
    progress.className = 'export-progress';
    progress.innerHTML = `
        <div class="d-flex align-items-center">
            <div class="spinner-border spinner-border-sm me-2" role="status"></div>
            <span>Exporting data...</span>
        </div>
    `;
    document.body.appendChild(progress);
}

/**
 * Hide export progress
 */
function hideExportProgress() {
    const progress = document.querySelector('.export-progress');
    if (progress) {
        progress.remove();
    }
}

// Load preferences on page load
window.addEventListener('load', function() {
    loadColumnPreferences();
});