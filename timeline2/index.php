<?php
/**
 * File: index.php (Modified with client-side filtering)
 * Gantt chart page with enhanced client-side filtering
 */
$pageTitle = 'Project Schedule Gantt Chart';

// Add custom CSS
$additionalCss = '
<link rel="stylesheet" href="css/gantt.css">
<link rel="stylesheet" href="css/gantt-responsive.css">
<link rel="stylesheet" href="css/gantt-custom.css">
<link rel="stylesheet" href="css/gantt-themes.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
';

// Add custom JS
$headerScripts = '
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/marked/4.3.0/marked.min.js"></script>
';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <?php echo $additionalCss; ?>

    <!-- Header Scripts -->
    <?php echo $headerScripts; ?>
</head>
<body>
<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Project Schedule Gantt Chart<span id="itemCountBadge" class="badge bg-secondary ms-2" title="Number of items displayed">0</span></h5>
                    <div>
                        <!-- Theme Switch -->
                        <span class="me-2">
                                <i class="bi bi-moon-fill theme-icon-dark"></i>
                                <i class="bi bi-sun-fill theme-icon-light"></i>
                                <label class="theme-switch ms-1">
                                    <input type="checkbox" id="themeSwitch" checked>
                                    <span class="theme-switch-slider"></span>
                                </label>
                            </span>

                        <button id="ganttZoomOut" class="btn btn-sm btn-outline-secondary" title="Zoom Out">
                            <i class="bi bi-zoom-out"></i>
                        </button>
                        <button id="ganttZoomReset" class="btn btn-sm btn-outline-secondary" title="Reset Zoom">
                            <i class="bi bi-arrows-fullscreen"></i>
                        </button>
                        <button id="ganttZoomIn" class="btn btn-sm btn-outline-secondary" title="Zoom In">
                            <i class="bi bi-zoom-in"></i>
                        </button>

                        <button id="refreshGantt" class="btn btn-sm btn-outline-primary ms-2">
                            <i class="bi bi-arrow-clockwise"></i> Refresh
                        </button>

                        <div class="btn-group ms-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" id="filterDropdownBtn">
                                <i class="bi bi-filter"></i> All
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" id="projectFilterDropdown">
                                <li><a class="dropdown-item" href="#" data-filter="all">All Projects</a></li>
                                <!-- Project filters will be added dynamically -->
                            </ul>
                        </div>

                        <button id="exportGantt" class="btn btn-sm btn-outline-secondary ms-2" onclick="GanttChart.Ajax.exportToCsv()">
                            <i class="bi bi-download"></i> Export
                        </button>

                        <button id="ganttHelpBtn" class="btn btn-sm btn-outline-info ms-2" title="Gantt Chart Help">
                            <i class="bi bi-question-circle"></i> Help
                        </button>
                    </div>
                </div>

                <div class="card-body p-0">
                    <!-- Legend for Gantt Chart -->
                    <div class="bg-light p-2 border-bottom" id="ganttLegend">
                        <small class="d-inline-block me-3"><span style="display:inline-block;width:12px;height:12px;background-color:var(--status-not-started);margin-right:5px;"></span> Not Started</small>
                        <small class="d-inline-block me-3"><span style="display:inline-block;width:12px;height:12px;background-color:var(--status-in-progress);margin-right:5px;"></span> In Progress</small>
                        <small class="d-inline-block me-3"><span style="display:inline-block;width:12px;height:12px;background-color:var(--status-completed);margin-right:5px;"></span> Completed</small>
                        <small class="d-inline-block me-3"><span style="display:inline-block;width:12px;height:12px;background-color:var(--status-late);margin-right:5px;"></span> Late</small>
                        <small class="d-inline-block me-3"><span style="display:inline-block;width:12px;height:12px;background-color:var(--status-on-hold);margin-right:5px;"></span> On Hold</small>
                        <small class="d-inline-block me-3"><span style="display:inline-block;width:2px;height:12px;background-color:var(--status-in-progress);margin-right:5px;"></span> Today</small>
                    </div>

                    <!-- Additional legend section: Markers and indicators -->
                    <div class="bg-light p-2 border-bottom">
                        <small class="d-inline-block me-3"><span style="display:inline-block;width:10px;height:10px;clip-path:polygon(50% 0%, 100% 50%, 50% 100%, 0% 50%);background-color:#7952b3;margin-right:5px;"></span> Start Date</small>
                        <small class="d-inline-block me-3"><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background-color:var(--status-late);margin-right:5px;"></span> End Date</small>
                        <small class="d-inline-block me-3"><span style="display:inline-block;width:10px;height:10px;clip-path:polygon(50% 0%, 100% 100%, 0% 100%);background-color:#fd7e14;margin-right:5px;"></span> Work Package</small>
                        <small class="d-inline-block me-3"><span style="display:inline-block;width:16px;height:16px;background-color:#ffc107;border-radius:50%;text-align:center;color:#000;font-weight:bold;">!</span> Warning</small>
                        <small class="d-inline-block me-3"><span style="display:inline-block;width:16px;height:16px;background-color:var(--status-late);border-radius:50%;text-align:center;color:#fff;font-weight:bold;">!</span> Critical</small>
                        <small class="d-inline-block me-3"><span style="display:inline-block;width:14px;height:14px;color:var(--status-in-progress);font-size:14px;"><i class="bi bi-link"></i></span> Linked Item</small>
                    </div>

                    <!-- Gantt filter buttons - now using client-side filtering -->
                    <div class="gantt-filter-container p-2 border-bottom">
                        <button class="gantt-filter-btn active" data-filter="all">All</button>
                        <button class="gantt-filter-btn" data-filter="in-progress">In Progress</button>
                        <button class="gantt-filter-btn" data-filter="ready-for-fabrication">Ready for Fabrication</button>
                        <button class="gantt-filter-btn" data-filter="has-workpackage">Has Work Package</button>
                        <button class="gantt-filter-btn" data-filter="categorize-needed">Categorization Needed</button>
                    </div>

                    <div id="ganttLoadingIndicator" class="gantt-loading">
                        <div class="gantt-loading-spinner"></div>
                        <p>Loading Gantt chart data...</p>
                    </div>

                    <div id="ganttContainer" class="gantt-container">
                        <div id="ganttTimelineHeader" class="gantt-timeline-header">
                            <!-- Week/month markers will be generated here -->
                        </div>

                        <div id="ganttBody" class="gantt-body">
                            <div id="ganttItemRows" class="gantt-item-rows">
                                <!-- Item rows will be generated here -->
                            </div>
                        </div>
                    </div>

                    <div id="noItemsMessage" class="alert alert-info text-center my-4" style="display: none;">
                        No projects found matching the current filter criteria.
                    </div>
                </div>

                <div class="card-footer d-flex justify-content-between">
                    <small class="text-muted">Click on any task bar to view more details</small>
                    <small class="text-muted">Hover over date markers for more information</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Gantt Chart Help Modal -->
