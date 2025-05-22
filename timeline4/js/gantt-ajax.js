/**
 * File: js/gantt-ajax.js
 * Handles AJAX requests for the Gantt chart with sequential loading
 */
GanttChart.Ajax = (function() {
    'use strict';

    /**
     * Load Gantt chart data with sequential enhancement
     * @param {string} filter - Filter to apply to data
     */
    function loadData(filter) {
        // Show loading state
        GanttChart.Core.showLoading();

        // Reset item count badge
        $('#itemCountBadge').text('0').removeClass('bg-success bg-primary').addClass('bg-secondary');

        // Step 1: Load core timeline data
        loadTimelineData(filter);
    }

    /**
     * Step 1: Load core timeline data
     * @param {string} filter - Filter to apply to data
     */
    function loadTimelineData(filter) {
        // Get data endpoint from config
        const config = GanttChart.Core.getConfig();
        const endpoint = config.dataEndpoint;

        // Add filter parameter if not 'all'
        const url = new URL(endpoint, window.location.href);
        if (filter && filter !== 'all') {
            url.searchParams.append('filter', filter);
        }

        // Perform AJAX request for timeline data
        $.ajax({
            url: url.toString(),
            method: 'GET',
            dataType: 'json',
            cache: false,
            success: function(response) {
                handleTimelineSuccess(response, filter);
            },
            error: function(xhr, status, error) {
                handleTimelineError(xhr, status, error);
            }
        });
    }

    /**
     * Handle successful timeline response
     * @param {Object} response - Server response
     * @param {string} filter - Current filter
     */
    function handleTimelineSuccess(response, filter) {
        try {
            // Validate response structure
            if (!response || !response.tasks || !response.dateRange) {
                console.error('Invalid timeline response format:', response);
                GanttChart.Core.showNoItems();
                return;
            }

            const tasks = response.tasks;
            const dateRange = response.dateRange;

            // Process tasks data to normalize property names if needed
            const processedTasks = tasks.map(task => {
                // Ensure consistent property names
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

                // Generate task bars (without badges/workweeks initially)
                GanttChart.Bars.generate(
                    processedTasks,
                    dateRange.start,
                    dateRange.end
                );

                // Initialize interactions
                GanttChart.Interactions.init();
                GanttChart.Interactions.resetFilters();

                // Apply any existing status filter
                if (GanttChart.Core.getState().currentStatusFilter !== 'all') {
                    const previousFilter = GanttChart.Core.getState().currentStatusFilter;
                    $('.gantt-filter-btn').removeClass('active');
                    $(`.gantt-filter-btn[data-filter="${previousFilter}"]`).addClass('active');
                    GanttChart.Interactions.filterItems(previousFilter);
                }

                // Update the item count badge
                GanttChart.Interactions.updateItemCount();

                // Show chart
                GanttChart.Core.showChart();

                // Step 2: Load enhancement data
                loadEnhancementData(processedTasks, filter);

            } else {
                // Show no items message
                GanttChart.Core.showNoItems();
            }
        } catch (error) {
            console.error('Error processing timeline data:', error);
            handleTimelineError(null, 'error', error.message || 'Unknown error');
        }
    }

    /**
     * Step 2: Load enhancement data (badges and workweeks)
     * @param {Array} tasks - Array of task objects
     * @param {string} filter - Current filter
     */
    function loadEnhancementData(tasks, filter) {
        // No need to extract RowGroupIDs - just pass the same filter
        // Load badges and workweeks simultaneously
        Promise.allSettled([
            loadBadgeData(filter),
            loadWorkweekData(filter)
        ]).then(results => {
            const badgeResult = results[0];
            const workweekResult = results[1];

            // Apply badge data if successful
            if (badgeResult.status === 'fulfilled' && badgeResult.value) {
                applyBadgeData(badgeResult.value);
            } else if (badgeResult.status === 'rejected') {
                console.error('Badge data loading failed:', badgeResult.reason);
            }

            // Apply workweek data if successful
            if (workweekResult.status === 'fulfilled' && workweekResult.value) {
                applyWorkweekData(workweekResult.value);
            } else if (workweekResult.status === 'rejected') {
                console.error('Workweek data loading failed:', workweekResult.reason);
            }
        });
    }

    /**
     * Load badge data using the same filter approach as timeline
     * @param {string} filter - Current filter
     * @returns {Promise} Promise that resolves with badge data
     */
    function loadBadgeData(filter) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: 'ajax/get_timeline_badges.php',
                method: 'GET',
                dataType: 'json',
                data: {
                    filter: filter
                },
                success: function(response) {
                    if (response.error) {
                        reject(new Error(response.error));
                    } else {
                        resolve(response);
                    }
                },
                error: function(xhr, status, error) {
                    reject(new Error(`Badge data request failed: ${error}`));
                }
            });
        });
    }

    /**
     * Load workweek data using the same filter approach as timeline
     * @param {string} filter - Current filter
     * @returns {Promise} Promise that resolves with workweek data
     */
    function loadWorkweekData(filter) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: 'ajax/get_timeline_workweeks.php',
                method: 'GET',
                dataType: 'json',
                data: {
                    filter: filter
                },
                success: function(response) {
                    if (response.error) {
                        reject(new Error(response.error));
                    } else {
                        resolve(response);
                    }
                },
                error: function(xhr, status, error) {
                    reject(new Error(`Workweek data request failed: ${error}`));
                }
            });
        });
    }

    /**
     * Apply badge data to existing task rows
     * @param {Object} badgeData - Badge data keyed by RowGroupID
     */
    function applyBadgeData(badgeData) {
        $('.task-row').each(function() {
            const $row = $(this);
            const rowGroupId = $row.attr('data-row-group-id') ||
                $row.find('.task-bar').attr('data-row-group-id');

            if (rowGroupId && badgeData[rowGroupId]) {
                const badges = badgeData[rowGroupId];

                // Apply badges to the task row
                if (typeof GanttChart.Bars.applyBadges === 'function') {
                    GanttChart.Bars.applyBadges($row, badges);
                } else {
                    // Fallback: store badge data as attributes
                    $row.attr('data-badge-data', JSON.stringify(badges));

                    // Update existing badges if they exist
                    const $timeline = $row.find('.gantt-timeline');

                    // Client approval badge
                    if (badges.ClientApprovalPercentComplete !== undefined) {
                        $row.attr('data-client-approval', badges.ClientApprovalPercentComplete);

                        // Update or create client approval badge
                        let $clientBadge = $timeline.find('.client-approval-badge');
                        if ($clientBadge.length === 0) {
                            $clientBadge = $('<span class="percentage-badge client-approval-badge"></span>');
                            $timeline.append($clientBadge);
                        }

                        const clientApproval = badges.ClientApprovalPercentComplete;
                        const clientColorClass = clientApproval > 99 ? 'badge-success' :
                            (clientApproval >= 5 ? 'badge-warning' : 'badge-danger');

                        $clientBadge
                            .removeClass('badge-success badge-warning badge-danger')
                            .addClass(clientColorClass)
                            .text(Math.round(clientApproval * 10) / 10 + '%')
                            .attr('title', `Client Approval: ${Math.round(clientApproval * 10) / 10}%`);
                    }

                    // Add other badges similarly...
                    // This is a simplified implementation
                }
            }
        });

        console.log('Badge data applied to task rows');
    }

    /**
     * Apply workweek data to existing task rows
     * @param {Object} workweekData - Workweek data keyed by RowGroupID
     */
    function applyWorkweekData(workweekData) {
        $('.task-row').each(function() {
            const $row = $(this);
            const rowGroupId = $row.attr('data-row-group-id');

            if (rowGroupId && workweekData[rowGroupId]) {
                const workweeks = workweekData[rowGroupId];

                // Store workweek data as attributes
                $row.attr('data-workweek-json', workweeks.WorkWeekJSON);
                $row.attr('data-workweek-count', workweeks.WorkWeekCount);

                // Apply workweek visualization using GanttChart.Bars
                if (typeof GanttChart.Bars.addWorkweekDots === 'function') {
                    const $timeline = $row.find('.gantt-timeline');
                    if ($timeline.length && workweeks.WorkWeekCount > 0) {
                        try {
                            const parsedWorkweeks = JSON.parse(workweeks.WorkWeekJSON);
                            GanttChart.Bars.addWorkweekDots($timeline, parsedWorkweeks);
                        } catch (e) {
                            console.error('Error parsing workweek JSON for row', rowGroupId, ':', e);
                        }
                    }
                }
            }
        });

        console.log('Workweek data applied to task rows');
    }

    /**
     * Handle timeline loading error
     * @param {Object} xhr - XHR object
     * @param {string} status - Error status
     * @param {string} error - Error message
     */
    function handleTimelineError(xhr, status, error) {
        console.error('Error loading timeline data:', error);

        // Reset item count badge
        $('#itemCountBadge').text('0').removeClass('bg-success bg-primary').addClass('bg-secondary');

        // Show no items message
        GanttChart.Core.showNoItems();

        // Show error alert
        alert('Error loading timeline data: ' + (error || 'Could not connect to server. Please try again later.'));
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

            // Create row data
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
        loadTimelineData: loadTimelineData,
        loadBadgeData: loadBadgeData,
        loadWorkweekData: loadWorkweekData,
        applyBadgeData: applyBadgeData,
        applyWorkweekData: applyWorkweekData,
        exportToCsv: exportToCsv
    };
})();