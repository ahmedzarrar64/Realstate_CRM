<?php
// Adjust path for includes
$base_path = '../../';
require_once $base_path . 'includes/header.php';
require_once $base_path . 'includes/functions.php';

$error = '';
$success = '';

// Get properties for dropdown
$properties_sql = "SELECT id, address FROM properties ORDER BY address";
$properties_result = $conn->query($properties_sql);
$properties = [];

while ($property = $properties_result->fetch_assoc()) {
    $properties[] = $property;
}

// Get owners for dropdown
$owners_sql = "SELECT id, name FROM owners ORDER BY name";
$owners_result = $conn->query($owners_sql);
$owners = [];

while ($owner = $owners_result->fetch_assoc()) {
    $owners[] = $owner;
}

// Check if uploads directory exists, if not create it
$upload_dir = $base_path . 'uploads/documents/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $document_type = isset($_POST['document_type']) ? trim($_POST['document_type']) : '';
    $entity_type = isset($_POST['entity_type']) ? trim($_POST['entity_type']) : '';
    $entity_id = isset($_POST['entity_id']) ? intval($_POST['entity_id']) : 0;
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    
    // Validate required fields
    if (empty($title)) {
        $error = "Document title is required.";
    } elseif (empty($document_type)) {
        $error = "Document type is required.";
    } elseif (!isset($_FILES['document']) || $_FILES['document']['error'] === UPLOAD_ERR_NO_FILE) {
        $error = "Please select a document to upload.";
    } else {
        // Process file upload
        $file = $_FILES['document'];
        $file_name = $file['name'];
        $file_tmp = $file['tmp_name'];
        $file_size = $file['size'];
        $file_error = $file['error'];
        
        // Check for upload errors
        if ($file_error !== UPLOAD_ERR_OK) {
            switch ($file_error) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $error = "The uploaded file exceeds the maximum file size limit.";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $error = "The file was only partially uploaded.";
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $error = "Missing a temporary folder.";
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $error = "Failed to write file to disk.";
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $error = "A PHP extension stopped the file upload.";
                    break;
                default:
                    $error = "Unknown upload error.";
            }
        } else {
            // Check file size (limit to 20MB)
            if ($file_size > 20 * 1024 * 1024) {
                $error = "File size exceeds the maximum limit of 20MB.";
            } else {
                // Get file extension
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                // Check allowed file types
                $allowed_extensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'jpg', 'jpeg', 'png', 'zip', 'rar'];
                
                if (!in_array($file_ext, $allowed_extensions)) {
                    $error = "Invalid file type. Allowed types: " . implode(', ', $allowed_extensions);
                } else {
                    // Create subdirectory based on entity type if provided
                    $entity_dir = '';
                    if (!empty($entity_type) && $entity_id > 0) {
                        $entity_dir = $entity_type . 's/' . $entity_id . '/';
                        if (!file_exists($upload_dir . $entity_dir)) {
                            mkdir($upload_dir . $entity_dir, 0777, true);
                        }
                    }
                    
                    // Generate unique filename
                    $new_file_name = uniqid('doc_') . '.' . $file_ext;
                    $file_path = 'uploads/documents/' . $entity_dir . $new_file_name;
                    $full_path = $base_path . $file_path;
                    
                    // Move uploaded file
                    if (move_uploaded_file($file_tmp, $full_path)) {
                        // Insert into database
                        $insert_sql = "INSERT INTO documents (title, document_type, file_path, file_size, entity_type, entity_id, description, created_at) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                        $insert_stmt = $conn->prepare($insert_sql);
                        
                        // If entity type is empty, set entity_id to NULL
                        if (empty($entity_type)) {
                            $entity_id = null;
                            $insert_stmt->bind_param("sssisss", $title, $document_type, $file_path, $file_size, $entity_type, $entity_id, $description);
                        } else {
                            $insert_stmt->bind_param("sssiiss", $title, $document_type, $file_path, $file_size, $entity_type, $entity_id, $description);
                        }
                        
                        if ($insert_stmt->execute()) {
                            $success = "Document uploaded successfully!";
                            
                            // Redirect to documents list
                            header("Location: index.php?success=upload");
                            exit;
                        } else {
                            $error = "Database error: " . $conn->error;
                            // Remove the file if database insert failed
                            if (file_exists($full_path)) {
                                unlink($full_path);
                            }
                        }
                        $insert_stmt->close();
                    } else {
                        $error = "Failed to upload file. Please try again.";
                    }
                }
            }
        }
    }
}

