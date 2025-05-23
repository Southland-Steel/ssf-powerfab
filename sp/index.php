<?php
/**
 * Machine Activity Dashboard
 * Main dashboard showing all machines and their current status
 */

// Page configuration
$pageTitle = 'Machine Activity Dashboard';
$pageStyles = ['dashboard.css', 'machine-modal.css'];
$pageScripts = ['dashboard.js', 'machine-modal.js'];
$hideNavigation = true; // We'll use a custom header for the dashboard

// Include database configuration
require_once 'includes/db_config.php';

// Initialize variables
$machines = array();
$error = '';
$lastUpdated = date('Y-m-d H:i:s');

// Fetch machine activity data
$sql = "SELECT
    FFR_CNC AS Machine,
    MAX(DATM) AS Last_Activity,
    DATEDIFF(day, MAX(DATM), GETDATE()) AS Days_Since_Activity,
    CASE
        WHEN DATEDIFF(hour, MAX(DATM), GETDATE()) < 1 THEN 'Active Now'
        WHEN DATEDIFF(hour, MAX(DATM), GETDATE()) < 24 THEN 'Active Today'
        WHEN DATEDIFF(day, MAX(DATM), GETDATE()) < 7 THEN 'Active This Week'
        ELSE 'Inactive'
    END AS Activity_Status
FROM FEEDBACK_FBK_RAW
WHERE FFR_CNC IS NOT NULL
GROUP BY FFR_CNC
ORDER BY MAX(DATM) DESC, FFR_CNC";

$machines = $db->query($sql);

if ($machines === false) {
    $error = $db->getError();
}

// Calculate statistics
$totalMachines = count($machines);
$activeMachines = 0;
$inactiveMachines = 0;

foreach ($machines as $machine) {
    if (in_array($machine['Activity_Status'], ['Active Now', 'Active Today'])) {
        $activeMachines++;
    } else {
        $inactiveMachines++;
    }
}

// Include header
require_once 'includes/header.php';
?>

    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="mb-0">
                        <i class="fas fa-industry me-2"></i>
                        Machine Activity Status
                    </h1>
                    <p class="mb-0 mt-1 opacity-75">Real-time monitoring of production machines</p>
                </div>
                <div class="col-md-6 text-md-end mt-3 mt-md-0">
                    <button class="btn btn-refresh" onclick="refreshData()">
                        <i class="fas fa-sync-alt me-2" id="loadingSpinner"></i>
                        Refresh Data
                    </button>
                    <div class="mt-2 small opacity-75">
                        Last updated: <span id="lastUpdated"><?php echo $lastUpdated; ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row dashboard-stats">
            <div class="col-md-4 mb-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="stat-number text-primary"><?php echo $totalMachines; ?></p>
                            <p class="stat-label">Total Machines</p>
                        </div>
                        <div class="ms-3">
                            <i class="fas fa-cogs fa-3x text-primary stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="stat-number text-success"><?php echo $activeMachines; ?></p>
                            <p class="stat-label">Active Machines</p>
                        </div>
                        <div class="ms-3">
                            <i class="fas fa-check-circle fa-3x text-success stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="stat-number text-danger"><?php echo $inactiveMachines; ?></p>
                            <p class="stat-label">Inactive Machines</p>
                        </div>
                        <div class="ms-3">
                            <i class="fas fa-times-circle fa-3x text-danger stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Machine Activity Table -->
        <div class="activity-table">
            <div class="table-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>
                            Machine Activity Details
                        </h5>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-md-end filter-buttons mt-3 mt-md-0">
                            <button class="filter-btn active" onclick="filterMachines('all')">All</button>
                            <button class="filter-btn" onclick="filterMachines('active')">Active</button>
                            <button class="filter-btn" onclick="filterMachines('inactive')">Inactive</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover mb-0" id="machineTable">
                    <thead>
                    <tr>
                        <th class="border-0 ps-4">Machine</th>
                        <th class="border-0">Last Activity</th>
                        <th class="border-0">Days Since Activity</th>
                        <th class="border-0">Status</th>
                        <th class="border-0">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($machines as $machine): ?>
                        <tr data-status="<?php echo strtolower(str_replace(' ', '-', $machine['Activity_Status'])); ?>">
                            <td class="ps-4">
                                <span class="machine-name">
                                    <i class="fas fa-server me-2 text-muted"></i>
                                    <?php echo htmlspecialchars($machine['Machine']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="last-activity">
                                    <?php
                                    $lastActivity = $machine['Last_Activity'];
                                    if ($lastActivity instanceof DateTime) {
                                        echo $lastActivity->format('Y-m-d H:i:s');
                                    } else {
                                        echo htmlspecialchars($lastActivity);
                                    }
                                    ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $days = $machine['Days_Since_Activity'];
                                if ($days == 0) {
                                    echo '<span class="days-today">Today</span>';
                                } elseif ($days == 1) {
                                    echo '<span class="days-recent">1 day</span>';
                                } else {
                                    echo '<span class="days-old">' . $days . ' days</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php
                                $statusClass = '';
                                switch($machine['Activity_Status']) {
                                    case 'Active Now':
                                        $statusClass = 'status-active-now';
                                        break;
                                    case 'Active Today':
                                        $statusClass = 'status-active-today';
                                        break;
                                    case 'Active This Week':
                                        $statusClass = 'status-active-week';
                                        break;
                                    default:
                                        $statusClass = 'status-inactive';
                                }
                                ?>
                                <span class="status-badge <?php echo $statusClass; ?>">
                                    <?php echo htmlspecialchars($machine['Activity_Status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="rawdata.php?machine=<?php echo urlencode($machine['Machine']); ?>"
                                   class="btn btn-sm btn-outline-primary"
                                   title="View raw feedback data">
                                    RAW
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if (empty($machines)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No machine data available</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Machine Status Modal -->
    <div class="modal fade machine-modal" id="machineStatusModal" tabindex="-1" aria-labelledby="machineStatusModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="machineStatusModalLabel">
                        <i class="fas fa-server me-2"></i>
                        Machine Status
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Content will be loaded dynamically -->
                    <div class="modal-loading">
                        <div class="loading-spinner"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="retryLoadMachineStatus()">
                        <i class="fas fa-sync-alt me-1"></i>
                        Refresh
                    </button>
                    <button type="button" class="btn btn-primary" onclick="viewMachineDetails()">
                        <i class="fas fa-chart-line me-2"></i>
                        View Production Details
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

<?php
// Include footer
require_once 'includes/footer.php';
?>