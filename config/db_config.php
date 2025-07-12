<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Change to your MySQL username
define('DB_PASS', ''); // Change to your MySQL password
define('DB_NAME', 'realstate_crm');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if ($conn->query($sql) !== TRUE) {
    die("Error creating database: " . $conn->error);
}

// Select the database
$conn->select_db(DB_NAME);

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
?>