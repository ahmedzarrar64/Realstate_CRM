<?php
// Test script to verify header.php is loading correctly
require_once 'includes/header.php';
?>

<div class="container mt-4">
    <div class="alert alert-success">
        <h4>Header Test Successful</h4>
        <p>The header.php file has been loaded successfully without errors.</p>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>