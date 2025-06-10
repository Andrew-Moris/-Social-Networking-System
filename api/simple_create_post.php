<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '../logs/simple_post_errors.log');
error_reporting(E_ALL);

error_log("\n\n=== بدء طلب إنشاء منشور جديد (النسخة المبسطة) ===\n");
error_log("URL: {$_SERVER['REQUEST_URI']}");
error_log("Method: {$_SERVER['REQUEST_METHOD']}");
error_log("POST params: " . json_encode(array_keys($_POST)));
error_log("FILES params: " . json_encode(array_keys($_FILES)));

session_start();
require_once '../config.php';

$user_id = $_SESSION['user_id'] ?? 4;
error_log("User ID: {$user_id}");

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Invalid request method: {$_SERVER['REQUEST_METHOD']}");
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'طريقة الطلب غير مدعومة.']);
    exit;
}

if (empty($_POST['content']) && empty($_FILES['image']['name']) && empty($_FILES['video']['name'])) {
    error_log("No content provided");
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'يجب إدخال نص للمنشور أو إضافة صورة أو فيديو.']);
    exit;
}

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $content = trim($_POST['content'] ?? '');
    $media_url = null;
    $media_type = null;
    
    if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        error_log("Processing image upload");
        
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', ''];
        if (!in_array($_FILES['image']['type'], $allowed_types) && !empty($_FILES['image']['type'])) {
            error_log("Invalid image type: {$_FILES['image']['type']}");
            throw new Exception('نوع الصورة غير مدعوم. الأنواع المدعومة هي: JPEG, PNG, GIF, WEBP');
        }
        
        if ($_FILES['image']['size'] > 10 * 1024 * 1024) {
            error_log("Image too large: {$_FILES['image']['size']} bytes");
            throw new Exception('حجم الصورة كبير جداً. الحد الأقصى هو 10MB');
        }
        
        $upload_dir = '../uploads/posts/';
        if (!is_dir($upload_dir)) {
            if (!is_dir('../uploads/')) {
                if (!@mkdir('../uploads/', 0777, true)) {
                    error_log("Failed to create uploads directory");
                } else {
                    error_log("Created uploads directory");
                }
            }
            if (!@mkdir($upload_dir, 0777, true)) {
                error_log("Failed to create posts directory");
            } else {
                error_log("Created posts directory");
            }
        }
        
        $file_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        if (empty($file_ext)) $file_ext = 'jpg';
        $file_name = time() . '_' . rand(1000, 9999) . '.' . $file_ext;
        $file_path = $upload_dir . $file_name;
        
        error_log("Uploading image to: {$file_path}");
        
        if (copy($_FILES['image']['tmp_name'], $file_path) || move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
            $media_url = 'uploads/posts/' . $file_name;
            $media_type = 'image';
            error_log("Image uploaded successfully: {$media_url}");
        } else {
            $error = error_get_last();
            error_log("Failed to upload image: " . ($error ? json_encode($error) : 'Unknown error'));
            throw new Exception('فشل في تحميل الصورة. يرجى المحاولة مرة أخرى');
        }
    }
    
    if (!empty($_FILES['video']['name']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
        error_log("Processing video upload");
        
        $allowed_types = ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime', 'video/x-ms-wmv', 'video/avi', 'video/mpeg', ''];
        if (!in_array($_FILES['video']['type'], $allowed_types) && !empty($_FILES['video']['type'])) {
            error_log("Invalid video type: {$_FILES['video']['type']}");
            throw new Exception('نوع الفيديو غير مدعوم. الأنواع المدعومة هي: MP4, WebM, OGG, MOV');
        }
        
        if ($_FILES['video']['size'] > 50 * 1024 * 1024) {
            error_log("Video too large: {$_FILES['video']['size']} bytes");
            throw new Exception('حجم الفيديو كبير جداً. الحد الأقصى هو 50MB');
        }
        
        $upload_dir = '../uploads/posts/';
        if (!is_dir($upload_dir)) {
            if (!is_dir('../uploads/')) {
                if (!@mkdir('../uploads/', 0777, true)) {
                    error_log("Failed to create uploads directory");
                } else {
                    error_log("Created uploads directory");
                }
            }
            if (!@mkdir($upload_dir, 0777, true)) {
                error_log("Failed to create posts directory");
            } else {
                error_log("Created posts directory");
            }
        }
        
        $file_ext = pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION);
        if (empty($file_ext)) $file_ext = 'mp4';
        $file_name = time() . '_' . rand(1000, 9999) . '.' . $file_ext;
        $file_path = $upload_dir . $file_name;
        
        error_log("Uploading video to: {$file_path}");
        
        if (copy($_FILES['video']['tmp_name'], $file_path) || move_uploaded_file($_FILES['video']['tmp_name'], $file_path)) {
            $media_url = 'uploads/posts/' . $file_name;
            $media_type = 'video';
            error_log("Video uploaded successfully: {$media_url}");
        } else {
            $error = error_get_last();
            error_log("Failed to upload video: " . ($error ? json_encode($error) : 'Unknown error'));
            throw new Exception('فشل في تحميل الفيديو. يرجى المحاولة مرة أخرى');
        }
    }
    
    error_log("Inserting post into database");
    $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, media_url, media_type, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$user_id, $content, $media_url, $media_type]);
    
    $post_id = $pdo->lastInsertId();
    error_log("Post inserted with ID: {$post_id}");
    
    if (!$post_id) {
        throw new Exception('فشل في إنشاء المنشور');
    }
    
    $stmt = $pdo->prepare("SELECT p.*, u.username, u.first_name, u.last_name, u.avatar_url FROM posts p JOIN users u ON p.user_id = u.id WHERE p.id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$post) {
        throw new Exception('فشل في استرجاع بيانات المنشور الجديد');
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true, 
        'message' => 'تم إنشاء المنشور بنجاح',
        'post' => $post
    ]);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'فشل في إنشاء المنشور: ' . $e->getMessage()]);
}
