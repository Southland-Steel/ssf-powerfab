<?php
require_once '../config_ssf_db.php';
$sequence_id = $_GET['sequence_id'] ?? null;
$pci_id = $_GET['pci_id'] ?? null;
$workweek = $_GET['workweek'] ?? null;

// Get station summary information
$stationQuery = "SELECT 
    pciss.ProductionControlItemStationSummaryID,
    pciss.StationID,
    pciss.PositionInRoute,
    pciss.TotalQuantity,
    pciss.QuantityCompleted,
    pciss.LastDateCompleted,
    pciss.FailedInspectionTestQuantity,
    pciss.PreviousStationQuantityCompleted,
    stations.Description as StationName,
    routes.Route,
    routes.IsAssemblyRoute,
    routes.IsPartRoute
FROM productioncontrolitemstationsummary as pciss
INNER JOIN stations ON stations.StationID = pciss.StationID
INNER JOIN productioncontrolitems as pci ON pci.ProductionControlItemID = pciss.ProductionControlItemID
LEFT JOIN routes ON routes.RouteID = pci.RouteID
WHERE pciss.SequenceID = :sequence_id 
AND pciss.ProductionControlItemID = :pci_id
ORDER BY pciss.PositionInRoute ASC";

$stmt = $db->prepare($stationQuery);
$stmt->execute([
    ':sequence_id' => $sequence_id,
    ':pci_id' => $pci_id
]);
$stationSummary = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get piecemark details
$piecemarkQuery = "SELECT
    REPLACE(pciseq.MainMark, char(1), '') as MainMark,
    REPLACE(pci.PieceMark, char(1), '') as PieceMark,
    pci.MainPiece as IsMainPiece,
    shapes.Shape,
    pci.DimensionString,
    ROUND(pci.Length / 25.4, 3) as InchLength,
    pca.AssemblyManHoursEach,
    ROUND(pci.ManHours / pci.Quantity, 2) as PieceMarkManHoursEach,
    ROUND(pci.Quantity / pca.AssemblyQuantity) as PieceMarkAssemblyQuantityEach,
    pciroute.Route,
    pcicategory.Description as CategoryName,
    pcisubcategory.Description as SubCategoryName,
    pci.GrossWeight,
    REPLACE(pcseq.Description, CHAR(1), '') AS SequenceName,
    REPLACE(pcseq.LotNumber, CHAR(1), '') AS LotNumber,
    wp.WorkPackageNumber
FROM productioncontrolitems as pci
INNER JOIN productioncontrolassemblies as pca ON pca.ProductionControlAssemblyID = pci.ProductionControlAssemblyID
INNER JOIN productioncontrolitemsequences as pciseq ON pciseq.ProductionControlAssemblyID = pca.ProductionControlAssemblyID
INNER JOIN productioncontrolsequences as pcseq ON pcseq.SequenceID = pciseq.SequenceID
INNER JOIN workpackages as wp ON wp.WorkPackageID = pcseq.WorkPackageID
INNER JOIN shapes ON shapes.ShapeID = pci.ShapeID
INNER JOIN routes as pciroute ON pciroute.RouteID = pci.RouteID
INNER JOIN productioncontrolcategories as pcicategory ON pcicategory.CategoryID = pci.CategoryID
INNER JOIN productioncontrolsubcategories as pcisubcategory ON pcisubcategory.SubCategoryID = pci.SubCategoryID
WHERE pci.ProductionControlItemID = :pci_id
LIMIT 1";

$stmt = $db->prepare($piecemarkQuery);
$stmt->execute([':pci_id' => $pci_id]);
$piecemarkInfo = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Piecemark <?= htmlspecialchars($piecemarkInfo['PieceMark']) ?></title>
    <link rel="stylesheet" href="workweeks.css?v=<?= time() ?>">
</head>
<body>
<header class="header">
    <div class="header-content">
        <img src="../images/ssf-horiz.png" alt="Logo" class="logo">
    </div>
</header>

<main class="main-container full-width">
    <div class="breadcrumbs">
        <a href="workweeks.php">Workweeks</a>
        <span class="separator">/</span>
        <a href="workweeks.php?workweek=<?= $workweek ?>">Week <?= $workweek ?></a>
        <span class="separator">/</span>
        <span>Piecemark <?= htmlspecialchars($piecemarkInfo['PieceMark']) ?></span>
    </div>

    <div class="content-wrapper">
        <div class="main-content">
            <!-- Piecemark Details -->
            <div class="data-table-container">
                <h2 class="section-title">Piecemark Details</h2>
                <table class="data-table">
                    <tbody>
                    <tr>
                        <th>Sequence / Lot</th>
                        <td><?= htmlspecialchars($piecemarkInfo['SequenceName']) ?> / <?= htmlspecialchars($piecemarkInfo['LotNumber']) ?></td>
                        <th>Work Package</th>
                        <td><?= htmlspecialchars($piecemarkInfo['WorkPackageNumber']) ?></td>
                    </tr>
                    <tr>
                        <th>Shape & Size</th>
                        <td><?= htmlspecialchars($piecemarkInfo['Shape']) ?> <?= htmlspecialchars($piecemarkInfo['DimensionString']) ?></td>
                        <th>Length</th>
                        <td><?= htmlspecialchars($piecemarkInfo['InchLength']) ?> inches</td>
                    </tr>
                    <tr>
                        <th>Category</th>
                        <td><?= htmlspecialchars($piecemarkInfo['CategoryName']) ?></td>
                        <th>SubCategory</th>
                        <td><?= htmlspecialchars($piecemarkInfo['SubCategoryName']) ?></td>
                    </tr>
                    <tr>
                        <th>Route</th>
                        <td><?= htmlspecialchars($piecemarkInfo['Route']) ?></td>
                        <th>Weight</th>
                        <td><?= htmlspecialchars($piecemarkInfo['GrossWeight']) ?> lbs</td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <!-- Station Summary -->
            <div class="data-table-container">
                <h2 class="section-title">Station Progress</h2>
                <table class="data-table">
                    <thead>
                    <tr>
                        <th>Station</th>
                        <th>Position</th>
                        <th>Progress</th>
                        <th>Completed</th>
                        <th>Failed</th>
                        <th>Last Update</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($stationSummary as $station): ?>
                        <tr>
                            <td><?= htmlspecialchars($station['StationName']) ?></td>
                            <td><?= htmlspecialchars($station['PositionInRoute']) ?></td>
                            <td>
                                <?php
                                $progressPercent = ($station['TotalQuantity'] > 0)
                                    ? round(($station['QuantityCompleted'] / $station['TotalQuantity']) * 100, 1)
                                    : 0;
                                ?>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= $progressPercent ?>%"></div>
                                    <span class="progress-text"><?= $progressPercent ?>%</span>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($station['QuantityCompleted']) ?> / <?= htmlspecialchars($station['TotalQuantity']) ?></td>
                            <td><?= htmlspecialchars($station['FailedInspectionTestQuantity']) ?></td>
                            <td><?= $station['LastDateCompleted'] ? date('Y-m-d', strtotime($station['LastDateCompleted'])) : '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>
</body>
</html>