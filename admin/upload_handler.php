<?php
session_start();
require_once '../includes/db_connect.php';

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

// CSRF protection
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

// Check if file was uploaded
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $error = 'Upload failed';
    
    if (isset($_FILES['file'])) {
        switch ($_FILES['file']['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $error = 'File size exceeds limit';
                break;
            case UPLOAD_ERR_PARTIAL:
                $error = 'File was only partially uploaded';
                break;
            case UPLOAD_ERR_NO_FILE:
                $error = 'No file was uploaded';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
            case UPLOAD_ERR_CANT_WRITE:
            case UPLOAD_ERR_EXTENSION:
                $error = 'Server error occurred';
                break;
        }
    }
    
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => $error]);
    exit;
}

// Get file info
$file = $_FILES['file'];
$fileName = basename($file['name']);
$fileSize = $file['size'];
$fileTmpPath = $file['tmp_name'];
$fileType = $file['type'];

// Validate file type
$fileType = strtolower($fileType);
$allowedTypes = [
    'image' => ['image/jpeg', 'image/png', 'image/gif'],
    'audio' => ['audio/mp3', 'audio/mpeg', 'audio/wav']
];

// Get upload type and folder
$uploadType = isset($_POST['type']) ? strtolower($_POST['type']) : '';
$contentType = isset($_POST['content_type']) ? strtolower($_POST['content_type']) : '';

if (!in_array($uploadType, ['image', 'audio'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Invalid upload type']);
    exit;
}

if (!in_array($contentType, ['flags', 'animals', 'jobs', 'stories'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Invalid content type']);
    exit;
}

// Check if file type is allowed
if (!in_array($fileType, $allowedTypes[$uploadType])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'File type not allowed']);
    exit;
}

// Check file size
$maxSizeBytes = ($uploadType === 'image') ? 2 * 1024 * 1024 : 5 * 1024 * 1024; // 2MB for images, 5MB for audio
if ($fileSize > $maxSizeBytes) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'File size exceeds limit']);
    exit;
}

// Create upload directory if it doesn't exist
$uploadDir = "../{$uploadType}s/{$contentType}/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Generate a safe filename
$fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
$newFileName = time() . '_' . md5(uniqid()) . '.' . $fileExtension;
$uploadPath = $uploadDir . $newFileName;
$dbPath = "{$uploadType}s/{$contentType}/{$newFileName}";

// Move the file
if (!move_uploaded_file($fileTmpPath, $uploadPath)) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Failed to save file']);
    exit;
}

// Return success response
echo json_encode([
    'success' => true,
    'filename' => $newFileName,
    'path' => $dbPath,
    'full_path' => $uploadPath
]);
exit; 