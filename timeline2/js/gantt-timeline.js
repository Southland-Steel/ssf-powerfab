/**
 * File: js/gantt-timeline.js
 * Gantt Chart Timeline Module
 * Handles timeline generation and utilities
 */
GanttChart.Timeline = (function() {
    'use strict';

    /**
     * Generate timeline based on date range
     * @param {string} minDate - Start date for timeline
     * @param {string} maxDate - End date for timeline
     */
    function generateTimeline(minDate, maxDate) {
        try {
            console.log('Generating timeline with dates:', minDate, maxDate);
            const $timelineHeader = $(GanttChart.Core.getConfig().timelineHeader);
            $timelineHeader.empty();

            // Ensure TimeUtils is initialized
            if (!GanttChart.TimeUtils.ensureInitialized()) {
                console.log('Initializing TimeUtils from Timeline generator');
                GanttChart.TimeUtils.initialize(minDate, maxDate);
            }

            // Parse dates with error handling
            let startDate, endDate;
            try {
                startDate = GanttChart.Core.parseDate(minDate);
                endDate = GanttChart.Core.parseDate(maxDate);

                if (!startDate || !endDate) {
                    throw new Error('Failed to parse dates');
                }

                // Add margin days to end date
                endDate = new Date(endDate);
                endDate.setDate(endDate.getDate() + 2);
            } catch (e) {
                console.error('Error parsing dates in generateTimeline:', e);
                // Fallback to default date range
                const today = new Date();
                startDate = new Date(today);
                startDate.setDate(today.getDate() - 30);
                endDate = new Date(today);
                endDate.setDate(today.getDate() + 30);

                // Reinitialize TimeUtils with these fallback dates
                GanttChart.TimeUtils.initialize(startDate, endDate);
            }

            // IMPROVED STRUCTURE: Create a proper header structure
            // First create the label placeholder to match the gantt-labels
            const $labelSpace = $('<div class="timeline-label-space"></div>');
            $timelineHeader.append($labelSpace);

            // Then create container for weeks
            const $weeksContainer = $('<div class="timeline-weeks-container"></div>');
            $timelineHeader.append($weeksContainer);

            // Calculate time range
            const timeRange = endDate.getTime() - startDate.getTime();
            const totalDays = timeRange / (24 * 60 * 60 * 1000);

            // Determine if we should show weeks, months, or quarters based on timespan
            if (totalDays <= 60) {
                // For timespans up to 60 days, show weeks
                generateWeekTimeline(startDate, endDate, totalDays, $weeksContainer);
            } else if (totalDays <= 365) {
                // For timespans up to a year, show months
                generateMonthTimeline(startDate, endDate, $weeksContainer);
            } else {
                // For longer timespans, show quarters
                generateQuarterTimeline(startDate, endDate, $weeksContainer);
            }

            // Add today marker if enabled in config
            if (GanttChart.Core.getConfig().todayIndicator) {
                addTodayMarker($weeksContainer);
            }
        } catch (error) {
            console.error('Error in generateTimeline:', error);
            // Create minimal fallback timeline
            const $timelineHeader = $(GanttChart.Core.getConfig().timelineHeader);
            $timelineHeader.html('<div class="timeline-error">Error generating timeline. Using simplified view.</div>');
        }
    }

    /**
     * Generate week-based timeline
     * @param {Date} startDate - Start date
     * @param {Date} endDate - End date
     * @param {number} totalDays - Total days in range
     * @param {jQuery} $container - Container element
     */
    function generateWeekTimeline(startDate, endDate, totalDays, $container) {
        // Adjust to start from previous Sunday
        let currentDate = new Date(startDate);
        const day = currentDate.getDay();
        if (day !== 0) {
            currentDate.setDate(currentDate.getDate() - day);
        }

        // Generate week markers
        while (currentDate < endDate) {
            addWeekMarker(currentDate, startDate, endDate, totalDays, $container);

            // Move to next week
            currentDate.setDate(currentDate.getDate() + 7);
        }
    }

    /**
     * Generate month-based timeline
     * @param {Date} startDate - Start date
     * @param {Date} endDate - End date
     * @param {jQuery} $container - Container element
     */
    function generateMonthTimeline(startDate, endDate, $container) {
        // Start from first day of the month
        let currentDate = new Date(startDate.getFullYear(), startDate.getMonth(), 1);

        // Generate month markers
        while (currentDate < endDate) {
            addMonthMarker(currentDate, $container);

            // Move to next month
            currentDate.setMonth(currentDate.getMonth() + 1);
        }
    }

    /**
     * Generate quarter-based timeline
     * @param {Date} startDate - Start date
     * @param {Date} endDate - End date
     * @param {jQuery} $container - Container element
     */
    function generateQuarterTimeline(startDate, endDate, $container) {
        // Start from first day of the quarter
        const startQuarter = Math.floor(startDate.getMonth() / 3);
        let currentDate = new Date(startDate.getFullYear(), startQuarter * 3, 1);

        // Generate quarter markers
        while (currentDate < endDate) {
            addQuarterMarker(currentDate, $container);

            // Move to next quarter
            currentDate.setMonth(currentDate.getMonth() + 3);
        }
    }

    /**
     * Add a week marker to the timeline
     * @param {Date} weekStart - Start date of week
     * @param {Date} startDate - Start date of timeline
     * @param {Date} endDate - End date of timeline
     * @param {number} totalDays - Total days in timeline
     * @param {jQuery} $container - Container element
     */
    function addWeekMarker(weekStart, startDate, endDate, totalDays, $container) {
        // Calculate position using TimeUtils
        const position = GanttChart.TimeUtils.dateToPosition(weekStart);

        // Calculate week end date
        const weekEndDate = new Date(weekStart);
        weekEndDate.setDate(weekEndDate.getDate() + 6);

        // Format date labels
        const monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
        const startLabel = monthNames[weekStart.getMonth()] + ' ' + weekStart.getDate();
        const endLabel = monthNames[weekEndDate.getMonth()] + ' ' + weekEndDate.getDate();

        // Get week number
        const weekNum = getWeekNumber(weekStart);

        // Create combined label
        const label = `
            <div class="week-number">W${weekNum}</div>
            <div class="week-range">${startLabel} - ${endLabel}</div>
        `;

        // Calculate week width
        const nextWeek = new Date(weekStart);
        nextWeek.setDate(nextWeek.getDate() + 7);
        const nextWeekPos = GanttChart.TimeUtils.dateToPosition(nextWeek);
        const width = nextWeekPos - position;

        // Create week marker
        const $week = $('<div class="timeline-week"></div>')
            .css({
                'left': position + '%',
                'width': width + '%'
            })
            .html(label);

        $container.append($week);

        // Add day markers for weeks if timeline is not too large
        if (totalDays < 60) {
            addDayMarkers(weekStart, startDate, endDate, $container);
        }
    }

    /**
     * Add a month marker to the timeline
     * @param {Date} monthStart - Start date of month
     * @param {jQuery} $container - Container element
     */
    function addMonthMarker(monthStart, $container) {
        // Calculate position using TimeUtils
        const position = GanttChart.TimeUtils.dateToPosition(monthStart);

        // Calculate month end date
        const monthEndDate = new Date(monthStart);
        monthEndDate.setMonth(monthEndDate.getMonth() + 1);
        monthEndDate.setDate(0); // Last day of the month

        // Format date label
        const monthNames = ["January", "February", "March", "April", "May", "June",
            "July", "August", "September", "October", "November", "December"];
        const monthLabel = monthNames[monthStart.getMonth()] + ' ' + monthStart.getFullYear();

        // Create month label
        const label = `
            <div class="month-label">${monthLabel}</div>
            <div class="days-count">${monthEndDate.getDate()} days</div>
        `;

        // Calculate month width
        const nextMonth = new Date(monthStart);
        nextMonth.setMonth(nextMonth.getMonth() + 1);
        const nextMonthPos = GanttChart.TimeUtils.dateToPosition(nextMonth);
        const width = nextMonthPos - position;

        // Create month marker
        const $month = $('<div class="timeline-month"></div>')
            .css({
                'left': position + '%',
                'width': width + '%'
            })
            .html(label);

        $container.append($month);
    }

    /**
     * Add a quarter marker to the timeline
     * @param {Date} quarterStart - Start date of quarter
     * @param {jQuery} $container - Container element
     */
    function addQuarterMarker(quarterStart, $container) {
        // Calculate position using TimeUtils
        const position = GanttChart.TimeUtils.dateToPosition(quarterStart);

        // Get quarter number (0-based to 1-based)
        const quarter = Math.floor(quarterStart.getMonth() / 3) + 1;

        // Format date label
        const quarterLabel = 'Q' + quarter + ' ' + quarterStart.getFullYear();

        // Calculate quarter end date
        const quarterEndDate = new Date(quarterStart);
        quarterEndDate.setMonth(quarterEndDate.getMonth() + 3);

        // Calculate quarter width
        const nextQuarter = new Date(quarterStart);
        nextQuarter.setMonth(nextQuarter.getMonth() + 3);
        const nextQuarterPos = GanttChart.TimeUtils.dateToPosition(nextQuarter);
        const width = nextQuarterPos - position;

        // Create quarter marker
        const $quarter = $('<div class="timeline-quarter"></div>')
            .css({
                'left': position + '%',
                'width': width + '%'
            })
            .html(`<div class="quarter-label">${quarterLabel}</div>`);

        $container.append($quarter);
    }

    /**
     * Add day markers for a week
     * @param {Date} weekStart - Start date of week
     * @param {Date} startDate - Start date of timeline
     * @param {Date} endDate - End date of timeline
     * @param {jQuery} $container - Container element
     */
    function addDayMarkers(weekStart, startDate, endDate, $container) {
        for (let i = 0; i < 7; i++) {
            const dayDate = new Date(weekStart);
            dayDate.setDate(dayDate.getDate() + i);

            // Skip if the day is outside our range
            if (dayDate < startDate || dayDate > endDate) continue;

            // Calculate position using TimeUtils
            const dayPos = GanttChart.TimeUtils.dateToPosition(dayDate);
            const formattedDate = GanttChart.Core.formatDate(dayDate);

            // Create day tick mark
            const $dayTick = $('<div class="timeline-day-marker"></div>')
                .css('left', dayPos + '%')
                .attr('title', formattedDate);

            // Add small day number
            const $dayLabel = $('<div class="timeline-day-label"></div>')
                .css('left', dayPos + '%')
                .text(dayDate.getDate().toString());

            $container.append($dayTick, $dayLabel);
        }
    }

    /**
     * Add today marker to the timeline
     * @param {jQuery} $container - Container element
     */
    function addTodayMarker($container) {
        const today = GanttChart.Core.getToday();
        const todayPos = GanttChart.TimeUtils.dateToPosition(today);

        // Skip if today is outside the visible range
        if (todayPos < 0 || todayPos > 100) return;

        const $todayMarker = $('<div class="timeline-today-marker"></div>')
            .css('left', todayPos + '%')
            .attr('title', 'Today: ' + GanttChart.Core.formatDate(today));

        const $todayLabel = $('<div class="timeline-today-label">Today</div>')
            .css('left', todayPos + '%');

        $container.append($todayMarker, $todayLabel);
    }

    /**
     * Get week number for a date
     * @param {Date} date - Date to get week number for
     * @return {number} Week number
     */
    function getWeekNumber(date) {
        const d = new Date(date);
        d.setHours(0, 0, 0, 0);

        // Thursday in current week decides the year
        d.setDate(d.getDate() + 3 - (d.getDay() + 6) % 7);

        // January 4 is always in week 1
        const week1 = new Date(d.getFullYear(), 0, 4);

        // Adjust to Thursday in week 1 and count number of weeks from date to week1
        return 1 + Math.round(((d.getTime() - week1.getTime()) / 86400000 - 3 + (week1.getDay() + 6) % 7) / 7);
    }

    // Public API
    return {
        generate: generateTimeline,
        getWeekNumber: getWeekNumber
    };
})();