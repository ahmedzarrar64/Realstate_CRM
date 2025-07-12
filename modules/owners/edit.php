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
$error = '';

// Get owner data
$owner = getOwnerById($id);

if (!$owner) {
    header('Location: index.php');
    exit();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $preferred_contact = $_POST['preferred_contact'];
    $notes = trim($_POST['notes']);
    
    // Validate form data
    if (empty($name)) {
        $error = 'Name is required';
    } elseif (empty($phone)) {
        $error = 'Phone is required';
    } elseif (empty($email)) {
        $error = 'Email is required';
    } elseif (empty($address)) {
        $error = 'Address is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } else {
        // Sanitize data
        $name = escapeString($name);
        $phone = escapeString($phone);
        $email = escapeString($email);
        $address = escapeString($address);
        $preferred_contact = escapeString($preferred_contact);
        $notes = escapeString($notes);
        
        // Update database
        $sql = "UPDATE owners 
                SET name = '$name', 
                    phone = '$phone', 
                    email = '$email', 
                    address = '$address', 
                    preferred_contact = '$preferred_contact', 
                    notes = '$notes' 
                WHERE id = $id";
        
        if (executeQuery($sql)) {
            // Redirect to owners list with success message
            header('Location: index.php?success=updated');
            exit();
        } else {
            $error = 'Error updating owner';
        }
    }
}
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h2><i class="fas fa-user-edit"></i> Edit Owner</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Property Owners</a></li>
                <li class="breadcrumb-item active">Edit Owner</li>
            </ol>
        </nav>
    </div>
</div>

<?php if ($error): ?>
    <?php echo showError($error); ?>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        Edit Owner Information
    </div>
    <div class="card-body">
        <form action="edit.php?id=<?php echo $id; ?>" method="post">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="name" class="form-label required-field">Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($owner['name']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="phone" class="form-label required-field">Phone</label>
                    <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($owner['phone']); ?>" required>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="email" class="form-label required-field">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($owner['email']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="preferred_contact" class="form-label required-field">Preferred Contact Method</label>
                    <select class="form-select" id="preferred_contact" name="preferred_contact" required>
                        <option value="Phone" <?php echo ($owner['preferred_contact'] === 'Phone') ? 'selected' : ''; ?>>Phone</option>
                        <option value="Email" <?php echo ($owner['preferred_contact'] === 'Email') ? 'selected' : ''; ?>>Email</option>
                        <option value="WhatsApp" <?php echo ($owner['preferred_contact'] === 'WhatsApp') ? 'selected' : ''; ?>>WhatsApp</option>
                    </select>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="address" class="form-label required-field">Address</label>
                <textarea class="form-control" id="address" name="address" rows="3" required><?php echo htmlspecialchars($owner['address']); ?></textarea>
            </div>
            
            <div class="mb-3">
                <label for="notes" class="form-label">Notes</label>
                <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($owner['notes']); ?></textarea>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="index.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Owner</button>
            </div>
        </form>
    </div>
</div>

<?php require_once $base_path . 'includes/footer.php'; ?>