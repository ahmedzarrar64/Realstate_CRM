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

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "Client ID is required.";
    header("Location: index.php");
    exit();
}

$client_id = (int)$_GET['id'];

// Get client details for logging
$sql = "SELECT name FROM clients WHERE id = $client_id";
$result = executeQuery($sql);

if ($result->num_rows === 0) {
    $_SESSION['error_message'] = "Client not found.";
    header("Location: index.php");
    exit();
}

$client = $result->fetch_assoc();
$client_name = $client['name'];

// Begin transaction
$conn->begin_transaction();

try {
    // Delete related records first to maintain referential integrity
    
    // Delete client interests
    $sql = "DELETE FROM client_interests WHERE client_id = $client_id";
    executeQuery($sql);
    
    // Delete documents
    // First get document file paths to delete the actual files
    $sql = "SELECT file_path FROM documents WHERE client_id = $client_id";
    $result = executeQuery($sql);
    $documents = [];
    while ($row = $result->fetch_assoc()) {
        $documents[] = $row['file_path'];
    }
    
    // Delete document records from database
    $sql = "DELETE FROM documents WHERE client_id = $client_id";
    executeQuery($sql);
    
    // Delete activity logs
    $sql = "DELETE FROM activity_logs WHERE related_to = 'client' AND related_id = $client_id";
    executeQuery($sql);
    
    // Delete the client
    $sql = "DELETE FROM clients WHERE id = $client_id";
    executeQuery($sql);
    
    // Commit transaction
    $conn->commit();
    
    // Delete actual document files
    foreach ($documents as $document) {
        $file_path = $base_path . 'uploads/documents/' . $document;
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    
    // Log the activity in admin activity log
    $activity_sql = "INSERT INTO activity_logs (user, activity_type, related_to, related_id, description) 
                    VALUES ('{$_SESSION['user_name']}', 'Delete', 'client', $client_id, 'Deleted client: $client_name')";
    executeQuery($activity_sql);
    
    $_SESSION['success_message'] = "Client deleted successfully.";
    header("Location: index.php");
    exit();
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    $_SESSION['error_message'] = "Error deleting client: " . $e->getMessage();
    header("Location: view.php?id=$client_id");
    exit();
}