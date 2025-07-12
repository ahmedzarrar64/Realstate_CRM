<?php
// Adjust path for includes
$base_path = '../../';
require_once $base_path . 'includes/header.php';
require_once $base_path . 'includes/functions.php';

// Check if user is admin
// This is a simple check - you might want to implement proper role-based access control
$is_admin = true; // For now, allow all logged-in users to access

if (!$is_admin) {
    // Redirect non-admin users
    header('Location: ' . $base_path . 'index.php');
    exit;
}

$error = '';
$success = '';

// Get current settings
$settings = [];
$settings_sql = "SELECT * FROM system_settings";
$settings_result = $conn->query($settings_sql);

while ($setting = $settings_result->fetch_assoc()) {
    $settings[$setting['setting_key']] = $setting['setting_value'];
}

// Default values if not set
$default_settings = [
    'company_name' => 'Real Estate CRM',
    'company_logo' => '',
    'company_address' => '',
    'company_phone' => '',
    'company_email' => '',
    'default_task_duration' => '7',
    'default_followup_days' => '3',
    'currency_symbol' => '$',
    'date_format' => 'Y-m-d',
    'time_format' => 'H:i',
    'default_commission_percentage' => '2.5',
    'enable_notifications' => '1',
    'system_theme' => 'light',
    'pagination_limit' => '10'
];

// Merge defaults with database settings
foreach ($default_settings as $key => $value) {
    if (!isset($settings[$key])) {
        $settings[$key] = $value;
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Process text settings
        $text_settings = [
            'company_name',
            'company_address',
            'company_phone',
            'company_email',
            'default_task_duration',
            'default_followup_days',
            'currency_symbol',
            'date_format',
            'time_format',
            'default_commission_percentage',
            'pagination_limit'
        ];
        
        foreach ($text_settings as $key) {
            if (isset($_POST[$key])) {
                $value = trim($_POST[$key]);
                
                // Update or insert setting
                $upsert_sql = "INSERT INTO system_settings (setting_key, setting_value) 
                              VALUES (?, ?) 
                              ON DUPLICATE KEY UPDATE setting_value = ?";
                $upsert_stmt = $conn->prepare($upsert_sql);
                $upsert_stmt->bind_param("sss", $key, $value, $value);
                $upsert_stmt->execute();
                $upsert_stmt->close();
                
                // Update local settings array
                $settings[$key] = $value;
            }
        }
        
        // Process checkbox settings
        $checkbox_settings = [
            'enable_notifications',
        ];
        
        foreach ($checkbox_settings as $key) {
            $value = isset($_POST[$key]) ? '1' : '0';
            
            // Update or insert setting
            $upsert_sql = "INSERT INTO system_settings (setting_key, setting_value) 
                          VALUES (?, ?) 
                          ON DUPLICATE KEY UPDATE setting_value = ?";
            $upsert_stmt = $conn->prepare($upsert_sql);
            $upsert_stmt->bind_param("sss", $key, $value, $value);
            $upsert_stmt->execute();
            $upsert_stmt->close();
            
            // Update local settings array
            $settings[$key] = $value;
        }
        
        // Process select settings
        $select_settings = [
            'system_theme',
        ];
        
        foreach ($select_settings as $key) {
            if (isset($_POST[$key])) {
                $value = $_POST[$key];
                
                // Update or insert setting
                $upsert_sql = "INSERT INTO system_settings (setting_key, setting_value) 
                              VALUES (?, ?) 
                              ON DUPLICATE KEY UPDATE setting_value = ?";
                $upsert_stmt = $conn->prepare($upsert_sql);
                $upsert_stmt->bind_param("sss", $key, $value, $value);
                $upsert_stmt->execute();
                $upsert_stmt->close();
                
                // Update local settings array
                $settings[$key] = $value;
            }
        }
        
        // Process logo upload
        if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['company_logo'];
            $file_name = $file['name'];
            $file_tmp = $file['tmp_name'];
            $file_size = $file['size'];
            
            // Check file type
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'svg'];
            
            if (in_array($file_ext, $allowed_extensions)) {
                // Check if uploads directory exists, if not create it
                $upload_dir = $base_path . 'uploads/system/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Delete old logo if exists
                if (!empty($settings['company_logo']) && file_exists($base_path . $settings['company_logo'])) {
                    unlink($base_path . $settings['company_logo']);
                }
                
                // Generate unique filename
                $new_file_name = 'company_logo_' . uniqid() . '.' . $file_ext;
                $file_path = 'uploads/system/' . $new_file_name;
                $full_path = $base_path . $file_path;
                
                // Move uploaded file
                if (move_uploaded_file($file_tmp, $full_path)) {
                    // Update or insert setting
                    $upsert_sql = "INSERT INTO system_settings (setting_key, setting_value) 
                                  VALUES (?, ?) 
                                  ON DUPLICATE KEY UPDATE setting_value = ?";
                    $upsert_stmt = $conn->prepare($upsert_sql);
                    $key = 'company_logo';
                    $upsert_stmt->bind_param("sss", $key, $file_path, $file_path);
                    $upsert_stmt->execute();
                    $upsert_stmt->close();
                    
                    // Update local settings array
                    $settings['company_logo'] = $file_path;
                } else {
                    throw new Exception("Failed to upload logo. Please try again.");
                }
            } else {
                throw new Exception("Invalid logo file type. Allowed types: " . implode(', ', $allowed_extensions));
            }
        }
        
        // Commit transaction
        $conn->commit();
        $success = "Settings updated successfully!";
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $error = "Error: " . $e->getMessage();
    }
}
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo $base_path; ?>index.php">Dashboard</a></li>
        <li class="breadcrumb-item active">System Settings</li>
    </ol>
