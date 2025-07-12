<?php
// Adjust path for includes
$base_path = '../';
require_once $base_path . 'includes/header.php';
require_once $base_path . 'includes/functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

// Process user actions (add, edit, delete)
$action = $_GET['action'] ?? '';
$user_id = $_GET['id'] ?? 0;

// Handle user deletion
if ($action === 'delete' && $user_id > 0) {
    // Prevent deleting own account
    if ($user_id == $_SESSION['user_id']) {
        $_SESSION['error_message'] = "You cannot delete your own account.";
    } else {
        $sql = "DELETE FROM users WHERE id = $user_id";
        if (executeQuery($sql)) {
            $_SESSION['success_message'] = "User deleted successfully.";
        } else {
            $_SESSION['error_message'] = "Error deleting user: " . mysqli_error($conn);
        }
    }
    header('Location: users.php');
    exit();
}

// Handle form submissions for add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';
    $edit_id = $_POST['edit_id'] ?? 0;
    
    $errors = [];
    
    // Validate inputs
    if (empty($username)) {
        $errors[] = "Username is required.";
    }
    if (empty($name)) {
        $errors[] = "Name is required.";
    }
    if ($edit_id == 0 && empty($password)) {
        $errors[] = "Password is required for new users.";
    }
    if (!empty($password) && strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }
    
    // Check if username already exists
    $check_sql = "SELECT id FROM users WHERE username = '$username' AND id != $edit_id";
    $check_result = executeQuery($check_sql);
    if ($check_result->num_rows > 0) {
        $errors[] = "Username already exists. Please choose another one.";
    }
    
    // If no errors, add or update user
    if (empty($errors)) {
        if ($edit_id > 0) {
            // Update existing user
            $sql = "UPDATE users SET username = '$username', name = '$name', email = '$email', role = '$role'";
            
            // Update password if provided
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql .= ", password = '$hashed_password'";
            }
            
            $sql .= " WHERE id = $edit_id";
            
            if (executeQuery($sql)) {
                $_SESSION['success_message'] = "User updated successfully.";
                header('Location: users.php');
                exit();
            } else {
                $errors[] = "Error updating user: " . mysqli_error($conn);
            }
        } else {
            // Add new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (username, password, name, email, role) 
                    VALUES ('$username', '$hashed_password', '$name', '$email', '$role')";
            
            if (executeQuery($sql)) {
                $_SESSION['success_message'] = "User added successfully.";
                header('Location: users.php');
                exit();
            } else {
                $errors[] = "Error adding user: " . mysqli_error($conn);
            }
        }
    }
}

// Get user data for editing
$edit_user = null;
if ($action === 'edit' && $user_id > 0) {
    $sql = "SELECT * FROM users WHERE id = $user_id";
    $result = executeQuery($sql);
    if ($result->num_rows > 0) {
        $edit_user = $result->fetch_assoc();
    }
}

// Get all users
$sql = "SELECT * FROM users ORDER BY username";
$users = executeQuery($sql);
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h2><i class="fas fa-users"></i> User Management</h2>
    </div>
    <div class="col-md-4 text-end">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="fas fa-user-plus"></i> Add New User
        </button>
    </div>
</div>

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

// Display error message if any
if (isset($_SESSION['error_message'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show">';
    echo $_SESSION['error_message'];
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
    unset($_SESSION['error_message']);
}
?>

<div class="card">
    <div class="card-header bg-primary text-white">
        <i class="fas fa-users"></i> System Users
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($users->num_rows > 0): ?>
                        <?php while ($user = $users->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email'] ?? ''); ?></td>
                                <td>
                                    <span class="badge <?php echo ($user['role'] === 'admin') ? 'bg-danger' : 'bg-info'; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($user['created_at']); ?></td>
                                <td>
                                    <a href="users.php?action=edit&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <a href="users.php?action=delete&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this user?');">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">No users found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addUserModalLabel">
                    <?php echo ($edit_user) ? 'Edit User' : 'Add New User'; ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <?php if ($edit_user): ?>
                        <input type="hidden" name="edit_id" value="<?php echo $edit_user['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($edit_user['username'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($edit_user['name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($edit_user['email'] ?? ''); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">
                            <?php echo ($edit_user) ? 'Password (leave blank to keep current)' : 'Password'; ?>
                        </label>
                        <input type="password" class="form-control" id="password" name="password" <?php echo ($edit_user) ? '' : 'required'; ?>>
                        <?php if ($edit_user): ?>
                            <div class="form-text text-muted">Leave blank to keep current password.</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select" id="role" name="role">
                            <option value="user" <?php echo (isset($edit_user) && $edit_user['role'] === 'user') ? 'selected' : ''; ?>>User</option>
                            <option value="admin" <?php echo (isset($edit_user) && $edit_user['role'] === 'admin') ? 'selected' : ''; ?>>Administrator</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <?php echo ($edit_user) ? 'Update User' : 'Add User'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php 
// Auto-open modal for edit
if ($edit_user): 
?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var myModal = new bootstrap.Modal(document.getElementById('addUserModal'));
        myModal.show();
    });
</script>
<?php endif; ?>

<?php require_once $base_path . 'includes/footer.php'; ?>