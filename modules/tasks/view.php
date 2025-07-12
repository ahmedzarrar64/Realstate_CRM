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

// Get task data with owner and property information
$sql = "SELECT t.*, o.name as owner_name, o.phone as owner_phone, o.email as owner_email, 
               p.address as property_address, p.property_type, p.status as property_status 
        FROM tasks t 
        LEFT JOIN owners o ON t.owner_id = o.id 
        LEFT JOIN properties p ON t.property_id = p.id 
        WHERE t.id = $id";
$result = executeQuery($sql);

if ($result->num_rows === 0) {
    header('Location: index.php');
    exit();
}

$task = $result->fetch_assoc();

// Check if task is overdue
$is_overdue = ($task['status'] === 'Pending' && strtotime($task['due_date']) < time());
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h2><i class="fas fa-tasks"></i> Task Details</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Tasks</a></li>
                <li class="breadcrumb-item active">View Task</li>
            </ol>
        </nav>
    </div>
    <div class="col-md-4 text-end">
        <a href="edit.php?id=<?php echo $id; ?>" class="btn btn-primary">
            <i class="fas fa-edit"></i> Edit Task
        </a>
        <?php if ($task['status'] === 'Pending'): ?>
            <a href="complete.php?id=<?php echo $id; ?>" class="btn btn-success">
                <i class="fas fa-check"></i> Mark as Completed
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge <?php echo ($task['status'] === 'Pending') ? 'bg-warning' : 'bg-success'; ?> me-2">
                            <?php echo $task['status']; ?>
                        </span>
                        <?php if ($is_overdue): ?>
                            <span class="badge bg-danger">Overdue</span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <strong>Due:</strong> <?php echo formatDate($task['due_date']); ?>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <h5 class="card-title">Task Description</h5>
                <div class="mb-4">
                    <div class="card">
                        <div class="card-body">
                            <p class="card-text"><?php echo nl2br(htmlspecialchars($task['task_description'])); ?></p>
                        </div>
                    </div>
                </div>
                
                <?php if ($task['owner_id']): ?>
                <h5 class="card-title">Owner Information</h5>
                <div class="mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <strong>Name:</strong>
                                </div>
                                <div class="col-md-8">
                                    <a href="../owners/view.php?id=<?php echo $task['owner_id']; ?>">
                                        <?php echo htmlspecialchars($task['owner_name']); ?>
                                    </a>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-4">
                                    <strong>Phone:</strong>
                                </div>
                                <div class="col-md-8">
                                    <a href="tel:<?php echo htmlspecialchars($task['owner_phone']); ?>">
                                        <?php echo htmlspecialchars($task['owner_phone']); ?>
                                    </a>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-4">
                                    <strong>Email:</strong>
                                </div>
                                <div class="col-md-8">
                                    <a href="mailto:<?php echo htmlspecialchars($task['owner_email']); ?>">
                                        <?php echo htmlspecialchars($task['owner_email']); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($task['property_id']): ?>
                <h5 class="card-title">Property Information</h5>
                <div class="mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <strong>Address:</strong>
                                </div>
                                <div class="col-md-8">
                                    <a href="../properties/view.php?id=<?php echo $task['property_id']; ?>">
                                        <?php echo htmlspecialchars($task['property_address']); ?>
                                    </a>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-4">
                                    <strong>Property Type:</strong>
                                </div>
                                <div class="col-md-8">
                                    <?php echo htmlspecialchars($task['property_type']); ?>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-4">
                                    <strong>Status:</strong>
                                </div>
                                <div class="col-md-8">
                                    <?php 
                                    $status_class = '';
                                    switch ($task['property_status']) {
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
                                        <?php echo $task['property_status']; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="d-flex justify-content-between mt-4">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                    <div>
                        <a href="edit.php?id=<?php echo $id; ?>" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="delete.php?id=<?php echo $id; ?>" class="btn btn-danger">
                            <i class="fas fa-trash-alt"></i> Delete
                        </a>
                        <?php if ($task['status'] === 'Pending'): ?>
                            <a href="complete.php?id=<?php echo $id; ?>" class="btn btn-success">
                                <i class="fas fa-check"></i> Complete
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once $base_path . 'includes/footer.php'; ?>