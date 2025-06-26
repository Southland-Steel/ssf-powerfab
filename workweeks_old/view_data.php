<?php
// workweeks/view_data.php
// A simple data viewer for workweek data

// Set page title and other variables for header
$page_title = "Workweek Data Viewer";
$show_workweeks = false;
$extra_css = '<link rel="stylesheet" href="css/workweeks.css">
<link rel="stylesheet" href="css/workweeks-style-fixes.css">';

// Calculate current workweek
require_once __DIR__ . '/../includes/functions/utility_functions.php';
$currentWorkweek = getCurrentWorkWeek();
$workweek = $_GET['workweek'] ?? $currentWorkweek;

// Include the header
include_once __DIR__ . '/../includes/header.php';

// Get the view type from query parameter (optional)
$viewType = $_GET['view'] ?? 'basic';

// Get the database connection
// (already included via header.php which includes db_connection.php)

// Initialize filters
$filters = [];
$filterSql = '';

// Add any additional filters from query parameters
foreach ($_GET as $key => $value) {
    if (in_array($key, ['jobnumber', 'mainmark', 'piecemark', 'workpackage', 'bay', 'route', 'category']) && !empty($value)) {
        $filters[$key] = $value;
    }
}

// Build filter SQL
if (!empty($filters)) {
    $filterSql .= ' AND (';
    $filterParts = [];

    foreach ($filters as $key => $value) {
        switch ($key) {
            case 'jobnumber':
                $filterParts[] = "pcj.JobNumber = :jobnumber";
                break;
            case 'mainmark':
                $filterParts[] = "pci.MainMark LIKE :mainmark";
                break;
            case 'piecemark':
                $filterParts[] = "pci.PieceMark LIKE :piecemark";
                break;
            case 'workpackage':
                $filterParts[] = "wp.WorkPackageNumber = :workpackage";
                break;
            case 'bay':
                $filterParts[] = "wp.Group1 = :bay";
                break;
            case 'route':
                $filterParts[] = "rt.Route LIKE :route";
                break;
            case 'category':
                $filterParts[] = "pccat.Description = :category";
                break;
        }
    }

    $filterSql .= implode(' AND ', $filterParts) . ')';
}

// Prepare SQL query based on view type
$sql = "SELECT ";

if ($viewType === 'detailed') {
    $sql .= "
        pcj.JobNumber,
        pcj.JobDescription,
        pcj.ProductionControlID,
        REPLACE(pcseq.Description, CHAR(1), '') as SequenceDescription,
        REPLACE(pcseq.LotNumber, CHAR(1), '') as LotNumber,
        pcseq.SequenceID,
        pcseq.AssemblyQuantity,
        wp.WorkPackageNumber,
        wp.Group2 as WorkWeek,
        wp.Group1 as Bay,
        wp.WorkshopID,
        wp.ReleasedToFab,
        wp.OnHold,
        wp.Completed,
        ws.Description as WorkshopDescription,
        REPLACE(pci.MainMark,CHAR(1),'') AS MainMark,
        REPLACE(pci.PieceMark,CHAR(1),'') AS PieceMark,
        pci.ProductionControlItemID,
        pci.MainPiece,
        pci.ShapeID,
        shapes.Shape,
        pci.DimensionString,
        ROUND(pci.Length / 25.4,3) as LengthInches,
        pciseq.ProductionControlItemSequenceID,
        pciseq.ProductionControlAssemblyID,
        pciseq.Quantity as SequenceMainMarkQuantity,
        rt.Route as RouteName,
        pccat.Description as Category,
        ROUND(pca.AssemblyWeightEach*2.20462,3) as NetAssemblyWeightEach,
        ROUND(pca.AssemblyManHoursEach,3) as AssemblyManHoursEach,
        stations.Description as StationDescription,
        stations.StationID,
        pciss.QuantityCompleted as StationQuantityCompleted,
        pciss.LastDateCompleted,
        pciss.TotalQuantity as StationTotalQuantity,
        pciss.ProductionControlItemStationSummaryID
    ";
} else {
    // Basic view with fewer columns
    $sql .= "
        pcj.JobNumber,
        REPLACE(pcseq.Description, CHAR(1), '') as SequenceDescription,
        REPLACE(pcseq.LotNumber, CHAR(1), '') as LotNumber,
        wp.WorkPackageNumber,
        wp.Group2 as WorkWeek,
        wp.Group1 as Bay,
        REPLACE(pci.MainMark,CHAR(1),'') AS MainMark,
        REPLACE(pci.PieceMark,CHAR(1),'') AS PieceMark,
        shapes.Shape,
        rt.Route as RouteName,
        pccat.Description as Category,
        ROUND(pca.AssemblyManHoursEach,3) as AssemblyManHoursEach,
        stations.Description as StationDescription,
        pciss.QuantityCompleted as StationQuantityCompleted,
        pciss.TotalQuantity as StationTotalQuantity
    ";
}

