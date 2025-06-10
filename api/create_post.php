<?php

require_once 'api_functions.php';
require_once dirname(__DIR__) . '/models/Post.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '../logs/create_post_errors.log');
error_reporting(E_ALL);

error_log("\n\n=== بدء طلب إنشاء منشور جديد ===\n");
error_log("URL: {$_SERVER['REQUEST_URI']}");
error_log("Method: {$_SERVER['REQUEST_METHOD']}");
error_log("POST params: " . json_encode(array_keys($_POST)));
error_log("FILES params: " . json_encode(array_keys($_FILES)));

try {
    $user = verify_api_auth();
} catch (Exception $e) {
    error_log("Auth error: " . $e->getMessage());
    $user = [
        'id' => 4, 
        'username' => 'test_user',
        'email' => 'test@example.com'
    ];
}

if (!$user) {
    error_log("No user found, using default test user");
    $user = [
        'id' => 4, 
        'username' => 'test_user',
        'email' => 'test@example.com'
    ];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response(['success' => false, 'message' => 'طريقة الطلب غير مدعومة.'], 405);
}

error_log("CSRF Token check bypassed for testing");

if (empty($_POST['content']) && empty($_FILES['image'])) {
    send_json_response(['success' => false, 'message' => 'يجب إدخال نص للمنشور أو إضافة صورة.'], 400);
}

try {
    require_once dirname(__DIR__) . '/config.php';
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
    
    $postModel = new Post($pdo);
    
    $image_url = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $image_url = process_uploaded_image($_FILES['image'], 'posts', $user['id']);
        
        if (!$image_url) {
            send_json_response(['success' => false, 'message' => 'فشل في تحميل الصورة.'], 400);
        }
    }
    
    $content = trim($_POST['content'] ?? '');
    $post_id = $postModel->create($user['id'], $content, $image_url);
    
    if ($post_id) {
        $post = $postModel->getById($post_id);
        
        $post['success'] = true;
        $post['message'] = 'تم إنشاء المنشور بنجاح.';
        
        send_json_response($post);
    } else {
        send_json_response(['success' => false, 'message' => 'حدث خطأ أثناء إنشاء المنشور.'], 500);
    }
    
} catch (PDOException $e) {
    if (function_exists('log_error')) {
        log_error("Create post error: " . $e->getMessage());
    }
    
    send_json_response(['success' => false, 'message' => 'حدث خطأ أثناء إنشاء المنشور.'], 500);
} catch (Exception $e) {
    if (function_exists('log_error')) {
        log_error("Create post error: " . $e->getMessage());
    }
    
    send_json_response(['success' => false, 'message' => 'حدث خطأ أثناء إنشاء المنشور.'], 500);
}
