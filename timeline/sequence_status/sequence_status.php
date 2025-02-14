<!DOCTYPE html>
<html>
<head>
    <title>Production Status</title>
    <link rel="stylesheet" href="sequence_status.css">
</head>
<body>
<div>
    <h2 style="margin: 10px 0;">Production Status for Job <?= $_GET['jobNumber'] ?> - Sequence <?= $_GET['sequenceName'] ?></h2>
    <p style="margin: 0 0 20px; color: #666;">Showing assembly progress through fabrication stations: Nested, Cut, Fit, Weld, and Final QC</p>
</div>
<div id="status-table"></div>

<script>
    let activeFilters = new Set(['all']);

    function loadData() {
        fetch('get_sequence_status.php?' + new URLSearchParams({
            jobNumber: '<?= $_GET['jobNumber'] ?>',
            sequenceName: '<?= $_GET['sequenceName'] ?>'
        }))
            .then(response => response.json())
            .then(data => {
                data.sort((a, b) => {
                    // First check if both items are fully complete (all stations >= 98%)
                    const aComplete = Object.keys(a.Stations).every(station =>
                        (a.Stations[station]?.Completed / a.Stations[station]?.Total) >= 0.98
                    );
                    const bComplete = Object.keys(b.Stations).every(station =>
                        (b.Stations[station]?.Completed / b.Stations[station]?.Total) >= 0.98
                    );

                    // Move completed items to bottom
                    if (aComplete && !bComplete) return 1;
                    if (!aComplete && bComplete) return -1;
                    if (aComplete && bComplete) return 0;

                    // Define station order for comparison
                    const stationOrder = ['SHIPPING', 'FINAL QC', 'WELD', 'FIT', 'CUT', 'NESTED'];

                    // Compare each station in order
                    for (const station of stationOrder) {
                        const aPercent = a.Stations[station]?.Completed / a.Stations[station]?.Total || 0;
                        const bPercent = b.Stations[station]?.Completed / b.Stations[station]?.Total || 0;

                        if (aPercent !== bPercent) {
                            // Sort in ascending order (least complete first)
                            return aPercent - bPercent;
                        }
                    }

                    return 0;
                });

                // Build category filter buttons
                const categories = [...new Set(data.map(assembly => assembly.Category))].filter(Boolean);
                let filterHtml = '<div class="filter-buttons">';
                filterHtml += `<button class="filter-button active" data-category="all">All Categories</button>`;
                categories.forEach(category => {
                    filterHtml += `<button class="filter-button" data-category="${category}">${category}</button>`;
                });
                filterHtml += '</div>';

                let html = `
                ${filterHtml}
                <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>SubCategory</th>
                        <th>Job</th>
                        <th>Sequence</th>
                        <th>Lot</th>
                        <th>Work Package</th>
                        <th>Main Mark</th>
                        <th>Gross Ea.</th>
                        <th>Nested</th>
                        <th>Cut</th>
                        <th>Fit</th>
                        <th>Weld</th>
                        <th>Final QC</th>
                        <th>JobSite</th>
                    </tr>
                </thead>
                <tbody>`;

                data.forEach(assembly => {
                    const isComplete = Object.keys(assembly.Stations).every(station =>
                        (assembly.Stations[station]?.Completed / assembly.Stations[station]?.Total) >= 0.98
                    );

                    html += `
                    <tr data-category="${assembly.Category}">
                        <td>${assembly.Category}</td>
                        <td>${assembly.SubCategory}</td>
                        <td>${assembly.JobNumber}</td>
                        <td>${assembly.SequenceName}</td>
                        <td>${assembly.LotNumber}</td>
                        <td>${assembly.WorkPackageNumber}</td>
                        <td>${assembly.MainMark}</td>
                        <td>${assembly.GrossAssemblyWeightEach}</td>`;

                    ['NESTED', 'CUT', 'FIT', 'WELD', 'FINAL QC', 'SHIPPING'].forEach(station => {
                        if (assembly.Stations[station]) {
                            const percent = (assembly.Stations[station].Completed / assembly.Stations[station].Total) * 100;
                            const statusClass = percent >= 90 ? 'complete' :
                                percent >= 50 ? 'partial' :
                                    'incomplete';
                            html += `<td class="status ${statusClass}">
                                ${assembly.Stations[station].Completed} / ${assembly.Stations[station].Total}
                                <br>(${percent.toFixed(1)}%)
                            </td>`;
                        } else {
                            html += '<td class="na">-</td>';
                        }
                    });

                    html += '</tr>';
                });

                html += '</tbody></table>';
                document.getElementById('status-table').innerHTML = html;

                // Add event listeners for filter buttons
                document.querySelectorAll('.filter-button').forEach(button => {
                    button.addEventListener('click', function() {
                        // Remove active class from all buttons
                        document.querySelectorAll('.filter-button').forEach(btn =>
                            btn.classList.remove('active'));

                        // Add active class to clicked button
                        this.classList.add('active');

                        const selectedCategory = this.dataset.category;
                        filterRows(selectedCategory);
                    });
                });
            });
    }

    function filterRows(category) {
        const rows = document.querySelectorAll('table tbody tr');
        rows.forEach(row => {
            const rowCategory = row.dataset.category;
            if (category === 'all' || rowCategory === category) {
                // Only show if not hidden by completion toggle
                row.style.display = row.classList.contains('hidden') ? 'none' : '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    loadData();
</script>
</body>
</html>