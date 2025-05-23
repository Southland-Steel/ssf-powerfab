<?php
/**
 * Raw Feedback Data Viewer
 * Displays all FEEDBACK_FBK_RAW data in a tabular format
 * Optimized for iPad viewing
 */

// Page configuration
$pageTitle = 'Raw Feedback Data';
$pageStyles = ['dashboard.css', 'rawdata.css'];
$pageScripts = ['rawdata.js'];

// Include database configuration
require_once 'includes/db_config.php';

// Get machine parameter (optional filter)
$machine = $_GET['machine'] ?? '';
$limit = $_GET['limit'] ?? 100;
$offset = $_GET['offset'] ?? 0;

// Build query
$sql = "SELECT * FROM FEEDBACK_FBK_RAW";
$params = [];

if (!empty($machine)) {
    $sql .= " WHERE FFR_CNC = ?";
    $params[] = $machine;
}

$sql .= " ORDER BY DATM DESC";

// For SQL Server 2012+, use OFFSET/FETCH
// For older versions, we'd need to use ROW_NUMBER()
$sql .= " OFFSET " . (int)$offset . " ROWS FETCH NEXT " . (int)$limit . " ROWS ONLY";

// Get total count for pagination
$countSql = "SELECT COUNT(*) as total FROM FEEDBACK_FBK_RAW";
if (!empty($machine)) {
    $countSql .= " WHERE FFR_CNC = ?";
    $totalCount = $db->queryValue($countSql, [$machine]);
} else {
    $totalCount = $db->queryValue($countSql);
}

// Fetch data
$data = $db->query($sql, $params);

// Debug output - remove after testing
if ($data === false) {
    echo "<!-- SQL Error: " . $db->getError() . " -->";
    echo "<!-- SQL Query: " . $sql . " -->";
}

// Get column information
$columnsSql = "
SELECT 
    COLUMN_NAME,
    DATA_TYPE,
    CHARACTER_MAXIMUM_LENGTH,
    ORDINAL_POSITION
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'FEEDBACK_FBK_RAW'
ORDER BY ORDINAL_POSITION";

$columns = $db->query($columnsSql);

// Debug column query
if ($columns === false || empty($columns)) {
    echo "<!-- Column Error: " . $db->getError() . " -->";
    // Try alternative query
    $columnsSql = "SELECT TOP 1 * FROM FEEDBACK_FBK_RAW";
    $sampleRow = $db->queryRow($columnsSql);
    if ($sampleRow) {
        $columns = [];
        $position = 1;
        foreach ($sampleRow as $colName => $value) {
            $columns[] = [
                'COLUMN_NAME' => $colName,
                'DATA_TYPE' => gettype($value),
                'CHARACTER_MAXIMUM_LENGTH' => null,
                'ORDINAL_POSITION' => $position++
            ];
        }
    }
}

