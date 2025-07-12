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

// Initialize variables with client data
$name = $client['name'];
$phone = $client['phone'];
$email = $client['email'];
$address = $client['address'];
$client_type = $client['client_type'];
$preferred_contact = $client['preferred_contact'];
$notes = $client['notes'];
$error = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize inputs
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $client_type = $_POST['client_type'];
    $preferred_contact = $_POST['preferred_contact'];
    $notes = trim($_POST['notes']);
    
    // Basic validation
    if (empty($name)) {
        $error = "Client name is required.";
    } elseif (empty($phone)) {
        $error = "Phone number is required.";
    } elseif (empty($email)) {
        $error = "Email is required.";
    } elseif (empty($address)) {
        $error = "Address is required.";
    } else {
        // Sanitize inputs
        $name = escapeString($name);
        $phone = escapeString($phone);
        $email = escapeString($email);
        $address = escapeString($address);
        $client_type = escapeString($client_type);
        $preferred_contact = escapeString($preferred_contact);
        $notes = escapeString($notes);
        
        // Update database
        $sql = "UPDATE clients SET 
                name = '$name', 
                phone = '$phone', 
                email = '$email', 
                address = '$address', 
                client_type = '$client_type', 
                preferred_contact = '$preferred_contact', 
                notes = '$notes' 
                WHERE id = $client_id";
        
        if (executeQuery($sql)) {
            // Log the activity
            $activity_sql = "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, details) 
                            VALUES ({$_SESSION['user_id']}, 'Update', 'client', $client_id, 'Updated client information')";
            executeQuery($activity_sql);
            
            $_SESSION['success_message'] = "Client updated successfully.";
            header("Location: view.php?id=$client_id");
            exit();
        } else {
            $error = "Error updating client. Please try again.";
        }
    }
}
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h2><i class="fas fa-edit"></i> Edit Client</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $base_path; ?>index.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="index.php">Clients</a></li>
                <li class="breadcrumb-item"><a href="view.php?id=<?php echo $client_id; ?>"><?php echo htmlspecialchars($client['name']); ?></a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </nav>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <i class="fas fa-user-edit"></i> Edit Client Information
            </div>
            <div class="card-body">
                <form method="post" action="">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label required-field">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label required-field">Phone</label>
                            <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="email" class="form-label required-field">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="client_type" class="form-label required-field">Client Type</label>
                            <select class="form-select" id="client_type" name="client_type" required>
                                <option value="Buyer" <?php echo ($client_type === 'Buyer') ? 'selected' : ''; ?>>Buyer</option>
                                <option value="Tenant" <?php echo ($client_type === 'Tenant') ? 'selected' : ''; ?>>Tenant</option>
                                <option value="Both" <?php echo ($client_type === 'Both') ? 'selected' : ''; ?>>Both</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="preferred_contact" class="form-label required-field">Preferred Contact Method</label>
                            <select class="form-select" id="preferred_contact" name="preferred_contact" required>
                                <option value="Phone" <?php echo ($preferred_contact === 'Phone') ? 'selected' : ''; ?>>Phone</option>
                                <option value="Email" <?php echo ($preferred_contact === 'Email') ? 'selected' : ''; ?>>Email</option>
                                <option value="WhatsApp" <?php echo ($preferred_contact === 'WhatsApp') ? 'selected' : ''; ?>>WhatsApp</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label required-field">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3" required><?php echo htmlspecialchars($address); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($notes); ?></textarea>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="view.php?id=<?php echo $client_id; ?>" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Client</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once $base_path . 'includes/footer.php'; ?>