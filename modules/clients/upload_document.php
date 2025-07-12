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
    !isset($_POST['document_name']) || empty($_POST['document_name']) || 
    !isset($_POST['document_type']) || empty($_POST['document_type']) || 
    !isset($_FILES['document_file']) || $_FILES['document_file']['error'] !== 0) {
    
    $_SESSION['error_message'] = "All required fields must be filled and a file must be uploaded.";
    header("Location: index.php");
    exit();
}

// Sanitize inputs
$client_id = (int)$_POST['client_id'];
$document_name = escapeString($_POST['document_name']);
$document_type = escapeString($_POST['document_type']);

// Check if client exists
$sql = "SELECT id FROM clients WHERE id = $client_id";
$result = executeQuery($sql);
if ($result->num_rows === 0) {
    $_SESSION['error_message'] = "Client not found.";
    header("Location: index.php");
    exit();
}

// Validate file
$file = $_FILES['document_file'];
$file_name = $file['name'];
$file_tmp = $file['tmp_name'];
$file_size = $file['size'];
$file_error = $file['error'];

// Get file extension
$file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

// Allowed extensions
$allowed_ext = array('pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png');

// Validate file extension
if (!in_array($file_ext, $allowed_ext)) {
    $_SESSION['error_message'] = "Invalid file type. Allowed types: PDF, DOC, DOCX, JPG, PNG.";
    header("Location: view.php?id=$client_id");
    exit();
}

// Validate file size (5MB max)
if ($file_size > 5242880) {
    $_SESSION['error_message'] = "File is too large. Maximum size is 5MB.";
    header("Location: view.php?id=$client_id");
    exit();
}

// Create uploads directory if it doesn't exist
$upload_dir = $base_path . 'uploads/documents/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Generate unique filename
$new_file_name = uniqid() . '.' . $file_ext;
$upload_path = $upload_dir . $new_file_name;

// Upload file
if (move_uploaded_file($file_tmp, $upload_path)) {
    // Insert document record
    $upload_date = date('Y-m-d H:i:s');
    $sql = "INSERT INTO documents (client_id, document_name, document_type, file_path, upload_date) 
            VALUES ($client_id, '$document_name', '$document_type', '$new_file_name', '$upload_date')";
    
    if (executeQuery($sql)) {
        // Log the activity
        $activity_sql = "INSERT INTO activity_logs (user, activity_type, related_to, related_id, description) 
                        VALUES ('{$_SESSION['user_name']}', 'Upload Document', 'client', $client_id, 'Uploaded document: $document_name')";
        executeQuery($activity_sql);
        
        $_SESSION['success_message'] = "Document uploaded successfully.";
    } else {
        // Delete the uploaded file if database insert fails
        unlink($upload_path);
        $_SESSION['error_message'] = "Error saving document information. Please try again.";
    }
} else {
    $_SESSION['error_message'] = "Error uploading file. Please try again.";
}

header("Location: view.php?id=$client_id");
exit();