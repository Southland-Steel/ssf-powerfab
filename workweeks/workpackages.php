<?php
require_once '../config_ssf_db.php';
$wp_id = $_GET['wp_id'] ?? null;
$workweek = $_GET['workweek'] ?? null;

$wpQuery = "
SELECT 
    wp.WorkPackageNumber,
    SUM(pciseq.Quantity) as TotalPieces,
    ROUND(SUM(pciseq.Quantity * pca.GrossAssemblyWeightEach), 2) as TotalWeight,
    ROUND(SUM(pciseq.Quantity * pca.AssemblyManHoursEach), 2) as TotalHours
FROM workpackages wp
LEFT JOIN productioncontrolsequences pcseq ON pcseq.WorkPackageID = wp.WorkPackageID
LEFT JOIN productioncontrolitemsequences pciseq ON pciseq.SequenceID = pcseq.SequenceID
LEFT JOIN productioncontrolassemblies pca ON pca.ProductionControlAssemblyID = pciseq.ProductionControlAssemblyID
WHERE wp.WorkPackageID = :wp_id
GROUP BY wp.WorkPackageID, wp.WorkPackageNumber";

$stmt = $db->prepare($wpQuery);
$stmt->execute([':wp_id' => $wp_id]);
$wpInfo = $stmt->fetch(PDO::FETCH_ASSOC);

$seqQuery = "
SELECT DISTINCT 
    pcseq.SequenceID,
    CONCAT(
        REPLACE(pcseq.Description, CHAR(1),''),
        ' - ',
        REPLACE(pcseq.LotNumber, CHAR(1), '')
    ) AS SequenceKey
FROM productioncontrolsequences pcseq
INNER JOIN workpackages as wp on wp.WorkPackageID = pcseq.WorkPackageID
WHERE pcseq.WorkPackageID = :wp_id
and wp.WorkshopID = 1
and pcseq.AssemblyQuantity > 0
ORDER BY pcseq.Description, pcseq.LotNumber";

$stmt = $db->prepare($seqQuery);
$stmt->execute([':wp_id' => $wp_id]);
$sequences = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>WP <?= htmlspecialchars($wpInfo['WorkPackageNumber']) ?></title>
    <link rel="stylesheet" href="workweeks.css?v=<?= time() ?>">
</head>
<body>
<header class="header">
    <div class="header-content">
        <img src="../images/ssf-horiz.png" alt="Logo" class="logo">
    </div>
</header>

<main class="main-container full-width">
    <div class="breadcrumbs">
        <a href="workweeks.php">Workweeks</a>
        <span class="separator">/</span>
        <a href="workweeks.php?workweek=<?= $workweek ?>">Week <?= $workweek ?></a>
        <span class="separator">/</span>
        <span>WP <?= htmlspecialchars($wpInfo['WorkPackageNumber']) ?></span>
    </div>

    <div class="content-wrapper">
        <div class="main-content">
            <div class="summary-cards">
                <div class="summary-card">
                    <div class="summary-card-title">Total Pieces</div>
                    <div class="summary-card-value"><?= number_format($wpInfo['TotalPieces']) ?></div>
                </div>
                <div class="summary-card">
                    <div class="summary-card-title">Total Weight</div>
                    <div class="summary-card-value"><?= number_format($wpInfo['TotalWeight'], 2) ?> lbs</div>
                </div>
                <div class="summary-card">
                    <div class="summary-card-title">Total Hours</div>
                    <div class="summary-card-value"><?= number_format($wpInfo['TotalHours'], 2) ?> hrs</div>
                </div>
            </div>

            <div class="sequence-buttons">
                <?php foreach ($sequences as $seq): ?>
                    <button class="sequence-btn"
                            data-sequence-id="<?= htmlspecialchars($seq['SequenceID']) ?>"
                            onclick="loadSequenceData(this.dataset.sequenceId)">
                        <?= htmlspecialchars($seq['SequenceKey']) ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <div id="data-container"></div>
        </div>
    </div>
</main>

<script>
    function loadSequenceData(sequenceId) {
        const buttons = document.querySelectorAll('.sequence-btn');
        buttons.forEach(btn => btn.classList.remove('active'));
        event.target.classList.add('active');

        fetch(`get_sequence_data.php?sequence_id=${sequenceId}`)
            .then(response => response.json())
            .then(data => {
                let html = '<table class="data-table">';
                html += `<thead><tr>
                    <th>PCIS ID</th>
                    <th>Main Mark</th>
                    <th>Assembly Qty</th>
                    <th>Assembly Hours Each</th>
                    <th>Assembly Weight Each</th>
                    <th>Total Hours</th>
                    <th>Total Weight</th>
                </tr></thead><tbody>`;

                data.forEach(item => {
                    const totalHours = item.Quantity * item.AssemblyManHoursEach;
                    const totalWeight = item.Quantity * item.GrossAssemblyWeightEach;

                    html += `<tr>
                        <td><a href="assemblies.php?pcis_id=${item.ProductionControlItemSequenceID}&workweek=<?= $workweek ?>" class="text-primary-600 hover:underline">${item.ProductionControlItemSequenceID}</a></td>
                        <td><a href="assemblies.php?pcis_id=${item.ProductionControlItemSequenceID}&workweek=<?= $workweek ?>" class="text-primary-600 hover:underline">${item.MainMark}</a></td>
                        <td>${item.Quantity}</td>
                        <td>${parseFloat(item.AssemblyManHoursEach).toFixed(3)}</td>
                        <td>${parseFloat(item.GrossAssemblyWeightEach).toFixed(2)}</td>
                        <td>${totalHours.toFixed(3)}</td>
                        <td>${totalWeight.toFixed(2)}</td>
                    </tr>`;
                });

                html += '</tbody></table>';
                document.getElementById('data-container').innerHTML = html;
            });
    }



    // Load first sequence and cutlist data by default
    document.querySelector('.sequence-btn')?.click();
</script>
</body>
</html>