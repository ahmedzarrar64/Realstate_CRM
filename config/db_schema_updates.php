<?php
require_once 'db_config.php';

// Create clients table
$sql_clients = "CREATE TABLE IF NOT EXISTS clients (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL,
    address TEXT NOT NULL,
    client_type ENUM('Buyer', 'Tenant', 'Both') NOT NULL DEFAULT 'Buyer',
    preferred_contact ENUM('Phone', 'Email', 'WhatsApp') NOT NULL DEFAULT 'Phone',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

executeQuery($sql_clients);

// Create client_interests table to track which properties clients are interested in
$sql_client_interests = "CREATE TABLE IF NOT EXISTS client_interests (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    client_id INT(11) NOT NULL,
    property_id INT(11) NOT NULL,
    interest_level ENUM('Low', 'Medium', 'High') NOT NULL DEFAULT 'Medium',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

executeQuery($sql_client_interests);

// Create documents table for document uploads
$sql_documents = "CREATE TABLE IF NOT EXISTS documents (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    document_type ENUM('CNIC', 'Agreement', 'Floor Plan', 'Ownership Paper', 'Other') NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_size INT(11) NOT NULL,
    file_type VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) NULL,
    entity_id INT(11) NULL,
    description TEXT NULL,
    owner_id INT(11) NULL,
    property_id INT(11) NULL,
    client_id INT(11) NULL,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (owner_id) REFERENCES owners(id) ON DELETE SET NULL,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE SET NULL,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

executeQuery($sql_documents);

// Create property_sales table for tracking sales and payments
$sql_property_sales = "CREATE TABLE IF NOT EXISTS property_sales (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    property_id INT(11) NOT NULL,
    client_id INT(11) NOT NULL,
    sale_date DATE NOT NULL,
    total_amount DECIMAL(12,2) NOT NULL,
    token_amount DECIMAL(12,2) DEFAULT 0,
    token_date DATE NULL,
    advance_amount DECIMAL(12,2) DEFAULT 0,
    advance_date DATE NULL,
    remaining_amount DECIMAL(12,2) DEFAULT 0,
    payment_status ENUM('Token Received', 'Advance Received', 'Fully Paid', 'Pending') NOT NULL DEFAULT 'Pending',
    sale_status ENUM('In Progress', 'Completed', 'Cancelled') NOT NULL DEFAULT 'In Progress',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

executeQuery($sql_property_sales);

// Create commissions table for tracking agent commissions
$sql_commissions = "CREATE TABLE IF NOT EXISTS commissions (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    sale_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    percentage DECIMAL(5,2) NOT NULL,
    status ENUM('Pending', 'Paid') NOT NULL DEFAULT 'Pending',
    payment_date DATE NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (sale_id) REFERENCES property_sales(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

executeQuery($sql_commissions);

// Create notifications table
$sql_notifications = "CREATE TABLE IF NOT EXISTS notifications (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('Task', 'Payment', 'System', 'Other') NOT NULL DEFAULT 'System',
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    related_id INT(11) NULL,
    related_type VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

executeQuery($sql_notifications);

// Update users table to add viewer role
$sql_update_users = "ALTER TABLE users MODIFY COLUMN role ENUM('Admin', 'Agent', 'Viewer') NOT NULL DEFAULT 'Agent'";
executeQuery($sql_update_users);

// Add property_images table
$sql_property_images = "CREATE TABLE IF NOT EXISTS property_images (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    property_id INT(11) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

executeQuery($sql_property_images);

// Add location coordinates to properties table for Google Maps integration
$sql_add_coordinates = "ALTER TABLE properties 
    ADD COLUMN latitude DECIMAL(10,8) NULL,
    ADD COLUMN longitude DECIMAL(11,8) NULL";

executeQuery($sql_add_coordinates);

// Add CNIC field to owners table
$sql_add_cnic = "ALTER TABLE owners ADD COLUMN cnic VARCHAR(20) NULL";
executeQuery($sql_add_cnic);

// Add profile_picture field to owners table
$sql_add_profile_pic = "ALTER TABLE owners ADD COLUMN profile_picture VARCHAR(255) NULL";
executeQuery($sql_add_profile_pic);

// Add activity_logs table
$sql_activity_logs = "CREATE TABLE IF NOT EXISTS activity_logs (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    action VARCHAR(255) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT(11) NOT NULL,
    details TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

executeQuery($sql_activity_logs);

echo "Database schema updates completed successfully!";
?>