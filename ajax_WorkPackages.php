<?php
// ajax_WorkPackages.php
require_once 'config_ssf_db.php';

header('Content-Type: application/json');

class WorkPackagesHandler {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getWorkPackagesByWeek($week) {
        try {
            $query = "SELECT 
                wp.WorkPackageID,
                pcj.ProductionControlID,
                pcj.JobNumber,
                wp.WorkPackageNumber,
                wp.Description as WorkPackageDescription,
                wp.StartDate,
                wp.DueDate,
                wp.Group1,
                wp.Group2 as WorkWeek,
                wp.Notes,
                wp.ReleasedToFab,
                wp.OnHold,
                ROUND(wp.Weight / 2.20462) AS Weight,
                ROUND(wp.Hours,1) AS Hours
            FROM workpackages as wp
            LEFT JOIN productioncontroljobs as pcj 
                ON pcj.ProductionControlID = wp.ProductionControlID
            WHERE wp.Completed = 0 
                AND wp.AssemblyQuantity > 0 
                AND wp.Group2 = ?";

            $stmt = $this->db->prepare($query);
            $stmt->execute([$week]);

            return [
                'success' => true,
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];

        } catch (PDOException $e) {
            return [
                'success' => false,
                'error' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
}

// Handle the request
try {
    if (!isset($_GET['week']) || empty($_GET['week'])) {
        throw new Exception('Week parameter is required');
    }

    $workPackages = new WorkPackagesHandler($db);
    $result = $workPackages->getWorkPackagesByWeek($_GET['week']);

    echo json_encode($result);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}