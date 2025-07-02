<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resource and Task Manager</title>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
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

        .pm-buttons, .job-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }

        .pm-button, .job-button {
            padding: 6px 12px;
            background-color: #e0e0e0;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .pm-button:hover, .job-button:hover {
            background-color: #d0d0d0;
        }

        .pm-button.active {
            background-color: #4CAF50;
            color: white;
            transform: scale(1.05);
            font-weight: bold;
        }

        .job-button.active {
            background-color: #2196F3;
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

        tr.past-date {
            background-color: transparent;
        }

        .on-hold {
            background-color: rgba(255, 245, 99, 0.25);
        }

        .status-reviewed {
            background-color: #fff3cd;
        }

        a {
            color: #0066cc;
            text-decoration: none;
            cursor: pointer;
        }

        a:hover {
            text-decoration: underline;
        }

        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        input[type="checkbox"] {
            margin: 0;
        }

        /* Help Button Styles */
        .help-button {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            padding: 8px 16px;
            background-color: #456ca0;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .help-button:hover {
            background-color: #3c5d8f;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 2000;
        }

        .modal.active {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            width: 80%;
            max-width: 800px;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
        }

        .close-modal {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 24px;
            cursor: pointer;
            color: #666;
            border: none;
            background: none;
            padding: 0;
        }

        .close-modal:hover {
            color: #333;
        }

        /* Markdown Content Styles */
        .markdown-content h1 {
            color: #333;
            margin-bottom: 1em;
        }

        .markdown-content h2 {
            color: #456ca0;
            margin-top: 1.5em;
            margin-bottom: 0.75em;
        }

        .markdown-content p {
            line-height: 1.6;
            margin-bottom: 1em;
        }

        .markdown-content ul {
            margin-bottom: 1em;
            padding-left: 2em;
        }

        .markdown-content li {
            margin-bottom: 0.5em;
        }

        .filter-section-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .job-link {
            color: #0066cc;
            cursor: pointer;
            text-decoration: none;
        }

        .job-link:hover {
            text-decoration: underline;
        }

        .job-link.active {
            color: #2196F3;
            font-weight: bold;
        }

        .no-results-message {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 60px 20px;
            text-align: center;
        }

        .no-results-box {
            background-color: #f8f9fa;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 30px 40px;
            max-width: 500px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .no-results-icon {
            font-size: 48px;
            color: #6c757d;
            margin-bottom: 15px;
        }

        .no-results-text {
            font-size: 18px;
            color: #495057;
            margin: 0;
            line-height: 1.5;
        }

        .filter-info {
            font-size: 16px;
            color: #6c757d;
            margin-top: 10px;
        }
    </style>
</head>
<body>
<button onclick="showDocumentation()" class="help-button">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10"></circle>
        <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
        <line x1="12" y1="17" x2="12.01" y2="17"></line>
    </svg>
    Help
</button>

<div id="docs-modal" class="modal">
    <div class="modal-content">
        <button class="close-modal" onclick="closeModal()">&times;</button>
        <div id="markdown-content" class="markdown-content"></div>
    </div>
</div>

<div class="container">
    <div class="resources-panel">
        <h2>Resources</h2>
        <ul id="resources" class="resources-list"></ul>
    </div>

    <div class="main-content">
        <div class="filters-section">
            <div class="filter-section-label">Project Manager Filter:</div>
            <div id="pmButtons" class="pm-buttons"></div>

            <div class="filter-section-label">Job Number Filter:</div>
            <div id="jobButtons" class="job-buttons"></div>

            <div class="filter-row">
                <div class="filter-group">
                    <input type="text" id="breakdownFilter" class="filter-input" placeholder="Filter by Sequence value...">
                </div>

                <div class="filter-group">
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
                    <th>JobSequenceLot</th>
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
            jobNumber: null,
            breakdown: '',
            schedule: '',
            showCompleted: false
        }
    };

    // Helper Functions
    async function fetchJson(url, options = {}) {
        const response = await fetch(url, options);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return await response.json();
    }

    // Initialize Application
    async function initializeApp() {
        await loadResources();
        bindEvents();

        // Restore saved filters
        const savedPM = localStorage.getItem('selectedPM');
        if (savedPM) {
            store.filters.pm = savedPM;
        }

        const savedJobNumber = localStorage.getItem('selectedJobNumber');
        if (savedJobNumber) {
            store.filters.jobNumber = savedJobNumber;
        }
    }

    // Load Resources
    async function loadResources() {
        try {
            store.resources = await fetchJson('ajax_get_resources.php');
            renderResources();
            selectResource(48);
            document.querySelector('.resource-item[data-id="32"]')?.classList.add('active');
        } catch (error) {
            console.error('Error loading resources:', error);
        }
    }

    // Load Tasks
    async function loadTasks(resourceId) {
        try {
            const data = await fetchJson(`ajax_get_tasks.php?resourceId=${resourceId}`);
            store.allTasks = data.sort((a, b) => {
                const endDateCompare = new Date(a.EndByDate) - new Date(b.EndByDate);
                return endDateCompare || (new Date(a.StartByDate) - new Date(b.StartByDate));
            });
            updatePMButtons();
            updateJobButtons();
            applyFilters();
        } catch (error) {
            console.error('Error loading tasks:', error);
        }
    }

    // Event Bindings
    function bindEvents() {
        document.getElementById('resources').addEventListener('click', (e) => {
            if (e.target.classList.contains('resource-item')) {
                selectResource(e.target.dataset.id);
            }
        });

        document.getElementById('breakdownFilter').addEventListener('input', updateFilters);
        document.getElementById('scheduleFilter').addEventListener('change', updateFilters);
        document.getElementById('completionFilter').addEventListener('change', updateFilters);
    }

    // Filter Updates
    function updateFilters() {
        store.filters = {
            ...store.filters,
            breakdown: document.getElementById('breakdownFilter').value.toLowerCase(),
            schedule: document.getElementById('scheduleFilter').value,
            showCompleted: document.getElementById('completionFilter').checked
        };
        applyFilters();
    }

    function applyFilters() {
        let filteredResults = store.allTasks;

        if (store.filters.pm) {
            filteredResults = filteredResults.filter(task => task.PM === store.filters.pm);
        }

        if (store.filters.jobNumber) {
            filteredResults = filteredResults.filter(task => task.JobNumber === store.filters.jobNumber);
        }

        filteredResults = filteredResults.filter(task => {
            // Create the JobSequenceLot string that matches what's displayed in the table
            const jobSequenceLot = `${task.JobNumber} - ${task.SequenceName} ${(task.LotNumber != 0) ? '-' + task.LotNumber : ''}`.toLowerCase();

            if (store.filters.breakdown && !jobSequenceLot.includes(store.filters.breakdown)) {
                return false;
            }

            if (store.filters.schedule && task.TaskPath !== store.filters.schedule) {
                return false;
            }

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
        const resourcesList = document.getElementById('resources');
        resourcesList.innerHTML = store.resources
            .map(resource => `
                <li class="resource-item" data-id="${resource.ResourceID}">
                    ${resource.ResourceDescription}
                </li>
            `).join('');
    }

    function renderTasks() {
        const tbody = document.querySelector('.tasks-table tbody');
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        if (store.filteredTasks.length === 0) {
            // Build the filter description
            let filterDescription = [];
            if (store.filters.pm) {
                filterDescription.push(`PM = ${store.filters.pm}`);
            }
            if (store.filters.jobNumber) {
                filterDescription.push(`Job Number = ${store.filters.jobNumber}`);
            }
            if (store.filters.breakdown) {
                filterDescription.push(`Breakdown contains "${store.filters.breakdown}"`);
            }
            if (store.filters.schedule) {
                filterDescription.push(`Task = "${store.filters.schedule}"`);
            }
            if (!store.filters.showCompleted) {
                filterDescription.push("Completion < 100%");
            }

            const filterText = filterDescription.length > 0
                ? filterDescription.join(' and ')
                : 'current criteria';

            // Check if PM or Job Number filters are active (from localStorage)
            const hasPersistentFilters = store.filters.pm || store.filters.jobNumber;

            // Create the clear filters button HTML if needed
            const clearFiltersButton = hasPersistentFilters ? `
            <button onclick="clearPersistentFilters()" style="
                margin-top: 20px;
                padding: 10px 20px;
                background-color: #dc3545;
                color: white;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 16px;
                transition: background-color 0.2s;
            " onmouseover="this.style.backgroundColor='#c82333'"
               onmouseout="this.style.backgroundColor='#dc3545'">
                Clear PM/Job Filters
            </button>
        ` : '';

            tbody.innerHTML = `
            <tr>
                <td colspan="8" style="padding: 0; border: none;">
                    <div class="no-results-message">
                        <div class="no-results-box">
                            <div class="no-results-icon">📋</div>
                            <p class="no-results-text">There are no tasks to display</p>
                            <p class="filter-info">where ${filterText}</p>
                            ${clearFiltersButton}
                        </div>
                    </div>
                </td>
            </tr>
        `;
            return;
        }

        tbody.innerHTML = store.filteredTasks
            .map(task => {
                const endDate = new Date(task.EndByDate);
                const jobLinkClass = store.filters.jobNumber === task.JobNumber ? 'job-link active' : 'job-link';
                return `
                <tr class="${task.JobStatusID == 13 ? 'on-hold' : ''}
                          ${task.ProjectStatus == 'REVIEWED' ? 'status-reviewed' : ''}">
                    <td>
                        <a class="${jobLinkClass}" onclick="filterByJobNumber('${task.JobNumber}')">
                            ${task.JobNumber} - ${task.SequenceName}${(task.LotNumber!=0) ? '-'+task.LotNumber : ''}
                        </a>
                    </td>
                    <td>${task.ProjectDescription}${formatNotes(task.Notes)}</td>
                    <td>${((task.Level == 2) ? task.ParentDescription + '->' : '')}${task.taskDescription}</td>
                    <td>${Math.round(parseFloat(task.PercentCompleted) * 100)}%</td>
                    <td>${task.StartByDate}</td>
                    <td class="${endDate < today ? 'past-date' : ''}">${task.EndByDate}</td>
                    <td>${task.ActualDuration}</td>
                    <td>${task.PM}</td>
                </tr>
            `;
            }).join('');
    }

    // Add this new function to handle clearing persistent filters
    function clearPersistentFilters() {
        // Clear the filters from store
        store.filters.pm = null;
        store.filters.jobNumber = null;

        // Clear from localStorage
        localStorage.removeItem('selectedPM');
        localStorage.removeItem('selectedJobNumber');

        // Update the UI buttons
        document.querySelectorAll('.pm-button').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.job-button').forEach(btn => btn.classList.remove('active'));

        // Reapply filters to update the display
        applyFilters();
    }

    function updatePMButtons() {
        const pms = [...new Set(store.allTasks.map(task => task.PM))].sort();
        const pmButtons = document.getElementById('pmButtons');

        pmButtons.innerHTML = pms
            .map(pm => `
                <button class="pm-button ${store.filters.pm === pm ? 'active' : ''}"
                        data-pm="${pm}">${pm}</button>
            `).join('') + '<button class="pm-button" id="clearPMFilter">Clear PM</button>';

        pmButtons.addEventListener('click', (e) => {
            if (e.target.classList.contains('pm-button')) {
                const pm = e.target.dataset.pm;
                document.querySelectorAll('.pm-button').forEach(btn => btn.classList.remove('active'));
                if (pm) {
                    e.target.classList.add('active');
                    store.filters.pm = pm;
                    localStorage.setItem('selectedPM', pm);
                } else {
                    store.filters.pm = null;
                    localStorage.removeItem('selectedPM');
                }
                applyFilters();
            }
        });
    }

    function updateJobButtons() {
        // Get unique job numbers from all tasks
        const jobNumbers = [...new Set(store.allTasks.map(task => task.JobNumber))].sort();
        const jobButtons = document.getElementById('jobButtons');

        jobButtons.innerHTML = jobNumbers
            .map(jobNumber => `
                <button class="job-button ${store.filters.jobNumber === jobNumber ? 'active' : ''}"
                        data-job="${jobNumber}">${jobNumber}</button>
            `).join('') + '<button class="job-button" id="clearJobFilter">Clear Job</button>';

        jobButtons.addEventListener('click', (e) => {
            if (e.target.classList.contains('job-button')) {
                const jobNumber = e.target.dataset.job;
                document.querySelectorAll('.job-button').forEach(btn => btn.classList.remove('active'));
                if (jobNumber) {
                    e.target.classList.add('active');
                    store.filters.jobNumber = jobNumber;
                    localStorage.setItem('selectedJobNumber', jobNumber);
                } else {
                    store.filters.jobNumber = null;
                    localStorage.removeItem('selectedJobNumber');
                }
                applyFilters();
            }
        });
    }

    function updateScheduleFilter() {
        const scheduleMap = new Map();
        store.filteredTasks.forEach(task => {
            scheduleMap.set(task.TaskPath, (scheduleMap.get(task.TaskPath) || 0) + 1);
        });

        const scheduleFilter = document.getElementById('scheduleFilter');
        const currentValue = scheduleFilter.value;

        const options = ['<option value="">All Tasks</option>'];
        Array.from(scheduleMap.entries())
            .sort((a, b) => a[0].localeCompare(b[0]))
            .forEach(([schedule, count]) => {
                options.push(`<option value="${schedule}">${schedule} (${count})</option>`);
            });

        scheduleFilter.innerHTML = options.join('');

        if (currentValue && scheduleMap.has(currentValue)) {
            scheduleFilter.value = currentValue;
        }
    }

    // Utility Functions
    function selectResource(resourceId) {
        // Clear all lower-level filters except PM and JobNumber
        store.filters = {
            pm: store.filters.pm,
            jobNumber: store.filters.jobNumber,
            breakdown: '',
            schedule: '',
            showCompleted: false
        };

        // Reset UI elements
        document.getElementById('breakdownFilter').value = '';
        document.getElementById('scheduleFilter').value = '';
        document.getElementById('completionFilter').checked = false;

        // Update resource selection
        store.selectedResource = resourceId;
        document.querySelectorAll('.resource-item').forEach(item => item.classList.remove('active'));
        document.querySelector(`.resource-item[data-id="${resourceId}"]`)?.classList.add('active');
        loadTasks(resourceId);
    }

    function filterByJobNumber(jobNumber) {
        if (store.filters.jobNumber === jobNumber) {
            // If clicking the same job number, clear the filter
            store.filters.jobNumber = null;
            localStorage.removeItem('selectedJobNumber');
        } else {
            // Set the new job number filter
            store.filters.jobNumber = jobNumber;
            localStorage.setItem('selectedJobNumber', jobNumber);
        }

        // Update the job buttons to reflect the new selection
        updateJobButtons();
        applyFilters();
    }

    function formatNotes(notes) {
        if (!notes || !notes.length) return '';

        return '<br><strong>Notes:</strong><br>' +
            notes.map((note, index) => {
                const date = new Date(note.time).toLocaleString();
                return `${index + 1}. ${note.NoteText} - ${date}`;
            }).join('<br>');
    }

    // Documentation Functions
    async function showDocumentation() {
        try {
            const response = await fetch('resources-guide.md');
            if (!response.ok) {
                throw new Error('Failed to load documentation');
            }
            const markdown = await response.text();
            document.getElementById('markdown-content').innerHTML = marked.parse(markdown);
            document.getElementById('docs-modal').classList.add('active');
        } catch (error) {
            console.error('Error loading documentation:', error);
            alert('Failed to load documentation. Please try again later.');
        }
    }

    function closeModal() {
        document.getElementById('docs-modal').classList.remove('active');
    }

    // Modal Event Listeners
    document.getElementById('docs-modal').addEventListener('click', function(event) {
        if (event.target === this) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && document.getElementById('docs-modal').classList.contains('active')) {
            closeModal();
        }
    });

    // Initialize the application when DOM is ready
    document.addEventListener('DOMContentLoaded', initializeApp);
</script>
</body>
</html>