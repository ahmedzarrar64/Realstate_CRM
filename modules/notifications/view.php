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

// Check if notification ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$notification_id = (int)$_GET['id'];

// Get notification details
$sql = "SELECT * FROM notifications WHERE id = $notification_id AND user_id = $user_id";
$result = executeQuery($sql);

if ($result->num_rows === 0) {
    // Notification not found or doesn't belong to user
    header('Location: index.php');
    exit();
}

$notification = $result->fetch_assoc();

// Mark notification as read if it's not already read
if ($notification['is_read'] == 0) {
    markNotificationAsRead($notification_id, $user_id);
    $notification['is_read'] = 1; // Update local variable
}

// Get related item details if available
$related_item = null;
if ($notification['related_id'] && $notification['related_type']) {
    $related_type = strtolower($notification['related_type']);
    $related_id = (int)$notification['related_id'];
    
    switch ($related_type) {
        case 'task':
            $sql = "SELECT t.*, o.name as owner_name, p.address as property_address 
                    FROM tasks t 
                    LEFT JOIN owners o ON t.owner_id = o.id 
                    LEFT JOIN properties p ON t.property_id = p.id 
                    WHERE t.id = $related_id";
            $result = executeQuery($sql);
            if ($result->num_rows > 0) {
                $related_item = $result->fetch_assoc();
                $related_item['type'] = 'Task';
                $related_item['url'] = $base_path . "modules/tasks/view.php?id=$related_id";
            }
            break;
            
        case 'property':
            $sql = "SELECT p.*, o.name as owner_name 
                    FROM properties p 
                    JOIN owners o ON p.owner_id = o.id 
                    WHERE p.id = $related_id";
            $result = executeQuery($sql);
            if ($result->num_rows > 0) {
                $related_item = $result->fetch_assoc();
                $related_item['type'] = 'Property';
                $related_item['url'] = $base_path . "modules/properties/view.php?id=$related_id";
            }
            break;
            
        case 'owner':
            $sql = "SELECT * FROM owners WHERE id = $related_id";
            $result = executeQuery($sql);
            if ($result->num_rows > 0) {
                $related_item = $result->fetch_assoc();
                $related_item['type'] = 'Owner';
                $related_item['url'] = $base_path . "modules/owners/view.php?id=$related_id";
            }
            break;
            
        case 'client':
            $sql = "SELECT * FROM clients WHERE id = $related_id";
            $result = executeQuery($sql);
            if ($result->num_rows > 0) {
                $related_item = $result->fetch_assoc();
                $related_item['type'] = 'Client';
                $related_item['url'] = $base_path . "modules/clients/view.php?id=$related_id";
            }
            break;
            
        case 'payment':
        case 'sale':
            $sql = "SELECT ps.*, p.address as property_address, c.name as client_name 
                    FROM property_sales ps 
                    JOIN properties p ON ps.property_id = p.id 
                    JOIN clients c ON ps.client_id = c.id 
                    WHERE ps.id = $related_id";
            $result = executeQuery($sql);
            if ($result->num_rows > 0) {
                $related_item = $result->fetch_assoc();
                $related_item['type'] = 'Property Sale';
                $related_item['url'] = $base_path . "modules/property_sales/view.php?id=$related_id";
            }
            break;
    }
}
?>

