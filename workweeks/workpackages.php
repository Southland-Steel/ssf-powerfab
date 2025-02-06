<?php
require_once '../config_ssf_db.php';
$wp_id = $_GET['wp_id'] ?? null;
$workweek = $_GET['workweek'] ?? null;

$wpQuery = "
SELECT 
    wp.WorkPackageNumber,
    pciseq.SequenceID,
    SUM(pciseq.Quantity) as TotalPieces,
    ROUND(SUM(pciseq.Quantity * pca.GrossAssemblyWeightEach), 2) as TotalWeight,
    ROUND(SUM(pciseq.Quantity * pca.AssemblyManHoursEach), 2) as TotalHours
FROM workpackages wp
LEFT JOIN productioncontrolsequences pcseq ON pcseq.WorkPackageID = wp.WorkPackageID
LEFT JOIN productioncontrolitemsequences pciseq ON pciseq.SequenceID = pcseq.SequenceID
LEFT JOIN productioncontrolassemblies pca ON pca.ProductionControlAssemblyID = pciseq.ProductionControlAssemblyID
WHERE wp.WorkPackageID = :wp_id
GROUP BY wp.WorkPackageID, wp.WorkPackageNumber";

$stmt = $db->prepare($wpQuery);
$stmt->execute([':wp_id' => $wp_id]);
$wpInfo = $stmt->fetch(PDO::FETCH_ASSOC);

$seqQuery = "
SELECT DISTINCT 
    pcseq.SequenceID,
    CONCAT(
        REPLACE(pcseq.Description, CHAR(1),''),
        ' - ',
        REPLACE(pcseq.LotNumber, CHAR(1), '')
    ) AS SequenceKey
FROM productioncontrolsequences pcseq
INNER JOIN workpackages as wp on wp.WorkPackageID = pcseq.WorkPackageID
WHERE pcseq.WorkPackageID = :wp_id
and wp.WorkshopID = 1
and pcseq.AssemblyQuantity > 0
ORDER BY pcseq.Description, pcseq.LotNumber";

$stmt = $db->prepare($seqQuery);
$stmt->execute([':wp_id' => $wp_id]);
$sequences = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>WP <?= htmlspecialchars($wpInfo['WorkPackageNumber']) ?></title>
    <link rel="stylesheet" href="workweeks.css?v=<?= time() ?>">
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
        <span>WP <?= htmlspecialchars($wpInfo['WorkPackageNumber']) ?></span>
    </div>

    <div class="content-wrapper">
        <div class="main-content">
            <div class="summary-cards">
                <div class="summary-card">
                    <div class="summary-card-title">Total Pieces</div>
                    <div class="summary-card-value"><?= number_format($wpInfo['TotalPieces']) ?></div>
                </div>
                <div class="summary-card">
                    <div class="summary-card-title">Total Weight</div>
                    <div class="summary-card-value"><?= number_format($wpInfo['TotalWeight'], 2) ?> lbs</div>
                </div>
                <div class="summary-card">
                    <div class="summary-card-title">Total Hours</div>
                    <div class="summary-card-value"><?= number_format($wpInfo['TotalHours'], 2) ?> hrs</div>
                </div>
            </div>

            <div class="sequence-buttons">
                <?php foreach ($sequences as $seq): ?>
                    <button class="sequence-btn"
                            data-sequence-id="<?= htmlspecialchars($seq['SequenceID']) ?>"
                            onclick="loadSequenceData(this.dataset.sequenceId)">
                        <?= htmlspecialchars($seq['SequenceKey']) ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <div id="data-container"></div>
        </div>
        <div class="side-content">
            <div id="workshop-lists">
                <!-- Workshop data will be populated here -->
            </div>
        </div>
    </div>
</main>

