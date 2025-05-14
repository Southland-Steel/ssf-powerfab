/**
 * workweeks/js/workweeks-help.js
 * Provides help functionality for the workweeks module
 */

// Load the markdown help file and convert it to HTML
function loadHelpContent() {
    return fetch('docs/workweeks.md')
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to load help document');
            }
            return response.text();
        })
        .then(markdown => {
            return convertMarkdownToHtml(markdown);
        });
}

// Convert markdown to HTML (simple implementation)
function convertMarkdownToHtml(markdown) {
    // Handle headings
    let html = markdown
        .replace(/^# (.*$)/gm, '<h2 class="mt-4 mb-3">$1</h2>')
        .replace(/^## (.*$)/gm, '<h3 class="mt-3 mb-2">$1</h3>')
        .replace(/^### (.*$)/gm, '<h4 class="mt-2 mb-2">$1</h4>');

    // Handle lists
    html = html
        .replace(/^\* (.*$)/gm, '<li>$1</li>')
        .replace(/<\/li>\n<li>/g, '</li><li>');

    // Wrap lists in <ul> tags
    html = html.replace(/(<li>.*<\/li>)/g, '<ul>$1</ul>');

    // Handle code blocks
    html = html.replace(/```(.+?)```/gs, '<pre class="bg-light p-3 rounded"><code>$1</code></pre>');

    // Handle inline formatting
    html = html
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/\*(.*?)\*/g, '<em>$1</em>')
        .replace(/`(.*?)`/g, '<code>$1</code>');

    // Handle paragraphs
    html = html.replace(/\n\n/g, '</p><p>');

    // Wrap with paragraphs
    html = '<p>' + html + '</p>';

    // Fix any double <ul> tags or other issues
    html = html
        .replace(/<\/ul>\s*<ul>/g, '')
        .replace(/<p><ul>/g, '<ul>')
        .replace(/<\/ul><\/p>/g, '</ul>');

    return html;
}

// Show the help modal with the markdown content
function showHelpModal() {
    loadHelpContent()
        .then(htmlContent => {
            document.getElementById('helpContent').innerHTML = htmlContent;
            const helpModal = new bootstrap.Modal(document.getElementById('helpModal'));
            helpModal.show();
        })
        .catch(error => {
            console.error('Error loading help content:', error);
            alert('Could not load help content. Please try again later.');
        });
}

// Initialize the help system
function initializeHelp() {
    // Add help button if it doesn't exist
    if (!document.getElementById('helpButton')) {
        const helpBtn = document.createElement('button');
        helpBtn.id = 'helpButton';
        helpBtn.className = 'btn btn-sm btn-outline-secondary position-fixed';
        helpBtn.style.right = '10px';
        helpBtn.style.bottom = '10px';
        helpBtn.style.zIndex = '1000';
        helpBtn.innerHTML = '<i class="bi bi-question-circle"></i> Help';
        helpBtn.onclick = showHelpModal;
        document.body.appendChild(helpBtn);
    }
}

// Initialize help system when the DOM is ready
document.addEventListener('DOMContentLoaded', initializeHelp);