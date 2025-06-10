<?php


session_start();
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$current_user_id = $_SESSION['user_id'];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method allowed');
    }
    
    $upload_dir = __DIR__ . '/../uploads/chat/';
    
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            throw new Exception('Failed to create upload directory');
        }
    }
    
    $uploaded_file = null;
    $file_type = '';
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploaded_file = $_FILES['image'];
        $file_type = 'image';
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; 
    }
    elseif (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
        $uploaded_file = $_FILES['video'];
        $file_type = 'video';
        $allowed_types = ['video/mp4', 'video/avi', 'video/mov', 'video/wmv', 'video/webm'];
        $max_size = 50 * 1024 * 1024; 
    }
    elseif (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $uploaded_file = $_FILES['file'];
        $file_type = 'file';
        $allowed_types = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain',
            'application/zip',
            'application/x-rar-compressed'
        ];
        $max_size = 10 * 1024 * 1024; 
    }
    else {
        throw new Exception('No valid file uploaded');
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $actual_mime = finfo_file($finfo, $uploaded_file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($actual_mime, $allowed_types)) {
        throw new Exception('Invalid file type. Allowed types: ' . implode(', ', $allowed_types));
    }
    
    if ($uploaded_file['size'] > $max_size) {
        $max_mb = $max_size / (1024 * 1024);
        throw new Exception("File too large. Maximum size: {$max_mb}MB");
    }
    
    if ($uploaded_file['size'] == 0) {
        throw new Exception('File is empty');
    }
    
    $extension = pathinfo($uploaded_file['name'], PATHINFO_EXTENSION);
    $filename = 'chat_' . $file_type . '_' . $current_user_id . '_' . time() . '_' . uniqid() . '.' . strtolower($extension);
    $filepath = $upload_dir . $filename;
    $relative_path = 'uploads/chat/' . $filename;
    
    if (move_uploaded_file($uploaded_file['tmp_name'], $filepath)) {
        echo json_encode([
            'success' => true,
            'message' => ucfirst($file_type) . ' uploaded successfully',
            'url' => $relative_path,
            'type' => $file_type,
            'filename' => $filename
        ]);
    } else {
        throw new Exception('Failed to save uploaded file');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 