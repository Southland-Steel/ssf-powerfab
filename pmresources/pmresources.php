<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resource and Task Manager</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .container {
            display: flex;
            height: 100vh;
        }

        .resources-panel {
            width: 250px;
            background-color: #f0f0f0;
            padding: 20px;
            overflow-y: auto;
        }

        .resources-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .resource-item {
            padding: 10px;
            margin: 5px 0;
            background-color: #e0e0e0;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .resource-item:hover {
            background-color: #74879f;
            color: white;
        }

        .resource-item.active {
            background-color: #456ca0;
            color: white;
            font-weight: bold;
        }

        .main-content {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .filters-section {
            padding: 15px;
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }

        .filter-row {
            display: flex;
            gap: 15px;
            margin-bottom: 10px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-label {
            font-size: 12px;
            margin-bottom: 4px;
            color: #666;
        }

        .filter-input {
            padding: 6px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .pm-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }

        .pm-button {
            padding: 6px 12px;
            background-color: #e0e0e0;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .pm-button:hover {
            background-color: #d0d0d0;
        }

        .pm-button.active {
            background-color: #4CAF50;
            color: white;
            transform: scale(1.05);
            font-weight: bold;
        }

        .table-container {
            flex-grow: 1;
            overflow-y: auto;
            padding: 15px;
        }

        .tasks-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .tasks-table th {
            position: sticky;
            top: 0;
            background-color: #f8f9fa;
            padding: 10px;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
            z-index: 1;
        }

        .tasks-table td {
            padding: 8px;
            border-bottom: 1px solid #dee2e6;
        }

        .tasks-table tr:hover {
            background-color: #f5f5f5;
        }

        .past-date {
            color: #dc3545;
            font-weight: bold;
            background-color: #ffebeb;
        }

        /* Remove any row-level past-date styling */
        tr.past-date {
            background-color: transparent;
        }

        .on-hold {
            background-color: rgba(255, 245, 99, 0.25);
        }

        .status-reviewed {
            background-color: #fff3cd;
        }

        /* Links */
        a {
            color: #0066cc;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        /* Checkbox styling */
        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        input[type="checkbox"] {
            margin: 0;
        }
        .filter-links {
            margin: 10px 0;
        }

        .filter-links a {
            color: #0066cc;
            text-decoration: none;
            margin-right: 10px;
        }

        .filter-links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="resources-panel">
        <h2>Resources</h2>
        <ul id="resources" class="resources-list"></ul>
        <br>
        <hr>
        <a href="instructions_view_grid_resources.php">Instructions / User Guide</a>
        <br>
        <br>
        <a href="instructions_view_grid_resources_notes.pdf">Adding Notes</a>


    </div>

    <div class="main-content">
        <div class="filters-section">
            <div id="pmButtons" class="pm-buttons"></div>

            <div class="filter-row">
                <div class="filter-group">
                    <label class="filter-label">Job Number</label>
                    <input type="text" id="jobFilter" class="filter-input" placeholder="Filter jobs...">
                </div>

                <div class="filter-group">
                    <label class="filter-label">Tasks</label>
                    <select id="scheduleFilter" class="filter-input">
                        <option value="">All Tasks</option>
                    </select>
                </div>

                <div class="filter-group checkbox-wrapper">
                    <label>
                        <input type="checkbox" id="completionFilter">
                        Show 100% Complete
                    </label>
                </div>
            </div>
        </div>

        <div class="table-container">
            <table class="tasks-table">
                <thead>
                <tr>
                    <th>Job Number</th>
                    <th>Project Description</th>
                    <th>Tasks</th>
                    <th>pct.</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Days</th>
                    <th>PM</th>
                </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Data Store
    const store = {
        allTasks: [],
        filteredTasks: [],
        resources: [],
        selectedResource: null,
        filters: {
            pm: null,
            job: '',
            schedule: '',
            showCompleted: false
        }
    };

    // Initialize Application
    function initializeApp() {
        loadResources();
        bindEvents();

        // Restore saved PM filter
        const savedPM = localStorage.getItem('selectedPM');
        if (savedPM) {
            store.filters.pm = savedPM;
        }
    }

    // Load Resources
    function loadResources() {
        $.ajax({
            url: 'ajax_grid_get_resources.php',
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                store.resources = data;
                renderResources();
                // Select Project Management (ID: 32) by default
                selectResource(32);
                // Mark it as active in the UI
                $('.resource-item[data-id="32"]').addClass('active');
            }
        });
    }

    // Load Tasks
    function loadTasks(resourceId) {
        $.ajax({
            url: 'ajax_grid_get_tasks.php',
            method: 'GET',
            data: { resourceId },
            dataType: 'json',
            success: function(data) {
                store.allTasks = data.sort((a, b) => {
                    // Compare end dates first
                    const endDateCompare = new Date(a.EndByDate) - new Date(b.EndByDate); // compare the end date
                    if (endDateCompare !== 0) return endDateCompare; // if both end dates are not the same return the difference and exit instance

                    // if end dates are equal it will make it to this line and return the value of the different start dates
                    return new Date(a.StartByDate) - new Date(b.StartByDate);
                });
                updatePMButtons();
                applyFilters();
            }
        });
    }

    // Event Bindings
    function bindEvents() {
        $('#resources').on('click', '.resource-item', function() {
            selectResource($(this).data('id'));
        });

        $('#jobFilter').on('input', updateFilters);
        $('#scheduleFilter').on('change', updateFilters);
        $('#completionFilter').on('change', updateFilters);
    }

    // Filter Updates
    function updateFilters() {
        store.filters = {
            ...store.filters,
            job: $('#jobFilter').val().toLowerCase(),
            schedule: $('#scheduleFilter').val(),
            showCompleted: $('#completionFilter').is(':checked')
        };
        applyFilters();
    }

    function applyFilters() {
        let filteredResults = store.allTasks;

        // 1. Project Manager (top level)
        if (store.filters.pm) {
            filteredResults = filteredResults.filter(task => task.PM === store.filters.pm);
        }

        // 2. Resource filter is handled by separate API call

        // 3. Lower level filters
        filteredResults = filteredResults.filter(task => {
            // Job Number
            if (store.filters.job && !task.JobNumber.toString().toLowerCase().includes(store.filters.job)) {
                return false;
            }

            // Schedule Description
            if (store.filters.schedule && task.TaskPath !== store.filters.schedule) {
                return false;
            }

            // Completion
            if (!store.filters.showCompleted && task.PercentCompleted >= 100) {
                return false;
            }

            return true;
        });

        store.filteredTasks = filteredResults;
        updateScheduleFilter();
        renderTasks();
    }

    // Rendering Functions
    function renderResources() {
        const resourcesList = $('#resources');
        resourcesList.empty();

        store.resources.forEach(resource => {
            resourcesList.append(`
            <li class="resource-item" data-id="${resource.ResourceID}">
                ${resource.ResourceDescription}
            </li>
        `);
        });
    }

    function renderTasks() {
        const tbody = $('.tasks-table tbody');
        tbody.empty();

        const today = new Date();
        today.setHours(0, 0, 0, 0);

        store.filteredTasks.forEach(task => {
            const startDate = new Date(task.StartByDate);
            const endDate = new Date(task.EndByDate);

            tbody.append(`
            <tr class="${task.JobStatusID == 13 ? 'on-hold' : ''}
                      ${task.ProjectStatus == 'REVIEWED' ? 'status-reviewed' : ''}">
                <td><a href="view_grid_project_resource_tasks.php?jobNumber=${task.JobNumber}">${task.JobNumber}</a></td>
                <td>${task.ProjectDescription}${formatNotes(task.Notes)}</td>
                <td>${task.TaskPath}</td>
                <td>${task.PercentCompleted}%</td>
                <td class="">${task.StartByDate}</td>
                <td class="${endDate < today ? 'past-date' : ''}">${task.EndByDate}</td>
                <td>${task.ActualDuration}</td>
                <td>${task.PM}</td>
            </tr>
        `);
        });
    }

    function updatePMButtons() {
        const pms = [...new Set(store.allTasks.map(task => task.PM))].sort();
        const pmButtons = $('#pmButtons');
        pmButtons.empty();

        pms.forEach(pm => {
            const isActive = store.filters.pm === pm ? 'active' : '';
            pmButtons.append(`
            <button class="pm-button ${isActive}" data-pm="${pm}">${pm}</button>
        `);
        });

        pmButtons.append('<button class="pm-button" id="clearPMFilter">Clear PM</button>');

        $('.pm-button').click(function() {
            const pm = $(this).data('pm');
            $('.pm-button').removeClass('active');
            if (pm) {
                $(this).addClass('active');
                store.filters.pm = pm;
                localStorage.setItem('selectedPM', pm);
            } else {
                store.filters.pm = null;
                localStorage.removeItem('selectedPM');
            }
            applyFilters();
        });
    }

    function updateScheduleFilter() {
        const scheduleMap = new Map();
        store.filteredTasks.forEach(task => {
            scheduleMap.set(task.TaskPath, (scheduleMap.get(task.TaskPath) || 0) + 1);
        });

        const scheduleFilter = $('#scheduleFilter');
        const currentValue = scheduleFilter.val(); // Save current selection
        scheduleFilter.html('<option value="">All Tasks</option>');

        Array.from(scheduleMap.entries())
            .sort((a, b) => a[0].localeCompare(b[0]))
            .forEach(([schedule, count]) => {
                scheduleFilter.append(`<option value="${schedule}">${schedule} (${count})</option>`);
            });

        // Restore selection if it still exists in the new options
        if (currentValue && scheduleMap.has(currentValue)) {
            scheduleFilter.val(currentValue);
        }
    }

    // Utility Functions
    function selectResource(resourceId) {
        // Clear all lower-level filters except PM
        store.filters = {
            pm: store.filters.pm, // Keep PM filter
            job: '',
            schedule: '',
            showCompleted: false
        };

        // Reset UI elements
        $('#jobFilter').val('');
        $('#scheduleFilter').val('');
        $('#completionFilter').prop('checked', false);

        // Update resource selection
        store.selectedResource = resourceId;
        $('.resource-item').removeClass('active');
        $(`.resource-item[data-id="${resourceId}"]`).addClass('active');
        loadTasks(resourceId);
    }

    function formatNotes(notes) {
        if (!notes || !notes.length) return '';

        let formattedNotes = '<br><strong>Notes:</strong><br>';
        notes.forEach((note, index) => {
            const date = new Date(note.time).toLocaleString();
            formattedNotes += `${index + 1}. ${note.NoteText} - ${date}<br>`;
        });
        return formattedNotes;
    }

    // Initialize the application
    $(document).ready(initializeApp);
</script>
</body>
</html>