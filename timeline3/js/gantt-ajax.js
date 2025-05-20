/**
 * File: js/gantt-ajax.js
 * Handles AJAX requests for the Gantt chart
 */
GanttChart.Ajax = (function() {
    'use strict';

    /**
     * Load Gantt chart data from server
     * @param {string} filter - Filter to apply to data
     */
    function loadData(filter) {
        // Show loading state
        GanttChart.Core.showLoading();

        // Reset item count badge
        $('#itemCountBadge').text('0').removeClass('bg-success bg-primary').addClass('bg-secondary');

        // Get data endpoint from config
        const config = GanttChart.Core.getConfig();
        const endpoint = config.dataEndpoint;

        // Add filter parameter if not 'all'
        const url = new URL(endpoint, window.location.href);
        if (filter && filter !== 'all') {
            url.searchParams.append('filter', filter);
        }

        // Perform AJAX request
        $.ajax({
            url: url.toString(),
            method: 'GET',
            dataType: 'json',
            cache: false,
            success: handleDataSuccess,
            error: handleDataError
        });
    }

    /**
     * Handle successful data response
     * @param {Object} response - Server response
     */
    function handleDataSuccess(response) {
        try {
            // Validate response structure
            if (!response || !response.tasks || !response.dateRange) {
                console.error('Invalid response format:', response);
                GanttChart.Core.showNoItems();
                return;
            }

            const tasks = response.tasks;
            const dateRange = response.dateRange;

            // Process tasks data to normalize property names if needed
            // This ensures backwards compatibility with existing code
            const processedTasks = tasks.map(task => {
                // Ensure rowGroupId is consistent (handle both capitalization variants)
                if (!task.rowGroupId && task.RowGroupID) {
                    task.rowGroupId = task.RowGroupID;
                }

                // Set default values for project property if missing
                if (!task.project && task.JobNumber) {
                    task.project = task.JobNumber;
                }

                // Ensure description property is present
                if (!task.description && task.taskDescription) {
                    task.description = task.taskDescription;
                } else if (!task.description && task.SequenceName) {
                    task.description = task.SequenceName + (task.LotNumber ? ' - ' + task.LotNumber : '');
                }

                return task;
            });

            // Update state with new data
            GanttChart.Core.setState({
                tasks: processedTasks,
                dateRange: dateRange
            });

            // Update project filters based on tasks
            const projects = GanttChart.Core.extractProjects(processedTasks);
            GanttChart.Core.updateProjectFilters(projects);

            if (processedTasks.length > 0) {
                // Generate timeline
                GanttChart.Timeline.generate(
                    dateRange.start,
                    dateRange.end
                );

                // Generate task bars
                GanttChart.Bars.generate(
                    processedTasks,
                    dateRange.start,
                    dateRange.end
                );

                // Initialize interactions
                GanttChart.Interactions.init();

                GanttChart.Interactions.resetFilters();

                if (GanttChart.Core.getState().currentStatusFilter !== 'all') {
                    const previousFilter = GanttChart.Core.getState().currentStatusFilter;

                    // Find and activate the corresponding filter button
                    $('.gantt-filter-btn').removeClass('active');
                    $(`.gantt-filter-btn[data-filter="${previousFilter}"]`).addClass('active');

                    // Apply the filter
                    GanttChart.Interactions.filterItems(previousFilter);
                }

                // Make sure to update the item count badge
                GanttChart.Interactions.updateItemCount();

                // Show chart
                GanttChart.Core.showChart();
            } else {
                // Show no items message
                GanttChart.Core.showNoItems();
            }
        } catch (error) {
            console.error('Error processing data:', error);
            handleDataError(null, 'error', error.message || 'Unknown error');
        }
    }

    /**
     * Handle data loading error
     * @param {Object} xhr - XHR object
     * @param {string} status - Error status
     * @param {string} error - Error message
     */
    function handleDataError(xhr, status, error) {
        console.error('Error loading Gantt data:', error);

        // Reset item count badge
        $('#itemCountBadge').text('0').removeClass('bg-success bg-primary').addClass('bg-secondary');

        // Show no items message
        GanttChart.Core.showNoItems();

        // Show error alert
        alert('Error loading data: ' + (error || 'Could not connect to server. Please try again later.'));
    }

    /**
     * Export Gantt chart data to CSV
     */
    function exportToCsv() {
        const state = GanttChart.Core.getState();
        const tasks = state.tasks;

        if (!tasks || tasks.length === 0) {
            alert('No data to export');
            return;
        }

        // Prepare CSV header
        const headers = [
            'Project',
            'Element',
            'Task Description',
            'Status',
            'Progress',
            'Start Date',
            'End Date',
            'Hours'
        ];

        // Add additional headers if enhanced data is present
        if (tasks[0].PercentageIFF !== undefined || tasks[0].percentageIFF !== undefined) {
            headers.push('IFF %', 'IFA %');

            // If client approval data is present
            if (tasks[0].ClientApprovalPercentComplete !== undefined) {
                headers.push('Client Approval %');
            }

            // If detailing IFF data is present
            if (tasks[0].DetailingIFFPercentComplete !== undefined) {
                headers.push('Detailing IFF %');
            }
        }

        // Prepare CSV rows
        const csvRows = [
            headers.join(',')
        ];

        // Add data rows
        tasks.forEach(task => {
            // Get project and element parts
            let projectParts = [];
            if (task.rowGroupId) {
                projectParts = task.rowGroupId.split('.');
            } else if (task.RowGroupID) {
                projectParts = task.RowGroupID.split('.');
            } else {
                projectParts = [task.project || task.JobNumber || 'Unknown', task.SequenceName || task.description || 'Unknown'];
            }

            const project = projectParts[0];
            const element = projectParts.slice(1).join('.');

            // Create base row data
            const row = [
                csvEscapeValue(project),
                csvEscapeValue(element),
                csvEscapeValue(task.taskDescription || task.TaskDescription || task.description || ''),
                csvEscapeValue(task.status || ''),
                task.percentage + '%',
                task.startDate ? GanttChart.Core.formatDate(task.startDate) : '',
                task.endDate ? GanttChart.Core.formatDate(task.endDate) : '',
                task.hours || ''
            ];

            // Add enhanced data if available
            if (headers.length > 8) {
                row.push(
                    (task.PercentageIFF !== undefined ? task.PercentageIFF : (task.percentageIFF !== undefined ? task.percentageIFF : 0)) + '%',
                    (task.PercentageIFA !== undefined ? task.PercentageIFA : (task.percentageIFA !== undefined ? task.percentageIFA : 0)) + '%'
                );

                // Add client approval if in headers
                if (headers.includes('Client Approval %')) {
                    row.push((task.ClientApprovalPercentComplete || 0) + '%');
                }

                // Add detailing IFF if in headers
                if (headers.includes('Detailing IFF %')) {
                    row.push((task.DetailingIFFPercentComplete || 0) + '%');
                }
            }

            csvRows.push(row.join(','));
        });

        // Create CSV content
        const csvContent = csvRows.join('\n');

        // Create download link
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.setAttribute('href', url);
        link.setAttribute('download', 'gantt_data.csv');
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    /**
     * Escape value for CSV format
     * @param {string} value - Value to escape
     * @return {string} Escaped value
     */
    function csvEscapeValue(value) {
        if (value === undefined || value === null) return '';

        value = String(value);

        // If value contains commas, quotes, or newlines, wrap in quotes and escape internal quotes
        if (/[",\n\r]/.test(value)) {
            return '"' + value.replace(/"/g, '""') + '"';
        }
        return value;
    }

    // Public API
    return {
        loadData: loadData,
        exportToCsv: exportToCsv
    };
})();