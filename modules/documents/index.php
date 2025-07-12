<?php
// Adjust path for includes
$base_path = '../../';
require_once $base_path . 'includes/header.php';
require_once $base_path . 'includes/functions.php';

$error = '';
$success = '';

// Handle document deletion
if (isset($_POST['delete_document']) && isset($_POST['document_id'])) {
    $document_id = intval($_POST['document_id']);
    
    // Get document file path before deleting record
    $get_doc_sql = "SELECT file_path FROM documents WHERE id = ?";
    $get_stmt = $conn->prepare($get_doc_sql);
    $get_stmt->bind_param("i", $document_id);
    $get_stmt->execute();
    $doc_result = $get_stmt->get_result();
    
    if ($doc_result->num_rows > 0) {
        $doc_data = $doc_result->fetch_assoc();
        $file_path = $base_path . $doc_data['file_path'];
        
        // Delete from database
        $delete_sql = "DELETE FROM documents WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $document_id);
        
        if ($delete_stmt->execute()) {
            // Delete file from server if it exists
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            $success = "Document deleted successfully!";
        } else {
            $error = "Error deleting document: " . $conn->error;
        }
        $delete_stmt->close();
    }
    $get_stmt->close();
}

// Handle success message from redirect
if (isset($_GET['success'])) {
    if ($_GET['success'] == 'upload') {
        $success = "Document uploaded successfully!";
    }
}

// Set up filtering
$filter_type = isset($_GET['type']) ? $_GET['type'] : '';
$filter_entity = isset($_GET['entity']) ? $_GET['entity'] : '';
$filter_entity_id = isset($_GET['entity_id']) ? intval($_GET['entity_id']) : 0;

// Build the query based on filters
$where_clauses = [];
$params = [];
$param_types = "";

if (!empty($filter_type)) {
    $where_clauses[] = "d.document_type = ?";
    $params[] = $filter_type;
    $param_types .= "s";
}

