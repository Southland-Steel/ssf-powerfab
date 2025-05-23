<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Steel Projects Monitor'; ?></title>

    <!-- External CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <!-- Core CSS -->
    <link href="css/core.css" rel="stylesheet">

    <!-- Page-specific CSS -->
    <?php if (isset($pageStyles) && is_array($pageStyles)): ?>
        <?php foreach ($pageStyles as $style): ?>
            <link href="css/<?php echo htmlspecialchars($style); ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Custom Page Head Content -->
    <?php if (isset($customHead)): ?>
        <?php echo $customHead; ?>
    <?php endif; ?>
</head>
<body>
<!-- Optional Navigation Bar -->
<?php if (!isset($hideNavigation) || !$hideNavigation): ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-industry me-2"></i>
                Steel Projects Monitor
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>"
                           href="index.php">
                            <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                        </a>
                    </li>
                    <!-- Add more navigation items as needed -->
                    <li class="nav-item">
                        <a class="nav-link" href="#" onclick="showHelp(); return false;">
                            <i class="fas fa-question-circle me-1"></i> Help
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
<?php endif; ?>

<!-- Main Content Container -->
<main class="main-content">