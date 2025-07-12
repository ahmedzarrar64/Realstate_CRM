<?php
// Adjust path for includes
$base_path = '../';
require_once $base_path . 'includes/header.php';
require_once $base_path . 'includes/functions.php';
require_once $base_path . 'includes/config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

$backup_message = '';
$backup_status = '';

// Process backup request
if (isset($_POST['create_backup'])) {
    // Set database credentials
    $db_host = 'localhost';
    $db_user = 'root';
    $db_password = '';
    $db_name = 'realstate_crm';
    
    // Generate backup filename with timestamp
    $backup_dir = '../backups/';
    
    // Create backup directory if it doesn't exist
    if (!file_exists($backup_dir)) {
        mkdir($backup_dir, 0777, true);
    }
    
    $timestamp = date('Y-m-d_H-i-s');
    $backup_file = $backup_dir . 'backup_' . $timestamp . '.sql';
    
    // Command to create backup using mysqldump
    $command = "mysqldump --host=$db_host --user=$db_user --password=$db_password $db_name > \"$backup_file\"";
    
    // Execute the command
    $output = [];
    $return_var = 0;
    exec($command, $output, $return_var);
    
    if ($return_var === 0) {
        $backup_status = 'success';
        $backup_message = "Database backup created successfully: " . basename($backup_file);
    } else {
        $backup_status = 'error';
        $backup_message = "Error creating database backup. Please check if mysqldump is available and your database credentials are correct.";
    }
}

// Get list of existing backups
$backup_dir = '../backups/';
$backups = [];

if (file_exists($backup_dir)) {
    $files = scandir($backup_dir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            $backups[] = [
                'filename' => $file,
                'size' => filesize($backup_dir . $file),
                'date' => filemtime($backup_dir . $file)
            ];
        }
    }
    
    // Sort backups by date (newest first)
    usort($backups, function($a, $b) {
        return $b['date'] - $a['date'];
    });
}

// Handle backup deletion
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $delete_file = $_GET['delete'];
    $delete_path = $backup_dir . basename($delete_file);
    
    if (file_exists($delete_path) && unlink($delete_path)) {
        $_SESSION['success_message'] = "Backup file deleted successfully.";
    } else {
        $_SESSION['error_message'] = "Error deleting backup file.";
    }
    
    header('Location: backup.php');
    exit();
}

// Handle backup download
if (isset($_GET['download']) && !empty($_GET['download'])) {
    $download_file = $_GET['download'];
    $download_path = $backup_dir . basename($download_file);
    
    if (file_exists($download_path)) {
        // Set headers for download
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($download_path) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($download_path));
        readfile($download_path);
        exit;
    } else {
        $_SESSION['error_message'] = "Backup file not found.";
        header('Location: backup.php');
        exit();
    }
}
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h2><i class="fas fa-database"></i> Database Backup</h2>
    </div>
</div>

<?php 
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

// Display backup message if any
if (!empty($backup_message)) {
    echo '<div class="alert alert-' . ($backup_status === 'success' ? 'success' : 'danger') . ' alert-dismissible fade show">';
    echo $backup_message;
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
}
?>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <i class="fas fa-plus-circle"></i> Create New Backup
            </div>
            <div class="card-body">
                <p>Create a backup of your database. This will export all tables and data into a SQL file that can be used to restore your database if needed.</p>
                <form method="post" action="">
                    <button type="submit" name="create_backup" class="btn btn-primary">
                        <i class="fas fa-download"></i> Create Backup
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <i class="fas fa-list"></i> Available Backups
            </div>
            <div class="card-body">
                <?php if (empty($backups)): ?>
                    <div class="alert alert-info">
                        No backup files found. Use the "Create Backup" button to create your first database backup.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Filename</th>
                                    <th>Size</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($backups as $backup): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($backup['filename']); ?></td>
                                        <td><?php echo formatFileSize($backup['size']); ?></td>
                                        <td><?php echo date('Y-m-d H:i:s', $backup['date']); ?></td>
                                        <td>
                                            <a href="backup.php?download=<?php echo urlencode($backup['filename']); ?>" class="btn btn-sm btn-success">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            <a href="backup.php?delete=<?php echo urlencode($backup['filename']); ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this backup file?');">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php 
// Function to format file size
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}
?>

<?php require_once $base_path . 'includes/footer.php'; ?>