<?php
// templates/template_view.php

// Set page-specific variables
$page_title = 'Data View Template';
$show_workweeks = true; // Set to true to show the work weeks selector

// Additional CSS specific to this page
$extra_css = '
<style>
    /* Additional page-specific CSS goes here */
    .custom-highlight {
        background-color: #ffffcc;
    }
</style>
';

// Include header - use __DIR__ for more reliable path resolution
include_once __DIR__ . '/../includes/header.php';
?>

    <!-- Page-specific content starts here -->
    <div class="card shadow">
        <div class="card-header bg-ssf-primary text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Data Table</h4>
            <div>
                <button class="btn btn-sm btn-outline-light">
                    <i class="bi bi-file-earmark-excel"></i> Export
                </button>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-container">
                <table id="dataTable" class="table table-striped table-bordered table-hover mb-0 sticky-header">
                    <thead>
                    <tr>
                        <th>Column 1</th>
                        <th>Column 2</th>
                        <!-- Add more columns as needed -->
                    </tr>
                    </thead>
                    <tbody>
                    <!-- Data populated via JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-light border-top">
            <div class="text-muted small">
                <span id="recordCount">0</span> records found
            </div>
        </div>
    </div>
    <!-- Page-specific content ends here -->

<?php
// Additional JavaScript specific to this page
$extra_js = '
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Load available weeks on page load if needed
        loadAvailableWeeks();
        
        // Function to load week data
        function loadWeekData(week) {
            // Update active button state
            document.querySelectorAll(".week-btn").forEach(btn => {
                btn.classList.remove("active");
                if (btn.textContent === week.toString()) {
                    btn.classList.add("active");
                }
            });

            // Show loading overlay
            window.showLoading();

            // Fetch data for selected week
            fetch("' . getUrl('templates/ajax_get_endpoint.php') . '?workweek=" + week)
                .then(response => response.json())
                .then(data => {
                    const tbody = document.querySelector("#dataTable tbody");
                    tbody.innerHTML = ""; // Clear existing data

                    data.forEach(item => {
                        const row = document.createElement("tr");
                        row.innerHTML = `
                            <td>${item.field1}</td>
                            <td>${item.field2}</td>
                        `;
                        tbody.appendChild(row);
                    });
                    
                    // Update record count
                    document.getElementById("recordCount").textContent = data.length;
                    
                    // Hide loading overlay
                    window.hideLoading();
                })
                .catch(error => {
                    console.error("Error:", error);
                    alert("Error loading week data");
                    window.hideLoading();
                });
        }

        // Function to load available work weeks
        function loadAvailableWeeks() {
            fetch("' . getUrl('templates/ajax_get_weeks.php') . '")
                .then(response => response.json())
                .then(data => {
                    const weeklist = data.weeks.map(week => {
                        return `<button class="week-btn" onclick="loadWeekData(\'${week}\')">${week}</button>`;
                    }).join("");

                    document.getElementById("activeWorkWeeks").innerHTML = weeklist;

                    // Load current week\'s data if available
                    if (data.weeks.length > 0) {
                        // Load selected week if available, otherwise load current week
                        const weekToLoad = data.selectedWeek || data.currentWeek;
                        loadWeekData(weekToLoad);

                        // Set active state on the correct button
                        const activeButton = Array.from(document.querySelectorAll(".week-btn"))
                            .find(btn => btn.textContent === weekToLoad.toString());
                        if (activeButton) {
                            activeButton.classList.add("active");
                        }
                    }
                })
                .catch(error => {
                    console.error("Error loading weeks:", error);
                    alert("Error loading available weeks");
                });
        }

        // Make loadWeekData global so it can be called from HTML
        window.loadWeekData = loadWeekData;
        window.loadAvailableWeeks = loadAvailableWeeks;
    });
</script>
';

// Include footer - use __DIR__ for more reliable path resolution
include_once __DIR__ . '/../includes/footer.php';
?>