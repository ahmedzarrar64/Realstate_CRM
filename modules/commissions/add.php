<?php
// Adjust path for includes
$base_path = '../../';
require_once $base_path . 'includes/header.php';
require_once $base_path . 'includes/functions.php';

// Initialize variables
$sale_id = isset($_GET['sale_id']) ? intval($_GET['sale_id']) : 0;
$agent_id = '';
$amount = '';
$percentage = '';
$is_percentage = 0;
$status = 'Unpaid'; // Default status
$notes = '';
$error = '';
$success = '';

// Get all property sales that don't have commissions yet or have the selected sale_id
$sales_sql = "SELECT ps.id, ps.sale_price, ps.sale_date, ps.buyer_name, 
                    p.address, p.property_type, o.name as owner_name
             FROM property_sales ps
             JOIN properties p ON ps.property_id = p.id
             JOIN owners o ON p.owner_id = o.id
             ORDER BY ps.sale_date DESC";
$sales_result = executeQuery($sales_sql);
$sales = [];
while ($row = $sales_result->fetch_assoc()) {
    $sales[] = $row;
}

// Get all agents (users)
$agents_sql = "SELECT id, name FROM users ORDER BY name ASC";
$agents_result = executeQuery($agents_sql);
$agents = [];
while ($row = $agents_result->fetch_assoc()) {
    $agents[] = $row;
}

// If sale_id is provided, get sale details
$selected_sale = null;
if ($sale_id > 0) {
    foreach ($sales as $sale) {
        if ($sale['id'] == $sale_id) {
            $selected_sale = $sale;
            break;
        }
    }
    
    // If sale found, pre-calculate default commission (e.g., 3% of sale price)
    if ($selected_sale) {
        $percentage = 3; // Default percentage
        $amount = $selected_sale['sale_price'] * ($percentage / 100);
        $is_percentage = 1;
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize inputs
    $sale_id = isset($_POST['sale_id']) ? intval($_POST['sale_id']) : 0;
    $agent_id = isset($_POST['agent_id']) ? intval($_POST['agent_id']) : 0;
    $is_percentage = isset($_POST['is_percentage']) ? 1 : 0;
    $status = isset($_POST['status']) ? trim($_POST['status']) : 'Unpaid';
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
    
    // Handle percentage or flat amount
    if ($is_percentage) {
        $percentage = isset($_POST['percentage']) ? floatval($_POST['percentage']) : 0;
        
        // Get sale price to calculate amount
        $sale_price_sql = "SELECT sale_price FROM property_sales WHERE id = ?";
        $sale_price_stmt = $conn->prepare($sale_price_sql);
        $sale_price_stmt->bind_param("i", $sale_id);
        $sale_price_stmt->execute();
        $sale_price_result = $sale_price_stmt->get_result();
        $sale_price_row = $sale_price_result->fetch_assoc();
        $sale_price_stmt->close();
        
        if ($sale_price_row) {
            $amount = $sale_price_row['sale_price'] * ($percentage / 100);
        } else {
            $error = "Invalid sale selected.";
        }
    } else {
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        $percentage = 0;
    }
    
    // Validation
    if ($sale_id <= 0) {
        $error = "Please select a property sale.";
    } elseif ($amount <= 0) {
        $error = "Commission amount must be greater than zero.";
    } elseif ($is_percentage && ($percentage <= 0 || $percentage > 100)) {
        $error = "Percentage must be between 0 and 100.";
    } else {
        // Insert commission record
        $sql = "INSERT INTO commissions (sale_id, agent_id, amount, percentage, is_percentage, status, notes, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiddsss", $sale_id, $agent_id, $amount, $percentage, $is_percentage, $status, $notes);
        
        if ($stmt->execute()) {
            $commission_id = $conn->insert_id;
            $success = "Commission recorded successfully!";
            header("refresh:2;url=view.php?id=" . $commission_id);
        } else {
            $error = "Error recording commission: " . $conn->error;
        }
        $stmt->close();
    }
    
    // If there was an error, get the selected sale again for the form
    if (!empty($error) && $sale_id > 0) {
        foreach ($sales as $sale) {
            if ($sale['id'] == $sale_id) {
                $selected_sale = $sale;
                break;
            }
        }
    }
}
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo $base_path; ?>index.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="index.php">Commissions</a></li>
        <li class="breadcrumb-item active">Add Commission</li>
    </ol>
</nav>

<div class="row mb-4">
    <div class="col">
        <h2><i class="fas fa-money-bill-wave"></i> Add Commission</h2>
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

<?php if (count($sales) === 0): ?>
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle"></i> No property sales found. 
    <a href="../property_sales/add.php" class="alert-link">Record a property sale</a> first.
