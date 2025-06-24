<?php

require_once '../config_ssf_db.php';

$error_message = '';
$connection_status = '';

$query_date =  (isset($_GET['query_date'])) ? $_GET['query_date'] : '2025-06-10';

try{
    $pdo = $db;

    $sql = "SELECT
        pcis.ProductionControlItemStationID,
        replace(pcis.MainMark, char(1), '') as MainMark,
        replace(pcis.PieceMark, char(1), '') as PieceMark,
        pcj.JobNumber,
        replace(pcseq.Description, char(1), '') as SequenceName, 
        replace(pcseq.LotNumber, char(1), '') as LotNumber,
        pcseq.WorkPackageID,
        wp.WorkPackageNumber,
        wp.Group2 as WorkWeek,
        stations.Description as StationName,
        pcis.Quantity as QuantityCompleted,
        users.Username,
        pcis.DateCompleted,
        case 
            when pcis.PieceMark is NULL then 
                1
            else 
                (select pci.MainPiece 
                from fabrication.productioncontrolitems as pci 
                where pci.MainMark = pcis.MainMark and pci.PieceMark = pcis.PieceMark and pci.ProductionControlID = pcis.ProductionControlID
                order by pci.ProductionControlItemID
                limit 1)
        end as MainPiece,
        case 
            when pcis.PieceMark is NULL then 
                (select pca.AssemblyManHoursEach * pcis.Quantity
                from fabrication.productioncontrolitems as pci 
                inner join fabrication.productioncontrolassemblies as pca on pca.ProductionControlAssemblyID = pci.ProductionControlAssemblyID
                where pci.MainMark = pcis.MainMark and pci.ProductionControlID = pcis.ProductionControlID
                order by pci.ProductionControlItemID
                limit 1)
            else 
                (select pca.AssemblyManHoursEach * pcis.Quantity
                from fabrication.productioncontrolitems as pci 
                inner join fabrication.productioncontrolassemblies as pca on pca.ProductionControlAssemblyID = pci.ProductionControlAssemblyID
                where pci.MainMark = pcis.MainMark and pci.PieceMark = pcis.PieceMark and pci.ProductionControlID = pcis.ProductionControlID
                order by pci.ProductionControlItemID
                limit 1)
        end as AssemblyManHours,
        case 
            when pcis.PieceMark is NULL then 
                (select fabrication.routes.Route as RouteName
                from fabrication.productioncontrolassemblies as pca1 
                inner join fabrication.productioncontrolitems as pci1 on pci1.ProductionControlItemID = pca1.MainPieceProductionControlItemID
                left join fabrication.routes on routes.RouteID = pci1.RouteID
                where pca1.MainMark = pcis.MainMark and pca1.ProductionControlID = pcis.ProductionControlID)
            else 
                (select fabrication.routes.Route as RouteName
                from fabrication.productioncontrolitems as pci 
                left join fabrication.routes on routes.RouteID = pci.RouteID
                where pci.MainMark = pcis.MainMark and pci.PieceMark = pcis.PieceMark and pci.ProductionControlID = pcis.ProductionControlID
                order by pci.ProductionControlItemID)
        end as RouteName
        FROM fabrication.productioncontrolitemstations as pcis
        inner join fabrication.productioncontroljobs as pcj on pcj.ProductionControlID = pcis.ProductionControlID
        inner join fabrication.productioncontrolsequences as pcseq on pcis.SequenceID = pcseq.SequenceID
        left join fabrication.workpackages as wp on wp.WorkPackageID = pcseq.WorkPackageID
        inner join fabrication.stations on stations.StationID = pcis.StationID
        inner join fabrication.users on users.UserID = pcis.UserID
        where pcis.DateCompleted = :query_date and stations.Description = 'final qc'
        order by AssemblyManHours desc";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':query_date', $query_date);

    //$stmt->execute();
    //$info = $stmt->fetchAll(PDO::FETCH_ASSOC);
    //var_dump($stmt);

    $stmt->execute();
    $sum = 0;
    $info = [];
    while($tmp = $stmt->fetch(PDO::FETCH_ASSOC)){
        $routeName = $tmp['RouteName'];
        if($routeName == 'BO'){
            $newHours = ($tmp['AssemblyManHours'] * 0.8);
        }
        else{
            $newHours = ($tmp['AssemblyManHours'] * 0.4);
        }
        $tmp['AssemblyManHours'] = $newHours;
        $info[] = $tmp;
        $sum += $newHours;
    }

}catch(PDOException $e) {
    var_dump($e->getMessage());
}
/* header('Content-Type: application/json');
echo json_encode($info);*/

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Final QC Man Hours Table</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .bg-gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        }

        .bg-gradient-success {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%) !important;
        }

        .table-container-custom {
            max-height: 80vh;
            overflow-y: auto;
        }

        .table th {
            position: sticky;
            top: 0;
            z-index: 10;
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%) !important;
            color: white !important;
            border-right: 1px solid rgba(255, 255, 255, 0.2) !important;
        }

        .table th:last-child {
            border-right: none !important;
        }

        .table tbody tr:hover {
            background-color: #e3f2fd !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .manhours-cell {
            color: #2196F3 !important;
        }

        .sort-toggle {
            border: none;
            border-radius: 25px;
            padding: 4px 4px;
            font-weight: bold;
            font-size: 14px;
            transition: all 0.3s ease;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .sort-toggle:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .sort-toggle.active {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%) !important;
            color: white !important;
        }

        .sort-toggle.inactive {
            background: #f8f9fa !important;
            color: #6c757d !important;
        }
    </style>