if (!empty($filter_entity)) {
    $where_clauses[] = "d.entity_type = ?";
    $params[] = $filter_entity;
    $param_types .= "s";
    
    if ($filter_entity_id > 0) {
        $where_clauses[] = "d.entity_id = ?";
        $params[] = $filter_entity_id;
        $param_types .= "i";
    }
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Get all documents with related entity information
$sql = "SELECT d.*, 
               CASE 
                   WHEN d.entity_type = 'property' THEN p.address
                   WHEN d.entity_type = 'owner' THEN o.name
                   ELSE NULL
               END as entity_name
        FROM documents d
        LEFT JOIN properties p ON d.entity_type = 'property' AND d.entity_id = p.id
        LEFT JOIN owners o ON d.entity_type = 'owner' AND d.entity_id = o.id
        $where_sql
        ORDER BY d.created_at DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$documents = [];

while ($document = $result->fetch_assoc()) {
    $documents[] = $document;
}
$stmt->close();

// Get document types for filter
$types_sql = "SELECT DISTINCT document_type FROM documents ORDER BY document_type";
$types_result = $conn->query($types_sql);
$document_types = [];

while ($type = $types_result->fetch_assoc()) {
    if (!empty($type['document_type'])) {
        $document_types[] = $type['document_type'];
    }
}

// Get entity types for filter
$entity_types = ['property', 'owner'];

// Get properties for filter
if ($filter_entity === 'property' || empty($filter_entity)) {
    $properties_sql = "SELECT id, address FROM properties ORDER BY address";
    $properties_result = $conn->query($properties_sql);
    $properties = [];
    
    while ($property = $properties_result->fetch_assoc()) {
        $properties[] = $property;
    }
}

// Get owners for filter
if ($filter_entity === 'owner' || empty($filter_entity)) {
    $owners_sql = "SELECT id, name FROM owners ORDER BY name";
    $owners_result = $conn->query($owners_sql);
    $owners = [];
    
    while ($owner = $owners_result->fetch_assoc()) {
        $owners[] = $owner;
    }
}
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo $base_path; ?>index.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Documents</li>
    </ol>
</nav>

<div class="row mb-4">
    <div class="col-md-8">
        <h2><i class="fas fa-file-alt"></i> Documents</h2>
    </div>
    <div class="col-md-4 text-end">
        <a href="upload.php" class="btn btn-primary">
            <i class="fas fa-upload"></i> Upload New Document
        </a>
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

<!-- Filter Section -->
<div class="card mb-4">
    <div class="card-header bg-light">
        <h5 class="mb-0"><i class="fas fa-filter"></i> Filter Documents</h5>
    </div>
    <div class="card-body">
        <form method="get" action="" class="row g-3">
            <div class="col-md-4">
                <label for="type" class="form-label">Document Type</label>
                <select class="form-select" id="type" name="type">
                    <option value="">All Types</option>
                    <?php foreach ($document_types as $type): ?>
                    <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $filter_type === $type ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($type); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="entity" class="form-label">Entity Type</label>
                <select class="form-select" id="entity" name="entity">
                    <option value="">All Entities</option>
                    <?php foreach ($entity_types as $type): ?>
                    <option value="<?php echo $type; ?>" <?php echo $filter_entity === $type ? 'selected' : ''; ?>>
                        <?php echo ucfirst($type); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4" id="entityIdContainer">
                <label for="entity_id" class="form-label">Select Entity</label>
                <select class="form-select" id="entity_id" name="entity_id" <?php echo empty($filter_entity) ? 'disabled' : ''; ?>>
                    <option value="">All</option>
                    <?php if ($filter_entity === 'property' && isset($properties)): ?>
                        <?php foreach ($properties as $property): ?>
                        <option value="<?php echo $property['id']; ?>" <?php echo $filter_entity_id === $property['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($property['address']); ?>
                        </option>
                        <?php endforeach; ?>
                    <?php elseif ($filter_entity === 'owner' && isset($owners)): ?>
                        <?php foreach ($owners as $owner): ?>
                        <option value="<?php echo $owner['id']; ?>" <?php echo $filter_entity_id === $owner['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($owner['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Apply Filters
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-undo"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

<?php if (empty($documents)): ?>
<div class="alert alert-info">
    <i class="fas fa-info-circle"></i> No documents found matching your criteria.
</div>
<?php else: ?>

<!-- Documents Table -->
<div class="card">
    <div class="card-header bg-white">
        <div class="row align-items-center">
            <div class="col">
                <h5 class="mb-0">Documents (<?php echo count($documents); ?>)</h5>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead>
                    <tr>
                        <th>Document Name</th>
                        <th>Type</th>
                        <th>Related To</th>
                        <th>Size</th>
                        <th>Uploaded</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($documents as $document): ?>
                    <tr>
                        <td>
                            <i class="<?php echo getFileIconClass($document['file_path']); ?> me-2"></i>
                            <?php echo htmlspecialchars($document['title']); ?>
                        </td>
                        <td><?php echo htmlspecialchars($document['document_type']); ?></td>
                        <td>
                            <?php if (!empty($document['entity_name'])): ?>
                                <?php echo ucfirst($document['entity_type']); ?>: 
                                <?php if ($document['entity_type'] === 'property'): ?>
                                <a href="<?php echo $base_path; ?>modules/properties/view.php?id=<?php echo $document['entity_id']; ?>">
                                    <?php echo htmlspecialchars($document['entity_name']); ?>
                                </a>
                                <?php elseif ($document['entity_type'] === 'owner'): ?>
                                <a href="<?php echo $base_path; ?>modules/owners/view.php?id=<?php echo $document['entity_id']; ?>">
                                    <?php echo htmlspecialchars($document['entity_name']); ?>
                                </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">None</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo formatFileSize($document['file_size']); ?></td>
                        <td><?php echo formatDateTime($document['created_at']); ?></td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="<?php echo $base_path . $document['file_path']; ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                    <i class="fas fa-download"></i> Download
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $document['id']; ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                            
                            <!-- Delete Modal -->
                            <div class="modal fade" id="deleteModal<?php echo $document['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $document['id']; ?>" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="deleteModalLabel<?php echo $document['id']; ?>">Confirm Delete</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p>Are you sure you want to delete this document?</p>
                                            <p><strong><?php echo htmlspecialchars($document['title']); ?></strong></p>
                                            <p class="text-danger">This action cannot be undone.</p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <form method="post">
                                                <input type="hidden" name="document_id" value="<?php echo $document['id']; ?>">
                                                <button type="submit" name="delete_document" class="btn btn-danger">Delete Document</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php endif; ?>

<script>
// Handle entity type change
document.getElementById('entity').addEventListener('change', function() {
    const entitySelect = document.getElementById('entity_id');
    const entityContainer = document.getElementById('entityIdContainer');
    
    if (this.value === '') {
        entitySelect.disabled = true;
        entitySelect.innerHTML = '<option value="">All</option>';
        return;
    }
    
    entitySelect.disabled = false;
    
    // Reload the page with the new entity type to get the correct entity options
    window.location.href = 'index.php?type=<?php echo urlencode($filter_type); ?>&entity=' + this.value;
});
</script>

<?php 
// Helper function to get appropriate Font Awesome icon class based on file extension
function getFileIconClass($file_path) {
    $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    
    switch ($extension) {
        case 'pdf':
            return 'fas fa-file-pdf text-danger';
        case 'doc':
        case 'docx':
            return 'fas fa-file-word text-primary';
        case 'xls':
        case 'xlsx':
            return 'fas fa-file-excel text-success';
        case 'ppt':
        case 'pptx':
            return 'fas fa-file-powerpoint text-warning';
        case 'jpg':
        case 'jpeg':
        case 'png':
        case 'gif':
            return 'fas fa-file-image text-info';
        case 'zip':
        case 'rar':
            return 'fas fa-file-archive text-secondary';
        case 'txt':
            return 'fas fa-file-alt';
        default:
            return 'fas fa-file';
    }
}

// Helper function to format file size
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
?>

<?php require_once $base_path . 'includes/footer.php'; ?>