// Finish the SQL query
$sql .= " FROM workpackages as wp 
    INNER JOIN productioncontroljobs as pcj ON pcj.ProductionControlID = wp.ProductionControlID
    INNER JOIN workshops as ws ON ws.WorkshopID = wp.WorkShopID
    INNER JOIN productioncontrolsequences as pcseq ON pcseq.WorkPackageID = wp.WorkPackageID AND pcseq.AssemblyQuantity > 0
    INNER JOIN productioncontrolitemsequences as pciseq ON pciseq.SequenceID = pcseq.SequenceID AND pciseq.Quantity > 0
    INNER JOIN productioncontrolassemblies as pca ON pca.ProductionControlAssemblyID = pciseq.ProductionControlAssemblyID
    INNER JOIN productioncontrolitems as pci ON pci.ProductionControlAssemblyID = pca.ProductionControlAssemblyID
    INNER JOIN shapes ON shapes.ShapeID = pci.ShapeID AND shapes.Shape NOT IN ('HS','NU','WA')
    LEFT JOIN routes as rt on rt.RouteID = pci.RouteID
    LEFT JOIN productioncontrolcategories as pccat on pccat.CategoryID = pci.CategoryID
    INNER JOIN productioncontrolitemstationsummary as pciss ON pciss.ProductionControlItemID = pci.ProductionControlItemID 
        AND pciss.SequenceID = pcseq.SequenceID 
        AND pciss.ProductionControlID = pcseq.ProductionControlID
    INNER JOIN stations ON stations.StationID = pciss.StationID
 WHERE wp.completed = 0 AND wp.Group2 = :workweek AND pcseq.AssemblyQuantity > 0 AND pci.MainPiece = 1
 " . $filterSql . "
 ORDER BY wp.WorkPackageNumber, SequenceDescription, MainMark, StationDescription
 LIMIT 1000";

$stmt = $db->prepare($sql);
$stmt->bindParam(':workweek', $workweek, PDO::PARAM_STR);

// Bind filter parameters
foreach ($filters as $key => $value) {
    switch ($key) {
        case 'mainmark':
        case 'piecemark':
        case 'route':
            $stmt->bindValue(':' . $key, '%' . $value . '%', PDO::PARAM_STR);
            break;
        default:
            $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
            break;
    }
}

