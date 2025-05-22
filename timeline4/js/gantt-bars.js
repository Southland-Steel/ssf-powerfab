/**
 * File: js/gantt-bars.js
 * Handles rendering task bars for the Gantt chart
 * Refactored to add percentage badges
 */
GanttChart.Bars = (function() {
    'use strict';

    /**
     * Generate task bars based on data
     * @param {Array} tasks - Array of task objects
     * @param {string} minDate - Start date for timeline
     * @param {string} maxDate - End date for timeline
     */
    function generateBars(tasks, minDate, maxDate) {
        const $itemRowsContainer = $(GanttChart.Core.getConfig().itemRows);
        $itemRowsContainer.empty();

        // Create a copy of the tasks array to avoid modifying the original
        const tasksCopy = [...tasks];

        // Sort strictly by start date first, then end date - using GanttChart.Core.parseDate
        tasksCopy.sort((a, b) => {
            // Use GanttChart.Core.parseDate for consistent date parsing
            const aStart = GanttChart.Core.parseDate(a.startDate);
            const bStart = GanttChart.Core.parseDate(b.startDate);

            // Compare start dates
            if (aStart.getTime() !== bStart.getTime()) {
                return aStart.getTime() - bStart.getTime();
            }

            // If start dates are equal, compare end dates
            const aEnd = GanttChart.Core.parseDate(a.endDate);
            const bEnd = GanttChart.Core.parseDate(b.endDate);
            return aEnd.getTime() - bEnd.getTime();
        });

        // Create a row for each task in the sorted array
        tasksCopy.forEach(task => {
            const $row = createSingleTaskRow(task);
            $itemRowsContainer.append($row);
        });
    }

    /**
     * Create a single task row
     * @param {Object} task - Task data
     * @return {jQuery} Task row element
     */
    function createSingleTaskRow(task) {
        // Create row element
        const $row = $('<div></div>')
            .addClass('gantt-row task-row')
            .attr('data-task-id', task.id)
            .attr('data-project', task.project)
            .attr('data-start-date', task.startDate)
            .attr('data-end-date', task.endDate)
            .attr('data-level', task.level);  // Add level attribute for filtering

        // Create labels section
        const $labels = $('<div class="gantt-labels"></div>');

        // Check for client approval complete and add special class
        const clientApproval = task.ClientApprovalPercentComplete || 0;
        $row.attr('data-client-approval', clientApproval);

        // Add client approval badge to labels section

            const clientApprovalBadge = createPercentageBadge(clientApproval, 'client-approval-badge', 'Client Approval (manual entry in project management)')
                .css({
                    'position': 'absolute',
                    'bottom': '5px',
                    'right': '5px',
                    'transform': 'none' // Override the translateX(-50%)
                });
            $labels.append(clientApprovalBadge);


        // Extract project and element from rowGroupId
        let parts = task.RowGroupId ? task.RowGroupId.split('.') : (task.rowGroupId ? task.rowGroupId.split('.') : [task.project, task.description]);
        const project = parts.shift(); // First part is project
        const element = parts.join('.'); // Rest is element path

        // Format dates using GanttChart.Core.formatDate for consistency
        const startFormatted = GanttChart.Core.formatDate(task.startDate);
        const endFormatted = GanttChart.Core.formatDate(task.endDate);

        // Prepare additional metrics if they exist
        let additionalMetrics = '';

        // Check if this is the enhanced data format by checking for new fields
        if (task.PercentageIFF !== undefined || task.HasPCI !== undefined) {
            const percentageIFF = typeof task.PercentageIFF !== 'undefined' ? task.PercentageIFF : (typeof task.percentageIFF !== 'undefined' ? task.percentageIFF : 0);
            const percentageIFA = typeof task.PercentageIFA !== 'undefined' ? task.PercentageIFA : (typeof task.percentageIFA !== 'undefined' ? task.percentageIFA : 0);

            if (percentageIFF > 0 || percentageIFA > 0) {
                additionalMetrics = `<div class="task-metrics">IFF: ${percentageIFF}% | IFA: ${percentageIFA}%</div>`;
            }
        }

        // Get sequence and lot info
        const sequenceName = task.SequenceName || task.sequenceName || element;
        const lotNumber = task.LotNumber || task.lotNumber || null;

        // Build label content
        let labelContent = `
            <div class="gantt-rowtitle">
                <strong class="project-code">${project}</strong>
                <span class="element-name">${sequenceName}${lotNumber ? ' - ' + lotNumber : ''}</span>
                <div class="task-description">${task.taskDescription || task.description || ''}</div>
            </div>
        `;

        $labels.prepend(labelContent);

        // Append labels to row
        $row.append($labels);

        // Create timeline column
        const $timeline = $('<div class="gantt-timeline"></div>');

        // Add today line
        addTodayLine($timeline);

        // Add task bar
        addTaskBar(task, $timeline);

        // Append timeline to row
        $row.append($timeline);

        return $row;
    }

    /**
     * Create a percentage badge with appropriate color coding
     * @param {number} percentage - Percentage value
     * @param {string} badgeClass - Additional CSS class for the badge
     * @param {string} tooltipText - Text to show in tooltip
     * @return {jQuery} Badge element
     */
    function createPercentageBadge(percentage, badgeClass, tooltipText) {
        // Round to 1 decimal place
        const roundedPercentage = Math.round(percentage * 10) / 10;

        // Determine color class based on percentage
        let colorClass = 'badge-danger';  // Red < 5%
        if (roundedPercentage > 99) {
            colorClass = 'badge-success';  // Green > 99%
        } else if (roundedPercentage >= 5) {
            colorClass = 'badge-warning';  // Yellow 5% - 99%
        }

        // Create badge element
        const $badge = $(`<span class="percentage-badge ${badgeClass} ${colorClass}" 
                            title="${tooltipText}: ${roundedPercentage}%">
                            ${roundedPercentage}%
                          </span>`);

        return $badge;
    }

    /**
     * Add today's line to the timeline
     * @param {jQuery} $timeline - Timeline element
     */
    function addTodayLine($timeline) {
        // Get today's date
        const today = GanttChart.Core.getToday();

        // Calculate position using TimeUtils
        const todayPos = GanttChart.TimeUtils.dateToPosition(today);

        if (todayPos >= 0 && todayPos <= 100) {
            // Create a today line
            const $todayLine = $('<div class="current-date-line"></div>')
                .css('left', todayPos + '%')
                .attr('title', 'Today: ' + GanttChart.Core.formatDate(today));

            $timeline.append($todayLine);
        }
    }

    /**
     * Add a task bar to the timeline
     * @param {Object} task - Task data
     * @param {jQuery} $timeline - Timeline element to add bar to
     */
    function addTaskBar(task, $timeline) {
        // Skip if missing dates
        if (!task.startDate || !task.endDate) {
            return;
        }

        // Parse dates using Core.parseDate
        const parsedStartDate = GanttChart.Core.parseDate(task.startDate);
        const parsedEndDate = GanttChart.Core.parseDate(task.endDate);

        if (!parsedStartDate || !parsedEndDate) {
            return;
        }

        // Calculate positions and width
        const startPos = GanttChart.TimeUtils.dateToPosition(parsedStartDate);
        const endPos = GanttChart.TimeUtils.dateToPosition(parsedEndDate);
        const width = Math.max(endPos - startPos, 0.5); // Minimum width of 0.5%

        // Determine bar class based on task status
        let barClass = 'task-bar';
        barClass += task.status ? ` status-${task.status}` : ' status-not-started';

        // Create bar element
        const $bar = $('<div></div>')
            .addClass(barClass)
            .css({
                'left': startPos + '%',
                'width': width + '%'
            })
            .attr('data-task-id', task.id);

        // Create tooltip content - include new metrics if available
        let tooltipContent = `
        ${task.taskDescription || task.description}
        Start: ${GanttChart.Core.formatDate(task.startDate)}
        End: ${GanttChart.Core.formatDate(task.endDate)}
        Progress: ${task.percentage}%
        ${task.hours ? 'Hours: ' + Math.round(task.hours,0) : ''}
    `;

        // Add any enhanced data to tooltip if available
        if (task.PercentageIFF !== undefined || task.percentageIFF !== undefined) {
            const percentageIFF = task.PercentageIFF !== undefined ? task.PercentageIFF : task.percentageIFF;
            const percentageIFA = task.PercentageIFA !== undefined ? task.PercentageIFA : task.percentageIFA;

            tooltipContent += `\nIFF from Tekla Production Control PieceMarks: ${percentageIFF}%\nIFA from Tekla Production Control PieceMarks: ${percentageIFA}%`;

            if (task.ClientApprovalPercentComplete) {
                tooltipContent += `\nClient Approval: ${task.ClientApprovalPercentComplete}%`;
            }

            if (task.DetailingIFFPercentComplete) {
                tooltipContent += `\nDetailing IFF (from status update): ${task.DetailingIFFPercentComplete}%`;
            }
        }

        $bar.attr('title', tooltipContent);

        // Add progress indicator if percentage is valid
        if (task.percentage >= 0) {
            const $progressBar = $('<div class="task-bar-progress"></div>')
                .css('width', task.percentage + '%');
            $bar.append($progressBar);
        }

        // Add task description
        const $taskLabel = $('<div class="task-bar-label"></div>')
            .text(task.taskDescription || task.description || 'Task');
        $bar.append($taskLabel);

        // MODIFIED: Change click event to navigate to sequence_detail.php instead of showing modal
        $bar.on('click', function() {
            // Extract jobNumber from project attribute
            const jobNumber = task.project;

            // Extract sequenceName and lotNumber
            const sequenceName = task.SequenceName || '';
            const lotNumber = task.LotNumber || '';

            // Navigate to sequence_detail.php with parameters
            const url = `sequence_detail.php?jobNumber=${encodeURIComponent(jobNumber)}&sequenceName=${encodeURIComponent(sequenceName)}`;

            // Add lotNumber parameter only if it exists
            const finalUrl = lotNumber ? `${url}&lotNumber=${encodeURIComponent(lotNumber)}` : url;

            window.location.href = finalUrl;
        });

        // Add the bar to the timeline
        $timeline.append($bar);

        // Extract percentage values
        const percentageIFF = typeof task.PercentageIFF !== 'undefined' ? task.PercentageIFF : (typeof task.percentageIFF !== 'undefined' ? task.percentageIFF : 0);
        const percentageIFA = typeof task.PercentageIFA !== 'undefined' ? task.PercentageIFA : (typeof task.percentageIFA !== 'undefined' ? task.percentageIFA : 0);
        const percentageCategorized = typeof task.PercentageCategorized !== 'undefined' ? task.PercentageCategorized : 0;
        const detailingIFFPercentComplete = typeof task.DetailingIFFPercentComplete !== 'undefined' ? task.DetailingIFFPercentComplete : 0;

        // Create badges and position them in the timeline (not inside the bar)
        // Calculate badge positions based on the bar's position
        const barOffset = parseFloat($bar.css('left'));
        const barWidth = parseFloat($bar.css('width'));

        // Create and add badges
        const $topLeftBadge = createPercentageBadge(detailingIFFPercentComplete, 'badge-top-left', 'Detailing IFF (from status update)')
            .css({
                'left': `20px`,
                'top': '5px'
            });

        const $bottomLeftBadge = createPercentageBadge(percentageCategorized, 'badge-bottom-left', 'Categorized (from Piecemarks that have categoryID in production control)')
            .css({
                'left': `20px`,
                'bottom': '5px'
            });

        const $topRightBadge = createPercentageBadge(percentageIFA, 'badge-top-right', 'IFA from Tekla Production Control PieceMarks')
            .css({
                'right': `20px`,
                'top': '5px'
            });

        const $bottomRightBadge = createPercentageBadge(percentageIFF, 'badge-bottom-right', 'IFF from Tekla Production Control PieceMarks')
            .css({
                'right': `20px`,
                'bottom': '5px'
            });

        // Add badges directly to timeline
        $timeline.append($topLeftBadge, $bottomLeftBadge, $topRightBadge, $bottomRightBadge);

        // Check if task is late
        const today = GanttChart.Core.getToday();
        if (parsedEndDate < today && task.percentage < 100) {
            $bar.addClass('status-late');

            // Add warning icon
            const $warning = $('<div class="task-warning-icon" title="Task is overdue"></div>');
            $bar.append($warning);
        }
    }

    // Public API
    return {
        generate: generateBars
    };
})();