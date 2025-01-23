<!DOCTYPE html>
<html>
<head>
    <title>Project Manager Interface</title>
    <style>
        body{
            background-color: #6c6c6c;
            font-family:system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", "Liberation Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
        }
        .gantt-container {
            position: relative;
            margin: 2px;
            overflow-x: auto;
            height: 98vh;
        }
        .timeline-row {
            position: sticky;
            top: 0;
            z-index: 10;
            background: #2b2d30;
            color: #456ca0;
        }
        .gantt-row {
            height: 40px;
            margin-bottom: 0;
            display: flex;
            align-items: center;
            border-bottom: 1px solid #2b2d30;
        }
        .gantt-labels {
            width: 200px;
            padding-right: 15px;
            left: 0;
            background: #2b2d30;
            color: #c1c1c1;
            border-right: 0;
            height: 39px;
        }
        .categorize-success{
            background-color: green;
        }
        .categorize-danger{
            background-color: red;
        }
        .gantt-rowtitle{
            font-weight: bold;
            font-size: medium;
            padding-left: 5px;
        }
        .gantt-pmname{
            font-size: small;
            padding-left: 15px;
        }
        .gantt-chart {
            position: relative;
            width: calc(100% - 200px);
            min-height: 40px;
            overflow: visible;
        }
        .categorize{
            position: absolute;
            left:0;
            bottom:0;
            font-size: small;
            padding-left: 3px;
            padding-bottom: 3px;
            color: #c1c1c1;
        }
        .gantt-timeline {
            position: relative;
            height: 38px;
            border-bottom: 1px solid #6c6c6c;
            margin-bottom: 1px;
            padding: 1px;
            width: 100%;
        }
        .timeline-marker {
            position: absolute;
            border-left: 1px solid #6c6c6c;
            height: 100%;
            font-size: 12px;
            color: #666;
            padding-top: 5px;
        }
        .gantt-bar {
            position: absolute;
            height: 35px;
            background: #007bff;
            border-radius: 4px;
            top: 2px;
            overflow: visible;

        }
        .gantt-bar-percentage {
            position: absolute;
            left: 0;
            bottom: 2px;
            color: white;
            font-size: 0.7em;
            white-space: nowrap;
            height: 5px;
            background-color: black;
        }
        .gantt-bar-percentage-text{
            position: absolute;
            text-align: right;
            width: 90%;
            bottom:-4px;
            left:0;
        }
        .gantt-bar-text{
            padding: 7px 8px;
            white-space: nowrap;
            font-size: 0.7em;
            color: white;
        }
        .velvet {
            background: #940045;
        }
        .vline-marker {
            position: absolute;
            top: 0;
            bottom: 0;
            width: 1px;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }
        .today-line {
            background-color: #0d6efd;
        }
        .horizon-line {
            background-color: #dc3545;
        }
        .wp-bracket{
            position: absolute;
            top:0;
            width: 5px;
            height: 10px;
            border:3px solid #000;
            border-bottom: none;
            z-index: 3;
            font-size: .6rem;
            white-space: nowrap;
        }
        .wp-start{
            border-right: none;
            transform: translateX(-2px);
        }
        .wp-end{
            border-left: none;
            transform: translateX(-6px);
        }
        .indicator{
            position: absolute;
            z-index: 5;
            font-size: .7rem;
            text-align: right;
            background-color: black;
        }
        .iff-indicator{
            width:18px;
            height: 18px;
            top:9px;
            border-radius: 50%;
            transform: translate(-50%, -50%);
            color: white;
        }
        .nsi-indicator{
            width:16px;
            height: 16px;
            top:29px;
            transform: translate(-50%, -50%) rotate(45deg);
            color:white;
        }
        .indicator.good{
            background-color: #00ff00;
            color:black;
        }
        .indicator.bad{
            background-color: #dc3545;
            color:black;
        }
        .filter-container {
            position: sticky;
            top: 0;
            z-index: 20;
            background: #2b2d30;
            padding: 10px;
            display: flex;
            gap: 10px;
            border-bottom: 1px solid #456ca0;
        }
        .filter-btn {
            padding: 5px 10px;
            background: #456ca0;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .filter-btn.active {
            background: #0d6efd;
        }
        .filter-btn:hover {
            background: #0d6efd;
        }
        .gantt-row.hidden {
            display: none;
        }
        .workload-bar {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            height: 20px;
            width: 200px;
            background: #eee;
            border: 1px solid #456ca0;
        }

        .workload-bar-fill {
            height: 100%;
            background-color: #456ca0;
        }
    </style>
