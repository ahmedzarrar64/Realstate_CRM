<?php
// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_password = '';
$db_name = 'realstate_crm';

// Create database connection
$conn = new mysqli($db_host, $db_user, $db_password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if database exists, if not create it
$check_db = "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$db_name'";
$result = $conn->query($check_db);

if ($result->num_rows == 0) {
    // Create database
    $create_db = "CREATE DATABASE $db_name";
    if ($conn->query($create_db) === TRUE) {
        // Database created successfully
    } else {
        die("Error creating database: " . $conn->error);
    }
}

// Select the database
$conn->select_db($db_name);

// Check if tables exist, if not create them
$tables = [
    'owners' => "CREATE TABLE owners (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        email VARCHAR(100),
        address TEXT,
        preferred_contact VARCHAR(20) DEFAULT 'Phone',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    'properties' => "CREATE TABLE properties (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        owner_id INT(11) NOT NULL,
        property_type VARCHAR(50) NOT NULL,
        address TEXT NOT NULL,
        area DECIMAL(10,2),
        price DECIMAL(12,2),
        status VARCHAR(20) DEFAULT 'Available',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (owner_id) REFERENCES owners(id) ON DELETE CASCADE
    )",
    
    'contact_logs' => "CREATE TABLE contact_logs (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        owner_id INT(11),
        property_id INT(11),
        contact_type VARCHAR(50) NOT NULL,
        contact_date DATETIME NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (owner_id) REFERENCES owners(id) ON DELETE CASCADE,
        FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
    )",
    
    'tasks' => "CREATE TABLE tasks (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        owner_id INT(11),
        property_id INT(11),
        task_description TEXT NOT NULL,
        due_date DATE NOT NULL,
        status VARCHAR(20) DEFAULT 'Pending',
        completion_date DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (owner_id) REFERENCES owners(id) ON DELETE CASCADE,
        FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
    )"
];

foreach ($tables as $table_name => $create_table_sql) {
    $check_table = "SHOW TABLES LIKE '$table_name'";
    $result = $conn->query($check_table);
    
    if ($result->num_rows == 0) {
        // Table doesn't exist, create it
        if ($conn->query($create_table_sql) !== TRUE) {
            die("Error creating table $table_name: " . $conn->error);
        }
    }
}

// Function to execute queries
function executeQuery($sql) {
    global $conn;
    $result = $conn->query($sql);
    if (!$result) {
        die("Query failed: " . $conn->error);
    }
    return $result;
}

// Function to get last inserted ID
function getLastInsertId() {
    global $conn;
    return $conn->insert_id;
}

// Function to escape strings for SQL injection prevention
function escapeString($string) {
    global $conn;
    return $conn->real_escape_string($string);
}

// Include notifications setup script
require_once __DIR__ . '/setup_notifications.php';
?>