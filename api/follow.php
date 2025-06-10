<?php


session_start();
require_once '../config.php';
require_once '../functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'غير مصرح']);
    exit;
}

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
    
    $data = json_decode(file_get_contents('php://input'), true);
    $user_id = isset($data['user_id']) ? (int)$data['user_id'] : 0;
    
    if ($user_id === 0 || $user_id === $_SESSION['user_id']) {
        http_response_code(400);
        echo json_encode(['error' => 'معرف المستخدم غير صالح']);
        exit;
    }
    
    $check_stmt = $pdo->prepare("SELECT id FROM followers WHERE follower_id = ? AND followed_id = ?");
    $check_stmt->execute([$_SESSION['user_id'], $user_id]);
    $existing = $check_stmt->fetch();
    
    error_log("Checking follow status - Current user: {$_SESSION['user_id']}, Target user: $user_id, Already following: " . ($existing ? 'Yes' : 'No'));
    
    if ($existing) {
        $delete_stmt = $pdo->prepare("DELETE FROM followers WHERE follower_id = ? AND followed_id = ?");
        $delete_stmt->execute([$_SESSION['user_id'], $user_id]);
        $is_following = false;
        
        error_log("Unfollowed user $user_id by user {$_SESSION['user_id']}");
    } else {
        $insert_stmt = $pdo->prepare("INSERT INTO followers (follower_id, followed_id, created_at) VALUES (?, ?, NOW())");
        $insert_stmt->execute([$_SESSION['user_id'], $user_id]);
        $is_following = true;
        
        error_log("Followed user $user_id by user {$_SESSION['user_id']}");
    }
    
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE followed_id = ?");
    $count_stmt->execute([$user_id]);
    $followers_count = $count_stmt->fetchColumn();
    
    
    echo json_encode([
        'success' => true,
        'is_following' => $is_following,
        'followers_count' => $followers_count
    ]);
    
} catch (PDOException $e) {
    error_log("Error in follow.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'حدث خطأ في قاعدة البيانات']);
}
