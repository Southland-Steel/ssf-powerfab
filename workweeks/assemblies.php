<?php
require_once '../config_ssf_db.php';
$pcis_id = $_GET['pcis_id'] ?? null;
$workweek = $_GET['workweek'] ?? null;

$query = "select 
    pciseq.ProductionControlItemSequenceID,
    pca.ProductionControlAssemblyID,
    REPLACE(pciseq.MainMark, char(1), '') as MainMark,
    pciseq.SequenceID,
    pcj.JobNumber,
    pciseq.Quantity as SequenceAssemblyQuantity,
    Round(pca.GrossAssemblyWeightEach * 2.20462, 2) as GrossAssemblyWeightEach,
    ROUND(pca.AssemblyManHoursEach, 3) as AssemblyManHoursEach,
    routes.Route,
    pcacat.Description as AssemblyCategory,
    pcasubcat.Description as AssemblySubCategory,
    pca.MainPieceProductionControlItemID,
    REPLACE(pcseq.Description, CHAR(1), '') AS SequenceName,
    REPLACE(pcseq.LotNumber, CHAR(1), '') AS LotNumber,
    wp.WorkPackageNumber
from productioncontrolitemsequences as pciseq
inner join productioncontrolassemblies as pca on pca.ProductionControlAssemblyID = pciseq.ProductionControlAssemblyID
inner join productioncontrolsequences as pcseq on pcseq.SequenceID = pciseq.SequenceID
inner join productioncontroljobs as pcj on pcj.ProductionControlID = pciseq.ProductionControlID
inner join productioncontrolitems as pci ON pci.ProductionControlItemID = pca.MainPieceProductionControlItemID
inner join workpackages as wp ON wp.WorkPackageID = pcseq.WorkPackageID
left join routes on routes.RouteID = pci.RouteID
left join productioncontrolcategories as pcacat ON pcacat.CategoryID = pci.CategoryID
left join productioncontrolsubcategories as pcasubcat ON pcasubcat.SubCategoryID = pci.SubCategoryID
where pciseq.ProductionControlItemSequenceID = :pcis_id and wp.WorkshopID = 1";

$stmt = $db->prepare($query);
$stmt->execute([':pcis_id' => $pcis_id]);
$assemblyInfo = $stmt->fetch(PDO::FETCH_ASSOC);

// Calculate totals for summary
$totalWeight = $assemblyInfo['GrossAssemblyWeightEach'] * $assemblyInfo['SequenceAssemblyQuantity'];
$totalHours = $assemblyInfo['AssemblyManHoursEach'] * $assemblyInfo['SequenceAssemblyQuantity'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Assembly <?= htmlspecialchars($assemblyInfo['MainMark']) ?></title>
    <link rel="stylesheet" href="workweeks.css?v=<?= time() ?>">
    <style>
        /* Status indicator container */
        .status-cell {
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
        }

        .status-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }

        .status-text {
            font-size: 0.75rem;
            color: #6b7280;
        }

        /* Status colors */
        .status-dot.red {
            background-color: #ef4444;
        }

        .status-dot.yellow {
            background-color: #f59e0b;
        }

        .status-dot.green {
            background-color: #10b981;
        }

        .status-dot.blue {
            background-color: #3b82f6;
        }

        .status-dot.empty {
            border: 2px solid #e5e7eb;
            background-color: transparent;
        }
    </style>
</head>
<body>
<header class="header">
    <div class="header-content full-width">
        <img src="../images/ssf-horiz.png" alt="Logo" class="logo">
    </div>
</header>

