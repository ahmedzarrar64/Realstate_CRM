<?php
// Adjust path for includes
$base_path = '../../';
require_once $base_path . 'includes/header.php';
require_once $base_path . 'includes/functions.php';

// Get all properties with coordinates
$sql = "SELECT p.*, o.name as owner_name 
        FROM properties p 
        JOIN owners o ON p.owner_id = o.id 
        WHERE p.latitude IS NOT NULL AND p.longitude IS NOT NULL";
$result = executeQuery($sql);
$properties = [];
while ($row = $result->fetch_assoc()) {
    $properties[] = $row;
}

// Get properties without coordinates (using address for geocoding)
$sql = "SELECT p.*, o.name as owner_name 
        FROM properties p 
        JOIN owners o ON p.owner_id = o.id 
        WHERE (p.latitude IS NULL OR p.longitude IS NULL) AND p.address IS NOT NULL";
$result = executeQuery($sql);
$properties_without_coords = [];
while ($row = $result->fetch_assoc()) {
    $properties_without_coords[] = $row;
}
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2><i class="fas fa-map-marked-alt"></i> Property Map</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="../properties/index.php" class="btn btn-primary">
            <i class="fas fa-list"></i> View Properties List
        </a>
        <a href="search.php" class="btn btn-success">
            <i class="fas fa-search-location"></i> Search Location
        </a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header bg-white">
        <div class="row">
            <div class="col-md-6">
                <h5 class="mb-0">Interactive Property Map</h5>
            </div>
            <div class="col-md-6">
                <div class="d-flex justify-content-end">
                    <select id="propertyTypeFilter" class="form-select form-select-sm" style="width: auto;">
                        <option value="all">All Property Types</option>
                        <option value="Apartment">Apartment</option>
                        <option value="House">House</option>
                        <option value="Commercial">Commercial</option>
                        <option value="Land">Land</option>
                        <option value="Industrial">Industrial</option>
                    </select>
                    <select id="statusFilter" class="form-select form-select-sm ms-2" style="width: auto;">
                        <option value="all">All Statuses</option>
                        <option value="Available">Available</option>
                        <option value="Under Negotiation">Under Negotiation</option>
                        <option value="Sold">Sold</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <!-- Map container with a fixed height -->
        <div id="propertyMap" style="height: 600px; width: 100%;"></div>
    </div>
</div>

<!-- Properties without coordinates -->
<?php if (count($properties_without_coords) > 0): ?>
<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0">Properties Without Map Coordinates</h5>
    </div>
    <div class="card-body">
        <p class="text-muted">The following properties don't have latitude/longitude coordinates and cannot be displayed on the map. Update these properties with coordinates to see them on the map.</p>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Address</th>
                        <th>Type</th>
                        <th>Owner</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($properties_without_coords as $property): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($property['address']); ?></td>
                        <td><?php echo htmlspecialchars($property['property_type']); ?></td>
                        <td><?php echo htmlspecialchars($property['owner_name']); ?></td>
                        <td>
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
                        <td>
                            <a href="../properties/edit.php?id=<?php echo $property['id']; ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-edit"></i> Update Coordinates
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Include Leaflet CSS and JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
    // Initialize the map
    var map = L.map('propertyMap').setView([0, 0], 2); // Default view of the world
    
    // Add OpenStreetMap tile layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);
    
    // Property data from PHP
    var properties = <?php echo json_encode($properties); ?>;
    var markers = [];
    var bounds = [];
    
    // Create markers for each property
    properties.forEach(function(property) {
        // Create marker
        var marker = L.marker([property.latitude, property.longitude])
            .addTo(map);
        
        // Create popup content
        var popupContent = `
            <div class="property-popup">
                <h5>${property.address}</h5>
                <p><strong>Type:</strong> ${property.property_type}</p>
                <p><strong>Owner:</strong> ${property.owner_name}</p>
                <p><strong>Price:</strong> $${parseFloat(property.price).toLocaleString()}</p>
                <p><strong>Status:</strong> ${property.status}</p>
                <div class="mt-2">
                    <a href="../properties/view.php?id=${property.id}" class="btn btn-sm btn-info text-white">View Details</a>
                    <a href="../properties/edit.php?id=${property.id}" class="btn btn-sm btn-primary">Edit</a>
                </div>
            </div>
        `;
        
        // Bind popup to marker
        marker.bindPopup(popupContent);
        
        // Store marker with property data for filtering
        markers.push({
            marker: marker,
            property: property
        });
        
        // Add coordinates to bounds
        bounds.push([property.latitude, property.longitude]);
    });
    
    // If we have properties, fit the map to show all markers
    if (bounds.length > 0) {
        map.fitBounds(bounds);
    }
    
    // Filter properties by type and status
    function filterProperties() {
        var typeFilter = document.getElementById('propertyTypeFilter').value;
        var statusFilter = document.getElementById('statusFilter').value;
        
        markers.forEach(function(item) {
            var visible = true;
            
            // Apply type filter
            if (typeFilter !== 'all' && item.property.property_type !== typeFilter) {
                visible = false;
            }
            
            // Apply status filter
            if (statusFilter !== 'all' && item.property.status !== statusFilter) {
                visible = false;
            }
            
            // Show or hide marker
            if (visible) {
                map.addLayer(item.marker);
            } else {
                map.removeLayer(item.marker);
            }
        });
    }
    
    // Add event listeners to filters
    document.getElementById('propertyTypeFilter').addEventListener('change', filterProperties);
    document.getElementById('statusFilter').addEventListener('change', filterProperties);
</script>

<style>
    .property-popup {
        min-width: 200px;
    }
    .property-popup h5 {
        margin-bottom: 10px;
        font-size: 16px;
    }
    .property-popup p {
        margin-bottom: 5px;
        font-size: 14px;
    }
</style>

<?php require_once $base_path . 'includes/footer.php'; ?>