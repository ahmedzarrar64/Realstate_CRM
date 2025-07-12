<?php
// Common utility functions for the CRM

/**
 * Display success message
 * @param string $message The success message to display
 * @return string HTML for success alert
 */
function showSuccess($message) {
    return '<div class="alert alert-success alert-dismissible fade show" role="alert">' . 
           $message . 
           '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' . 
           '</div>';
}

/**
 * Display error message
 * @param string $message The error message to display
 * @return string HTML for error alert
 */
function showError($message) {
    return '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . 
           $message . 
           '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' . 
           '</div>';
}

/**
 * Format date to a readable format
 * @param string $date The date to format
 * @param string $format The format to use (default: 'M d, Y')
 * @return string Formatted date
 */
function formatDate($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

/**
 * Format date and time to a readable format
 * @param string $datetime The datetime to format
 * @param string $format The format to use (default: 'M d, Y h:i A')
 * @return string Formatted datetime
 */
function formatDateTime($datetime, $format = 'M d, Y h:i A') {
    return date($format, strtotime($datetime));
}

/**
 * Get all owners from database
 * @return array Array of owners
 */
function getAllOwners() {
    $sql = "SELECT * FROM owners ORDER BY name ASC";
    $result = executeQuery($sql);
    $owners = [];
    while ($row = $result->fetch_assoc()) {
        $owners[] = $row;
    }
    return $owners;
}

/**
 * Get owner by ID
 * @param int $id The owner ID
 * @return array|null Owner data or null if not found
 */
function getOwnerById($id) {
    $id = (int)$id; // Ensure integer
    $sql = "SELECT * FROM owners WHERE id = $id";
    $result = executeQuery($sql);
    return $result->fetch_assoc();
}

/**
 * Get all properties from database
 * @param string $filter Optional filter by status
 * @return array Array of properties
 */
function getAllProperties($filter = '') {
    $sql = "SELECT p.*, o.name as owner_name 
            FROM properties p 
            JOIN owners o ON p.owner_id = o.id";
    
    if (!empty($filter)) {
        $filter = escapeString($filter);
        $sql .= " WHERE p.status = '$filter'";
    }
    
    $sql .= " ORDER BY p.created_at DESC";
    
    $result = executeQuery($sql);
    $properties = [];
    while ($row = $result->fetch_assoc()) {
        $properties[] = $row;
    }
    return $properties;
}

/**
 * Get property by ID
 * @param int $id The property ID
 * @return array|null Property data or null if not found
 */
function getPropertyById($id) {
    $id = (int)$id; // Ensure integer
    $sql = "SELECT p.*, o.name as owner_name 
            FROM properties p 
            JOIN owners o ON p.owner_id = o.id 
            WHERE p.id = $id";
    $result = executeQuery($sql);
    return $result->fetch_assoc();
}

/**
 * Get contact logs for an owner
 * @param int $ownerId The owner ID
 * @return array Array of contact logs
 */
function getContactLogsByOwner($ownerId) {
    $ownerId = (int)$ownerId; // Ensure integer
    $sql = "SELECT cl.*, p.address as property_address 
            FROM contact_logs cl 
            LEFT JOIN properties p ON cl.property_id = p.id 
            WHERE cl.owner_id = $ownerId 
            ORDER BY cl.contact_date DESC";
    $result = executeQuery($sql);
    $logs = [];
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
    return $logs;
}

/**
 * Get all tasks
 * @param string $filter Optional filter by status
 * @return array Array of tasks
 */
function getAllTasks($filter = '') {
    $sql = "SELECT t.*, o.name as owner_name, p.address as property_address 
            FROM tasks t 
            LEFT JOIN owners o ON t.owner_id = o.id 
            LEFT JOIN properties p ON t.property_id = p.id";
    
    if (!empty($filter)) {
        $filter = escapeString($filter);
        $sql .= " WHERE t.status = '$filter'";
    }
    
    $sql .= " ORDER BY t.due_date ASC";
    
    $result = executeQuery($sql);
    $tasks = [];
    while ($row = $result->fetch_assoc()) {
        $tasks[] = $row;
    }
    return $tasks;
}

/**
 * Get tasks due today
 * @return array Array of tasks due today
 */
function getTodaysTasks() {
    $today = date('Y-m-d');
    $sql = "SELECT t.*, o.name as owner_name, p.address as property_address 
            FROM tasks t 
            LEFT JOIN owners o ON t.owner_id = o.id 
            LEFT JOIN properties p ON t.property_id = p.id 
            WHERE t.due_date = '$today' AND t.status = 'Pending' 
            ORDER BY t.created_at ASC";
    $result = executeQuery($sql);
    $tasks = [];
    while ($row = $result->fetch_assoc()) {
        $tasks[] = $row;
    }
    return $tasks;
}

/**
 * Get recent contact logs
 * @param int $limit Number of logs to retrieve (default: 10)
 * @return array Array of recent contact logs
 */
function getRecentContactLogs($limit = 10) {
    $limit = (int)$limit; // Ensure integer
    $sql = "SELECT cl.*, o.name as owner_name, p.address as property_address 
            FROM contact_logs cl 
            JOIN owners o ON cl.owner_id = o.id 
            LEFT JOIN properties p ON cl.property_id = p.id 
            ORDER BY cl.contact_date DESC 
            LIMIT $limit";
    $result = executeQuery($sql);
    $logs = [];
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
    return $logs;
}

/**
 * Get dashboard statistics
 * @return array Array of statistics
 */
function getDashboardStats() {
    // Total owners
    $sql_owners = "SELECT COUNT(*) as total FROM owners";
    $result_owners = executeQuery($sql_owners);
    $total_owners = $result_owners->fetch_assoc()['total'];
    
    // Total clients
    $sql_clients = "SELECT COUNT(*) as total FROM clients";
    $result_clients = executeQuery($sql_clients);
    $total_clients = $result_clients->fetch_assoc()['total'];
    
    // Active listings
    $sql_properties = "SELECT COUNT(*) as total FROM properties WHERE status != 'Sold'";
    $result_properties = executeQuery($sql_properties);
    $active_listings = $result_properties->fetch_assoc()['total'];
    
    // Today's follow-ups
    $today = date('Y-m-d');
    $sql_tasks = "SELECT COUNT(*) as total FROM tasks WHERE due_date = '$today' AND status = 'Pending'";
    $result_tasks = executeQuery($sql_tasks);
    $todays_tasks = $result_tasks->fetch_assoc()['total'];
    
    return [
        'total_owners' => $total_owners,
        'total_clients' => $total_clients,
        'active_listings' => $active_listings,
        'todays_tasks' => $todays_tasks
    ];
}

/**
 * Get user notifications
 * @param int $user_id User ID
 * @param int $limit Number of notifications to retrieve (default: 10)
 * @param bool $unread_only Whether to retrieve only unread notifications (default: false)
 * @return array Array of notifications
 */
function getUserNotifications($user_id, $limit = 10, $unread_only = false) {
    $user_id = (int)$user_id; // Ensure integer
    $limit = (int)$limit; // Ensure integer
    
    $sql = "SELECT * FROM notifications WHERE user_id = $user_id";
    
    if ($unread_only) {
        $sql .= " AND is_read = 0";
    }
    
    $sql .= " ORDER BY created_at DESC LIMIT $limit";
    
    $result = executeQuery($sql);
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    return $notifications;
}

/**
 * Get unread notification count for a user
 * @param int $user_id User ID
 * @return int Number of unread notifications
 */
function getUnreadNotificationCount($user_id) {
    $user_id = (int)$user_id; // Ensure integer
    $sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = $user_id AND is_read = 0";
    $result = executeQuery($sql);
    return $result->fetch_assoc()['count'];
}

/**
 * Create a new notification
 * @param int $user_id User ID
 * @param string $title Notification title
 * @param string $message Notification message
 * @param string $type Notification type (Task, Payment, System, Other)
 * @param int|null $related_id Related entity ID (optional)
 * @param string|null $related_type Related entity type (optional)
 * @return bool True if successful, false otherwise
 */
function createNotification($user_id, $title, $message, $type = 'System', $related_id = null, $related_type = null) {
    $user_id = (int)$user_id; // Ensure integer
    $title = escapeString($title);
    $message = escapeString($message);
    $type = escapeString($type);
    
    $related_id_sql = $related_id ? (int)$related_id : "NULL";
    $related_type_sql = $related_type ? "'" . escapeString($related_type) . "'" : "NULL";
    
    $sql = "INSERT INTO notifications (user_id, title, message, type, related_id, related_type) 
            VALUES ($user_id, '$title', '$message', '$type', $related_id_sql, $related_type_sql)";
    
    return executeQuery($sql) ? true : false;
}

/**
 * Mark notification as read
 * @param int $notification_id Notification ID
 * @param int $user_id User ID (for security check)
 * @return bool True if successful, false otherwise
 */
function markNotificationAsRead($notification_id, $user_id) {
    $notification_id = (int)$notification_id; // Ensure integer
    $user_id = (int)$user_id; // Ensure integer
    
    $sql = "UPDATE notifications SET is_read = 1 
            WHERE id = $notification_id AND user_id = $user_id";
    
    return executeQuery($sql) ? true : false;
}

/**
 * Mark all notifications as read for a user
 * @param int $user_id User ID
 * @return bool True if successful, false otherwise
 */
function markAllNotificationsAsRead($user_id) {
    $user_id = (int)$user_id; // Ensure integer
    
    $sql = "UPDATE notifications SET is_read = 1 
            WHERE user_id = $user_id AND is_read = 0";
    
    return executeQuery($sql) ? true : false;
}

/**
 * Delete a notification
 * @param int $notification_id Notification ID
 * @param int $user_id User ID (for security check)
 * @return bool True if successful, false otherwise
 */
function deleteNotification($notification_id, $user_id) {
    $notification_id = (int)$notification_id; // Ensure integer
    $user_id = (int)$user_id; // Ensure integer
    
    $sql = "DELETE FROM notifications 
            WHERE id = $notification_id AND user_id = $user_id";
    
    return executeQuery($sql) ? true : false;
}

/**
 * Delete all read notifications for a user
 * @param int $user_id User ID
 * @return bool True if successful, false otherwise
 */
function deleteAllReadNotifications($user_id) {
    $user_id = (int)$user_id; // Ensure integer
    
    $sql = "DELETE FROM notifications 
            WHERE user_id = $user_id AND is_read = 1";
    
    return executeQuery($sql) ? true : false;
}
?>