<main class="main-container full-width">
    <div class="breadcrumbs">
        <a href="workweeks.php">Workweeks</a>
        <span class="separator">/</span>
        <a href="workweeks.php?workweek=<?= $workweek ?>">Week <?= $workweek ?></a>
        <span class="separator">/</span>
        <span>Assembly <?= htmlspecialchars($assemblyInfo['MainMark']) ?></span>
    </div>

    <div class="content-wrapper">
        <div class="main-content">
            <div class="summary-cards">
                <div class="summary-card">
                    <div class="summary-card-title">Assembly Quantity</div>
                    <div class="summary-card-value"><?= number_format($assemblyInfo['SequenceAssemblyQuantity']) ?></div>
                </div>
                <div class="summary-card">
                    <div class="summary-card-title">Total Weight</div>
                    <div class="summary-card-value"><?= number_format($totalWeight, 2) ?> lbs</div>
                </div>
                <div class="summary-card">
                    <div class="summary-card-title">Total Hours</div>
                    <div class="summary-card-value"><?= number_format($totalHours, 2) ?> hrs</div>
                </div>
                <div class="summary-card">
                    <div class="summary-card-title">Job Number</div>
                    <div class="summary-card-value"><?= htmlspecialchars($assemblyInfo['JobNumber']) ?></div>
                </div>
            </div>

            <div class="data-table-container">
                <h2 class="section-title">Assembly Details</h2>
                <table class="data-table">
                    <tbody>
                    <tr>
                        <th>Sequence / Lot</th>
                        <td><?= htmlspecialchars($assemblyInfo['SequenceName']) ?> / <?= htmlspecialchars($assemblyInfo['LotNumber']) ?></td>
                        <th>Work Package</th>
                        <td><?= htmlspecialchars($assemblyInfo['WorkPackageNumber']) ?></td>
                    </tr>
                    <tr>
                        <th>Route</th>
                        <td><?= htmlspecialchars($assemblyInfo['Route']) ?></td>
                        <th>Category / SubCategory</th>
                        <td><?= htmlspecialchars($assemblyInfo['AssemblyCategory']) ?> / <?= htmlspecialchars($assemblyInfo['AssemblySubCategory']) ?></td>
                    </tr>
                    <tr>
                        <th>PCIS ID</th>
                        <td><?= htmlspecialchars($assemblyInfo['ProductionControlItemSequenceID']) ?></td>
                        <th>Assembly ID</th>
                        <td><?= htmlspecialchars($assemblyInfo['ProductionControlAssemblyID']) ?></td>
                    </tr>
                    <tr>
                        <th>Sequence ID</th>
                        <td><?= htmlspecialchars($assemblyInfo['SequenceID']) ?></td>
                        <th>MainPiece PCI ID</th>
                        <td><?= htmlspecialchars($assemblyInfo['MainPieceProductionControlItemID']) ?></td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <div id="piecemarks">
                <h2 class="section-title">Assembly Piecemarks</h2>
                <table class="data-table">
                    <thead>
                    <tr>
                        <th>Piecemark</th>
                        <th>Shape & Size</th>
                        <th>Length</th>
                        <th>CUT</th>
                        <th>STATION</th>
                        <th>Routing</th>
                        <th>Category</th>
                        <th>Required</th>
                        <th>Cut List</th>
                        <th>On Order</th>
                        <th>In Stock</th>
                        <th>TFS</th>
                        <th>Hours</th>
                        <th>Weight</th>
                    </tr>
                    </thead>
                    <tbody id="piecemarks-body">
                    </tbody>
                </table>
            </div>
        </div>
        <div class="side-content">
            <h2 class="section-title">Related Cutlists</h2>
            <div id="cutlist-container">
                <div class="loading">Loading cutlists...</div>
            </div>
        </div>
    </div>
