<?php
// Set base path for includes
$base_path = '../../';
require_once $base_path . 'includes/config.php';
require_once $base_path . 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ' . $base_path . 'login.php');
    exit();
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Check if notification ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$notification_id = (int)$_GET['id'];

// Delete notification
if (deleteNotification($notification_id, $user_id)) {
    // Redirect back with success message
    header('Location: index.php?success=2');
} else {
    // Redirect back with error message
    header('Location: index.php?error=2');
}