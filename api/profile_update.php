<?php

require_once 'api_functions.php';
require_once dirname(__DIR__) . '/controllers/ProfileController.php';

$user = verify_api_auth();

if (!$user) {
    send_json_response(['success' => false, 'message' => 'غير مصرح. يرجى تسجيل الدخول.'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response(['success' => false, 'message' => 'طريقة الطلب غير مدعومة.'], 405);
}

if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    send_json_response(['success' => false, 'message' => 'توكن CSRF غير صالح.'], 403);
}

try {
    require_once dirname(__DIR__) . '/config.php';
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
    
    $controller = new ProfileController($pdo);
    
    $result = $controller->updateProfile(
        $user['id'],
        $_POST,
        $_FILES
    );
    
    send_json_response($result);
    
} catch (PDOException $e) {
    if (function_exists('log_error')) {
        log_error("Profile update error: " . $e->getMessage());
    }
    
    send_json_response(['success' => false, 'message' => 'حدث خطأ أثناء تحديث الملف الشخصي.'], 500);
} catch (Exception $e) {
    if (function_exists('log_error')) {
        log_error("Profile update error: " . $e->getMessage());
    }
    
    send_json_response(['success' => false, 'message' => 'حدث خطأ أثناء تحديث الملف الشخصي.'], 500);
}
