/**
 * File: js/gantt-help.js
 * Handles loading and displaying help documentation
 */
GanttChart.Help = (function() {
    'use strict';

    /**
     * Initialize help functionality
     */
    function initialize() {
        // Set up help button click handler
        $('#ganttHelpBtn').on('click', function() {
            loadHelpContent();
            $('#ganttHelpModal').modal('show');
        });
    }

    /**
     * Load help content from markdown file
     */
    function loadHelpContent() {
        const $content = $('#ganttHelpContent');

        // Show loading indicator
        $content.html(`
            <div class="text-center">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading help content...</span>
                </div>
                <p class="mt-2">Loading help documentation...</p>
            </div>
        `);

        // Load the markdown file
        $.ajax({
            url: 'docs/gantt-help.md',
            dataType: 'text',
            success: function(markdown) {
                // Convert markdown to HTML using marked library
                const html = marked.parse(markdown);
                $content.html(html);
            },
            error: function(xhr, status, error) {
                $content.html(`
                    <div class="alert alert-danger">
                        <strong>Error loading help documentation:</strong> ${error}
                    </div>
                `);
            }
        });
    }

    // Public API
    return {
        init: initialize,
        loadHelpContent: loadHelpContent
    };
})();