<?php
// Adjust path for includes
$base_path = '../../';
require_once $base_path . 'includes/header.php';
require_once $base_path . 'includes/functions.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$property_id = intval($_GET['id']);
$error = '';
$success = '';

// Get all owners for dropdown
$owners_sql = "SELECT * FROM owners ORDER BY name";
$owners_result = $conn->query($owners_sql);
$owners = [];

while ($owner = $owners_result->fetch_assoc()) {
    $owners[] = $owner;
}

// Get property details
$property_sql = "SELECT * FROM properties WHERE id = ?";
$stmt = $conn->prepare($property_sql);
$stmt->bind_param("i", $property_id);
$stmt->execute();
$property_result = $stmt->get_result();

if ($property_result->num_rows === 0) {
    // Property not found
    header('Location: index.php');
    exit;
}

$property = $property_result->fetch_assoc();
$stmt->close();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    $owner_id = isset($_POST['owner_id']) ? intval($_POST['owner_id']) : 0;
    $property_type = isset($_POST['property_type']) ? trim($_POST['property_type']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $area = isset($_POST['area']) ? floatval($_POST['area']) : 0;
    $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
    $status = isset($_POST['status']) ? trim($_POST['status']) : '';
    $latitude = isset($_POST['latitude']) ? trim($_POST['latitude']) : null;
    $longitude = isset($_POST['longitude']) ? trim($_POST['longitude']) : null;
    
    // Convert empty coordinates to NULL
    if (empty($latitude)) $latitude = null;
    if (empty($longitude)) $longitude = null;
    
    // Validate required fields
    if ($owner_id <= 0) {
        $error = "Please select an owner.";
    } elseif (empty($property_type)) {
        $error = "Property type is required.";
    } elseif (empty($address)) {
        $error = "Address is required.";
    } elseif ($area <= 0) {
        $error = "Area must be greater than zero.";
    } elseif ($price <= 0) {
        $error = "Price must be greater than zero.";
    } elseif (empty($status)) {
        $error = "Status is required.";
    } else {
        // Update property in database
        $update_sql = "UPDATE properties SET 
                      owner_id = ?, 
                      property_type = ?, 
                      address = ?, 
                      area = ?, 
                      price = ?, 
                      status = ?,
                      latitude = ?,
                      longitude = ?,
                      updated_at = NOW() 
                      WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("issddssddi", $owner_id, $property_type, $address, $area, $price, $status, $latitude, $longitude, $property_id);
        
        if ($update_stmt->execute()) {
            $success = "Property updated successfully!";
            
            // Update local property array with new values
            $property['owner_id'] = $owner_id;
            $property['property_type'] = $property_type;
            $property['address'] = $address;
            $property['area'] = $area;
            $property['price'] = $price;
            $property['status'] = $status;
            $property['latitude'] = $latitude;
            $property['longitude'] = $longitude;
        } else {
            $error = "Error updating property: " . $conn->error;
        }
        $update_stmt->close();
    }
}

// Get property images count
$images_sql = "SELECT COUNT(*) as image_count FROM property_images WHERE property_id = ?";
$images_stmt = $conn->prepare($images_sql);
$images_stmt->bind_param("i", $property_id);
$images_stmt->execute();
$images_result = $images_stmt->get_result()->fetch_assoc();
$image_count = $images_result['image_count'];
$images_stmt->close();

// Get property documents count
$docs_sql = "SELECT COUNT(*) as doc_count FROM documents WHERE property_id = ?";
$docs_stmt = $conn->prepare($docs_sql);
$docs_stmt->bind_param("i", $property_id);
$docs_stmt->execute();
$docs_result = $docs_stmt->get_result()->fetch_assoc();
$doc_count = $docs_result['doc_count'];
$docs_stmt->close();
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo $base_path; ?>index.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="index.php">Properties</a></li>
        <li class="breadcrumb-item active">Edit Property</li>
    </ol>
</nav>

