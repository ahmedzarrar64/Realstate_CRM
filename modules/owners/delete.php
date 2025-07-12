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

// Get owner data to confirm it exists
$owner = getOwnerById($id);

if (!$owner) {
    header('Location: index.php');
    exit();
}

$error = '';
$confirm = isset($_GET['confirm']) && $_GET['confirm'] === 'yes';

// If confirmed, delete the owner
if ($confirm) {
    // Delete owner (cascade will handle related records)
    $sql = "DELETE FROM owners WHERE id = $id";
    
    if (executeQuery($sql)) {
        // Redirect to owners list with success message
        header('Location: index.php?success=deleted');
        exit();
    } else {
        $error = 'Error deleting owner';
    }
}
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h2><i class="fas fa-user-times"></i> Delete Owner</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Property Owners</a></li>
                <li class="breadcrumb-item active">Delete Owner</li>
            </ol>
        </nav>
    </div>
</div>

<?php if ($error): ?>
    <?php echo showError($error); ?>
<?php endif; ?>

<div class="card">
    <div class="card-header bg-danger text-white">
        <i class="fas fa-exclamation-triangle"></i> Confirm Deletion
    </div>
    <div class="card-body">
        <p>Are you sure you want to delete the following owner?</p>
        
        <table class="table">
            <tr>
                <th width="30%">Name:</th>
                <td><?php echo htmlspecialchars($owner['name']); ?></td>
            </tr>
            <tr>
                <th>Phone:</th>
                <td><?php echo htmlspecialchars($owner['phone']); ?></td>
            </tr>
            <tr>
                <th>Email:</th>
                <td><?php echo htmlspecialchars($owner['email']); ?></td>
            </tr>
        </table>
        
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-circle"></i> Warning: This will also delete all properties, contact logs, and tasks associated with this owner.
        </div>
        
        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
            <a href="index.php" class="btn btn-secondary">Cancel</a>
            <a href="delete.php?id=<?php echo $id; ?>&confirm=yes" class="btn btn-danger">Delete Owner</a>
        </div>
    </div>
</div>

<?php require_once $base_path . 'includes/footer.php'; ?>