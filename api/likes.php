<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        $user_id = authenticateRequest();
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['post_id'])) {
            jsonError('Post ID is required');
        }

        $post_id = (int)$data['post_id'];
        
        $stmt = $mysqli->prepare("
            SELECT p.id, p.user_id 
            FROM posts p
            WHERE p.id = ? AND (
                p.is_private = 0 OR 
                p.user_id = ? OR
                EXISTS (
                    SELECT 1 FROM friendships f 
                    WHERE ((f.sender_id = ? AND f.receiver_id = p.user_id) OR
                          (f.sender_id = p.user_id AND f.receiver_id = ?))
                    AND f.status = 'accepted'
                )
            )
        ");
        $stmt->bind_param("iiii", $post_id, $user_id, $user_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $post = $result->fetch_assoc();
        
        if (!$post) {
            jsonError('Post not found or not accessible', 404);
        }
        
        $stmt = $mysqli->prepare("SELECT 1 FROM likes WHERE user_id = ? AND post_id = ?");
        $stmt->bind_param("ii", $user_id, $post_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->fetch_assoc()) {
            jsonError('Post already liked');
        }
        
        $query = "INSERT INTO likes (user_id, post_id) VALUES (?, ?)";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("ii", $user_id, $post_id);
        
        if ($stmt->execute()) {
            if ($post['user_id'] != $user_id) {
                $stmt = $mysqli->prepare("
                    INSERT INTO notifications (user_id, from_user_id, type, reference_id)
                    VALUES (?, ?, 'like', ?)
                ");
                $stmt->bind_param("iii", $post['user_id'], $user_id, $post_id);
                $stmt->execute();
            }
            
            jsonResponse(['message' => 'Post liked successfully']);
        } else {
            jsonError('Failed to like post');
        }
        break;

    case 'DELETE':
        $user_id = authenticateRequest();
        $post_id = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;
        
        if (!$post_id) {
            jsonError('Post ID is required');
        }
        
        $stmt = $mysqli->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
        $stmt->bind_param("ii", $user_id, $post_id);
        
        if ($stmt->execute()) {
            jsonResponse(['message' => 'Post unliked successfully']);
        } else {
            jsonError('Failed to unlike post');
        }
        break;

    case 'GET':
        $user_id = authenticateRequest();
        $post_id = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $offset = ($page - 1) * $limit;
        
        if (!$post_id) {
            jsonError('Post ID is required');
        }
        
        $stmt = $mysqli->prepare("
            SELECT p.id 
            FROM posts p
            WHERE p.id = ? AND (
                p.is_private = 0 OR 
                p.user_id = ? OR
                EXISTS (
                    SELECT 1 FROM friendships f 
                    WHERE ((f.sender_id = ? AND f.receiver_id = p.user_id) OR
                          (f.sender_id = p.user_id AND f.receiver_id = ?))
                    AND f.status = 'accepted'
                )
            )
        ");
        $stmt->bind_param("iiii", $post_id, $user_id, $user_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if (!$result->fetch_assoc()) {
            jsonError('Post not found or not accessible', 404);
        }
        
        $query = "SELECT l.*, u.username, u.profile_picture
                 FROM likes l
                 JOIN users u ON l.user_id = u.id
                 WHERE l.post_id = ?
                 ORDER BY l.created_at DESC
                 LIMIT ? OFFSET ?";
        
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("iii", $post_id, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $likes = [];
        while ($like = $result->fetch_assoc()) {
            $likes[] = $like;
        }
        
        jsonResponse($likes);
        break;

    default:
        jsonError('Method not allowed', 405);
        break;
}
?> 