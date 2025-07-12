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

// Get contact log data with owner and property information
$sql = "SELECT cl.*, o.name as owner_name, p.address as property_address 
        FROM contact_logs cl 
        LEFT JOIN owners o ON cl.owner_id = o.id 
        LEFT JOIN properties p ON cl.property_id = p.id 
        WHERE cl.id = $id";
$result = executeQuery($sql);

if ($result->num_rows === 0) {
    header('Location: index.php');
    exit();
}

$contact_log = $result->fetch_assoc();

// Process deletion
if (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === 'yes') {
    $sql = "DELETE FROM contact_logs WHERE id = $id";
    
    if (executeQuery($sql)) {
        $_SESSION['success_message'] = "Contact log successfully deleted.";
        
        // Redirect based on context
        if (!empty($contact_log['owner_id'])) {
            header("Location: ../owners/view.php?id={$contact_log['owner_id']}");
        } elseif (!empty($contact_log['property_id'])) {
            header("Location: ../properties/view.php?id={$contact_log['property_id']}");
        } else {
            header("Location: index.php");
        }
        exit();
    } else {
        $error_message = "Error deleting contact log: " . mysqli_error($conn);
    }
}
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h2><i class="fas fa-trash-alt"></i> Delete Contact Log</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Contact Logs</a></li>
                <li class="breadcrumb-item active">Delete Contact Log</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card border-danger">
            <div class="card-header bg-danger text-white">
                <i class="fas fa-exclamation-triangle"></i> Warning: Delete Contact Log
            </div>
            <div class="card-body">
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <div class="alert alert-warning">
                    <p><strong>You are about to delete the following contact log:</strong></p>
                    <ul>
                        <li><strong>Date & Time:</strong> <?php echo formatDateTime($contact_log['contact_date']); ?></li>
                        <li><strong>Contact Type:</strong> <?php echo htmlspecialchars($contact_log['contact_type']); ?></li>
                        <?php if ($contact_log['owner_name']): ?>
                        <li><strong>Owner:</strong> <?php echo htmlspecialchars($contact_log['owner_name']); ?></li>
                        <?php endif; ?>
                        <?php if ($contact_log['property_address']): ?>
                        <li><strong>Property:</strong> <?php echo htmlspecialchars($contact_log['property_address']); ?></li>
                        <?php endif; ?>
                    </ul>
                    <p><strong>Description:</strong></p>
                    <div class="card">
                        <div class="card-body">
                            <?php echo nl2br(htmlspecialchars($contact_log['description'])); ?>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-danger">
                    <p><strong>Warning:</strong> This action cannot be undone. Are you sure you want to delete this contact log?</p>
                </div>
                
                <form method="post" onsubmit="return confirm('Are you sure you want to delete this contact log? This action cannot be undone.');">
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