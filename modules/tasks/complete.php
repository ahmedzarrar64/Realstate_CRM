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

// Get task data
$sql = "SELECT * FROM tasks WHERE id = $id";
$result = executeQuery($sql);

if ($result->num_rows === 0) {
    header('Location: index.php');
    exit();
}

$task = $result->fetch_assoc();

// Check if task is already completed
if ($task['status'] === 'Completed') {
    $_SESSION['error_message'] = "This task is already marked as completed.";
    header('Location: view.php?id=' . $id);
    exit();
}

// Process completion
if (isset($_POST['confirm_complete']) && $_POST['confirm_complete'] === 'yes') {
    $completion_date = date('Y-m-d H:i:s');
    $sql = "UPDATE tasks SET status = 'Completed', completion_date = '$completion_date' WHERE id = $id";
    
    if (executeQuery($sql)) {
        $_SESSION['success_message'] = "Task successfully marked as completed.";
        
        // Redirect based on context
        if (!empty($_POST['redirect'])) {
            switch ($_POST['redirect']) {
                case 'owner':
                    header("Location: ../owners/view.php?id={$task['owner_id']}");
                    break;
                case 'property':
                    header("Location: ../properties/view.php?id={$task['property_id']}");
                    break;
                default:
                    header("Location: view.php?id=$id");
            }
        } else {
            header("Location: view.php?id=$id");
        }
        exit();
    } else {
        $error_message = "Error updating task: " . mysqli_error($conn);
    }
}

// Determine redirect source
$redirect = '';
if (isset($_GET['from'])) {
    switch ($_GET['from']) {
        case 'owner':
            $redirect = 'owner';
            break;
        case 'property':
            $redirect = 'property';
            break;
    }
}
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h2><i class="fas fa-check"></i> Complete Task</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Tasks</a></li>
                <li class="breadcrumb-item active">Complete Task</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card border-success">
            <div class="card-header bg-success text-white">
                <i class="fas fa-check-circle"></i> Mark Task as Completed
            </div>
            <div class="card-body">
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <div class="alert alert-info">
                    <p><strong>You are about to mark the following task as completed:</strong></p>
                    <ul>
                        <li><strong>Due Date:</strong> <?php echo formatDate($task['due_date']); ?></li>
                        <li><strong>Description:</strong> <?php echo htmlspecialchars($task['task_description']); ?></li>
                    </ul>
                </div>
                
                <form method="post">
                    <input type="hidden" name="redirect" value="<?php echo $redirect; ?>">
                    <div class="d-flex justify-content-between">
                        <a href="view.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Cancel
                        </a>
                        <button type="submit" name="confirm_complete" value="yes" class="btn btn-success">
                            <i class="fas fa-check"></i> Mark as Completed
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once $base_path . 'includes/footer.php'; ?>