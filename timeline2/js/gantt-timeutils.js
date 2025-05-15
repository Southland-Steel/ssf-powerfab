/**
 * File: js/gantt-timeutils.js
 * Gantt Chart TimeUtils Module
 * Centralized time-to-position calculation utilities
 */
GanttChart.TimeUtils = (function() {
    'use strict';

    // Private variables for date range
    let startDate = null;
    let endDate = null;
    let totalTimespan = 0;
    let initialized = false;

    /**
     * Initialize with timeline date range
     * @param {string|Date} minDate - Start date for timeline
     * @param {string|Date} maxDate - End date for timeline
     */
    function initialize(minDate, maxDate) {
        console.log('TimeUtils initializing with dates:', minDate, maxDate);

        if (!minDate || !maxDate) {
            console.error('Invalid date range provided to TimeUtils.initialize:', { minDate, maxDate });

            // Provide fallback values if no valid dates are provided
            const today = new Date();
            minDate = minDate || new Date(today.getFullYear(), today.getMonth(), today.getDate() - 30);
            maxDate = maxDate || new Date(today.getFullYear(), today.getMonth(), today.getDate() + 30);

            console.log('Using fallback date range:', { minDate, maxDate });
        }

        // Parse and store dates
        startDate = GanttChart.Core.parseDate(minDate);
        endDate = GanttChart.Core.parseDate(maxDate);

        if (!startDate || !endDate) {
            console.error('Failed to parse dates in TimeUtils.initialize');

            // Last resort fallback
            startDate = new Date();
            startDate.setDate(startDate.getDate() - 30);
            endDate = new Date();
            endDate.setDate(endDate.getDate() + 30);
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

        console.log('TimeUtils initialized:', {
            startDate: startDate.toISOString(),
            endDate: endDate.toISOString(),
            totalTimespan: totalTimespan
        });
    }

    /**
     * Try to initialize from Core state if not already initialized
     * This function checks if Core has date range and initializes itself
     */
    function ensureInitialized() {
        if (initialized) return true;

        const state = GanttChart.Core.getState();
        if (state.dateRange && state.dateRange.min_date && state.dateRange.max_date) {
            initialize(state.dateRange.min_date, state.dateRange.max_date);
            return true;
        }

        console.warn('TimeUtils not initialized and no date range available in Core state');
        return false;
    }

    /**
     * Calculate position percentage for a date on the timeline
     * @param {Date|string} date - Date to position
     * @return {number} Position percentage (0-100)
     */
    function dateToPosition(date) {
        // Try to initialize if not already done
        if (!ensureInitialized()) {
            console.error('TimeUtils not initialized with valid date range');
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
        // Try to initialize if not already done
        if (!ensureInitialized()) {
            console.error('TimeUtils not initialized with valid date range');
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
     * @return {number} Width percentage (can be negative if endDate is before startDate)
     */
    function calculateWidth(startDate, endDate) {
        const startPos = dateToPosition(startDate);
        const endPos = dateToPosition(endDate);
        return endPos - startPos;
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
     * @return {number} Total timespan in milliseconds
     */
    function getTotalTimespan() {
        ensureInitialized();
        return totalTimespan;
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
        ensureInitialized: ensureInitialized
    };
})();