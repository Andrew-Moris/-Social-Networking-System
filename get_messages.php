<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

session_start();
require_once 'config.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول أولاً']);
    exit;
}

if (!isset($_GET['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'معرف المستخدم مطلوب']);
    exit;
}

$current_user_id = $_SESSION['user_id'];
$target_user_id = (int)$_GET['user_id'];
$last_id = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;

try {
    $requestKey = "get_messages_{$current_user_id}_{$target_user_id}_{$last_id}";
    if (isset($_SESSION['last_request'][$requestKey]) && (time() - $_SESSION['last_request'][$requestKey]) < 2) {
        echo json_encode(['success' => true, 'messages' => [], 'cached' => true]);
        exit;
    }
    
    $_SESSION['last_request'][$requestKey] = time();
    
    global $pdo;
    
    if (!isset($pdo)) {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    
    $query = "SELECT m.*, u.username as sender_username
             FROM messages m
             JOIN users u ON m.sender_id = u.id
             WHERE ((m.sender_id = :current_user_id AND m.receiver_id = :target_user_id)
                OR (m.sender_id = :target_user_id2 AND m.receiver_id = :current_user_id2))
             AND m.id > :last_id
             ORDER BY m.id ASC
             LIMIT 50";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ':current_user_id' => $current_user_id,
        ':target_user_id' => $target_user_id,
        ':target_user_id2' => $target_user_id,
        ':current_user_id2' => $current_user_id,
        ':last_id' => $last_id
    ]);
    
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($messages as &$message) {
        if (isset($message['created_at'])) {
            $message['timestamp'] = date('H:i', strtotime($message['created_at']));
        }
        $message['id'] = (int)$message['id']; 
    }
    
    echo json_encode([
        'success' => true,
        'messages' => $messages,
        'count' => count($messages)
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_messages.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'خطأ في الاتصال بقاعدة البيانات'
    ]);
}