</nav>

<div class="row mb-4">
    <div class="col">
        <h2><i class="fas fa-cogs"></i> System Settings</h2>
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
        <h5 class="mb-0">System Configuration</h5>
    </div>
    <div class="card-body">
        <form method="post" enctype="multipart/form-data">
            <ul class="nav nav-tabs mb-4" id="settingsTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="company-tab" data-bs-toggle="tab" data-bs-target="#company" type="button" role="tab" aria-controls="company" aria-selected="true">
                        <i class="fas fa-building"></i> Company
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="defaults-tab" data-bs-toggle="tab" data-bs-target="#defaults" type="button" role="tab" aria-controls="defaults" aria-selected="false">
                        <i class="fas fa-sliders-h"></i> Defaults
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="appearance-tab" data-bs-toggle="tab" data-bs-target="#appearance" type="button" role="tab" aria-controls="appearance" aria-selected="false">
                        <i class="fas fa-palette"></i> Appearance
                    </button>
                </li>
            </ul>
            
            <div class="tab-content" id="settingsTabsContent">
                <!-- Company Settings Tab -->
                <div class="tab-pane fade show active" id="company" role="tabpanel" aria-labelledby="company-tab">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="company_name" class="form-label">Company Name</label>
                                <input type="text" class="form-control" id="company_name" name="company_name" value="<?php echo htmlspecialchars($settings['company_name']); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="company_address" class="form-label">Company Address</label>
                                <textarea class="form-control" id="company_address" name="company_address" rows="3"><?php echo htmlspecialchars($settings['company_address']); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="company_phone" class="form-label">Company Phone</label>
                                <input type="text" class="form-control" id="company_phone" name="company_phone" value="<?php echo htmlspecialchars($settings['company_phone']); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="company_email" class="form-label">Company Email</label>
                                <input type="email" class="form-control" id="company_email" name="company_email" value="<?php echo htmlspecialchars($settings['company_email']); ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="company_logo" class="form-label">Company Logo</label>
                                <input type="file" class="form-control" id="company_logo" name="company_logo" accept="image/*">
                                <div class="form-text">Recommended size: 200x50 pixels. Allowed formats: JPG, PNG, GIF, SVG.</div>
                            </div>
                            
                            <?php if (!empty($settings['company_logo'])): ?>
                            <div class="mb-3">
                                <label class="form-label">Current Logo</label>
                                <div class="border p-3 text-center">
                                    <img src="<?php echo $base_path . htmlspecialchars($settings['company_logo']); ?>" alt="Company Logo" class="img-fluid" style="max-height: 100px;">
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Defaults Settings Tab -->
                <div class="tab-pane fade" id="defaults" role="tabpanel" aria-labelledby="defaults-tab">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="default_task_duration" class="form-label">Default Task Duration (days)</label>
                                <input type="number" class="form-control" id="default_task_duration" name="default_task_duration" min="1" value="<?php echo htmlspecialchars($settings['default_task_duration']); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="default_followup_days" class="form-label">Default Follow-up Days</label>
                                <input type="number" class="form-control" id="default_followup_days" name="default_followup_days" min="1" value="<?php echo htmlspecialchars($settings['default_followup_days']); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="default_commission_percentage" class="form-label">Default Commission Percentage</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="default_commission_percentage" name="default_commission_percentage" min="0" step="0.01" value="<?php echo htmlspecialchars($settings['default_commission_percentage']); ?>">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="currency_symbol" class="form-label">Currency Symbol</label>
                                <input type="text" class="form-control" id="currency_symbol" name="currency_symbol" value="<?php echo htmlspecialchars($settings['currency_symbol']); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="date_format" class="form-label">Date Format</label>
                                <select class="form-select" id="date_format" name="date_format">
                                    <option value="Y-m-d" <?php echo $settings['date_format'] === 'Y-m-d' ? 'selected' : ''; ?>>YYYY-MM-DD</option>
                                    <option value="m/d/Y" <?php echo $settings['date_format'] === 'm/d/Y' ? 'selected' : ''; ?>>MM/DD/YYYY</option>
                                    <option value="d/m/Y" <?php echo $settings['date_format'] === 'd/m/Y' ? 'selected' : ''; ?>>DD/MM/YYYY</option>
                                    <option value="d.m.Y" <?php echo $settings['date_format'] === 'd.m.Y' ? 'selected' : ''; ?>>DD.MM.YYYY</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="time_format" class="form-label">Time Format</label>
                                <select class="form-select" id="time_format" name="time_format">
                                    <option value="H:i" <?php echo $settings['time_format'] === 'H:i' ? 'selected' : ''; ?>>24-hour (14:30)</option>
                                    <option value="h:i A" <?php echo $settings['time_format'] === 'h:i A' ? 'selected' : ''; ?>>12-hour (02:30 PM)</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="pagination_limit" class="form-label">Items Per Page</label>
                                <select class="form-select" id="pagination_limit" name="pagination_limit">
                                    <option value="10" <?php echo $settings['pagination_limit'] === '10' ? 'selected' : ''; ?>>10</option>
                                    <option value="25" <?php echo $settings['pagination_limit'] === '25' ? 'selected' : ''; ?>>25</option>
                                    <option value="50" <?php echo $settings['pagination_limit'] === '50' ? 'selected' : ''; ?>>50</option>
                                    <option value="100" <?php echo $settings['pagination_limit'] === '100' ? 'selected' : ''; ?>>100</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="enable_notifications" name="enable_notifications" value="1" <?php echo $settings['enable_notifications'] === '1' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="enable_notifications">Enable System Notifications</label>
                    </div>
                </div>
                
                <!-- Appearance Settings Tab -->
                <div class="tab-pane fade" id="appearance" role="tabpanel" aria-labelledby="appearance-tab">
                    <div class="mb-3">
                        <label for="system_theme" class="form-label">System Theme</label>
                        <select class="form-select" id="system_theme" name="system_theme">
                            <option value="light" <?php echo $settings['system_theme'] === 'light' ? 'selected' : ''; ?>>Light</option>
                            <option value="dark" <?php echo $settings['system_theme'] === 'dark' ? 'selected' : ''; ?>>Dark</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Settings
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once $base_path . 'includes/footer.php'; ?>