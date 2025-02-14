<!DOCTYPE html>
<html>
<head>
    <title>[Page Title]</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- jQuery (needed for Bootstrap) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="template_style.css">
</head>
<body class="bg-light">
<div id="workweeks" class="container-fluid">
    <div id="activeWorkWeeks">
        <!-- Work week buttons will be inserted here -->
    </div>
</div>

<div class="container-fluid py-4">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">[Header Title]</h4>
        </div>

        <div class="card-body p-0">
            <div class="table-container">
                <table id="dataTable" class="table table-striped table-bordered table-hover mb-0 sticky-header">
                    <thead class="table-light">
                    <tr>
                        <th>Column 1</th>
                        <th>Column 2</th>
                    </tr>
                    </thead>
                    <tbody>
                    <!-- Data populated via JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Load available weeks on page load
        loadAvailableWeeks();
    });

    function loadAvailableWeeks() {
        fetch('ajax_get_weeks.php')
            .then(response => response.json())
            .then(data => {
                const weeklist = data.weeks.map(week => {
                    return `<button class="week-btn" onclick="loadWeekData('${week}')">${week}</button>`;
                }).join(' ');

                document.getElementById('activeWorkWeeks').innerHTML =
                    `<strong>Work Weeks:</strong> ${weeklist}`;

                // Load current week's data if available
                if (data.weeks.length > 0) {
                    // Load selected week if available, otherwise load current week
                    const weekToLoad = data.selectedWeek || data.currentWeek;
                    loadWeekData(weekToLoad);

                    // Set active state on the correct button
                    const activeButton = Array.from(document.querySelectorAll('.week-btn'))
                        .find(btn => btn.textContent === weekToLoad.toString());
                    if (activeButton) {
                        activeButton.classList.add('active');
                    }
                }
            })
            .catch(error => {
                console.error('Error loading weeks:', error);
                alert('Error loading available weeks');
            });
    }

    function loadWeekData(week) {
        // Update active button state
        document.querySelectorAll('.week-btn').forEach(btn => {
            btn.classList.remove('active');
            if (btn.textContent === week.toString()) {
                btn.classList.add('active');
            }
        });

        // Fetch data for selected week
        fetch(`ajax_get_endpoint.php?workweek=${week}`)
            .then(response => response.json())
            .then(data => {
                const tbody = document.querySelector('#dataTable tbody');
                tbody.innerHTML = ''; // Clear existing data

                data.forEach(item => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                            <td>${item.field1}</td>
                            <td>${item.field2}</td>
                        `;
                    tbody.appendChild(row);
                });
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading week data');
            });
    }
</script>
</body>
</html>