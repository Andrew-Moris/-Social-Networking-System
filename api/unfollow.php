<?php

require_once '../config.php';
require_once '../functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'يجب تسجيل الدخول أولاً']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'معرف المستخدم مطلوب']);
    exit;
}

$follower_id = (int)$_SESSION['user_id'];
$followed_id = (int)$input['user_id'];

if ($follower_id == $followed_id) {
    echo json_encode(['status' => 'error', 'message' => 'لا يمكنك إلغاء متابعة نفسك']);
    exit;
}

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
    
    $stmt = $pdo->prepare('SELECT id FROM users WHERE id = ?');
    $stmt->execute([$followed_id]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'المستخدم غير موجود']);
        exit;
    }
    
    $check_stmt = $pdo->prepare('SELECT id FROM follows WHERE follower_id = ? AND followed_id = ?');
    $check_stmt->execute([$follower_id, $followed_id]);
    
    if (!$check_stmt->fetch()) {
        $alt_check_stmt = $pdo->prepare('SELECT id FROM followers WHERE follower_id = ? AND followed_id = ?');
        $alt_check_stmt->execute([$follower_id, $followed_id]);
        
        if (!$alt_check_stmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'أنت لا تتابع هذا المستخدم حاليًا']);
            exit;
        } else {
            $delete_stmt = $pdo->prepare('DELETE FROM followers WHERE follower_id = ? AND followed_id = ?');
            $result = $delete_stmt->execute([$follower_id, $followed_id]);
            
            echo json_encode([
                'status' => 'success', 
                'message' => 'تم إلغاء المتابعة بنجاح',
                'followers_count' => 0
            ]);
            exit;
        }
    }
    
    $delete_stmt = $pdo->prepare('DELETE FROM follows WHERE follower_id = ? AND followed_id = ?');
    $result = $delete_stmt->execute([$follower_id, $followed_id]);
    
    if ($result) {
        $count_stmt = $pdo->prepare('SELECT COUNT(*) FROM follows WHERE followed_id = ?');
        $count_stmt->execute([$followed_id]);
        $followers_count = $count_stmt->fetchColumn();
        
        echo json_encode([
            'status' => 'success', 
            'message' => 'تم إلغاء المتابعة بنجاح',
            'followers_count' => $followers_count
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'حدث خطأ أثناء محاولة إلغاء المتابعة']);
    }
    
} catch (PDOException $e) {
    error_log('Unfollow Error: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'حدث خطأ في قاعدة البيانات']);
}