// Get document types from previous uploads for suggestions
$types_sql = "SELECT DISTINCT document_type FROM documents ORDER BY document_type";
$types_result = $conn->query($types_sql);
$document_types = [];

while ($type = $types_result->fetch_assoc()) {
    if (!empty($type['document_type'])) {
        $document_types[] = $type['document_type'];
    }
}

// Add default document types if none exist
if (empty($document_types)) {
    $document_types = ['Contract', 'Agreement', 'Invoice', 'Receipt', 'Certificate', 'Report', 'Other'];
}
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo $base_path; ?>index.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="index.php">Documents</a></li>
        <li class="breadcrumb-item active">Upload Document</li>
    </ol>
</nav>

<div class="row mb-4">
    <div class="col">
        <h2><i class="fas fa-upload"></i> Upload Document</h2>
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
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Upload New Document</h5>
    </div>
    <div class="card-body">
        <form method="post" enctype="multipart/form-data">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="title" class="form-label">Document Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="title" name="title" required>
                </div>
                <div class="col-md-6">
                    <label for="document_type" class="form-label">Document Type <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="document_type" name="document_type" list="document_types" required>
                    <datalist id="document_types">
                        <?php foreach ($document_types as $type): ?>
                        <option value="<?php echo htmlspecialchars($type); ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="entity_type" class="form-label">Related To</label>
                    <select class="form-select" id="entity_type" name="entity_type">
                        <option value="">None</option>
                        <option value="property">Property</option>
                        <option value="owner">Owner</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="entity_id" class="form-label">Select Entity</label>
                    <select class="form-select" id="entity_id" name="entity_id" disabled>
                        <option value="">Select...</option>
                    </select>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
            </div>
            
            <div class="mb-3">
                <label for="document" class="form-label">Document File <span class="text-danger">*</span></label>
                <input type="file" class="form-control" id="document" name="document" required>
                <div class="form-text">
                    Allowed file types: PDF, DOC, DOCX, XLS, XLSX, TXT, JPG, JPEG, PNG, ZIP, RAR. Maximum file size: 20MB.
                </div>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="index.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-upload"></i> Upload Document
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Handle entity type change
document.getElementById('entity_type').addEventListener('change', function() {
    const entitySelect = document.getElementById('entity_id');
    
    if (this.value === '') {
        entitySelect.disabled = true;
        entitySelect.innerHTML = '<option value="">Select...</option>';
        return;
    }
    
    entitySelect.disabled = false;
    entitySelect.innerHTML = '<option value="">Select...</option>';
    
    if (this.value === 'property') {
        // Add properties to dropdown
        <?php foreach ($properties as $property): ?>
        const propertyOption = document.createElement('option');
        propertyOption.value = '<?php echo $property['id']; ?>';
        propertyOption.textContent = '<?php echo addslashes(htmlspecialchars($property['address'])); ?>';
        entitySelect.appendChild(propertyOption);
        <?php endforeach; ?>
    } else if (this.value === 'owner') {
        // Add owners to dropdown
        <?php foreach ($owners as $owner): ?>
        const ownerOption = document.createElement('option');
        ownerOption.value = '<?php echo $owner['id']; ?>';
        ownerOption.textContent = '<?php echo addslashes(htmlspecialchars($owner['name'])); ?>';
        entitySelect.appendChild(ownerOption);
        <?php endforeach; ?>
    }
});

// File size validation
document.getElementById('document').addEventListener('change', function() {
    const fileInput = this;
    const maxSize = 20 * 1024 * 1024; // 20MB
    
    if (fileInput.files.length > 0) {
        const fileSize = fileInput.files[0].size;
        
        if (fileSize > maxSize) {
            alert('File size exceeds the maximum limit of 20MB.');
            fileInput.value = ''; // Clear the file input
        }
    }
});
</script>

<?php require_once $base_path . 'includes/footer.php'; ?>