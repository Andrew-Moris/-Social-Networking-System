<?php
session_start();
require_once '../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$current_user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    switch ($action) {
        case 'get_users':
            $stmt = $pdo->prepare("
                SELECT u.id, u.username, u.first_name, u.last_name, u.avatar_url,
                       us.is_online, us.last_seen,
                       (SELECT COUNT(*) FROM messages 
                        WHERE receiver_id = :current_user 
                        AND sender_id = u.id 
                        AND is_read = 0) as unread_count
                FROM users u
                LEFT JOIN user_status us ON u.id = us.user_id
                WHERE u.id != :current_user
                ORDER BY us.is_online DESC, us.last_seen DESC
            ");
            $stmt->execute(['current_user' => $current_user_id]);
            echo json_encode(['success' => true, 'users' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'get_messages':
            $other_user_id = $_GET['user_id'] ?? null;
            if (!$other_user_id) {
                throw new Exception('User ID is required');
            }

            $stmt = $pdo->prepare("
                SELECT m.*, 
                       u_sender.username as sender_username,
                       u_sender.avatar_url as sender_avatar
                FROM messages m
                JOIN users u_sender ON m.sender_id = u_sender.id
                WHERE (m.sender_id = :user1 AND m.receiver_id = :user2)
                   OR (m.sender_id = :user2 AND m.receiver_id = :user1)
                ORDER BY m.created_at ASC
            ");
            $stmt->execute([
                'user1' => $current_user_id,
                'user2' => $other_user_id
            ]);
            
            $mark_read = $pdo->prepare("
                UPDATE messages 
                SET is_read = 1 
                WHERE sender_id = :other_user 
                AND receiver_id = :current_user 
                AND is_read = 0
            ");
            $mark_read->execute([
                'other_user' => $other_user_id,
                'current_user' => $current_user_id
            ]);

            echo json_encode(['success' => true, 'messages' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'send_message':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            $data = json_decode(file_get_contents('php://input'), true);
            $receiver_id = $data['receiver_id'] ?? null;
            $content = $data['content'] ?? null;

            if (!$receiver_id || !$content) {
                throw new Exception('Missing required fields');
            }

            $stmt = $pdo->prepare("
                INSERT INTO messages (sender_id, receiver_id, content)
                VALUES (:sender_id, :receiver_id, :content)
            ");
            $stmt->execute([
                'sender_id' => $current_user_id,
                'receiver_id' => $receiver_id,
                'content' => $content
            ]);

            echo json_encode(['success' => true, 'message_id' => $pdo->lastInsertId()]);
            break;

        case 'delete_message':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            $data = json_decode(file_get_contents('php://input'), true);
            $message_id = $data['message_id'] ?? null;

            if (!$message_id) {
                throw new Exception('Message ID is required');
            }

            $stmt = $pdo->prepare("
                DELETE FROM messages 
                WHERE id = :message_id 
                AND (sender_id = :user_id OR receiver_id = :user_id)
            ");
            $stmt->execute([
                'message_id' => $message_id,
                'user_id' => $current_user_id
            ]);

            echo json_encode(['success' => true]);
            break;

        case 'update_status':
            $stmt = $pdo->prepare("
                INSERT INTO user_status (user_id, is_online, last_seen)
                VALUES (:user_id, 1, CURRENT_TIMESTAMP)
                ON DUPLICATE KEY UPDATE 
                    is_online = 1,
                    last_seen = CURRENT_TIMESTAMP
            ");
            $stmt->execute(['user_id' => $current_user_id]);
            echo json_encode(['success' => true]);
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} 