<script>
    function loadSequenceData(sequenceId) {
        const buttons = document.querySelectorAll('.sequence-btn');
        buttons.forEach(btn => btn.classList.remove('active'));
        event.target.classList.add('active');

        // First fetch cutlist data to know which PCISs have cutlists
        fetch(`ajax_get_workpackage_cutlists.php?sequence_id=${sequenceId}`)
            .then(response => response.json())
            .then(cutlistData => {
                // Save cutlist data globally
                savedCutlistData = cutlistData;

                // Then load sequence data
                return fetch(`get_sequence_data.php?sequence_id=${sequenceId}`);
            })
            .then(response => response.json())
            .then(data => {
                let html = '<table class="data-table">';
                html += `<thead><tr>
                <th>PCIS ID</th>
                <th>Main Mark</th>
                <th>Assembly Qty</th>
                <th>Assembly Hours Each</th>
                <th>Assembly Weight Each</th>
                <th>Total Hours</th>
                <th>Total Weight</th>
                <th>Parts to Process</th>
            </tr></thead><tbody>`;

                data.forEach(item => {
                    const totalHours = item.Quantity * item.AssemblyManHoursEach;
                    const totalWeight = item.Quantity * item.GrossAssemblyWeightEach;

                    // Calculate remaining parts for this PCIS
                    const remainingParts = savedCutlistData
                        .filter(cutlist => Number(cutlist.ProductionControlItemSequenceID) === Number(item.ProductionControlItemSequenceID))
                        .reduce((sum, cutlist) => sum + Number(cutlist.RemainingItems || 0), 0);

                    // Create parts to process cell with proper styling and click handler
                    const partsCell = remainingParts > 0
                        ? `<td class="has-cutlist" onclick="showCutlistModal(event, ${item.ProductionControlItemSequenceID}, '${item.MainMark}')">${remainingParts}</td>`
                        : `<td>0</td>`;

                    html += `<tr>
                    <td><a href="assemblies.php?pcis_id=${item.ProductionControlItemSequenceID}&workweek=<?= $workweek ?>" class="text-primary-600 hover:underline">${item.ProductionControlItemSequenceID}</a></td>
                    <td><a href="assemblies.php?pcis_id=${item.ProductionControlItemSequenceID}&workweek=<?= $workweek ?>" class="text-primary-600 hover:underline">${item.MainMark}</a></td>
                    <td>${item.Quantity}</td>
                    <td>${parseFloat(item.AssemblyManHoursEach).toFixed(3)}</td>
                    <td>${parseFloat(item.GrossAssemblyWeightEach).toFixed(2)}</td>
                    <td>${totalHours.toFixed(3)}</td>
                    <td>${totalWeight.toFixed(2)}</td>
                    ${partsCell}
                </tr>`;
                });

                html += '</tbody></table>';
                document.getElementById('data-container').innerHTML = html;
            });
    }

    // Add the modal functions
    let savedCutlistData = [];

    function organizeCutlistData(data) {
        const workshops = {};

        // Sort data by WorkPackageNumber first
        data.sort((a, b) => {
            if (a.WorkPackageNumber < b.WorkPackageNumber) return -1;
            if (a.WorkPackageNumber > b.WorkPackageNumber) return 1;
            return 0;
        });

        data.forEach(item => {
            const workshop = item.WorkShop || 'Unassigned';
            const machineGroup = item.MachineGroup || 'Unassigned';
            const wpNumber = item.WorkPackageNumber;

            if (!workshops[workshop]) {
                workshops[workshop] = {};
            }
            if (!workshops[workshop][machineGroup]) {
                workshops[workshop][machineGroup] = {};
            }
            if (!workshops[workshop][machineGroup][wpNumber]) {
                workshops[workshop][machineGroup][wpNumber] = [];
            }

            workshops[workshop][machineGroup][wpNumber].push({
                Shape: item.Shape || 'N/A',
                Size: item.DimensionSizesImperial || 'N/A',
                Remaining: item.RemainingItems || 0,
                Nest: item.NestNumber || 'N/A',
                WPID: item.WorkPackageID
            });
        });

        return workshops;
    }

    function updateSidePanelData(sequenceId) {
        fetch(`ajax_get_workpackage_cutlists.php?sequence_id=${sequenceId}`)
            .then(response => response.json())
            .then(data => {
                const organized = organizeCutlistData(data);
                let html = '';

                for (const [workshop, machineGroups] of Object.entries(organized)) {
                    html += `<div class="workshop-group">
                        <h3 class="section-title">${workshop}</h3>`;

                    for (const [machineGroup, workPackages] of Object.entries(machineGroups)) {
                        html += `<div class="machine-group">
                            <h4 class="text-sm font-bold mb-2">${machineGroup}</h4>
                            <table class="cutlist-table">
                                <thead>
                                    <tr>
                                        <th>Shape</th>
                                        <th>Size</th>
                                        <th>Rem.</th>
                                        <th>Nest</th>
                                        <th>WPID</th>
                                    </tr>
                                </thead>
                                <tbody>`;

                        for (const [wpNumber, items] of Object.entries(workPackages)) {
                            html += `<tr class="wp-header">
                                <td colspan="5">WP ${wpNumber}</td>
                            </tr>`;

                            items.forEach(item => {
                                html += `<tr>
                                    <td>${item.Shape}</td>
                                    <td>${item.Size}</td>
                                    <td>${item.Remaining}</td>
                                    <td>${item.Nest}</td>
                                    <td>${item.WPID}</td>
                                </tr>`;
                            });

                            html += `<tr class="wp-border"><td colspan="5"></td></tr>`;
                        }

                        html += `</tbody></table></div>`;
                    }
                    html += `</div>`;
                }

                document.getElementById('workshop-lists').innerHTML = html;
            });
    }

    function showCutlistModal(event, pcisId, mainMark) {
        event.preventDefault();

        const filteredData = savedCutlistData.filter(item =>
            Number(item.ProductionControlItemSequenceID) === Number(pcisId)
        );

        if (!filteredData || filteredData.length === 0) {
            return;
        }

        // Create modal if it doesn't exist
        let modal = document.getElementById('cutlistModal');
        let backdrop = document.getElementById('modalBackdrop');

        if (!modal) {
            backdrop = document.createElement('div');
            backdrop.id = 'modalBackdrop';
            backdrop.className = 'modal-backdrop';

            modal = document.createElement('div');
            modal.id = 'cutlistModal';
            modal.className = 'modal';

            backdrop.appendChild(modal);
            document.body.appendChild(backdrop);
        }

        // Generate modal content
        let html = `
        <div class="modal-header">
            <h2 class="modal-title">Cutlist Details - ${mainMark}</h2>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="cutlist-modal-content">
    `;

        // Group by workshop and machine
        const grouped = filteredData.reduce((acc, item) => {
            const workshop = item.WorkShop || 'Unassigned';
            const machineGroup = item.MachineGroup || 'Unassigned';

            if (!acc[workshop]) acc[workshop] = {};
            if (!acc[workshop][machineGroup]) acc[workshop][machineGroup] = [];

            acc[workshop][machineGroup].push(item);
            return acc;
        }, {});

        // Generate content for each group
        for (const [workshop, machineGroups] of Object.entries(grouped)) {
            html += `
            <div class="workshop-group">
                <h3 class="section-title">Workshop: ${workshop}</h3>
        `;

            for (const [machineGroup, items] of Object.entries(machineGroups)) {
                html += `
                <div class="machine-group">
                    <h4 class="font-bold mb-2">Machine Group: ${machineGroup}</h4>
                    <table class="cutlist-table-modal">
                        <thead>
                            <tr>
                                <th>Shape</th>
                                <th>Size</th>
                                <th>Total</th>
                                <th>Remaining</th>
                                <th>Completed</th>
                                <th>Nest #</th>
                                <th>Description</th>
                                <th>Machine</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

                items.forEach(item => {
                    html += `
                    <tr>
                        <td>${item.Shape || 'N/A'}</td>
                        <td>${item.DimensionSizesImperial || 'N/A'}</td>
                        <td>${item.TotalItems}</td>
                        <td>${item.RemainingItems}</td>
                        <td>${item.CompletedItems}</td>
                        <td>${item.NestNumber || 'N/A'}</td>
                        <td>${item.CutlistDescription || ''}</td>
                        <td>${item.Machine || ''}</td>
                    </tr>
                `;
                });

                html += `
                        </tbody>
                    </table>
                </div>
            `;
            }

            html += `</div>`;
        }

        html += `</div>`;
        modal.innerHTML = html;
        openModal();
    }

    function openModal() {
        const backdrop = document.getElementById('modalBackdrop');
        if (backdrop) {
            backdrop.style.display = 'block';
            document.body.style.overflow = 'hidden';

            // Add ESC key listener
            document.addEventListener('keydown', handleEscKey);
        }
    }

    function closeModal() {
        const backdrop = document.getElementById('modalBackdrop');
        if (backdrop) {
            backdrop.style.display = 'none';
            document.body.style.overflow = 'auto';

            // Remove ESC key listener
            document.removeEventListener('keydown', handleEscKey);
        }
    }

    function handleEscKey(event) {
        if (event.key === 'Escape') {
            closeModal();
        }
    }

    // Load first sequence and cutlist data by default
    document.querySelector('.sequence-btn')?.click();
</script>
</body>
</html>