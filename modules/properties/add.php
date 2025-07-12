<?php
// Adjust path for includes
$base_path = '../../';
require_once $base_path . 'includes/header.php';
require_once $base_path . 'includes/functions.php';

// Get all owners for dropdown
$owners = getAllOwners();

// Check if owner_id is provided in URL
$owner_id = isset($_GET['owner_id']) ? (int)$_GET['owner_id'] : '';

// Initialize variables
$property_type = '';
$address = '';
$area = '';
$price = '';
$status = 'Available';
$error = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $owner_id = (int)$_POST['owner_id'];
    $property_type = trim($_POST['property_type']);
    $address = trim($_POST['address']);
    $area = trim($_POST['area']);
    $price = trim($_POST['price']);
    $status = $_POST['status'];
    $latitude = isset($_POST['latitude']) ? trim($_POST['latitude']) : null;
    $longitude = isset($_POST['longitude']) ? trim($_POST['longitude']) : null;
    
    // Convert empty coordinates to NULL
    if (empty($latitude)) $latitude = null;
    if (empty($longitude)) $longitude = null;
    
    // Validate form data
    if (empty($owner_id)) {
        $error = 'Owner is required';
    } elseif (empty($property_type)) {
        $error = 'Property type is required';
    } elseif (empty($address)) {
        $error = 'Address is required';
    } elseif (empty($area)) {
        $error = 'Area is required';
    } elseif (!is_numeric($area)) {
        $error = 'Area must be a number';
    } elseif (empty($price)) {
        $error = 'Price is required';
    } elseif (!is_numeric($price)) {
        $error = 'Price must be a number';
    } else {
        // Sanitize data
        $property_type = escapeString($property_type);
        $address = escapeString($address);
        $area = (float)$area;
        $price = (float)$price;
        $status = escapeString($status);
        
        // Insert into database
        $sql = "INSERT INTO properties (owner_id, property_type, address, area, price, status, latitude, longitude) 
                VALUES ($owner_id, '$property_type', '$address', $area, $price, '$status', " . 
                ($latitude === null ? "NULL" : "'$latitude'") . ", " . 
                ($longitude === null ? "NULL" : "'$longitude'") . ")";
        
        if (executeQuery($sql)) {
            // Get the ID of the newly inserted property
            $new_property_id = $conn->insert_id;
            
            // Redirect to properties list with success message or to map search if coordinates are not set
            if ($latitude === null || $longitude === null) {
                header('Location: ../mapping/search.php?property_id=' . $new_property_id);
            } else {
                header('Location: index.php?success=added');
            }
            exit();
        } else {
            $error = 'Error adding property';
        }
    }
}
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h2><i class="fas fa-plus-circle"></i> Add New Property</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Properties</a></li>
                <li class="breadcrumb-item active">Add New Property</li>
            </ol>
        </nav>
    </div>
</div>

<?php if ($error): ?>
    <?php echo showError($error); ?>
<?php endif; ?>

<?php if (empty($owners)): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i> You need to add at least one property owner before adding properties.
        <a href="../owners/add.php" class="btn btn-primary btn-sm ms-3">Add Owner</a>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-header">
            Property Information
        </div>
        <div class="card-body">
            <form action="add.php" method="post">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="owner_id" class="form-label required-field">Owner</label>
                        <select class="form-select" id="owner_id" name="owner_id" required>
                            <option value="">Select Owner</option>
                            <?php foreach ($owners as $owner): ?>
                                <option value="<?php echo $owner['id']; ?>" <?php echo ($owner_id == $owner['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($owner['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="property_type" class="form-label required-field">Property Type</label>
                        <select class="form-select" id="property_type" name="property_type" required>
                            <option value="">Select Type</option>
                            <option value="Apartment" <?php echo ($property_type === 'Apartment') ? 'selected' : ''; ?>>Apartment</option>
                            <option value="House" <?php echo ($property_type === 'House') ? 'selected' : ''; ?>>House</option>
                            <option value="Commercial" <?php echo ($property_type === 'Commercial') ? 'selected' : ''; ?>>Commercial</option>
                            <option value="Land" <?php echo ($property_type === 'Land') ? 'selected' : ''; ?>>Land</option>
                            <option value="Industrial" <?php echo ($property_type === 'Industrial') ? 'selected' : ''; ?>>Industrial</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="address" class="form-label required-field">Address</label>
                    <textarea class="form-control" id="address" name="address" rows="3" required><?php echo htmlspecialchars($address); ?></textarea>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="area" class="form-label required-field">Area (sqm)</label>
                        <input type="number" step="0.01" class="form-control" id="area" name="area" value="<?php echo htmlspecialchars($area); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="price" class="form-label required-field">Price ($)</label>
                        <input type="number" step="0.01" class="form-control" id="price" name="price" value="<?php echo htmlspecialchars($price); ?>" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="status" class="form-label required-field">Status</label>
                    <select class="form-select" id="status" name="status" required>
                        <option value="Available" <?php echo ($status === 'Available') ? 'selected' : ''; ?>>Available</option>
                        <option value="Under Negotiation" <?php echo ($status === 'Under Negotiation') ? 'selected' : ''; ?>>Under Negotiation</option>
                        <option value="Sold" <?php echo ($status === 'Sold') ? 'selected' : ''; ?>>Sold</option>
                    </select>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="latitude" class="form-label">Latitude</label>
                        <input type="text" class="form-control" id="latitude" name="latitude" value="">
                    </div>
                    <div class="col-md-6">
                        <label for="longitude" class="form-label">Longitude</label>
                        <input type="text" class="form-control" id="longitude" name="longitude" value="">
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="fas fa-map-marker-alt"></i> Set Property Location</h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> You can set the property location in two ways:
                                    <ul>
                                        <li>Enter latitude and longitude coordinates manually above</li>
                                        <li>After saving the property, use the map search tool to find and set the location visually</li>
                                    </ul>
                                </div>
                                <div id="map-preview" style="height: 300px; background-color: #f8f9fa; display: flex; align-items: center; justify-content: center;">
                                    <div class="text-center">
                                        <i class="fas fa-map-marked-alt fa-4x text-muted mb-3"></i>
                                        <h5>Map Preview</h5>
                                        <p>Save the property first to access the interactive map</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> After saving the property, you'll be able to search and set the location on a map.
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Property</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="alert alert-info mt-4">
        <i class="fas fa-info-circle"></i> After saving the property, you'll be able to:
        <ul>
            <li>Search and set the location on a map</li>
            <li>Upload property images</li>
            <li>Add property documents</li>
        </ul>
    </div>
    
    <!-- Preview of Property Media Options -->
    <div class="row mt-4">
        <!-- Property Images Preview -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-images"></i> Property Images</h5>
                </div>
                <div class="card-body">
                    <p>After saving the property, you'll be able to upload and manage images.</p>
                    <div class="text-center py-4">
                        <i class="fas fa-camera fa-4x text-muted mb-3"></i>
                        <h5>Image Gallery</h5>
                        <p>Upload multiple images for this property</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Property Documents Preview -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-file-alt"></i> Property Documents</h5>
                </div>
                <div class="card-body">
                    <p>After saving the property, you'll be able to upload and manage documents.</p>
                    <div class="text-center py-4">
                        <i class="fas fa-file-upload fa-4x text-muted mb-3"></i>
                        <h5>Document Repository</h5>
                        <p>Store contracts, deeds, and other important files</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once $base_path . 'includes/footer.php'; ?>