<?php
// Adjust path for includes
$base_path = '../../';
require_once $base_path . 'includes/header.php';
require_once $base_path . 'includes/functions.php';

// Get property ID if provided (for editing existing property)
$property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;
$property = null;

// If property ID is provided, get property details
if ($property_id > 0) {
    $sql = "SELECT * FROM properties WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $property_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $property = $result->fetch_assoc();
    }
    $stmt->close();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $latitude = isset($_POST['latitude']) ? trim($_POST['latitude']) : null;
    $longitude = isset($_POST['longitude']) ? trim($_POST['longitude']) : null;
    
    // Convert empty coordinates to NULL
    if (empty($latitude)) $latitude = null;
    if (empty($longitude)) $longitude = null;
    
    if ($property_id > 0 && $latitude !== null && $longitude !== null) {
        // Update property coordinates
        $update_sql = "UPDATE properties SET latitude = ?, longitude = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ddi", $latitude, $longitude, $property_id);
        
        if ($update_stmt->execute()) {
            // Redirect back to property edit page
            header("Location: ../properties/edit.php?id=$property_id&success=coordinates_updated");
            exit;
        }
    }
}
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2><i class="fas fa-map-marker-alt"></i> Search & Set Location</h2>
    </div>
    <div class="col-md-6 text-end">
        <?php if ($property_id > 0): ?>
        <a href="../properties/edit.php?id=<?php echo $property_id; ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Property
        </a>
        <?php else: ?>
        <a href="../properties/index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Properties
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header bg-white">
        <div class="row">
            <div class="col-md-6">
                <h5 class="mb-0">Search Location & Set Coordinates</h5>
            </div>
            <div class="col-md-6">
                <div class="input-group">
                    <input type="text" id="searchInput" class="form-control" placeholder="Search for a location...">
                    <button class="btn btn-primary" type="button" id="searchButton">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <button class="btn btn-success" type="button" id="currentLocationButton">
                        <i class="fas fa-map-marker-alt"></i> My Location
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-12">
                <div id="map" style="height: 500px;"></div>
            </div>
        </div>
        
        <div id="notification-area" class="alert d-none mb-3">
            <span id="notification-message"></span>
            <button type="button" class="btn-close float-end" aria-label="Close" onclick="this.parentElement.classList.add('d-none');"></button>
        </div>
        
        <form method="post" id="coordinatesForm">
            <div class="row">
                <div class="col-md-5">
                    <div class="mb-3">
                        <label for="latitude" class="form-label">Latitude</label>
                        <input type="text" class="form-control" id="latitude" name="latitude" value="<?php echo htmlspecialchars($property['latitude'] ?? ''); ?>" readonly>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="mb-3">
                        <label for="longitude" class="form-label">Longitude</label>
                        <input type="text" class="form-control" id="longitude" name="longitude" value="<?php echo htmlspecialchars($property['longitude'] ?? ''); ?>" readonly>
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-success w-100">
                        <i class="fas fa-save"></i> Save Coordinates
                    </button>
                </div>
            </div>
        </form>
        
        <div class="alert alert-info mt-3">
            <i class="fas fa-info-circle"></i> <strong>Tip:</strong> Search for a location using the search box, or click directly on the map to set coordinates. You can also drag the marker to fine-tune the position.
        </div>
    </div>
</div>

