/**
 * File: js/gantt-bars.js
 * Handles rendering task bars for the Gantt chart with integrated IFF display
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
     * Create a single task row with integrated IFF badge
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
            .attr('data-level', task.level)
            .attr('data-row-group-id', task.RowGroupID || task.rowGroupId)
            .attr('data-has-iff', task.hasIFFData ? 'true' : 'false')
            .attr('data-iff-percentage', task.iffPercentage || 0);

        // Create labels section
        const $labels = $('<div class="gantt-labels"></div>');

        // Extract project and element from rowGroupId
        let parts = task.RowGroupID ? task.RowGroupID.split('.') : (task.rowGroupId ? task.rowGroupId.split('.') : [task.project, task.description]);
        const project = parts.shift(); // First part is project
        const element = parts.join('.'); // Rest is element path

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

        $labels.html(labelContent);

        // Append labels to row
        $row.append($labels);

        // Create timeline column
        const $timeline = $('<div class="gantt-timeline"></div>');

        // Add today line
        addTodayLine($timeline);

        // Add task bar
        addTaskBar(task, $timeline);

        // Add IFF badge if task has IFF data
        if (task.hasIFFData) {
            addIFFBadge($timeline, task);
            // Also add IFF milestone marker on the timeline
            addIFFMilestone($timeline, task);
        }

        // Append timeline to row
        $row.append($timeline);

        return $row;
    }

    /**
     * Add IFF badge to timeline
     * @param {jQuery} $timeline - Timeline element
     * @param {Object} task - Task data with IFF information
     */
    function addIFFBadge($timeline, task) {
        const iffPercentage = task.iffPercentage || 0;

        // Create IFF badge positioned at top left
        // This data comes from the Detailing resource with "Issued for Fabrication" task
        const $iffBadge = createPercentageBadge(
            iffPercentage,
            'badge-top-left iff-badge detailing-iff',
            `Detailing IFF: ${iffPercentage}% (Milestone: ${task.iffSubtask?.formattedMilestoneDate || 'N/A'})`
        ).css({
            'left': '20px',
            'top': '5px'
        });

        $timeline.append($iffBadge);
    }

    /**
     * Add IFF milestone marker on the timeline
     * @param {jQuery} $timeline - Timeline element
     * @param {Object} task - Task data with IFF information
     */
    function addIFFMilestone($timeline, task) {
        if (!task.iffSubtask || !task.iffSubtask.milestoneDate) {
            return;
        }

        // Parse the IFF milestone date
        const milestoneDate = GanttChart.Core.parseDate(task.iffSubtask.milestoneDate);
        if (!milestoneDate) {
            return;
        }

        // Calculate position using TimeUtils
        const milestonePos = GanttChart.TimeUtils.dateToPosition(milestoneDate);

        // Only add if within visible range
        if (milestonePos < 0 || milestonePos > 100) {
            return;
        }

        // Create milestone marker
        const $milestone = $('<div class="iff-milestone-marker"></div>')
            .css('left', milestonePos + '%');

        // Determine status class based on IFF percentage
        const iffPercentage = task.iffPercentage || 0;
        if (iffPercentage >= 99) {
            $milestone.addClass('milestone-complete');
        } else if (iffPercentage > 0) {
            $milestone.addClass('milestone-in-progress');
        } else {
            $milestone.addClass('milestone-pending');
        }

        // Create tooltip content
        const tooltipText = `IFF Milestone: ${task.iffSubtask.formattedMilestoneDate}\nStatus: ${task.iffSubtask.status}\nProgress: ${iffPercentage}%`;
        $milestone.attr('title', tooltipText);

        // Add percentage label if space allows
        if (iffPercentage > 0) {
            const $label = $('<div class="iff-milestone-label"></div>')
                .text(Math.round(iffPercentage) + '%')
                .css('left', milestonePos + '%');

            $timeline.append($label);
        }

        $timeline.append($milestone);
    }

    /**
     * Apply badge data to an existing task row (for remaining badges)
     * @param {jQuery} $row - The task row element
     * @param {Object} badges - Badge data object
     */
    function applyBadges($row, badges) {
        const $timeline = $row.find('.gantt-timeline');

        if (!$timeline.length) {
            return;
        }

        // Store badge data as attributes for filtering
        $row.attr('data-client-approval', badges.ClientApprovalPercentComplete || 0);

        // Client approval badge in labels section
        const $labels = $row.find('.gantt-labels');
        const clientApprovalBadge = createPercentageBadge(
            badges.ClientApprovalPercentComplete || 0,
            'client-approval-badge',
            'Client Approval (manual entry in project management)'
        ).css({
            'position': 'absolute',
            'bottom': '5px',
            'right': '5px',
            'transform': 'none' // Override the translateX(-50%)
        });
        $labels.append(clientApprovalBadge);

        // Extract percentage values
        const percentageIFA = badges.PercentageIFA || 0;
        const percentageCategorized = badges.PercentageCategorized || 0;

        // Note: IFF badge is already added from task data, so we skip it here

        // Create and add remaining badges to timeline
        const $bottomLeftBadge = createPercentageBadge(
            percentageCategorized,
            'badge-bottom-left',
            'Categorized (from Piecemarks that have categoryID in production control)'
        ).css({
            'left': '20px',
            'bottom': '5px'
        });

        const $topRightBadge = createPercentageBadge(
            percentageIFA,
            'badge-top-right',
            'IFA from Tekla Production Control PieceMarks'
        ).css({
            'right': '20px',
            'top': '5px'
        });

        // Note: IFF badge (bottom-right) is now added from task data in createSingleTaskRow
        // Only add the production control IFF badge if it's different from the detailing IFF

        // Add badges to timeline (excluding IFF which is already added)
        $timeline.append($bottomLeftBadge, $topRightBadge);
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
                            title="${tooltipText}">
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

        // Add planning horizon line (8 weeks from today)
        const planningHorizonDate = new Date(today);
        planningHorizonDate.setDate(planningHorizonDate.getDate() + (8 * 7)); // 8 weeks = 56 days

        const planningHorizonPos = GanttChart.TimeUtils.dateToPosition(planningHorizonDate);

        if (planningHorizonPos >= 0 && planningHorizonPos <= 100) {
            // Create planning horizon line
            const $planningLine = $('<div class="planning-horizon-line"></div>')
                .css('left', planningHorizonPos + '%')
                .attr('title', 'Planning Horizon (8 weeks): ' + GanttChart.Core.formatDate(planningHorizonDate));

            $timeline.append($planningLine);
        }
    }

    /**
     * Add a task bar to the timeline with enhanced tooltip
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
            .attr('data-task-id', task.id)
            .attr('data-row-group-id', task.RowGroupID || task.rowGroupId);

        // Create enhanced tooltip content including IFF info
        let tooltipContent = `
        ${task.taskDescription || task.description}
        Start: ${GanttChart.Core.formatDate(task.startDate)}
        End: ${GanttChart.Core.formatDate(task.endDate)}
        Progress: ${task.percentage}%
        ${task.hours ? 'Hours: ' + Math.round(task.hours, 0) : ''}`;

        // Add IFF information if available
        if (task.hasIFFData && task.iffSubtask) {
            tooltipContent += `
        
        Detailing IFF: ${task.iffPercentage}%
        IFF Milestone: ${task.iffSubtask.formattedMilestoneDate || 'N/A'}`;
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

        // Add click event to navigate to sequence_detail.php
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

        // Check if task is late
        const today = GanttChart.Core.getToday();
        if (parsedEndDate < today && task.percentage < 100) {
            $bar.addClass('status-late');

            // Add warning icon
            const $warning = $('<div class="task-warning-icon" title="Task is overdue"></div>');
            $bar.append($warning);
        }
    }

    /**
     * Add workweek dots to the timeline
     * @param {jQuery} $timeline - Timeline element
     * @param {Array} workweeks - Array of workweek objects
     */
    function addWorkweekDots($timeline, workweeks) {
        if (!workweeks || workweeks.length === 0) {
            return;
        }

        // Calculate the earliest and latest workweek dates for bracket positioning
        const firstWorkweek = workweeks[0];
        const lastWorkweek = workweeks[workweeks.length - 1];

        // Parse the start and end dates
        const startDate = GanttChart.Core.parseDate(firstWorkweek.start);
        const endDate = GanttChart.Core.parseDate(lastWorkweek.end);

        if (!startDate || !endDate) {
            console.error('Invalid workweek dates');
            return;
        }

        // Calculate positions using TimeUtils
        const startPos = GanttChart.TimeUtils.dateToPosition(startDate);
        const endPos = GanttChart.TimeUtils.dateToPosition(endDate);

        // Add start bracket
        const $startBracket = $('<div class="workweek-bracket workweek-bracket-start"></div>')
            .css('left', startPos + '%')
            .attr('title', `First workweek starts: ${firstWorkweek.start}`);
        $timeline.append($startBracket);

        // Add end bracket
        const $endBracket = $('<div class="workweek-bracket workweek-bracket-end"></div>')
            .css('left', endPos + '%')
            .attr('title', `Last workweek ends: ${lastWorkweek.end}`);
        $timeline.append($endBracket);

        // Add dots for each workweek
        workweeks.forEach(workweek => {
            // Use Wednesday date for positioning the dot (center of workweek)
            const workweekWednesdayDate = GanttChart.Core.parseDate(workweek.wednesday);
            if (!workweekWednesdayDate) {
                return;
            }

            const dotPos = GanttChart.TimeUtils.dateToPosition(workweekWednesdayDate);

            // Create dot element
            const $dot = $('<div class="workweek-dot"></div>')
                .css('left', dotPos + '%');

            // Set status class
            if (parseInt(workweek.onhold) === 1) {
                $dot.addClass('status-onhold');
            } else if (parseInt(workweek.released) === 1) {
                $dot.addClass('status-released');
            } else {
                $dot.addClass('status-pending');
            }

            // Create tooltip content
            const tooltipText = `${workweek.display}\nWork Package: ${workweek.wpn || 'N/A'}\nStatus: ${parseInt(workweek.released) === 1 ? 'Released' : (parseInt(workweek.onhold) === 1 ? 'On Hold' : 'Pending')}`;
            $dot.attr('title', tooltipText);

            $timeline.append($dot);
        });
    }

    // Public API
    return {
        generate: generateBars,
        createSingleTaskRow: createSingleTaskRow,
        applyBadges: applyBadges,
        createPercentageBadge: createPercentageBadge,
        addWorkweekDots: addWorkweekDots
    };
})();