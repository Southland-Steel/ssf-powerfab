/**
 * File: js/gantt-bars.js
 * Handles rendering task bars for the Gantt chart
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
            .attr('data-end-date', task.endDate);

        // Create labels section
        const $labels = $('<div class="gantt-labels"></div>');

        // Extract project and element from rowGroupId
        let parts = task.rowGroupId.split('.');
        const project = parts.shift(); // First part is project
        const element = parts.join('.'); // Rest is element path

        // Format dates using GanttChart.Core.formatDate for consistency
        const startFormatted = GanttChart.Core.formatDate(task.startDate);
        const endFormatted = GanttChart.Core.formatDate(task.endDate);

        // Parse dates using GanttChart.Core.parseDate for display
        const parsedStart = GanttChart.Core.parseDate(task.startDate);
        const parsedEnd = GanttChart.Core.parseDate(task.endDate);

        $labels.append(`
            <div class="gantt-rowtitle">
                <strong class="project-code">${project}:</strong>
                <span class="element-name">${element}</span>
                <div class="task-description">${task.taskDescription || task.description || ''}</div>
                <div class="task-dates" title="Dates used for sorting">
                    ${startFormatted} → ${endFormatted}
                </div>
            </div>
        `);

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

        // Create tooltip content
        const tooltipContent = `
            ${task.taskDescription || task.description}
            Start: ${GanttChart.Core.formatDate(task.startDate)}
            End: ${GanttChart.Core.formatDate(task.endDate)}
            Progress: ${task.percentage}%
            ${task.hours ? 'Hours: ' + Math.round(task.hours,0) : ''}
        `;

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

        // Add click event to show details
        $bar.on('click', function() {
            showTaskDetails(task.id);
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
     * Show task details in a modal
     * @param {number|string} taskId - Task ID
     */
    function showTaskDetails(taskId) {
        // Show modal
        const $modal = $('#taskDetailModal');
        const $content = $('#taskDetailContent');

        // Show loading state
        $content.html(`
            <div class="text-center">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `);

        $modal.modal('show');

        // Get task details from server
        const config = GanttChart.Core.getConfig();
        $.ajax({
            url: config.taskDetailsEndpoint,
            data: { id: taskId },
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.error) {
                    $content.html(`<div class="alert alert-danger">${response.error}</div>`);
                    return;
                }

                const task = response.task;
                const relatedTasks = response.relatedTasks || [];

                // Format dates
                const startDate = GanttChart.Core.formatDate(task.ActualStartDate);
                const endDate = GanttChart.Core.formatDate(task.ActualEndDate);

                // Update modal title
                $('#taskDetailModalLabel').text(`${task.JobNumber}: ${task.ElementName} - ${task.TaskDescription}`);

                // Build content
                let html = `
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6>Task Information</h6>
                            <dl class="row">
                                <dt class="col-sm-4">Project:</dt>
                                <dd class="col-sm-8">${task.JobNumber}</dd>
                                
                                <dt class="col-sm-4">Element:</dt>
                                <dd class="col-sm-8">${task.ElementName}</dd>
                                
                                <dt class="col-sm-4">Description:</dt>
                                <dd class="col-sm-8">${task.TaskDescription}</dd>
                                
                                <dt class="col-sm-4">Resource:</dt>
                                <dd class="col-sm-8">${task.ResourceName || 'Not assigned'}</dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <h6>Schedule Information</h6>
                            <dl class="row">
                                <dt class="col-sm-4">Start Date:</dt>
                                <dd class="col-sm-8">${startDate}</dd>
                                
                                <dt class="col-sm-4">End Date:</dt>
                                <dd class="col-sm-8">${endDate}</dd>
                                
                                <dt class="col-sm-4">Progress:</dt>
                                <dd class="col-sm-8">
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar" role="progressbar" style="width: ${task.PercentComplete}%;" 
                                            aria-valuenow="${task.PercentComplete}" aria-valuemin="0" aria-valuemax="100">
                                            ${task.PercentComplete}%
                                        </div>
                                    </div>
                                </dd>
                                
                                <dt class="col-sm-4">Hours:</dt>
                                <dd class="col-sm-8">${task.EstimatedHours || 0} (Estimated) / ${task.ActualHoursComplete || 0} (Actual)</dd>
                            </dl>
                        </div>
                    </div>
                `;

                // Add related tasks if available
                if (relatedTasks.length > 0) {
                    html += `
                        <div class="row">
                            <div class="col-12">
                                <h6>Related Tasks</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-striped">
                                        <thead>
                                            <tr>
                                                <th>Description</th>
                                                <th>Resource</th>
                                                <th>Start Date</th>
                                                <th>End Date</th>
                                                <th>Progress</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                    `;

                    relatedTasks.forEach(relatedTask => {
                        html += `
                            <tr>
                                <td>${relatedTask.TaskDescription}</td>
                                <td>${relatedTask.ResourceName || 'Not assigned'}</td>
                                <td>${GanttChart.Core.formatDate(relatedTask.ActualStartDate)}</td>
                                <td>${GanttChart.Core.formatDate(relatedTask.ActualEndDate)}</td>
                                <td>
                                    <div class="progress" style="height: 18px;">
                                        <div class="progress-bar" role="progressbar" style="width: ${relatedTask.PercentComplete}%;" 
                                            aria-valuenow="${relatedTask.PercentComplete}" aria-valuemin="0" aria-valuemax="100">
                                            ${relatedTask.PercentComplete}%
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });

                    html += `
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    `;
                }

                // Update modal content
                $content.html(html);
            },
            error: function() {
                $content.html('<div class="alert alert-danger">Failed to load task details.</div>');
            }
        });
    }

    // Public API
    return {
        generate: generateBars,
        showTaskDetails: showTaskDetails
    };
})();