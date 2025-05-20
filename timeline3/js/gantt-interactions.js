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
        // If filter is 'all', show all rows
        if (filter === 'all') {
            $('.task-row').show();
            updateItemCount();
            return;
        }

        // Hide all rows first
        $('.task-row').hide();

        // Show rows based on filter
        switch (filter) {
            case 'in-progress':
                $('.task-bar.status-in-progress').closest('.task-row').show();
                break;
            case 'not-started':
                $('.task-bar.status-not-started').closest('.task-row').show();
                break;
            case 'completed':
                $('.task-bar.status-completed').closest('.task-row').show();
                break;
            case 'late':
                $('.task-bar.status-late').closest('.task-row').show();
                break;
            case 'level-1':
                $('.task-row[data-level="1"]').show();
                break;
            case 'level-2':
                $('.task-row[data-level="2"]').show();
                break;
            default:
                // If filter is a project ID, show rows matching that project
                if (filter.match(/^[A-Z0-9-]+$/)) {
                    $(`.task-row[data-group-id^="${filter}."]`).show();
                } else {
                    // Default to showing all
                    $('.task-row').show();
                }
                break;
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
        updateItemCount: updateItemCount
    };
})();