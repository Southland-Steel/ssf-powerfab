<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Production Summary Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }
        
        .dashboard-container {
            text-align: center;
            background: white;
            padding: 50px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            width: 100%;
        }
        
        .dashboard-title {
            color: #333;
            margin-bottom: 40px;
            font-size: 2.5rem;
            font-weight: bold;
        }
        
        .summary-btn {
            display: block;
            width: 100%;
            padding: 20px;
            margin: 15px 0;
            font-size: 1.2rem;
            font-weight: bold;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            border: none;
        }
        
        .summary-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }
        
        .btn-cut {
            background-color: #007bff;
            color: white;
        }
        
        .btn-cut:hover {
            background-color: #0056b3;
            color: white;
        }
        
        .btn-fit {
            background-color: #28a745;
            color: white;
        }
        
        .btn-fit:hover {
            background-color: #1e7e34;
            color: white;
        }
        
        .btn-qc {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-qc:hover {
            background-color: #c82333;
            color: white;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <h1 class="dashboard-title">Production Summary Dashboard</h1>
        
        <a href="ww-cut/view_cut_summary.php" class="summary-btn btn-cut">
            Cut Summary Report
        </a>
        
        <a href="ww-fit/view_fit_summary.php" class="summary-btn btn-fit">
            Fit Summary Report
        </a>
        
        <a href="ww-finalqc/view_finalqc_summary.php" class="summary-btn btn-qc">
            Final QC Summary Report
        </a>
    </div>
</body>
</html>