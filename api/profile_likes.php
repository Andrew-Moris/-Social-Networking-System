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
    
    $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : $_SESSION['user_id'];
    
    $stmt = $pdo->prepare("
        SELECT p.content as post_content, l.created_at as liked_at
        FROM likes l
        JOIN posts p ON l.post_id = p.id
        WHERE l.user_id = ?
        ORDER BY l.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $likes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($likes);
    
} catch (PDOException $e) {
    error_log("Error in profile_likes.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'حدث خطأ في قاعدة البيانات']);
} 