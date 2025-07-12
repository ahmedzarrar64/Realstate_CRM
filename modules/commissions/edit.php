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

// Get commission details
$sql = "SELECT c.*, 
               ps.sale_price, ps.buyer_name,
               p.address
        FROM commissions c
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

// Initialize variables with existing data
$sale_id = $commission['sale_id'];
$agent_id = $commission['agent_id'];
$amount = $commission['amount'];
$percentage = $commission['percentage'];
$is_percentage = $commission['is_percentage'];
$status = $commission['status'];
$notes = $commission['notes'];

// Get all agents (users)
$agents_sql = "SELECT id, name FROM users ORDER BY name ASC";
$agents_result = executeQuery($agents_sql);
$agents = [];
while ($row = $agents_result->fetch_assoc()) {
    $agents[] = $row;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize inputs
    $agent_id = isset($_POST['agent_id']) ? intval($_POST['agent_id']) : 0;
    $is_percentage = isset($_POST['is_percentage']) ? 1 : 0;
    $status = isset($_POST['status']) ? trim($_POST['status']) : 'Unpaid';
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
    
    // Handle percentage or flat amount
    if ($is_percentage) {
        $percentage = isset($_POST['percentage']) ? floatval($_POST['percentage']) : 0;
        
        // Get sale price to calculate amount
        $sale_price = $commission['sale_price'];
        $amount = $sale_price * ($percentage / 100);
    } else {
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        $percentage = 0;
    }
    
    // Validation
    if ($amount <= 0) {
        $error = "Commission amount must be greater than zero.";
    } elseif ($is_percentage && ($percentage <= 0 || $percentage > 100)) {
        $error = "Percentage must be between 0 and 100.";
    } else {
        // Update commission record
        $sql = "UPDATE commissions 
                SET agent_id = ?, amount = ?, percentage = ?, is_percentage = ?, status = ?, notes = ?, updated_at = NOW() 
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iddssi", $agent_id, $amount, $percentage, $is_percentage, $status, $notes, $commission_id);
        
        if ($stmt->execute()) {
            $success = "Commission updated successfully!";
            
            // Refresh commission data
            $sql = "SELECT c.*, 
                   ps.sale_price, ps.buyer_name,
                   p.address
            FROM commissions c
            JOIN property_sales ps ON c.sale_id = ps.id
            JOIN properties p ON ps.property_id = p.id
            WHERE c.id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $commission_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $commission = $result->fetch_assoc();
            
            // Update variables
            $amount = $commission['amount'];
            $percentage = $commission['percentage'];
            $is_percentage = $commission['is_percentage'];
            $status = $commission['status'];
            $notes = $commission['notes'];
        } else {
            $error = "Error updating commission: " . $conn->error;
        }
        $stmt->close();
    }
}
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo $base_path; ?>index.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="index.php">Commissions</a></li>
        <li class="breadcrumb-item"><a href="view.php?id=<?php echo $commission_id; ?>">View Commission</a></li>
        <li class="breadcrumb-item active">Edit Commission</li>
    </ol>
</nav>

<div class="row mb-4">
    <div class="col">
        <h2><i class="fas fa-edit"></i> Edit Commission</h2>
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
        <h5 class="mb-0">Commission Details</h5>
    </div>
    <div class="card-body">
        <form method="post" action="" id="commissionForm">
            <div class="mb-3">
                <label class="form-label">Property Sale</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($commission['address']); ?> - $<?php echo number_format($commission['sale_price'], 2); ?> - Buyer: <?php echo htmlspecialchars($commission['buyer_name']); ?>" readonly>
                <div class="form-text">Sale cannot be changed. To assign commission to a different sale, create a new commission.</div>
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
                <a href="view.php?id=<?php echo $commission_id; ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Commission</button>
            </div>
        </form>
    </div>
</div>

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
    
    function calculateAmount() {
        const isPercentage = document.getElementById('is_percentage').checked;
        if (!isPercentage) return;
        
        const percentageInput = document.getElementById('percentage');
        const amountInput = document.getElementById('amount');
        const salePrice = <?php echo $commission['sale_price']; ?>;
        
        if (percentageInput.value) {
            const percentage = parseFloat(percentageInput.value);
            
            if (!isNaN(percentage)) {
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