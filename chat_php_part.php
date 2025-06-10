<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$current_user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT u.id, u.username, u.display_name, u.avatar, u.last_active, 
                        (SELECT MAX(m.created_at) FROM messages m WHERE (m.sender_id = u.id AND m.receiver_id = :current_user) OR (m.sender_id = :current_user AND m.receiver_id = u.id)) as last_message_time,
                        (SELECT COUNT(*) FROM messages WHERE sender_id = u.id AND receiver_id = :current_user AND is_read = 0) as unread_count
                        FROM users u 
                        WHERE u.id != :current_user
                        ORDER BY last_message_time DESC");
    $stmt->bindParam(':current_user', $current_user_id, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching users: " . $e->getMessage());
    $users = [];
}

function isUserOnline($lastActive) {
    if (!$lastActive) return false;
    $lastActiveTime = strtotime($lastActive);
    $currentTime = time();
    return ($currentTime - $lastActiveTime) < 300; 
}
?>
