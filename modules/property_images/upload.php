<?php
// Adjust path for includes
$base_path = '../../';
require_once $base_path . 'includes/header.php';
require_once $base_path . 'includes/functions.php';

// Check if property ID is provided
if (!isset($_GET['property_id']) || empty($_GET['property_id'])) {
    header('Location: ' . $base_path . 'modules/properties/index.php');
    exit;
}

$property_id = intval($_GET['property_id']);
$error = '';
$success = '';

// Get property details
$property_sql = "SELECT p.*, o.name as owner_name 
                FROM properties p 
                LEFT JOIN owners o ON p.owner_id = o.id 
                WHERE p.id = ?";
$stmt = $conn->prepare($property_sql);
$stmt->bind_param("i", $property_id);
$stmt->execute();
$property_result = $stmt->get_result();

if ($property_result->num_rows === 0) {
    // Property not found
    header('Location: ' . $base_path . 'modules/properties/index.php');
    exit;
}

$property = $property_result->fetch_assoc();
$stmt->close();

// Check if uploads directory exists, if not create it
$upload_dir = $base_path . 'uploads/properties/' . $property_id . '/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if files were uploaded
    if (isset($_FILES['property_images']) && !empty($_FILES['property_images']['name'][0])) {
        $files = $_FILES['property_images'];
        $file_count = count($files['name']);
        $success_count = 0;
        $error_count = 0;
        
        // Get count of existing images to determine if we need to set primary
        $count_sql = "SELECT COUNT(*) as image_count FROM property_images WHERE property_id = ?";
        $count_stmt = $conn->prepare($count_sql);
        $count_stmt->bind_param("i", $property_id);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result()->fetch_assoc();
        $existing_images = $count_result['image_count'];
        $count_stmt->close();
        
        // Process each uploaded file
        for ($i = 0; $i < $file_count; $i++) {
            if ($files['error'][$i] === 0) {
                $file_name = $files['name'][$i];
                $file_tmp = $files['tmp_name'][$i];
                $file_size = $files['size'][$i];
                $file_type = $files['type'][$i];
                
                // Generate unique filename
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $new_file_name = uniqid('property_') . '.' . $file_ext;
                $file_path = 'uploads/properties/' . $property_id . '/' . $new_file_name;
                $full_path = $base_path . $file_path;
                
                // Check if file is an image
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!in_array($file_type, $allowed_types)) {
                    $error .= "File '$file_name' is not an allowed image type. ";
                    $error_count++;
                    continue;
                }
                
                // Check file size (limit to 5MB)
                if ($file_size > 5 * 1024 * 1024) {
                    $error .= "File '$file_name' exceeds the maximum size limit of 5MB. ";
                    $error_count++;
                    continue;
                }
                
                // Move uploaded file
                if (move_uploaded_file($file_tmp, $full_path)) {
                    // Set as featured if it's the first image
                    $is_featured = ($existing_images + $success_count) === 0 ? 1 : 0;
                    
                    // Use original file name if no title provided
                    $file_name = isset($_POST['image_titles'][$i]) && !empty($_POST['image_titles'][$i]) ? 
                             $_POST['image_titles'][$i] : $file_name;
                    
                    // Insert into database
                    $insert_sql = "INSERT INTO property_images (property_id, file_name, file_path, is_featured, created_at) 
                                  VALUES (?, ?, ?, ?, NOW())";
                    $insert_stmt = $conn->prepare($insert_sql);
                    $insert_stmt->bind_param("issi", $property_id, $file_name, $file_path, $is_featured);
                    
                    if ($insert_stmt->execute()) {
                        $success_count++;
                    } else {
                        $error .= "Database error for '$file_name': " . $conn->error . " ";
                        $error_count++;
                        // Remove the file if database insert failed
                        if (file_exists($full_path)) {
                            unlink($full_path);
                        }
                    }
                    $insert_stmt->close();
                } else {
                    $error .= "Failed to upload '$file_name'. ";
                    $error_count++;
                }
            } else {
                $error .= "Error with file '{$files['name'][$i]}': " . $files['error'][$i] . " ";
                $error_count++;
            }
        }
        
        if ($success_count > 0) {
            $success = "$success_count image(s) uploaded successfully!";
            if ($error_count === 0) {
                // Redirect to gallery if all uploads were successful
                header("Location: index.php?property_id=$property_id&success=upload");
                exit;
            }
        }
    } else {
        $error = "No files were selected for upload.";
    }
}
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo $base_path; ?>index.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?php echo $base_path; ?>modules/properties/index.php">Properties</a></li>
        <li class="breadcrumb-item"><a href="<?php echo $base_path; ?>modules/properties/view.php?id=<?php echo $property_id; ?>"><?php echo htmlspecialchars($property['address']); ?></a></li>
        <li class="breadcrumb-item"><a href="index.php?property_id=<?php echo $property_id; ?>">Image Gallery</a></li>
        <li class="breadcrumb-item active">Upload Images</li>
    </ol>
