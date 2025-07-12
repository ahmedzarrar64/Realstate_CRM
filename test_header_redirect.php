<?php
// Test file to verify header redirects are working correctly
$base_path = '';
require_once 'includes/header.php';

// Try to redirect
header('Location: index.php?test=1');
exit();

// This code should not be executed
require_once 'includes/footer.php';
?>