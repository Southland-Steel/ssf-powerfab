/**
 * assets/js/common.js
 * Common JavaScript functions for the SSF Production Management System
 */

// Wait for document to be ready
document.addEventListener('DOMContentLoaded', function() {
    initializeLoading();
    initializeAjaxHandlers();
    initializeWorkWeeks();
});

/**
 * Initialize loading overlay functionality
 */
function initializeLoading() {
    // Create global loading functions
    window.showLoading = function() {
        document.getElementById('loading-overlay').style.display = 'flex';
    };

    window.hideLoading = function() {
        document.getElementById('loading-overlay').style.display = 'none';
    };
}

/**
 * Initialize AJAX request handlers
 */
function initializeAjaxHandlers() {
    // Show loading overlay on AJAX requests if jQuery is available
    if (typeof $ !== 'undefined') {
        $(document).ajaxStart(function() {
            window.showLoading();
        }).ajaxStop(function() {
            window.hideLoading();
        });
    }

    // Add fetch request interceptor
    const originalFetch = window.fetch;
    window.fetch = function() {
        window.showLoading();
        return originalFetch.apply(this, arguments)
            .then(function(response) {
                window.hideLoading();
                return response;
            })
            .catch(function(error) {
                window.hideLoading();
                throw error;
            });
    };
}

/**
 * Initialize work weeks functionality if needed
 */
function initializeWorkWeeks() {
    if (document.getElementById('activeWorkWeeks') && typeof loadAvailableWeeks === 'function') {
        loadAvailableWeeks();
    }
}

/**
 * Load data for a specific work week
 * @param {string} week - The work week to load data for
 * @param {string} endpoint - The AJAX endpoint to fetch data from
 * @param {function} callback - Function to call with the loaded data
 */
function loadWeekData(week, endpoint, callback) {
    // Update active button state
    document.querySelectorAll('.week-btn').forEach(btn => {
        btn.classList.remove('active');
        if (btn.textContent === week.toString()) {
            btn.classList.add('active');
        }
    });

    // Store selected week in session storage
    sessionStorage.setItem('selectedWeek', week);

    // Fetch data for selected week
    fetch(`${endpoint}?workweek=${week}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (typeof callback === 'function') {
                callback(data);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading week data');
        });
}

/**
 * Format a date in a user-friendly format
 * @param {string} dateString - The date string to format
 * @param {boolean} includeTime - Whether to include time in the formatted string
 * @return {string} The formatted date string
 */
function formatDate(dateString, includeTime = false) {
    if (!dateString) return '';

    const date = new Date(dateString);
    const options = {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    };

    if (includeTime) {
        options.hour = '2-digit';
        options.minute = '2-digit';
    }

    return date.toLocaleDateString('en-US', options);
}

/**
 * Format a number as currency
 * @param {number} amount - The amount to format
 * @param {string} currencyCode - The currency code (default: USD)
 * @return {string} The formatted currency string
 */
function formatCurrency(amount, currencyCode = 'USD') {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currencyCode
    }).format(amount);
}

/**
 * Create an exportable table from data
 * @param {Array} data - The data to display in the table
 * @param {Array} columns - The columns to display
 * @param {string} tableId - The ID of the table element
 */
function createDataTable(data, columns, tableId) {
    const table = document.getElementById(tableId);
    if (!table) return;

    const thead = table.querySelector('thead');
    const tbody = table.querySelector('tbody');

    // Clear existing content
    thead.innerHTML = '';
    tbody.innerHTML = '';

    // Create header row
    const headerRow = document.createElement('tr');
    columns.forEach(column => {
        const th = document.createElement('th');
        th.textContent = column.label || column.field;
        headerRow.appendChild(th);
    });
    thead.appendChild(headerRow);

    // Create data rows
    data.forEach(item => {
        const row = document.createElement('tr');

        columns.forEach(column => {
            const td = document.createElement('td');

            // Apply formatter if specified
            if (typeof column.formatter === 'function') {
                td.innerHTML = column.formatter(item[column.field], item);
            } else {
                td.textContent = item[column.field] || '';
            }

            // Apply CSS class if specified
            if (typeof column.cellClass === 'function') {
                const className = column.cellClass(item[column.field], item);
                if (className) {
                    td.classList.add(className);
                }
            }

            row.appendChild(td);
        });

        tbody.appendChild(row);
    });
}

/**
 * Export table data to CSV
 * @param {string} tableId - The ID of the table to export
 * @param {string} filename - The filename for the exported CSV
 */
function exportTableToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return;

    let csv = [];
    const rows = table.querySelectorAll('tr');

    for (let i = 0; i < rows.length; i++) {
        const row = [], cols = rows[i].querySelectorAll('td, th');

        for (let j = 0; j < cols.length; j++) {
            // Get the text content and escape double quotes
            let data = cols[j].textContent.replace(/"/g, '""');
            // Wrap with quotes if the text contains commas or quotes
            if (data.includes(',') || data.includes('"') || data.includes('\n')) {
                data = `"${data}"`;
            }
            row.push(data);
        }

        csv.push(row.join(','));
    }

    // Download CSV file
    const csvContent = 'data:text/csv;charset=utf-8,' + csv.join('\n');
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement('a');
    link.setAttribute('href', encodedUri);
    link.setAttribute('download', filename || 'export.csv');
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}