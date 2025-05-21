<?php
/**
 * File: index.php
 * Gantt chart for project schedule visualization
 */
$pageTitle = 'Project Schedule Gantt Chart';

// Add custom CSS
$additionalCss = '
<link rel="stylesheet" href="css/gantt.css">
<link rel="stylesheet" href="css/gantt-responsive.css">
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
                        <?php if(false): ?>
                        <button id="ganttZoomOut" class="btn btn-sm btn-outline-secondary" title="Zoom Out">
                            <i class="bi bi-zoom-out"></i>
                        </button>
                        <button id="ganttZoomReset" class="btn btn-sm btn-outline-secondary" title="Reset Zoom">
                            <i class="bi bi-arrows-fullscreen"></i>
                        </button>
                        <button id="ganttZoomIn" class="btn btn-sm btn-outline-secondary" title="Zoom In">
                            <i class="bi bi-zoom-in"></i>
                        </button>
                        <?php endif; ?>

                        <button id="refreshGantt" class="btn btn-sm btn-outline-primary ms-2" title="Refresh Data">
                            <i class="bi bi-arrow-clockwise"></i> Refresh
                        </button>

                        <div class="btn-group ms-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" id="filterDropdownBtn">
                                <i class="bi bi-filter"></i> All Projects
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" id="projectFilterDropdown">
                                <li><a class="dropdown-item" href="#" data-filter="all">All Projects</a></li>
                                <!-- Project filters will be added dynamically -->
                            </ul>
                        </div>
                        <?php if(false): ?>
                        <button id="exportGantt" class="btn btn-sm btn-outline-secondary ms-2" title="Export to CSV">
                            <i class="bi bi-download"></i> Export
                        </button>
                        <?php endif; ?>
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
                        <small class="d-inline-block me-3"><span style="display:inline-block;width:2px;height:12px;background-color:var(--gantt-today-color);margin-right:5px;"></span> Today</small>
                    </div>

                    <!-- Filter buttons - client-side filtering -->
                    <div class="gantt-filter-container p-2 border-bottom">
                        <div class="d-flex justify-content-between w-100 align-items-center">
                            <div>
                                <button class="gantt-filter-btn active" data-filter="all">All Tasks</button>
                                <button class="gantt-filter-btn" data-filter="in-progress">In Progress</button>
                                <button class="gantt-filter-btn" data-filter="not-started">Not Started</button>
                                <button class="gantt-filter-btn" data-filter="late">Late</button>
                                <button class="gantt-filter-btn" data-filter="client-approval-complete">Client Approved</button>
                            </div>
                            <div class="sort-indicator">
                                <span class="badge bg-secondary">
                                    <i class="bi bi-arrow-down-up me-1"></i> Sorted chronologically
                                </span>
                            </div>
                        </div>
                    </div>

                    <div id="ganttLoadingIndicator" class="gantt-loading">
                        <div class="gantt-loading-spinner"></div>
                        <p>Loading Gantt chart data...</p>
                    </div>

                    <div id="ganttContainer" class="gantt-container">
                        <div id="ganttTimelineHeader" class="gantt-timeline-header">
                            <!-- Timeline header will be generated here -->
                        </div>

                        <div id="ganttBody" class="gantt-body">
                            <div id="ganttItemRows" class="gantt-item-rows">
                                <!-- Item rows will be generated here -->
                            </div>
                        </div>
                    </div>

                    <div id="noItemsMessage" class="alert alert-info text-center my-4" style="display: none;">
                        No tasks found matching the current filter criteria.
                    </div>
                </div>

                <div class="card-footer d-flex justify-content-between">
                    <small class="text-muted">Click on any task bar to view more details</small>
                    <small class="text-muted">Hover over bars for more information</small>
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
                <div id="ganttHelpContent">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading help content...</span>
                        </div>
                        <p class="mt-2">Loading help documentation...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript files -->
<script src="js/gantt-core.js"></script>
<script src="js/gantt-timeutils.js"></script>
<script src="js/gantt-timeline.js"></script>
<script src="js/gantt-bars.js"></script>
<script src="js/gantt-interactions.js"></script>
<script src="js/gantt-ajax.js"></script>
<script src="js/gantt-theme.js"></script>
<script src="js/gantt-help.js"></script>

<script>
    $(document).ready(function() {
        // Set configuration
        GanttChart.Core.setConfig({
            dataEndpoint: 'ajax/get_timeline_data.php',
            taskDetailsEndpoint: 'ajax/get_task_details.php',
            container: '#ganttContainer',
            timelineHeader: '#ganttTimelineHeader',
            itemRows: '#ganttItemRows',
            loadingIndicator: '#ganttLoadingIndicator',
            noItemsMessage: '#noItemsMessage',
            refreshButton: '#refreshGantt',
            exportButton: '#exportGantt',
            helpButton: '#ganttHelpBtn',
            filterDropdown: '#projectFilterDropdown',
            filterButton: '#filterDropdownBtn',
            currentFilter: 'all',
            todayIndicator: true
        });

        // Initialize modules
        GanttChart.Theme.init();
        GanttChart.Core.init();
        GanttChart.Help.init();

        // Load data
        GanttChart.Ajax.loadData('all');

        // Ensure the item count is updated once everything is loaded
        $(document).ajaxComplete(function(event, xhr, settings) {
            if (settings.url.includes('get_timeline_data.php')) {
                setTimeout(function() {
                    GanttChart.Interactions.updateItemCount();
                }, 100);
            }
        });
    });
</script>
</body>
</html>