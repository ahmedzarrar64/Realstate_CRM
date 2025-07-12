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

// Get all owners for dropdown
$owners = getAllOwners();

// Get all properties for dropdown
$properties = getAllProperties();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize inputs
    $owner_id = isset($_POST['owner_id']) ? (int)$_POST['owner_id'] : null;
    $property_id = isset($_POST['property_id']) ? (int)$_POST['property_id'] : null;
    $task_description = mysqli_real_escape_string($conn, $_POST['task_description']);
    $due_date = mysqli_real_escape_string($conn, $_POST['due_date']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    // Validation
    $errors = [];
    
    if (empty($task_description)) {
        $errors[] = "Task description is required.";
    }
    
    if (empty($due_date)) {
        $errors[] = "Due date is required.";
    }
    
    if (empty($status)) {
        $errors[] = "Status is required.";
    }
    
    // If no errors, update database
    if (empty($errors)) {
        $sql = "UPDATE tasks 
                SET owner_id = " . ($owner_id ? $owner_id : "NULL") . ", 
                    property_id = " . ($property_id ? $property_id : "NULL") . ", 
                    task_description = '$task_description', 
                    due_date = '$due_date', 
                    status = '$status' 
                WHERE id = $id";
        
        if (executeQuery($sql)) {
            $_SESSION['success_message'] = "Task updated successfully.";
            
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
            $errors[] = "Error updating task: " . mysqli_error($conn);
        }
    }
}
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h2><i class="fas fa-edit"></i> Edit Task</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Tasks</a></li>
                <li class="breadcrumb-item active">Edit Task</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-tasks"></i> Task Details
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="task_description" class="form-label">Task Description <span class="text-danger">*</span></label>
                        <textarea name="task_description" id="task_description" class="form-control" rows="3" required><?php echo htmlspecialchars($task['task_description']); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="due_date" class="form-label">Due Date <span class="text-danger">*</span></label>
                        <input type="date" name="due_date" id="due_date" class="form-control" required value="<?php echo date('Y-m-d', strtotime($task['due_date'])); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="owner_id" class="form-label">Owner</label>
                        <select name="owner_id" id="owner_id" class="form-select">
                            <option value="">Select Owner (Optional)</option>
                            <?php foreach ($owners as $owner): ?>
                                <option value="<?php echo $owner['id']; ?>" <?php echo ($task['owner_id'] == $owner['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($owner['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="property_id" class="form-label">Property</label>
                        <select name="property_id" id="property_id" class="form-select">
                            <option value="">Select Property (Optional)</option>
                            <?php foreach ($properties as $property): ?>
                                <option value="<?php echo $property['id']; ?>" 
                                        data-owner="<?php echo $property['owner_id']; ?>" 
                                        <?php echo ($task['property_id'] == $property['id']) ? 'selected' : ''; ?>
                                        <?php echo ($task['property_id'] != $property['id'] && $task['owner_id'] && $task['owner_id'] != $property['owner_id']) ? 'style="display:none;"' : ''; ?>>
                                    <?php echo htmlspecialchars($property['address']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" id="status" class="form-select" required>
                            <option value="Pending" <?php echo ($task['status'] === 'Pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="Completed" <?php echo ($task['status'] === 'Completed') ? 'selected' : ''; ?>>Completed</option>
                        </select>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Task
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Filter properties based on selected owner
        const ownerSelect = document.getElementById('owner_id');
        const propertySelect = document.getElementById('property_id');
        
        ownerSelect.addEventListener('change', function() {
            const selectedOwnerId = this.value;
            const propertyOptions = propertySelect.options;
            
            // Reset property selection if owner doesn't match
            const currentPropertyOption = propertySelect.options[propertySelect.selectedIndex];
            if (currentPropertyOption && currentPropertyOption.getAttribute('data-owner') !== selectedOwnerId) {
                propertySelect.value = '';
            }
            
            // Show/hide properties based on owner
            for (let i = 0; i < propertyOptions.length; i++) {
                const option = propertyOptions[i];
                if (i === 0) { // Skip the placeholder option
                    continue;
                }
                
                const ownerIdForProperty = option.getAttribute('data-owner');
                if (selectedOwnerId === '' || ownerIdForProperty === selectedOwnerId) {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                }
            }
        });
    });
</script>

<?php require_once $base_path . 'includes/footer.php'; ?>