</head>
<body>
<div class="filter-container" id="filterButtons"></div>
<div class="gantt-container" id="ganttChart"></div>
<script>

    class GanttChart {
        constructor(containerId) {
            this.container = document.getElementById(containerId);
            this.init();
        }

        init() {
            this.fetchData()
                .then(data => {
                    this.setupData(data);
                    this.createTimeline();
                    this.createProjectRows();
                    this.createFilterButtons();
                })
                .catch(error => {
                    console.error('Error initializing Gantt chart:', error);
                    this.container.innerHTML = 'Error loading data. Please try again later.';
                });
        }

        fetchData() {
            return fetch('ajax_ssf_get_timelinefabrication.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                });
        }

        setupData(data) {
            data.sequences.sort((a, b) => {
                const startCompare = new Date(a.fabrication.start) - new Date(b.fabrication.start);
                return startCompare || new Date(a.fabrication.end) - new Date(b.fabrication.end);
            });

            this.data = data;
            const startDate = new Date(data.dateRange.start);
            this.startDate = new Date(startDate.getFullYear(), startDate.getMonth(), 1);

            const endDate = new Date(data.dateRange.end);
            this.endDate = new Date(endDate.getFullYear(), endDate.getMonth() + 1, 0);

            this.totalDays = (this.endDate - this.startDate) / (1000 * 60 * 60 * 24);
        }

        formatDate(date) {
            const d = new Date(date);
            const year = d.getFullYear();
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        calculatePosition(date) {
            const dateObj = new Date(date);
            const daysSinceStart = (dateObj - this.startDate) / (1000 * 60 * 60 * 24);
            const position = (daysSinceStart / this.totalDays) * 100;
            return position;
        }

        calculateWidth(startDate, endDate) {
            const start = this.calculatePosition(startDate);
            const end = this.calculatePosition(endDate);
            return end - start;
        }

        createTimeline() {
            const timelineRow = document.createElement('div');
            timelineRow.className = 'timeline-row';

            timelineRow.innerHTML = `
                <div class="gantt-row">
                    <div class="gantt-labels"></div>
                    <div class="gantt-chart">
                        <div class="gantt-timeline">
                            ${this.generateMonthMarkers()}
                        </div>
                    </div>
                </div>
            `;

            this.container.appendChild(timelineRow);
        }

        generateMonthMarkers() {
            const months = [];
            let currentDate = new Date(this.startDate);

            // Ensure we start on the first day of the month
            currentDate.setDate(1);

            while (currentDate <= this.endDate) {
                // Skip if current date is before start date
                if (currentDate >= this.startDate) {
                    const formattedDate = this.formatDate(currentDate);
                    const position = this.calculatePosition(formattedDate);
                    const monthLabel = currentDate.toLocaleDateString('en-US', {
                        month: 'short',
                        year: 'numeric'
                    });

                    months.push(`
                        <div class="timeline-marker" style="left: ${position}%">
                            ${monthLabel}
                        </div>
                    `);
                }

                // Move to first day of next month
                currentDate = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 1);
            }

            return months.join('');
        }
        createDateLines() {
            const today = new Date('2025-01-22'); // Set to your current date
            const horizonDate = new Date(today);
            horizonDate.setDate(today.getDate() + 56);

            const todayPosition = this.calculatePosition(this.formatDate(today));
            const horizonPosition = this.calculatePosition(this.formatDate(horizonDate));

            return `
                <div class="vline-marker today-line" style="left: ${todayPosition}%"></div>
                <div class="vline-marker horizon-line" style="left: ${horizonPosition}%"></div>
            `;
        }

        createProjectRows() {
            const fragment = document.createDocumentFragment();

            const maxHours = Math.max(...this.data.sequences.map(s => s.fabrication.hours));

            this.data.sequences.forEach(sequence => {
                const row = document.createElement('div');
                row.className = 'gantt-row';

                const startPosition = this.calculatePosition(sequence.fabrication.start);
                const width = this.calculateWidth(sequence.fabrication.start, sequence.fabrication.end);

                const opacity = (sequence.fabrication.hours / maxHours) * 0.9 + 0.1; // Range from 0.1 to 1.0

                // Calculate WP positions if sequence has work package
                const wpBrackets = sequence.hasWorkPackage ? `
            <div class="wp-bracket wp-start" style="left: ${this.calculatePosition(sequence.wp.start)}%;" title="Start Date: ${sequence.wp.start}"></div>
            <div class="wp-bracket wp-end" style="left: ${this.calculatePosition(sequence.wp.end)}%;" title="End Date: ${sequence.wp.end}"></div>
        ` : '';

                const isCategorizeOverdue = new Date(sequence.categorize.start) < new Date() && sequence.categorize.percentage < 100;
                const categorizeClass = sequence.categorize.percentage === 100 ? 'categorize-success' :
                    (isCategorizeOverdue ? 'categorize-danger' : '');

                const nsiPosition = this.calculatePosition(sequence.nsi.start);
                const iffPosition = this.calculatePosition(sequence.iff.start);

                row.innerHTML = `
            <div class="gantt-labels ${categorizeClass}">
                <div class="gantt-rowtitle">${sequence.project}: ${sequence.sequence}</div>
                <div class="gantt-pmname">PM: ${sequence.pm}</div>
            </div>
            <div class="gantt-chart" style="background-color: rgba(25, 50, 100, ${opacity})" title="Hours: ${sequence.fabrication.hours} (${Math.round(opacity*100)}% of largest sequence)">
                <div class="categorize" title="${sequence.categorize.start} (${sequence.categorize.percentage}%)">Categorize by: ${sequence.categorize.start} (${sequence.categorize.percentage}%)</div>
                ${wpBrackets}
                <div class="indicator iff-indicator good" style="left:${iffPosition}%;" title="IFF: ${sequence.iff.start}">${sequence.iff.percentage}%</div>
                <div class="indicator nsi-indicator bad" style="left:${nsiPosition}%;" title="NSI: ${sequence.nsi.start}">${sequence.nsi.percentage}%</div>
                ${this.createDateLines()}
                <div class="hover-line"></div>
                <div class="hover-date"></div>
                <div class="gantt-bar ${sequence.hasWorkPackage ? 'velvet' : ''}" style="left: ${startPosition}%; width: ${width}%">
                    <div class="gantt-bar-text">${sequence.fabrication.description} - Start: ${sequence.fabrication.start} - End: ${sequence.fabrication.end}</div>
                    <div class="gantt-bar-percentage" style="width:${sequence.fabrication.percentage}%">
                        <div class="gantt-bar-percentage-text">${sequence.fabrication.percentage}%</div>
                    </div>
                </div>
            </div>
        `;

                fragment.appendChild(row);
            });

            this.container.appendChild(fragment);
        }
        createFilterButtons() {
            const filterContainer = document.getElementById('filterButtons');

            // Create "Show All" button
            const showAllBtn = document.createElement('button');
            showAllBtn.className = 'filter-btn active';
            showAllBtn.textContent = 'Show All Projects';
            showAllBtn.onclick = () => this.filterProjects('all');
            filterContainer.appendChild(showAllBtn);

            // Get unique project numbers
            const projects = [...new Set(this.data.sequences.map(seq => seq.project))];

            // Create button for each project
            projects.forEach(project => {
                const btn = document.createElement('button');
                btn.className = 'filter-btn';
                btn.textContent = project;
                btn.onclick = () => this.filterProjects(project);
                filterContainer.appendChild(btn);
            });
        }

        filterProjects(project) {
            // Update button states
            const buttons = document.querySelectorAll('.filter-btn');
            buttons.forEach(btn => {
                btn.classList.toggle('active',
                    (project === 'all' && btn.textContent === 'Show All') ||
                    btn.textContent === project
                );
            });

            // Update row visibility
            const rows = Array.from(document.querySelectorAll('.gantt-row')).filter(row =>
                !row.closest('.timeline-row'));
            rows.forEach(row => {
                const projectNumber = row.querySelector('.gantt-rowtitle')?.textContent.split(':')[0];
                if (project === 'all') {
                    row.classList.remove('hidden');
                } else {
                    row.classList.toggle('hidden', projectNumber !== project);
                }
            });
        }
    }

    // Initialize the chart when the page loads
    document.addEventListener('DOMContentLoaded', () => {
        const gantt = new GanttChart('ganttChart');
    });
</script>
</body>
</html>