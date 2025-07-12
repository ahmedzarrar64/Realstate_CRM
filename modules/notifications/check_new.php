<?php
/**
 * Notifications - Check for new notifications (AJAX endpoint)
 * This file handles AJAX requests to check for new notifications
 */

// Include necessary files
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required'
    ]);
    exit;
}

// Get user ID from session
$userId = $_SESSION['user_id'];

// Get last check timestamp if provided
$lastCheck = isset($_GET['last_check']) ? (int)$_GET['last_check'] : 0;

// Get unread notification count
$count = getUnreadNotificationCount($userId);

// Get recent notifications (limit to 5)
$notifications = getUserNotifications($userId, 5, 'unread');

// Get new notifications since last check
$newNotifications = [];
if ($lastCheck > 0) {
    $timestamp = date('Y-m-d H:i:s', $lastCheck / 1000); // Convert JS timestamp to MySQL datetime
    
    // Query for notifications created after the last check
    $query = "SELECT * FROM notifications 
              WHERE user_id = ? AND created_at > ? 
              ORDER BY created_at DESC LIMIT 5";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('is', $userId, $timestamp);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $newNotifications[] = $row;
    }
}

// Return JSON response
echo json_encode([
    'success' => true,
    'count' => $count,
    'notifications' => $notifications,
    'new_notifications' => $newNotifications
]);