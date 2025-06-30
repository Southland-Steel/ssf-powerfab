<?php

ob_start();
error_reporting(0);
ini_set('display_errors', 0);

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
    'data' => []
];

try {
    ob_clean();
    
    $pdo = $db;

    $finalqcData = exportFinalQCData($pdo, $start_date, $end_date);
    $cutData = exportCutData($pdo, $start_date, $end_date);
    $fitData = exportFitData($pdo, $start_date, $end_date);

    $combinedRecords = [];

    if (isset($finalqcData['records']) && is_array($finalqcData['records'])) {
        foreach ($finalqcData['records'] as $record) {
            $combinedRecords[] = [
                'DateCompleted' => $record['DateCompleted'],
                'JobNumber' => $record['JobNumber'],
                'StationName' => 'FinalQC',
                'CalculatedHours' => $record['CalculatedHours']
            ];
        }
    }

    if (isset($cutData['records']) && is_array($cutData['records'])) {
        foreach ($cutData['records'] as $record) {
            $combinedRecords[] = [
                'DateCompleted' => $record['DateCompleted'],
                'JobNumber' => $record['JobNumber'],
                'StationName' => 'Cut',
                'CalculatedHours' => $record['CalculatedHours']
            ];
        }
    }

    if (isset($fitData['records']) && is_array($fitData['records'])) {
        foreach ($fitData['records'] as $record) {
            $combinedRecords[] = [
                'DateCompleted' => $record['DateCompleted'],
                'JobNumber' => $record['JobNumber'],
                'StationName' => 'Fit',
                'CalculatedHours' => $record['CalculatedHours']
            ];
        }
    }
    
    $response['data'] = $combinedRecords;
    $response['success'] = true;
    $response['message'] = 'Data retrieved successfully';
    $response['summary'] = [
        'date_range' => "From {$start_date} to {$end_date}",
        'total_records' => count($combinedRecords),
        'finalqc_records' => count($finalqcData['records'] ?? []),
        'cut_records' => count($cutData['records'] ?? []),
        'fit_records' => count($fitData['records'] ?? [])
    ];
    
} catch (Exception $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
    $response['success'] = false;
} catch (PDOException $e) {
    $response['message'] = 'Database connection error';
    $response['success'] = false;
}

ob_clean();
echo json_encode($response, JSON_NUMERIC_CHECK);
exit;

function exportFinalQCData($pdo, $start_date, $end_date) {
    try {
        $sql = "SELECT
            pcis.DateCompleted,
            pcj.JobNumber,
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
            end as BaseAssemblyManHours,
            case 
                when pcis.PieceMark is NULL then 
                    (select COALESCE(fabrication.routes.Route, 'UNKNOWN') as RouteName
                    from fabrication.productioncontrolassemblies as pca1 
                    inner join fabrication.productioncontrolitems as pci1 on pci1.ProductionControlItemID = pca1.MainPieceProductionControlItemID
                    left join fabrication.routes on routes.RouteID = pci1.RouteID
                    where pca1.MainMark = pcis.MainMark and pca1.ProductionControlID = pcis.ProductionControlID)
                else 
                    (select COALESCE(fabrication.routes.Route, 'UNKNOWN') as RouteName
                    from fabrication.productioncontrolitems as pci 
                    left join fabrication.routes on routes.RouteID = pci.RouteID
                    where pci.MainMark = pcis.MainMark and pci.PieceMark = pcis.PieceMark and pci.ProductionControlID = pcis.ProductionControlID
                    order by pci.ProductionControlItemID)
            end as RouteName
        FROM fabrication.productioncontrolitemstations as pcis
        INNER JOIN fabrication.stations on stations.StationID = pcis.StationID
        INNER JOIN fabrication.productioncontrolsequences as pcseq on pcis.SequenceID = pcseq.SequenceID
        INNER JOIN fabrication.productioncontroljobs as pcj on pcj.ProductionControlID = pcis.ProductionControlID
        LEFT JOIN fabrication.workpackages as wp on wp.WorkPackageID = pcseq.WorkPackageID
        WHERE STR_TO_DATE(pcis.DateCompleted, '%Y-%m-%d') BETWEEN :begin_date AND :end_date 
            AND stations.Description = 'final qc' 
            AND pcis.DateCompleted IS NOT NULL 
            AND wp.WorkshopID = 1
        ORDER BY pcis.DateCompleted DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':begin_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->execute();
        
        $results = [];
        $jobTotals = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $dateCompleted = $row['DateCompleted'];
            $jobNumber = $row['JobNumber'];
            $routeName = $row['RouteName'] ?? 'UNKNOWN';
            $baseHours = floatval($row['BaseAssemblyManHours'] ?? 0);

            if ($routeName == 'BO') {
                $calculatedHours = $baseHours * 0.8;
            } elseif ($routeName == 'SHIP LOOSE') {
                $calculatedHours = $baseHours * 0;
            } else {
                $calculatedHours = $baseHours * 0.4;
            }

            $key = $dateCompleted . '_' . $jobNumber;
            if (!isset($jobTotals[$key])) {
                $jobTotals[$key] = [
                    'DateCompleted' => $dateCompleted,
                    'JobNumber' => $jobNumber,
                    'CalculatedHours' => 0
                ];
            }
            $jobTotals[$key]['CalculatedHours'] += $calculatedHours;
        }

        foreach ($jobTotals as $jobTotal) {
            $results[] = [
                'DateCompleted' => $jobTotal['DateCompleted'],
                'JobNumber' => $jobTotal['JobNumber'],
                'CalculatedHours' => round($jobTotal['CalculatedHours'], 4)
            ];
        }
        
        return [
            'export_type' => 'finalqc',
            'date_range' => "From {$start_date} to {$end_date}",
            'total_records' => count($results),
            'records' => $results
        ];
    } catch (Exception $e) {
        return [
            'export_type' => 'finalqc',
            'date_range' => "From {$start_date} to {$end_date}",
            'total_records' => 0,
            'records' => [],
            'error' => $e->getMessage()
        ];
    }
}

