<?php
// Set base path for includes
$base_path = '../../';
require_once $base_path . 'includes/header.php';
require_once $base_path . 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ' . $base_path . 'login.php');
    exit();
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Process filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Get notifications based on filter
$sql = "SELECT * FROM notifications WHERE user_id = $user_id";

if ($filter === 'unread') {
    $sql .= " AND is_read = 0";
} elseif ($filter === 'read') {
    $sql .= " AND is_read = 1";
}

// Add sorting
$sql .= " ORDER BY created_at DESC";

// Execute query
$result = executeQuery($sql);

// Process actions
$message = '';

// Mark all as read
if (isset($_GET['mark_all_read']) && $_GET['mark_all_read'] == 1) {
    if (markAllNotificationsAsRead($user_id)) {
        $message = showSuccess('All notifications marked as read.');
    } else {
        $message = showError('Failed to mark notifications as read.');
    }
}

// Delete all read notifications
if (isset($_GET['delete_read']) && $_GET['delete_read'] == 1) {
    if (deleteAllReadNotifications($user_id)) {
        $message = showSuccess('All read notifications deleted.');
    } else {
        $message = showError('Failed to delete read notifications.');
    }
}
?>

<div class="row mb-4">
    <div class="col-md-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $base_path; ?>index.php">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Notifications</li>
            </ol>
        </nav>
        <div class="d-flex justify-content-between align-items-center">
            <h2><i class="fas fa-bell"></i> Notifications</h2>
            <div>
                <?php if ($filter !== 'unread'): ?>
                    <a href="?filter=unread" class="btn btn-sm btn-outline-primary me-2">
                        <i class="fas fa-filter"></i> Show Unread Only
                    </a>
                <?php endif; ?>
                <?php if ($filter !== 'read'): ?>
                    <a href="?filter=read" class="btn btn-sm btn-outline-primary me-2">
                        <i class="fas fa-filter"></i> Show Read Only
                    </a>
                <?php endif; ?>
                <?php if ($filter !== 'all'): ?>
                    <a href="?filter=all" class="btn btn-sm btn-outline-primary me-2">
                        <i class="fas fa-filter"></i> Show All
                    </a>
                <?php endif; ?>
                <a href="?mark_all_read=1" class="btn btn-sm btn-outline-success me-2">
                    <i class="fas fa-check-double"></i> Mark All as Read
                </a>
                <a href="?delete_read=1" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete all read notifications?')">
                    <i class="fas fa-trash"></i> Delete Read Notifications
                </a>
            </div>
        </div>
        <hr>
        
        <?php echo $message; ?>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <?php if ($result->num_rows > 0): ?>
                    <div class="list-group">
                        <?php while ($notification = $result->fetch_assoc()): ?>
                            <div class="list-group-item list-group-item-action <?php echo $notification['is_read'] ? '' : 'bg-light'; ?>">
                                <div class="d-flex w-100 justify-content-between align-items-center">
                                    <div>
                                        <?php 
                                        $icon = 'info-circle text-primary';
                                        $badge_class = 'primary';
                                        
                                        switch ($notification['type']) {
                                            case 'Task':
                                                $icon = 'tasks text-warning';
                                                $badge_class = 'warning';
                                                break;
                                            case 'Payment':
                                                $icon = 'money-bill-wave text-success';
                                                $badge_class = 'success';
                                                break;
                                            case 'System':
                                                $icon = 'cog text-secondary';
                                                $badge_class = 'secondary';
                                                break;
                                        }
                                        ?>
                                        <h5 class="mb-1">
                                            <i class="fas fa-<?php echo $icon; ?> me-2"></i>
                                            <?php echo htmlspecialchars($notification['title']); ?>
                                            <span class="badge bg-<?php echo $badge_class; ?> ms-2"><?php echo $notification['type']; ?></span>
                                            <?php if (!$notification['is_read']): ?>
                                                <span class="badge bg-danger ms-2">New</span>
                                            <?php endif; ?>
                                        </h5>
                                        <p class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i> <?php echo formatDateTime($notification['created_at']); ?>
                                        </small>
                                    </div>
                                    <div class="btn-group">
                                        <?php if (!$notification['is_read']): ?>
                                            <a href="mark_read.php?id=<?php echo $notification['id']; ?>" class="btn btn-sm btn-outline-success" data-bs-toggle="tooltip" title="Mark as Read">
                                                <i class="fas fa-check"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($notification['related_id'] && $notification['related_type']): ?>
                                            <a href="../<?php echo strtolower($notification['related_type']); ?>/view.php?id=<?php echo $notification['related_id']; ?>" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="View Related Item">
                                                <i class="fas fa-external-link-alt"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="delete.php?id=<?php echo $notification['id']; ?>" class="btn btn-sm btn-outline-danger" data-bs-toggle="tooltip" title="Delete" onclick="return confirm('Are you sure you want to delete this notification?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-bell-slash fa-4x text-muted mb-3"></i>
                        <h4 class="text-muted">No notifications found</h4>
                        <?php if ($filter !== 'all'): ?>
                            <p>
                                <a href="?filter=all" class="btn btn-outline-primary mt-3">
                                    <i class="fas fa-filter"></i> Show All Notifications
                                </a>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    // Initialize tooltips
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    });
</script>

<?php require_once $base_path . 'includes/footer.php'; ?>