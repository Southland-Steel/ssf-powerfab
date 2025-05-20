/**
 * File: js/gantt-interactions.js
 * Handles user interactions with the Gantt chart
 */
GanttChart.Interactions = (function() {
    'use strict';

    /**
     * Initialize all interactions
     */
    function initialize() {
        initializeRowHover();
        initializeZoom();
        initializeFilterButtons();
    }

    /**
     * Initialize row hover effects
     */
    function initializeRowHover() {
        $(document).on('mouseenter', '.gantt-row', function() {
            $(this).addClass('hovered');
        }).on('mouseleave', '.gantt-row', function() {
            $(this).removeClass('hovered');
        });
    }

    /**
     * Initialize zoom functionality
     */
    function initializeZoom() {
        // Set up zoom buttons
        const $zoomIn = $('#ganttZoomIn');
        const $zoomOut = $('#ganttZoomOut');
        const $zoomReset = $('#ganttZoomReset');

        if (!$zoomIn.length || !$zoomOut.length || !$zoomReset.length) {
            return;
        }

        let zoomLevel = 1; // Default zoom level

        // Zoom in button
        $zoomIn.on('click', function() {
            zoomLevel = Math.min(zoomLevel + 0.25, 2);
            applyZoom(zoomLevel);
        });

        // Zoom out button
        $zoomOut.on('click', function() {
            zoomLevel = Math.max(zoomLevel - 0.25, 0.5);
            applyZoom(zoomLevel);
        });

        // Reset zoom button
        $zoomReset.on('click', function() {
            zoomLevel = 1;
            applyZoom(zoomLevel);
        });
    }

    /**
     * Apply zoom level to Gantt chart
     * @param {number} level - Zoom level (0.5 to 2)
     */
    function applyZoom(level) {
        $('.gantt-container').css('--zoom-level', level);

        // Update heights based on zoom level
        const baseRowHeight = 50; // Base height in px
        const baseTimelineHeaderHeight = 50;
        const baseTaskBarHeight = 30;

        const rowHeight = baseRowHeight * level;
        const headerHeight = baseTimelineHeaderHeight * level;
        const barHeight = baseTaskBarHeight * level;

        $('.gantt-row').css('height', rowHeight + 'px');
        $('.gantt-timeline-header').css('height', headerHeight + 'px');
        $('.task-bar').css('height', barHeight + 'px');
        $('.task-bar').css('top', ((rowHeight - barHeight) / 2) + 'px');
    }

    /**
     * Initialize filter buttons
     */
    function initializeFilterButtons() {
        $('.gantt-filter-btn').on('click', function() {
            $('.gantt-filter-btn').removeClass('active');
            $(this).addClass('active');

            const filter = $(this).data('filter');
            filterItems(filter);
        });
    }

    /**
     * Filter items based on criteria
     * @param {string} filter - Filter identifier
     */
    function filterItems(filter) {
        // Get the current project filter from config
        const config = GanttChart.Core.getConfig();
        const currentProjectFilter = config.currentFilter;

        // Update state to track the current status filter
        GanttChart.Core.getState().currentStatusFilter = filter;

        // If filter is 'all', show all rows (respecting current project filter)
        if (filter === 'all') {
            if (currentProjectFilter && currentProjectFilter !== 'all' &&
                currentProjectFilter.match(/^[A-Z0-9-]+$/)) {
                // If a project filter is active, only show rows for that project
                $('.task-row').hide();
                $(`.task-row[data-project="${currentProjectFilter}"]`).show();
            } else {
                // Otherwise show all rows
                $('.task-row').show();
            }
            updateItemCount();
            return;
        }

        // Hide all rows first
        $('.task-row').hide();

        // Build a selector based on the status filter
        let selector;
        switch (filter) {
            case 'in-progress':
                selector = '.task-bar.status-in-progress';
                break;
            case 'not-started':
                selector = '.task-bar.status-not-started';
                break;
            case 'completed':
                selector = '.task-bar.status-completed';
                break;
            case 'late':
                selector = '.task-bar.status-late';
                break;
            case 'level-1':
                selector = '[data-level="1"]';
                break;
            case 'level-2':
                selector = '[data-level="2"]';
                break;
            case 'client-approval-complete':
                // Special case, handled separately below
                break;
            default:
                // Default to showing all (respecting current project filter)
                if (currentProjectFilter && currentProjectFilter !== 'all' &&
                    currentProjectFilter.match(/^[A-Z0-9-]+$/)) {
                    $(`.task-row[data-project="${currentProjectFilter}"]`).show();
                } else {
                    $('.task-row').show();
                }
                updateItemCount();
                return;
        }

        // Handle the client approval filter separately (it uses a function filter)
        if (filter === 'client-approval-complete') {
            let $rows = $('.task-row');

            // Apply project filter if one is active
            if (currentProjectFilter && currentProjectFilter !== 'all' &&
                currentProjectFilter.match(/^[A-Z0-9-]+$/)) {
                $rows = $rows.filter(`[data-project="${currentProjectFilter}"]`);
            }

            // Filter by client approval percentage
            $rows.filter(function() {
                const approvalValue = parseFloat($(this).attr('data-client-approval') || 0);
                return approvalValue >= 99;
            }).show();
        } else if (selector) {
            // Apply both the status filter and the project filter if one is active
            let $rows = $(selector).closest('.task-row');

            if (currentProjectFilter && currentProjectFilter !== 'all' &&
                currentProjectFilter.match(/^[A-Z0-9-]+$/)) {
                $rows = $rows.filter(`[data-project="${currentProjectFilter}"]`);
            }

            $rows.show();
        }

        // Update the item count badge
        updateItemCount();

        // Show "no items" message if no rows are visible
        if ($('.task-row:visible').length === 0) {
            $(GanttChart.Core.getConfig().noItemsMessage).show();
            $(GanttChart.Core.getConfig().container).hide();
        } else {
            $(GanttChart.Core.getConfig().noItemsMessage).hide();
            $(GanttChart.Core.getConfig().container).show();
        }
    }

    /**
     * Reset filters to show all tasks (respecting current project filter)
     */
    function resetFilters() {
        // Get the current project filter
        const config = GanttChart.Core.getConfig();
        const currentProjectFilter = config.currentFilter;

        // Reset status filter state
        GanttChart.Core.getState().currentStatusFilter = 'all';

        // Remove 'active' class from all filter buttons
        $('.gantt-filter-btn').removeClass('active');

        // Add 'active' class to the 'All Tasks' button
        $('.gantt-filter-btn[data-filter="all"]').addClass('active');

        // Show all rows (respecting current project filter)
        if (currentProjectFilter && currentProjectFilter !== 'all' &&
            currentProjectFilter.match(/^[A-Z0-9-]+$/)) {
            $('.task-row').hide();
            $(`.task-row[data-project="${currentProjectFilter}"]`).show();
        } else {
            $('.task-row').show();
        }

        // Update the item count
        updateItemCount();
    }

    /**
     * Update item count badge
     */
    function updateItemCount() {
        const count = $('.task-row:visible').length;
        $('#itemCountBadge').text(count);

        // Change badge color based on count
        const $badge = $('#itemCountBadge');

        if (count > 20) {
            $badge.removeClass('bg-secondary bg-success').addClass('bg-primary');
        } else if (count > 0) {
            $badge.removeClass('bg-secondary bg-primary').addClass('bg-success');
        } else {
            $badge.removeClass('bg-success bg-primary').addClass('bg-secondary');
        }
    }

    /**
     * Refresh interactions after data update
     */
    function refresh() {
        updateItemCount();
    }

    // Public API
    return {
        init: initialize,
        refresh: refresh,
        filterItems: filterItems,
        updateItemCount: updateItemCount,
        resetFilters: resetFilters
    };
})();