function exportCutData($pdo, $start_date, $end_date) {
    try {
        $sql = "SELECT
            pcis.DateCompleted,
            SUM(case 
                when pcis.PieceMark is NULL then 
                    (select pci.ManHours * pcis.Quantity
                     from fabrication.productioncontrolassemblies as pca1 
                    inner join fabrication.productioncontrolitems as pci on pci.ProductionControlItemID = pca1.MainPieceProductionControlItemID
                    where pca1.MainMark = pcis.MainMark and pca1.ProductionControlID = pcis.ProductionControlID)
                else 
                    (select pci.ManHours * pcis.Quantity
                    from fabrication.productioncontrolitems as pci 
                    where pci.MainMark = pcis.MainMark and pci.PieceMark = pcis.PieceMark and pci.ProductionControlID = pcis.ProductionControlID)
            end) as BaseManHours,
            pcj.JobNumber
        FROM fabrication.productioncontrolitemstations as pcis
        INNER JOIN fabrication.stations on stations.StationID = pcis.StationID
        INNER JOIN fabrication.productioncontrolsequences as pcseq on pcis.SequenceID = pcseq.SequenceID
        INNER JOIN fabrication.productioncontroljobs as pcj on pcj.ProductionControlID = pcis.ProductionControlID
        LEFT JOIN fabrication.workpackages as wp on wp.WorkPackageID = pcseq.WorkPackageID
        WHERE STR_TO_DATE(pcis.DateCompleted, '%Y-%m-%d') BETWEEN :begin_date AND :end_date 
            AND stations.Description = 'cut' 
            AND pcis.DateCompleted IS NOT NULL 
            AND wp.WorkshopID = 1
        GROUP BY pcis.DateCompleted, pcj.JobNumber
        ORDER BY pcis.DateCompleted DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':begin_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->execute();
        
        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $baseHours = floatval($row['BaseManHours'] ?? 0);
            $calculatedHours = $baseHours * 0.2;
            
            $results[] = [
                'DateCompleted' => $row['DateCompleted'],
                'JobNumber' => $row['JobNumber'],
                'CalculatedHours' => round($calculatedHours, 4)
            ];
        }
        
        return [
            'export_type' => 'cut',
            'date_range' => "From {$start_date} to {$end_date}",
            'total_records' => count($results),
            'records' => $results
        ];
    } catch (Exception $e) {
        return [
            'export_type' => 'cut',
            'date_range' => "From {$start_date} to {$end_date}",
            'total_records' => 0,
            'records' => [],
            'error' => $e->getMessage()
        ];
    }
}

function exportFitData($pdo, $start_date, $end_date) {
    try {
        $sql = "SELECT
            pcis.DateCompleted,
            SUM(case 
                when pcis.PieceMark is NULL then 
                    (select pca.AssemblyManHoursEach * pcis.Quantity from fabrication.productioncontrolassemblies as pca 
                    inner join fabrication.productioncontrolitems as pci on pci.ProductionControlItemID = pca.MainPieceProductionControlItemID
                    where pca.MainMark = pcis.MainMark and pca.ProductionControlID = pcis.ProductionControlID)
                else 
                    (select pca.AssemblyManHoursEach * pcis.Quantity
                    from fabrication.productioncontrolassemblies as pca 
                    inner join fabrication.productioncontrolitems as pci on pci.ProductionControlItemID = pca.MainPieceProductionControlItemID
                    where pci.MainMark = pcis.MainMark and pci.PieceMark = pcis.PieceMark and pci.ProductionControlID = pcis.ProductionControlID)
            end) as BaseAssemblyManHours,
            pcj.JobNumber
        FROM fabrication.productioncontrolitemstations as pcis
        INNER JOIN fabrication.stations on stations.StationID = pcis.StationID
        INNER JOIN fabrication.productioncontrolsequences as pcseq on pcis.SequenceID = pcseq.SequenceID
        INNER JOIN fabrication.productioncontroljobs as pcj on pcj.ProductionControlID = pcis.ProductionControlID
        LEFT JOIN fabrication.workpackages as wp on wp.WorkPackageID = pcseq.WorkPackageID
        WHERE STR_TO_DATE(pcis.DateCompleted, '%Y-%m-%d') BETWEEN :begin_date AND :end_date 
            AND stations.Description = 'fit' 
            AND pcis.DateCompleted IS NOT NULL 
            AND wp.WorkshopID = 1
        GROUP BY pcis.DateCompleted, pcj.JobNumber
        ORDER BY pcis.DateCompleted DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':begin_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->execute();
        
        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $baseHours = floatval($row['BaseAssemblyManHours'] ?? 0);
            $calculatedHours = $baseHours * 0.4;
            
            $results[] = [
                'DateCompleted' => $row['DateCompleted'],
                'JobNumber' => $row['JobNumber'],
                'CalculatedHours' => round($calculatedHours, 4)
            ];
        }
        
        return [
            'export_type' => 'fit',
            'date_range' => "From {$start_date} to {$end_date}",
            'total_records' => count($results),
            'records' => $results
        ];
    } catch (Exception $e) {
        return [
            'export_type' => 'fit',
            'date_range' => "From {$start_date} to {$end_date}",
            'total_records' => 0,
            'records' => [],
            'error' => $e->getMessage()
        ];
    }
}
?>