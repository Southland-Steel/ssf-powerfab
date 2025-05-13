<?php
// checkups/cutlist_invalidations.php

// Include utility functions first to ensure getAssetUrl is available
require_once '../includes/functions/utility_functions.php';

// Set page-specific variables
$page_title = 'Cutlist Invalidations';
$show_workweeks = false;

// Additional CSS for this page
$extra_css = '
<link rel="stylesheet" href="' . getUrl('checkups/css/checkups.css') . '">
';

// Include header
include_once '../includes/header.php';
?>

    <div class="row mb-3">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo getUrl('checkups/index.php'); ?>">Checkups</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Cutlist Invalidations</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-12">
            <div class="card shadow">
                <div class="card-header bg-ssf-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Invalidated Cutlist Items</h5>
                    <div>
                        <button class="btn btn-sm btn-outline-light" id="exportBtn">
                            <i class="bi bi-file-earmark-excel"></i> Export to CSV
                        </button>
                        <button class="btn btn-sm btn-outline-light ms-2" id="refreshBtn">
                            <i class="bi bi-arrow-clockwise"></i> Refresh
                        </button>
                        <a href="<?php echo getUrl('checkups/view_documentation.php?doc=cutlist_invalidations'); ?>" class="btn btn-sm btn-outline-light ms-2">
                            <i class="bi bi-question-circle"></i> Help
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="invalidationsTable" class="table table-striped table-bordered table-hover mb-0 sticky-header">
                            <thead>
                            <tr>
                                <th>Cutlist ID</th>
                                <th>Item ID</th>
                                <th>Cutlist Description</th>
                                <th>Created Date</th>
                                <th>Invalidated Date</th>
                                <th>Machine</th>
                                <th>Workshop</th>
                                <th>Nest #1</th>
                                <th>Nest #2</th>
                                <th>Shape</th>
                                <th>Grade</th>
                                <th>Dimension</th>
                                <th>Length</th>
                                <th>Status</th>
                            </tr>
                            </thead>
                            <tbody id="invalidationsTableBody">
                            <tr>
                                <td colspan="14" class="text-center">
                                    <div class="d-flex justify-content-center align-items-center py-4">
                                        <div class="spinner-border text-ssf-primary me-2" role="status"></div>
                                        <span>Loading invalidation data...</span>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span id="recordCount">0</span> invalidated cutlist items found
                        </div>
                        <div id="lastUpdated" class="text-muted">
                            Last updated: Never
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Details Modal -->
    <div class="modal fade" id="itemDetailsModal" tabindex="-1" aria-labelledby="itemDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-ssf-primary text-white">
                    <h5 class="modal-title" id="itemDetailsModalLabel">Cutlist Item Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="itemDetailsContent">
                    <div class="d-flex justify-content-center">
                        <div class="spinner-border text-ssf-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

<?php
// Additional JavaScript for this page
$extra_js = '
<!-- Ensure common.js is loaded first -->
<script src="' . getAssetUrl('js/common.js') . '"></script>
<script src="' . getUrl('checkups/js/invalidations-core.js') . '"></script>
<script src="' . getUrl('checkups/js/invalidations-ui.js') . '"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Initialize the invalidations modules
        InvalidationsCore.init({
            apiUrl: "' . getUrl('checkups/ajax/get_invalidations.php') . '"
        });
        
        InvalidationsUI.init({
            tableId: "invalidationsTable",
            modalId: "itemDetailsModal",
            patternApiUrl: "' . getUrl('checkups/ajax/get_pattern_info.php') . '"
        });
        
        // Load initial data
        InvalidationsCore.loadInvalidations();
        
        // Set up refresh button
        document.getElementById("refreshBtn").addEventListener("click", function() {
            InvalidationsCore.loadInvalidations();
        });
        
        // Set up export button
        document.getElementById("exportBtn").addEventListener("click", function() {
            exportTableToCSV("invalidationsTable", "cutlist_invalidations_" + new Date().toISOString().slice(0,10) + ".csv");
        });
    });
</script>
';

// Include footer
include_once '../includes/footer.php';
?>