</div>
<?php else: ?>
<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0">Commission Details</h5>
    </div>
    <div class="card-body">
        <form method="post" action="" id="commissionForm">
            <div class="mb-3">
                <label for="sale_id" class="form-label required-field">Select Property Sale</label>
                <select class="form-select" id="sale_id" name="sale_id" required>
                    <option value="">Select Property Sale</option>
                    <?php foreach ($sales as $sale): ?>
                        <option value="<?php echo $sale['id']; ?>" 
                                <?php echo ($sale_id == $sale['id']) ? 'selected' : ''; ?>
                                data-sale-price="<?php echo $sale['sale_price']; ?>">
                            <?php echo htmlspecialchars($sale['address']); ?> - 
                            $<?php echo number_format($sale['sale_price'], 2); ?> - 
                            <?php echo formatDate($sale['sale_date']); ?> - 
                            Buyer: <?php echo htmlspecialchars($sale['buyer_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="mb-3">
                <label for="agent_id" class="form-label">Agent (Optional)</label>
                <select class="form-select" id="agent_id" name="agent_id">
                    <option value="">No Agent</option>
                    <?php foreach ($agents as $agent): ?>
                        <option value="<?php echo $agent['id']; ?>" <?php echo ($agent_id == $agent['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($agent['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="is_percentage" name="is_percentage" 
                           <?php echo $is_percentage ? 'checked' : ''; ?> value="1">
                    <label class="form-check-label" for="is_percentage">
                        Calculate commission as percentage of sale price
                    </label>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6" id="percentageField" <?php echo !$is_percentage ? 'style="display:none;"' : ''; ?>>
                    <label for="percentage" class="form-label required-field">Percentage (%)</label>
                    <input type="number" step="0.01" min="0" max="100" class="form-control" id="percentage" name="percentage" 
                           value="<?php echo htmlspecialchars($percentage); ?>">
                </div>
                <div class="col-md-6" id="amountField" <?php echo $is_percentage ? 'style="display:none;"' : ''; ?>>
                    <label for="amount" class="form-label required-field">Amount ($)</label>
                    <input type="number" step="0.01" min="0" class="form-control" id="amount" name="amount" 
                           value="<?php echo htmlspecialchars($amount); ?>">
                </div>
                <div class="col-md-6">
                    <label for="status" class="form-label required-field">Status</label>
                    <select class="form-select" id="status" name="status" required>
                        <option value="Unpaid" <?php echo ($status === 'Unpaid') ? 'selected' : ''; ?>>Unpaid</option>
                        <option value="Paid" <?php echo ($status === 'Paid') ? 'selected' : ''; ?>>Paid</option>
                    </select>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="notes" class="form-label">Notes</label>
                <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($notes); ?></textarea>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="index.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Commission</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
    // Toggle between percentage and flat amount
    document.getElementById('is_percentage').addEventListener('change', function() {
        const percentageField = document.getElementById('percentageField');
        const amountField = document.getElementById('amountField');
        
        if (this.checked) {
            percentageField.style.display = '';
            amountField.style.display = 'none';
            calculateAmount();
        } else {
            percentageField.style.display = 'none';
            amountField.style.display = '';
        }
    });
    
    // Calculate amount based on percentage
    document.getElementById('percentage').addEventListener('input', calculateAmount);
    document.getElementById('sale_id').addEventListener('change', calculateAmount);
    
    function calculateAmount() {
        const isPercentage = document.getElementById('is_percentage').checked;
        if (!isPercentage) return;
        
        const percentageInput = document.getElementById('percentage');
        const saleSelect = document.getElementById('sale_id');
        const amountInput = document.getElementById('amount');
        
        if (saleSelect.value && percentageInput.value) {
            const selectedOption = saleSelect.options[saleSelect.selectedIndex];
            const salePrice = parseFloat(selectedOption.getAttribute('data-sale-price'));
            const percentage = parseFloat(percentageInput.value);
            
            if (!isNaN(salePrice) && !isNaN(percentage)) {
                const calculatedAmount = salePrice * (percentage / 100);
                amountInput.value = calculatedAmount.toFixed(2);
            }
        }
    }
    
    // Form validation
    document.getElementById('commissionForm').addEventListener('submit', function(event) {
        const isPercentage = document.getElementById('is_percentage').checked;
        let isValid = true;
        
        if (isPercentage) {
            const percentage = parseFloat(document.getElementById('percentage').value);
            if (isNaN(percentage) || percentage <= 0 || percentage > 100) {
                alert('Please enter a valid percentage between 0 and 100.');
                isValid = false;
            }
        } else {
            const amount = parseFloat(document.getElementById('amount').value);
            if (isNaN(amount) || amount <= 0) {
                alert('Please enter a valid amount greater than zero.');
                isValid = false;
            }
        }
        
        if (!isValid) {
            event.preventDefault();
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