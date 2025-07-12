<?php
// Close container div and add scripts
// Flush the output buffer
ob_end_flush();
?>
</div> <!-- End of container -->
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom JS -->
    <script src="<?php echo isset($base_path) ? $base_path : ''; ?>js/script.js"></script>
    <!-- Notifications JS -->
    <script src="<?php echo isset($base_path) ? $base_path : ''; ?>js/notifications.js"></script>
</body>
</html>