<?php
// Adjust path for includes
$base_path = '../../';
require_once $base_path . 'includes/header.php';
require_once $base_path . 'includes/functions.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "Client ID is required.";
    header("Location: index.php");
    exit();
}

$client_id = (int)$_GET['id'];

// Get client details
$sql = "SELECT * FROM clients WHERE id = $client_id";
$result = executeQuery($sql);

if ($result->num_rows === 0) {
    $_SESSION['error_message'] = "Client not found.";
    header("Location: index.php");
    exit();
}

$client = $result->fetch_assoc();

// Get client interests if any
$interests = [];
$sql = "SELECT ci.*, p.address, p.property_type, p.price 
        FROM client_interests ci 
        JOIN properties p ON ci.property_id = p.id 
        WHERE ci.client_id = $client_id 
        ORDER BY ci.date_added DESC";
$result = executeQuery($sql);

while ($row = $result->fetch_assoc()) {
    $interests[] = $row;
}

// Get client documents if any
$documents = [];
$sql = "SELECT * FROM documents WHERE client_id = $client_id ORDER BY upload_date DESC";
$result = executeQuery($sql);

while ($row = $result->fetch_assoc()) {
    $documents[] = $row;
}

// Get client activity logs
$activities = [];
$sql = "SELECT * FROM activity_logs 
        WHERE related_to = 'client' AND related_id = $client_id 
        ORDER BY activity_date DESC 
        LIMIT 10";
$result = executeQuery($sql);

while ($row = $result->fetch_assoc()) {
    $activities[] = $row;
}
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h2><i class="fas fa-user"></i> Client Details</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $base_path; ?>index.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="index.php">Clients</a></li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($client['name']); ?></li>
            </ol>
        </nav>
    </div>
    <div class="col-md-4 text-end">
        <a href="edit.php?id=<?php echo $client_id; ?>" class="btn btn-primary">
            <i class="fas fa-edit"></i> Edit Client
        </a>
        <a href="index.php" class="btn btn-secondary ms-2">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>
</div>

<?php
// Display success message if any
if (isset($_SESSION['success_message'])) {
    echo showSuccess($_SESSION['success_message']);
    unset($_SESSION['success_message']);
}

// Display error message if any
if (isset($_SESSION['error_message'])) {
    echo showError($_SESSION['error_message']);
    unset($_SESSION['error_message']);
}
?>