</nav>

<div class="row mb-4">
    <div class="col">
        <h2><i class="fas fa-upload"></i> Upload Property Images</h2>
        <h5 class="text-muted"><?php echo htmlspecialchars($property['address']); ?></h5>
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
        <h5 class="mb-0">Upload Images</h5>
    </div>
    <div class="card-body">
        <form method="post" enctype="multipart/form-data" id="uploadForm">
            <div class="mb-3">
                <label for="property_images" class="form-label">Select Images</label>
                <input type="file" class="form-control" id="property_images" name="property_images[]" multiple accept="image/*" required>
                <div class="form-text">You can select multiple images. Allowed formats: JPG, PNG, GIF, WEBP. Max size: 5MB per image.</div>
            </div>
            
            <div id="image_previews" class="row mb-3">
                <!-- Image previews will be displayed here -->
            </div>
            
            <div id="image_titles" class="mb-3">
                <!-- Image title inputs will be added here dynamically -->
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="index.php?property_id=<?php echo $property_id; ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-upload"></i> Upload Images
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Preview images before upload
document.getElementById('property_images').addEventListener('change', function(event) {
    const previewContainer = document.getElementById('image_previews');
    const titleContainer = document.getElementById('image_titles');
    
    // Clear previous previews and titles
    previewContainer.innerHTML = '';
    titleContainer.innerHTML = '';
    
    const files = event.target.files;
    
    if (files.length > 0) {
        // Add title section header
        titleContainer.innerHTML = '<h5 class="mb-3">Image Titles (Optional)</h5>';
        
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            
            // Only process image files
            if (!file.type.match('image.*')) {
                continue;
            }
            
            // Create preview column
            const col = document.createElement('div');
            col.className = 'col-md-3 col-sm-6 mb-3';
            
            // Create preview card
            const card = document.createElement('div');
            card.className = 'card h-100';
            
            // Create image preview
            const img = document.createElement('img');
            img.className = 'card-img-top';
            img.style.height = '150px';
            img.style.objectFit = 'cover';
            
            // Create card body for file name
            const cardBody = document.createElement('div');
            cardBody.className = 'card-body';
            
            const fileName = document.createElement('p');
            fileName.className = 'card-text small text-truncate';
            fileName.textContent = file.name;
            
            cardBody.appendChild(fileName);
            card.appendChild(img);
            card.appendChild(cardBody);
            col.appendChild(card);
            previewContainer.appendChild(col);
            
            // Create title input for this image
            const titleGroup = document.createElement('div');
            titleGroup.className = 'mb-3';
            
            const titleLabel = document.createElement('label');
            titleLabel.className = 'form-label';
            titleLabel.textContent = `Title for ${file.name}`;
            
            const titleInput = document.createElement('input');
            titleInput.type = 'text';
            titleInput.className = 'form-control';
            titleInput.name = `image_titles[${i}]`;
            titleInput.placeholder = 'Enter a title for this image (optional)';
            
            titleGroup.appendChild(titleLabel);
            titleGroup.appendChild(titleInput);
            titleContainer.appendChild(titleGroup);
            
            // Read and display the image
            const reader = new FileReader();
            reader.onload = (function(aImg) { 
                return function(e) { 
                    aImg.src = e.target.result; 
                }; 
            })(img);
            reader.readAsDataURL(file);
        }
    }
});
</script>

<?php require_once $base_path . 'includes/footer.php'; ?>