/**
 * File: js/gantt-timeutils.js
 * Time-related utilities for the Gantt chart
 */
GanttChart.TimeUtils = (function() {
    'use strict';

    // Private variables for date range
    let startDate = null;
    let endDate = null;
    let totalTimespan = 0;
    let initialized = false;

    /**
     * Initialize with date range
     * @param {string|Date} minDate - Start date of the range
     * @param {string|Date} maxDate - End date of the range
     */
    function initialize(minDate, maxDate) {
        // Parse and store dates
        startDate = GanttChart.Core.parseDate(minDate);
        endDate = GanttChart.Core.parseDate(maxDate);

        if (!startDate || !endDate) {
            console.error('Failed to parse dates in TimeUtils.initialize');

            // Fallback to default dates
            const today = new Date();
            startDate = new Date(today.getFullYear(), today.getMonth(), today.getDate() - 30);
            endDate = new Date(today.getFullYear(), today.getMonth(), today.getDate() + 30);
        }

        // Calculate total timespan in milliseconds
        totalTimespan = endDate.getTime() - startDate.getTime();

        if (totalTimespan <= 0) {
            console.error('Invalid timespan (endDate must be after startDate)');
            endDate = new Date(startDate);
            endDate.setDate(endDate.getDate() + 60);
            totalTimespan = endDate.getTime() - startDate.getTime();
        }

        initialized = true;
    }

    /**
     * Ensure initialization
     * @return {boolean} Whether initialization succeeded
     */
    function ensureInitialized() {
        if (initialized) return true;

        const state = GanttChart.Core.getState();
        if (state.dateRange && state.dateRange.start && state.dateRange.end) {
            initialize(state.dateRange.start, state.dateRange.end);
            return true;
        }

        // Fallback initialization
        const today = new Date();
        const startDate = new Date(today);
        startDate.setDate(today.getDate() - 30);
        const endDate = new Date(today);
        endDate.setDate(today.getDate() + 30);

        initialize(startDate, endDate);
        return true;
    }

    /**
     * Calculate position percentage for a date on the timeline
     * @param {Date|string} date - Date to position
     * @return {number} Position percentage (0-100)
     */
    function dateToPosition(date) {
        // Initialize if needed
        if (!ensureInitialized()) {
            return 0;
        }

        // Parse date if it's a string
        const parsedDate = GanttChart.Core.parseDate(date);
        if (!parsedDate) return 0;

        // Calculate position
        const current = parsedDate.getTime();
        const position = ((current - startDate.getTime()) / totalTimespan) * 100;

        // Clamp to 0-100 range
        return Math.max(0, Math.min(100, position));
    }

    /**
     * Calculate date from a position percentage
     * @param {number} position - Position percentage (0-100)
     * @return {Date} Date at that position
     */
    function positionToDate(position) {
        // Initialize if needed
        if (!ensureInitialized()) {
            return new Date();
        }

        // Clamp position to 0-100
        const clampedPosition = Math.max(0, Math.min(100, position));

        // Calculate time at position
        const timeOffset = (clampedPosition / 100) * totalTimespan;
        const timestamp = startDate.getTime() + timeOffset;

        return new Date(timestamp);
    }

    /**
     * Calculate width percentage between two dates
     * @param {Date|string} startDate - Start date
     * @param {Date|string} endDate - End date
     * @return {number} Width percentage
     */
    function calculateWidth(startDate, endDate) {
        const startPos = dateToPosition(startDate);
        const endPos = dateToPosition(endDate);
        return Math.max(endPos - startPos, 0.5); // Minimum width of 0.5%
    }

    /**
     * Get start date of the timeline
     * @return {Date} Start date
     */
    function getStartDate() {
        ensureInitialized();
        return startDate;
    }

    /**
     * Get end date of the timeline
     * @return {Date} End date
     */
    function getEndDate() {
        ensureInitialized();
        return endDate;
    }

    /**
     * Get the total timespan of the timeline in milliseconds
     * @return {number} Total timespan
     */
    function getTotalTimespan() {
        ensureInitialized();
        return totalTimespan;
    }

    /**
     * Check if a date is within the visible timeline
     * @param {Date|string} date - Date to check
     * @return {boolean} Whether the date is visible
     */
    function isDateVisible(date) {
        const parsedDate = GanttChart.Core.parseDate(date);
        if (!parsedDate) return false;

        const timestamp = parsedDate.getTime();
        return timestamp >= startDate.getTime() && timestamp <= endDate.getTime();
    }

    /**
     * Calculate the number of days between two dates
     * @param {Date|string} date1 - First date
     * @param {Date|string} date2 - Second date
     * @return {number} Number of days
     */
    function daysBetween(date1, date2) {
        const d1 = GanttChart.Core.parseDate(date1);
        const d2 = GanttChart.Core.parseDate(date2);

        if (!d1 || !d2) return 0;

        const diffTime = Math.abs(d2 - d1);
        return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    }

    // Public API
    return {
        initialize: initialize,
        dateToPosition: dateToPosition,
        positionToDate: positionToDate,
        calculateWidth: calculateWidth,
        getStartDate: getStartDate,
        getEndDate: getEndDate,
        getTotalTimespan: getTotalTimespan,
        ensureInitialized: ensureInitialized,
        isDateVisible: isDateVisible,
        daysBetween: daysBetween
    };
})();