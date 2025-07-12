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
$sql = "SELECT t.*, o.name as owner_name, p.address as property_address 
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

// Process deletion
if (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === 'yes') {
    $sql = "DELETE FROM tasks WHERE id = $id";
    
    if (executeQuery($sql)) {
        $_SESSION['success_message'] = "Task successfully deleted.";
        
        // Redirect based on context
        if (!empty($task['owner_id'])) {
            header("Location: ../owners/view.php?id={$task['owner_id']}");
        } elseif (!empty($task['property_id'])) {
            header("Location: ../properties/view.php?id={$task['property_id']}");
        } else {
            header("Location: index.php");
        }
        exit();
    } else {
        $error_message = "Error deleting task: " . mysqli_error($conn);
    }
}
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h2><i class="fas fa-trash-alt"></i> Delete Task</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Tasks</a></li>
                <li class="breadcrumb-item active">Delete Task</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card border-danger">
            <div class="card-header bg-danger text-white">
                <i class="fas fa-exclamation-triangle"></i> Warning: Delete Task
            </div>
            <div class="card-body">
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <div class="alert alert-warning">
                    <p><strong>You are about to delete the following task:</strong></p>
                    <ul>
                        <li><strong>Due Date:</strong> <?php echo formatDate($task['due_date']); ?></li>
                        <li><strong>Status:</strong> <?php echo htmlspecialchars($task['status']); ?></li>
                        <?php if ($task['owner_name']): ?>
                        <li><strong>Owner:</strong> <?php echo htmlspecialchars($task['owner_name']); ?></li>
                        <?php endif; ?>
                        <?php if ($task['property_address']): ?>
                        <li><strong>Property:</strong> <?php echo htmlspecialchars($task['property_address']); ?></li>
                        <?php endif; ?>
                    </ul>
                    <p><strong>Description:</strong></p>
                    <div class="card">
                        <div class="card-body">
                            <?php echo nl2br(htmlspecialchars($task['task_description'])); ?>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-danger">
                    <p><strong>Warning:</strong> This action cannot be undone. Are you sure you want to delete this task?</p>
                </div>
                
                <form method="post" onsubmit="return confirm('Are you sure you want to delete this task? This action cannot be undone.');">
                    <div class="d-flex justify-content-between">
                        <a href="view.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Cancel
                        </a>
                        <button type="submit" name="confirm_delete" value="yes" class="btn btn-danger">
                            <i class="fas fa-trash-alt"></i> Confirm Delete
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once $base_path . 'includes/footer.php'; ?>