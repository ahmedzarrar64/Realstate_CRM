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

// Handle image deletion
if (isset($_POST['delete_image']) && isset($_POST['image_id'])) {
    $image_id = intval($_POST['image_id']);
    
    // Get image file path before deleting record
    $get_image_sql = "SELECT file_path FROM property_images WHERE id = ? AND property_id = ?";
    $get_stmt = $conn->prepare($get_image_sql);
    $get_stmt->bind_param("ii", $image_id, $property_id);
    $get_stmt->execute();
    $image_result = $get_stmt->get_result();
    
    if ($image_result->num_rows > 0) {
        $image_data = $image_result->fetch_assoc();
        $file_path = $base_path . $image_data['file_path'];
        
        // Delete from database
        $delete_sql = "DELETE FROM property_images WHERE id = ? AND property_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("ii", $image_id, $property_id);
        
        if ($delete_stmt->execute()) {
            // Delete file from server if it exists
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            $success = "Image deleted successfully!";
        } else {
            $error = "Error deleting image: " . $conn->error;
        }
        $delete_stmt->close();
    }
    $get_stmt->close();
}

// Get all images for this property
$images_sql = "SELECT * FROM property_images WHERE property_id = ? ORDER BY is_featured DESC, created_at DESC";
$images_stmt = $conn->prepare($images_sql);
$images_stmt->bind_param("i", $property_id);
$images_stmt->execute();
$images_result = $images_stmt->get_result();
$images = [];

while ($image = $images_result->fetch_assoc()) {
    $images[] = $image;
}
$images_stmt->close();

// Handle setting primary image
if (isset($_POST['set_primary']) && isset($_POST['image_id'])) {
    $image_id = intval($_POST['image_id']);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // First, set all images for this property as non-featured
        $reset_sql = "UPDATE property_images SET is_featured = 0 WHERE property_id = ?";
        $reset_stmt = $conn->prepare($reset_sql);
        $reset_stmt->bind_param("i", $property_id);
        $reset_stmt->execute();
        $reset_stmt->close();
        
        // Then set the selected image as featured
        $primary_sql = "UPDATE property_images SET is_featured = 1 WHERE id = ? AND property_id = ?";
        $primary_stmt = $conn->prepare($primary_sql);
        $primary_stmt->bind_param("ii", $image_id, $property_id);
        $primary_stmt->execute();
        $primary_stmt->close();
        
        $conn->commit();
        $success = "Primary image updated successfully!";
        
        // Refresh the page to show updated primary status
        header("Location: index.php?property_id=$property_id&success=primary_updated");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error updating primary image: " . $e->getMessage();
    }
}

// Handle success message from redirect
if (isset($_GET['success'])) {
    if ($_GET['success'] == 'upload') {
        $success = "Images uploaded successfully!";
    } else if ($_GET['success'] == 'primary_updated') {
        $success = "Primary image updated successfully!";
    }
}
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo $base_path; ?>index.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?php echo $base_path; ?>modules/properties/index.php">Properties</a></li>
        <li class="breadcrumb-item"><a href="<?php echo $base_path; ?>modules/properties/view.php?id=<?php echo $property_id; ?>"><?php echo htmlspecialchars($property['address']); ?></a></li>
        <li class="breadcrumb-item active">Image Gallery</li>
    </ol>
</nav>

<div class="row mb-4">
    <div class="col-md-8">
        <h2><i class="fas fa-images"></i> Property Image Gallery</h2>
        <h5 class="text-muted"><?php echo htmlspecialchars($property['address']); ?></h5>
    </div>
    <div class="col-md-4 text-end">
        <a href="upload.php?property_id=<?php echo $property_id; ?>" class="btn btn-primary">
            <i class="fas fa-upload"></i> Upload New Images
        </a>
        <a href="<?php echo $base_path; ?>modules/properties/view.php?id=<?php echo $property_id; ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Property
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

<?php if (empty($images)): ?>
<div class="alert alert-info">
    <i class="fas fa-info-circle"></i> No images have been uploaded for this property yet.
    <a href="upload.php?property_id=<?php echo $property_id; ?>" class="alert-link">Upload images now</a>.
</div>
<?php else: ?>

<div class="row">
    <?php foreach ($images as $image): ?>
    <div class="col-md-4 col-sm-6 mb-4">
        <div class="card h-100">
            <div class="position-relative">
                <img src="<?php echo $base_path . htmlspecialchars($image['file_path']); ?>" class="card-img-top" alt="Property Image" style="height: 200px; object-fit: cover;">
                <?php if ($image['is_featured']): ?>
                <span class="position-absolute top-0 start-0 badge bg-success m-2">
                    <i class="fas fa-star"></i> Featured
                </span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <h6 class="card-title"><?php echo htmlspecialchars($image['file_name'] ?: 'Property Image'); ?></h6>
                <p class="card-text small text-muted">
                    Uploaded: <?php echo formatDate($image['created_at']); ?>
                </p>
                <div class="d-flex justify-content-between">
                    <?php if (!$image['is_featured']): ?>
                    <form method="post" class="d-inline">
                        <input type="hidden" name="image_id" value="<?php echo $image['id']; ?>">
                        <button type="submit" name="set_primary" class="btn btn-sm btn-outline-success">
                            <i class="fas fa-star"></i> Set as Primary
                        </button>
                    </form>
                    <?php else: ?>
                    <span class="text-success"><i class="fas fa-check-circle"></i> Primary Image</span>
                    <?php endif; ?>
                    
                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $image['id']; ?>">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Delete Modal -->
        <div class="modal fade" id="deleteModal<?php echo $image['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $image['id']; ?>" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteModalLabel<?php echo $image['id']; ?>">Confirm Delete</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete this image? This action cannot be undone.</p>
                        <div class="text-center">
                            <img src="<?php echo $base_path . htmlspecialchars($image['file_path']); ?>" class="img-fluid mb-2" style="max-height: 200px;" alt="Property Image">
                        </div>
                        <?php if ($image['is_featured']): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> This is the primary image. Deleting it will require setting another image as primary.
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="image_id" value="<?php echo $image['id']; ?>">
                            <button type="submit" name="delete_image" class="btn btn-danger">Delete Image</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php endif; ?>

<?php require_once $base_path . 'includes/footer.php'; ?>