<div class="row mb-4">
    <div class="col">
        <h2><i class="fas fa-edit"></i> Edit Property</h2>
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
        <h5 class="mb-0">Property Information</h5>
    </div>
    <div class="card-body">
        <form method="post">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="owner_id" class="form-label">Owner</label>
                    <select class="form-select" id="owner_id" name="owner_id" required>
                        <option value="">Select Owner</option>
                        <?php foreach ($owners as $owner): ?>
                        <option value="<?php echo $owner['id']; ?>" <?php echo $property['owner_id'] == $owner['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($owner['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="property_type" class="form-label">Property Type</label>
                    <select class="form-select" id="property_type" name="property_type" required>
                        <option value="">Select Type</option>
                        <option value="Apartment" <?php echo $property['property_type'] === 'Apartment' ? 'selected' : ''; ?>>Apartment</option>
                        <option value="House" <?php echo $property['property_type'] === 'House' ? 'selected' : ''; ?>>House</option>
                        <option value="Commercial" <?php echo $property['property_type'] === 'Commercial' ? 'selected' : ''; ?>>Commercial</option>
                        <option value="Land" <?php echo $property['property_type'] === 'Land' ? 'selected' : ''; ?>>Land</option>
                        <option value="Industrial" <?php echo $property['property_type'] === 'Industrial' ? 'selected' : ''; ?>>Industrial</option>
                    </select>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="address" class="form-label">Address</label>
                <textarea class="form-control" id="address" name="address" rows="3" required><?php echo htmlspecialchars($property['address']); ?></textarea>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="area" class="form-label">Area (sq ft/m²)</label>
                    <input type="number" class="form-control" id="area" name="area" step="0.01" min="0" value="<?php echo $property['area']; ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="price" class="form-label">Price</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" value="<?php echo $property['price']; ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status" required>
                        <option value="">Select Status</option>
                        <option value="Available" <?php echo $property['status'] === 'Available' ? 'selected' : ''; ?>>Available</option>
                        <option value="Under Negotiation" <?php echo $property['status'] === 'Under Negotiation' ? 'selected' : ''; ?>>Under Negotiation</option>
                        <option value="Sold" <?php echo $property['status'] === 'Sold' ? 'selected' : ''; ?>>Sold</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="latitude" class="form-label">Latitude</label>
                    <input type="text" class="form-control" id="latitude" name="latitude" value="<?php echo htmlspecialchars($property['latitude'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label for="longitude" class="form-label">Longitude</label>
                    <input type="text" class="form-control" id="longitude" name="longitude" value="<?php echo htmlspecialchars($property['longitude'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-12">
                    <a href="../mapping/search.php?property_id=<?php echo $property_id; ?>" class="btn btn-info">
                        <i class="fas fa-map-marker-alt"></i> Search & Set Location on Map
                    </a>
                    <small class="text-muted ms-2">Use this to search for a location and set coordinates by clicking on the map</small>
                </div>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="index.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Property Media Section -->
<div class="row mt-4">
    <!-- Property Images -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-images"></i> Property Images</h5>
            </div>
            <div class="card-body">
                <p>Manage images for this property.</p>
                <?php if ($image_count > 0): ?>
                <p><strong><?php echo $image_count; ?></strong> image(s) uploaded for this property.</p>
                <?php else: ?>
                <p>No images uploaded yet.</p>
                <?php endif; ?>
                <div class="d-grid gap-2">
                    <a href="<?php echo $base_path; ?>modules/property_images/index.php?property_id=<?php echo $property_id; ?>" class="btn btn-info">
                        <i class="fas fa-photo-video"></i> View Image Gallery
                    </a>
                    <a href="<?php echo $base_path; ?>modules/property_images/upload.php?property_id=<?php echo $property_id; ?>" class="btn btn-outline-info">
                        <i class="fas fa-upload"></i> Upload New Images
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Property Documents -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-file-alt"></i> Property Documents</h5>
            </div>
            <div class="card-body">
                <p>Manage documents related to this property.</p>
                <?php if ($doc_count > 0): ?>
                <p><strong><?php echo $doc_count; ?></strong> document(s) uploaded for this property.</p>
                <?php else: ?>
                <p>No documents uploaded yet.</p>
                <?php endif; ?>
                <div class="d-grid gap-2">
                    <a href="<?php echo $base_path; ?>modules/documents/index.php?entity=property&entity_id=<?php echo $property_id; ?>" class="btn btn-warning">
                        <i class="fas fa-folder-open"></i> View Documents
                    </a>
                    <a href="<?php echo $base_path; ?>modules/documents/upload.php?entity_type=property&entity_id=<?php echo $property_id; ?>" class="btn btn-outline-warning">
                        <i class="fas fa-upload"></i> Upload New Document
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Map Preview Section -->
<div class="card mt-4">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0"><i class="fas fa-map-marker-alt"></i> Property Location</h5>
    </div>
    <div class="card-body">
        <?php if (!empty($property['latitude']) && !empty($property['longitude'])): ?>
        <div id="property-map" style="height: 400px;"></div>
        <?php else: ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No location coordinates available. Add latitude and longitude to see the property on the map.
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($property['latitude']) && !empty($property['longitude'])): ?>
<!-- Leaflet CSS and JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize map
        const map = L.map('property-map').setView([<?php echo $property['latitude']; ?>, <?php echo $property['longitude']; ?>], 15);
        
        // Add OpenStreetMap tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);
        
        // Add marker for the property
        const marker = L.marker([<?php echo $property['latitude']; ?>, <?php echo $property['longitude']; ?>]).addTo(map);
        marker.bindPopup("<b><?php echo htmlspecialchars($property['address']); ?></b><br>$<?php echo number_format($property['price'], 2); ?>").openPopup();
    });
</script>
<?php endif; ?>

<?php require_once $base_path . 'includes/footer.php'; ?>