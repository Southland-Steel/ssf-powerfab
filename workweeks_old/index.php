<?php
// workweeks/index.php

// Set page title and other variables for header
$page_title = "Work Weeks";
$show_workweeks = false; // We're handling workweeks ourselves in this page
$extra_css = '<link rel="stylesheet" href="css/workweeks.css">
<link rel="stylesheet" href="css/workweeks-style-fixes.css">';
$extra_js = '
<script src="js/workweeks-utils.js"></script>
<script src="js/workweeks-core.js"></script>
<script src="js/workweeks-stations.js"></script>
<script src="js/workweeks-display.js"></script>
<script src="js/workweeks-filters.js"></script>
<script src="js/workweeks-help.js"></script>
';

// Calculate current workweek
require_once __DIR__ . '/../includes/functions/utility_functions.php';
$currentWorkweek = getCurrentWorkWeek();
$workweek = $_GET['workweek'] ?? $currentWorkweek;

// Include the header
include_once __DIR__ . '/../includes/header.php';
?>

    <!-- Big text indicator for current work week -->
    <div id="big-text"><?php echo $workweek; ?></div>

    <!-- Container for work weeks selector -->
    <div id="activeFabWorkpackages" class="container-fluid mb-3">
        <!-- Active fabrication jobs will be inserted here via JavaScript -->
    </div>

    <div id="projectData" class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="mb-0" style="font-size: 1.5rem;">Workweek Details</h2>
            <img src="../assets/images/ssf-logo.png" alt="Southland Steel" height="40px">
        </div>

        <div class="row">
            <div class="col-lg-9">
                <div id="projectSummary" class="card">
                    <div class="card-header bg-ssf-primary text-white">
                        Project Summary (for what's visible)
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 summary-data">
                                <h6>Line Items</h6>
                                <p id="lineItemSummary"></p>
                            </div>
                            <div class="col-md-4 summary-data">
                                <h6>Hours</h6>
                                <p id="hoursSummary"></p>
                            </div>
                            <div class="col-md-4 summary-data">
                                <h6>Weight</h6>
                                <p id="weightSummary"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
                <table id="weekschedule" class="table-bordered">
                    <thead>
                    <tr>
                        <th>Workweek for</th>
                        <th>Week #</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    function getAdjustedWorkWeek($workweek, $offset) {
                        return $workweek + $offset;
                    }
                    ?>
                    <tr>
                        <td>CNC</td>
                        <td><?= getAdjustedWorkWeek($workweek, 6); ?></td>
                    </tr>
                    <tr>
                        <td>Cut</td>
                        <td><?= getAdjustedWorkWeek($workweek, 4); ?></td>
                    </tr>
                    <tr>
                        <td>Kit</td>
                        <td><?= getAdjustedWorkWeek($workweek, 3); ?></td>
                    </tr>
                    <tr>
                        <td>Fit</td>
                        <td><?= getAdjustedWorkWeek($workweek, 1); ?></td>
                    </tr>
                    <tr>
                        <td>Weld &amp; Final QC</td>
                        <td><?= getAdjustedWorkWeek($workweek, 0); ?></td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="filters-wrapper">
            <div class="filter-group" id="bayFilter">
                <!-- Bay filters will be inserted here -->
            </div>
            <div class="filter-group" id="wpFilter">
                <!-- Work package filters will be inserted here -->
            </div>
            <div class="filter-group" id="routeFilter">
                <!-- Route filters will be inserted here -->
            </div>
            <div class="filter-group" id="categoryFilter">
                <!-- Category filters will be inserted here -->
            </div>
            <div class="filter-group" id="sequenceFilter">
                <!-- Sequence filters will be inserted here -->
            </div>
        </div>

        <div class="table-responsive">
            <table id="projectTable" class="table table-bordered table-striped">
                <thead>
                <!-- Table header will be dynamically populated -->
                </thead>
                <tbody>
                <!-- Table body will be dynamically populated -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Piecemark Details Modal -->
    <div class="modal fade" id="piecemarkModal" tabindex="-1" aria-labelledby="piecemarkModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="piecemarkModalLabel">Piecemark Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-striped" id="piecemarkTable">
                        <thead>
                        <!-- Header data will dynamically be inserted here -->
                        </thead>
                        <tbody>
                        <!-- Body data will dynamically be inserted here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- JSON Data Modal -->
    <div class="modal fade" id="jsonModal" tabindex="-1" aria-labelledby="jsonModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="jsonModalLabel">Project Data Details</h5>
                    <div class="ms-3">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="expandAllJson()">Expand All</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="collapseAllJson()">Collapse All</button>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="jsonContent" style="max-height: 70vh; overflow-y: auto;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="copyJsonToClipboard()">Copy to Clipboard</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Help Modal -->
    <div class="modal fade" id="helpModal" tabindex="-1" aria-labelledby="helpModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="helpModalLabel">Workweeks Module Help</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="helpContent">
                    <!-- Help content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize the application when the DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            // Set initial workweek to what's in the URL or the current one
            const workweek = '<?php echo $workweek; ?>';
            initializeWorkweeks(workweek);

            // Add click handlers for export buttons if needed
            const exportBtn = document.createElement('button');
            exportBtn.className = 'btn btn-sm btn-outline-success position-fixed';
            exportBtn.style.right = '100px';
            exportBtn.style.bottom = '10px';
            exportBtn.style.zIndex = '1000';
            exportBtn.innerHTML = '<i class="bi bi-file-excel"></i> Export to CSV';
            exportBtn.onclick = function() {
                exportTableToCSV('projectTable', `workweek_${workweek}_data.csv`);
            };
            document.body.appendChild(exportBtn);
        });

        // Function to export table to CSV
        function exportTableToCSV(tableId, filename) {
            const table = document.getElementById(tableId);
            if (!table) return;

            let csv = [];
            const rows = table.querySelectorAll('tr');

            for (let i = 0; i < rows.length; i++) {
                const row = [];
                const cols = rows[i].querySelectorAll('td, th');

                for (let j = 0; j < cols.length; j++) {
                    // Get text content (strip HTML)
                    let data = cols[j].textContent.replace(/(\r\n|\n|\r)/gm, ' ').trim();

                    // Escape double quotes
                    data = data.replace(/"/g, '""');

                    // Add quotes if the content contains commas or quotes
                    if (data.includes(',') || data.includes('"')) {
                        data = `"${data}"`;
                    }

                    row.push(data);
                }

                csv.push(row.join(','));
            }

            // Create a CSV file and download it
            const csvContent = 'data:text/csv;charset=utf-8,' + csv.join('\n');
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement('a');
            link.setAttribute('href', encodedUri);
            link.setAttribute('download', filename);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>

<?php
// Include the footer
include_once __DIR__ . '/../includes/footer.php';
?>