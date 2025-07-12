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

// Check if ID and client_id are provided
if (!isset($_GET['id']) || empty($_GET['id']) || !isset($_GET['client_id']) || empty($_GET['client_id'])) {
    $_SESSION['error_message'] = "Required parameters are missing.";
    header("Location: index.php");
    exit();
}

$interest_id = (int)$_GET['id'];
$client_id = (int)$_GET['client_id'];

// Verify the interest belongs to the client
$sql = "SELECT ci.id, p.title 
        FROM client_interests ci 
        JOIN properties p ON ci.property_id = p.id 
        WHERE ci.id = $interest_id AND ci.client_id = $client_id";
$result = executeQuery($sql);

if ($result->num_rows === 0) {
    $_SESSION['error_message'] = "Interest not found or does not belong to this client.";
    header("Location: view.php?id=$client_id");
    exit();
}

$interest = $result->fetch_assoc();
$property_title = $interest['title'];

// Delete the interest
$sql = "DELETE FROM client_interests WHERE id = $interest_id";

if (executeQuery($sql)) {
    // Log the activity
    $activity_sql = "INSERT INTO activity_logs (user, activity_type, related_to, related_id, description) 
                    VALUES ('{$_SESSION['user_name']}', 'Delete Interest', 'client', $client_id, 'Removed interest in property: $property_title')";
    executeQuery($activity_sql);
    
    $_SESSION['success_message'] = "Property interest removed successfully.";
} else {
    $_SESSION['error_message'] = "Error removing property interest. Please try again.";
}

header("Location: view.php?id=$client_id");
exit();