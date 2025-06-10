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
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Only GET method allowed');
    }
    
    $search_term = isset($_GET['q']) ? trim($_GET['q']) : '';
    
    if (empty($search_term)) {
        throw new Exception('Search term is required');
    }
    
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $search_stmt = $pdo->prepare("
        SELECT u.id, u.username, u.first_name, u.last_name, u.avatar_url,
               CASE WHEN f.follower_id IS NOT NULL THEN 1 ELSE 0 END as is_following,
               CASE WHEN f2.followed_id IS NOT NULL THEN 1 ELSE 0 END as follows_me
        FROM users u
        LEFT JOIN followers f ON u.id = f.followed_id AND f.follower_id = ?
        LEFT JOIN followers f2 ON u.id = f2.follower_id AND f2.followed_id = ?
        WHERE u.id != ? AND (
            u.username LIKE ? OR 
            u.first_name LIKE ? OR 
            u.last_name LIKE ? OR
            CONCAT(u.first_name, ' ', u.last_name) LIKE ?
        )
        ORDER BY 
            CASE WHEN f.follower_id IS NOT NULL THEN 1 ELSE 0 END DESC,
            CASE WHEN f2.followed_id IS NOT NULL THEN 1 ELSE 0 END DESC,
            u.username ASC
        LIMIT 20
    ");
    
    $search_pattern = '%' . $search_term . '%';
    $search_stmt->execute([
        $current_user_id, $current_user_id, $current_user_id,
        $search_pattern, $search_pattern, $search_pattern, $search_pattern
    ]);
    
    $users = $search_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'users' => $users,
            'search_term' => $search_term
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 