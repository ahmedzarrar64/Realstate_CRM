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

// Get owner data
$owner = getOwnerById($id);

if (!$owner) {
    header('Location: index.php');
    exit();
}

// Get owner's properties
$sql = "SELECT * FROM properties WHERE owner_id = $id ORDER BY created_at DESC";
$result = executeQuery($sql);
$properties = [];
while ($row = $result->fetch_assoc()) {
    $properties[] = $row;
}

// Get contact logs for this owner
$contact_logs = getContactLogsByOwner($id);

// Get tasks related to this owner
$sql = "SELECT * FROM tasks WHERE owner_id = $id ORDER BY due_date ASC";
$result = executeQuery($sql);
$tasks = [];
while ($row = $result->fetch_assoc()) {
    $tasks[] = $row;
}
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h2><i class="fas fa-user"></i> <?php echo htmlspecialchars($owner['name']); ?></h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Property Owners</a></li>
                <li class="breadcrumb-item active">View Owner</li>
            </ol>
        </nav>
    </div>
    <div class="col-md-4 text-end">
        <a href="edit.php?id=<?php echo $id; ?>" class="btn btn-primary">
            <i class="fas fa-edit"></i> Edit Owner
        </a>
        <a href="../contacts/add.php?owner_id=<?php echo $id; ?>" class="btn btn-info text-white">
            <i class="fas fa-phone-alt"></i> Log Contact
        </a>
    </div>
</div>

<div class="row">
    <!-- Owner Details -->
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-info-circle"></i> Owner Information
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <th width="40%">Name:</th>
                        <td><?php echo htmlspecialchars($owner['name']); ?></td>
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
                        <th>Address:</th>
                        <td><?php echo htmlspecialchars($owner['address']); ?></td>
                    </tr>
                    <tr>
                        <th>Preferred Contact:</th>
                        <td><?php echo htmlspecialchars($owner['preferred_contact']); ?></td>
                    </tr>
                    <tr>
                        <th>Notes:</th>
                        <td><?php echo nl2br(htmlspecialchars($owner['notes'])); ?></td>
                    </tr>
                    <tr>
                        <th>Added On:</th>
                        <td><?php echo formatDate($owner['created_at']); ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Tasks -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-tasks"></i> Follow-up Tasks</span>
                <a href="../tasks/add.php?owner_id=<?php echo $id; ?>" class="btn btn-sm btn-primary">
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
                        No tasks found for this owner.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <!-- Properties -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-building"></i> Properties</span>
                <a href="../properties/add.php?owner_id=<?php echo $id; ?>" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus"></i> Add Property
                </a>
            </div>
            <div class="card-body">
                <?php if (count($properties) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Address</th>
                                    <th>Type</th>
                                    <th>Area</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($properties as $property): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($property['address']); ?></td>
                                        <td><?php echo htmlspecialchars($property['property_type']); ?></td>
                                        <td><?php echo htmlspecialchars($property['area']); ?> sqm</td>
                                        <td>$<?php echo number_format($property['price'], 2); ?></td>
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
                                        <td>
                                            <a href="../properties/view.php?id=<?php echo $property['id']; ?>" class="btn btn-sm btn-info text-white">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="../properties/edit.php?id=<?php echo $property['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mb-0">
                        No properties found for this owner.
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Contact History -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-history"></i> Contact History</span>
                <a href="../contacts/add.php?owner_id=<?php echo $id; ?>" class="btn btn-sm btn-primary">
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
                                        <?php if ($log['property_address']): ?>
                                            <small class="text-muted">
                                                <i class="fas fa-building"></i> Regarding property: <?php echo htmlspecialchars($log['property_address']); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mb-0">
                        No contact history found for this owner.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once $base_path . 'includes/footer.php'; ?>