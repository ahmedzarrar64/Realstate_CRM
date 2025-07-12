<?php
// Adjust path for includes
$base_path = '../../';
require_once $base_path . 'includes/header.php';
require_once $base_path . 'includes/functions.php';

// Initialize variables
$property_id = '';
$buyer_name = '';
$buyer_contact = '';
$sale_price = '';
$sale_date = date('Y-m-d'); // Default to today
$notes = '';
$error = '';
$success = '';

// Get available properties (only those with status 'Available' or 'Under Negotiation')
$sql = "SELECT p.*, o.name as owner_name 
        FROM properties p 
        JOIN owners o ON p.owner_id = o.id 
        WHERE p.status IN ('Available', 'Under Negotiation')
        ORDER BY p.address ASC";
$result = executeQuery($sql);
$properties = [];
while ($row = $result->fetch_assoc()) {
    $properties[] = $row;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize inputs
    $property_id = isset($_POST['property_id']) ? intval($_POST['property_id']) : 0;
    $buyer_name = isset($_POST['buyer_name']) ? trim($_POST['buyer_name']) : '';
    $buyer_contact = isset($_POST['buyer_contact']) ? trim($_POST['buyer_contact']) : '';
    $sale_price = isset($_POST['sale_price']) ? floatval($_POST['sale_price']) : 0;
    $sale_date = isset($_POST['sale_date']) ? trim($_POST['sale_date']) : '';
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
    
    // Validation
    if ($property_id <= 0) {
        $error = "Please select a property.";
    } elseif (empty($buyer_name)) {
        $error = "Buyer name is required.";
    } elseif (empty($buyer_contact)) {
        $error = "Buyer contact information is required.";
    } elseif ($sale_price <= 0) {
        $error = "Sale price must be greater than zero.";
    } elseif (empty($sale_date)) {
        $error = "Sale date is required.";
    } else {
        // Get property details to update property status
        $property_sql = "SELECT * FROM properties WHERE id = ?";
        $property_stmt = $conn->prepare($property_sql);
        $property_stmt->bind_param("i", $property_id);
        $property_stmt->execute();
        $property_result = $property_stmt->get_result();
        $property = $property_result->fetch_assoc();
        $property_stmt->close();
        
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Insert sale record
            $sql = "INSERT INTO property_sales (property_id, buyer_name, buyer_contact, sale_price, sale_date, notes, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issdss", $property_id, $buyer_name, $buyer_contact, $sale_price, $sale_date, $notes);
            $stmt->execute();
            $sale_id = $conn->insert_id;
            $stmt->close();
            
            // Update property status to 'Sold'
            $update_sql = "UPDATE properties SET status = 'Sold', updated_at = NOW() WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $property_id);
            $update_stmt->execute();
            $update_stmt->close();
            
            // Commit transaction
            $conn->commit();
            
            // Success message and redirect
            $success = "Property sale recorded successfully!";
            header("refresh:2;url=view.php?id=" . $sale_id);
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo $base_path; ?>index.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="index.php">Property Sales</a></li>
        <li class="breadcrumb-item active">Add Sale</li>
    </ol>
</nav>

<div class="row mb-4">
    <div class="col">
        <h2><i class="fas fa-handshake"></i> Record Property Sale</h2>
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

<?php if (count($properties) === 0): ?>
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle"></i> No available properties found for sale. 
    <a href="../properties/add.php" class="alert-link">Add a new property</a> or 
    <a href="../properties/index.php" class="alert-link">check existing properties</a>.
</div>
<?php else: ?>
<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0">Sale Details</h5>
    </div>
    <div class="card-body">
        <form method="post" action="">
            <div class="mb-3">
                <label for="property_id" class="form-label required-field">Select Property</label>
                <select class="form-select" id="property_id" name="property_id" required>
                    <option value="">Select Property</option>
                    <?php foreach ($properties as $property): ?>
                        <option value="<?php echo $property['id']; ?>" <?php echo ($property_id == $property['id']) ? 'selected' : ''; ?>
                                data-price="<?php echo $property['price']; ?>">
                            <?php echo htmlspecialchars($property['address']); ?> 
                            (<?php echo htmlspecialchars($property['property_type']); ?>) - 
                            Owner: <?php echo htmlspecialchars($property['owner_name']); ?> - 
                            Listed: $<?php echo number_format($property['price'], 2); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">Only properties with status 'Available' or 'Under Negotiation' are shown.</div>
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
                <a href="index.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Record Sale</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
    // Auto-fill sale price with property price
    document.getElementById('property_id').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.value) {
            const propertyPrice = selectedOption.getAttribute('data-price');
            document.getElementById('sale_price').value = propertyPrice;
        }
    });
</script>

<style>
    .required-field::after {
        content: " *";
        color: red;
    }
</style>

<?php require_once $base_path . 'includes/footer.php'; ?>