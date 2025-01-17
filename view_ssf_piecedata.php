<?php
// view_ssf_piecedata.php

$currentYear = substr(date('o'), -2);
$currentWeek = date('W'); // Gets the week number (01-53)
$currentWorkweek = intval($currentYear . str_pad($currentWeek, 2, '0', STR_PAD_LEFT));

$workweek = $_GET['workweek'] ?? $currentWorkweek;

require_once 'config_ssf_db.php';

// Query the database using Medoo to fetch distinct WorkPackageNumber
$resources = $db->query("
SELECT DISTINCT Group2 as WorkWeeks FROM workpackages INNER JOIN productioncontroljobs as pcj ON pcj.productionControlID = workpackages.productionControlID WHERE Completed = 0 AND OnHold = 0 ORDER BY WorkWeeks ASC;
")->fetchAll(PDO::FETCH_ASSOC);

$weeks = array_filter(array_column($resources, 'WorkWeeks'), function($week) {
return $week !== null && $week !== '';
});

sort($weeks);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Production Control Data</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        .sidebar {
            background-color: #f8f9fa;
            padding: 15px;
            border-right: 1px solid #dee2e6;
        }
        .nest-group-item {
            cursor: pointer;
            padding: 5px 10px;
            margin: 2px 0;
            border-radius: 4px;
        }
        .nest-group-item:hover {
            background-color: #e9ecef;
        }
        .nest-group-item.active {
            background-color: #0d6efd;
            color: white;
        }
        .filter-btn {
            margin: 2px;
            white-space: nowrap;
        }
        .filter-btn.active {
            background-color: #0d6efd;
            color: white;
        }
        .filter-section {
            padding: 10px;
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 10px;
        }
        #loading {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1000;
            background: rgba(255, 255, 255, 0.8);
            padding: 20px;
            border-radius: 10px;
        }
        .workweek-btn {
            margin: 2px;
        }
        .filter-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <!-- Work Week Buttons -->
    <div class="row mt-3 mb-3">
        <div class="col-12">
            <div class="filter-header">
                <h6>Work Weeks</h6>
                <button class="btn btn-outline-secondary btn-sm" onclick="resetWorkWeek()">Reset</button>
            </div>
            <div class="d-flex flex-wrap" id="workweekButtons">
                <?php
                // First set of weeks (2448-2452)
                for($i = 2448; $i <= 2452; $i++) {
                    echo "<button class='btn btn-outline-primary btn-sm workweek-btn m-1' data-workweek='$i'>$i</button>";
                }
                // Second set of weeks (2501-2510)
                for($i = 2501; $i <= 2510; $i++) {
                    echo "<button class='btn btn-outline-primary btn-sm workweek-btn m-1' data-workweek='$i'>$i</button>";
                }
                ?>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Sidebar with Nest Groups -->
        <div class="col-md-2">
            <div class="filter-header">
                <h5>Nest Groups</h5>
                <button class="btn btn-outline-secondary btn-sm" onclick="resetNestGroup()">Reset</button>
            </div>
            <div id="nestGroupsList" class="mb-3">
                <!-- Nest groups will be populated here -->
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-10">
            <!-- Work Package Buttons -->
            <div class="filter-section">
                <div class="filter-header">
                    <h6>Work Packages</h6>
                    <button class="btn btn-outline-secondary btn-sm" onclick="resetWorkPackage()">Reset</button>
                </div>
                <div id="workPackageButtons">
                    <!-- Work package buttons will be populated here -->
                </div>
            </div>

            <!-- Shape Buttons -->
            <div class="filter-section mb-3">
                <div class="filter-header">
                    <h6>Shapes</h6>
                    <button class="btn btn-outline-secondary btn-sm" onclick="resetShape()">Reset</button>
                </div>
                <div id="shapeButtons">
                    <!-- Shape buttons will be populated here -->
                </div>
            </div>

            <!-- Loading Spinner -->
            <div id="loading" class="d-none">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>

            <!-- Data Table -->
            <div class="table-responsive">
                <table id="dataTable" class="table table-striped table-bordered table-sm">
                    <thead>
                    <tr>
                        <th>Job Number</th>
                        <th>Work Package</th>
                        <th>Sequence Name</th>
                        <th>Lot Number</th>
                        <th>Main Mark</th>
                        <th>Piece Mark</th>
                        <th>Shape</th>
                        <th>Dimension</th>
                        <th>Length (in)</th>
                        <th>Sequence Qty</th>
                        <th>Pieces/Assembly</th>
                        <th>Pieces/Sequence</th>
                        <th>Nest Qty</th>
                        <th>Nest Group</th>
                        <th>Bar Number</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
    let dataTable;
    let activeFilters = {
        workweek: '2503',
        nestGroup: null,
        workPackage: null,
        shape: null
    };

    function initializeDataTable(data) {
        if (dataTable) {
            dataTable.destroy();
        }

        dataTable = $('#dataTable').DataTable({
            data: data,
            columns: [
                { data: 'JobNumber' },
                { data: 'WorkPackageNumber' },
                { data: 'SequenceName' },
                { data: 'LotNumber' },
                { data: 'MainMark' },
                { data: 'PieceMark' },
                { data: 'Shape' },
                { data: 'DimensionString' },
                { data: 'InchLength' },
                { data: 'SequenceAssemblyQuantity' },
                { data: 'PiecesPerAssembly' },
                { data: 'PiecesPerSequence' },
                { data: 'NestQuantity' },
                { data: 'NestGroup' },
                { data: 'NestNumber' }
            ],
            scrollY: '60vh',
            scrollX: true,
            scrollCollapse: true,
            paging: true,
            searching: true,
            ordering: true,
            pageLength: 100
        });
    }

    function loadWorkweekData(workweek) {
        $('#loading').removeClass('d-none');

        $.ajax({
            url: 'ajax_ssf_get_piecedata.php',
            method: 'POST',
            data: { workweek: workweek },
            dataType: 'json',
            success: function(response) {
                if (response.error) {
                    alert('Error: ' + response.error);
                    return;
                }

                updateFilterButtons(response);
                initializeDataTable(response);
            },
            error: function(xhr, status, error) {
                alert('Error loading data: ' + error);
            },
            complete: function() {
                $('#loading').addClass('d-none');
            }
        });
    }

    function updateFilterButtons(data) {
        // Create an array of objects containing both nest group and shape
        const nestGroupsWithShapes = data.reduce((acc, row) => {
            // Include null/undefined nest groups
            const nestGroup = row.NestGroup || 'Undefined';
            const existingEntry = acc.find(item => item.nestGroup === nestGroup);
            if (!existingEntry) {
                acc.push({
                    nestGroup: nestGroup,
                    shape: row.Shape || 'Unknown'
                });
            }
            return acc;
        }, []);

        // Sort by shape first, then nest group, with "Undefined" at the end
        nestGroupsWithShapes.sort((a, b) => {
            if (a.nestGroup === 'Undefined' && b.nestGroup !== 'Undefined') return 1;
            if (a.nestGroup !== 'Undefined' && b.nestGroup === 'Undefined') return -1;
            if (a.shape === b.shape) {
                return a.nestGroup.localeCompare(b.nestGroup);
            }
            return a.shape.localeCompare(b.shape);
        });

        // Create the display text for each nest group
        const nestGroupsHtml = nestGroupsWithShapes.map(item => {
            let displayText;
            if (item.nestGroup === 'Undefined') {
                displayText = 'Undefined';
            } else {
                displayText = item.shape.toLowerCase() === 'plate'
                    ? `PL - ${item.nestGroup}`
                    : `${item.shape} - ${item.nestGroup}`;
            }
            return `<div class="nest-group-item" data-nest-group="${escapeHtml(item.nestGroup)}" data-shape="${escapeHtml(item.shape)}">${escapeHtml(displayText)}</div>`;
        }).join('');

        $('#nestGroupsList').html(nestGroupsHtml);

        // Update Work Package buttons - include undefined
        const workPackages = [...new Set(data.map(row => row.WorkPackageNumber || 'Undefined'))].sort((a, b) => {
            if (a === 'Undefined') return 1;
            if (b === 'Undefined') return -1;
            return a.localeCompare(b);
        });

        const workPackagesHtml = workPackages.map(wp =>
            `<button class="btn btn-outline-primary btn-sm filter-btn${wp === 'Undefined' ? ' btn-outline-secondary' : ''}" data-wp="${escapeHtml(wp)}">${escapeHtml(wp)}</button>`
        ).join('');
        $('#workPackageButtons').html(workPackagesHtml);

        // Update Shape buttons - include undefined
        const shapes = [...new Set(data.map(row => row.Shape || 'Undefined'))].sort((a, b) => {
            if (a === 'Undefined') return 1;
            if (b === 'Undefined') return -1;
            return a.localeCompare(b);
        });

        const shapesHtml = shapes.map(shape =>
            `<button class="btn btn-outline-primary btn-sm filter-btn${shape === 'Undefined' ? ' btn-outline-secondary' : ''}" data-shape="${escapeHtml(shape)}">${escapeHtml(shape)}</button>`
        ).join('');
        $('#shapeButtons').html(shapesHtml);
    }

    function escapeHtml(unsafe) {
        if (unsafe == null) return '';
        return unsafe
            .toString()
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function resetWorkWeek() {
        $('.workweek-btn').removeClass('active');
        activeFilters.workweek = '2503';
        loadWorkweekData('2503');
    }

    function resetNestGroup() {
        $('.nest-group-item').removeClass('active');
        activeFilters.nestGroup = null;
        applyFilters();
    }

    function resetWorkPackage() {
        $('[data-wp]').removeClass('active');
        activeFilters.workPackage = null;
        applyFilters();
    }

    function resetShape() {
        $('[data-shape]').removeClass('active');
        activeFilters.shape = null;
        applyFilters();
    }

    function applyFilters() {
        dataTable.draw();
    }

    function decimalInchesToFractional(decimalInches) {
        // Ensure we're working with a number and round to 4 decimal points for better precision
        decimalInches = Number(decimalInches);
        if (isNaN(decimalInches)) return '';

        decimalInches = Math.round(decimalInches * 10000) / 10000;

        // Convert to feet and inches
        const totalFeet = Math.floor(decimalInches / 12);
        const remainingInches = decimalInches % 12;

        // Get whole inches
        const wholeInches = Math.floor(remainingInches);

        // Get fractional inches (to 16ths)
        const fractionalInches = remainingInches - wholeInches;
        // Multiply by 16 and round to nearest whole number to get 16ths
        let sixteenths = Math.round(fractionalInches * 16);

        // Handle carry-over if we round up to 16/16
        if (sixteenths === 16) {
            sixteenths = 0;
            wholeInches += 1;
            // Check if inches now equals 12, if so, increment feet
            if (wholeInches === 12) {
                totalFeet += 1;
                wholeInches = 0;
            }
        }

        // Simplify fraction if possible
        function simplifyFraction(numerator, denominator) {
            if (numerator === 0) return { numerator: 0, denominator: 1 };
            const gcd = (a, b) => b === 0 ? a : gcd(b, a % b);
            const divisor = gcd(numerator, denominator);
            return {
                numerator: numerator / divisor,
                denominator: denominator / divisor
            };
        }

        // Format output
        let result = '';
        if (totalFeet > 0) {
            result += `${totalFeet}'-`;
        }

        if (sixteenths === 0) {
            result += `${wholeInches}"`;
        } else {
            const fraction = simplifyFraction(sixteenths, 16);
            result += `${wholeInches} ${fraction.numerator}/${fraction.denominator}"`;
        }

        return result;
    }

    // Event handlers
    $(document).ready(function() {
        // Work Week button clicks
        $(document).on('click', '.workweek-btn', function() {
            $('.workweek-btn').removeClass('active');
            $(this).addClass('active');
            activeFilters.workweek = $(this).data('workweek');
            loadWorkweekData(activeFilters.workweek);
        });

        // Nest Group clicks
        $(document).on('click', '.nest-group-item', function() {
            $('.nest-group-item').removeClass('active');
            if (activeFilters.nestGroup === $(this).data('nest-group')) {
                activeFilters.nestGroup = null;
            } else {
                $(this).addClass('active');
                activeFilters.nestGroup = $(this).data('nest-group');
            }
            applyFilters();
        });

        // Work Package button clicks
        $(document).on('click', '[data-wp]', function() {
            $('[data-wp]').removeClass('active');
            if (activeFilters.workPackage === $(this).data('wp')) {
                activeFilters.workPackage = null;
            } else {
                $(this).addClass('active');
                activeFilters.workPackage = $(this).data('wp');
            }
            applyFilters();
        });

        // Shape button clicks
        $(document).on('click', '[data-shape]', function() {
            $('[data-shape]').removeClass('active');
            if (activeFilters.shape === $(this).data('shape')) {
                activeFilters.shape = null;
            } else {
                $(this).addClass('active');
                activeFilters.shape = $(this).data('shape');
            }
            applyFilters();
        });

        // Initial load
        loadWorkweekData('2503');
    });

    // Custom filtering function for DataTables
    $.fn.dataTable.ext.search.push(function(settings, searchData, index, rowData, counter) {
        const matchNestGroup = !activeFilters.nestGroup ||
            (activeFilters.nestGroup === 'Undefined' && !rowData.NestGroup) ||
            String(rowData.NestGroup) === String(activeFilters.nestGroup);

        const matchWorkPackage = !activeFilters.workPackage ||
            (activeFilters.workPackage === 'Undefined' && !rowData.WorkPackageNumber) ||
            rowData.WorkPackageNumber === activeFilters.workPackage;

        const matchShape = !activeFilters.shape ||
            (activeFilters.shape === 'Undefined' && !rowData.Shape) ||
            rowData.Shape === activeFilters.shape;

        return matchNestGroup && matchWorkPackage && matchShape;
    });
</script>
</body>
</html>