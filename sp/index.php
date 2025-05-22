<?php
// Include the database class (adjust path as needed)
require_once 'Database.php';

// Initialize database connection
$db = new Database();

// Initialize variables
$machines = array();
$error = '';
$lastUpdated = date('Y-m-d H:i:s');

// Fetch machine activity data
if ($db->isConnected()) {
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
} else {
    $error = "Database connection failed: " . $db->getError();
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Machine Activity Status Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .dashboard-header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            transition: transform 0.2s;
            height: 100%;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 600;
            margin: 0;
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .activity-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }

        .table-header {
            background: #f8f9fa;
            padding: 1rem 1.5rem;
            border-bottom: 2px solid #dee2e6;
        }

        .status-badge {
            padding: 0.375rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-active-now {
            background-color: #d4edda;
            color: #155724;
        }

        .status-active-today {
            background-color: #cce5ff;
            color: #004085;
        }

        .status-active-week {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }

        .machine-name {
            font-weight: 600;
            color: #2c3e50;
        }

        .last-activity {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .refresh-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 5px;
            transition: background 0.2s;
        }

        .refresh-btn:hover {
            background: #2980b9;
            color: white;
        }

        .loading-spinner {
            display: none;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .filter-buttons {
            gap: 0.5rem;
        }

        .filter-btn {
            padding: 0.375rem 1rem;
            border-radius: 20px;
            border: 1px solid #dee2e6;
            background: white;
            color: #6c757d;
            transition: all 0.2s;
        }

        .filter-btn:hover {
            background: #f8f9fa;
        }

        .filter-btn.active {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }

        @media (max-width: 768px) {
            .stat-number {
                font-size: 2rem;
            }

            .table-responsive {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
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
                <button class="btn refresh-btn" onclick="refreshData()">
                    <i class="fas fa-sync-alt me-2 loading-spinner" id="loadingSpinner"></i>
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
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="stat-number text-primary"><?php echo $totalMachines; ?></p>
                        <p class="stat-label mb-0">Total Machines</p>
                    </div>
                    <div class="ms-3">
                        <i class="fas fa-cogs fa-3x text-primary opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="stat-number text-success"><?php echo $activeMachines; ?></p>
                        <p class="stat-label mb-0">Active Machines</p>
                    </div>
                    <div class="ms-3">
                        <i class="fas fa-check-circle fa-3x text-success opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="stat-number text-danger"><?php echo $inactiveMachines; ?></p>
                        <p class="stat-label mb-0">Inactive Machines</p>
                    </div>
                    <div class="ms-3">
                        <i class="fas fa-times-circle fa-3x text-danger opacity-25"></i>
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
                                echo '<span class="text-success fw-bold">Today</span>';
                            } elseif ($days == 1) {
                                echo '<span class="text-info">1 day</span>';
                            } else {
                                echo '<span class="text-muted">' . $days . ' days</span>';
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
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (empty($machines)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-inbox fa-3x mb-3"></i>
                    <p>No machine data available</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JavaScript -->
<script>
    // Auto-refresh every 30 seconds
    //let refreshInterval = setInterval(refreshData, 30000);

    // Refresh data function
    function refreshData() {
        const spinner = document.getElementById('loadingSpinner');
        spinner.style.display = 'inline-block';

        // Reload the page to fetch fresh data
        setTimeout(() => {
            window.location.reload();
        }, 500);
    }

    // Filter machines function
    function filterMachines(filter) {
        const rows = document.querySelectorAll('#machineTable tbody tr');
        const buttons = document.querySelectorAll('.filter-btn');

        // Update active button
        buttons.forEach(btn => {
            btn.classList.remove('active');
            if (btn.textContent.toLowerCase() === filter) {
                btn.classList.add('active');
            }
        });

        // Filter rows
        rows.forEach(row => {
            const status = row.getAttribute('data-status');

            if (filter === 'all') {
                row.style.display = '';
            } else if (filter === 'active') {
                if (status === 'active-now' || status === 'active-today') {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            } else if (filter === 'inactive') {
                if (status === 'inactive' || status === 'active-this-week') {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        });

        // Update statistics
        updateStatistics();
    }

    // Update statistics based on visible rows
    function updateStatistics() {
        const visibleRows = document.querySelectorAll('#machineTable tbody tr:not([style*="display: none"])');
        let activeCount = 0;
        let inactiveCount = 0;

        visibleRows.forEach(row => {
            const status = row.getAttribute('data-status');
            if (status === 'active-now' || status === 'active-today') {
                activeCount++;
            } else {
                inactiveCount++;
            }
        });

        // Update the numbers (optional - you can implement this if needed)
    }

    // Sort table by clicking headers
    document.addEventListener('DOMContentLoaded', function() {
        const table = document.getElementById('machineTable');
        const headers = table.querySelectorAll('th');

        headers.forEach((header, index) => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', () => {
                sortTable(index);
            });
        });
    });

    // Simple table sorting function
    function sortTable(columnIndex) {
        const table = document.getElementById('machineTable');
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));

        // Toggle sort direction
        const isAscending = table.getAttribute('data-sort-dir') !== 'asc';
        table.setAttribute('data-sort-dir', isAscending ? 'asc' : 'desc');

        // Sort rows
        rows.sort((a, b) => {
            const aValue = a.cells[columnIndex].textContent.trim();
            const bValue = b.cells[columnIndex].textContent.trim();

            // Handle numeric columns
            if (columnIndex === 2) { // Days column
                const aNum = parseInt(aValue) || 0;
                const bNum = parseInt(bValue) || 0;
                return isAscending ? aNum - bNum : bNum - aNum;
            }

            // Text comparison
            return isAscending ?
                aValue.localeCompare(bValue) :
                bValue.localeCompare(aValue);
        });

        // Re-append sorted rows
        rows.forEach(row => tbody.appendChild(row));
    }

    // Add visual feedback for sortable columns
    const style = document.createElement('style');
    style.textContent = `
            #machineTable th:hover {
                background-color: #f0f0f0;
            }
            #machineTable th::after {
                content: ' ↕';
                opacity: 0.3;
                font-size: 0.8em;
            }
        `;
    document.head.appendChild(style);
</script>
</body>
</html>