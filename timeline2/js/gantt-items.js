/**
 * File: js/gantt-items.js
 * Gantt Chart Items Module
 * Handles item row rendering and management
 */
GanttChart.Items = (function() {
    'use strict';

    /**
     * Generate item rows based on data
     * @param {Array} items - Array of item objects
     * @param {string} minDate - Start date for timeline
     * @param {string} maxDate - End date for timeline
     */
    function generateItemRows(items, minDate, maxDate) {
        const $itemRowsContainer = $(GanttChart.Core.getConfig().itemRows);
        $itemRowsContainer.empty();

        // Create item rows
        items.forEach(function(item) {
            const $row = createItemRow(item);
            $itemRowsContainer.append($row);
        });
    }

    /**
     * Create a single item row
     * @param {Object} item - Item data
     * @return {jQuery} Created row element
     */
    function createItemRow(item) {
        // Create row container with additional class for custom indicators
        const rowClasses = getItemRowClasses(item);

        const $row = $('<div></div>')
            .addClass(rowClasses)
            .attr('data-item-id', item.id)
            .attr('data-status', item.status || 'default');

        // Create timeline column
        const $timeline = $('<div class="gantt-timeline"></div>');
        $row.append($timeline);

        // Add today's line if enabled in config
        if (GanttChart.Core.getConfig().todayIndicator) {
            addTodayLine($timeline);
        }

        // Add custom indicators based on item properties
        addCustomIndicators(item, $timeline);

        // Determine start and end dates
        const startDate = getEarliestDate(item);
        const endDate = getLatestDate(item);

        if (startDate && endDate) {
            // Add item bar
            addItemBar(item, startDate, endDate, $timeline);

            // Add date markers
            addDateMarkers(item, $timeline);
        }

        return $row;
    }

    /**
     * Get CSS classes for the item row based on item properties
     * @param {Object} item - Item data
     * @return {string} CSS classes
     */
    function getItemRowClasses(item) {
        let classes = 'gantt-row';

        // Add class for linked items
        if (item.linked) {
            classes += ' linked-item';
        }

        // Add class based on status
        if (item.status) {
            classes += ' status-' + item.status.toLowerCase().replace(/\s+/g, '-');
        }

        // Add class for priority
        if (item.priority) {
            classes += ' priority-' + item.priority.toLowerCase();
        }

        return classes;
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
     * Add custom indicators to timeline based on item properties
     * @param {Object} item - Item data
     * @param {jQuery} $timeline - Timeline element
     */
    function addCustomIndicators(item, $timeline) {
        // Add indicator for linked items
        if (item.linked) {
            $timeline.append('<div class="linked-indicator" title="Linked Item"><i class="bi bi-link"></i></div>');
        }

        // Add indicator for priority items
        if (item.priority === 'high') {
            $timeline.append('<div class="priority-indicator high" title="High Priority"><i class="bi bi-exclamation-triangle"></i></div>');
        } else if (item.priority === 'medium') {
            $timeline.append('<div class="priority-indicator medium" title="Medium Priority"><i class="bi bi-exclamation"></i></div>');
        }
    }

    /**
     * Add item bar to the timeline
     * @param {Object} item - Item data
     * @param {string} startDate - Start date for item
     * @param {string} endDate - End date for item
     * @param {jQuery} $timeline - Timeline element
     */
    function addItemBar(item, startDate, endDate, $timeline) {
        // Parse dates
        const parsedStartDate = GanttChart.Core.parseDate(startDate);
        const parsedEndDate = GanttChart.Core.parseDate(endDate);

        // Calculate positions using TimeUtils
        const startPos = GanttChart.TimeUtils.dateToPosition(parsedStartDate);
        const endPos = GanttChart.TimeUtils.dateToPosition(parsedEndDate);
        const width = Math.max(endPos - startPos, 3); // Ensure minimum width

        // Determine bar style based on status
        const barClasses = getItemBarClasses(item);

        // Create bar element
        const $bar = $('<div></div>')
            .addClass(barClasses)
            .css({
                'left': startPos + '%',
                'width': width + '%'
            });

        // Add bar title with link if applicable
        const barTitle = createBarTitle(item);

        // Add bar content with details
        const barContent = `
            ${barTitle}
            <span class="item-details">
                ${item.description ? item.description : ''}
                ${startDate ? 'Start: ' + GanttChart.Core.formatDate(startDate) : ''}
                ${endDate ? 'End: ' + GanttChart.Core.formatDate(endDate) : ''}
            </span>
        `;

        $bar.html(barContent);

        // Add progress indicator if percentage is available
        if (item.percentage !== undefined && item.percentage !== null) {
            addProgressIndicator(item, $bar);
        }

        // Add warning indicators
        addWarningIndicators(item, $bar);

        // Add the bar to the timeline
        $timeline.append($bar);
    }

    /**
     * Create bar title element
     * @param {Object} item - Item data
     * @return {string} HTML for bar title
     */
    function createBarTitle(item) {
        let title = '';

        // If item has an URL, create a link
        if (item.url) {
            title = `<a href="${item.url}" class="item-title-link" title="View Item Details">${item.title || item.id}</a>`;
        } else {
            title = `<span class="item-title">${item.title || item.id}</span>`;
        }

        return `<span class="item-title-display">${title}</span>`;
    }

    /**
     * Add progress indicator to item bar
     * @param {Object} item - Item data
     * @param {jQuery} $bar - Bar element
     */
    function addProgressIndicator(item, $bar) {
        const percentage = Math.min(Math.max(0, item.percentage), 100);

        const $progressBar = $('<div class="item-bar-percentage"></div>')
            .css('width', percentage + '%');

        const $progressText = $('<div class="item-bar-percentage-text"></div>')
            .text(percentage + '%');

        $progressBar.append($progressText);
        $bar.append($progressBar);
    }

    /**
     * Get CSS classes for item bar based on status
     * @param {Object} item - Item data
     * @return {string} CSS classes
     */
    function getItemBarClasses(item) {
        let classes = 'item-bar';

        // Base styling on status
        if (item.status) {
            const statusClass = item.status.toLowerCase().replace(/\s+/g, '-');
            classes += ' status-' + statusClass;
        }

        // Add milestone class if it's a milestone
        if (item.milestone) {
            classes += ' milestone';
        }

        // Add class based on completion
        if (item.percentage >= 100) {
            classes += ' completed';
        } else if (item.percentage > 0) {
            classes += ' in-progress';
        } else {
            classes += ' not-started';
        }

        return classes;
    }

    /**
     * Add warning indicators to item bar
     * @param {Object} item - Item data
     * @param {jQuery} $bar - Item bar element
     */
    function addWarningIndicators(item, $bar) {
        const today = GanttChart.Core.getToday();

        // Check for overdue items (end date in the past with < 100% completion)
        if (item.end_date && item.percentage < 100) {
            const endDate = GanttChart.Core.parseDate(item.end_date);

            if (endDate < today) {
                $bar.append('<div class="date-conflict-warning overdue" title="Overdue: End date has passed but item is not complete">!</div>');
            }
        }

        // Check for items at risk (due soon with low progress)
        if (item.end_date && item.percentage < 70) {
            const endDate = GanttChart.Core.parseDate(item.end_date);
            const daysUntilDue = Math.ceil((endDate - today) / (24 * 60 * 60 * 1000));

            if (daysUntilDue > 0 && daysUntilDue <= 5) {
                $bar.append('<div class="date-conflict-warning at-risk" title="At Risk: Due soon with insufficient progress">!</div>');
            }
        }

        // Add other custom warnings based on item properties
        if (item.warnings && item.warnings.length > 0) {
            item.warnings.forEach(warning => {
                $bar.append(`<div class="date-conflict-warning custom-warning" title="${warning.message}">!</div>`);
            });
        }
    }

    /**
     * Add date markers to the timeline
     * @param {Object} item - Item data
     * @param {jQuery} $timeline - Timeline element
     */
    function addDateMarkers(item, $timeline) {
        // Add start date marker if available
        if (item.start_date) {
            const startDate = GanttChart.Core.parseDate(item.start_date);
            const startPos = GanttChart.TimeUtils.dateToPosition(startDate);

            const $startMarker = $('<div class="date-marker start-date-marker"></div>')
                .css('left', startPos + '%')
                .attr('title', 'Start Date: ' + GanttChart.Core.formatDate(item.start_date));

            $timeline.append($startMarker);
        }

        // Add end date marker if available
        if (item.end_date) {
            const endDate = GanttChart.Core.parseDate(item.end_date);
            const endPos = GanttChart.TimeUtils.dateToPosition(endDate);

            const $endMarker = $('<div class="date-marker end-date-marker"></div>')
                .css('left', endPos + '%')
                .attr('title', 'End Date: ' + GanttChart.Core.formatDate(item.end_date));

            $timeline.append($endMarker);
        }

        // Add milestone markers if available
        if (item.milestones && item.milestones.length > 0) {
            item.milestones.forEach(milestone => {
                if (milestone.date) {
                    const milestoneDate = GanttChart.Core.parseDate(milestone.date);
                    const milestonePos = GanttChart.TimeUtils.dateToPosition(milestoneDate);

                    const $milestoneMarker = $('<div class="date-marker milestone-marker"></div>')
                        .css('left', milestonePos + '%')
                        .attr('title', `Milestone: ${milestone.title || 'Unnamed'} - ${GanttChart.Core.formatDate(milestone.date)}`);

                    $timeline.append($milestoneMarker);
                }
            });
        }
    }

    /**
     * Get earliest date from item
     * @param {Object} item - Item data
     * @return {string} Earliest date
     */
    function getEarliestDate(item) {
        // Collect all available dates
        const dates = [
            item.start_date,
            item.planned_start_date,
            item.actual_start_date
        ].filter(Boolean); // Remove null/undefined

        // Add milestone dates if available
        if (item.milestones && item.milestones.length > 0) {
            item.milestones.forEach(milestone => {
                if (milestone.date) {
                    dates.push(milestone.date);
                }
            });
        }

        if (dates.length === 0) return null;

        // Find earliest date
        return dates.reduce((earliest, date) => {
            const current = GanttChart.Core.parseDate(date).getTime();
            const earliestTime = GanttChart.Core.parseDate(earliest).getTime();
            return current < earliestTime ? date : earliest;
        }, dates[0]);
    }

    /**
     * Get latest date from item
     * @param {Object} item - Item data
     * @return {string} Latest date
     */
    function getLatestDate(item) {
        // Collect all available dates
        const dates = [
            item.end_date,
            item.planned_end_date,
            item.actual_end_date
        ].filter(Boolean); // Remove null/undefined

        // Add milestone dates if available
        if (item.milestones && item.milestones.length > 0) {
            item.milestones.forEach(milestone => {
                if (milestone.date) {
                    dates.push(milestone.date);
                }
            });
        }

        if (dates.length === 0) return null;

        // Find latest date
        return dates.reduce((latest, date) => {
            const current = GanttChart.Core.parseDate(date).getTime();
            const latestTime = GanttChart.Core.parseDate(latest).getTime();
            return current > latestTime ? date : latest;
        }, dates[0]);
    }

    // Public API
    return {
        generate: generateItemRows,
        createItemRow: createItemRow,
        getItemRowClasses: getItemRowClasses,
        getItemBarClasses: getItemBarClasses
    };
})();