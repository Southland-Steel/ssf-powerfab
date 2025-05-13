/**
 * checkups/js/invalidations-ui.js
 * UI functionality for the cutlist invalidations page
 */

const InvalidationsUI = (() => {
    // Private variables
    let config = {
        tableId: 'invalidationsTable',
        modalId: 'itemDetailsModal'
    };

    // Initialize the module
    const init = (options) => {
        config = { ...config, ...options };

        // Set up event listeners
        setupEventListeners();
    };

    // Set up event listeners
    const setupEventListeners = () => {
        // Event delegation for table rows
        const table = document.getElementById(config.tableId);
        if (table) {
            table.addEventListener('click', handleTableClick);
        }

        // Make the table sortable
        setupSortableTable();

        // Setup search functionality
        setupSearch();
    };

    // Handle clicks on the table
    const handleTableClick = (event) => {
        // Find the closest row
        const row = event.target.closest('tr.invalidation-row');
        if (!row) return;

        // Get the item ID
        const itemId = row.dataset.itemId;
        if (!itemId) return;

        // Show the details modal
        showItemDetails(itemId);
    };

    // Show item details in the modal
    const showItemDetails = (itemId) => {
        // Get the item data
        const item = InvalidationsCore.getItemById(itemId);
        if (!item) {
            alert('Item not found!');
            return;
        }

        // Get the modal
        const modal = document.getElementById(config.modalId);
        if (!modal) return;

        // Get the modal content
        const modalContent = document.getElementById('itemDetailsContent');
        if (!modalContent) return;

        // Update the modal title
        const modalTitle = modal.querySelector('.modal-title');
        if (modalTitle) {
            modalTitle.textContent = `Cutlist Item Details - ID: ${itemId}`;
        }

        // Generate the content
        const content = `
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <h6 class="fw-bold">Cutlist Information</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <tr>
                                    <th>Cutlist ID:</th>
                                    <td>${item.ProductionControlCutListID}</td>
                                </tr>
                                <tr>
                                    <th>Item ID:</th>
                                    <td>${item.ProductionControlCutListItemID}</td>
                                </tr>
                                <tr>
                                    <th>Description:</th>
                                    <td>${item.CutlistDescription || 'N/A'}</td>
                                </tr>
                                <tr>
                                    <th>Machine:</th>
                                    <td>${item.MachineName || 'N/A'}</td>
                                </tr>
                                <tr>
                                    <th>Workshop:</th>
                                    <td>${item.WorkshopName || 'N/A'}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <h6 class="fw-bold">Timing Information</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <tr>
                                    <th>Created:</th>
                                    <td>${item.DateTimeCreatedFormatted || 'N/A'}</td>
                                </tr>
                                <tr>
                                    <th>Invalidated:</th>
                                    <td>${item.DateTimeInvalidatedFormatted || 'N/A'}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <h6 class="fw-bold">Part Information</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <tr>
                                    <th>Shape:</th>
                                    <td>${item.ShapeName || 'N/A'}</td>
                                </tr>
                                <tr>
                                    <th>Grade:</th>
                                    <td>${item.Grade || 'N/A'}</td>
                                </tr>
                                <tr>
                                    <th>Dimension:</th>
                                    <td>${item.DimensionSizesImperial || 'N/A'}</td>
                                </tr>
                                <tr>
                                    <th>Length:</th>
                                    <td title="${item.LengthInches || 'N/A'} inches">
                                        ${typeof inchesToFeetInches === 'function' ? inchesToFeetInches(item.LengthInches) : (item.LengthInches || 'N/A')}
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <h6 class="fw-bold">Nest Information</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <tr>
                                    <th>Nest #1:</th>
                                    <td>${item.CutlistNumber1 || 'N/A'}</td>
                                </tr>
                                <tr>
                                    <th>Nest #2:</th>
                                    <td>${item.CutlistNumber2 || 'N/A'}</td>
                                </tr>
                                <tr>
                                    <th>Barcode ID:</th>
                                    <td>${item.ProductionControlCutListBarcodeID || 'N/A'}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        This item has been invalidated and requires attention from the nesting department.
                    </div>
                </div>
            </div>
        `;

        modalContent.innerHTML = content;

        // Initialize and show the modal using Bootstrap
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    };

    // Setup sortable table
    const setupSortableTable = () => {
        const table = document.getElementById(config.tableId);
        if (!table) return;

        const headers = table.querySelectorAll('thead th');

        headers.forEach((header, index) => {
            header.addEventListener('click', () => {
                // Get the current sort direction
                const currentDir = header.dataset.sortDir || 'none';

                // Reset all headers
                headers.forEach(h => {
                    h.dataset.sortDir = 'none';
                    h.classList.remove('sort-asc', 'sort-desc');
                });

                // Set the new sort direction
                let newDir = 'asc';
                if (currentDir === 'asc') {
                    newDir = 'desc';
                }

                header.dataset.sortDir = newDir;
                header.classList.add(`sort-${newDir}`);

                // Sort the table
                sortTable(index, newDir);
            });
        });
    };

    // Sort the table
    const sortTable = (columnIndex, direction) => {
        const table = document.getElementById(config.tableId);
        if (!table) return;

        const tbody = table.querySelector('tbody');
        if (!tbody) return;

        const rows = Array.from(tbody.querySelectorAll('tr.invalidation-row'));

        // Sort the rows
        rows.sort((a, b) => {
            const aValue = a.cells[columnIndex].textContent.trim();
            const bValue = b.cells[columnIndex].textContent.trim();

            // Compare the values
            if (aValue === bValue) return 0;

            // Check if the values are numbers
            const aNum = parseFloat(aValue);
            const bNum = parseFloat(bValue);

            if (!isNaN(aNum) && !isNaN(bNum)) {
                return direction === 'asc' ? aNum - bNum : bNum - aNum;
            }

            // Compare as strings
            return direction === 'asc'
                ? aValue.localeCompare(bValue)
                : bValue.localeCompare(aValue);
        });

        // Reappend the rows in sorted order
        rows.forEach(row => {
            tbody.appendChild(row);
        });
    };

    // Setup search functionality
    const setupSearch = () => {
        // Since we're only showing active invalidations that need attention,
        // there's no need to implement search functionality for removed items
        // This functionality has been intentionally omitted per requirements
    };

    // Filter the table based on search input - Not used as search functionality was removed
    const filterTable = () => {
        // Functionality removed as per requirements
    };

    // Update the filtered record count - Not used as search functionality was removed
    const updateFilteredCount = () => {
        // Functionality removed as per requirements
    };

    // Public API
    return {
        init
    };
})();