$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

    <div class="container-fluid mt-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="mb-0">Workweek Data Viewer - Week <?php echo htmlspecialchars($workweek); ?></h2>
            <div>
                <a href="index.php?workweek=<?php echo urlencode($workweek); ?>" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-layout-text-window"></i> Dashboard View
                </a>
                <a href="#" class="btn btn-sm btn-outline-success" id="exportBtn">
                    <i class="bi bi-file-excel"></i> Export to CSV
                </a>
            </div>
        </div>

        <!-- Filter Form -->
        <div class="card mb-3">
            <div class="card-header bg-ssf-primary text-white">
                <h5 class="mb-0">Filter Data</h5>
            </div>
            <div class="card-body">
                <form action="view_data.php" method="get" class="row g-3">
                    <input type="hidden" name="workweek" value="<?php echo htmlspecialchars($workweek); ?>">
                    <input type="hidden" name="view" value="<?php echo htmlspecialchars($viewType); ?>">

                    <div class="col-md-2">
                        <label for="jobnumber" class="form-label">Job Number</label>
                        <input type="text" class="form-control" id="jobnumber" name="jobnumber"
                               value="<?php echo isset($filters['jobnumber']) ? htmlspecialchars($filters['jobnumber']) : ''; ?>">
                    </div>

                    <div class="col-md-2">
                        <label for="mainmark" class="form-label">Main Mark</label>
                        <input type="text" class="form-control" id="mainmark" name="mainmark"
                               value="<?php echo isset($filters['mainmark']) ? htmlspecialchars($filters['mainmark']) : ''; ?>">
                    </div>

                    <div class="col-md-2">
                        <label for="workpackage" class="form-label">Work Package</label>
                        <input type="text" class="form-control" id="workpackage" name="workpackage"
                               value="<?php echo isset($filters['workpackage']) ? htmlspecialchars($filters['workpackage']) : ''; ?>">
                    </div>

                    <div class="col-md-2">
                        <label for="bay" class="form-label">Bay</label>
                        <input type="text" class="form-control" id="bay" name="bay"
                               value="<?php echo isset($filters['bay']) ? htmlspecialchars($filters['bay']) : ''; ?>">
                    </div>

                    <div class="col-md-2">
                        <label for="route" class="form-label">Route</label>
                        <input type="text" class="form-control" id="route" name="route"
                               value="<?php echo isset($filters['route']) ? htmlspecialchars($filters['route']) : ''; ?>">
                    </div>

                    <div class="col-md-2">
                        <label for="view" class="form-label">View Type</label>
                        <select class="form-select" id="view" name="view">
                            <option value="basic" <?php echo $viewType === 'basic' ? 'selected' : ''; ?>>Basic</option>
                            <option value="detailed" <?php echo $viewType === 'detailed' ? 'selected' : ''; ?>>Detailed</option>
                        </select>
                    </div>

                    <div class="col-12 mt-3">
                        <button type="submit" class="btn btn-ssf-primary">Apply Filters</button>
                        <a href="view_data.php?workweek=<?php echo urlencode($workweek); ?>" class="btn btn-outline-secondary">Clear Filters</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Workweek Selector -->
        <div class="mb-3">
            <div class="btn-group" id="workweekSelector">
                <button type="button" class="btn btn-ssf-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    Select Workweek
                </button>
                <ul class="dropdown-menu">
                    <?php
                    // Get available workweeks
                    $query = "SELECT DISTINCT Group2 as WorkWeeks 
                          FROM workpackages 
                          INNER JOIN productioncontroljobs as pcj ON pcj.productionControlID = workpackages.productionControlID 
                          WHERE Completed = 0 AND OnHold = 0 
                          ORDER BY WorkWeeks ASC";
                    $result = $db->query($query);
                    $workweeks = $result->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($workweeks as $ww) {
                        if (empty($ww['WorkWeeks'])) continue;
                        $isActive = $ww['WorkWeeks'] == $workweek;
                        echo '<li><a class="dropdown-item ' . ($isActive ? 'active' : '') . '" href="view_data.php?workweek=' .
                            urlencode($ww['WorkWeeks']) . '">' . htmlspecialchars($ww['WorkWeeks']) . '</a></li>';
                    }
                    ?>
                </ul>
            </div>
        </div>

        <!-- Results Table -->
        <div class="table-responsive">
            <table class="table table-sm table-bordered table-striped" id="dataTable">
                <thead class="bg-ssf-primary text-white">
                <tr>
                    <?php
                    // Get column headers from first row
                    if (!empty($results)) {
                        foreach (array_keys($results[0]) as $column) {
                            echo '<th>' . htmlspecialchars($column) . '</th>';
                        }
                    } else {
                        echo '<th>No data available</th>';
                    }
                    ?>
                </tr>
                </thead>
                <tbody>
                <?php
                if (!empty($results)) {
                    foreach ($results as $row) {
                        echo '<tr>';
                        foreach ($row as $value) {
                            echo '<td>' . htmlspecialchars($value ?? '') . '</td>';
                        }
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="' . (count(array_keys($results[0] ?? [])) ?: 1) . '" class="text-center">No data available</td></tr>';
                }
                ?>
                </tbody>
            </table>
        </div>

        <?php if (count($results) >= 1000): ?>
            <div class="alert alert-warning mt-3">
                <i class="bi bi-exclamation-triangle-fill"></i> Results are limited to 1000 rows. Please use filters to narrow your search.
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Export to CSV functionality
            document.getElementById('exportBtn').addEventListener('click', function(e) {
                e.preventDefault();
                exportTableToCSV('dataTable', 'workweek_<?php echo $workweek; ?>_data.csv');
            });

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
        });
    </script>

<?php
// Include the footer
include_once __DIR__ . '/../includes/footer.php';
?>