</main>

<!-- Footer -->
<?php if (!isset($hideFooter) || !$hideFooter): ?>
    <footer class="footer mt-5 py-3 bg-light">
        <div class="container text-center text-muted">
            <small>
                Steel Projects Monitor &copy; <?php echo date('Y'); ?>
                | Last updated: <span id="footerLastUpdate"><?php echo date('Y-m-d H:i:s'); ?></span>
            </small>
        </div>
    </footer>
<?php endif; ?>

<!-- External JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Common JavaScript Functions -->
<script>
    // Global utility functions
    function showHelp() {
        alert('Help documentation coming soon. Check /sp/docs/activity-status.md for details.');
    }

    // Format date/time consistently
    function formatDateTime(dateString) {
        const date = new Date(dateString);
        return date.toLocaleString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    // Handle AJAX errors consistently
    function handleAjaxError(error, defaultMessage = 'An error occurred') {
        console.error('AJAX Error:', error);
        let message = defaultMessage;

        if (error.responseJSON && error.responseJSON.error) {
            message = error.responseJSON.error;
        } else if (error.responseText) {
            try {
                const response = JSON.parse(error.responseText);
                message = response.error || message;
            } catch (e) {
                message = error.responseText;
            }
        }

        return message;
    }

    // Show loading overlay
    function showLoading() {
        if (!document.getElementById('loadingOverlay')) {
            const overlay = document.createElement('div');
            overlay.id = 'loadingOverlay';
            overlay.className = 'loading-overlay';
            overlay.innerHTML = '<div class="loading-spinner" style="width: 3rem; height: 3rem;"></div>';
            document.body.appendChild(overlay);
        }
        document.getElementById('loadingOverlay').style.display = 'flex';
    }

    // Hide loading overlay
    function hideLoading() {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.style.display = 'none';
        }
    }
</script>

<!-- Page-specific JavaScript -->
<?php if (isset($pageScripts) && is_array($pageScripts)): ?>
    <?php foreach ($pageScripts as $script): ?>
        <script src="js/<?php echo htmlspecialchars($script); ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Custom Page Scripts -->
<?php if (isset($customScripts)): ?>
    <?php echo $customScripts; ?>
<?php endif; ?>
</body>
</html>