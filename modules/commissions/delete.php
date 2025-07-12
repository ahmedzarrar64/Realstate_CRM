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

$commission_id = intval($_GET['id']);
$error = '';
$success = '';

// Get commission details to check if it exists
$sql = "SELECT c.*, 
               u.name as agent_name,
               ps.sale_price, ps.buyer_name,
               p.address
        FROM commissions c
        LEFT JOIN users u ON c.agent_id = u.id
        JOIN property_sales ps ON c.sale_id = ps.id
        JOIN properties p ON ps.property_id = p.id
        WHERE c.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $commission_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Commission not found
    header('Location: index.php');
    exit;
}

$commission = $result->fetch_assoc();
$stmt->close();

// Process deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    // Delete the commission record
    $delete_sql = "DELETE FROM commissions WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $commission_id);
    
    if ($delete_stmt->execute()) {
        $success = "Commission deleted successfully!";
        header("refresh:2;url=index.php");
    } else {
        $error = "Error deleting commission: " . $conn->error;
    }
    $delete_stmt->close();
}
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo $base_path; ?>index.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="index.php">Commissions</a></li>
        <li class="breadcrumb-item active">Delete Commission</li>
    </ol>
</nav>

<div class="row mb-4">
    <div class="col">
        <h2><i class="fas fa-trash"></i> Delete Commission</h2>
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
    <div class="mt-2">Redirecting to commissions list...</div>
</div>
<?php else: ?>

<div class="card">
    <div class="card-header bg-danger text-white">
        <h5 class="mb-0">Confirm Deletion</h5>
    </div>
    <div class="card-body">
        <p class="lead">Are you sure you want to delete this commission record?</p>
        
        <div class="row mb-4">
            <div class="col-md-6">
                <h6>Commission Details</h6>
                <p><strong>Amount:</strong> $<?php echo number_format($commission['amount'], 2); ?></p>
                <?php if ($commission['is_percentage']): ?>
                <p><strong>Percentage:</strong> <?php echo number_format($commission['percentage'], 2); ?>%</p>
                <?php endif; ?>
                <p><strong>Status:</strong> <?php echo $commission['status']; ?></p>
                <p><strong>Created:</strong> <?php echo formatDateTime($commission['created_at']); ?></p>
            </div>
            <div class="col-md-6">
                <h6>Related Information</h6>
                <p><strong>Property:</strong> <?php echo htmlspecialchars($commission['address']); ?></p>
                <p><strong>Sale Price:</strong> $<?php echo number_format($commission['sale_price'], 2); ?></p>
                <p><strong>Buyer:</strong> <?php echo htmlspecialchars($commission['buyer_name']); ?></p>
                <?php if (!empty($commission['agent_name'])): ?>
                <p><strong>Agent:</strong> <?php echo htmlspecialchars($commission['agent_name']); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> 
            Warning: This action cannot be undone. The commission record will be permanently deleted.
        </div>
        
        <form method="post" action="">
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="view.php?id=<?php echo $commission_id; ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" name="confirm_delete" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Confirm Delete
                </button>
            </div>
        </form>
    </div>
</div>

<?php endif; ?>

<?php require_once $base_path . 'includes/footer.php'; ?>