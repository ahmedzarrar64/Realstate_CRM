<?php
// Adjust path for includes
$base_path = '../../';
require_once $base_path . 'includes/header.php';
require_once $base_path . 'includes/functions.php';

// Get all property sales with property and owner details
$sql = "SELECT ps.*, p.address, p.property_type, o.name as owner_name 
        FROM property_sales ps
        JOIN properties p ON ps.property_id = p.id
        JOIN owners o ON p.owner_id = o.id
        ORDER BY ps.sale_date DESC";
$result = executeQuery($sql);

// Initialize array to store sales
$sales = [];
while ($row = $result->fetch_assoc()) {
    $sales[] = $row;
}
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2><i class="fas fa-handshake"></i> Property Sales</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Sale
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white">
        <div class="row">
            <div class="col-md-6">
                <h5 class="mb-0">All Property Sales</h5>
            </div>
            <div class="col-md-6">
                <div class="input-group">
                    <input type="text" id="searchInput" class="form-control" placeholder="Search sales...">
                    <button class="btn btn-outline-secondary" type="button" id="exportBtn">
                        <i class="fas fa-file-export"></i> Export CSV
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <?php if (count($sales) > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover" id="salesTable">
                <thead>
                    <tr>
                        <th>Property</th>
                        <th>Owner</th>
                        <th>Buyer</th>
                        <th>Sale Price</th>
                        <th>Sale Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sales as $sale): ?>
                    <tr>
                        <td>
                            <a href="../properties/view.php?id=<?php echo $sale['property_id']; ?>">
                                <?php echo htmlspecialchars($sale['address']); ?>
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($sale['property_type']); ?></span>
                            </a>
                        </td>
                        <td>
                            <a href="../owners/view.php?id=<?php echo $sale['owner_id']; ?>">
                                <?php echo htmlspecialchars($sale['owner_name']); ?>
                            </a>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($sale['buyer_name']); ?>
                            <div class="small text-muted"><?php echo htmlspecialchars($sale['buyer_contact']); ?></div>
                        </td>
                        <td>$<?php echo number_format($sale['sale_price'], 2); ?></td>
                        <td><?php echo formatDate($sale['sale_date']); ?></td>
                        <td>
                            <a href="view.php?id=<?php echo $sale['id']; ?>" class="btn btn-sm btn-info text-white" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="edit.php?id=<?php echo $sale['id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="#" class="btn btn-sm btn-danger delete-confirm" 
                               data-id="<?php echo $sale['id']; ?>" 
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
            <i class="fas fa-info-circle"></i> No property sales found. 
            <a href="add.php" class="alert-link">Add your first property sale</a>.
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
                Are you sure you want to delete this property sale record? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmDelete" class="btn btn-danger">Delete</a>
            </div>
        </div>
    </div>
</div>

<script>
    // Search functionality
    document.getElementById('searchInput').addEventListener('keyup', function() {
        const searchValue = this.value.toLowerCase();
        const tableRows = document.querySelectorAll('#salesTable tbody tr');
        
        tableRows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchValue) ? '' : 'none';
        });
    });
    
    // Export to CSV
    document.getElementById('exportBtn').addEventListener('click', function() {
        const table = document.getElementById('salesTable');
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
        link.setAttribute('download', 'property_sales_' + new Date().toISOString().slice(0,10) + '.csv');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
    
    // Delete confirmation
    const deleteButtons = document.querySelectorAll('.delete-confirm');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const saleId = this.getAttribute('data-id');
            document.getElementById('confirmDelete').href = 'delete.php?id=' + saleId;
        });
    });
</script>

<?php require_once $base_path . 'includes/footer.php'; ?>