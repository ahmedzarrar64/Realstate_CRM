<?php
/**
 * Notifications Module - Setup
 * This file creates the necessary database tables for the notifications module
 */

// Include necessary files
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

// Create notifications table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('Task','Payment','System') NOT NULL DEFAULT 'System',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `is_read` (`is_read`),
  KEY `entity_type_entity_id` (`entity_type`, `entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

$success = false;
$message = '';

if ($conn->query($sql) === TRUE) {
    // Create sample notifications for the admin user
    $adminId = $_SESSION['user_id'];
    
    // Welcome notification
    $welcomeSql = "INSERT INTO `notifications` (`user_id`, `title`, `message`, `type`) 
                  VALUES (?, 'Welcome to Notifications', 'The notification system has been successfully set up. You will now receive notifications about important events in the system.', 'System')";
    $stmt = $conn->prepare($welcomeSql);
    $stmt->bind_param('i', $adminId);
    $stmt->execute();
    
    $success = true;
    $message = 'Notifications table created successfully and sample notification added.';
} else {
    $message = 'Error creating notifications table: ' . $conn->error;
}

// HTML output
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications Setup - Real Estate CRM</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4><i class="fas fa-bell me-2"></i>Notifications Module Setup</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
                            </div>
                            <p>The notifications module has been successfully set up. You can now:</p>
                            <ul>
                                <li>Receive notifications about important system events</li>
                                <li>Get notified about new tasks and assignments</li>
                                <li>Track payment notifications</li>
                                <li>Manage all your notifications in one place</li>
                            </ul>
                        <?php else: ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i><?php echo $message; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-4">
                            <a href="../../index.php" class="btn btn-primary">
                                <i class="fas fa-home me-2"></i>Go to Dashboard
                            </a>
                            <a href="index.php" class="btn btn-secondary ms-2">
                                <i class="fas fa-bell me-2"></i>View Notifications
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>