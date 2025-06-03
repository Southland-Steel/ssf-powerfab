<?php
/**
 * Machine Production Details
 * Shows detailed production metrics and analysis for a specific machine
 */

// Page configuration
$pageTitle = 'Machine Production Details';
$pageStyles = ['dashboard.css', 'machine-details.css'];
$pageScripts = ['machine-details.js'];

// Include database configuration
require_once 'includes/db_config.php';

// Get machine parameter
$machine = $_GET['machine'] ?? '';
$period = $_GET['period'] ?? 'today';

// Validate machine parameter
if (empty($machine)) {
    header('Location: index.php');
    exit;
}

// Escape for display
$machineDisplay = htmlspecialchars($machine);

// Include header
require_once 'includes/header.php';
?>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="mb-0">
                        <i class="fas fa-chart-line me-2"></i>
                        Production Details: <?php echo $machineDisplay; ?>
                    </h1>
                    <p class="mb-0 mt-1 opacity-75">Detailed production analysis and metrics</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="index.php" class="btn btn-outline-light">
                        <i class="fas fa-arrow-left me-2"></i>
                        Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mt-4">
        <!-- Period Selector -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="btn-group period-selector" role="group">
                                    <button type="button" class="btn btn-outline-primary" data-period="today">Today</button>
                                    <button type="button" class="btn btn-outline-primary" data-period="yesterday">Yesterday</button>
                                    <button type="button" class="btn btn-outline-primary" data-period="7days">Last 7 Days</button>
                                    <button type="button" class="btn btn-outline-primary" data-period="30days">Last 30 Days</button>
                                    <button type="button" class="btn btn-outline-primary" data-period="custom">Custom Range</button>
                                </div>
                            </div>
                            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                <button class="btn btn-primary" onclick="refreshProductionData()">
                                    <i class="fas fa-sync-alt me-2"></i>
                                    Refresh
                                </button>
                                <button class="btn btn-secondary" onclick="exportData()">
                                    <i class="fas fa-download me-2"></i>
                                    Export
                                </button>
                            </div>
                        </div>

                        <!-- Custom Date Range (hidden by default) -->
                        <div id="customDateRange" class="row mt-3" style="display: none;">
                            <div class="col-md-4">
                                <label for="startDate" class="form-label">Start Date</label>
                                <input type="datetime-local" class="form-control" id="startDate">
                            </div>
                            <div class="col-md-4">
                                <label for="endDate" class="form-label">End Date</label>
                                <input type="datetime-local" class="form-control" id="endDate">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">&nbsp;</label>
                                <button class="btn btn-primary w-100" onclick="applyCustomRange()">Apply Range</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div id="loadingState" class="text-center py-5" style="display: none;">
            <div class="loading-spinner" style="width: 3rem; height: 3rem; margin: 0 auto;"></div>
            <p class="mt-3 text-muted">Loading production data...</p>
        </div>

        <!-- Error State -->
        <div id="errorState" class="alert alert-danger" style="display: none;">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <span id="errorMessage">Error loading data</span>
        </div>

        <!-- Production Overview -->
        <div id="productionContent" style="display: none;">
            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-label">Total Parts Cut</div>
                        <div class="stat-number text-primary" id="totalParts">0</div>
                        <div class="stat-trend" id="partsTrend"></div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-label">Nests Completed</div>
                        <div class="stat-number text-info" id="nestsCompleted">0</div>
                        <div class="stat-trend" id="nestsTrend"></div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-label">Production Time</div>
                        <div class="stat-number text-success" id="productionTime">0h</div>
                        <div class="stat-trend" id="timeTrend"></div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-label">Efficiency</div>
                        <div class="stat-number text-warning" id="efficiency">0%</div>
                        <div class="stat-trend" id="efficiencyTrend"></div>
                    </div>
                </div>
            </div>

            <!-- Activity DataTable -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-th-list me-2"></i>
                                Production Activity Log
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover" id="activityTable">
                                    <thead>
                                    <tr>
                                        <th>Date/Time</th>
                                        <th>Type</th>
                                        <th>Piece Mark</th>
                                        <th>Size</th>
                                        <th>Grade</th>
                                        <th>Job</th>
                                        <th>Sequence</th>
                                        <th>Nest</th>
                                        <th>Weight (lbs)</th>
                                        <th>Action</th>
                                    </tr>
                                    </thead>
                                    <tbody id="activityTableBody">
                                    <!-- Table rows will be populated by JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Nests and Jobs Table -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <h5 class="mb-0">
                                        <i class="fas fa-layer-group me-2"></i>
                                        Nests Summary
                                    </h5>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <input type="text" class="form-control form-control-sm w-auto d-inline-block"
                                           placeholder="Search..." id="searchNests" style="width: 200px;">
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0" id="nestsTable">
                                    <thead>
                                    <tr>
                                        <th>Time Range</th>
                                        <th>Nest</th>
                                        <th>Job</th>
                                        <th>Drawing</th>
                                        <th>Parts Cut</th>
                                        <th>Duration</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                    </thead>
                                    <tbody id="nestsTableBody">
                                    <!-- Table rows will be populated by JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Time Analysis -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-clock me-2"></i>
                                Time Breakdown
                            </h5>
                        </div>
                        <div class="card-body">
                            <canvas id="timeBreakdownChart"></canvas>
                            <div id="timeBreakdownLegend" class="mt-3"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-chart-area me-2"></i>
                                Production Trend
                            </h5>
                        </div>
                        <div class="card-body">
                            <canvas id="productionTrendChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stop Analysis -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-pause-circle me-2"></i>
                                Stop Analysis
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm" id="stopsTable">
                                    <thead>
                                    <tr>
                                        <th>Stop Time</th>
                                        <th>Duration</th>
                                        <th>Previous Activity</th>
                                        <th>Next Activity</th>
                                    </tr>
                                    </thead>
                                    <tbody id="stopsTableBody">
                                    <!-- Table rows will be populated by JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bar Details Modal -->
    <div class="modal fade" id="barDetailsModal" tabindex="-1" aria-labelledby="barDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="barDetailsModalLabel">
                        <i class="fas fa-bars me-2"></i>
                        Bar Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="barDetailsLoading" class="text-center py-4">
                        <div class="loading-spinner" style="width: 2rem; height: 2rem; margin: 0 auto;"></div>
                        <p class="mt-2 text-muted">Loading bar details...</p>
                    </div>
                    <div id="barDetailsContent" style="display: none;">
                        <!-- Bar details will be populated here -->
                    </div>
                    <div id="barDetailsError" class="alert alert-danger" style="display: none;">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <span id="barErrorMessage">Error loading bar details</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden input to store machine name for JavaScript -->
    <input type="hidden" id="machineName" value="<?php echo $machineDisplay; ?>">
    <input type="hidden" id="currentPeriod" value="<?php echo htmlspecialchars($period); ?>">

    <!-- Include DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">

    <!-- Include DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<?php
// Include footer
require_once 'includes/footer.php';
?>