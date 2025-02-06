<?php
// get_workweek_data.php
header('Content-Type: application/json');

require_once '../config_ssf_db.php';

try {
    $workweek = $_GET['workweek'] ?? null;

    if (!$workweek) {
        throw new Exception('Workweek parameter is required');
    }

    $query = "SELECT 
        wp.WorkPackageID,
        wp.ProductionControlID,
        wp.WorkPackageNumber,
        wp.ReleasedToFab,
        wp.AssemblyQuantity as WPAssemblyQuantity,
        ROUND(wp.GrossWeight * 2.20462,0) AS WPGrossWeight,
        ROUND(wp.Hours,0) as WPHours,
        js.Description as JobStatus
    FROM workpackages as wp
    INNER JOIN productioncontroljobs as pcj 
        ON pcj.ProductionControlID = wp.ProductionControlID
    INNER JOIN jobstatuses as js 
        ON js.JobStatusID = pcj.JobStatusID
    WHERE wp.Completed = 0 
        AND wp.AssemblyQuantity > 0 
        AND js.Purpose = 0 
        AND wp.WorkshopID = 1
        AND wp.Group2 = :workweek
    ORDER BY wp.WorkPackageNumber";

    $stmt = $db->prepare($query);
    $stmt->execute([':workweek' => $workweek]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Convert boolean values
    foreach ($results as &$row) {
        $row['ReleasedToFab'] = (bool)$row['ReleasedToFab'];
    }

    echo json_encode($results);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}