<?php
// Adjust path for includes
$base_path = '../../';
require_once $base_path . 'includes/config.php';
require_once $base_path . 'includes/functions.php';

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: " . $base_path . "login.php");
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = "Invalid request method.";
    header("Location: index.php");
    exit();
}

// Validate required fields
if (!isset($_POST['client_id']) || empty($_POST['client_id']) || 
    !isset($_POST['property_id']) || empty($_POST['property_id']) || 
    !isset($_POST['interest_level']) || empty($_POST['interest_level'])) {
    
    $_SESSION['error_message'] = "All required fields must be filled.";
    header("Location: index.php");
    exit();
}

// Sanitize inputs
$client_id = (int)$_POST['client_id'];
$property_id = (int)$_POST['property_id'];
$interest_level = escapeString($_POST['interest_level']);
$notes = isset($_POST['notes']) ? escapeString($_POST['notes']) : '';

// Check if client exists
$sql = "SELECT id FROM clients WHERE id = $client_id";
$result = executeQuery($sql);
if ($result->num_rows === 0) {
    $_SESSION['error_message'] = "Client not found.";
    header("Location: index.php");
    exit();
}

// Check if property exists
$sql = "SELECT id, title FROM properties WHERE id = $property_id";
$result = executeQuery($sql);
if ($result->num_rows === 0) {
    $_SESSION['error_message'] = "Property not found.";
    header("Location: view.php?id=$client_id");
    exit();
}
$property = $result->fetch_assoc();

// Check if interest already exists
$sql = "SELECT id FROM client_interests WHERE client_id = $client_id AND property_id = $property_id";
$result = executeQuery($sql);
if ($result->num_rows > 0) {
    $_SESSION['error_message'] = "This property interest already exists for this client.";
    header("Location: view.php?id=$client_id");
    exit();
}

// Insert interest
$date_added = date('Y-m-d H:i:s');
$sql = "INSERT INTO client_interests (client_id, property_id, interest_level, notes, date_added) 
        VALUES ($client_id, $property_id, '$interest_level', '$notes', '$date_added')";

if (executeQuery($sql)) {
    // Log the activity
    $activity_sql = "INSERT INTO activity_logs (user, activity_type, related_to, related_id, description) 
                    VALUES ('{$_SESSION['user_name']}', 'Add Interest', 'client', $client_id, 'Added interest in property: {$property['title']}')";
    executeQuery($activity_sql);
    
    $_SESSION['success_message'] = "Property interest added successfully.";
} else {
    $_SESSION['error_message'] = "Error adding property interest. Please try again.";
}

header("Location: view.php?id=$client_id");
exit();