<div class="row">
    <div class="col-md-8">
        <!-- Client Information Card -->
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle"></i> Client Information
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
                    echo "<span class='badge $badge_class ms-2'>" . htmlspecialchars($client['client_type']) . "</span>";
                    ?>
                </h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3 fw-bold">Full Name:</div>
                    <div class="col-md-9"><?php echo htmlspecialchars($client['name']); ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-3 fw-bold">Phone:</div>
                    <div class="col-md-9">
                        <a href="tel:<?php echo htmlspecialchars($client['phone']); ?>">
                            <?php echo htmlspecialchars($client['phone']); ?>
                        </a>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-3 fw-bold">Email:</div>
                    <div class="col-md-9">
                        <a href="mailto:<?php echo htmlspecialchars($client['email']); ?>">
                            <?php echo htmlspecialchars($client['email']); ?>
                        </a>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-3 fw-bold">Address:</div>
                    <div class="col-md-9"><?php echo htmlspecialchars($client['address']); ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-3 fw-bold">Preferred Contact:</div>
                    <div class="col-md-9"><?php echo htmlspecialchars($client['preferred_contact']); ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-3 fw-bold">Notes:</div>
                    <div class="col-md-9"><?php echo nl2br(htmlspecialchars($client['notes'])); ?></div>
                </div>
            </div>
        </div>

        <!-- Property Interests -->
        <div class="card mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-heart"></i> Property Interests</h5>
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addInterestModal">
                    <i class="fas fa-plus"></i> Add Interest
                </button>
            </div>
            <div class="card-body">
                <?php if (count($interests) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Property</th>
                                    <th>Type</th>
                                    <th>Price</th>
                                    <th>Interest Level</th>
                                    <th>Date Added</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($interests as $interest): ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo $base_path; ?>modules/properties/view.php?id=<?php echo $interest['property_id']; ?>">
                                                <?php echo htmlspecialchars($interest['title']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($interest['property_type']); ?></td>
                                        <td><?php echo htmlspecialchars($interest['price']); ?></td>
                                        <td>
                                            <?php 
                                            $interest_level = $interest['interest_level'];
                                            $level_badge = '';
                                            switch ($interest_level) {
                                                case 'High':
                                                    $level_badge = 'bg-danger';
                                                    break;
                                                case 'Medium':
                                                    $level_badge = 'bg-warning';
                                                    break;
                                                case 'Low':
                                                    $level_badge = 'bg-info';
                                                    break;
                                            }
                                            echo "<span class='badge $level_badge'>" . htmlspecialchars($interest_level) . "</span>";
                                            ?>
                                        </td>
                                        <td><?php echo formatDate($interest['date_added']); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-danger delete-interest" data-id="<?php echo $interest['id']; ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No property interests recorded for this client.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Documents -->
        <div class="card mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-file-alt"></i> Documents</h5>
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#uploadDocumentModal">
                    <i class="fas fa-upload"></i> Upload Document
                </button>
            </div>
            <div class="card-body">
                <?php if (count($documents) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Document Name</th>
                                    <th>Type</th>
                                    <th>Upload Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($documents as $document): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($document['document_name']); ?></td>
                                        <td><?php echo htmlspecialchars($document['document_type']); ?></td>
                                        <td><?php echo formatDate($document['upload_date']); ?></td>
                                        <td>
                                            <a href="<?php echo $base_path; ?>uploads/documents/<?php echo $document['file_path']; ?>" class="btn btn-sm btn-info text-white" target="_blank">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            <button class="btn btn-sm btn-danger delete-document" data-id="<?php echo $document['id']; ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No documents uploaded for this client.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Activity Log -->
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-history"></i> Recent Activity</h5>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php if (count($activities) > 0): ?>
                        <?php foreach ($activities as $activity): ?>
                            <li class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($activity['activity_type']); ?></h6>
                                    <small><?php echo formatDateTime($activity['activity_date']); ?></small>
                                </div>
                                <p class="mb-1"><?php echo htmlspecialchars($activity['description']); ?></p>
                                <small>By: <?php echo htmlspecialchars($activity['user']); ?></small>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="list-group-item text-center text-muted">No recent activity</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-bolt"></i> Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addTaskModal">
                        <i class="fas fa-tasks"></i> Add Task
                    </button>
                    <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#addContactLogModal">
                        <i class="fas fa-phone"></i> Log Contact
                    </button>
                    <a href="<?php echo $base_path; ?>modules/properties/index.php?client_id=<?php echo $client_id; ?>" class="btn btn-outline-info">
                        <i class="fas fa-home"></i> Show Matching Properties
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Interest Modal -->
<div class="modal fade" id="addInterestModal" tabindex="-1" aria-labelledby="addInterestModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addInterestModalLabel">Add Property Interest</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addInterestForm" action="add_interest.php" method="post">
                <div class="modal-body">
                    <input type="hidden" name="client_id" value="<?php echo $client_id; ?>">
                    <div class="mb-3">
                        <label for="property_id" class="form-label">Select Property</label>
                        <select class="form-select" id="property_id" name="property_id" required>
                            <option value="">-- Select Property --</option>
                            <?php 
                            $properties_sql = "SELECT id, title FROM properties WHERE status = 'Active' ORDER BY title ASC";
                            $properties_result = executeQuery($properties_sql);
                            while ($property = $properties_result->fetch_assoc()) {
                                echo "<option value='{$property['id']}'>" . htmlspecialchars($property['title']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="interest_level" class="form-label">Interest Level</label>
                        <select class="form-select" id="interest_level" name="interest_level" required>
                            <option value="High">High</option>
                            <option value="Medium" selected>Medium</option>
                            <option value="Low">Low</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Interest</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Upload Document Modal -->
<div class="modal fade" id="uploadDocumentModal" tabindex="-1" aria-labelledby="uploadDocumentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadDocumentModalLabel">Upload Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="uploadDocumentForm" action="upload_document.php" method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="client_id" value="<?php echo $client_id; ?>">
                    <div class="mb-3">
                        <label for="document_name" class="form-label">Document Name</label>
                        <input type="text" class="form-control" id="document_name" name="document_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="document_type" class="form-label">Document Type</label>
                        <select class="form-select" id="document_type" name="document_type" required>
                            <option value="ID/Passport">ID/Passport</option>
                            <option value="Contract">Contract</option>
                            <option value="Application">Application</option>
                            <option value="Financial Statement">Financial Statement</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="document_file" class="form-label">File</label>
                        <input type="file" class="form-control" id="document_file" name="document_file" required>
                        <div class="form-text">Allowed file types: PDF, DOC, DOCX, JPG, PNG (Max 5MB)</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Task Modal -->
<div class="modal fade" id="addTaskModal" tabindex="-1" aria-labelledby="addTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addTaskModalLabel">Add Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addTaskForm" action="<?php echo $base_path; ?>modules/tasks/add.php" method="post">
                <div class="modal-body">
                    <input type="hidden" name="client_id" value="<?php echo $client_id; ?>">
                    <input type="hidden" name="redirect_url" value="<?php echo $base_path; ?>modules/clients/view.php?id=<?php echo $client_id; ?>">
                    <div class="mb-3">
                        <label for="task_description" class="form-label">Task Description</label>
                        <input type="text" class="form-control" id="task_description" name="description" required>
                    </div>
                    <div class="mb-3">
                        <label for="task_due_date" class="form-label">Due Date</label>
                        <input type="date" class="form-control" id="task_due_date" name="due_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="task_priority" class="form-label">Priority</label>
                        <select class="form-select" id="task_priority" name="priority" required>
                            <option value="High">High</option>
                            <option value="Medium" selected>Medium</option>
                            <option value="Low">Low</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Task</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Contact Log Modal -->
<div class="modal fade" id="addContactLogModal" tabindex="-1" aria-labelledby="addContactLogModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addContactLogModalLabel">Log Contact</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addContactLogForm" action="<?php echo $base_path; ?>modules/contacts/add.php" method="post">
                <div class="modal-body">
                    <input type="hidden" name="client_id" value="<?php echo $client_id; ?>">
                    <input type="hidden" name="redirect_url" value="<?php echo $base_path; ?>modules/clients/view.php?id=<?php echo $client_id; ?>">
                    <div class="mb-3">
                        <label for="contact_type" class="form-label">Contact Type</label>
                        <select class="form-select" id="contact_type" name="contact_type" required>
                            <option value="Phone">Phone</option>
                            <option value="Email">Email</option>
                            <option value="WhatsApp">WhatsApp</option>
                            <option value="In-Person">In-Person</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="contact_description" class="form-label">Description</label>
                        <textarea class="form-control" id="contact_description" name="description" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Contact Log</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Delete interest confirmation
    document.querySelectorAll('.delete-interest').forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this interest?')) {
                window.location.href = 'delete_interest.php?id=' + this.getAttribute('data-id') + '&client_id=<?php echo $client_id; ?>';
            }
        });
    });

    // Delete document confirmation
    document.querySelectorAll('.delete-document').forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this document?')) {
                window.location.href = 'delete_document.php?id=' + this.getAttribute('data-id') + '&client_id=<?php echo $client_id; ?>';
            }
        });
    });
</script>

<?php require_once $base_path . 'includes/footer.php'; ?>