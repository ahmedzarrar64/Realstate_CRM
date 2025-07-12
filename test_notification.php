<?php
// Test file to verify header.php is working correctly
$base_path = '';
require_once 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="alert alert-success">
                <h4>Header Test Successful</h4>
                <p>If you can see this message, the header.php file is loading correctly without errors.</p>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>