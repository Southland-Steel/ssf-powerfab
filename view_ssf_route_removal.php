<?php
require_once 'config_ssf_db.php';

// Get selected job number from URL parameter
$selectedJob = isset($_GET['job']) ? $_GET['job'] : null;

// Query to get all unique job numbers
$jobQuery = "SELECT DISTINCT pcj.JobNumber
             FROM productioncontroljobs as pcj
             JOIN productioncontrolitems as pci 
                ON pcj.ProductionControlID = pci.ProductionControlID
             JOIN routes as r on pci.RouteID = r.RouteID
             WHERE pci.RouteID is not null AND r.Route NOT LIKE '!%'
             ORDER BY pcj.JobNumber";

try {
    $jobStmt = $db->query($jobQuery);
    $jobs = $jobStmt->fetchAll(PDO::FETCH_COLUMN);
} catch(PDOException $e) {
    die("Job query failed: " . $e->getMessage());
}

// Query to get route data, modified to include job filter
$sql = "SELECT 
            DISTINCT pci.RouteID,
            routes.Route, 
            pcj.JobNumber,
            COUNT(ProductionControlItemID) as ItemCount
        FROM fabrication.productioncontrolitems as pci
        LEFT JOIN routes on routes.RouteID = pci.RouteID
        LEFT JOIN productioncontroljobs as pcj 
            ON pcj.ProductionControlID = pci.ProductionControlID
        WHERE pci.RouteID is not null AND routes.Route NOT LIKE '!%' ";

if ($selectedJob) {
    $sql .= "AND pcj.JobNumber = :jobNumber ";
}

$sql .= "GROUP BY pci.ProductionControlID, pci.RouteID, pci.ProductionControlID";

try {
    $stmt = $db->prepare($sql);
    if ($selectedJob) {
        $stmt->bindParam(':jobNumber', $selectedJob);
    }
    $stmt->execute();
    $routes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Query failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Route Removal Tracking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
          rel="stylesheet">
    <style>
        .sidebar {
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding: 20px;
            background-color: #f8f9fa;
            overflow-y: auto;
            border-right: 1px solid #dee2e6;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
        }

        .job-list {
            list-style: none;
            padding: 0;
        }

        .job-list li {
            margin: 5px 0;
        }

        .job-list a {
            text-decoration: none;
            padding: 5px 10px;
            display: block;
            border-radius: 5px;
        }

        .job-list a.active {
            background-color: #0d6efd;
            color: white;
        }

        .job-list a:hover:not(.active) {
            background-color: #e9ecef;
        }

        .stats-box {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }

        .table-container {
            margin-top: 2rem;
            margin-bottom: 2rem;
        }

        .refresh-button {
            margin: 1rem 0;
        }
    </style>
</head>
<body>
<!-- Sidebar Navigation -->
<div class="sidebar" style="width: 250px;">
    <h4 class="mb-4">Job Numbers</h4>
    <ul class="job-list">
        <li>
            <a href="view_ssf_route_removal.php"
               class="<?php echo !$selectedJob ? 'active' : ''; ?>">
                Show All Jobs
            </a>
        </li>
        <?php foreach($jobs as $job): ?>
            <li>
                <a href="?job=<?php echo urlencode($job); ?>"
                   class="<?php echo $selectedJob === $job ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($job); ?>
                    <?php
                    // Count items for this job
                    $jobCount = array_reduce($routes, function($carry, $route) use ($job) {
                        return $carry + ($route['JobNumber'] === $job ? $route['ItemCount'] : 0);
                    }, 0);
                    if ($jobCount > 0) {
                        echo "<span class='badge bg-secondary float-end'>$jobCount</span>";
                    }
                    ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</div>

<!-- Main Content -->
<div class="main-content">
    <h1 class="mt-4">
        Route Removal Tracking
        <?php if ($selectedJob): ?>
            <small class="text-muted">- Job <?php echo htmlspecialchars($selectedJob); ?></small>
        <?php endif; ?>
    </h1>

    <!-- Stats Summary -->
    <div class="row stats-box">
        <div class="col-md-4">
            <h5>Total Routes to Process:</h5>
            <p class="h3"><?php echo count($routes); ?></p>
        </div>
        <div class="col-md-4">
            <h5>Total Items:</h5>
            <p class="h3"><?php
                echo array_sum(array_column($routes, 'ItemCount'));
                ?></p>
        </div>
    </div>

    <!-- Refresh Button -->
    <button class="btn btn-primary refresh-button"
            onclick="window.location.reload();">
        Refresh Data
    </button>

    <!-- Data Table -->
    <div class="table-container">
        <?php if (count($routes) > 0): ?>
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                <tr>
                    <th>Route ID</th>
                    <th>Route Name</th>
                    <th>Job Number</th>
                    <th>Item Count</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach($routes as $route): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($route['RouteID']); ?></td>
                        <td><?php echo htmlspecialchars($route['Route']); ?></td>
                        <td><?php echo htmlspecialchars($route['JobNumber']); ?></td>
                        <td><?php echo htmlspecialchars($route['ItemCount']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-success">
                <?php if ($selectedJob): ?>
                    No routes to process for Job <?php echo htmlspecialchars($selectedJob); ?>.
                    This job is complete!
                <?php else: ?>
                    No routes to process. All jobs are complete!
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js">
</script>
</body>
</html>