</head>
<body class="bg-light">
<div class="container-fluid px-1 py-1">
    <div class="card border-0 shadow mb-2">
        <div class="card-header bg-gradient-primary text-white text-center py-2 px-2">
            <h1 class="h4 mb-1">Final QC Details for SSF <?= $query_date ?></h1>
            <p class="small mb-0 opacity-75">Displaying <?= count($info).' Items and '.number_format($sum,2);?> Earned Man Hours Total</p>
        </div>
    </div>
    <div class="card border-0 shadow mb-2">
        <div class="card-body py-2 px-3">
            <div class="d-flex align-items-center justify-content-center">
                <span class="me-3 fw-bold text-muted small">SORT BY:</span>
                <button id="sortByManHours" class="sort-toggle active me-2" onclick="sortTable('manhours')">
                    MAN HOURS
                </button>
                <button id="sortByJobNumber" class="sort-toggle inactive" onclick="sortTable('jobnumber')">
                    JOB NUMBER
                </button>
            </div>
        </div>
    </div>
    <div class="card border-0 shadow">
        <div class="card-body p-1">
            <div class="table-container-custom border rounded shadow-sm">
                <table class="table table-striped table-hover table-sm mb-0" id="dataTable">
                    <thead class="bg-gradient-success">
                    <tr>
                        <th class="text-center fw-bold text-uppercase small py-2 px-1">Station ID</th>
                        <th class="text-center fw-bold text-uppercase small py-2 px-1">Route Name</th>
                        <th class="text-center fw-bold text-uppercase small py-2 px-1">Main Mark</th>
                        <th class="text-center fw-bold text-uppercase small py-2 px-1">Piece Mark</th>
                        <th class="text-center fw-bold text-uppercase small py-2 px-1">Job Number</th>
                        <th class="text-center fw-bold text-uppercase small py-2 px-1">Sequence Name</th>
                        <th class="text-center fw-bold text-uppercase small py-2 px-1">Lot Number</th>
                        <th class="text-center fw-bold text-uppercase small py-2 px-1">Work Package Number</th>
                        <th class="text-center fw-bold text-uppercase small py-2 px-1">Work Week</th>
                        <th class="text-center fw-bold text-uppercase small py-2 px-1">Station Name</th>
                        <th class="text-center fw-bold text-uppercase small py-2 px-1">Quantity Completed</th>
                        <th class="text-center fw-bold text-uppercase small py-2 px-1">Username</th>
                        <th class="text-center fw-bold text-uppercase small py-2 px-1">Date Completed</th>
                        <th class="text-center fw-bold text-uppercase small py-2 px-1">Main Piece</th>
                        <th class="text-center fw-bold text-uppercase small py-2 px-1">Man Hours</th>
                    </tr>
                    </thead>
                    <tbody id="tableBody">
                    <?php foreach($info as $row): ?>
                        <tr data-manhours="<?= round($row['AssemblyManHours'],2)?>" data-jobnumber="<?= $row['JobNumber']?>">
                            <td class="text-center py-1 px-1"><?= $row['ProductionControlItemStationID']?></td>
                            <td class="text-center py-1 px-1"><?= $row['RouteName']?></td>
                            <td class="text-center py-1 px-1"><?= $row['MainMark']?></td>
                            <td class="text-center py-1 px-1"><?= $row['PieceMark'] == null ? '*'.$row['MainMark']:$row['PieceMark']?></td>
                            <td class="text-center py-1 px-1"><?= $row['JobNumber']?></td>
                            <td class="text-center py-1 px-1"><?= $row['SequenceName']?></td>
                            <td class="text-center py-1 px-1"><?= $row['LotNumber']?></td>
                            <td class="text-center py-1 px-1"><?= $row['WorkPackageNumber']?></td>
                            <td class="text-center py-1 px-1"><?= $row['WorkWeek']?></td>
                            <td class="text-center py-1 px-1"><?= $row['StationName']?></td>
                            <td class="text-center py-1 px-1"><?= $row['QuantityCompleted']?></td>
                            <td class="text-center py-1 px-1"><?= $row['Username']?></td>
                            <td class="text-center py-1 px-1"><?= $row['DateCompleted']?></td>
                            <td class="text-center py-1 px-1"><?= $row['MainPiece']?></td>
                            <td class="text-center py-1 px-1 fw-bold manhours-cell"><?= round($row['AssemblyManHours'],2)?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function sortTable(sortBy) {
        const tableBody = document.getElementById('tableBody');
        const rows = Array.from(tableBody.querySelectorAll('tr'));

        const manHoursBtn = document.getElementById('sortByManHours');
        const jobNumberBtn = document.getElementById('sortByJobNumber');

        if (sortBy === 'manhours') {
            manHoursBtn.className = 'sort-toggle active me-2';
            jobNumberBtn.className = 'sort-toggle inactive';

            rows.sort((a, b) => {
                const aValue = parseFloat(a.getAttribute('data-manhours'));
                const bValue = parseFloat(b.getAttribute('data-manhours'));
                return bValue - aValue;
            });
        } else {
            manHoursBtn.className = 'sort-toggle inactive me-2';
            jobNumberBtn.className = 'sort-toggle active';

            rows.sort((a, b) => {
                const aValue = a.getAttribute('data-jobnumber');
                const bValue = b.getAttribute('data-jobnumber');
                return aValue.localeCompare(bValue, undefined, {numeric: true});
            });
        }

        tableBody.innerHTML = '';
        rows.forEach(row => tableBody.appendChild(row));
    }
</script>
</body>
</html>