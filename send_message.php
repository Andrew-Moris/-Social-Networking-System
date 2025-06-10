<?php

session_start();
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

require_once 'config.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'يجب تسجيل الدخول أولاً']);
    exit;
}

if (!isset($_POST['message']) || empty(trim($_POST['message']))) {
    echo json_encode(['success' => false, 'error' => 'الرسالة فارغة']);
    exit;
}

$message = trim($_POST['message']);
$userId = $_SESSION['user_id'];
$receiver_id = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;

if (!$receiver_id) {
    echo json_encode(['success' => false, 'error' => 'معرف المستلم غير صالح']);
    exit;
}

try {
    global $pdo;
    
    if (!isset($pdo)) {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM messages 
        WHERE sender_id = :user_id 
        AND receiver_id = :receiver_id 
        AND content = :message 
        AND created_at > DATE_SUB(NOW(), INTERVAL 2 SECOND)
    ");
    
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':receiver_id', $receiver_id);
    $stmt->bindParam(':message', $message);
    $stmt->execute();
    
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'error' => 'لا يمكن إرسال نفس الرسالة مرتين متتاليتين']);
        exit;
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO messages (sender_id, receiver_id, content, created_at) 
        VALUES (:user_id, :receiver_id, :message, NOW())
    ");
    
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':receiver_id', $receiver_id);
    $stmt->bindParam(':message', $message);
    
    if ($stmt->execute()) {
        if (function_exists('updateUserActivity')) {
            updateUserActivity($userId);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'تم إرسال الرسالة بنجاح',
            'message_id' => $pdo->lastInsertId()
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'فشل في إرسال الرسالة']);
    }
    
} catch(PDOException $e) {
    error_log("Error in send_message.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'خطأ في قاعدة البيانات']);
}