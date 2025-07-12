<?php
/**
 * Notifications - Delete All Read Notifications
 * This file handles the deletion of all read notifications for the current user
 */

// Include necessary files
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../../login.php');
    exit;
}

// Get user ID from session
$userId = $_SESSION['user_id'];

// Check if this is an AJAX request
$isAjax = isset($_GET['ajax']) && $_GET['ajax'] == 1;

// Delete all read notifications for this user
$result = deleteAllReadNotifications($userId);

// Handle response based on request type
if ($isAjax) {
    // Set content type to JSON
    header('Content-Type: application/json');
    
    // Get updated unread count
    $count = getUnreadNotificationCount($userId);
    
    // Return JSON response
    echo json_encode([
        'success' => $result,
        'count' => $count,
        'message' => $result ? 'All read notifications deleted successfully' : 'Failed to delete notifications'
    ]);
    exit;
} else {
    // Redirect back to notifications page with status
    $status = $result ? 'success' : 'error';
    $message = $result ? 'All read notifications deleted successfully' : 'Failed to delete notifications';
    
    header('Location: index.php?status=' . $status . '&message=' . urlencode($message));
    exit;
}