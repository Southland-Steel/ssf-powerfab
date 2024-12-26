<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SSF Production Management</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .container {
            margin-top: 50px;
        }
        .btn {
            width: 100%;
            margin-bottom: 20px;
        }
        h2 {
            margin-bottom: 20px;
        }
        .logo{
            height: 50px;
            width:auto;
            margin-right: 50px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="d-flex align-items-center justify-content-center mb-5">
        <img src="images/ssf-logo.png" alt="SSF Logo" class="logo me-3">
        <h1>SSF Production Management System</h1>
    </div>
    <div class="row">
        <div class="col-md-4">
            <h5 class="text-center">SSF Production Resources</h5>
            <a href="view_ssf_hit_stations.php" class="btn btn-success">View SSF Production Monitor</a>
            <a href="workweeks.php" class="btn btn-warning">View SSF Workweeks</a>

        </div>
        <div class="col-md-4"></div>
        <div class="col-md-4">
        </div>
    </div>
</div>
<script src="js/IsDev.js?v=<?php echo time(); ?>"></script>
</body>
</html>