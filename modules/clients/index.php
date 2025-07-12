<?php
// Adjust path for includes
$base_path = '../../';
require_once $base_path . 'includes/header.php';
require_once $base_path . 'includes/functions.php';

// Get all clients
$sql = "SELECT * FROM clients ORDER BY name ASC";
$result = executeQuery($sql);
$clients = [];
while ($row = $result->fetch_assoc()) {
    $clients[] = $row;
}
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h2><i class="fas fa-users"></i> Client Management</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $base_path; ?>index.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Clients</li>
            </ol>
        </nav>
    </div>
    <div class="col-md-4 text-end">
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-plus-circle"></i> Add New Client
        </a>
        <button id="export-btn" class="btn btn-success ms-2">
            <i class="fas fa-file-export"></i> Export CSV
        </button>
    </div>
</div>

<?php
// Display success message if any
if (isset($_SESSION['success_message'])) {
    echo showSuccess($_SESSION['success_message']);
    unset($_SESSION['success_message']);
}
?>

<div class="card">
    <div class="card-header bg-white">
        <div class="row">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" id="searchInput" class="form-control" placeholder="Search clients...">
                </div>
            </div>
            <div class="col-md-6">
                <div class="d-flex justify-content-end">
                    <select id="clientTypeFilter" class="form-select me-2" style="width: auto;">
                        <option value="">All Types</option>
                        <option value="Buyer">Buyers</option>
                        <option value="Tenant">Tenants</option>
                        <option value="Both">Both</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="dataTable" class="table table-hover">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Preferred Contact</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($clients) > 0): ?>
                        <?php foreach ($clients as $client): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($client['name']); ?></td>
                                <td>
                                    <?php 
                                    $badge_class = '';
                                    switch ($client['client_type']) {
                                        case 'Buyer':
                                            $badge_class = 'bg-primary';
                                            break;
                                        case 'Tenant':
                                            $badge_class = 'bg-info';
                                            break;
                                        case 'Both':
                                            $badge_class = 'bg-success';
                                            break;
                                    }
                                    echo "<span class='badge $badge_class'>" . htmlspecialchars($client['client_type']) . "</span>";
                                    ?>
                                </td>
                                <td>
                                    <a href="tel:<?php echo htmlspecialchars($client['phone']); ?>">
                                        <?php echo htmlspecialchars($client['phone']); ?>
                                    </a>
                                </td>
                                <td>
                                    <a href="mailto:<?php echo htmlspecialchars($client['email']); ?>">
                                        <?php echo htmlspecialchars($client['email']); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($client['preferred_contact']); ?></td>
                                <td class="actions-cell">
                                    <a href="view.php?id=<?php echo $client['id']; ?>" class="btn btn-sm btn-info text-white" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit.php?id=<?php echo $client['id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="delete.php?id=<?php echo $client['id']; ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this client?')">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">No clients found. <a href="add.php">Add your first client</a>.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Search functionality
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('keyup', function() {
                const searchValue = this.value.toLowerCase();
                const rows = document.querySelectorAll('#dataTable tbody tr');
                
                rows.forEach(row => {
                    const name = row.cells[0].textContent.toLowerCase();
                    const phone = row.cells[2].textContent.toLowerCase();
                    const email = row.cells[3].textContent.toLowerCase();
                    
                    if (name.includes(searchValue) || phone.includes(searchValue) || email.includes(searchValue)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }
        
        // Client type filter
        const clientTypeFilter = document.getElementById('clientTypeFilter');
        if (clientTypeFilter) {
            clientTypeFilter.addEventListener('change', function() {
                const filterValue = this.value.toLowerCase();
                const rows = document.querySelectorAll('#dataTable tbody tr');
                
                rows.forEach(row => {
                    if (!filterValue) {
                        row.style.display = '';
                        return;
                    }
                    
                    const clientType = row.cells[1].textContent.toLowerCase();
                    if (clientType.includes(filterValue)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }
        
        // Export to CSV functionality
        const exportBtn = document.getElementById('export-btn');
        if (exportBtn) {
            exportBtn.addEventListener('click', function() {
                // Get visible rows
                const visibleRows = [];
                document.querySelectorAll('#dataTable tbody tr').forEach(row => {
                    if (row.style.display !== 'none') {
                        visibleRows.push(row);
                    }
                });
                
                // Create CSV content
                let csv = [];
                let headers = [];
                
                // Get headers (excluding Actions)
                document.querySelectorAll('#dataTable thead th').forEach(th => {
                    if (th.textContent !== 'Actions') {
                        headers.push('"' + th.textContent.trim() + '"');
                    }
                });
                csv.push(headers.join(','));
                
                // Add row data
                visibleRows.forEach(row => {
                    let rowData = [];
                    // Skip the last cell (Actions)
                    for (let i = 0; i < row.cells.length - 1; i++) {
                        rowData.push('"' + row.cells[i].textContent.trim().replace(/"/g, '""') + '"');
                    }
                    csv.push(rowData.join(','));
                });
                
                // Download CSV file
                const csvContent = csv.join('\n');
                const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.setAttribute('href', url);
                link.setAttribute('download', 'clients_export.csv');
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });
        }
    });
</script>

<?php require_once $base_path . 'includes/footer.php'; ?>