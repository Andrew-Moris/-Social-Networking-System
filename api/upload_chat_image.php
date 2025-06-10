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
    
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No image uploaded or upload error');
    }
    
    $file = $_FILES['image'];
    
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowed_types)) {
        throw new Exception('Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed');
    }
    
    $max_size = 5 * 1024 * 1024; 
    if ($file['size'] > $max_size) {
        throw new Exception('File too large. Maximum size is 5MB');
    }
    
    $upload_dir = __DIR__ . '/../uploads/chat/';
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            throw new Exception('Failed to create upload directory');
        }
        
        $htaccess_content = "Options -Indexes\n";
        $htaccess_content .= "Order deny,allow\n";
        $htaccess_content .= "Allow from all\n";
        $htaccess_content .= "<FilesMatch \"\\.(php|php3|php4|php5|phtml|pl|py|jsp|asp|sh|cgi)$\">\n";
        $htaccess_content .= "Order deny,allow\n";
        $htaccess_content .= "Deny from all\n";
        $htaccess_content .= "</FilesMatch>\n";
        file_put_contents($upload_dir . '.htaccess', $htaccess_content);
    }
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = 'chat_' . $current_user_id . '_' . time() . '_' . uniqid() . '.' . $file_extension;
    $file_path = $upload_dir . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        throw new Exception('Failed to save uploaded file');
    }
    
    if (extension_loaded('gd')) {
        try {
            $image_info = getimagesize($file_path);
            if ($image_info !== false) {
                $width = $image_info[0];
                $height = $image_info[1];
                $type = $image_info[2];
                
                $max_width = 800;
                $max_height = 600;
                
                if ($width > $max_width || $height > $max_height) {
                    $ratio = min($max_width / $width, $max_height / $height);
                    $new_width = (int)($width * $ratio);
                    $new_height = (int)($height * $ratio);
                    
                    $new_image = imagecreatetruecolor($new_width, $new_height);
                    
                    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
                        imagealphablending($new_image, false);
                        imagesavealpha($new_image, true);
                        $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
                        imagefilledrectangle($new_image, 0, 0, $new_width, $new_height, $transparent);
                    }
                    
                    switch ($type) {
                        case IMAGETYPE_JPEG:
                            $source = imagecreatefromjpeg($file_path);
                            break;
                        case IMAGETYPE_PNG:
                            $source = imagecreatefrompng($file_path);
                            break;
                        case IMAGETYPE_GIF:
                            $source = imagecreatefromgif($file_path);
                            break;
                        case IMAGETYPE_WEBP:
                            if (function_exists('imagecreatefromwebp')) {
                                $source = imagecreatefromwebp($file_path);
                            } else {
                                throw new Exception('WebP not supported');
                            }
                            break;
                        default:
                            throw new Exception('Unsupported image type');
                    }
                    
                    imagecopyresampled($new_image, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                    
                    switch ($type) {
                        case IMAGETYPE_JPEG:
                            imagejpeg($new_image, $file_path, 85);
                            break;
                        case IMAGETYPE_PNG:
                            imagepng($new_image, $file_path, 8);
                            break;
                        case IMAGETYPE_GIF:
                            imagegif($new_image, $file_path);
                            break;
                        case IMAGETYPE_WEBP:
                            if (function_exists('imagewebp')) {
                                imagewebp($new_image, $file_path, 85);
                            }
                            break;
                    }
                    
                    imagedestroy($source);
                    imagedestroy($new_image);
                }
            }
        } catch (Exception $e) {
            error_log("Image optimization failed: " . $e->getMessage());
        }
    }
    
    $image_url = 'uploads/chat/' . $filename;
    
    echo json_encode([
        'success' => true,
        'message' => 'Image uploaded successfully',
        'data' => [
            'image_url' => $image_url,
            'filename' => $filename
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 