/**
 * File: js/gantt-core.js
 * Gantt Chart Core Module
 * Core functionality for Gantt chart initialization and state management
 */
const GanttChart = {};

GanttChart.Core = (function() {
    'use strict';

    // Configuration settings
    const config = {
        container: '#ganttContainer',
        timelineHeader: '#ganttTimelineHeader',
        itemRows: '#ganttItemRows',
        loadingIndicator: '#ganttLoadingIndicator',
        noItemsMessage: '#noItemsMessage',
        refreshButton: '#refreshGantt',
        filterDropdown: '.dropdown-item[data-filter]',
        filterButton: '#filterDropdownBtn',
        helpButton: '#ganttHelpBtn',
        currentFilter: 'all',
        legendContainer: '#ganttLegend',
        todayIndicator: true
    };

    // Application state
    let state = {
        items: [],
        dateRange: null,
        initialized: false
    };

    /**
     * Initialize the Gantt chart
     */
    function initialize() {
        if (state.initialized) return;

        // Set up refresh button
        $(config.refreshButton).on('click', function() {
            GanttChart.Ajax.loadData(config.currentFilter);
        });

        // Set up filter dropdown
        $(config.filterDropdown).on('click', function(e) {
            e.preventDefault();
            const filter = $(this).data('filter');
            config.currentFilter = filter;

            // Update button text
            $(config.filterButton).text($(this).text());

            // Load data with the selected filter
            GanttChart.Ajax.loadData(filter);
        });

        // Initialize help functionality
        if (typeof GanttChart.Help !== 'undefined') {
            GanttChart.Help.init();
        }

        // Initial data load
        GanttChart.Ajax.loadData(config.currentFilter);

        // Set up window resize handler
        $(window).on('resize', function() {
            adjustGanttWidth();
        });

        // Initial width adjustment
        setTimeout(adjustGanttWidth, 500);

        state.initialized = true;
    }

    /**
     * Show loading indicator
     */
    function showLoading() {
        $(config.loadingIndicator).show();
        $(config.container).hide();
        $(config.noItemsMessage).hide();
    }

    /**
     * Show empty state message
     */
    function showNoItems() {
        $(config.loadingIndicator).hide();
        $(config.container).hide();
        $(config.noItemsMessage).show();
    }

    /**
     * Show the Gantt chart
     */
    function showChart() {
        $(config.loadingIndicator).hide();
        $(config.container).show();
        $(config.noItemsMessage).hide();

        // Adjust width after showing the chart
        setTimeout(adjustGanttWidth, 100);
    }

    /**
     * Parse date string to Date object with consistent timezone handling
     * @param {string|Date} dateString - Date string or Date object
     * @return {Date|null} Parsed date object or null
     */
    function parseDate(dateString) {
        if (!dateString) return null;

        // Handle ISO format YYYY-MM-DD
        if (typeof dateString === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(dateString)) {
            const [year, month, day] = dateString.split('-').map(Number);
            return new Date(Date.UTC(year, month - 1, day, 12, 0, 0));
        }

        // Handle Date object
        if (dateString instanceof Date) {
            const d = new Date(dateString);
            d.setUTCHours(12, 0, 0, 0);
            return d;
        }

        // Handle other string formats
        try {
            const d = new Date(dateString);
            if (isNaN(d.getTime())) return null;
            return new Date(Date.UTC(d.getUTCFullYear(), d.getUTCMonth(), d.getUTCDate(), 12, 0, 0));
        } catch (e) {
            console.error("Error parsing date:", e);
            return null;
        }
    }

    /**
     * Format date for display
     * @param {Date|string} date - Date to format
     * @return {string} Formatted date string
     */
    function formatDate(date) {
        if (!date) return 'N/A';

        const d = parseDate(date);
        if (!d) return 'Invalid Date';

        return d.getUTCFullYear() + '-' +
            String(d.getUTCMonth() + 1).padStart(2, '0') + '-' +
            String(d.getUTCDate()).padStart(2, '0');
    }

    /**
     * Compare two dates (ignoring time)
     * @param {Date|string} date1 - First date
     * @param {Date|string} date2 - Second date
     * @return {number} Difference in milliseconds
     */
    function compareDates(date1, date2) {
        const d1 = parseDate(date1);
        const d2 = parseDate(date2);

        if (!d1 || !d2) return 0;
        return d1.getTime() - d2.getTime();
    }

    /**
     * Get today's date (normalized)
     * @return {Date} Today's date
     */
    function getToday() {
        const today = new Date();
        return parseDate(today.toISOString().split('T')[0]);
    }

    /**
     * Set configuration options
     * @param {Object} newConfig - New configuration values to set
     */
    function setConfig(newConfig) {
        Object.assign(config, newConfig);
    }

    /**
     * Update application state
     * @param {Object} newState - New state properties
     */
    function updateState(newState) {
        state = {...state, ...newState};

        // If date range is set, initialize TimeUtils
        if (newState.dateRange && newState.dateRange.min_date && newState.dateRange.max_date) {
            GanttChart.TimeUtils.initialize(
                newState.dateRange.min_date,
                newState.dateRange.max_date
            );
        }
    }

    /**
     * Get current application state
     * @return {Object} Current state
     */
    function getState() {
        return state;
    }

    /**
     * Get configuration settings
     * @return {Object} Configuration settings
     */
    function getConfig() {
        return config;
    }

    /**
     * Ensure the Gantt chart takes the full available width
     * This function adjusts width properties dynamically based on container size
     */
    function adjustGanttWidth() {
        // Get the available width
        const containerWidth = $('.container-fluid').width();
        const cardBodyWidth = $('.card-body').width();
        const availableWidth = Math.min(containerWidth, cardBodyWidth);

        console.log('Available width:', availableWidth);

        // Set the gantt container width
        $('#ganttContainer').css('width', availableWidth + 'px');

        // Calculate and set appropriate widths for nested elements
        const labelWidth = parseInt($('.gantt-labels').css('width') || '200');
        const timelineWidth = availableWidth - labelWidth;

        // Adjust timeline elements
        $('.gantt-timeline').css('width', timelineWidth + 'px');
        $('#ganttTimelineHeader').css('width', availableWidth + 'px');

        // Make sure the gantt-body is also full width
        $('#ganttBody').css('width', availableWidth + 'px');

        console.log('Adjusted widths - Labels:', labelWidth, 'Timeline:', timelineWidth);
    }

    // Public API
    return {
        init: initialize,
        showLoading: showLoading,
        showNoItems: showNoItems,
        showChart: showChart,
        parseDate: parseDate,
        formatDate: formatDate,
        compareDates: compareDates,
        getToday: getToday,
        getConfig: getConfig,
        setConfig: setConfig,
        setState: updateState,
        getState: getState,
        adjustGanttWidth: adjustGanttWidth  // New public method
    };
})();

// Initialize on document ready
$(document).ready(function() {
    GanttChart.Core.init();
});