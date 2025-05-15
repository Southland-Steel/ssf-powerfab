/**
 * File: js/gantt-help.js
 * Gantt Chart Help Module
 * Handles help functionality for the Gantt chart
 */
GanttChart.Help = (function() {
    'use strict';

    /**
     * Initialize help functionality
     */
    function initialize() {
        // Add click handler to help button
        $('#ganttHelpBtn').on('click', showHelpModal);
    }

    /**
     * Show help modal with content from markdown file
     */
    function showHelpModal() {
        // Show loading state
        $('#ganttHelpContent').html('<div class="text-center my-3"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-2">Loading help content...</p></div>');

        // Show the modal while loading
        const helpModal = new bootstrap.Modal(document.getElementById('ganttHelpModal'));
        helpModal.show();

        // Determine help document path
        const helpDocPath = GanttChart.Core.getConfig().helpDocPath || 'docs/gantt-help.md';

        // Fetch the markdown file
        $.ajax({
            url: helpDocPath,
            dataType: 'text',
            cache: false,
            success: function(markdownContent) {
                // Convert markdown to HTML (if you have a markdown parser)
                const htmlContent = convertMarkdownToHTML(markdownContent);

                // Set the content
                $('#ganttHelpContent').html(htmlContent);
            },
            error: function() {
                // Show error message
                $('#ganttHelpContent').html('<div class="alert alert-danger">Error loading help content. Please try again later.</div>');
            }
        });
    }

    /**
     * Convert markdown to HTML using marked.js
     * @param {string} markdown - Markdown content
     * @return {string} HTML content
     */
    function convertMarkdownToHTML(markdown) {
        // Check if marked library is available
        if (typeof marked !== 'undefined') {
            // Use marked.js for proper markdown parsing
            return marked.parse(markdown);
        } else {
            // Fallback to simple parsing if marked is not available
            // This is a very basic implementation

            // Replace headers
            let html = markdown
                .replace(/^# (.*$)/gm, '<h3>$1</h3>')
                .replace(/^## (.*$)/gm, '<h4>$1</h4>')
                .replace(/^### (.*$)/gm, '<h5>$1</h5>');

            // Replace paragraphs (lines that don't start with special characters)
            html = html.replace(/^(?![#\-\*\d])(.*$)/gm, '<p>$1</p>');

            // Replace lists
            html = html.replace(/^\- (.*$)/gm, '<li>$1</li>');

            // Wrap lists
            html = html.replace(/(<li>.*<\/li>)\n(?!<li>)/g, '<ul>$1</ul>');

            // Replace empty lines between list items with nothing
            html = html.replace(/<\/li>\n<li>/g, '</li><li>');

            // Bold
            html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');

            // Italic
            html = html.replace(/\*(.*?)\*/g, '<em>$1</em>');

            return html;
        }
    }

    // Public API
    return {
        init: initialize
    };
})();