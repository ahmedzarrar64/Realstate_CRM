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

// Get commission details with related data
$sql = "SELECT c.*, 
               u.name as agent_name, u.email as agent_email, u.phone as agent_phone,
               ps.sale_date, ps.sale_price, ps.buyer_name, ps.buyer_contact,
               p.id as property_id, p.address, p.property_type,
               o.id as owner_id, o.name as owner_name
        FROM commissions c
        LEFT JOIN users u ON c.agent_id = u.id
        JOIN property_sales ps ON c.sale_id = ps.id
        JOIN properties p ON ps.property_id = p.id
        JOIN owners o ON p.owner_id = o.id
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

// Calculate commission percentage of sale price
$commission_percentage_of_sale = ($commission['sale_price'] > 0) ? 
    ($commission['amount'] / $commission['sale_price']) * 100 : 0;
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo $base_path; ?>index.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="index.php">Commissions</a></li>
        <li class="breadcrumb-item active">View Commission</li>
    </ol>
</nav>

<div class="row mb-4">
    <div class="col-md-8">
        <h2>
            <i class="fas fa-money-bill-wave"></i> 
            Commission Details
            <span class="badge <?php echo $commission['status'] === 'Paid' ? 'bg-success' : 'bg-warning text-dark'; ?>">
                <?php echo $commission['status']; ?>
            </span>
        </h2>
    </div>
    <div class="col-md-4 text-end">
        <a href="edit.php?id=<?php echo $commission_id; ?>" class="btn btn-primary">
            <i class="fas fa-edit"></i> Edit Commission
        </a>
        <?php if ($commission['status'] === 'Unpaid'): ?>
        <a href="mark_paid.php?id=<?php echo $commission_id; ?>" class="btn btn-success">
            <i class="fas fa-check-circle"></i> Mark as Paid
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="row">
    <!-- Commission Details -->
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Commission Information</h5>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6>Commission Details</h6>
                        <p>
                            <strong>Amount:</strong> $<?php echo number_format($commission['amount'], 2); ?>
                        </p>
                        <?php if ($commission['is_percentage']): ?>
                        <p>
                            <strong>Percentage:</strong> <?php echo number_format($commission['percentage'], 2); ?>%
                        </p>
                        <?php else: ?>
                        <p>
                            <strong>Percentage of Sale:</strong> <?php echo number_format($commission_percentage_of_sale, 2); ?>%
                            <span class="text-muted">(calculated)</span>
                        </p>
                        <?php endif; ?>
                        <p>
                            <strong>Status:</strong> 
                            <span class="badge <?php echo $commission['status'] === 'Paid' ? 'bg-success' : 'bg-warning text-dark'; ?>">
                                <?php echo $commission['status']; ?>
                            </span>
                        </p>
                        <p>
                            <strong>Created:</strong> <?php echo formatDateTime($commission['created_at']); ?>
                        </p>
                        <?php if ($commission['updated_at']): ?>
                        <p>
                            <strong>Last Updated:</strong> <?php echo formatDateTime($commission['updated_at']); ?>
                        </p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <h6>Agent Information</h6>
                        <?php if (!empty($commission['agent_name'])): ?>
                        <p>
                            <strong>Name:</strong> <?php echo htmlspecialchars($commission['agent_name']); ?>
                        </p>
                        <?php if (!empty($commission['agent_email'])): ?>
                        <p>
                            <strong>Email:</strong> 
                            <a href="mailto:<?php echo htmlspecialchars($commission['agent_email']); ?>">
                                <?php echo htmlspecialchars($commission['agent_email']); ?>
                            </a>
                        </p>
                        <?php endif; ?>
                        <?php if (!empty($commission['agent_phone'])): ?>
                        <p>
                            <strong>Phone:</strong> 
                            <a href="tel:<?php echo htmlspecialchars($commission['agent_phone']); ?>">
                                <?php echo htmlspecialchars($commission['agent_phone']); ?>
                            </a>
                        </p>
                        <?php endif; ?>
                        <?php else: ?>
                        <p class="text-muted">No agent assigned to this commission.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (!empty($commission['notes'])): ?>
                <div class="mb-3">
                    <h6>Notes</h6>
                    <p class="text-muted"><?php echo nl2br(htmlspecialchars($commission['notes'])); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Sale Details -->
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Related Sale</h5>
            </div>
            <div class="card-body">
                <h6>Property Information</h6>
                <p>
                    <strong>Address:</strong> 
                    <a href="../properties/view.php?id=<?php echo $commission['property_id']; ?>">
                        <?php echo htmlspecialchars($commission['address']); ?>
                    </a>
                </p>
                <p>
                    <strong>Type:</strong> <?php echo htmlspecialchars($commission['property_type']); ?>
                </p>
                <p>
                    <strong>Owner:</strong> 
                    <a href="../owners/view.php?id=<?php echo $commission['owner_id']; ?>">
                        <?php echo htmlspecialchars($commission['owner_name']); ?>
                    </a>
                </p>
                
                <hr>
                
                <h6>Sale Information</h6>
                <p>
                    <strong>Sale Price:</strong> $<?php echo number_format($commission['sale_price'], 2); ?>
                </p>
                <p>
                    <strong>Sale Date:</strong> <?php echo formatDate($commission['sale_date']); ?>
                </p>
                <p>
                    <strong>Buyer:</strong> <?php echo htmlspecialchars($commission['buyer_name']); ?>
                </p>
                <p>
                    <strong>Buyer Contact:</strong> <?php echo htmlspecialchars($commission['buyer_contact']); ?>
                </p>
                
                <div class="d-grid gap-2 mt-3">
                    <a href="../property_sales/view.php?id=<?php echo $commission['sale_id']; ?>" class="btn btn-outline-primary">
                        <i class="fas fa-eye"></i> View Complete Sale Details
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once $base_path . 'includes/footer.php'; ?>