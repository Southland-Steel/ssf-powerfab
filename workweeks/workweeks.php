<?php
$currentYear = substr(date('o'), -2);
$currentWeek = date('W');
$currentWorkweek = intval($currentYear . str_pad($currentWeek, 2, '0', STR_PAD_LEFT));
$workweek = $_GET['workweek'] ?? $currentWorkweek;

require_once '../config_ssf_db.php';

$resources = $db->query("
    SELECT DISTINCT Group2 as WorkWeeks 
    FROM workpackages 
    INNER JOIN productioncontroljobs as pcj 
    ON pcj.productionControlID = workpackages.productionControlID 
    WHERE Completed = 0 AND OnHold = 0 
    ORDER BY WorkWeeks ASC;
")->fetchAll(PDO::FETCH_ASSOC);

$weeks = array_filter(array_column($resources, 'WorkWeeks'), function($week) {
    return $week !== null && $week !== '';
});
sort($weeks);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Work Package Data</title>
    <link rel="stylesheet" href="workweeks.css?v=<?= time() ?>">
    <style>
        .workshop-group {
            margin-bottom: 1.5rem;
        }

        .machine-group {
            margin-bottom: 1rem;
            padding: 0.5rem;
            background: #f9fafb;
            border-radius: 0.375rem;
        }

        .mb-2 {
            margin-bottom: 0.5rem;
        }

        .cutlist-table td, .cutlist-table th {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        .wp-header {
            font-weight: 500;
            color: #4b5563;
            background-color: #e5e7eb;
        }

        .wp-header td {
            padding: 0.25rem 0.5rem;
        }

        .wp-border td {
            padding: 0;
            height: 1px;
            border-bottom: 2px solid #e5e7eb;
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
        <?php if (isset($workweek)): ?>
            <span class="separator">/</span>
            <span>Week <?= htmlspecialchars($workweek) ?></span>
        <?php endif; ?>
    </div>

    <div class="content-wrapper">
        <div class="main-content">
            <div id="summary-cards" class="summary-cards"></div>

            <div class="sequence-buttons">
                <?php foreach ($weeks as $week): ?>
                    <button class="sequence-btn <?= ($week == $workweek) ? 'active' : '' ?>"
                            onclick="loadProjectData('<?= $week ?>')"><?= $week ?></button>
                <?php endforeach; ?>
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                    <tr>
                        <th>WP ID</th>
                        <th>PC ID</th>
                        <th>WP Number</th>
                        <th>Released</th>
                        <th>Quantity</th>
                        <th>Quantity Left</th>
                        <th>Weight</th>
                        <th>Weight Left</th>
                        <th>Hours</th>
                        <th>Hours Left</th>
                        <th>Parts Left to Cut</th>
                    </tr>
                    </thead>
                    <tbody id="data-body">
                    <!-- Data will be loaded here -->
                    </tbody>
                </table>
            </div>
        </div>
        <div class="side-content">
            <h2 class="section-title">Remaining Cut Lists</h2>
            <p class="text-sm text-gray-600 mb-4">Shows outstanding parts that still need to be cut, organized by workshop and machine group.</p>
            <div id="workshop-lists">
                <!-- Workshop data will be populated here -->
            </div>
        </div>
    </div>
</main>
<div class="modal-backdrop" id="modalBackdrop">
    <div class="modal" id="cutlistModal">
        <div class="modal-header">
            <h2 class="modal-title">Work Package Cutlist Details</h2>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="cutlist-modal-content" id="modalContent">
        </div>
    </div>
</div>

<script>
    let workpackagesWithCutlists = new Set();

    function debugModal(action, workpackageId, workpackageNumber) {
        console.log(`Modal ${action}:`, {
            workpackageId,
            workpackageNumber,
            savedCutlistData: savedCutlistData.length,
            modalDisplay: document.getElementById('cutlistModal').style.display,
            backdropDisplay: document.getElementById('modalBackdrop').style.display
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        const workweek = '<?= $workweek ?>';
        loadProjectData(workweek);
        updateSidePanelData(workweek);

        document.querySelectorAll('.sequence-btn').forEach(button => {
            button.addEventListener('click', function() {
                document.querySelectorAll('.sequence-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                this.classList.add('active');
            });
        });
        const backdrop = document.getElementById('modalBackdrop');
        if (backdrop) {
            backdrop.addEventListener('click', function(event) {
                if (event.target === this) {
                    closeModal();
                }
            });
        }
    });

    function loadProjectData(week) {
        const dataBody = document.getElementById('data-body');

        // First, fetch cutlist data to know which workpackages have cutlists
        fetch(`ajax_get_workpackage_cutlists.php?workweek=${week}`)
            .then(response => response.json())
            .then(cutlistData => {
                savedCutlistData = cutlistData; // Save the cutlist data globally
                workpackagesWithCutlists = new Set(
                    cutlistData.map(item => item.WorkPackageID)
                );

                // Then load workpackage data
                return fetch(`ajax_get_workweek_workpackages.php?workweek=${week}`);
            })
            .then(response => response.json())
            .then(data => {
                dataBody.innerHTML = data.map(wp => {
                    const hasCutlist = workpackagesWithCutlists.has(wp.WorkPackageID);
                    const totalRemaining = hasCutlist ? getTotalRemaining(savedCutlistData, wp.WorkPackageID) : 0;

                    const totalHoursLeft = Number(wp.CutHoursLeft || 0) +
                        Number(wp.FitHoursLeft || 0) +
                        Number(wp.WeldHoursLeft || 0) +
                        Number(wp.FinalQCHoursLeft || 0);

                    const quantityLeft = Number(wp.FinalQCQtyLeft || 0);
                    const weightLeft = Number(wp.FinalQCWeightLeft || 0);

                    // Create the parts to process cell with proper styling and click handler
                    const partsCell = totalRemaining > 0
                        ? `<td class="has-cutlist" onclick="showCutlistModal(event, ${wp.WorkPackageID}, '${wp.WorkPackageNumber}')">${totalRemaining}</td>`
                        : `<td>0</td>`;

                    return `
                <tr>
                    <td>${wp.WorkPackageID}</td>
                    <td>${wp.ProductionControlID}</td>
                    <td><a href="workpackages.php?wp_id=${wp.WorkPackageID}&workweek=${week}" class="text-primary-600 hover:underline">${wp.WorkPackageNumber}</a></td>
                    <td>${wp.ReleasedToFab ? 'Yes' : 'No'}</td>
                    <td>${wp.WPAssemblyQuantity}</td>
                    <td class="wp-qty-left">${quantityLeft}</td>
                    <td>${wp.WPGrossWeight}</td>
                    <td class="wp-weight-left">${weightLeft.toFixed(2)}</td>
                    <td>${wp.WPHours}</td>
                    <td class="wp-hours-left">${totalHoursLeft.toFixed(2)}</td>
                    ${partsCell}
                </tr>`;
                }).join('');
            })
            .catch(error => {
                console.error('Error loading data:', error);
                dataBody.innerHTML = '<tr><td colspan="11">Error loading data</td></tr>';
            })
            .finally(() => {
                updateSummaries();
            });
    }

    function getTotalRemaining(cutlistData, workpackageId) {
        return cutlistData
            .filter(item => Number(item.WorkPackageID) === Number(workpackageId))
            .reduce((sum, item) => sum + Number(item.RemainingItems || 0), 0);
    }

    function updateSummaries() {
            const rows = document.querySelectorAll('#data-body tr');
            let totalQty = 0;
            let totalWeight = 0;
            let totalHours = 0;

            rows.forEach(row => {
                totalQty += Number(row.querySelector('td:nth-child(5)').textContent || 0); // WPAssemblyQuantity
                totalWeight += Number(row.querySelector('td:nth-child(7)').textContent || 0); // WPGrossWeight
                totalHours += Number(row.querySelector('td:nth-child(9)').textContent || 0); // WPHours
            });

            document.getElementById('summary-cards').innerHTML = `
           <div class="summary-card">
               <div class="summary-card-title">Total Parts</div>
               <div class="summary-card-value">${totalQty}</div>
           </div>
           <div class="summary-card">
               <div class="summary-card-title">Total Weight (lbs)</div>
               <div class="summary-card-value">${totalWeight.toFixed(2)}</div>
           </div>
           <div class="summary-card">
               <div class="summary-card-title">Total Hours</div>
               <div class="summary-card-value">${totalHours.toFixed(2)}</div>
           </div>
       `;
    }

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

    let savedCutlistData = [];

    function updateSidePanelData(week) {
        fetch(`ajax_get_workpackage_cutlists.php?workweek=${week}`)
            .then(response => response.json())
            .then(data => {
                savedCutlistData = data;

                const organized = organizeCutlistData(data);
                let html = '';

                for (const [workshop, machineGroups] of Object.entries(organized)) {
                    html += `<div class="workshop-group">
                    <h3 class="section-title">Workshop: ${workshop}</h3>`;

                    for (const [machineGroup, workPackages] of Object.entries(machineGroups)) {
                        html += `<div class="machine-group">
                        <h4 class="text-sm font-bold mb-2">Machine Group: ${machineGroup}</h4>
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

                            // Add an empty bordered row after each workpackage
                            html += `<tr class="wp-border"><td colspan="5"></td></tr>`;
                        }

                        html += `</tbody></table></div>`;
                    }
                    html += `</div>`;
                }

                document.getElementById('workshop-lists').innerHTML = html;
            });
    }

    function showCutlistModal(event, workpackageId, workpackageNumber) {
        event.preventDefault();
        debugModal('opening', workpackageId, workpackageNumber);

        const modalTitle = document.querySelector('.modal-title');
        const modalContent = document.getElementById('modalContent');
        const modal = document.getElementById('cutlistModal');
        const backdrop = document.getElementById('modalBackdrop');

        if (!modalTitle || !modalContent || !modal || !backdrop) {
            console.error('Modal elements not found');
            return;
        }

        modalTitle.textContent = `Cutlist Details - WP ${workpackageNumber}`;

        // Convert workpackageId to number for comparison
        const wpId = Number(workpackageId);

        // Debug the types to verify the comparison
        console.log('Types:', {
            passedId: typeof workpackageId,
            convertedId: typeof wpId,
            sampleSavedId: typeof savedCutlistData[0]?.WorkPackageID
        });

        // Filter with numerical comparison
        const filteredData = savedCutlistData.filter(item => Number(item.WorkPackageID) === wpId);

        console.log('Filtered data:', {
            workpackageId: wpId,
            totalRecords: savedCutlistData.length,
            matchingRecords: filteredData.length
        });

        if (!filteredData || filteredData.length === 0) {
            modalContent.innerHTML = `
            <div class="error">
                No cutlist data found for WorkPackage ID: ${workpackageId}<br>
                (Total records available: ${savedCutlistData.length})
            </div>`;
            openModal();
            return;
        }

        // Generate the modal content HTML as before...
        let html = '';

        // Group by workshop and machine
        const grouped = filteredData.reduce((acc, item) => {
            const workshop = item.WorkShop || 'Unassigned';
            const machineGroup = item.MachineGroup || 'Unassigned';

            if (!acc[workshop]) acc[workshop] = {};
            if (!acc[workshop][machineGroup]) acc[workshop][machineGroup] = [];

            acc[workshop][machineGroup].push(item);
            return acc;
        }, {});

        // Generate HTML for grouped data
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

        modalContent.innerHTML = html;
        openModal();
    }

    function openModal() {
        const backdrop = document.getElementById('modalBackdrop');
        const modal = document.getElementById('cutlistModal');

        if (backdrop && modal) {
            backdrop.style.display = 'block';
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';

            // Add ESC key listener
            document.addEventListener('keydown', handleEscKey);
        } else {
            console.error('Modal elements not found during open');
        }
    }

    function closeModal() {
        const backdrop = document.getElementById('modalBackdrop');
        const modal = document.getElementById('cutlistModal');

        if (backdrop && modal) {
            backdrop.style.display = 'none';
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';

            // Remove ESC key listener
            document.removeEventListener('keydown', handleEscKey);
        } else {
            console.error('Modal elements not found during close');
        }
    }

    // Add ESC key handler
    function handleEscKey(event) {
        if (event.key === 'Escape') {
            closeModal();
        }
    }
</script>
</body>
</html>