<div class="row mb-4">
    <div class="col-md-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $base_path; ?>index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="index.php">Notifications</a></li>
                <li class="breadcrumb-item active" aria-current="page">View Notification</li>
            </ol>
        </nav>
        <div class="d-flex justify-content-between align-items-center">
            <h2><i class="fas fa-bell"></i> Notification Details</h2>
            <div>
                <a href="index.php" class="btn btn-sm btn-outline-secondary me-2">
                    <i class="fas fa-arrow-left"></i> Back to Notifications
                </a>
                <a href="delete.php?id=<?php echo $notification_id; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this notification?')">
                    <i class="fas fa-trash"></i> Delete
                </a>
            </div>
        </div>
        <hr>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <?php 
                $icon = 'info-circle';
                $badge_class = 'primary';
                
                switch ($notification['type']) {
                    case 'Task':
                        $icon = 'tasks';
                        $badge_class = 'warning';
                        break;
                    case 'Payment':
                        $icon = 'money-bill-wave';
                        $badge_class = 'success';
                        break;
                    case 'System':
                        $icon = 'cog';
                        $badge_class = 'secondary';
                        break;
                }
                ?>
                <h5 class="mb-0">
                    <i class="fas fa-<?php echo $icon; ?> me-2"></i>
                    <?php echo htmlspecialchars($notification['title']); ?>
                    <span class="badge bg-<?php echo $badge_class; ?> ms-2"><?php echo $notification['type']; ?></span>
                </h5>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <h6 class="text-muted mb-2">Message:</h6>
                    <p class="card-text"><?php echo nl2br(htmlspecialchars($notification['message'])); ?></p>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-muted mb-2">Created:</h6>
                        <p><i class="fas fa-clock me-1"></i> <?php echo formatDateTime($notification['created_at']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-2">Status:</h6>
                        <p>
                            <?php if ($notification['is_read']): ?>
                                <span class="badge bg-success"><i class="fas fa-check me-1"></i> Read</span>
                            <?php else: ?>
                                <span class="badge bg-danger"><i class="fas fa-times me-1"></i> Unread</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($related_item): ?>
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-link me-2"></i> Related <?php echo $related_item['type']; ?>
                </h5>
            </div>
            <div class="card-body">
                <?php switch ($related_item['type']): ?>
                    <?php case 'Task': ?>
                        <h6><?php echo htmlspecialchars($related_item['task_description']); ?></h6>
                        <p>
                            <strong>Due Date:</strong> <?php echo formatDate($related_item['due_date']); ?><br>
                            <strong>Status:</strong> 
                            <?php if ($related_item['status'] == 'Pending'): ?>
                                <span class="badge bg-warning">Pending</span>
                            <?php else: ?>
                                <span class="badge bg-success">Done</span>
                            <?php endif; ?><br>
                            <?php if ($related_item['owner_name']): ?>
                                <strong>Owner:</strong> <?php echo htmlspecialchars($related_item['owner_name']); ?><br>
                            <?php endif; ?>
                            <?php if ($related_item['property_address']): ?>
                                <strong>Property:</strong> <?php echo htmlspecialchars($related_item['property_address']); ?>
                            <?php endif; ?>
                        </p>
                    <?php break; ?>
                    
                    <?php case 'Property': ?>
                        <h6><?php echo htmlspecialchars($related_item['address']); ?></h6>
                        <p>
                            <strong>Type:</strong> <?php echo htmlspecialchars($related_item['property_type']); ?><br>
                            <strong>Price:</strong> $<?php echo number_format($related_item['price'], 2); ?><br>
                            <strong>Status:</strong> 
                            <?php 
                            $status_class = 'primary';
                            switch ($related_item['status']) {
                                case 'Available':
                                    $status_class = 'success';
                                    break;
                                case 'Under Negotiation':
                                    $status_class = 'warning';
                                    break;
                                case 'Sold':
                                    $status_class = 'danger';
                                    break;
                            }
                            ?>
                            <span class="badge bg-<?php echo $status_class; ?>"><?php echo $related_item['status']; ?></span><br>
                            <strong>Owner:</strong> <?php echo htmlspecialchars($related_item['owner_name']); ?>
                        </p>
                    <?php break; ?>
                    
                    <?php case 'Owner': ?>
                        <h6><?php echo htmlspecialchars($related_item['name']); ?></h6>
                        <p>
                            <strong>Phone:</strong> <?php echo htmlspecialchars($related_item['phone']); ?><br>
                            <strong>Email:</strong> <?php echo htmlspecialchars($related_item['email']); ?><br>
                            <strong>Address:</strong> <?php echo htmlspecialchars($related_item['address']); ?>
                        </p>
                    <?php break; ?>
                    
                    <?php case 'Client': ?>
                        <h6><?php echo htmlspecialchars($related_item['name']); ?></h6>
                        <p>
                            <strong>Type:</strong> <?php echo htmlspecialchars($related_item['client_type']); ?><br>
                            <strong>Phone:</strong> <?php echo htmlspecialchars($related_item['phone']); ?><br>
                            <strong>Email:</strong> <?php echo htmlspecialchars($related_item['email']); ?><br>
                            <strong>Address:</strong> <?php echo htmlspecialchars($related_item['address']); ?>
                        </p>
                    <?php break; ?>
                    
                    <?php case 'Property Sale': ?>
                        <h6>Sale: <?php echo htmlspecialchars($related_item['property_address']); ?></h6>
                        <p>
                            <strong>Client:</strong> <?php echo htmlspecialchars($related_item['client_name']); ?><br>
                            <strong>Sale Date:</strong> <?php echo formatDate($related_item['sale_date']); ?><br>
                            <strong>Amount:</strong> $<?php echo number_format($related_item['total_amount'], 2); ?><br>
                            <strong>Status:</strong> 
                            <?php 
                            $status_class = 'primary';
                            switch ($related_item['sale_status']) {
                                case 'In Progress':
                                    $status_class = 'warning';
                                    break;
                                case 'Completed':
                                    $status_class = 'success';
                                    break;
                                case 'Cancelled':
                                    $status_class = 'danger';
                                    break;
                            }
                            ?>
                            <span class="badge bg-<?php echo $status_class; ?>"><?php echo $related_item['sale_status']; ?></span><br>
                            <strong>Payment Status:</strong> 
                            <?php 
                            $payment_class = 'primary';
                            switch ($related_item['payment_status']) {
                                case 'Token Received':
                                case 'Advance Received':
                                    $payment_class = 'warning';
                                    break;
                                case 'Fully Paid':
                                    $payment_class = 'success';
                                    break;
                                case 'Pending':
                                    $payment_class = 'danger';
                                    break;
                            }
                            ?>
                            <span class="badge bg-<?php echo $payment_class; ?>"><?php echo $related_item['payment_status']; ?></span>
                        </p>
                    <?php break; ?>
                    
                    <?php default: ?>
                        <p>Related item information not available.</p>
                <?php endswitch; ?>
                
                <a href="<?php echo $related_item['url']; ?>" class="btn btn-primary">
                    <i class="fas fa-external-link-alt me-1"></i> View <?php echo $related_item['type']; ?>
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-cogs me-2"></i> Actions</h5>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <a href="index.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-list me-2"></i> All Notifications
                    </a>
                    <a href="mark_all_read.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-check-double me-2"></i> Mark All as Read
                    </a>
                    <a href="index.php?delete_read=1" class="list-group-item list-group-item-action" onclick="return confirm('Are you sure you want to delete all read notifications?')">
                        <i class="fas fa-trash me-2"></i> Delete Read Notifications
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once $base_path . 'includes/footer.php'; ?>