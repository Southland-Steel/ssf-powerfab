<!DOCTYPE html>
<html>
<head>
    <title>Cut List View</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="cutlist_style.css?<?= time(); ?>">
</head>
<body class="bg-light">
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
                    <h5>Machine Group</h5>
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
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Cut List Items</h4>
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
                        <th>Grade</th>
                        <th>Job Number</th>
                        <th>Sequence</th>
                        <th>Work Package</th>
                        <th>Main Mark</th>
                        <th>Machine Group</th>
                        <th>Machine</th>
                        <th>Cutlist</th>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    let allData = [];
    let uniqueFilters = {
        machineGroups: new Set(),
        machines: new Set(),
        shapes: new Set(),
        grades: new Set(),
        dimensions: new Set()
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
            dimensions: new Set()
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

        const filteredData = allData.filter(item => {
            return (selectedMachineGroups.length === 0 || selectedMachineGroups.includes(item.MachineGroup)) &&
                (selectedMachines.length === 0 || selectedMachines.includes(item.MachineName)) &&
                (selectedShapes.length === 0 || selectedShapes.includes(item.Shape)) &&
                (selectedGrades.length === 0 || selectedGrades.includes(item.Grade)) &&
                (selectedDimensions.length === 0 || selectedDimensions.includes(item.DimensionString));
        });

        // Get available values for each filter based on current filtered data
        const availableValues = {
            machineGroup: new Set(filteredData.map(item => item.MachineGroup)),
            machine: new Set(filteredData.map(item => item.MachineName)),
            shape: new Set(filteredData.map(item => item.Shape)),
            grade: new Set(filteredData.map(item => item.Grade)),
            dimension: new Set(filteredData.map(item => item.DimensionString))
        };

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

        // Display grouped data
        Object.values(groupedData).forEach(item => {
            const row = document.createElement('tr');
            row.setAttribute('data-id', item.ProductionControlCutListItemID);
            row.style.cursor = 'pointer';
            row.innerHTML = `
            <td>${item.NestNumber || ''}</td>
            <td>${item.Barcode || ''}</td>
            <td>${item.Shape}</td>
            <td>${item.DimensionString}</td>
            <td>${item.Grade}</td>
            <td>${item.JobNumber}</td>
            <td>Sequence</td>
            <td>${item.WorkPackageNumber}</td>
            <td>${item.MainMark}</td>
            <td>${item.MachineGroup}</td>
            <td>${item.MachineName}</td>
            <td>${item.CutlistName}</td>
        `;
            tbody.appendChild(row);

            // Add click event listener to show/hide details
            row.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                toggleDetails(id, filteredData);
            });
        });
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
    <td colspan="12">
        <h6>Related Items</h6>
        <table class="table">
            <thead>
                <tr>
                    <th>MainMark</th>
                    <th>PieceMark</th>
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
    });
</script>
</body>
</html>