/**
 * checkups/js/invalidations-core.js
 * Core functionality for handling cutlist invalidations data
 */

const InvalidationsCore = (() => {
    // Private variables
    let config = {
        apiUrl: '',
        onDataLoaded: null
    };

    let invalidationsData = [];

    // Initialize the module
    const init = (options) => {
        config = { ...config, ...options };
    };

    // Load invalidations data from the server
    const loadInvalidations = () => {
        // Show loading indicator
        window.showLoading();

        // Update the last updated timestamp
        const lastUpdated = document.getElementById('lastUpdated');
        if (lastUpdated) {
            lastUpdated.innerHTML = '<div class="spinner-border spinner-border-sm text-secondary me-2" role="status"></div> Updating...';
        }

        // Fetch data from the API
        fetch(config.apiUrl)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Store the data
                    invalidationsData = data.data;

                    // Update the table
                    renderInvalidationsTable();

                    // Update the timestamp
                    if (lastUpdated) {
                        lastUpdated.textContent = 'Last updated: ' + new Date().toLocaleString();
                    }

                    // Call the onDataLoaded callback if provided
                    if (typeof config.onDataLoaded === 'function') {
                        config.onDataLoaded(invalidationsData);
                    }
                } else {
                    console.error('Error loading invalidations:', data.error);
                    alert('Error loading invalidations: ' + data.error);
                }

                // Hide loading indicator
                window.hideLoading();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading invalidations data. Please try again later.');
                window.hideLoading();

                // Update the timestamp
                if (lastUpdated) {
                    lastUpdated.textContent = 'Last updated: Failed to update';
                }
            });
    };

    // Render the invalidations table
    const renderInvalidationsTable = () => {
        const tableBody = document.getElementById('invalidationsTableBody');
        if (!tableBody) return;

        // Clear existing content
        tableBody.innerHTML = '';

        // Update record count
        const recordCount = document.getElementById('recordCount');
        if (recordCount) {
            recordCount.textContent = invalidationsData.length;
        }

        // If no data, show a message
        if (invalidationsData.length === 0) {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td colspan="14" class="text-center py-4">
                    <i class="bi bi-check-circle-fill text-success fs-4 mb-2"></i>
                    <p class="mb-0">No invalidated cutlist items found.</p>
                </td>
            `;
            tableBody.appendChild(row);
            return;
        }

        // Render each row
        invalidationsData.forEach(item => {
            const row = document.createElement('tr');
            row.classList.add('invalidation-row');
            row.dataset.itemId = item.ProductionControlCutListItemID;

            row.innerHTML = `
                <td>${item.ProductionControlCutListID}</td>
                <td>${item.ProductionControlCutListItemID}</td>
                <td>${item.CutlistDescription || 'N/A'}</td>
                <td title="${item.DateTimeCreatedFormatted || 'N/A'}">${item.DateTimeCreatedRelative || 'N/A'}</td>
                <td title="${item.DateTimeInvalidatedFormatted || 'N/A'}">${item.DateTimeInvalidatedRelative || 'N/A'}</td>
                <td>${item.MachineName || 'N/A'}</td>
                <td>${item.WorkshopName || 'N/A'}</td>
                <td>${item.CutlistNumber1 || 'N/A'}</td>
                <td>${item.CutlistNumber2 || 'N/A'}</td>
                <td>${item.ShapeName || 'N/A'}</td>
                <td>${item.Grade || 'N/A'}</td>
                <td>${item.DimensionSizesImperial || 'N/A'}</td>
                <td title="${item.LengthInches || 'N/A'}">${typeof inchesToFeetInches === 'function' ? inchesToFeetInches(item.LengthInches) : (item.LengthInches || 'N/A')}</td>
                <td>
                    <span class="badge bg-danger">Invalidated</span>
                </td>
            `;

            tableBody.appendChild(row);
        });
    };

    // Get item details by ID
    const getItemById = (itemId) => {
        return invalidationsData.find(item => item.ProductionControlCutListItemID == itemId);
    };

    // Public API
    return {
        init,
        loadInvalidations,
        getItemById
    };
})();