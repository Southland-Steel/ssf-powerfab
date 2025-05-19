/**
 * File: js/gantt-ajax.js
 * Gantt Chart AJAX Module
 * Handles all AJAX communication for the Gantt chart
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

        // Reset the item count badge while loading
        if ($('#itemCountBadge').length) {
            $('#itemCountBadge').text('0');
        }

        // If Custom module exists with loadProjectData function, use it
        if (GanttChart.Custom && typeof GanttChart.Custom.loadProjectData === 'function') {
            // Delegate to the project-specific data loader
            GanttChart.Custom.loadProjectData(filter);
        } else {
            // Original implementation as fallback
            console.log('Using standard data loader with filter:', filter);

            // Determine the correct endpoint to use
            const endpoint = GanttChart.Core.getConfig().dataEndpoint || 'ajax/get_timeline_data.php';

            // Perform AJAX request
            $.ajax({
                url: endpoint,
                method: 'GET',
                data: { filter: filter },
                dataType: 'json',
                cache: false,
                success: handleDataSuccess,
                error: handleDataError
            });
        }
    }

    /**
     * Handle successful data load
     * @param {Object} response - Server response
     */
    function handleDataSuccess(response) {
        try {

            // Validate response
            if (!response) {
                GanttChart.Core.showNoItems();
                return;
            }

            // Extract data structure - try to handle different formats
            const sequences = response.sequences || (response.data && response.data.sequences) || [];
            const dateRange = response.dateRange || (response.data && response.data.date_range) || null;

            // Validate data fields
            if (!dateRange || !dateRange.start || !dateRange.end) {
                console.error('Invalid date range in response:', dateRange);
                GanttChart.Core.showNoItems();
                return;
            }

            // Update state with new data
            GanttChart.Core.setState({
                items: sequences,
                dateRange: dateRange
            });

            // Explicitly initialize TimeUtils with date range
            if (GanttChart.TimeUtils && typeof GanttChart.TimeUtils.initialize === 'function') {
                GanttChart.TimeUtils.initialize(dateRange.start, dateRange.end);
            }

            if (sequences.length > 0) {
                // Update the item count badge if it exists
                if ($('#itemCountBadge').length) {
                    updateItemCountBadge(sequences.length);
                }

                // Generate timeline
                console.log('Generating timeline');
                GanttChart.Timeline.generate(
                    dateRange.start,
                    dateRange.end
                );

                // Generate item rows
                console.log('Generating item rows');
                GanttChart.Items.generate(
                    sequences,
                    dateRange.start,
                    dateRange.end
                );

                // Initialize interactions
                console.log('Initializing interactions');
                GanttChart.Interactions.init();

                // Show chart
                GanttChart.Core.showChart();
            } else {
                // Set the item count badge to 0
                if ($('#itemCountBadge').length) {
                    $('#itemCountBadge').text('0').removeClass('bg-success bg-primary').addClass('bg-secondary');
                }

                // Show no items message
                GanttChart.Core.showNoItems();
            }
        } catch (error) {
            console.error('Error in handleDataSuccess:', error);
            handleDataError(null, 'error', error.message || 'Unknown error');
        }
    }

    /**
     * Update item count badge
     * @param {number} count - Number of items
     */
    function updateItemCountBadge(count) {
        const $badge = $('#itemCountBadge');
        if (!$badge.length) return;

        $badge.text(count);

        // Change badge color based on count
        if (count > 20) {
            $badge.removeClass('bg-secondary bg-success').addClass('bg-primary');
        } else if (count > 0) {
            $badge.removeClass('bg-secondary bg-primary').addClass('bg-success');
        } else {
            $badge.removeClass('bg-success bg-primary').addClass('bg-secondary');
        }
    }

    /**
     * Handle data load error
     * @param {Object} xhr - XHR object
     * @param {string} status - Error status
     * @param {string} error - Error message
     */
    function handleDataError(xhr, status, error) {
        console.error('Error loading Gantt data:', error);

        // Reset the item count badge on error
        if ($('#itemCountBadge').length) {
            $('#itemCountBadge').text('0').removeClass('bg-success bg-primary').addClass('bg-secondary');
        }

        GanttChart.Core.showNoItems();

        // Show alert
        alert('Error loading data: ' + (error || 'Could not connect to server. Please try again later.'));
    }

    /**
     * Load item details
     * @param {number|string} itemId - Item ID to load details for
     * @param {Function} callback - Callback function to handle response
     */
    function loadItemDetails(itemId, callback) {
        // Determine the correct endpoint to use
        const endpoint = GanttChart.Core.getConfig().detailsEndpoint || 'ajax/get_item_details.php';

        $.ajax({
            url: endpoint,
            method: 'GET',
            data: { id: itemId },
            dataType: 'json',
            cache: false,
            success: function(response) {
                if (response.success && typeof callback === 'function') {
                    callback(response.data);
                } else {
                    console.error('Error loading item details:', response.message || 'Unknown error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading item details:', error);
            }
        });
    }

    /**
     * Export Gantt chart data to CSV
     */
    function exportToCsv() {
        const state = GanttChart.Core.getState();
        const items = state.items;

        if (!items || items.length === 0) {
            alert('No data to export');
            return;
        }

        // Prepare CSV header
        const headers = [
            'ID',
            'Title',
            'Status',
            'Progress',
            'Start Date',
            'End Date',
            'Assignee',
            'Priority',
            'Description'
        ];

        // Prepare CSV rows
        const csvRows = [
            headers.join(',')
        ];

        // Add data rows
        items.forEach(item => {
            const row = [
                item.id,
                csvEscapeValue(item.title || ''),
                csvEscapeValue(item.status || ''),
                item.percentage !== undefined ? item.percentage + '%' : '',
                item.start_date ? GanttChart.Core.formatDate(item.start_date) : '',
                item.end_date ? GanttChart.Core.formatDate(item.end_date) : '',
                csvEscapeValue(item.assignee || ''),
                csvEscapeValue(item.priority || ''),
                csvEscapeValue(item.description || '')
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
        // If value contains commas, quotes, or newlines, wrap in quotes and escape internal quotes
        if (/[",\n\r]/.test(value)) {
            return '"' + value.replace(/"/g, '""') + '"';
        }
        return value;
    }

    // Public API
    return {
        loadData: loadData,
        loadItemDetails: loadItemDetails,
        exportToCsv: exportToCsv
    };
})();