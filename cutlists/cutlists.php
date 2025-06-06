<!DOCTYPE html>
<html>
<head>
    <title>Cut List View</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <link rel="stylesheet" href="cutlist_style.css?<?= time(); ?>">
</head>
<body class="bg-light">
<div class="position-fixed top-0 end-0 p-3" style="z-index: 1030;">
    <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#helpModal" id="helpModalBtn">
        <i class="bi bi-question-circle me-1"></i>
        View Guide
    </button>
</div>
<div id="workweeks" class="container-fluid">
    <div id="activeWorkWeeks">
        <!-- Work week buttons will be inserted here -->
    </div>
</div>

<div class="container-fluid py-1">
    <!-- Filters -->
    <div class="row mb-1">
        <div class="col">
            <div class="d-flex flex-wrap">

                <div class="filter-section">
                    <h5>Group</h5>
                    <div id="machineGroupFilter" class="checkbox-container">
                        <!-- Populated by JavaScript -->
                    </div>
                </div>
                <div class="filter-section">
                    <h5>Machine</h5>
                    <div id="machineFilter" class="checkbox-container">
                        <!-- Populated by JavaScript -->
                    </div>
                </div>
                <div class="filter-section">
                    <h5>Shape</h5>
                    <div id="shapeFilter" class="checkbox-container">
                        <!-- Populated by JavaScript -->
                    </div>
                </div>
                <div class="filter-section">
                    <h5>Work Package</h5>
                    <div id="workPackageFilter" class="checkbox-container">
                        <!-- Populated by JavaScript -->
                    </div>
                </div>
                <div class="filter-section">
                    <h5>Grade</h5>
                    <div id="gradeFilter" class="checkbox-container">
                        <!-- Populated by JavaScript -->
                    </div>
                </div>
                <div class="filter-section">
                    <h5>Dimension</h5>
                    <div id="dimensionFilter" class="checkbox-container">
                        <!-- Populated by JavaScript -->
                    </div>
                </div>
                <div class="filter-section">
                    <h5>LOC</h5>
                    <div id="locationFilter" class="checkbox-container">
                        <!-- Populated by JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Cut List Items</h4>
        </div>
        <div class="stats-summary card-body bg-light py-2 border-bottom">
            <div class="row text-center align-items-center">
                <div class="col-md-2">
                    <strong>Line Items:</strong><br>
                    <span id="summary-line-items">0</span>
                </div>
                <div class="col-md-2">
                    <strong>Total Weight:</strong><br>
                    <span id="summary-weight">0</span> lbs
                </div>
                <div class="col-md-2">
                    <strong>Total Pieces to Cut:</strong><br>
                    <span id="summary-pieces">0</span>
                </div>
                <div class="col-md-2">
                    <strong>Unique Jobs:</strong><br>
                    <span id="summary-jobs">0</span>
                </div>
                <div class="col-md-2">
                    <strong>Work Packages:</strong><br>
                    <span id="summary-packages">0</span>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#detailsModal">
                        View Detailed Summary
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="dataTable" class="table table-striped table-bordered table-hover mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>Nest Number</th>
                        <th>Barcode</th>
                        <th>Shape</th>
                        <th>Dimension</th>
                        <th>Length</th>
                        <th>Grade</th>
                        <th>Location</th>
                        <th>Job Number</th>
                        <th>Sequence</th>
                        <th>Work Package</th>
                        <th>Weight (lbs)</th>
                        <th>Group</th>
                        <th>Machine</th>
                        <th>[ID] Cutlist</th>
                    </tr>
                    </thead>
                    <tbody>
                    <!-- Data populated via JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loading-overlay" style="display: none;">
    <div class="loading-spinner">
        Loading...
    </div>
</div>

