<?php
// Adjust path for includes
$base_path = './';
require_once $base_path . 'includes/header.php';
require_once $base_path . 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user data
$sql = "SELECT * FROM users WHERE id = $user_id";
$result = executeQuery($sql);

if ($result->num_rows === 0) {
    header('Location: index.php');
    exit();
}

$user = $result->fetch_assoc();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $errors = [];
    
    // Validate inputs
    if (empty($name)) {
        $errors[] = "Name is required.";
    }
    
    // Check if user wants to change password
    if (!empty($current_password)) {
        // Verify current password
        if (!password_verify($current_password, $user['password'])) {
            $errors[] = "Current password is incorrect.";
        }
        
        // Validate new password
        if (empty($new_password)) {
            $errors[] = "New password is required when changing password.";
        } elseif (strlen($new_password) < 6) {
            $errors[] = "New password must be at least 6 characters long.";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "New password and confirmation do not match.";
        }
    }
    
    // If no errors, update user profile
    if (empty($errors)) {
        // Start building the SQL query
        $sql = "UPDATE users SET name = '$name', email = '$email'";
        
        // Add password update if provided
        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $sql .= ", password = '$hashed_password'";
        }
        
        $sql .= " WHERE id = $user_id";
        
        if (executeQuery($sql)) {
            // Update session data
            $_SESSION['name'] = $name;
            
            $_SESSION['success_message'] = "Profile updated successfully.";
            header('Location: profile.php');
            exit();
        } else {
            $errors[] = "Error updating profile: " . mysqli_error($conn);
        }
    }
}
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h2><i class="fas fa-user-circle"></i> My Profile</h2>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <i class="fas fa-user-edit"></i> Edit Profile
            </div>
            <div class="card-body">
                <?php 
                // Display error messages if any
                if (isset($errors) && !empty($errors)) {
                    echo '<div class="alert alert-danger">';
                    echo '<ul class="mb-0">';
                    foreach ($errors as $error) {
                        echo '<li>' . $error . '</li>';
                    }
                    echo '</ul>';
                    echo '</div>';
                }
                
                // Display success message if any
                if (isset($_SESSION['success_message'])) {
                    echo '<div class="alert alert-success alert-dismissible fade show">';
                    echo $_SESSION['success_message'];
                    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                    echo '</div>';
                    unset($_SESSION['success_message']);
                }
                ?>
                
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                        <div class="form-text text-muted">Username cannot be changed.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <input type="text" class="form-control" id="role" value="<?php echo htmlspecialchars($user['role']); ?>" readonly>
                        <div class="form-text text-muted">Role cannot be changed.</div>
                    </div>
                    
                    <hr>
                    
                    <h5>Change Password</h5>
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password">
                        <div class="form-text text-muted">Leave blank if you don't want to change your password.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password">
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once $base_path . 'includes/footer.php'; ?>