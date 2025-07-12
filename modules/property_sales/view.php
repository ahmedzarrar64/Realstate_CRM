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

// Get sale details with property and owner information
$sql = "SELECT ps.*, p.address, p.property_type, p.area, p.price as listing_price, 
               o.id as owner_id, o.name as owner_name, o.email as owner_email, o.phone as owner_phone 
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

// Get related commissions
$commission_sql = "SELECT c.*, u.name as agent_name 
                  FROM commissions c
                  LEFT JOIN users u ON c.agent_id = u.id
                  WHERE c.sale_id = ?
                  ORDER BY c.created_at DESC";
$commission_stmt = $conn->prepare($commission_sql);
$commission_stmt->bind_param("i", $sale_id);
$commission_stmt->execute();
$commission_result = $commission_stmt->get_result();
$commissions = [];
while ($row = $commission_result->fetch_assoc()) {
    $commissions[] = $row;
}
$commission_stmt->close();

// Calculate profit
$profit = $sale['sale_price'] - $sale['listing_price'];
$profit_percentage = ($sale['listing_price'] > 0) ? ($profit / $sale['listing_price']) * 100 : 0;

// Calculate total commissions
$total_commission = 0;
foreach ($commissions as $commission) {
    $total_commission += $commission['amount'];
}

// Calculate net profit after commissions
$net_profit = $profit - $total_commission;
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo $base_path; ?>index.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="index.php">Property Sales</a></li>
        <li class="breadcrumb-item active">View Sale</li>
    </ol>
</nav>

<div class="row mb-4">
    <div class="col-md-8">
        <h2>
            <i class="fas fa-handshake"></i> 
            Sale: <?php echo htmlspecialchars($sale['address']); ?>
        </h2>
    </div>
    <div class="col-md-4 text-end">
        <a href="edit.php?id=<?php echo $sale_id; ?>" class="btn btn-primary">
            <i class="fas fa-edit"></i> Edit Sale
        </a>
        <a href="../commissions/add.php?sale_id=<?php echo $sale_id; ?>" class="btn btn-success">
            <i class="fas fa-plus"></i> Add Commission
        </a>
    </div>
</div>

<div class="row">
    <!-- Sale Details -->
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Sale Details</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6>Property Information</h6>
                        <p>
                            <strong>Address:</strong> 
                            <a href="../properties/view.php?id=<?php echo $sale['property_id']; ?>">
                                <?php echo htmlspecialchars($sale['address']); ?>
                            </a>
                        </p>
                        <p>
                            <strong>Type:</strong> <?php echo htmlspecialchars($sale['property_type']); ?>
                        </p>
                        <p>
                            <strong>Area:</strong> <?php echo htmlspecialchars($sale['area']); ?> sqm
                        </p>
                        <p>
                            <strong>Owner:</strong> 
                            <a href="../owners/view.php?id=<?php echo $sale['owner_id']; ?>">
                                <?php echo htmlspecialchars($sale['owner_name']); ?>
                            </a>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6>Sale Information</h6>
                        <p>
                            <strong>Buyer:</strong> <?php echo htmlspecialchars($sale['buyer_name']); ?>
                        </p>
                        <p>
                            <strong>Contact:</strong> <?php echo htmlspecialchars($sale['buyer_contact']); ?>
                        </p>
                        <p>
                            <strong>Sale Date:</strong> <?php echo formatDate($sale['sale_date']); ?>
                        </p>
                        <p>
                            <strong>Recorded:</strong> <?php echo formatDateTime($sale['created_at']); ?>
                        </p>
                    </div>
                </div>
                
                <?php if (!empty($sale['notes'])): ?>
                <div class="mb-3">
                    <h6>Notes</h6>
                    <p class="text-muted"><?php echo nl2br(htmlspecialchars($sale['notes'])); ?></p>
                </div>
                <?php endif; ?>
                
                <hr>
                
                <div class="row">
                    <div class="col-md-3">
                        <div class="card bg-light mb-3">
                            <div class="card-body text-center">
                                <h6 class="card-title">Listing Price</h6>
                                <h4 class="text-primary">$<?php echo number_format($sale['listing_price'], 2); ?></h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light mb-3">
                            <div class="card-body text-center">
                                <h6 class="card-title">Sale Price</h6>
                                <h4 class="text-success">$<?php echo number_format($sale['sale_price'], 2); ?></h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light mb-3">
                            <div class="card-body text-center">
                                <h6 class="card-title">Gross Profit</h6>
                                <h4 class="<?php echo $profit >= 0 ? 'text-success' : 'text-danger'; ?>">
                                    $<?php echo number_format($profit, 2); ?>
                                    <small>(<?php echo number_format($profit_percentage, 1); ?>%)</small>
                                </h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light mb-3">
                            <div class="card-body text-center">
                                <h6 class="card-title">Net Profit</h6>
                                <h4 class="<?php echo $net_profit >= 0 ? 'text-success' : 'text-danger'; ?>">
                                    $<?php echo number_format($net_profit, 2); ?>
                                </h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Commissions -->
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Commissions</h5>
                <span class="badge bg-info">
                    Total: $<?php echo number_format($total_commission, 2); ?>
                </span>
            </div>
            <div class="card-body">
                <?php if (count($commissions) > 0): ?>
                    <div class="list-group">
                        <?php foreach ($commissions as $commission): ?>
                            <div class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">
                                        <?php echo htmlspecialchars($commission['agent_name'] ?? 'No Agent'); ?>
                                    </h6>
                                    <span class="badge <?php echo $commission['status'] === 'Paid' ? 'bg-success' : 'bg-warning text-dark'; ?>">
                                        <?php echo $commission['status']; ?>
                                    </span>
                                </div>
                                <p class="mb-1">
                                    <strong>Amount:</strong> $<?php echo number_format($commission['amount'], 2); ?>
                                    <?php if ($commission['is_percentage']): ?>
                                        (<?php echo number_format($commission['percentage'], 2); ?>%)
                                    <?php endif; ?>
                                </p>
                                <small class="text-muted">
                                    <?php echo formatDateTime($commission['created_at']); ?>
                                    <a href="../commissions/edit.php?id=<?php echo $commission['id']; ?>" class="ms-2">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No commissions recorded for this sale.
                    </div>
                    <div class="d-grid">
                        <a href="../commissions/add.php?sale_id=<?php echo $sale_id; ?>" class="btn btn-outline-primary">
                            <i class="fas fa-plus"></i> Add Commission
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once $base_path . 'includes/footer.php'; ?>