<div class="modal fade gantt-help-modal" id="ganttHelpModal" tabindex="-1" aria-labelledby="ganttHelpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ganttHelpModalLabel">Gantt Chart Help</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="ganttHelpContent"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Gantt Chart Detail Modal -->
<div class="modal fade" id="ganttDetailModal" tabindex="-1" aria-labelledby="ganttDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ganttDetailModalLabel">Project Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="ganttDetailContent">
                <!-- Detail content will be inserted here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Load JavaScript files -->
<script src="js/gantt-core.js"></script>
<script src="js/gantt-timeutils.js"></script>
<script src="js/gantt-timeline.js"></script>
<script src="js/gantt-items.js"></script>
<script src="js/gantt-interactions.js"></script>
<script src="js/gantt-ajax.js"></script>
<script src="js/gantt-help.js"></script>
<script src="js/gantt-theme.js"></script>

<!-- Custom script for this specific implementation -->
<script src="js/gantt-custom.js"></script>

<script>
    window.timelineData = null;        // Cached timeline data
    window.workpackagesData = null;    // Cached workpackages data
    window.loadedDataFilter = null;    // Current filter for loaded data
    window.timelineXHR = null;         // Timeline data request object
    window.workpackagesXHR = null;     // Workpackages request object
    window.currentJobFilter = 'all';   // Current job filter
    window.catStatusCache = {};        // Cache for categorization status
    window.catStatusXHR = null;        // Categorization status request object

    // In gantt-core.js
    function initialize() {
        if (state.initialized) return;

        // Set up UI event handlers
        setupEventHandlers();

        // Set up window resize handler
        $(window).on('resize', function() {
            adjustGanttWidth();
        });

        // Initial width adjustment
        setTimeout(adjustGanttWidth, 500);

        state.initialized = true;

        // Trigger an event that initialization is complete
        $(document).trigger('gantt:initialized');
    }

    function setupEventHandlers() {
        // Set up refresh button
        $(config.refreshButton).on('click', function() {
            GanttChart.Ajax.loadData(config.currentFilter);
        });

        // Set up filter dropdown (consider moving this to Interactions module)
        $(config.filterDropdown).on('click', function(e) {
            e.preventDefault();
            const filter = $(this).data('filter');
            config.currentFilter = filter;

            // Update button text
            $(config.filterButton).text($(this).text());

            // Load data with the selected filter
            GanttChart.Ajax.loadData(filter);
        });
    }

    // Then in index.php
    $(document).ready(function() {
        console.log('Document ready');

        // First, set the configuration
        GanttChart.Core.setConfig({
            dataEndpoint: 'ajax/get_timeline_data.php',
            workpackagesEndpoint: 'ajax/get_workpackages.php',
            catstatusEndpoint: 'ajax/get_catstatus.php',
            helpDocPath: 'docs/gantt-help.md',
            // Add a retry option for data loading
            retryOnFailure: false,
            maxRetries: 3
        });
        console.log('Config set');

        // Initialize theme module
        GanttChart.Theme.init();
        console.log('Theme initialized');

        // Initialize the core functionality
        // Core.init() no longer automatically loads data
        GanttChart.Core.init();
        console.log('Core initialized');

        // Then, explicitly load the data after all modules are ready
        console.log('Explicitly loading data...');
        setTimeout(function() {
            // This will use the Custom module's loadProjectData if available
            GanttChart.Ajax.loadData('all');
        }, 100);
    });
</script>
</body>
</html>