<!-- Include Leaflet CSS and JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<!-- Include Leaflet Geocoder for search functionality -->
<link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
<script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize the map
        <?php if (!empty($property['latitude']) && !empty($property['longitude'])): ?>
        var map = L.map('map').setView([<?php echo $property['latitude']; ?>, <?php echo $property['longitude']; ?>], 15);
        <?php else: ?>
        var map = L.map('map').setView([0, 0], 2); // Default view of the world
        <?php endif; ?>
        
        // Add OpenStreetMap tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);
        
        // Initialize marker
        var marker;
        <?php if (!empty($property['latitude']) && !empty($property['longitude'])): ?>
        marker = L.marker([<?php echo $property['latitude']; ?>, <?php echo $property['longitude']; ?>], {
            draggable: true
        }).addTo(map);
        <?php else: ?>
        marker = L.marker([0, 0], {
            draggable: true
        });
        <?php endif; ?>
        
        // Update form fields when marker is dragged
        marker.on('dragend', function(event) {
            var position = marker.getLatLng();
            document.getElementById('latitude').value = position.lat.toFixed(6);
            document.getElementById('longitude').value = position.lng.toFixed(6);
        });
        
        // Add click event to map to set marker
        map.on('click', function(e) {
            var position = e.latlng;
            
            // If marker doesn't exist, create it
            if (!map.hasLayer(marker)) {
                marker.setLatLng(position).addTo(map);
            } else {
                marker.setLatLng(position);
            }
            
            // Update form fields
            document.getElementById('latitude').value = position.lat.toFixed(6);
            document.getElementById('longitude').value = position.lng.toFixed(6);
        });
        
        // Add geocoder control for search
        var geocoder = L.Control.geocoder({
            defaultMarkGeocode: false
        }).addTo(map);
        
        // Handle geocoding results
        geocoder.on('markgeocode', function(e) {
            var bbox = e.geocode.bbox;
            var position = e.geocode.center;
            
            // Set marker position
            if (!map.hasLayer(marker)) {
                marker.setLatLng(position).addTo(map);
            } else {
                marker.setLatLng(position);
            }
            
            // Update form fields
            document.getElementById('latitude').value = position.lat.toFixed(6);
            document.getElementById('longitude').value = position.lng.toFixed(6);
            
            // Zoom to location
            map.fitBounds(bbox);
        });
        
        // Handle search button click
        document.getElementById('searchButton').addEventListener('click', function() {
            var searchInput = document.getElementById('searchInput').value;
            if (searchInput.trim() !== '') {
                geocoder.geocode(searchInput);
            }
        });
        
        // Handle search input enter key
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('searchButton').click();
            }
        });
        
        // Handle current location button click
        document.getElementById('currentLocationButton').addEventListener('click', function() {
            if (navigator.geolocation) {
                // Show loading indicator
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Locating...';
                var button = this;
                
                navigator.geolocation.getCurrentPosition(function(position) {
                    // Get current position
                    var lat = position.coords.latitude;
                    var lng = position.coords.longitude;
                    var currentPosition = L.latLng(lat, lng);
                    
                    // Set marker position
                    if (!map.hasLayer(marker)) {
                        marker.setLatLng(currentPosition).addTo(map);
                    } else {
                        marker.setLatLng(currentPosition);
                    }
                    
                    // Update form fields
                    document.getElementById('latitude').value = lat.toFixed(6);
                    document.getElementById('longitude').value = lng.toFixed(6);
                    
                    // Zoom to location
                    map.setView(currentPosition, 16);
                    
                    // Reset button text
                    button.innerHTML = '<i class="fas fa-map-marker-alt"></i> My Location';
                    
                    // Show success message
                    showNotification('Your location has been detected successfully!', 'success');
                    
                }, function(error) {
                    // Handle errors
                    var errorMessage = '';
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            errorMessage = 'User denied the request for Geolocation.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMessage = 'Location information is unavailable.';
                            break;
                        case error.TIMEOUT:
                            errorMessage = 'The request to get user location timed out.';
                            break;
                        case error.UNKNOWN_ERROR:
                            errorMessage = 'An unknown error occurred.';
                            break;
                    }
                    
                    // Reset button text
                    button.innerHTML = '<i class="fas fa-map-marker-alt"></i> My Location';
                    
                    // Show error message
                    showNotification('Error: ' + errorMessage, 'danger');
                    
                }, {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                });
            } else {
                showNotification('Geolocation is not supported by this browser.', 'warning');
            }
        });
        
        // Function to show notifications
        function showNotification(message, type) {
            var notificationArea = document.getElementById('notification-area');
            var notificationMessage = document.getElementById('notification-message');
            
            // Set message
            notificationMessage.textContent = message;
            
            // Remove all alert classes
            notificationArea.classList.remove('alert-success', 'alert-danger', 'alert-warning', 'alert-info');
            
            // Add appropriate alert class
            notificationArea.classList.add('alert-' + type);
            
            // Show notification
            notificationArea.classList.remove('d-none');
            
            // Auto-hide after 5 seconds for success messages
            if (type === 'success') {
                setTimeout(function() {
                    notificationArea.classList.add('d-none');
                }, 5000);
            }
        }
    });
</script>

<style>
    .leaflet-control-geocoder {
        display: none; /* Hide the default geocoder control as we're using our own search box */
    }
</style>

<?php require_once $base_path . 'includes/footer.php'; ?>