<?php
// Adjust path for includes
$base_path = '../../';
require_once $base_path . 'includes/header.php';
require_once $base_path . 'includes/functions.php';

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$agent_filter = isset($_GET['agent']) ? intval($_GET['agent']) : 0;

// Build query based on filters
$where_clauses = [];
$params = [];
$param_types = '';

if ($status_filter !== 'all') {
    $where_clauses[] = 'c.status = ?';
    $params[] = $status_filter;
    $param_types .= 's';
}

if ($agent_filter > 0) {
    $where_clauses[] = 'c.agent_id = ?';
    $params[] = $agent_filter;
    $param_types .= 'i';
}

$where_sql = '';
if (!empty($where_clauses)) {
    $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
}

// Get all commissions with related data
$sql = "SELECT c.*, 
               u.name as agent_name, 
               ps.sale_date, ps.sale_price, 
               p.address, p.property_type,
               o.name as owner_name
        FROM commissions c
        LEFT JOIN users u ON c.agent_id = u.id
        JOIN property_sales ps ON c.sale_id = ps.id
        JOIN properties p ON ps.property_id = p.id
        JOIN owners o ON p.owner_id = o.id
        $where_sql
        ORDER BY c.created_at DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// Initialize array to store commissions
$commissions = [];
while ($row = $result->fetch_assoc()) {
    $commissions[] = $row;
}
$stmt->close();

// Get all agents for filter dropdown
$agents_sql = "SELECT id, name FROM users ORDER BY name ASC";
$agents_result = executeQuery($agents_sql);
$agents = [];
while ($row = $agents_result->fetch_assoc()) {
    $agents[] = $row;
}

// Calculate totals
$total_commissions = 0;
$total_paid = 0;
$total_unpaid = 0;

foreach ($commissions as $commission) {
    $total_commissions += $commission['amount'];
    if ($commission['status'] === 'Paid') {
        $total_paid += $commission['amount'];
    } else {
        $total_unpaid += $commission['amount'];
    }
}
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2><i class="fas fa-money-bill-wave"></i> Commissions</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Commission
        </a>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card bg-light">
            <div class="card-body text-center">
                <h5 class="card-title">Total Commissions</h5>
                <h3 class="text-primary">$<?php echo number_format($total_commissions, 2); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-light">
            <div class="card-body text-center">
                <h5 class="card-title">Paid Commissions</h5>
                <h3 class="text-success">$<?php echo number_format($total_paid, 2); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-light">
            <div class="card-body text-center">
                <h5 class="card-title">Unpaid Commissions</h5>
                <h3 class="text-danger">$<?php echo number_format($total_unpaid, 2); ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white">
        <div class="row">
            <div class="col-md-6">
                <h5 class="mb-0">All Commissions</h5>
            </div>
            <div class="col-md-6">
                <div class="d-flex justify-content-end">
                    <!-- Filters -->
                    <form method="get" action="" class="d-flex">
                        <select name="status" class="form-select form-select-sm me-2" onchange="this.form.submit()">
                            <option value="all" <?php echo ($status_filter === 'all') ? 'selected' : ''; ?>>All Statuses</option>
                            <option value="Paid" <?php echo ($status_filter === 'Paid') ? 'selected' : ''; ?>>Paid</option>
                            <option value="Unpaid" <?php echo ($status_filter === 'Unpaid') ? 'selected' : ''; ?>>Unpaid</option>
                        </select>
                        <select name="agent" class="form-select form-select-sm me-2" onchange="this.form.submit()">
                            <option value="0" <?php echo ($agent_filter === 0) ? 'selected' : ''; ?>>All Agents</option>
                            <?php foreach ($agents as $agent): ?>
                                <option value="<?php echo $agent['id']; ?>" <?php echo ($agent_filter === $agent['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($agent['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" id="exportBtn" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-file-export"></i> Export
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <?php if (count($commissions) > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover" id="commissionsTable">
                <thead>
                    <tr>
                        <th>Agent</th>
                        <th>Property</th>
                        <th>Sale Date</th>
                        <th>Sale Price</th>
                        <th>Commission</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($commissions as $commission): ?>
                    <tr>
                        <td>
                            <?php if (!empty($commission['agent_name'])): ?>
                                <?php echo htmlspecialchars($commission['agent_name']); ?>
                            <?php else: ?>
                                <span class="text-muted">No Agent</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="../property_sales/view.php?id=<?php echo $commission['sale_id']; ?>">
                                <?php echo htmlspecialchars($commission['address']); ?>
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($commission['property_type']); ?></span>
                            </a>
                            <div class="small text-muted">Owner: <?php echo htmlspecialchars($commission['owner_name']); ?></div>
                        </td>
                        <td><?php echo formatDate($commission['sale_date']); ?></td>
                        <td>$<?php echo number_format($commission['sale_price'], 2); ?></td>
                        <td>
                            $<?php echo number_format($commission['amount'], 2); ?>
                            <?php if ($commission['is_percentage']): ?>
                                <span class="text-muted">(<?php echo number_format($commission['percentage'], 2); ?>%)</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge <?php echo $commission['status'] === 'Paid' ? 'bg-success' : 'bg-warning text-dark'; ?>">
                                <?php echo $commission['status']; ?>
                            </span>
                        </td>
                        <td>
                            <a href="view.php?id=<?php echo $commission['id']; ?>" class="btn btn-sm btn-info text-white" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="edit.php?id=<?php echo $commission['id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="#" class="btn btn-sm btn-danger delete-confirm" 
                               data-id="<?php echo $commission['id']; ?>" 
                               data-bs-toggle="modal" 
                               data-bs-target="#deleteModal" 
                               title="Delete">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No commissions found matching your filters. 
            <?php if ($status_filter !== 'all' || $agent_filter > 0): ?>
                <a href="index.php" class="alert-link">Clear filters</a> to see all commissions.
            <?php else: ?>
                <a href="add.php" class="alert-link">Add your first commission</a>.
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this commission record? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmDelete" class="btn btn-danger">Delete</a>
            </div>
        </div>
    </div>
</div>

<script>
    // Export to CSV
    document.getElementById('exportBtn').addEventListener('click', function() {
        const table = document.getElementById('commissionsTable');
        let csv = [];
        const rows = table.querySelectorAll('tr');
        
        for (let i = 0; i < rows.length; i++) {
            const row = [], cols = rows[i].querySelectorAll('td, th');
            
            for (let j = 0; j < cols.length - 1; j++) { // Skip the Actions column
                // Get the text content and clean it
                let data = cols[j].innerText.replace(/\n/g, ' ').replace(/"/g, '""');
                row.push('"' + data + '"');
            }
            csv.push(row.join(','));
        }
        
        const csvContent = 'data:text/csv;charset=utf-8,' + csv.join('\n');
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement('a');
        link.setAttribute('href', encodedUri);
        link.setAttribute('download', 'commissions_' + new Date().toISOString().slice(0,10) + '.csv');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
    
    // Delete confirmation
    const deleteButtons = document.querySelectorAll('.delete-confirm');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const commissionId = this.getAttribute('data-id');
            document.getElementById('confirmDelete').href = 'delete.php?id=' + commissionId;
        });
    });
</script>

<?php require_once $base_path . 'includes/footer.php'; ?>