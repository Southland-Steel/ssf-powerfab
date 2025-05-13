<?php
// checkups/view_documentation.php

// Include utility functions
require_once '../includes/functions/utility_functions.php';

// Set page-specific variables
$page_title = 'Cutlist Invalidations Documentation';
$show_workweeks = false;

// Get the doc name from query string
$docName = isset($_GET['doc']) ? $_GET['doc'] : 'cutlist_invalidations';

// Define the path to the documentation file
$docPath = __DIR__ . '/docs/' . $docName . '.md';

// Check if the file exists
if (!file_exists($docPath)) {
    // Set error message
    $errorMessage = "Documentation file not found: {$docPath}";

    // Include header
    include_once '../includes/header.php';

    // Show error message
    echo '<div class="alert alert-danger mt-3">';
    echo '<i class="bi bi-exclamation-triangle-fill me-2"></i>';
    echo $errorMessage;
    echo '</div>';

    // Include footer
    include_once '../includes/footer.php';
    exit;
}

// Get the Markdown content
$markdown = file_get_contents($docPath);

// Include header
include_once '../includes/header.php';
?>

    <div class="row">
        <div class="col-md-12 mb-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo getUrl('checkups'); ?>">Checkups</a></li>
                    <?php if ($docName === 'cutlist_invalidations'): ?>
                        <li class="breadcrumb-item"><a href="<?php echo getUrl('checkups/cutlist_invalidations.php'); ?>">Cutlist Invalidations</a></li>
                    <?php endif; ?>
                    <li class="breadcrumb-item active" aria-current="page">Documentation</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card shadow">
                <div class="card-header bg-ssf-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><?php echo $page_title; ?></h5>
                    <a href="<?php echo getUrl("checkups/cutlist_invalidations.php"); ?>" class="btn btn-sm btn-outline-light">
                        <i class="bi bi-arrow-left"></i> Back to Invalidations
                    </a>
                </div>
                <div class="card-body p-4">
                    <div class="markdown-content">
                        <?php
                        // Include Parsedown library - a lightweight PHP Markdown parser
                        // If not available, display pre-formatted text
                        $parsedownPath = __DIR__ . '/../vendor/parsedown/Parsedown.php';

                        if (file_exists($parsedownPath)) {
                            require_once $parsedownPath;
                            $parsedown = new Parsedown();
                            echo $parsedown->text($markdown);
                        } else {
                            // Fallback: display as pre-formatted text
                            echo '<pre class="markdown-raw">' . htmlspecialchars($markdown) . '</pre>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php
// Add custom CSS for the markdown content
$extra_css = '
<style>
    .markdown-content h1 {
        font-size: 1.8rem;
        margin-bottom: 1.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #ddd;
        color: var(--ssf-primary);
    }
    
    .markdown-content h2 {
        font-size: 1.5rem;
        margin-top: 2rem;
        margin-bottom: 1rem;
        color: var(--ssf-primary);
    }
    
    .markdown-content h3 {
        font-size: 1.25rem;
        margin-top: 1.5rem;
        margin-bottom: 0.75rem;
        color: var(--ssf-primary);
    }
    
    .markdown-content h4 {
        font-size: 1.1rem;
        margin-top: 1.25rem;
        margin-bottom: 0.5rem;
        color: var(--ssf-secondary);
    }
    
    .markdown-content ul,
    .markdown-content ol {
        margin-bottom: 1rem;
        padding-left: 2rem;
    }
    
    .markdown-content p {
        margin-bottom: 1rem;
        line-height: 1.6;
    }
    
    .markdown-content code {
        background-color: #f8f9fa;
        padding: 0.2rem 0.4rem;
        border-radius: 3px;
        font-size: 0.85rem;
        color: #d63384;
    }
    
    .markdown-content pre {
        background-color: #f8f9fa;
        padding: 1rem;
        border-radius: 5px;
        border: 1px solid #dee2e6;
        margin-bottom: 1rem;
        overflow-x: auto;
        font-size: 0.85rem;
    }
    
    .markdown-content blockquote {
        border-left: 4px solid var(--ssf-accent);
        padding-left: 1rem;
        color: #6c757d;
        margin-bottom: 1rem;
    }
    
    .markdown-content table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 1rem;
    }
    
    .markdown-content table th,
    .markdown-content table td {
        border: 1px solid #dee2e6;
        padding: 0.5rem;
    }
    
    .markdown-content table th {
        background-color: #f8f9fa;
    }
    
    .markdown-content hr {
        margin: 2rem 0;
        border: 0;
        border-top: 1px solid #dee2e6;
    }
    
    .markdown-raw {
        white-space: pre-wrap;
        font-family: monospace;
        line-height: 1.5;
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 5px;
        padding: 1rem;
    }
</style>
';

// Include footer
include_once '../includes/footer.php';
?>