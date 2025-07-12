<?php
// Adjust path for includes
$base_path = '../../';
require_once $base_path . 'includes/header.php';
require_once $base_path . 'includes/functions.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$sale_id = intval($_GET['id']);
$error = '';
$success = '';

// Get sale details to check if it exists and get property_id
$sql = "SELECT ps.*, p.address 
        FROM property_sales ps
        JOIN properties p ON ps.property_id = p.id
        WHERE ps.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $sale_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Sale not found
    header('Location: index.php');
    exit;
}

$sale = $result->fetch_assoc();
$property_id = $sale['property_id'];
$property_address = $sale['address'];
$stmt->close();

// Check if there are related commissions
$commission_sql = "SELECT COUNT(*) as count FROM commissions WHERE sale_id = ?";
$commission_stmt = $conn->prepare($commission_sql);
$commission_stmt->bind_param("i", $sale_id);
$commission_stmt->execute();
$commission_result = $commission_stmt->get_result();
$commission_count = $commission_result->fetch_assoc()['count'];
$commission_stmt->close();

// Process deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // First delete related commissions if any
        if ($commission_count > 0) {
            $delete_commissions_sql = "DELETE FROM commissions WHERE sale_id = ?";
            $delete_commissions_stmt = $conn->prepare($delete_commissions_sql);
            $delete_commissions_stmt->bind_param("i", $sale_id);
            $delete_commissions_stmt->execute();
            $delete_commissions_stmt->close();
        }
        
        // Then delete the sale record
        $delete_sql = "DELETE FROM property_sales WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $sale_id);
        $delete_stmt->execute();
        $delete_stmt->close();
        
        // Update property status back to Available if needed
        // This is optional and depends on business logic
        $update_property_sql = "UPDATE properties SET status = 'Available', updated_at = NOW() WHERE id = ?";
        $update_property_stmt = $conn->prepare($update_property_sql);
        $update_property_stmt->bind_param("i", $property_id);
        $update_property_stmt->execute();
        $update_property_stmt->close();
        
        // Commit transaction
        $conn->commit();
        
        $success = "Property sale record deleted successfully!";
        header("refresh:2;url=index.php");
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $error = "Error: " . $e->getMessage();
    }
}
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo $base_path; ?>index.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="index.php">Property Sales</a></li>
        <li class="breadcrumb-item active">Delete Sale</li>
    </ol>
</nav>

<div class="row mb-4">
    <div class="col">
        <h2><i class="fas fa-trash"></i> Delete Property Sale</h2>
    </div>
</div>

<?php if (!empty($error)): ?>
<div class="alert alert-danger">
    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
</div>
<?php endif; ?>

<?php if (!empty($success)): ?>
<div class="alert alert-success">
    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
    <div class="mt-2">Redirecting to sales list...</div>
</div>
<?php else: ?>

<div class="card">
    <div class="card-header bg-danger text-white">
        <h5 class="mb-0">Confirm Deletion</h5>
    </div>
    <div class="card-body">
        <p class="lead">Are you sure you want to delete the sale record for:</p>
        <p><strong>Property:</strong> <?php echo htmlspecialchars($property_address); ?></p>
        <p><strong>Buyer:</strong> <?php echo htmlspecialchars($sale['buyer_name']); ?></p>
        <p><strong>Sale Date:</strong> <?php echo formatDate($sale['sale_date']); ?></p>
        <p><strong>Sale Price:</strong> $<?php echo number_format($sale['sale_price'], 2); ?></p>
        
        <?php if ($commission_count > 0): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> 
            Warning: This sale has <?php echo $commission_count; ?> associated commission record(s). 
            Deleting this sale will also delete all related commission records.
        </div>
        <?php endif; ?>
        
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> 
            Note: Deleting this sale will revert the property status back to "Available".
        </div>
        
        <form method="post" action="">
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="index.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" name="confirm_delete" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Confirm Delete
                </button>
            </div>
        </form>
    </div>
</div>

<?php endif; ?>

<?php require_once $base_path . 'includes/footer.php'; ?>