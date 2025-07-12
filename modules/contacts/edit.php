<?php
// Adjust path for includes
$base_path = '../../';
require_once $base_path . 'includes/header.php';
require_once $base_path . 'includes/functions.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$id = (int)$_GET['id'];

// Get contact log data
$sql = "SELECT * FROM contact_logs WHERE id = $id";
$result = executeQuery($sql);

if ($result->num_rows === 0) {
    header('Location: index.php');
    exit();
}

$contact_log = $result->fetch_assoc();

// Get all owners for dropdown
$owners = getAllOwners();

// Get all properties for dropdown
$properties = getAllProperties();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize inputs
    $owner_id = isset($_POST['owner_id']) ? (int)$_POST['owner_id'] : null;
    $property_id = isset($_POST['property_id']) ? (int)$_POST['property_id'] : null;
    $contact_type = mysqli_real_escape_string($conn, $_POST['contact_type']);
    $contact_date = mysqli_real_escape_string($conn, $_POST['contact_date']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    
    // Validation
    $errors = [];
    
    if (empty($owner_id)) {
        $errors[] = "Owner is required.";
    }
    
    if (empty($contact_type)) {
        $errors[] = "Contact type is required.";
    }
    
    if (empty($contact_date)) {
        $errors[] = "Contact date is required.";
    }
    
    if (empty($description)) {
        $errors[] = "Description is required.";
    }
    
    // If no errors, update database
    if (empty($errors)) {
        $sql = "UPDATE contact_logs 
                SET owner_id = $owner_id, 
                    property_id = " . ($property_id ? $property_id : "NULL") . ", 
                    contact_type = '$contact_type', 
                    contact_date = '$contact_date', 
                    description = '$description' 
                WHERE id = $id";
        
        if (executeQuery($sql)) {
            $_SESSION['success_message'] = "Contact log updated successfully.";
            
            // Redirect based on context
            if (!empty($contact_log['owner_id'])) {
                header("Location: ../owners/view.php?id={$contact_log['owner_id']}");
            } elseif (!empty($contact_log['property_id'])) {
                header("Location: ../properties/view.php?id={$contact_log['property_id']}");
            } else {
                header("Location: index.php");
            }
            exit();
        } else {
            $errors[] = "Error updating contact log: " . mysqli_error($conn);
        }
    }
}
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h2><i class="fas fa-edit"></i> Edit Contact Log</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Contact Logs</a></li>
                <li class="breadcrumb-item active">Edit Contact Log</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-address-book"></i> Contact Log Details
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="owner_id" class="form-label">Owner <span class="text-danger">*</span></label>
                        <select name="owner_id" id="owner_id" class="form-select" required>
                            <option value="">Select Owner</option>
                            <?php foreach ($owners as $owner): ?>
                                <option value="<?php echo $owner['id']; ?>" <?php echo ($contact_log['owner_id'] == $owner['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($owner['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="property_id" class="form-label">Property</label>
                        <select name="property_id" id="property_id" class="form-select">
                            <option value="">Select Property (Optional)</option>
                            <?php foreach ($properties as $property): ?>
                                <option value="<?php echo $property['id']; ?>" 
                                        data-owner="<?php echo $property['owner_id']; ?>" 
                                        <?php echo ($contact_log['property_id'] == $property['id']) ? 'selected' : ''; ?>
                                        <?php echo ($contact_log['property_id'] != $property['id'] && $contact_log['owner_id'] && $contact_log['owner_id'] != $property['owner_id']) ? 'style="display:none;"' : ''; ?>>
                                    <?php echo htmlspecialchars($property['address']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="contact_type" class="form-label">Contact Type <span class="text-danger">*</span></label>
                        <select name="contact_type" id="contact_type" class="form-select" required>
                            <option value="">Select Contact Type</option>
                            <option value="Call" <?php echo ($contact_log['contact_type'] === 'Call') ? 'selected' : ''; ?>>Call</option>
                            <option value="WhatsApp" <?php echo ($contact_log['contact_type'] === 'WhatsApp') ? 'selected' : ''; ?>>WhatsApp</option>
                            <option value="Email" <?php echo ($contact_log['contact_type'] === 'Email') ? 'selected' : ''; ?>>Email</option>
                            <option value="Visit" <?php echo ($contact_log['contact_type'] === 'Visit') ? 'selected' : ''; ?>>Visit</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="contact_date" class="form-label">Contact Date & Time <span class="text-danger">*</span></label>
                        <?php 
                        // Format the date for datetime-local input
                        $contact_date = date('Y-m-d\TH:i', strtotime($contact_log['contact_date']));
                        ?>
                        <input type="datetime-local" name="contact_date" id="contact_date" class="form-control" required value="<?php echo $contact_date; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea name="description" id="description" class="form-control" rows="5" required><?php echo htmlspecialchars($contact_log['description']); ?></textarea>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Contact Log
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Filter properties based on selected owner
        const ownerSelect = document.getElementById('owner_id');
        const propertySelect = document.getElementById('property_id');
        
        ownerSelect.addEventListener('change', function() {
            const selectedOwnerId = this.value;
            const propertyOptions = propertySelect.options;
            
            // Reset property selection if owner doesn't match
            const currentPropertyOption = propertySelect.options[propertySelect.selectedIndex];
            if (currentPropertyOption && currentPropertyOption.getAttribute('data-owner') !== selectedOwnerId) {
                propertySelect.value = '';
            }
            
            // Show/hide properties based on owner
            for (let i = 0; i < propertyOptions.length; i++) {
                const option = propertyOptions[i];
                if (i === 0) { // Skip the placeholder option
                    continue;
                }
                
                const ownerIdForProperty = option.getAttribute('data-owner');
                if (selectedOwnerId === '' || ownerIdForProperty === selectedOwnerId) {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                }
            }
        });
    });
</script>

<?php require_once $base_path . 'includes/footer.php'; ?>