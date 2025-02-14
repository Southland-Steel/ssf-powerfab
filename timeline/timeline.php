<!DOCTYPE html>
<html>
<head>
    <title>Project Manager Interface</title>
    <style>
        .help-button {
            position: fixed;
            top: 50px;
            right: 27px;
            z-index: 1000;
            padding: 3px 16px;
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
        .docs-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1001;
        }

        .docs-modal.active {
            display: block;
        }

        .docs-modal-content {
            position: relative;
            background-color: white;
            margin: 50px auto;
            padding: 20px;
            width: 80%;
            max-width: 800px;
            max-height: 80vh;
            overflow-y: auto;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .close-btn {
            position: absolute;
            right: 10px;
            top: 10px;
            font-size: 24px;
            cursor: pointer;
            border: none;
            background: none;
        }
    </style>
    <link rel="stylesheet" href="timeline.css?v=<?= filemtime('timeline.css') ?>">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/marked/4.0.2/marked.min.js"></script>
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
<div class="filter-container" id="filterButtons"></div>
<div class="gantt-container" id="ganttChart"></div>

<div id="hoursModal" class="hours-modal">
    <div class="hours-modal-content">
        <button class="filter-btn" onclick="closeHoursModal()">Close</button>
        <table class="hours-table">
            <thead>
            <tr>
                <th>Week</th>
                <th>Total Hours</th>
                <th>Sequence Hours from Workpackage Data</th>
            </tr>
            </thead>
            <tbody id="hoursTableBody"></tbody>
        </table>
    </div>
</div>

<div id="docs-modal" class="docs-modal">
    <div class="docs-modal-content">
        <button class="close-btn" onclick="closeDocModal()">&times;</button>
        <div id="markdown-content"></div>
    </div>
</div>

<script>

    class GanttChart {
        constructor(containerId) {
            this.container = document.getElementById(containerId);
            this.init();
        }

        init() {
            Promise.all([
                this.fetchData(),
                this.fetchWorkpackages()
            ])
                .then(([timelineData, workpackagesData]) => {
                    this.setupData(timelineData);
                    this.workpackages = workpackagesData;
                    this.createTimeline();
                    this.createProjectRows();
                    this.createFilterButtons();
                    this.updateGanttLabels();
                })
                .catch(error => {
                    console.error('Error initializing Gantt chart:', error);
                    this.container.innerHTML = 'Error loading data. Please try again later.';
                });
        }

        fetchWorkpackages() {
            return fetch('ajax_get_ssf_timelinefabrication_workpackages.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                });
        }

        createWorkpackagePoints(sequence) {
            if (!this.workpackages) return '';

            const sequenceWPs = this.workpackages.filter(wp =>
                wp.jobNumber === sequence.project &&
                wp.sequence === sequence.sequence
            );

            return sequenceWPs.map(wp => {
                const wpDate = new Date(wp.completionfriday);
                wpDate.setDate(wpDate.getDate() - 2); // Shift 2 days left
                const position = this.calculatePosition(wpDate);

                let statusClass = wp.onHold ? 'on-hold' :
                                    wp.released ? 'released' :
                                        'not-released';

                                return `
                            <div
                                class="wp-point ${statusClass}"
                                style="left: ${position}%"
                                data-wp-id="${wp.workPackageId}"
                                title="[${wp.workWeek}] WP: ${wp.workPackageNumber} - WPQty: ${wp.wpAssemblyQty} - Weight: ${wp.grossWeight} lbs - Hours: ${wp.hours} - Status: ${wp.workPackageStatus}"
                            ></div>
                        `;
                            }).join('');
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
            const maxHours = Math.max(...this.data.sequences.map(s => Number(s.fabrication.hours) || 0));

            this.data.sequences.forEach(sequence => {
                const row = document.createElement('div');
                row.className = 'gantt-row';

                const startPosition = this.calculatePosition(sequence.fabrication.start);
                const width = this.calculateWidth(sequence.fabrication.start, sequence.fabrication.end);
                const opacity = (Number(sequence.fabrication.hours) || 0) / maxHours * 0.9 + 0.1;

                // Find work packages for this sequence
                const sequenceWPs = this.workpackages.filter(wp =>
                    wp.jobNumber === sequence.project &&
                    wp.sequence === sequence.sequence
                );

                // Calculate brackets based on work package dates
                let workPackageBrackets = '';
                if (sequenceWPs.length > 0) {
                    const startDate = new Date(Math.min(...sequenceWPs.map(wp => new Date(wp.startDate))));
                    const endDate = new Date(Math.max(...sequenceWPs.map(wp => new Date(wp.endDate))));

                    workPackageBrackets = `
                <div class="wp-bracket wp-start"
                     style="left: ${this.calculatePosition(startDate)}%;"
                     title="Start Date: ${startDate.toISOString().split('T')[0]}"></div>
                <div class="wp-bracket wp-end"
                     style="left: ${this.calculatePosition(endDate)}%;"
                     title="End Date: ${endDate.toISOString().split('T')[0]}"></div>
            `;
                }

                const hasWorkPackage = sequenceWPs.length > 0;

                const isCategorizeOverdue = new Date(sequence.categorize.start) < new Date() && sequence.categorize.percentage < 100;

                row.innerHTML = `
            <div class="gantt-labels" data-rowid="${sequence.project}:${sequence.sequence}" title="ScheduleTaskID: ${sequence.fabrication.id}, RowID: ${sequence.project}:${sequence.sequence}">
                <div class="gantt-rowtitle"><a href="sequence_status/sequence_status.php?jobNumber=${sequence.project}&sequenceName=${sequence.sequence}">${sequence.project}: ${sequence.sequence}</a></div>
                <div class="gantt-pmname">PM: ${sequence.pm}</div>
            </div>
            <div class="gantt-chart" style="background-color: rgba(25, 50, 100, ${opacity})" title="Hours: ${Number(sequence.fabrication.hours).toLocaleString()} (${Math.round(opacity*100)}% of largest sequence)">
                <div class="categorize">Categorize by: ${sequence.categorize.start}</div>
                ${workPackageBrackets}
                ${this.createDateLines()}
                <div class="hover-line"></div>
                <div class="hover-date"></div>
                <div class="gantt-bar ${hasWorkPackage ? 'velvet' : ''}" style="left: ${startPosition}%; width: ${width}%">
                    <div class="gantt-bar-text">${sequence.fabrication.description} - Start: ${sequence.fabrication.start} - End: ${sequence.fabrication.end}</div>
                    <div class="gantt-bar-percentage" style="width:${sequence.fabrication.percentage}%">
                        <div class="gantt-bar-percentage-text">${sequence.fabrication.percentage}%</div>
                    </div>
                </div>
                ${this.createWorkpackagePoints(sequence)}
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

            const hoursBtn = document.createElement('button');
            hoursBtn.className = 'filter-btn view-hours-btn';
            hoursBtn.textContent = 'View Weekly Hours';
            hoursBtn.onclick = () => {
                const weeklyData = this.calculateWeeklyHours();
                this.populateHoursTable(weeklyData);
                document.getElementById('hoursModal').style.display = 'block';
            };
            filterContainer.appendChild(hoursBtn);

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
        async showWeeklyHours() {
            const weeklyData = this.calculateWeeklyHours(this.data.sequences);
            this.populateHoursTable(weeklyData);
            document.getElementById('hoursModal').style.display = 'block';
        }


        populateHoursTable(weeklyData) {
            const maxHours = Math.max(...weeklyData.flatMap(week =>
                week.workpackages.map(wp => wp.hours)
            ));

            const getBallSize = (hours) => {
                const maxSize = 24;
                return Math.max(Math.sqrt(hours / maxHours) * maxSize, 6);
            };

            const tbody = document.getElementById('hoursTableBody');
            tbody.innerHTML = weeklyData.map((week, index) => `
        <tr>
            <td>${this.getYearWeek(new Date(week.date))}</td>
            <td>${Math.round(week.totalHours)}</td>
            <td>${week.workpackages.map(wp => {
                const ballSize = getBallSize(wp.hours);
                return `<div
                    class="wp-button"
                    style="--ball-size: ${ballSize}px;"
                    title="Job: ${wp.jobNumber}
Sequence: ${wp.sequence}
Hours: ${Math.round(wp.hours)}"
                >WP${wp.workPackageNumber}</div>`;
            }).join('')}</td>
        </tr>
    `).join('');
        }

        getWeekNumber(date) {
            const firstDayOfYear = new Date(date.getFullYear(), 0, 1);
            const days = Math.floor((date - firstDayOfYear) / (24 * 60 * 60 * 1000));
            return Math.ceil((days + firstDayOfYear.getDay() + 1) / 7);
        }

        getYearWeek(date) {
            const year = date.getFullYear().toString().slice(-2);
            const week = this.getWeekNumber(date).toString().padStart(2, '0');
            const monthName = date.toLocaleString('default', { month: 'long' });
            return `${year}${week} - ${monthName}`;
        }

        calculateWeeklyHours() {
            if (!this.workpackages || this.workpackages.length === 0) {
                console.error('No workpackages data available');
                return [];
            }

            const convertWorkWeekToDate = (workWeek) => {
                const year = '20' + workWeek.slice(0, 2);
                const week = parseInt(workWeek.slice(2));
                const firstDayOfYear = new Date(year, 0, 1);
                const dayOffset = (week - 1) * 7;
                const targetDate = new Date(firstDayOfYear);
                targetDate.setDate(firstDayOfYear.getDate() + dayOffset);
                return targetDate;
            };

            const workpackagesByWeek = {};

            // First pass: Group workpackages by week and workpackage number
            this.workpackages.forEach(wp => {
                const wpWeek = wp.workWeek;
                if (!workpackagesByWeek[wpWeek]) {
                    workpackagesByWeek[wpWeek] = new Map(); // Use Map to store unique WP numbers
                }

                // If this WP number already exists for this week, skip it
                if (!workpackagesByWeek[wpWeek].has(wp.workPackageNumber)) {
                    workpackagesByWeek[wpWeek].set(wp.workPackageNumber, wp);
                }
            });

            // Convert to weekly data format
            const weeklyData = Object.entries(workpackagesByWeek).map(([workWeek, weekWPs]) => {
                const weekDate = convertWorkWeekToDate(workWeek);
                const uniqueWorkpackages = Array.from(weekWPs.values());

                return {
                    date: weekDate,
                    totalHours: uniqueWorkpackages.reduce((sum, wp) => sum + Number(wp.hours), 0),
                    workpackages: uniqueWorkpackages.map(wp => ({
                        workPackageNumber: wp.workPackageNumber,
                        hours: Number(wp.hours),
                        jobNumber: wp.jobNumber,
                        sequence: wp.sequence
                    }))
                };
            });

            return weeklyData.sort((a, b) => a.date - b.date);
        }

        updateGanttLabels() {
            // Get all labels at once and create a Map for faster lookups
            const labelElements = new Map();
            document.querySelectorAll(".gantt-labels[data-rowid]").forEach(el => {
                const rowId = el.getAttribute("data-rowid");
                if (rowId) {
                    const [JobNumber, SequenceName] = rowId.split(":");
                    labelElements.set(rowId, {
                        element: el,
                        JobNumber,
                        SequenceName,
                        chartElement: el.nextElementSibling // Get the gantt-chart div
                    });
                }
            });

            if (labelElements.size === 0) return;

            // Create the request payload
            const jobSequences = Array.from(labelElements.values()).map(({JobNumber, SequenceName}) => ({
                JobNumber,
                SequenceName
            }));

            fetch("ajax_get_ssf_timelinefabrication_catstatus.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ jobSequences })
            })
                .then(response => response.json())
                .then(data => {
                    if (!data || data.error) {
                        console.error("Error fetching data:", data.error);
                        return;
                    }

                    // Process all entries at once
                    data.forEach(entry => {
                        const rowId = `${entry.JobNumber}:${entry.SequenceName}`;
                        const labelInfo = labelElements.get(rowId);
                        if (!labelInfo) return;

                        const element = labelInfo.element;
                        const chartElement = labelInfo.chartElement;
                        const totalItems = entry.TotalItems || 0;

                        // Calculate percentages once
                        const iffPercentage = totalItems > 0 ?
                            ((entry.IFFCount / totalItems) * 100).toFixed(1) : '0.0';
                        const categorizedPercentage = totalItems > 0 ?
                            ((entry.CategorizedCount / totalItems) * 100).toFixed(1) : '0.0';

                        // Set title text
                        element.title = `Total Items: ${totalItems}\n` +
                            `IFF Status: ${entry.IFFCount} - IFF (${iffPercentage}%)\n` +
                            `Categorization Status: ${entry.CategorizedCount} - Categorized (${categorizedPercentage}%)`;

                        // Update classes
                        element.classList.remove("categorize-success", "categorize-danger");
                        element.style.backgroundImage = "none";

                        element.classList.add(entry.CategorizedCount > 0 ? "categorize-success" : "categorize-danger");

                        if (entry.NotIFFCount > 0) {
                            element.style.backgroundImage = "repeating-linear-gradient(45deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.1) 5px, transparent 5px, transparent 10px)";
                        }

                        // Remove any existing indicators
                        chartElement.querySelectorAll('.indicator').forEach(ind => ind.remove());

                        // Calculate positions
                        const sequence = this.data.sequences.find(s =>
                            s.project === entry.JobNumber && s.sequence === entry.SequenceName);
                        if (sequence) {
                            const iffPosition = sequence.iff.percentage == -1 ? 0 :
                                this.calculatePosition(sequence.iff.start);
                            const nsiPosition = sequence.nsi.percentage == -1 ? 0 :
                                this.calculatePosition(sequence.nsi.start);

                            // Create new indicators
                            const iffIndicator = document.createElement('div');
                            iffIndicator.className = `indicator iff-indicator ${Number(iffPercentage) > 98 ? 'good' : 'bad'}`;
                            iffIndicator.style.left = `${iffPosition}%`;
                            iffIndicator.title = `IFF: ${sequence.iff.start}`;
                            iffIndicator.textContent = `${iffPercentage}%`;

                            const nsiIndicator = document.createElement('div');
                            nsiIndicator.className = `indicator nsi-indicator ${sequence.nsi.percentage > 98 ? 'good' : 'bad'}`;
                            nsiIndicator.style.left = `${nsiPosition}%`;
                            nsiIndicator.title = `NSI: ${sequence.nsi.start}`;
                            nsiIndicator.textContent = `${sequence.nsi.percentage}%`;

                            chartElement.appendChild(iffIndicator);
                            chartElement.appendChild(nsiIndicator);
                        }
                    });
                })
                .catch(error => console.error("Error fetching IFF data:", error));
        }

    }

    function closeHoursModal() {
        document.getElementById('hoursModal').style.display = 'none';
    }



    // Initialize the chart when the page loads
    document.addEventListener('DOMContentLoaded', () => {
        const gantt = new GanttChart('ganttChart');
    });
</script>
<script>
    // Add the export button to the filter container
    const filterContainer = document.querySelector('.filter-container');
    const exportButton = document.createElement('button');
    exportButton.className = 'filter-btn';
    exportButton.textContent = 'Export Data';
    exportButton.onclick = exportGanttData;
    filterContainer.appendChild(exportButton);

    function exportGanttData() {
        // Get all gantt rows except the timeline row
        const rows = Array.from(document.querySelectorAll('.gantt-row')).filter(row =>
            !row.closest('.timeline-row')
        );

        // Extract data from each row
        const data = rows.map(row => {
            const titleElement = row.querySelector('.gantt-rowtitle');
            const pmElement = row.querySelector('.gantt-pmname');
            const barElement = row.querySelector('.gantt-bar-text');
            const percentageElement = row.querySelector('.gantt-bar-percentage-text');
            const labelsDiv = row.querySelector('.gantt-labels');

            // Get workpackage brackets data
            const wpStartBracket = row.querySelector('.wp-bracket.wp-start');
            const wpEndBracket = row.querySelector('.wp-bracket.wp-end');
            const wpStartDate = wpStartBracket ? wpStartBracket.title.replace('Start Date: ', '') : '';
            const wpEndDate = wpEndBracket ? wpEndBracket.title.replace('End Date: ', '') : '';

            // Get project and sequence
            const [project, sequence] = titleElement ?
                titleElement.textContent.split(':').map(s => s.trim()) : ['', ''];

            // Get PM name
            const pm = pmElement ?
                pmElement.textContent.replace('PM:', '').trim() : '';

            // Get categorize info
            const categorizeInfo = categorizeElement ?
                categorizeElement.title : '';

            // Get bar info
            const barInfo = barElement ?
                barElement.textContent : '';

            // Get completion percentage
            const percentage = percentageElement ?
                percentageElement.textContent : '';

            // Get categorize status
            const categorizeStatus = labelsDiv?.classList.contains('categorize-success') ? 'Success' :
                labelsDiv?.classList.contains('categorize-danger') ? 'Danger' : 'Normal';

            return {
                project,
                sequence,
                pm,
                categorizeInfo,
                barInfo,
                percentage,
                categorizeStatus,
                wpStartDate,
                wpEndDate
            };
        });

        // Create CSV content
        const headers = [
            'Project',
            'Sequence',
            'PM',
            'Categorize Info',
            'Bar Info',
            'Percentage',
            'Categorize Status',
            'WP Start Date',
            'WP End Date'
        ];

        const csvContent = [
            headers.join(','),
            ...data.map(row => [
                row.project,
                row.sequence,
                row.pm,
                `"${row.categorizeInfo.replace(/"/g, '""')}"`,
                `"${row.barInfo.replace(/"/g, '""')}"`,
                row.percentage,
                row.categorizeStatus,
                row.wpStartDate,
                row.wpEndDate
            ].join(','))
        ].join('\n');

        // Create and trigger download
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'gantt_data.csv';
        link.style.display = 'none';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    async function showDocumentation() {
        try {
            const response = await fetch('doc_timeline.md');
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const markdown = await response.text();
            const markdownContent = document.getElementById('markdown-content');
            if (!markdownContent) {
                throw new Error('Markdown content container not found');
            }
            markdownContent.innerHTML = marked.parse(markdown);
            const modal = document.getElementById('docs-modal');
            if (!modal) {
                throw new Error('Modal container not found');
            }
            modal.classList.add('active');
        } catch (error) {
            console.error('Error loading documentation:', error);
            alert('Failed to load documentation: ' + error.message);
        }
    }
    function closeDocModal() {
        document.getElementById('docs-modal').classList.remove('active');
    }
</script>
</body>
</html>