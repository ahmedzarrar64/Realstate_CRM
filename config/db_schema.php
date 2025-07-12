<?php
require_once 'db_config.php';

// Create owners table
$sql_owners = "CREATE TABLE IF NOT EXISTS owners (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL,
    address TEXT NOT NULL,
    preferred_contact ENUM('Phone', 'Email', 'WhatsApp') NOT NULL DEFAULT 'Phone',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

executeQuery($sql_owners);

// Create properties table
$sql_properties = "CREATE TABLE IF NOT EXISTS properties (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    owner_id INT(11) NOT NULL,
    property_type VARCHAR(50) NOT NULL,
    address TEXT NOT NULL,
    area DECIMAL(10,2) NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    status ENUM('Available', 'Under Negotiation', 'Sold') NOT NULL DEFAULT 'Available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES owners(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

executeQuery($sql_properties);

// Create contact_logs table
$sql_contact_logs = "CREATE TABLE IF NOT EXISTS contact_logs (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    owner_id INT(11) NOT NULL,
    property_id INT(11) NULL,
    contact_date DATETIME NOT NULL,
    contact_type ENUM('Call', 'WhatsApp', 'Email', 'Visit') NOT NULL,
    description TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES owners(id) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

executeQuery($sql_contact_logs);

// Create tasks table
$sql_tasks = "CREATE TABLE IF NOT EXISTS tasks (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    task_description TEXT NOT NULL,
    assigned_to VARCHAR(100) NOT NULL,
    due_date DATE NOT NULL,
    status ENUM('Pending', 'Done') NOT NULL DEFAULT 'Pending',
    owner_id INT(11) NULL,
    property_id INT(11) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES owners(id) ON DELETE SET NULL,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

executeQuery($sql_tasks);

// Create users table (for optional login system)
$sql_users = "CREATE TABLE IF NOT EXISTS users (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('Admin', 'Agent') NOT NULL DEFAULT 'Agent',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

executeQuery($sql_users);

// Insert default admin user
$default_password = password_hash('admin123', PASSWORD_DEFAULT);
$sql_default_user = "INSERT INTO users (username, password, role) 
                    SELECT 'admin', '$default_password', 'Admin' 
                    FROM dual 
                    WHERE NOT EXISTS (SELECT * FROM users WHERE username = 'admin')";

executeQuery($sql_default_user);

echo "Database schema created successfully!";
?>