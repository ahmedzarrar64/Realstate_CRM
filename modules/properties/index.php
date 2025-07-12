<?php
// Adjust path for includes
$base_path = '../../';
require_once $base_path . 'includes/header.php';
require_once $base_path . 'includes/functions.php';

// Get filter parameter
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';

// Get all properties with optional filter
$properties = getAllProperties($filter);

// Handle messages
$message = '';
if (isset($_GET['success'])) {
    $message = showSuccess("Property successfully " . $_GET['success']);
}
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2><i class="fas fa-building"></i> Properties</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-plus-circle"></i> Add New Property
        </a>
        <button id="exportCSV" class="btn btn-success">
            <i class="fas fa-file-export"></i> Export to CSV
        </button>
    </div>
</div>

<?php if ($message): ?>
    <?php echo $message; ?>
<?php endif; ?>

<div class="card">
    <div class="card-header bg-white">
        <div class="row">
            <div class="col-md-6">
                <input type="text" id="tableSearch" class="form-control" placeholder="Search properties...">
            </div>
            <div class="col-md-6">
                <div class="d-flex justify-content-end">
                    <select id="statusFilter" class="form-select">
                        <option value="all" <?php echo ($filter === '') ? 'selected' : ''; ?>>All Statuses</option>
                        <option value="available" <?php echo ($filter === 'Available') ? 'selected' : ''; ?>>Available</option>
                        <option value="under negotiation" <?php echo ($filter === 'Under Negotiation') ? 'selected' : ''; ?>>Under Negotiation</option>
                        <option value="sold" <?php echo ($filter === 'Sold') ? 'selected' : ''; ?>>Sold</option>
                    </select>
                    <a href="index.php" class="btn btn-outline-secondary ms-2" id="resetFilters">
                        <i class="fas fa-sync-alt"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="dataTable">
                <thead>
                    <tr>
                        <th>Owner</th>
                        <th>Address</th>
                        <th>Type</th>
                        <th>Area (sqm)</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($properties) > 0): ?>
                        <?php foreach ($properties as $property): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($property['owner_name']); ?></td>
                                <td><?php echo htmlspecialchars($property['address']); ?></td>
                                <td><?php echo htmlspecialchars($property['property_type']); ?></td>
                                <td><?php echo htmlspecialchars($property['area']); ?></td>
                                <td>$<?php echo number_format($property['price'], 2); ?></td>
                                <td class="status-cell">
                                    <?php 
                                    $status_class = '';
                                    switch ($property['status']) {
                                        case 'Available':
                                            $status_class = 'bg-success';
                                            break;
                                        case 'Under Negotiation':
                                            $status_class = 'bg-info';
                                            break;
                                        case 'Sold':
                                            $status_class = 'bg-danger';
                                            break;
                                    }
                                    ?>
                                    <span class="badge <?php echo $status_class; ?>">
                                        <?php echo $property['status']; ?>
                                    </span>
                                </td>
                                <td class="actions-cell">
                                    <a href="view.php?id=<?php echo $property['id']; ?>" class="btn btn-sm btn-info text-white" data-bs-toggle="tooltip" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit.php?id=<?php echo $property['id']; ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Edit Property">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="delete.php?id=<?php echo $property['id']; ?>" class="btn btn-sm btn-danger delete-confirm" data-bs-toggle="tooltip" title="Delete Property">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">No properties found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Handle status filter change
    document.getElementById('statusFilter').addEventListener('change', function() {
        const status = this.value;
        if (status === 'all') {
            window.location.href = 'index.php';
        } else {
            window.location.href = 'index.php?filter=' + encodeURIComponent(status.charAt(0).toUpperCase() + status.slice(1));
        }
    });
</script>

<?php require_once $base_path . 'includes/footer.php'; ?>