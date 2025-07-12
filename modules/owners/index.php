<?php
// Adjust path for includes
$base_path = '../../';
require_once $base_path . 'includes/header.php';
require_once $base_path . 'includes/functions.php';

// Get all owners
$owners = getAllOwners();

// Handle messages
$message = '';
if (isset($_GET['success'])) {
    $message = showSuccess("Owner successfully " . $_GET['success']);
}
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2><i class="fas fa-users"></i> Property Owners</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-plus-circle"></i> Add New Owner
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
            <div class="col-md-8">
                <input type="text" id="tableSearch" class="form-control" placeholder="Search owners...">
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="dataTable">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Address</th>
                        <th>Preferred Contact</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($owners) > 0): ?>
                        <?php foreach ($owners as $owner): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($owner['name']); ?></td>
                                <td><?php echo htmlspecialchars($owner['phone']); ?></td>
                                <td><?php echo htmlspecialchars($owner['email']); ?></td>
                                <td><?php echo htmlspecialchars($owner['address']); ?></td>
                                <td><?php echo htmlspecialchars($owner['preferred_contact']); ?></td>
                                <td class="actions-cell">
                                    <a href="view.php?id=<?php echo $owner['id']; ?>" class="btn btn-sm btn-info text-white" data-bs-toggle="tooltip" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit.php?id=<?php echo $owner['id']; ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Edit Owner">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="delete.php?id=<?php echo $owner['id']; ?>" class="btn btn-sm btn-danger delete-confirm" data-bs-toggle="tooltip" title="Delete Owner">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">No property owners found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once $base_path . 'includes/footer.php'; ?>