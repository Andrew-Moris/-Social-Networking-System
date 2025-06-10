<?php

require_once 'api_functions.php';
require_once dirname(__DIR__) . '/models/Post.php';

$user = verify_api_auth();

if (!$user) {
    send_json_response(['success' => false, 'message' => 'غير مصرح. يرجى تسجيل الدخول.'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response(['success' => false, 'message' => 'طريقة الطلب غير مدعومة.'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['post_id']) || !isset($input['action'])) {
    send_json_response(['success' => false, 'message' => 'بيانات غير كاملة.'], 400);
}

$post_id = $input['post_id'];
$action = $input['action'];
$is_like = ($action === 'like');

try {
    require_once dirname(__DIR__) . '/config.php';
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
    
    $postModel = new Post($pdo);
    
    $result = $postModel->likeOrDislike($post_id, $user['id'], $is_like);
    
    if ($result) {
        $post = $postModel->getById($post_id);
        
        send_json_response([
            'success' => true,
            'message' => $is_like ? 'تم الإعجاب بالمنشور بنجاح.' : 'تم إلغاء الإعجاب بالمنشور بنجاح.',
            'likes_count' => $post['likes_count'] ?? 0,
            'dislikes_count' => $post['dislikes_count'] ?? 0,
            'user_reaction' => $is_like ? 1 : 0
        ]);
    } else {
        send_json_response(['success' => false, 'message' => 'حدث خطأ أثناء معالجة طلبك.'], 500);
    }
    
} catch (PDOException $e) {
    if (function_exists('log_error')) {
        log_error("Like post error: " . $e->getMessage());
    }
    
    send_json_response(['success' => false, 'message' => 'حدث خطأ أثناء معالجة طلبك.'], 500);
} catch (Exception $e) {
    if (function_exists('log_error')) {
        log_error("Like post error: " . $e->getMessage());
    }
    
    send_json_response(['success' => false, 'message' => 'حدث خطأ أثناء معالجة طلبك.'], 500);
}
