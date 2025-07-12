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

// Process deletion
if (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === 'yes') {
    // Delete related contact logs
    $sql = "DELETE FROM contact_logs WHERE property_id = $id";
    executeQuery($sql);
    
    // Delete related tasks
    $sql = "DELETE FROM tasks WHERE property_id = $id";
    executeQuery($sql);
    
    // Delete the property
    $sql = "DELETE FROM properties WHERE id = $id";
    executeQuery($sql);
    
    // Set success message and redirect
    $_SESSION['success_message'] = "Property successfully deleted.";
    header('Location: index.php');
    exit();
}
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h2><i class="fas fa-trash-alt"></i> Delete Property</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Properties</a></li>
                <li class="breadcrumb-item active">Delete Property</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card border-danger">
            <div class="card-header bg-danger text-white">
                <i class="fas fa-exclamation-triangle"></i> Warning: Delete Property
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <p><strong>You are about to delete the following property:</strong></p>
                    <ul>
                        <li><strong>Address:</strong> <?php echo htmlspecialchars($property['address']); ?></li>
                        <li><strong>Property Type:</strong> <?php echo htmlspecialchars($property['property_type']); ?></li>
                        <li><strong>Area:</strong> <?php echo htmlspecialchars($property['area']); ?> sqm</li>
                        <li><strong>Price:</strong> $<?php echo number_format($property['price'], 2); ?></li>
                        <li><strong>Status:</strong> <?php echo htmlspecialchars($property['status']); ?></li>
                        <?php if ($owner): ?>
                        <li><strong>Owner:</strong> <?php echo htmlspecialchars($owner['name']); ?></li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="alert alert-danger">
                    <p><strong>Warning:</strong> This action cannot be undone. Deleting this property will also remove:</p>
                    <ul>
                        <li>All contact logs associated with this property</li>
                        <li>All tasks associated with this property</li>
                    </ul>
                </div>
                
                <form method="post" onsubmit="return confirm('Are you sure you want to delete this property? This action cannot be undone.');">
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