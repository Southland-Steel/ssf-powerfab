<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config_ssf_db.php';

$default_days = 30;

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;

if (!$start_date || !$end_date) {
    $end_date = date('Y-m-d');
    $start_date = date('Y-m-d', strtotime("-{$default_days} days"));
}

$response = [
    'success' => false,
    'message' => '',
    'data' => [
        'cut_data' => [],
        'fit_data' => [],
        'finalqc_data' => [],
        'all_dates' => [],
        'statistics' => [],
        'export_data' => []
    ]
];

try {

    $pdo = $db; // rename db to pdo from config

    $cut_data = [];
    $fit_data = [];
    $finalqc_data = [];
    $all_dates = [];

    $cut_query = "select pcis.DateCompleted,
    sum(case 
        when pcis.PieceMark is NULL then 
            (select pci.ManHours * pcis.Quantity
             from fabrication.productioncontrolassemblies as pca1 
            inner join fabrication.productioncontrolitems as pci on pci.ProductionControlItemID = pca1.MainPieceProductionControlItemID
            where pca1.MainMark = pcis.MainMark and pca1.ProductionControlID = pcis.ProductionControlID)
        else 
            (select pci.ManHours * pcis.Quantity
            from fabrication.productioncontrolitems as pci 
            where pci.MainMark = pcis.MainMark and pci.PieceMark = pcis.PieceMark and pci.ProductionControlID = pcis.ProductionControlID)
    end) as ManHours
    from fabrication.productioncontrolitemstations as pcis 
    inner join fabrication.stations on stations.StationID = pcis.StationID
    inner join fabrication.productioncontrolsequences as pcseq on pcis.SequenceID = pcseq.SequenceID
    left join fabrication.workpackages as wp on wp.WorkPackageID = pcseq.WorkPackageID
    where STR_TO_DATE(pcis.DateCompleted, '%Y-%m-%d') BETWEEN :begin_date AND :end_date 
    and stations.Description = 'cut' and pcis.DateCompleted IS NOT NULL and wp.WorkshopID = 1
    group by pcis.DateCompleted
    order by pcis.DateCompleted desc";

    $cut_stmt = $pdo->prepare($cut_query);
    $cut_stmt->bindParam(':begin_date', $start_date);
    $cut_stmt->bindParam(':end_date', $end_date);
    $cut_stmt->execute();

    while($tmp = $cut_stmt->fetch(PDO::FETCH_ASSOC)){
        $newHours = ($tmp['ManHours'] * 0.2);
        $cut_data[$tmp['DateCompleted']] = $newHours;
        $all_dates[] = $tmp['DateCompleted'];
    }

    $fit_query = "select pcis.DateCompleted,
    sum(case 
        when pcis.PieceMark is NULL then 
            (select pca.AssemblyManHoursEach * pcis.Quantity from fabrication.productioncontrolassemblies as pca 
            inner join fabrication.productioncontrolitems as pci on pci.ProductionControlItemID = pca.MainPieceProductionControlItemID
            where pca.MainMark = pcis.MainMark and pca.ProductionControlID = pcis.ProductionControlID)
        else 
            (select pca.AssemblyManHoursEach * pcis.Quantity
            from fabrication.productioncontrolassemblies as pca 
            inner join fabrication.productioncontrolitems as pci on pci.ProductionControlItemID = pca.MainPieceProductionControlItemID
            where pci.MainMark = pcis.MainMark and pci.PieceMark = pcis.PieceMark and pci.ProductionControlID = pcis.ProductionControlID)
    end) as AssemblyManHours
    from fabrication.productioncontrolitemstations as pcis 
    inner join fabrication.stations on stations.StationID = pcis.StationID
    inner join fabrication.productioncontrolsequences as pcseq on pcis.SequenceID = pcseq.SequenceID
    left join fabrication.workpackages as wp on wp.WorkPackageID = pcseq.WorkPackageID
    where STR_TO_DATE(pcis.DateCompleted, '%Y-%m-%d') BETWEEN :begin_date AND :end_date 
    and stations.Description = 'fit' and pcis.DateCompleted IS NOT NULL and wp.WorkshopID = 1
    group by pcis.DateCompleted
    order by pcis.DateCompleted desc";

    $fit_stmt = $pdo->prepare($fit_query);
    $fit_stmt->bindParam(':begin_date', $start_date);
    $fit_stmt->bindParam(':end_date', $end_date);
    $fit_stmt->execute();

    while($tmp = $fit_stmt->fetch(PDO::FETCH_ASSOC)){
        $newHours = ($tmp['AssemblyManHours'] * 0.4);
        $fit_data[$tmp['DateCompleted']] = $newHours;
        if (!in_array($tmp['DateCompleted'], $all_dates)) {
            $all_dates[] = $tmp['DateCompleted'];
        }
    }

    $finalqc_query = "SELECT
        pcis.DateCompleted,
        case 
            when pcis.PieceMark is NULL then 
                (select pca1.AssemblyManHoursEach * pcis.Quantity from fabrication.productioncontrolassemblies as pca1 
                inner join fabrication.productioncontrolitems as pci on pci.ProductionControlItemID = pca1.MainPieceProductionControlItemID
                where pca1.MainMark = pcis.MainMark and pca1.ProductionControlID = pcis.ProductionControlID)
            else 
                (select pca.AssemblyManHoursEach * pcis.Quantity
                from fabrication.productioncontrolitems as pci 
                inner join fabrication.productioncontrolassemblies as pca on pca.ProductionControlAssemblyID = pci.ProductionControlAssemblyID
                where pci.MainMark = pcis.MainMark and pci.PieceMark = pcis.PieceMark and pci.ProductionControlID = pcis.ProductionControlID)
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
        end as AssemblyRouteName
        from fabrication.productioncontrolitemstations as pcis
        inner join fabrication.stations on stations.StationID = pcis.StationID
        inner join fabrication.productioncontrolsequences as pcseq on pcis.SequenceID = pcseq.SequenceID
        left join fabrication.workpackages as wp on wp.WorkPackageID = pcseq.WorkPackageID
        where STR_TO_DATE(pcis.DateCompleted, '%Y-%m-%d') BETWEEN :begin_date AND :end_date 
        and stations.Description = 'final qc' and pcis.DateCompleted IS NOT NULL and wp.WorkshopID = 1
        order by pcis.DateCompleted desc";

    $finalqc_stmt = $pdo->prepare($finalqc_query);
    $finalqc_stmt->bindParam(':begin_date', $start_date);
    $finalqc_stmt->bindParam(':end_date', $end_date);
    $finalqc_stmt->execute();

    $date_totals = [];
    while($tmp = $finalqc_stmt->fetch(PDO::FETCH_ASSOC)){
        $date = $tmp['DateCompleted'];
        $routeName = $tmp['AssemblyRouteName'];

        if($routeName == 'BO'){
            $newHours = ($tmp['AssemblyManHours'] * 0.8);
        }
        elseif($routeName == 'SHIP LOOSE'){
            $newHours = ($tmp['AssemblyManHours'] * 0);
        }
        else{
            $newHours = ($tmp['AssemblyManHours'] * 0.4);
        }

        if(!isset($date_totals[$date])){
            $date_totals[$date] = 0;
        }
        $date_totals[$date] += $newHours;

        if (!in_array($date, $all_dates)) {
            $all_dates[] = $date;
        }
    }

    foreach($date_totals as $date => $sum){
        $finalqc_data[$date] = $sum;
    }

    rsort($all_dates);

    // Calculate statistics
    $total_days = count($all_dates);
    $cut_total = array_sum($cut_data);
    $fit_total = array_sum($fit_data);
    $finalqc_total = array_sum($finalqc_data);

    $cut_avg = $total_days > 0 ? $cut_total / $total_days : 0;
    $fit_avg = $total_days > 0 ? $fit_total / $total_days : 0;
    $finalqc_avg = $total_days > 0 ? $finalqc_total / $total_days : 0;
    $total_avg = $cut_avg + $fit_avg + $finalqc_avg;

    // Calculate 6-day averages
    $cut_6day_avg = 0;
    $fit_6day_avg = 0;
    $finalqc_6day_avg = 0;
    $total_6day_avg = 0;

    if (count($all_dates) >= 1) {
        $last_6_dates = array_slice($all_dates, 0, min(6, count($all_dates)));
        $cut_6day_sum = 0;
        $fit_6day_sum = 0;
        $finalqc_6day_sum = 0;
        $days_with_data = 0;

        foreach ($last_6_dates as $date) {
            $cut_hours = isset($cut_data[$date]) ? $cut_data[$date] : 0;
            $fit_hours = isset($fit_data[$date]) ? $fit_data[$date] : 0;
            $finalqc_hours = isset($finalqc_data[$date]) ? $finalqc_data[$date] : 0;

            $cut_6day_sum += $cut_hours;
            $fit_6day_sum += $fit_hours;
            $finalqc_6day_sum += $finalqc_hours;
            $days_with_data++;
        }

        if ($days_with_data > 0) {
            $cut_6day_avg = $cut_6day_sum / $days_with_data;
            $fit_6day_avg = $fit_6day_sum / $days_with_data;
            $finalqc_6day_avg = $finalqc_6day_sum / $days_with_data;
            $total_6day_avg = $cut_6day_avg + $fit_6day_avg + $finalqc_6day_avg;
        }
    }

    $export_data = [];
    foreach($all_dates as $date) {
        if (isset($cut_data[$date])) {
            $export_data[] = [
                'DateCompleted' => $date,
                'StationName' => 'Cut',
                'CalculatedHours' => $cut_data[$date]
            ];
        }
        if (isset($fit_data[$date])) {
            $export_data[] = [
                'DateCompleted' => $date,
                'StationName' => 'Fit',
                'CalculatedHours' => $fit_data[$date]
            ];
        }
        if (isset($finalqc_data[$date])) {
            $export_data[] = [
                'DateCompleted' => $date,
                'StationName' => 'FinalQC',
                'CalculatedHours' => $finalqc_data[$date]
            ];
        }
    }

    $response['success'] = true;
    $response['message'] = 'Data retrieved successfully';
    $response['data'] = [
        'cut_data' => $cut_data,
        'fit_data' => $fit_data,
        'finalqc_data' => $finalqc_data,
        'all_dates' => $all_dates,
        'statistics' => [
            'total_days' => $total_days,
            'cut_total' => $cut_total,
            'fit_total' => $fit_total,
            'finalqc_total' => $finalqc_total,
            'cut_avg' => round($cut_avg, 2),
            'fit_avg' => round($fit_avg, 2),
            'finalqc_avg' => round($finalqc_avg, 2),
            'total_avg' => round($total_avg, 2),
            'cut_6day_avg' => round($cut_6day_avg, 2),
            'fit_6day_avg' => round($fit_6day_avg, 2),
            'finalqc_6day_avg' => round($finalqc_6day_avg, 2),
            'total_6day_avg' => round($total_6day_avg, 2)
        ],
        'export_data' => $export_data
    ];

} catch(Exception $e) {
    $response['success'] = false;
    $response['message'] = 'Database error: ' . $e->getMessage();
}

echo json_encode($response);
?>