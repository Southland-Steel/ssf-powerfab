/**
 * File: js/gantt-interactions.js
 * Gantt Chart Interactions Module
 * Handles user interactions with the Gantt chart
 */
GanttChart.Interactions = (function() {
    'use strict';

    /**
     * Initialize all interactions
     */
    function initialize() {
        initializeRowHover();
        initializeTooltips();
        initializeDetailPanels();
        initializeZoom();
        initializeFiltering();
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
     * Initialize tooltip behaviors
     */
    function initializeTooltips() {
        // For date markers, warnings, etc.
        $(document).on('mouseenter', '.date-marker, .date-conflict-warning, .item-bar', function() {
            // Use browser's native title attribute for simplicity
            // Could be enhanced with custom tooltips if needed
        });
    }

    /**
     * Initialize detail panels
     */
    function initializeDetailPanels() {
        // Toggle detail panel when clicking an item
        $(document).on('click', '.item-bar', function(e) {
            // Prevent navigation if clicked on a link inside the bar
            if ($(e.target).closest('a').length > 0) return;

            const $row = $(this).closest('.gantt-row');
            const itemId = $row.data('item-id');

            // Toggle detail panel
            if ($row.hasClass('expanded')) {
                hideDetailPanel($row);
            } else {
                // Close any other open panels
                $('.gantt-row.expanded').each(function() {
                    hideDetailPanel($(this));
                });

                showDetailPanel($row, itemId);
            }
        });

        // Close detail panel when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.gantt-row, .item-bar, .detail-panel').length) {
                $('.gantt-row.expanded').each(function() {
                    hideDetailPanel($(this));
                });
            }
        });
    }

    /**
     * Show detail panel for an item
     * @param {jQuery} $row - Row element
     * @param {number|string} itemId - Item ID
     */
    function showDetailPanel($row, itemId) {
        const item = findItemById(itemId);
        if (!item) return;

        // Create detail panel
        const $detailPanel = $('<div class="detail-panel"></div>');

        // Add content based on item data
        $detailPanel.html(`
            <div class="detail-header">
                <h3>${item.title || 'Item #' + item.id}</h3>
                <button class="close-panel-btn">&times;</button>
            </div>
            <div class="detail-content">
                ${item.description ? `<p class="item-description">${item.description}</p>` : ''}
                <div class="detail-grid">
                    <div class="detail-row">
                        <div class="detail-label">Status:</div>
                        <div class="detail-value status-badge ${item.status?.toLowerCase()}">${item.status || 'Not Set'}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Progress:</div>
                        <div class="detail-value">${item.percentage !== undefined ? item.percentage + '%' : 'Not Started'}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Start Date:</div>
                        <div class="detail-value">${item.start_date ? GanttChart.Core.formatDate(item.start_date) : 'Not Set'}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">End Date:</div>
                        <div class="detail-value">${item.end_date ? GanttChart.Core.formatDate(item.end_date) : 'Not Set'}</div>
                    </div>
                    ${item.assignee ? `
                    <div class="detail-row">
                        <div class="detail-label">Assigned To:</div>
                        <div class="detail-value">${item.assignee}</div>
                    </div>
                    ` : ''}
                    ${item.priority ? `
                    <div class="detail-row">
                        <div class="detail-label">Priority:</div>
                        <div class="detail-value priority-badge ${item.priority.toLowerCase()}">${item.priority}</div>
                    </div>
                    ` : ''}
                </div>
                ${createMilestonesSection(item)}
                ${createNotesSection(item)}
                ${createLinksSection(item)}
            </div>
        `);

        // Add click handler for close button
        $detailPanel.find('.close-panel-btn').on('click', function() {
            hideDetailPanel($row);
        });

        // Insert after the row
        $row.after($detailPanel);
        $row.addClass('expanded');

        // Scroll the detail panel into view
        $detailPanel[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    /**
     * Hide detail panel for a row
     * @param {jQuery} $row - Row element
     */
    function hideDetailPanel($row) {
        $row.removeClass('expanded');
        $row.next('.detail-panel').remove();
    }

    /**
     * Create milestones section for detail panel
     * @param {Object} item - Item data
     * @return {string} HTML for milestones section
     */
    function createMilestonesSection(item) {
        if (!item.milestones || item.milestones.length === 0) return '';

        let milestonesHtml = `
            <div class="detail-section">
                <h4>Milestones</h4>
                <ul class="milestones-list">
        `;

        item.milestones.forEach(milestone => {
            const date = milestone.date ? GanttChart.Core.formatDate(milestone.date) : 'No date';
            const completed = milestone.completed ? 'completed' : '';

            milestonesHtml += `
                <li class="milestone-item ${completed}">
                    <span class="milestone-title">${milestone.title}</span>
                    <span class="milestone-date">${date}</span>
                    ${milestone.completed ? '<span class="milestone-status">✓</span>' : ''}
                </li>
            `;
        });

        milestonesHtml += `
                </ul>
            </div>
        `;

        return milestonesHtml;
    }

    /**
     * Create notes section for detail panel
     * @param {Object} item - Item data
     * @return {string} HTML for notes section
     */
    function createNotesSection(item) {
        if (!item.notes) return '';

        return `
            <div class="detail-section">
                <h4>Notes</h4>
                <div class="item-notes">${item.notes}</div>
            </div>
        `;
    }

    /**
     * Create links section for detail panel
     * @param {Object} item - Item data
     * @return {string} HTML for links section
     */
    function createLinksSection(item) {
        if (!item.links || item.links.length === 0) return '';

        let linksHtml = `
            <div class="detail-section">
                <h4>Related Links</h4>
                <ul class="links-list">
        `;

        item.links.forEach(link => {
            linksHtml += `
                <li class="link-item">
                    <a href="${link.url}" target="_blank" class="link-url">
                        ${link.title || link.url}
                    </a>
                    ${link.description ? `<span class="link-description"> - ${link.description}</span>` : ''}
                </li>
            `;
        });

        linksHtml += `
                </ul>
            </div>
        `;

        return linksHtml;
    }

    /**
     * Find item by ID
     * @param {number|string} itemId - Item ID to find
     * @return {Object|null} Found item or null
     */
    function findItemById(itemId) {
        const state = GanttChart.Core.getState();
        return state.items.find(item => item.id == itemId) || null;
    }

    /**
     * Initialize zoom functionality
     */
    function initializeZoom() {
        // Only initialize if zoom buttons exist
        const $zoomIn = $('#ganttZoomIn');
        const $zoomOut = $('#ganttZoomOut');
        const $zoomReset = $('#ganttZoomReset');

        if ($zoomIn.length === 0 || $zoomOut.length === 0) return;

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
        const $container = $(GanttChart.Core.getConfig().container);
        $container.css('--zoom-level', level);
    }

    /**
     * Initialize filtering functionality
     */
    function initializeFiltering() {
        // Set up custom filter buttons if they exist
        $(document).on('click', '.gantt-filter-btn', function() {
            const filter = $(this).data('filter');
            if (filter) {
                // FIXED: Instead of just filtering UI elements, reload data with filter
                // This ensures we get fresh data from the server with the filter applied
                GanttChart.Core.setConfig({ currentFilter: filter });
                GanttChart.Ajax.loadData(filter);

                // Update active state
                $('.gantt-filter-btn').removeClass('active');
                $(this).addClass('active');
            }
        });
    }

    /**
     * Filter items by specific criteria
     * @param {string} filter - Filter name
     */
    function filterItems(filter) {
        console.log("Client-side filtering with: " + filter);
        // Skip if no filter or 'all'
        if (!filter || filter === 'all') {
            $('.gantt-row').show();
            return;
        }

        // Hide all rows first
        $('.gantt-row').hide();

        // Show rows that match the filter
        if (filter === 'in-progress') {
            $('.gantt-row .item-bar.in-progress').closest('.gantt-row').show();
        } else if (filter === 'completed') {
            $('.gantt-row .item-bar.completed').closest('.gantt-row').show();
        } else if (filter === 'not-started') {
            $('.gantt-row .item-bar.not-started').closest('.gantt-row').show();
        } else if (filter === 'overdue') {
            $('.gantt-row .date-conflict-warning.overdue').closest('.gantt-row').show();
        } else if (filter === 'at-risk') {
            $('.gantt-row .date-conflict-warning.at-risk').closest('.gantt-row').show();
        } else if (filter === 'high-priority') {
            $('.gantt-row.priority-high').show();
        } else if (filter.startsWith('status-')) {
            // Filter by status if filter is 'status-value'
            const statusMatch = filter.match(/^status-(.+)$/);
            if (statusMatch) {
                const status = statusMatch[1];
                $(`.gantt-row[data-status="${status}"]`).show();
            }
        }
    }

    /**
     * Refresh interactions after data update
     */
    function refresh() {
        // Reinitialize interactions that need refreshing
        initializeTooltips();
    }

    // Public API
    return {
        init: initialize,
        refresh: refresh,
        filterItems: filterItems
    };
})();