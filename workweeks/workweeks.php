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
</head>
<body>
<header class="header">
    <div class="header-content">
        <img src="../images/ssf-horiz.png" alt="Logo" class="logo">
    </div>
</header>

<main class="main-container">
    <div class="breadcrumbs">
        <a href="workweeks.php">Workweeks</a>
    </div>
    <div id="summary-cards" class="summary-cards"></div>

    <div class="sequence-buttons">
        <?php foreach ($weeks as $week): ?>
            <button class="sequence-btn <?= ($week == $workweek) ? 'active' : '' ?>"
                    onclick="loadProjectData('<?= $week ?>')"><?= $week ?></button>
        <?php endforeach; ?>
    </div>

    <div class="loading">Loading data...</div>

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
            </tr>
            </thead>
            <tbody id="data-body">
            <!-- Data will be loaded here -->
            </tbody>
        </table>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        loadProjectData('<?= $workweek ?>');

        document.querySelectorAll('.sequence-btn').forEach(button => {
            button.addEventListener('click', function() {
                document.querySelectorAll('.sequence-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                this.classList.add('active');
            });
        });
    });

    function loadProjectData(week) {
        const loading = document.querySelector('.loading');
        const dataBody = document.getElementById('data-body');

        loading.style.display = 'block';
        dataBody.innerHTML = '';

        fetch(`ajax_get_workweek_workpackages.php?workweek=${week}`)
            .then(response => response.json())
            .then(data => {
                dataBody.innerHTML = data.map(wp => {
                    const totalHoursLeft = Number(wp.CutHoursLeft || 0) +
                        Number(wp.FitHoursLeft || 0) +
                        Number(wp.WeldHoursLeft || 0) +
                        Number(wp.FinalQCHoursLeft || 0);

                    const quantityLeft = Number(wp.FinalQCQtyLeft || 0);
                    const weightLeft = Number(wp.FinalQCWeightLeft || 0);

                    return `
                    <tr>
                        <td>${wp.WorkPackageID}</td>
                        <td>${wp.ProductionControlID}</td>
                        <td><a href="workpackages.php?workweek=${week}&wp_id=${wp.WorkPackageID}">${wp.WorkPackageNumber}</a></td>
                        <td>${wp.ReleasedToFab ? 'Yes' : 'No'}</td>
                        <td>${wp.WPAssemblyQuantity}</td>
                        <td class="wp-qty-left">${quantityLeft}</td>
                        <td>${wp.WPGrossWeight}</td>
                        <td class="wp-weight-left">${weightLeft.toFixed(2)}</td>
                        <td>${wp.WPHours}</td>
                        <td class="wp-hours-left">${totalHoursLeft.toFixed(2)}</td>
                    </tr>`;
                }).join('');
            })
            .catch(error => {
                console.error('Error loading data:', error);
                dataBody.innerHTML = '<tr><td colspan="10">Error loading data</td></tr>';
            })
            .finally(() => {
                loading.style.display = 'none';
                updateSummaries();
            });
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
</script>
</body>
</html>