<?php
require_once '../config_ssf_db.php';

$query_record = (isset($_GET['RecordNum'])) ? $_GET['RecordNum'] : '';
$parent_record = null;

try {
    if (!empty($query_record)) {
        $parent_query = "SELECT itr.ParentInspectionTestRecordID 
            FROM fabrication.inspectiontestrecords as itr 
            WHERE itr.InspectionTestRecordID = :query_record";
        $parent_stmt = $db->prepare($parent_query);
        $parent_stmt->bindParam(':query_record', $query_record);
        $parent_stmt->execute();
        $parent_result = $parent_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($parent_result && !empty($parent_result['ParentInspectionTestRecordID'])) {
            $parent_record = $parent_result['ParentInspectionTestRecordID'];
        }
    }

    $query = "SELECT itr.TestDateTime,
        infs.String as field,
        fvs.String as fieldValue,
        itr.TestFailed
        FROM fabrication.inspectiontestrecords as itr
        inner join fabrication.inspectiontestrecordfields as itrf on itrf.InspectionTestRecordID = itr.InspectionTestRecordID
        inner join fabrication.inspectionteststrings as fvs on fvs.InspectionTestStringID = itrf.ValueStringID
        inner join fabrication.inspectiontestfields as inf on inf.InspectionTestFieldID = itrf.InspectionTestFieldID
        inner join fabrication.inspectionteststrings as infs on infs.InspectionTestStringID = inf.FieldTitleStringID
        where itr.InspectionTestRecordID = :query_record";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':query_record', $query_record);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    var_dump($e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inspection Test Records - Details</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 5px;
            line-height: 1.6;
        }
        .container{
            min-width: 100%;
            position: relative;
        }
        .main-content {
            padding:20px;
            width: 100%;
            box-sizing: border-box;
        }
        .monitor-header {
            width: 100%;
            box-sizing: border-box;
            margin-bottom: 20px;
            padding: 5px;
            background-color: #f0f0f0;
            border-radius: 5px;
            position: relative;
        }
        .monitor-header h1 {
            color: #333;
            margin-bottom: 10px;
        }

        .return-button {
            position: absolute;
            top: 10px;
            right: 15px;
            background-color: #007bff;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        
        .return-button:hover {
            background-color: #0056b3;
            color: white;
            text-decoration: none;
        }

        .parent-link {
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
        }
        
        .parent-link:hover {
            text-decoration: underline;
            color: #0056b3;
        }

        .table-container {
            max-height: 78.5vh;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }
        
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .center {
            text-align: center;
        }
        
        .no-data {
            text-align: center;
            padding: 20px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="monitor-header">
            <a href="view_open_ncr_summary.php" class="return-button">← Return to Summary</a>
            <h1>Inspection NCR - Details</h1>
            <?php if (!empty($query_record)): ?>
                <p>
                    Record Number: <?php echo htmlspecialchars($query_record); ?>
                    <?php if ($parent_record): ?>
                        | Parent Record: <a href="?RecordNum=<?php echo htmlspecialchars($parent_record); ?>" class="parent-link"><?php echo htmlspecialchars($parent_record); ?></a>
                    <?php endif; ?>
                </p>
            <?php endif; ?>
        </header>
        
        <div class="table-container">
            <?php if (isset($results) && count($results) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Test Date | Time</th>
                            <th>Field</th>
                            <th>Field Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['TestDateTime']); ?></td>
                                <td><?php echo htmlspecialchars($row['field']); ?></td>
                                <td>
                                    <?php
                                    if (strtolower($row['field']) === 'is ncr closed?') {
                                        echo ($row['fieldValue'] == '1') ? 'Yes' : 'No';
                                    } else {
                                        echo htmlspecialchars($row['fieldValue']);
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php elseif (isset($results)): ?>
                <div class="no-data">No records found for the specified Record Number.</div>
            <?php endif; ?>
            
            <?php if (empty($query_record)): ?>
                <div class="no-data">Please provide a RecordNum parameter in the URL (e.g., ?RecordNum=123)</div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>