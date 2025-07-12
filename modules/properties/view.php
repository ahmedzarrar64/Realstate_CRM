<?php
// Adjust path for includes
$base_path = '../../';
require_once $base_path . 'includes/header.php';
require_once $base_path . 'includes/functions.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$id = (int)$_GET['id'];

// Get property data
$property = getPropertyById($id);

if (!$property) {
    header('Location: index.php');
    exit();
}

// Get owner details
$owner = getOwnerById($property['owner_id']);

// Get contact logs related to this property
$sql = "SELECT cl.*, o.name as owner_name 
        FROM contact_logs cl 
        JOIN owners o ON cl.owner_id = o.id 
        WHERE cl.property_id = $id 
        ORDER BY cl.contact_date DESC";
$result = executeQuery($sql);
$contact_logs = [];
while ($row = $result->fetch_assoc()) {
    $contact_logs[] = $row;
}

// Get tasks related to this property
$sql = "SELECT * FROM tasks WHERE property_id = $id ORDER BY due_date ASC";
$result = executeQuery($sql);
$tasks = [];
while ($row = $result->fetch_assoc()) {
    $tasks[] = $row;
}
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h2><i class="fas fa-building"></i> Property Details</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Properties</a></li>
                <li class="breadcrumb-item active">View Property</li>
            </ol>
        </nav>
    </div>
    <div class="col-md-4 text-end">
        <a href="edit.php?id=<?php echo $id; ?>" class="btn btn-primary">
            <i class="fas fa-edit"></i> Edit Property
        </a>
        <a href="../contacts/add.php?property_id=<?php echo $id; ?>&owner_id=<?php echo $property['owner_id']; ?>" class="btn btn-info text-white">
            <i class="fas fa-phone-alt"></i> Log Contact
        </a>
    </div>
</div>

<div class="row">
    <!-- Property Details -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-info-circle"></i> Property Information
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <th width="40%">Address:</th>
                        <td><?php echo htmlspecialchars($property['address']); ?></td>
                    </tr>
                    <tr>
                        <th>Property Type:</th>
                        <td><?php echo htmlspecialchars($property['property_type']); ?></td>
                    </tr>
                    <tr>
                        <th>Area:</th>
                        <td><?php echo htmlspecialchars($property['area']); ?> sqm</td>
                    </tr>
                    <tr>
                        <th>Price:</th>
                        <td>$<?php echo number_format($property['price'], 2); ?></td>
                    </tr>
                    <tr>
                        <th>Status:</th>
                        <td>
                            <?php 
                            $status_class = '';
                            switch ($property['status']) {
                                case 'Available':
                                    $status_class = 'bg-success';
                                    break;
                                case 'Under Negotiation':
                                    $status_class = 'bg-info';
                                    break;
                                case 'Sold':
                                    $status_class = 'bg-danger';
                                    break;
                            }
                            ?>
                            <span class="badge <?php echo $status_class; ?>">
                                <?php echo $property['status']; ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Added On:</th>
                        <td><?php echo formatDate($property['created_at']); ?></td>
                    </tr>
                    <tr>
                        <th>Last Updated:</th>
                        <td><?php echo isset($property['updated_at']) ? formatDate($property['updated_at']) : 'Not available'; ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Owner Information -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-user"></i> Owner Information
            </div>
            <div class="card-body">
                <?php if ($owner): ?>
                    <table class="table table-borderless">
                        <tr>
                            <th width="40%">Name:</th>
                            <td>
                                <a href="../owners/view.php?id=<?php echo $owner['id']; ?>">
                                    <?php echo htmlspecialchars($owner['name']); ?>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <th>Phone:</th>
                            <td>
                                <a href="tel:<?php echo htmlspecialchars($owner['phone']); ?>">
                                    <?php echo htmlspecialchars($owner['phone']); ?>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <th>Email:</th>
                            <td>
                                <a href="mailto:<?php echo htmlspecialchars($owner['email']); ?>">
                                    <?php echo htmlspecialchars($owner['email']); ?>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <th>Preferred Contact:</th>
                            <td><?php echo htmlspecialchars($owner['preferred_contact']); ?></td>
                        </tr>
                    </table>
                <?php else: ?>
                    <div class="alert alert-warning mb-0">
                        Owner information not available.
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Tasks -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-tasks"></i> Follow-up Tasks</span>
                <a href="../tasks/add.php?property_id=<?php echo $id; ?>&owner_id=<?php echo $property['owner_id']; ?>" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus"></i> Add Task
                </a>
            </div>
            <div class="card-body">
                <?php if (count($tasks) > 0): ?>
                    <ul class="list-group">
                        <?php foreach ($tasks as $task): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge <?php echo ($task['status'] === 'Pending') ? 'bg-warning' : 'bg-success'; ?> me-2">
                                        <?php echo $task['status']; ?>
                                    </span>
                                    <?php echo htmlspecialchars($task['task_description']); ?>
                                    <br>
                                    <small class="text-muted">
                                        Due: <?php echo formatDate($task['due_date']); ?>
                                    </small>
                                </div>
                                <div>
                                    <a href="../tasks/edit.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="alert alert-info mb-0">
                        No tasks found for this property.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <!-- Contact History -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-history"></i> Contact History</span>
                <a href="../contacts/add.php?property_id=<?php echo $id; ?>&owner_id=<?php echo $property['owner_id']; ?>" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus"></i> Add Contact Log
                </a>
            </div>
            <div class="card-body">
                <?php if (count($contact_logs) > 0): ?>
                    <div class="timeline">
                        <?php foreach ($contact_logs as $log): ?>
                            <div class="timeline-item">
                                <div class="card mb-3">
                                    <div class="card-header bg-light">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <?php 
                                                $icon = '';
                                                switch ($log['contact_type']) {
                                                    case 'Call':
                                                        $icon = '<i class="fas fa-phone-alt text-primary"></i>';
                                                        break;
                                                    case 'WhatsApp':
                                                        $icon = '<i class="fab fa-whatsapp text-success"></i>';
                                                        break;
                                                    case 'Email':
                                                        $icon = '<i class="fas fa-envelope text-info"></i>';
                                                        break;
                                                    case 'Visit':
                                                        $icon = '<i class="fas fa-home text-warning"></i>';
                                                        break;
                                                }
                                                echo $icon . ' <strong>' . $log['contact_type'] . '</strong>';
                                                ?>
                                            </div>
                                            <div class="timeline-date">
                                                <?php echo formatDateTime($log['contact_date']); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <p><?php echo nl2br(htmlspecialchars($log['description'])); ?></p>
                                        <small class="text-muted">
                                            <i class="fas fa-user"></i> Contact with: <?php echo htmlspecialchars($log['owner_name']); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mb-0">
                        No contact history found for this property.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once $base_path . 'includes/footer.php'; ?>