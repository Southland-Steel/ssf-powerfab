<?php
// view_grid_workpackage_assembly_station_status.php

$currentYear = substr(date('o'), -2);
$currentWeek = date('W');
$currentWorkweek = intval($currentYear . str_pad($currentWeek, 2, '0', STR_PAD_LEFT));

$workweek = $_GET['workweek'] ?? $currentWorkweek;

require_once 'config_ssf_db.php';

$query = "
    SELECT DISTINCT Group2 as WorkWeeks 
    FROM workpackages 
    INNER JOIN productioncontroljobs as pcj 
        ON pcj.productionControlID = workpackages.productionControlID 
    WHERE Completed = 0 AND OnHold = 0 AND Group2 is not null
    ORDER BY WorkWeeks ASC
";

$stmt = $db->prepare($query);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

$weeks = array_column($results, 'WorkWeeks');

sort($weeks);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="format-detection" content="telephone=no">
    <title>Production Scheduler</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/workweeks.css?v=<?php echo time(); ?>" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #0d6efd;
            --light-blue: #e7f1ff;
        }

        .logo-container img {
            max-height: 40px;
            margin: 5px;
        }
        #big-text {
            position: fixed;
            top: -10px;
            right: 10px;
            font-size: 3rem;
            color: rgba(0, 0, 0, 0.3);
            z-index: 1000;
        }

    </style>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
</head>
<body>
<div id="big-text"><?= $workweek; ?></div>

<div class="container-fluid">
    <div>
        <div class="row">
            <div class="col">
                <div class="logo-container">
                    <img src="images/ssf-horiz.png" alt="Southland Steel Fabricators" class="img-fluid">
                </div>
            </div>
        </div>

        <div class="week-selector">
            <div id="weekContainer" class="d-flex">
                <!-- Week buttons will be dynamically inserted here -->
            </div>
            
            <!-- Add id to WorkPackages container -->
            <div id="workPackagesContainer" class="wp">
                <!-- WorkPackages content will be inserted here -->
            </div>
        </div>
    </div>

    <div class="table-container">
        <table id="projectTable" class="table table-hover">
            <thead>
            <tr>
                <th>Job<br>Route</th>
                <th>SeqLot<br>Main</th>
                <th>WP</th>
                <th>Asm. Qty</th>
                <th>Net # Each / Total</th>
                <th>Hrs. Each / Total</th>
                <!-- Dynamically generated column station headers here -->
            </tr>
            </thead>
            <tbody>
            <!-- assembly data will be loaded here -->
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="piecemarkModal" tabindex="-1" aria-labelledby="piecemarkModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="piecemarkModalLabel">Piecemark Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <table class="table table-striped" id="piecemarkTable">
                    <thead>
                    <tr>
                        <th>Piecemark</th>
                        <th>Job Quantity</th>
                        <th>Assembly Each Quantity</th>
                        <th>WP Quantity</th>
                        <th>Completed Quantity</th>
                    </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/WorkWeeks.js?v=<?php echo time(); ?>"></script>
<script src="js/WorkPackages.js?v=<?php echo time(); ?>"></script>
<script src="js/WorkPackageData.js?v=<?php echo time(); ?>"></script>
<script src="js/WorkPackageDisplay.js?v=<?php echo time(); ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Get weeks from PHP
    const weeks = <?= json_encode($weeks); ?>;
    
    // Initialize all classes
    const workWeeks = new WorkWeeks('#weekContainer', weeks);
    const workPackages = new WorkPackages('#workPackagesContainer');  // Use the existing tag name
    const workPackageData = new WorkPackageData();
    // Pass both container and table selectors
    const workPackageDisplay = new WorkPackageDisplay('#workPackagesContainer', '#projectTable');
    
    // Give the display class access to the data class for calculations
    workPackageDisplay.setWorkPackageData(workPackageData);
    
    // Set up event chain
    workWeeks.setWeekSelectedCallback((weekNumber) => {
        // When week is selected, load work packages
        workPackages.loadWorkPackageData(weekNumber);
        workPackageDisplay.showLoading(); // Make sure table exists before calling
    });
    
    workPackages.setWorkPackageSelectedCallback((wpNumber, wpId) => {
        // When work package is selected:
        // 1. Render the work package detail
        const selectedWP = workPackages.getSelectedWorkPackage();
        if (selectedWP) {
            workPackageDisplay.renderWorkPackageDetail(selectedWP);
            workPackageDisplay.showLoading();
            workPackageData.loadData(wpId);
        }
    });
    
    workPackageData.setDataLoadedCallback((assemblyData, totals) => {
        if (assemblyData && totals) {
            workPackageDisplay.renderAssemblyTable(
                assemblyData, 
                workPackageData.getOrderedStations()
            );
            workPackageDisplay.renderWorkPackageStatistics(totals);
        }
    });
    
    workPackageData.setErrorCallback((error) => {
        workPackageDisplay.showError(
            'Error loading work package data. Please try again.'
        );
    });
    
    // Select the current week by default
    const currentWeek = '<?= $workweek ?>';
    workWeeks.selectWeek(currentWeek);
});
</script>
</body>
</html>