<?php
// Start output buffering to prevent headers already sent error
ob_start();

session_start();
require_once __DIR__ . '/config.php';
// We'll handle schema creation in config.php

// Simple authentication check (for optional login system)
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

// Session expiration settings
$session_timeout = 1800; // 30 minutes
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $session_timeout)) {
    // Last request was more than 30 minutes ago
    session_unset();     // Unset $_SESSION variable for the run-time
    session_destroy();   // Destroy session data in storage
    header('Location: login.php?expired=1');
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time(); // Update last activity time

// Redirect if not logged in
if (!isLoggedIn() && basename($_SERVER['PHP_SELF']) != 'login.php') {
    $login_path = isset($base_path) ? $base_path . 'login.php' : '/realState/login.php';
    header('Location: ' . $login_path . (isset($_GET['expired']) ? '?expired=1' : ''));
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Real Estate CRM</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo isset($base_path) ? $base_path : ''; ?>css/style.css">
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo isset($base_path) ? $base_path : ''; ?>index.php">Real Estate CRM</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo isset($base_path) ? $base_path : ''; ?>index.php"><i class="fas fa-home"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo isset($base_path) ? $base_path : ''; ?>modules/owners/index.php"><i class="fas fa-users"></i> Property Owners</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo isset($base_path) ? $base_path : ''; ?>modules/clients/index.php"><i class="fas fa-user-tie"></i> Clients</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo isset($base_path) ? $base_path : ''; ?>modules/properties/index.php"><i class="fas fa-building"></i> Properties</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo isset($base_path) ? $base_path : ''; ?>modules/contacts/index.php"><i class="fas fa-address-book"></i> Contact Logs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo isset($base_path) ? $base_path : ''; ?>modules/tasks/index.php"><i class="fas fa-tasks"></i> Tasks</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                    <!-- Notifications Dropdown -->
                    <li class="nav-item dropdown me-2">
                        <?php 
                        // Check if notifications table exists before calling functions
                        $table_exists = $conn->query("SHOW TABLES LIKE 'notifications'")->num_rows > 0;
                        
                        // Check if the functions exist before calling them
                        $unread_count = 0;
                        $notifications = [];
                        
                        if ($table_exists && function_exists('getUnreadNotificationCount') && function_exists('getUserNotifications')) {
                            $unread_count = getUnreadNotificationCount($_SESSION['user_id']);
                            $notifications = getUserNotifications($_SESSION['user_id'], 5);
                        }
                        ?>
                        <a class="nav-link" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bell"></i>
                            <?php if ($unread_count > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?php echo $unread_count; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="notificationsDropdown" style="width: 300px; max-height: 400px; overflow-y: auto;">
                            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                                <h6 class="m-0">Notifications</h6>
                                <?php if ($unread_count > 0): ?>
                                    <a href="<?php echo isset($base_path) ? $base_path : ''; ?>modules/notifications/mark_all_read.php" class="text-decoration-none small">Mark all as read</a>
                                <?php endif; ?>
                            </div>
                            <?php if (count($notifications) > 0): ?>
                                <?php foreach ($notifications as $notification): ?>
                                    <a class="dropdown-item py-2 <?php echo $notification['is_read'] ? '' : 'bg-light'; ?>" href="<?php echo isset($base_path) ? $base_path : ''; ?>modules/notifications/view.php?id=<?php echo $notification['id']; ?>">
                                        <div class="d-flex align-items-center">
                                            <?php 
                                            $icon = 'info-circle text-primary';
                                            switch ($notification['type']) {
                                                case 'Task':
                                                    $icon = 'tasks text-warning';
                                                    break;
                                                case 'Payment':
                                                    $icon = 'money-bill-wave text-success';
                                                    break;
                                                case 'System':
                                                    $icon = 'cog text-secondary';
                                                    break;
                                            }
                                            ?>
                                            <div class="me-3">
                                                <i class="fas fa-<?php echo $icon; ?> fa-lg"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold small"><?php echo htmlspecialchars($notification['title']); ?></div>
                                                <div class="small text-muted"><?php echo htmlspecialchars(substr($notification['message'], 0, 50)) . (strlen($notification['message']) > 50 ? '...' : ''); ?></div>
                                                <div class="small text-muted"><?php echo formatDateTime($notification['created_at'], 'M d, h:i A'); ?></div>
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item text-center small text-muted py-2" href="<?php echo isset($base_path) ? $base_path : ''; ?>modules/notifications/index.php">View all notifications</a>
                            <?php else: ?>
                                <div class="dropdown-item text-center py-3">
                                    <i class="fas fa-bell-slash text-muted"></i><br>
                                    <span class="text-muted">No notifications</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </li>
                    
                    <!-- User Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo $_SESSION['username']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?php echo isset($base_path) ? $base_path : ''; ?>profile.php"><i class="fas fa-id-card"></i> Profile</a></li>
                            <?php if ($_SESSION['role'] == 'Admin'): ?>
                                <li><a class="dropdown-item" href="<?php echo isset($base_path) ? $base_path : ''; ?>modules/system_settings/index.php"><i class="fas fa-cogs"></i> System Settings</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo isset($base_path) ? $base_path : ''; ?>logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container mt-4">