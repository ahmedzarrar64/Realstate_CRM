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

$document_id = (int)$_GET['id'];
$client_id = (int)$_GET['client_id'];

// Verify the document belongs to the client
$sql = "SELECT id, document_name, file_path FROM documents WHERE id = $document_id AND client_id = $client_id";
$result = executeQuery($sql);

if ($result->num_rows === 0) {
    $_SESSION['error_message'] = "Document not found or does not belong to this client.";
    header("Location: view.php?id=$client_id");
    exit();
}

$document = $result->fetch_assoc();
$document_name = $document['document_name'];
$file_path = $document['file_path'];

// Delete the document record
$sql = "DELETE FROM documents WHERE id = $document_id";

if (executeQuery($sql)) {
    // Delete the actual file
    $file_to_delete = $base_path . 'uploads/documents/' . $file_path;
    if (file_exists($file_to_delete)) {
        unlink($file_to_delete);
    }
    
    // Log the activity
    $activity_sql = "INSERT INTO activity_logs (user, activity_type, related_to, related_id, description) 
                    VALUES ('{$_SESSION['user_name']}', 'Delete Document', 'client', $client_id, 'Deleted document: $document_name')";
    executeQuery($activity_sql);
    
    $_SESSION['success_message'] = "Document deleted successfully.";
} else {
    $_SESSION['error_message'] = "Error deleting document. Please try again.";
}

header("Location: view.php?id=$client_id");
exit();