// Include header
require_once 'includes/header.php';
?>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="mb-0">
                        <i class="fas fa-database me-2"></i>
                        Raw Feedback Data
                        <?php if (!empty($machine)): ?>
                            <span class="h5 text-white-50">- <?php echo htmlspecialchars($machine); ?></span>
                        <?php endif; ?>
                    </h1>
                    <p class="mb-0 mt-1 opacity-75">FEEDBACK_FBK_RAW table data</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="index.php" class="btn btn-outline-light">
                        <i class="fas fa-arrow-left me-2"></i>
                        Back to Dashboard
                    </a>
                    <button class="btn btn-light ms-2" onclick="exportTableData()">
                        <i class="fas fa-download me-2"></i>
                        Export CSV
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container-fluid mt-4">
        <!-- Controls Bar -->
        <div class="controls-bar mb-3">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-search"></i>
                    </span>
                        <input type="text" class="form-control" id="tableSearch"
                               placeholder="Search in visible data...">
                    </div>
                </div>
                <div class="col-md-4 text-center">
                <span class="text-muted">
                    Showing <?php echo number_format($offset + 1); ?> -
                    <?php echo number_format(min($offset + $limit, $totalCount)); ?>
                    of <?php echo number_format($totalCount); ?> records
                </span>
                </div>
                <div class="col-md-4 text-end">
                    <button class="btn btn-sm btn-outline-secondary" onclick="toggleColumns()">
                        <i class="fas fa-columns me-1"></i>
                        Columns
                    </button>
                    <button class="btn btn-sm btn-outline-secondary ms-1" onclick="resetFilters()">
                        <i class="fas fa-redo me-1"></i>
                        Reset
                    </button>
                </div>
            </div>
        </div>

        <!-- Column Toggle Modal -->
        <div id="columnTogglePanel" class="column-toggle-panel" style="display: none;">
            <div class="panel-header">
                <h6>Toggle Columns</h6>
                <button class="btn-close" onclick="toggleColumns()"></button>
            </div>
            <div class="panel-body">
                <div class="mb-2">
                    <button class="btn btn-sm btn-primary" onclick="selectAllColumns(true)">Show All</button>
                    <button class="btn btn-sm btn-secondary" onclick="selectAllColumns(false)">Hide All</button>
                </div>
                <div class="column-list">
                    <?php foreach ($columns as $col): ?>
                        <label class="column-toggle-item">
                            <input type="checkbox" class="column-toggle"
                                   data-column="<?php echo htmlspecialchars($col['COLUMN_NAME']); ?>"
                                   checked>
                            <span><?php echo htmlspecialchars($col['COLUMN_NAME']); ?></span>
                            <small class="text-muted">(<?php echo $col['DATA_TYPE']; ?>)</small>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Data Table -->
        <div class="table-container">
            <table class="table table-sm table-striped table-hover" id="rawDataTable">
                <thead class="sticky-top">
                <tr>
                    <?php foreach ($columns as $col): ?>
                        <th class="column-<?php echo htmlspecialchars($col['COLUMN_NAME']); ?>">
                            <?php echo htmlspecialchars($col['COLUMN_NAME']); ?>
                            <span class="column-type"><?php echo $col['DATA_TYPE']; ?></span>
                        </th>
                    <?php endforeach; ?>
                </tr>
                </thead>
                <tbody>
                <?php if ($data === false): ?>
                    <tr>
                        <td colspan="<?php echo count($columns); ?>" class="text-center py-4 text-danger">
                            Error loading data: <?php echo htmlspecialchars($db->getError()); ?>
                        </td>
                    </tr>
                <?php elseif (empty($data)): ?>
                    <tr>
                        <td colspan="<?php echo count($columns); ?>" class="text-center py-4">
                            No data found
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($data as $row): ?>
                        <tr>
                            <?php foreach ($columns as $col): ?>
                                <td class="column-<?php echo htmlspecialchars($col['COLUMN_NAME']); ?>">
                                    <?php
                                    $value = $row[$col['COLUMN_NAME']] ?? '';

                                    // Special formatting for certain columns
                                    if ($col['COLUMN_NAME'] == 'FFR_TYP') {
                                        $typeClass = '';
                                        switch($value) {
                                            case 'P': $typeClass = 'badge bg-success'; break;
                                            case 'N': $typeClass = 'badge bg-info'; break;
                                            case 'A': $typeClass = 'badge bg-danger'; break;
                                        }
                                        echo '<span class="' . $typeClass . '">' . htmlspecialchars($value) . '</span>';
                                    } elseif ($col['COLUMN_NAME'] == 'DATM' && $value) {
                                        // Format datetime
                                        if ($value instanceof DateTime) {
                                            echo $value->format('Y-m-d H:i:s');
                                        } else {
                                            echo htmlspecialchars($value);
                                        }
                                    } elseif (is_numeric($value) && $col['DATA_TYPE'] == 'float') {
                                        echo number_format((float)$value, 2);
                                    } else {
                                        echo htmlspecialchars($value);
                                    }
                                    ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <nav aria-label="Data pagination" class="mt-3">
            <ul class="pagination justify-content-center">
                <?php
                $currentPage = floor($offset / $limit) + 1;
                $totalPages = ceil($totalCount / $limit);
                $maxPagesToShow = 10;

                // Previous button
                if ($currentPage > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?machine=<?php echo urlencode($machine); ?>&offset=<?php echo ($currentPage - 2) * $limit; ?>&limit=<?php echo $limit; ?>">
                            Previous
                        </a>
                    </li>
                <?php endif;

                // Page numbers
                $startPage = max(1, $currentPage - floor($maxPagesToShow / 2));
                $endPage = min($totalPages, $startPage + $maxPagesToShow - 1);

                if ($startPage > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?machine=<?php echo urlencode($machine); ?>&offset=0&limit=<?php echo $limit; ?>">1</a>
                    </li>
                    <?php if ($startPage > 2): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif;
                endif;

                for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <li class="page-item <?php echo $i == $currentPage ? 'active' : ''; ?>">
                        <a class="page-link" href="?machine=<?php echo urlencode($machine); ?>&offset=<?php echo ($i - 1) * $limit; ?>&limit=<?php echo $limit; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor;

                if ($endPage < $totalPages): ?>
                    <?php if ($endPage < $totalPages - 1): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; ?>
                    <li class="page-item">
                        <a class="page-link" href="?machine=<?php echo urlencode($machine); ?>&offset=<?php echo ($totalPages - 1) * $limit; ?>&limit=<?php echo $limit; ?>">
                            <?php echo $totalPages; ?>
                        </a>
                    </li>
                <?php endif;

                // Next button
                if ($currentPage < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?machine=<?php echo urlencode($machine); ?>&offset=<?php echo $currentPage * $limit; ?>&limit=<?php echo $limit; ?>">
                            Next
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>

        <!-- Records per page selector -->
        <div class="text-center mt-2">
            <label>Records per page: </label>
            <select class="form-select form-select-sm d-inline-block w-auto" onchange="changeLimit(this.value)">
                <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100</option>
                <option value="500" <?php echo $limit == 500 ? 'selected' : ''; ?>>500</option>
                <option value="1000" <?php echo $limit == 1000 ? 'selected' : ''; ?>>1,000</option>
                <option value="5000" <?php echo $limit == 5000 ? 'selected' : ''; ?>>5,000</option>
            </select>
        </div>
    </div>

<?php
// Include footer
require_once 'includes/footer.php';
?>