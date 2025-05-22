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
     * Filter items based on criteria - with container visibility fix
     * @param {string} filter - Filter identifier
     */
    function filterItems(filter) {
        console.log("Filtering with:", filter);

        // Get references to key elements
        const $container = $(GanttChart.Core.getConfig().container);
        const $noItemsMessage = $(GanttChart.Core.getConfig().noItemsMessage);

        // IMPORTANT FIX: Show container and hide the "no items" message immediately
        // This ensures we're starting with a visible container regardless of previous state
        $container.show();
        $noItemsMessage.hide();

        // Get the current project filter
        const config = GanttChart.Core.getConfig();
        const projectFilter = config.currentFilter;

        // Update state to track the current status filter
        GanttChart.Core.getState().currentStatusFilter = filter;

        // Reset all rows to be visible
        $('.task-row').show();

        // Step 1: Apply project filter
        if (projectFilter && projectFilter !== 'all') {
            $('.task-row').not(`[data-project="${projectFilter}"]`).hide();
        }

        // If filter is 'all', we're done
        if (filter === 'all') {
            updateItemCount();
            return;
        }

        // Step 2: Apply status filter to the visible rows
        const $visibleRows = $('.task-row:visible');

        switch (filter) {
            case 'in-progress':
                $visibleRows.each(function() {
                    if ($(this).find('.task-bar.status-in-progress').length === 0) {
                        $(this).hide();
                    }
                });
                break;

            case 'not-started':
                $visibleRows.each(function() {
                    if ($(this).find('.task-bar.status-not-started').length === 0) {
                        $(this).hide();
                    }
                });
                break;

            case 'completed':
                $visibleRows.each(function() {
                    if ($(this).find('.task-bar.status-completed').length === 0) {
                        $(this).hide();
                    }
                });
                break;

            case 'late':
                $visibleRows.each(function() {
                    if ($(this).find('.task-bar.status-late').length === 0) {
                        $(this).hide();
                    }
                });
                break;

            case 'level-1':
                $visibleRows.each(function() {
                    if ($(this).attr('data-level') !== '1') {
                        $(this).hide();
                    }
                });
                break;

            case 'level-2':
                $visibleRows.each(function() {
                    if ($(this).attr('data-level') !== '2') {
                        $(this).hide();
                    }
                });
                break;

            case 'client-approval-complete':
                $visibleRows.each(function() {
                    const approvalValue = parseFloat($(this).attr('data-client-approval') || 0);
                    if (approvalValue < 99) {
                        $(this).hide();
                    }
                });
                break;
        }

        // Update the item count badge
        updateItemCount();

        // Show "no items" message if no rows are visible
        const visibleCount = $('.task-row:visible').length;
        if (visibleCount === 0) {
            // Add debug logging to verify
            console.log("No visible rows after filtering - showing 'no items' message");
            $noItemsMessage.show();
            $container.hide();
        } else {
            // Add debug logging to verify
            console.log(`Found ${visibleCount} visible rows - showing container`);
            $noItemsMessage.hide();
            $container.show();
        }

        // Force container to be visible if we have items (as a failsafe)
        if (visibleCount > 0 && $container.is(':hidden')) {
            console.warn("Container was hidden despite having visible rows - forcing display");
            $container.show();
            $noItemsMessage.hide();
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