<!-- Modal structure -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-xl"  style="max-width: 90vw;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detailed Production Summary</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card mb-3">
                            <div class="card-header">By Machine</div>
                            <div class="card-body" id="machine-breakdown">
                                <!-- Populated by JavaScript -->
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card mb-3">
                            <div class="card-header">By Sequence</div>
                            <div class="card-body" id="sequence-breakdown">
                                <!-- Populated by JavaScript -->
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card mb-3">
                            <div class="card-header">By Work Package</div>
                            <div class="card-body" id="workpackage-breakdown">
                                <!-- Populated by JavaScript -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="helpModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title d-flex align-items-center">
                    <i class="bi bi-book me-2"></i>
                    Grid Structures Post Fabrication Status Guide
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body guide-content">
                <div id="guideContent" class="markdown-body px-4">
                    <!-- The markdown content will be inserted here -->
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
    let allData = [];
    let uniqueFilters = {
        machineGroups: new Set(),
        machines: new Set(),
        shapes: new Set(),
        grades: new Set(),
        dimensions: new Set(),
        locations: new Set(),
        workPackages: new Set()
    };

    // Load available weeks on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadAvailableWeeks();
    });

    function showLoading() {
        document.getElementById('loading-overlay').style.display = 'flex';
    }

    function hideLoading() {
        document.getElementById('loading-overlay').style.display = 'none';
    }

    function loadAvailableWeeks() {
        showLoading();
        fetch('ajax_get_cutlist_workweeks.php')
            .then(response => response.json())
            .then(data => {
                const weeklist = data.weeks.map(week => {
                    return `<button class="week-btn" onclick="loadWeekData('${week}')">${week}</button>`;
                }).join(' ');

                document.getElementById('activeWorkWeeks').innerHTML =
                    `<strong>Work Weeks:</strong> ${weeklist}`;

                if (data.weeks.length > 0) {
                    const weekToLoad = data.selectedWeek || data.currentWeek;
                    loadWeekData(weekToLoad);
                }
            })
            .catch(error => {
                console.error('Error loading weeks:', error);
                alert('Error loading available weeks');
            })
            .finally(() => {
                hideLoading();
            });
    }

    function loadWeekData(week) {
        showLoading();
        // Update active button state
        document.querySelectorAll('.week-btn').forEach(btn => {
            btn.classList.remove('active');
            if (btn.textContent === week.toString()) {
                btn.classList.add('active');
            }
        });

        // Clear filters
        uniqueFilters = {
            machineGroups: new Set(),
            machines: new Set(),
            shapes: new Set(),
            grades: new Set(),
            dimensions: new Set(),
            locations: new Set(),
            workPackages: new Set()
        };

        // Fetch data for selected week
        fetch(`ajax_get_cutlist_data.php?workweek=${week}`)
            .then(response => response.json())
            .then(data => {
                allData = data;

                // Collect unique filter values
                data.forEach(item => {
                    uniqueFilters.machineGroups.add(item.MachineGroup);
                    uniqueFilters.machines.add(item.MachineName);
                    uniqueFilters.shapes.add(item.Shape);
                    uniqueFilters.grades.add(item.Grade);
                    uniqueFilters.dimensions.add(item.DimensionString);
                    uniqueFilters.locations.add(item.Location || '-');
                    uniqueFilters.workPackages.add(item.WorkPackageNumber || '-');
                });

                // Populate filter checkboxes
                populateFilterCheckboxes();

                // Display data
                displayFilteredData();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading week data');
            })
            .finally(() => {
                hideLoading();
            });
    }

    function populateFilterCheckboxes() {
        // Populate Machine Group filter
        document.getElementById('machineGroupFilter').innerHTML = Array.from(uniqueFilters.machineGroups)
            .sort()
            .map(group => createCheckboxHTML('machineGroup', group))
            .join('');

        // Populate Machine filter
        document.getElementById('machineFilter').innerHTML = Array.from(uniqueFilters.machines)
            .sort()
            .map(machine => createCheckboxHTML('machine', machine))
            .join('');

        // Populate Shape filter
        document.getElementById('shapeFilter').innerHTML = Array.from(uniqueFilters.shapes)
            .sort()
            .map(shape => createCheckboxHTML('shape', shape))
            .join('');

        // Populate work package filter
        document.getElementById('workPackageFilter').innerHTML = Array.from(uniqueFilters.workPackages)
            .sort()
            .map(wp => createCheckboxHTML('workPackage', wp))
            .join('');

        // Populate Grade filter
        document.getElementById('gradeFilter').innerHTML = Array.from(uniqueFilters.grades)
            .sort()
            .map(grade => createCheckboxHTML('grade', grade))
            .join('');

        // Populate Dimension filter
        document.getElementById('dimensionFilter').innerHTML = Array.from(uniqueFilters.dimensions)
            .sort()
            .map(dim => createCheckboxHTML('dimension', dim))
            .join('');

        document.getElementById('locationFilter').innerHTML = Array.from(uniqueFilters.locations)
            .sort()
            .map(location => createCheckboxHTML('location', location))
            .join('');

        // Add event listeners to all checkboxes
        document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                displayFilteredData();
            });
        });

        // Initial call to set up initial filter states
        displayFilteredData();
    }

    // Create checkbox HTML (unchanged)
    function createCheckboxHTML(type, value) {
        return `
        <div class="form-check">
            <input class="form-check-input" type="checkbox" value="${value}" id="${type}_${value}" name="${type}">
            <label class="form-check-label" for="${type}_${value}">${value}</label>
        </div>`;
    }

    function getSelectedFilters(filterName) {
        return Array.from(document.querySelectorAll(`input[name="${filterName}"]:checked`))
            .map(cb => cb.value);
    }

    function displayFilteredData() {
        const selectedMachineGroups = getSelectedFilters('machineGroup');
        const selectedMachines = getSelectedFilters('machine');
        const selectedShapes = getSelectedFilters('shape');
        const selectedGrades = getSelectedFilters('grade');
        const selectedDimensions = getSelectedFilters('dimension');
        const selectedLocations = getSelectedFilters('location');
        const selectedWorkPackages = getSelectedFilters('workPackage');

        const filteredData = allData.filter(item => {
            return (selectedMachineGroups.length === 0 || selectedMachineGroups.includes(item.MachineGroup)) &&
                (selectedMachines.length === 0 || selectedMachines.includes(item.MachineName)) &&
                (selectedShapes.length === 0 || selectedShapes.includes(item.Shape)) &&
                (selectedGrades.length === 0 || selectedGrades.includes(item.Grade)) &&
                (selectedDimensions.length === 0 || selectedDimensions.includes(item.DimensionString)) &&
                (selectedLocations.length === 0 || selectedLocations.includes(item.Location || '-')) &&
                (selectedWorkPackages.length === 0 || selectedWorkPackages.includes(item.WorkPackageNumber || '-'));
        });

        // Get available values for each filter based on current filtered data
        const availableValues = {
            machineGroup: new Set(filteredData.map(item => item.MachineGroup)),
            machine: new Set(filteredData.map(item => item.MachineName)),
            shape: new Set(filteredData.map(item => item.Shape)),
            grade: new Set(filteredData.map(item => item.Grade)),
            dimension: new Set(filteredData.map(item => item.DimensionString)),
            location: new Set(filteredData.map(item => item.Location || '-')),
            workPackage: new Set(filteredData.map(item => item.WorkPackageNumber || '-'))
        };

        // Add this code to update the counts based on visible items
        const visibleDimensions = new Set(filteredData.map(item => item.DimensionString));
        const visibleLocations = new Set(filteredData.map(item => item.Location || '-'));
        const visibleWorkPackages = new Set(filteredData.map(item => item.WorkPackageNumber || '-'));

        // Update the headers with current counts
        document.querySelectorAll('.filter-section h5').forEach(header => {
            if (header.textContent.includes('Dimension')) {
                header.innerHTML = `Dimension [${visibleDimensions.size}]`;
            }
            if (header.textContent.includes('LOC')) {
                header.innerHTML = `LOC [${visibleLocations.size}]`;
            }
            if (header.textContent.includes('Work Package')) {
                header.innerHTML = `Work Package [${visibleWorkPackages.size}]`;
            }
        });

        // Update filter visibility
        Object.entries(availableValues).forEach(([filterType, values]) => {
            document.querySelectorAll(`input[name="${filterType}"]`).forEach(checkbox => {
                const isAvailable = values.has(checkbox.value);
                const formCheck = checkbox.closest('.form-check');

                // Hide unavailable options unless they're checked
                if (formCheck) {
                    formCheck.style.display = isAvailable || checkbox.checked ? 'block' : 'none';
                }
            });
        });

        const tbody = document.querySelector('#dataTable tbody');
        tbody.innerHTML = '';

        // Group by ProductionControlCutListItemID
        const groupedData = {};
        filteredData.forEach(item => {
            if (!groupedData[item.ProductionControlCutListItemID]) {
                groupedData[item.ProductionControlCutListItemID] = item;
            }
        });

        updateStatusSummary(filteredData, groupedData);

        // Display grouped data
        Object.values(groupedData)
            .sort((a, b) => {
                // Handle null/undefined/empty cases
                const aStr = a.NestNumber || '';
                const bStr = b.NestNumber || '';

                // If either is empty, sort empty ones last
                if (!aStr) return 1;
                if (!bStr) return -1;

                // Split the nest numbers into their parts (before and after hyphen)
                const [aMain, aSecondary] = aStr.split('-').map(n => parseInt(n) || 0);
                const [bMain, bSecondary] = bStr.split('-').map(n => parseInt(n) || 0);

                // Compare main numbers first
                if (aMain !== bMain) {
                    return aMain - bMain;
                }

                // If main numbers are equal, compare secondary numbers
                return aSecondary - bSecondary;
            })
            .forEach(item => {
                const row = document.createElement('tr');
                row.setAttribute('data-id', item.ProductionControlCutListItemID);
                row.style.cursor = 'pointer';
                row.innerHTML = `
            <td>${item.NestNumber || '-'}</td>
            <td>${item.Barcode || '-'}</td>
            <td>${item.Shape}</td>
            <td>${item.DimensionString}</td>
            <td>${item.Length || '-'}</td>
            <td>${item.Grade}</td>
            <td>
                <a href="#"
                   class="location-link"
                   data-bs-toggle="popover"
                   data-bs-trigger="click"
                   data-bs-html="true"
                   data-inventory-id="${item.InventoryItemID || ''}"
                   data-location="${item.Location || '-'}"
                   title="Serial Numbers">
                    ${item.Location || '-'}
                </a>
            </td>
            <td>${item.JobNumber}</td>
            <td>${item.Sequence}</td>
            <td>${item.WorkPackageNumber}</td>
            <td>${item.Weight}</td>
            <td>${item.MachineGroup}</td>
            <td>${item.MachineName}</td>
            <td>[${item.ProductionControlCutlistID}] ${item.CutlistName}</td>
        `;
                tbody.appendChild(row);

                // Add click event listener to show/hide details
                row.addEventListener('click', function (e) {
                    // Don't trigger row click if clicking the location link
                    if (e.target.closest('.location-link')) {
                        return;
                    }
                    const id = this.getAttribute('data-id');
                    toggleDetails(id, filteredData);
                });

                // Add location link click handler
                const locationLink = row.querySelector('.location-link');
                if (locationLink) {
                    locationLink.addEventListener('click', function (e) {
                        e.preventDefault();
                        e.stopPropagation();

                        // Close all other popovers first
                        document.querySelectorAll('.location-link').forEach(link => {
                            if (link !== this) {
                                const popover = bootstrap.Popover.getInstance(link);
                                if (popover) {
                                    popover.dispose();
                                }
                            }
                        });

                        // If popover already exists on this link, close it and return
                        const existingPopover = bootstrap.Popover.getInstance(this);
                        if (existingPopover) {
                            existingPopover.dispose();
                            return;
                        }

                        // If popover already exists, return
                        if (bootstrap.Popover.getInstance(this)) {
                            return;
                        }

                        const inventoryId = this.getAttribute('data-inventory-id');
                        if (!inventoryId) {
                            return;
                        }

                        // Show loading state
                        new bootstrap.Popover(this, {
                            content: 'Loading serial numbers...',
                            html: true
                        }).show();

                        // Fetch serial numbers
                        fetch(`ajax_get_serial_numbers.php?inventoryId=${inventoryId}`)
                            .then(response => response.json())
                            .then(data => {
                                // Destroy existing popover
                                const popover = bootstrap.Popover.getInstance(this);
                                if (popover) {
                                    popover.dispose();
                                }

                                // Create content from serial numbers - note we're accessing SerialNumber property
                                const content = data.length > 0
                                    ? `<div class="serial-numbers">
                                 <strong>Location: ${this.getAttribute('data-location')}</strong><br>
                                 ${data.map(item => `<div>${item.SerialNumber}</div>`).join('')}
                               </div>`
                                    : 'No serial numbers found';

                                // Initialize new popover with data
                                new bootstrap.Popover(this, {
                                    content: content,
                                    html: true,
                                    template: '<div class="popover" role="tooltip"><div class="popover-arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>'
                                }).show();
                            })
                            .catch(error => {
                                console.error('Error fetching serial numbers:', error);
                                const popover = bootstrap.Popover.getInstance(this);
                                if (popover) {
                                    popover.dispose();
                                }
                                new bootstrap.Popover(this, {
                                    content: 'Error loading serial numbers',
                                    html: true
                                }).show();
                            });
                    });
                }
            });


    }

    function updateStatusSummary(filteredData, groupedData) {
        // Calculate basic summary statistics
        const totalLineItems = Object.keys(groupedData).length;
        const totalWeight = filteredData.reduce((sum, item) => sum + (parseFloat(item.Weight) || 0), 0);
        const totalPieces = filteredData.reduce((sum, item) => sum + (parseInt(item.Quantity) || 0), 0);
        const uniqueJobs = new Set(filteredData.map(item => item.JobNumber)).size;
        const uniqueWorkPackages = new Set(filteredData.map(item => item.WorkPackageNumber)).size;

        // Update summary values
        document.getElementById('summary-line-items').textContent = totalLineItems.toLocaleString();
        document.getElementById('summary-weight').textContent = totalWeight.toLocaleString();
        document.getElementById('summary-pieces').textContent = totalPieces.toLocaleString();
        document.getElementById('summary-jobs').textContent = uniqueJobs.toLocaleString();
        document.getElementById('summary-packages').textContent = uniqueWorkPackages.toLocaleString();

        // Calculate machine load stats
        const machineLoadStats = {};
        filteredData.forEach(item => {
            if (!machineLoadStats[item.MachineName]) {
                machineLoadStats[item.MachineName] = {
                    pieces: 0,
                    weight: 0
                };
            }
            machineLoadStats[item.MachineName].pieces += (parseInt(item.Quantity) || 0);
            machineLoadStats[item.MachineName].weight += (parseFloat(item.Weight) || 0);
        });

        // Update machine load stats
        const machineStats = {};
        filteredData.forEach(item => {
            if (!machineStats[item.MachineName]) {
                machineStats[item.MachineName] = {
                    lineItems: new Set(),
                    pieces: 0,
                    weight: 0
                };
            }
            machineStats[item.MachineName].lineItems.add(item.ProductionControlCutListItemID);
            machineStats[item.MachineName].pieces += (parseInt(item.Quantity) || 0);
            machineStats[item.MachineName].weight += (parseFloat(item.Weight) || 0);
        });

        const machineHtml = Object.entries(machineStats)
            .map(([machine, stats]) => `
            <div class="mb-2">
                <strong>${machine}</strong><br>
                Line Items: ${stats.lineItems.size}<br>
                Pieces: ${stats.pieces.toLocaleString()}<br>
                Weight: ${Math.round(stats.weight).toLocaleString()} lbs
            </div>
        `).join('');
        document.getElementById('machine-breakdown').innerHTML = machineHtml;

        // Sequence breakdown
        const sequenceStats = {};
        filteredData.forEach(item => {
            const seq = item.Sequence || 'Unassigned';
            if (!sequenceStats[seq]) {
                sequenceStats[seq] = {
                    lineItems: new Set(),
                    pieces: 0,
                    weight: 0
                };
            }
            sequenceStats[seq].lineItems.add(item.ProductionControlCutListItemID);
            sequenceStats[seq].pieces += (parseInt(item.Quantity) || 0);
            sequenceStats[seq].weight += (parseFloat(item.Weight) || 0);
        });

        const sequenceHtml = Object.entries(sequenceStats)
            .map(([seq, stats]) => `
            <div class="mb-2">
                <strong>${seq}</strong><br>
                Line Items: ${stats.lineItems.size}<br>
                Pieces: ${stats.pieces.toLocaleString()}<br>
                Weight: ${Math.round(stats.weight).toLocaleString()} lbs
            </div>
        `).join('');
        document.getElementById('sequence-breakdown').innerHTML = sequenceHtml;

        // Work Package breakdown
        const packageStats = {};
        filteredData.forEach(item => {
            const pkg = item.WorkPackageNumber || 'Unassigned';
            if (!packageStats[pkg]) {
                packageStats[pkg] = {
                    lineItems: new Set(),
                    pieces: 0,
                    weight: 0
                };
            }
            packageStats[pkg].lineItems.add(item.ProductionControlCutListItemID);
            packageStats[pkg].pieces += (parseInt(item.Quantity) || 0);
            packageStats[pkg].weight += (parseFloat(item.Weight) || 0);
        });

        const packageHtml = Object.entries(packageStats)
            .map(([pkg, stats]) => `
            <div class="mb-2">
                <strong>${pkg}</strong><br>
                Line Items: ${stats.lineItems.size}<br>
                Pieces: ${stats.pieces.toLocaleString()}<br>
                Weight: ${Math.round(stats.weight).toLocaleString()} lbs
            </div>
        `).join('');
        document.getElementById('workpackage-breakdown').innerHTML = packageHtml;
    }



    function toggleDetails(id, data) {
        const parentRow = document.querySelector(`tr[data-id="${id}"]`);
        const existingDetail = document.querySelector(`.detail-row[data-parent="${id}"]`);

        // Remove expanded class from all rows
        document.querySelectorAll('tr').forEach(row => row.classList.remove('expanded-row'));

        // Remove any existing detail rows
        document.querySelectorAll('.detail-row').forEach(row => row.remove());

        // If we're closing the detail row, just return
        if (existingDetail) {
            return;
        }

        // Add expanded class to parent row
        parentRow.classList.add('expanded-row');

        // Find all related items
        const relatedItems = data.filter(item => item.ProductionControlCutListItemID.toString() === id.toString());
        console.log(relatedItems);
        console.log(id);

        // Create detail row
        const detailRow = document.createElement('tr');
        detailRow.className = 'detail-row';
        detailRow.setAttribute('data-parent', id);

        // Create detail content
        const detailContent = `
            <td colspan="14">
                <h6>Related Items</h6>
                <table class="table">
                    <thead>
                        <tr>
                            <th>MainMark</th>
                            <th>PieceMark</th>
                            <th>Width</th>
                            <th>Length</th>
                            <th>Quantity</th>
                            <th>WorkWeek</th>
                            <th>Sequence</th>
                            <th>Category</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${relatedItems.map(item => `
                            <tr>
                                <td>${item.MainMark}</td>
                                <td>${item.PieceMark}</td>
                                <td>${item.PartWidth || '-'}"</td>
                                <td title="${item.PartLength || '-'}" >${item.PartLength ? inchesToFeetAndInches(item.PartLength) : '-'}</td>
                                <td>${item.Quantity}</td>
                                <td>${item.WorkWeek}</td>
                                <td class="${item.Sequence ? '' : 'undefined'}">${item.Sequence || 'undefined'}</td>
                                <td>${item.Category || ''}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </td>`;

        detailRow.innerHTML = detailContent;

        // Insert detail row after the clicked row
        parentRow.after(detailRow);

        // Show the detail row with animation
        setTimeout(() => {
            detailRow.style.display = 'table-row';
        }, 0);
    }

    // Function to sync machine filters with machine group selection
    function syncMachineFilters() {
        const selectedMachineGroups = getSelectedFilters('machineGroup');
        const machineCheckboxes = document.querySelectorAll('input[name="machine"]');

        machineCheckboxes.forEach(cb => {
            const machineData = allData.find(item => item.MachineName === cb.value);
            if (machineData) {
                cb.disabled = selectedMachineGroups.length > 0 &&
                    !selectedMachineGroups.includes(machineData.MachineGroup);
                if (cb.disabled) {
                    cb.checked = false;
                }
            }
        });

        displayFilteredData();
    }

    // Add event listener for machine group changes
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('input[name="machineGroup"]').forEach(checkbox => {
            checkbox.addEventListener('change', syncMachineFilters);
        });

        const guideContent = document.getElementById('guideContent');
        if (guideContent) {
            fetch('guide-cutlists.md')
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
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.location-link') && !e.target.closest('.popover')) {
            document.querySelectorAll('.location-link').forEach(link => {
                const popover = bootstrap.Popover.getInstance(link);
                if (popover) {
                    popover.dispose();
                }
            });
        }
    });

    function inchesToFeetAndInches(inches) {
        inches = parseFloat(inches);
        const feet = Math.floor(inches / 12);
        const remainingInches = inches % 12;
        const wholeInches = Math.floor(remainingInches);
        const fractionNumerator = Math.round((remainingInches - wholeInches) * 16);

        const fractions = {
            16: '',
            15: '15/16',
            14: '7/8',
            13: '13/16',
            12: '3/4',
            11: '11/16',
            10: '5/8',
            9: '9/16',
            8: '1/2',
            7: '7/16',
            6: '3/8',
            5: '5/16',
            4: '1/4',
            3: '3/16',
            2: '1/8',
            1: '1/16'
        };

        let wholeInchesAdjusted = wholeInches;
        let fractionStr = '';

        if (fractionNumerator === 16) {
            wholeInchesAdjusted++;
        } else {
            fractionStr = fractionNumerator > 0 ? ' ' + fractions[fractionNumerator] : '';
        }

        return (feet > 0 ? `${feet}'-` : '') + wholeInchesAdjusted + fractionStr + '"';
    }

</script>
</body>
</html>