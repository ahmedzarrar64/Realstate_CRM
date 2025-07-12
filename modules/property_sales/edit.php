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

// Get sale details
$sql = "SELECT ps.*, p.address, p.property_type, o.name as owner_name 
        FROM property_sales ps
        JOIN properties p ON ps.property_id = p.id
        JOIN owners o ON p.owner_id = o.id
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
$stmt->close();

// Initialize variables with existing data
$property_id = $sale['property_id'];
$buyer_name = $sale['buyer_name'];
$buyer_contact = $sale['buyer_contact'];
$sale_price = $sale['sale_price'];
$sale_date = $sale['sale_date'];
$notes = $sale['notes'];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize inputs
    $buyer_name = isset($_POST['buyer_name']) ? trim($_POST['buyer_name']) : '';
    $buyer_contact = isset($_POST['buyer_contact']) ? trim($_POST['buyer_contact']) : '';
    $sale_price = isset($_POST['sale_price']) ? floatval($_POST['sale_price']) : 0;
    $sale_date = isset($_POST['sale_date']) ? trim($_POST['sale_date']) : '';
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
    
    // Validation
    if (empty($buyer_name)) {
        $error = "Buyer name is required.";
    } elseif (empty($buyer_contact)) {
        $error = "Buyer contact information is required.";
    } elseif ($sale_price <= 0) {
        $error = "Sale price must be greater than zero.";
    } elseif (empty($sale_date)) {
        $error = "Sale date is required.";
    } else {
        // Update sale record
        $sql = "UPDATE property_sales 
                SET buyer_name = ?, buyer_contact = ?, sale_price = ?, sale_date = ?, notes = ?, updated_at = NOW() 
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdssi", $buyer_name, $buyer_contact, $sale_price, $sale_date, $notes, $sale_id);
        
        if ($stmt->execute()) {
            $success = "Property sale updated successfully!";
            // Refresh sale data
            $sql = "SELECT ps.*, p.address, p.property_type, o.name as owner_name 
                    FROM property_sales ps
                    JOIN properties p ON ps.property_id = p.id
                    JOIN owners o ON p.owner_id = o.id
                    WHERE ps.id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $sale_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $sale = $result->fetch_assoc();
        } else {
            $error = "Error updating sale: " . $conn->error;
        }
        $stmt->close();
    }
}
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo $base_path; ?>index.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="index.php">Property Sales</a></li>
        <li class="breadcrumb-item"><a href="view.php?id=<?php echo $sale_id; ?>">View Sale</a></li>
        <li class="breadcrumb-item active">Edit Sale</li>
    </ol>
</nav>

<div class="row mb-4">
    <div class="col">
        <h2><i class="fas fa-edit"></i> Edit Property Sale</h2>
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
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0">Sale Details</h5>
    </div>
    <div class="card-body">
        <form method="post" action="">
            <div class="mb-3">
                <label class="form-label">Property</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($sale['address']); ?> (<?php echo htmlspecialchars($sale['property_type']); ?>)" readonly>
                <div class="form-text">Property cannot be changed. Owner: <?php echo htmlspecialchars($sale['owner_name']); ?></div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="buyer_name" class="form-label required-field">Buyer Name</label>
                    <input type="text" class="form-control" id="buyer_name" name="buyer_name" value="<?php echo htmlspecialchars($buyer_name); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="buyer_contact" class="form-label required-field">Buyer Contact</label>
                    <input type="text" class="form-control" id="buyer_contact" name="buyer_contact" value="<?php echo htmlspecialchars($buyer_contact); ?>" required>
                    <div class="form-text">Phone number or email address</div>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="sale_price" class="form-label required-field">Sale Price ($)</label>
                    <input type="number" step="0.01" class="form-control" id="sale_price" name="sale_price" value="<?php echo htmlspecialchars($sale_price); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="sale_date" class="form-label required-field">Sale Date</label>
                    <input type="date" class="form-control" id="sale_date" name="sale_date" value="<?php echo htmlspecialchars($sale_date); ?>" required>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="notes" class="form-label">Notes</label>
                <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($notes); ?></textarea>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="view.php?id=<?php echo $sale_id; ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Sale</button>
            </div>
        </form>
    </div>
</div>

<style>
    .required-field::after {
        content: " *";
        color: red;
    }
</style>

<?php require_once $base_path . 'includes/footer.php'; ?>