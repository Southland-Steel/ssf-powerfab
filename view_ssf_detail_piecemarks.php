<?php
// view_ssf_workweeks.php

$currentYear = substr(date('o'), -2);
$currentWeek = date('W');
$currentWorkweek = intval($currentYear . str_pad($currentWeek, 2, '0', STR_PAD_LEFT));

// Validate workweek input
$workweek = filter_input(INPUT_GET, 'workweek', FILTER_VALIDATE_INT) ?? $currentWorkweek;

require_once 'config_ssf_db.php';

try {
    // Query the database using Medoo to fetch distinct WorkPackageNumber
    $resources = $db->query("
        SELECT DISTINCT Group2 as WorkWeeks 
        FROM workpackages 
        INNER JOIN productioncontroljobs as pcj 
            ON pcj.productionControlID = workpackages.productionControlID 
        WHERE Completed = 0 AND OnHold = 0 
        ORDER BY WorkWeeks ASC;
    ")->fetchAll(PDO::FETCH_ASSOC);

    $weeks = array_filter(array_column($resources, 'WorkWeeks'), function($week) {
        return $week !== null && $week !== '';
    });

    sort($weeks);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die("Unable to fetch work weeks. Please try again later.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SSF Detail Piecemarks</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .week-btn {
            padding: 2px 20px;
            margin: 5px;
            background-color: #99332B;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            font-size: 14px;
        }

        .week-btn:hover {
            background-color: #7A2822;
        }

        .week-btn:active {
            background-color: #5C1E1A;
            transform: scale(0.95);
        }

        .week-btn.active {
            background-color: white;
            border: 1px solid #99332B;
            color: #99332B;
            font-weight: bold;
        }

        .filter-btn {
            padding: 2px 20px;
            margin: 5px;
            background-color: #2B6299;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            font-size: 14px;
        }

        .filter-btn:hover {
            background-color: #224E7A;
        }

        .filter-btn:active {
            background-color: #1A3B5C;
            transform: scale(0.95);
        }

        .filter-btn.active {
            background-color: white;
            border: 1px solid #2B6299;
            color: #2B6299;
            font-weight: bold;
        }

        .filter-btn:disabled {
            cursor: not-allowed;
            background-color: #cccccc;
        }

        .opacity-50 {
            opacity: 0.5;
        }

        .loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .table-container {
            margin: 0;
            padding: 0;
            background: #fff;
            width: 100%;
            height: calc(100vh - 20px);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .table-header {
            padding: 20px 20px 0 20px;
        }

        .table-wrapper {
            flex: 1;
            overflow: auto;
            padding: 0 20px 20px 20px;
            position: relative;
            width: 100%;
        }

        .custom-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .custom-table th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            position: sticky;
            top: 0;
            z-index: 10;
            cursor: pointer;
            padding: 12px 15px;
        }

        .custom-table td {
            border-bottom: 1px solid #dee2e6;
            padding: 12px 15px;
            white-space: nowrap;
        }

        .custom-table th,
        .custom-table td {
            box-sizing: border-box;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        #filterContainer {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        #filterContainer > div {
            margin-bottom: 10px;
        }

        #filterContainer > div:last-child {
            margin-bottom: 0;
        }

        #recordCount {
            color: #666;
            font-size: 0.9em;
        }

        .row-highlight { background-color: #fff3cd; }
        .row-warning { background-color: #f8d7da; }
        .row-success { background-color: #d4edda; }
    </style>
</head>
<body>
<div id="activeFabWorkpackages" class="container-fluid">
    <!-- Active fabrication jobs will be inserted here -->
</div>
<div class="container-fluid">
    <div class="loading d-none" role="status" aria-live="polite">
        <div class="spinner-border text-primary">
            <span class="visually-hidden">Loading data, please wait...</span>
        </div>
    </div>

    <div class="table-container">
        <div class="table-header">
            <h2 class="mb-4">SSF Detail Piecemarks</h2>
        </div>
        <div class="table-wrapper">
            <table id="piecemarkTable" class="custom-table">
                <thead>
                <tr>
                    <th data-column="JobNumber" role="columnheader" aria-sort="none">
                        <span>Job Number</span>
                        <span class="d-block">SeqLot</span>
                    </th>
                    <th data-column="WorkPackageNumber" role="columnheader" aria-sort="none">
                        <span>WP</span>
                        <span class="d-block">QAssembly</span>
                    </th>
                    <th data-column="MainMark" role="columnheader" aria-sort="none">
                        <span>Main</span>
                        <span class="d-block">PieceMark</span>
                    </th>
                    <th data-column="Route" role="columnheader" aria-sort="none">
                        <span>Route</span>
                        <span class="d-block">Category</span>
                    </th>
                    <th data-column="Shape" role="columnheader" aria-sort="none">Shape</th>
                    <th data-column="DimensionString" role="columnheader" aria-sort="none">Dimension</th>
                    <th data-column="InchLength" role="columnheader" aria-sort="none">Length (Inches)</th>
                    <th data-column="QuantityNested" role="columnheader" aria-sort="none">Nesting</th>
                    <th data-column="QuantityCutlisted" role="columnheader" aria-sort="none">Cut</th>
                    <th data-column="Kit" role="columnheader" aria-sort="none">Kit</th>
                </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    let currentData = [];
    let currentCategoryFilter = 'all';
    let currentRouteFilter = 'all';
    let currentShapeFilter = 'all';
    let currentSort = { column: 'InchLength', direction: 'asc' };

    function formatNumber(value) {
        return Number(value).toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function checkStatus(row) {
        return {
            isNested: row.QuantityNested > 0,
            isCutlisted: row.QuantityCutlisted > 0,
            needsAttention: row.QuantityNested < row.QuantityCutlisted
        };
    }

    function createFilterButtons() {
        const categories = [...new Set(currentData.map(item => item.CategoryName).filter(Boolean))];
        const routes = [...new Set(currentData.map(item => item.Route).filter(Boolean))];
        const shapes = [...new Set(currentData.map(item => item.Shape).filter(Boolean))];

        let filterContainer = document.getElementById('filterContainer');
        if (!filterContainer) {
            filterContainer = document.createElement('div');
            filterContainer.id = 'filterContainer';
            filterContainer.className = 'mb-3';
            document.querySelector('.table-header').appendChild(filterContainer);
        }

        let categoryFilterHtml = `
        <div class="mb-2">
            <strong>Category Filter: </strong>
            <button class="filter-btn active" onclick="filterCategory('all', this)">All Categories</button>`;

        categories.forEach(category => {
            categoryFilterHtml += `
            <button class="filter-btn" onclick="filterCategory('${category}', this)">
                ${category}
            </button>`;
        });

        let routeFilterHtml = `
        <div class="mb-2">
            <strong>Route Filter: </strong>
            <button class="filter-btn active" onclick="filterRoute('all', this)">All Routes</button>`;

        routes.forEach(route => {
            if (route) {
                routeFilterHtml += `
                <button class="filter-btn" onclick="filterRoute('${route}', this)">
                    ${route}
                </button>`;
            }
        });

        let shapeFilterHtml = `
        <div class="mb-2">
            <strong>Shape Filter: </strong>
            <button class="filter-btn active" onclick="filterShape('all', this)">All Shapes</button>`;

        shapes.forEach(shape => {
            if (shape) {
                shapeFilterHtml += `
                <button class="filter-btn" onclick="filterShape('${shape}', this)">
                    ${shape}
                </button>`;
            }
        });

        filterContainer.innerHTML = categoryFilterHtml + routeFilterHtml + shapeFilterHtml;
    }

    function filterCategory(category, button) {
        currentCategoryFilter = category;
        document.querySelectorAll('#filterContainer div:nth-child(1) .filter-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        button.classList.add('active');
        applyFilters();
    }

    function filterRoute(route, button) {
        currentRouteFilter = route;
        document.querySelectorAll('#filterContainer div:nth-child(2) .filter-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        button.classList.add('active');
        applyFilters();
    }

    function filterShape(shape, button) {
        currentShapeFilter = shape;
        document.querySelectorAll('#filterContainer div:nth-child(3) .filter-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        button.classList.add('active');
        applyFilters();
    }

    function updateFilterButtons() {
        // First disable all buttons except 'All' buttons
        document.querySelectorAll('#filterContainer .filter-btn').forEach(button => {
            if (!button.textContent.trim().startsWith('All')) {
                button.disabled = true;
                button.classList.add('opacity-50');
            }
        });

        // Get filtered data based on current filters
        const filteredData = currentData.filter(item => {
            const matchesCategory = currentCategoryFilter === 'all' ||
                item.CategoryName === currentCategoryFilter;
            const matchesRoute = currentRouteFilter === 'all' ||
                (currentRouteFilter === 'undefined' ? !item.Route : item.Route === currentRouteFilter);
            const matchesShape = currentShapeFilter === 'all' ||
                (currentShapeFilter === 'undefined' ? !item.Shape : item.Shape === currentShapeFilter);

            return matchesCategory && matchesRoute && matchesShape;
        });

        // Enable buttons based on filtered data
        filteredData.forEach(item => {
            if (item.Route) {
                document.querySelectorAll('#filterContainer div:nth-child(2) .filter-btn').forEach(button => {
                    if (button.textContent.trim() === item.Route) {
                        button.disabled = false;
                        button.classList.remove('opacity-50');
                    }
                });
            }

            if (item.CategoryName) {
                document.querySelectorAll('#filterContainer div:nth-child(1) .filter-btn').forEach(button => {
                    if (button.textContent.trim() === item.CategoryName) {
                        button.disabled = false;
                        button.classList.remove('opacity-50');
                    }
                });
            }

            if (item.Shape) {
                document.querySelectorAll('#filterContainer div:nth-child(3) .filter-btn').forEach(button => {
                    if (button.textContent.trim() === item.Shape) {
                        button.disabled = false;
                        button.classList.remove('opacity-50');
                    }
                });
            }
        });
    }

    function applyFilters() {
        let filteredData = currentData.filter(item => {
            let matchesCategory = currentCategoryFilter === 'all' ||
                item.CategoryName === currentCategoryFilter;

            let matchesRoute = currentRouteFilter === 'all' ||
                (currentRouteFilter === 'undefined' ? !item.Route : item.Route === currentRouteFilter);

            let matchesShape = currentShapeFilter === 'all' ||
                (currentShapeFilter === 'undefined' ? !item.Shape : item.Shape === currentShapeFilter);

            return matchesCategory && matchesRoute && matchesShape;
        });

        filteredData = sortData(filteredData, currentSort.column, currentSort.direction);
        renderTable(filteredData);

        // Update filter buttons after applying filters
        updateFilterButtons();
    }

    function buildTableRow(row) {
        const status = checkStatus(row);
        const tr = document.createElement('tr');

        if (status.needsAttention) tr.classList.add('row-warning');
        if (status.isNested && status.isCutlisted) tr.classList.add('row-success');

        tr.innerHTML = `
            <td>${row.JobNumber}<br/>${row.SequenceName}</td>
            <td>${row.LotNumber}<br/>${row.WorkPackageNumber}</td>
            <td>${row.MainMark}<br/>${row.PieceMark}</td>
            <td>${row.Route || ''}<br/>${row.CategoryName}</td>
            <td>${row.Shape}</td>
            <td>${row.DimensionString}</td>
            <td>${formatNumber(row.InchLength)}</td>
            <td>${row.QuantityNested}</td>
            <td>${row.QuantityCutlisted}</td>
            <td>0</td>
        `;

        return tr;
    }

    function sortData(data, column, direction = 'asc') {
        const sortableData = [...data];
        return sortableData.sort((a, b) => {
            let valueA = a[column];
            let valueB = b[column];

            if (!isNaN(valueA) && !isNaN(valueB)) {
                valueA = Number(valueA);
                valueB = Number(valueB);
            }

            if (valueA < valueB) return direction === 'asc' ? -1 : 1;
            if (valueA > valueB) return direction === 'asc' ? 1 : -1;
            return 0;
        });
    }

    function renderTable(data) {
        const tbody = document.querySelector('#piecemarkTable tbody');
        tbody.innerHTML = '';

        const fragment = document.createDocumentFragment();
        data.forEach(row => {
            fragment.appendChild(buildTableRow(row));
        });

        tbody.appendChild(fragment);
    }

    function setupSortHandlers() {
        const sortHandlers = new Map(); // Store handlers to prevent duplicates

        document.querySelectorAll('#piecemarkTable th').forEach(th => {
            // Remove existing handler if it exists
            const oldHandler = sortHandlers.get(th);
            if (oldHandler) {
                th.removeEventListener('click', oldHandler);
            }

            // Create new handler
            const handler = () => {
                const column = th.dataset.column;
                const direction = th.getAttribute('aria-sort') === 'ascending' ? 'desc' : 'asc';

                // Update sort indicators
                document.querySelectorAll('#piecemarkTable th').forEach(header => {
                    header.setAttribute('aria-sort', 'none');
                });
                th.setAttribute('aria-sort', direction === 'asc' ? 'ascending' : 'descending');

                // Update current sort state
                currentSort = { column, direction };

                // Apply filters with new sort
                applyFilters();
            };

            // Store and add new handler
            sortHandlers.set(th, handler);
            th.addEventListener('click', handler);
        });
    }

    async function loadData(workweek) {
        const loading = document.querySelector('.loading');
        loading.classList.remove('d-none');

        try {
            const response = await fetch(`ajax_ssf_detail_piecemarks.php?workweek=${encodeURIComponent(workweek)}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const result = await response.json();

            currentData = result.data;
            createFilterButtons();
            applyFilters();

        } catch (error) {
            console.error('Error loading data:', error);
            alert('Error loading data. Please try again later.');
        } finally {
            loading.classList.add('d-none');
        }
    }

    function initializePage() {
        const currentWeek = <?= $workweek ?>;
        const weeks = <?= json_encode($weeks); ?>;
        const weeklist = [];

        weeks.forEach(week => {
            weeklist.push(`
                <button class="week-btn ${week == currentWeek ? 'active' : ''}"
                        onclick="loadData('${week}')"
                        aria-pressed="${week == currentWeek ? 'true' : 'false'}">
                    ${week}
                </button>`);
        });

        $(document).on('click', '.week-btn', function() {
            $('.week-btn').removeClass('active').attr('aria-pressed', 'false');
            $(this).addClass('active').attr('aria-pressed', 'true');
        });

        $('#activeFabWorkpackages').html(`<strong>Work Weeks:</strong> ${weeklist.join(' ')}`);

        setupSortHandlers();
        loadData(currentWeek);
    }

    document.addEventListener('DOMContentLoaded', initializePage);
</script>
</body>
</html>