</main>
<script>
    function getCutStationCell(piece) {
        const required = parseInt(piece.SequencePieceMarkQuantityNeeded);
        const cutStationCompleted = parseInt(piece.CutStationCompleted) || 0;

        if (piece.Shape === 'HS' || piece.Shape === 'NU' || piece.Shape === 'WA' || !piece.InchLength) {
            return ''; // No station status needed for these items
        }

        let status = {
            class: 'empty',
            text: '-'
        };

        if (cutStationCompleted > 0) {
            if (cutStationCompleted >= required) {
                status = { class: 'green', text: `${cutStationCompleted}` };
            } else {
                status = { class: 'yellow', text: `${cutStationCompleted}/${required}` };
            }
        }

        return `
        <div class="status-cell">
            <div class="status-dot ${status.class}"></div>
            <div class="status-text">${status.text}</div>
        </div>
    `;
    }

    function getCutStatusCell(piece) {
        // First check for non-actionable items
        if (piece.Shape === 'HS' || piece.Shape === 'NU' || piece.Shape === 'WA' || !piece.InchLength) {
            return `
            <div class="status-cell">
                <div class="status-dot empty"></div>
                <div class="status-text">N/A</div>
            </div>
        `;
        }

        const required = parseInt(piece.SequencePieceMarkQuantityNeeded);
        const tfs = parseInt(piece.QuantityTFS) || 0;
        const inStock = parseInt(piece.QuantityLinkedInventoryInStock) || 0;
        const onOrder = parseInt(piece.QuantityLinkedInventoryOnOrder) || 0;
        const cutList = parseInt(piece.QuantityCutList) || 0;
        const cutStationCompleted = parseInt(piece.CutStationCompleted) || 0;

        let status = {
            class: 'empty',
            text: 'OK'
        };

        // Add cut station completed to our status check
        if (tfs >= required) {
            status = { class: 'green', text: 'TFS' };
        } else if (inStock >= required) {
            status = { class: 'blue', text: 'Stock' };
        } else if (onOrder >= required) {
            status = { class: 'yellow', text: 'Order' };
        } else if (cutStationCompleted >= required) {
            status = { class: 'green', text: 'Cut' };
        } else if (cutList < required && (inStock + onOrder) < required) {
            status = { class: 'red', text: 'Need' };
        }

        // Add tooltip with cut station details if available
        let tooltip = '';
        if (cutStationCompleted > 0) {
            const lastCutDate = new Date(piece.CutStationLastCompleted).toLocaleDateString();
            tooltip = `title="Cut Station: ${cutStationCompleted} completed on ${lastCutDate}"`;
        }

        return `
        <div class="status-cell" ${tooltip}>
            <div class="status-dot ${status.class}"></div>
            <div class="status-text">${status.text}</div>
        </div>
    `;
    }

    function loadPiecemarks() {
        fetch(`get_assembly_piecemarks.php?pcis_id=<?= $pcis_id ?>`)
            .then(response => response.json())
            .then(data => {
                let html = '';
                data.forEach(piece => {
                    const piecemarkClass = piece.IsMainPiece ? 'font-bold' : '';
                    html += `
    <tr class="${piecemarkClass}">
        <td>
            <a href="piecemark.php?sequence_id=<?= htmlspecialchars($assemblyInfo['SequenceID']) ?>&pci_id=${piece.ProductionControlItemID}&workweek=<?= $workweek ?>&assemblyMark=<?= htmlspecialchars($assemblyInfo['MainMark']) ?>">
            ${piece.PieceMark}
            </a>
            <div class="text-sm text-gray-500">${piece.ProductionControlItemID}</div>
        </td>
        <td>${piece.Shape} ${piece.DimensionString}</td>
        <td>${piece.FormattedLength}</td>
        <td>${getCutStatusCell(piece)}</td>
        <td>${getCutStationCell(piece)}</td>
        <td>
            <div>${piece.Route}</div>
        </td>
        <td>${piece.CategoryName}<div class="text-sm text-gray-500">${piece.SubCategoryName}</div></td>
        <td>${piece.SequencePieceMarkQuantityNeeded}</td>
        <td>${parseInt(piece.QuantityCutList) || 0}</td>
        <td>${parseInt(piece.QuantityLinkedInventoryOnOrder) || 0}</td>
        <td>${parseInt(piece.QuantityLinkedInventoryInStock) || 0}</td>
        <td>${parseInt(piece.QuantityTFS) || 0}</td>
        <td>
            <div>${piece.PieceMarkManHoursEach} hrs each</div>
        </td>
        <td>${piece.GrossWeight} lbs</td>
    </tr>`;
                });
                document.getElementById('piecemarks-body').innerHTML = html;
            });
    }

    document.addEventListener('DOMContentLoaded', () => {
        loadPiecemarks();
    });
</script>
</body>
</html>