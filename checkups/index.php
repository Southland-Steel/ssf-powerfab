<?php
// checkups/index.php

// Include utility functions first to ensure getUrl is available
require_once '../includes/functions/utility_functions.php';

// Set page-specific variables
$page_title = 'Production Checkups';
$show_workweeks = false;

// Include header
include_once '../includes/header.php';
?>

    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow">
                <div class="card-header bg-ssf-primary text-white">
                    <h5 class="card-title mb-0">Production Checkups</h5>
                </div>
                <div class="card-body">
                    <p class="lead">Welcome to the Production Checkups section. This area provides tools to identify and resolve issues in the production workflow.</p>

                    <div class="list-group mt-4">
                        <a href="<?php echo getUrl('checkups/cutlist_invalidations.php'); ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1">Cutlist Invalidations</h5>
                                <p class="mb-1">View all cutlist items that have been invalidated but not cut or completed.</p>
                            </div>
                            <span class="badge bg-ssf-primary rounded-pill" id="invalidation-count">
                            <div class="spinner-border spinner-border-sm" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </span>
                        </a>
                        <!-- Additional checkup tools can be added here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Fetch the count of invalidated items for the badge
            fetch('<?php echo getUrl("checkups/ajax/get_invalidations.php?count_only=1"); ?>')
                .then(response => response.json())
                .then(data => {
                    const countBadge = document.getElementById('invalidation-count');
                    if (data.count !== undefined) {
                        countBadge.textContent = data.count;

                        // Apply warning styling if there are invalidations
                        if (data.count > 0) {
                            countBadge.classList.remove('bg-ssf-primary');
                            countBadge.classList.add('bg-warning', 'text-dark');
                        }
                    } else {
                        countBadge.textContent = 'Error';
                    }
                })
                .catch(error => {
                    console.error('Error fetching invalidation count:', error);
                    document.getElementById('invalidation-count').textContent = 'Error';
                });
        });
    </script>

<?php
// Include footer
include_once '../includes/footer.php';
?>