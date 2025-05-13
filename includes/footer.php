<?php
// includes/footer.php

// Close any open database connections or perform cleanup if needed
// ...
?>
</div><!-- End of Main Container -->

<footer class="footer mt-auto py-3 bg-ssf-primary text-white">
    <div class="container text-center">
        <span>&copy; <?php echo date('Y'); ?> SSF Production Management System</span>
        <span class="ms-2">v<?php echo isset($version) ? $version : '1.0.0'; ?></span>
    </div>
</footer>

<!-- Bootstrap Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Common JavaScript -->
<script src="<?php echo getAssetUrl('js/common.js'); ?>"></script>

<!-- Navigation JavaScript -->
<script src="<?php echo getAssetUrl('js/navigation.js'); ?>"></script>

<?php if (isset($extra_js)): ?>
    <?php echo $extra_js; ?>
<?php endif; ?>
</body>
</html>