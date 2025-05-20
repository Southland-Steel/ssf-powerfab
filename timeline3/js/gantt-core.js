/**
 * File: js/gantt-core.js
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
        exportButton: '#exportGantt',
        helpButton: '#ganttHelpBtn',
        filterDropdown: '#projectFilterDropdown',
        filterButton: '#filterDropdownBtn',
        currentFilter: 'all',
        todayIndicator: true,
        dataEndpoint: 'ajax/get_timeline_data.php',
        taskDetailsEndpoint: 'ajax/get_task_details.php'
    };

    // Application state
    let state = {
        tasks: [],
        dateRange: null,
        initialized: false,
        projects: []
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

        // Set up export button
        $(config.exportButton).on('click', function() {
            GanttChart.Ajax.exportToCsv();
        });

        // Set up help button
        $(config.helpButton).on('click', function() {
            $('#ganttHelpModal').modal('show');
        });

        // Set up filter dropdown delegation
        $(document).on('click', '.dropdown-item[data-filter]', function(e) {
            e.preventDefault();
            const filter = $(this).data('filter');
            config.currentFilter = filter;

            // Update button text
            $(config.filterButton).text($(this).text());

            // Load data with the selected filter
            GanttChart.Ajax.loadData(filter);
        });

        // Set up filter buttons
        $(document).on('click', '.gantt-filter-btn', function() {
            $('.gantt-filter-btn').removeClass('active');
            $(this).addClass('active');

            const filter = $(this).data('filter');
            GanttChart.Interactions.filterItems(filter);
        });

        // Set up window resize handler
        $(window).on('resize', function() {
            adjustGanttWidth();
        });

        // Initial width adjustment
        setTimeout(adjustGanttWidth, 100);

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
            const date = new Date(Date.UTC(year, month - 1, day, 0, 0, 0));
            return date;
        }

        // Handle Date object
        if (dateString instanceof Date) {
            const d = new Date(dateString);
            d.setHours(0, 0, 0, 0);
            return d;
        }

        // Handle other string formats
        try {
            const d = new Date(dateString);
            if (isNaN(d.getTime())) {
                console.error('Invalid date:', dateString);
                return null;
            }
            const normalizedDate = new Date(d.getFullYear(), d.getMonth(), d.getDate(), 0, 0, 0);
            return normalizedDate;
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

        return d.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }

    /**
     * Get today's date (normalized)
     * @return {Date} Today's date
     */
    function getToday() {
        const today = new Date();
        // Use local time instead of UTC
        today.setHours(0, 0, 0, 0);
        return today;
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
    function setState(newState) {
        state = {...state, ...newState};

        // Initialize TimeUtils if date range is set
        if (newState.dateRange && GanttChart.TimeUtils) {
            GanttChart.TimeUtils.initialize(
                newState.dateRange.start,
                newState.dateRange.end
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
     * Ensure the Gantt chart takes full available width
     */
    function adjustGanttWidth() {
        // Get the available width
        const containerWidth = $('.container-fluid').width();
        const cardBodyWidth = $('.card-body').width();
        const availableWidth = Math.min(containerWidth, cardBodyWidth);

        // Set the gantt container width
        $(config.container).css('width', availableWidth + 'px');

        // Calculate and set appropriate widths for nested elements
        const labelWidth = parseInt($('.gantt-labels').css('width') || '200');
        const timelineWidth = availableWidth - labelWidth;

        // Adjust timeline elements
        $('.gantt-timeline').css('width', timelineWidth + 'px');
        $(config.timelineHeader).css('width', availableWidth + 'px');

        // Make sure the gantt-body is also full width
        $('#ganttBody').css('width', availableWidth + 'px');
    }

    /**
     * Extract unique projects from tasks
     * @param {Array} tasks - Array of task objects
     * @return {Array} Array of unique project objects
     */
    function extractProjects(tasks) {
        const projectMap = {};

        tasks.forEach(task => {
            if (!projectMap[task.project]) {
                projectMap[task.project] = true;
            }
        });

        return Object.keys(projectMap).map(project => ({
            id: project,
            name: project
        }));
    }

    /**
     * Update project filters dropdown
     * @param {Array} projects - Array of project objects
     */
    function updateProjectFilters(projects) {
        const $dropdown = $(config.filterDropdown);

        // Clear existing project filters except 'All Projects'
        $dropdown.find('li:not(:first-child)').remove();

        // Add divider if there are projects
        if (projects.length > 0) {
            $dropdown.append('<li><hr class="dropdown-divider"></li>');
        }

        // Add filters for each project
        projects.forEach(project => {
            $dropdown.append(`<li><a class="dropdown-item" href="#" data-filter="${project.id}">${project.name}</a></li>`);
        });
    }

    // Public API
    return {
        init: initialize,
        showLoading: showLoading,
        showNoItems: showNoItems,
        showChart: showChart,
        parseDate: parseDate,
        formatDate: formatDate,
        getToday: getToday,
        getConfig: getConfig,
        setConfig: setConfig,
        setState: setState,
        getState: getState,
        adjustGanttWidth: adjustGanttWidth,
        extractProjects: extractProjects,
        updateProjectFilters: updateProjectFilters
    };
})();