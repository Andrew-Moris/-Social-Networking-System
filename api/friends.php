<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        $user_id = authenticateRequest();
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['receiver_id'])) {
            jsonError('Receiver ID is required');
        }

        $receiver_id = (int)$data['receiver_id'];
        
        $stmt = $mysqli->prepare("
            SELECT id, status 
            FROM friendships 
            WHERE (sender_id = ? AND receiver_id = ?) OR
                  (sender_id = ? AND receiver_id = ?)
        ");
        $stmt->bind_param("iiii", $user_id, $receiver_id, $receiver_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $existing = $result->fetch_assoc();
        
        if ($existing) {
            if ($existing['status'] === 'pending') {
                jsonError('Friend request already sent');
            } elseif ($existing['status'] === 'accepted') {
                jsonError('Already friends');
            } elseif ($existing['status'] === 'blocked') {
                jsonError('Cannot send friend request');
            }
        }
        
        $query = "INSERT INTO friendships (sender_id, receiver_id) VALUES (?, ?)";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("ii", $user_id, $receiver_id);
        
        if ($stmt->execute()) {
            $stmt = $mysqli->prepare("
                INSERT INTO notifications (user_id, from_user_id, type)
                VALUES (?, ?, 'friend_request')
            ");
            $stmt->bind_param("ii", $receiver_id, $user_id);
            $stmt->execute();
            
            jsonResponse(['message' => 'Friend request sent successfully']);
        } else {
            jsonError('Failed to send friend request');
        }
        break;

    case 'GET':
        $user_id = authenticateRequest();
        $type = isset($_GET['type']) ? $_GET['type'] : 'friends';
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $offset = ($page - 1) * $limit;
        
        switch ($type) {
            case 'friends':
                $query = "SELECT u.id, u.username, u.profile_picture, u.last_active,
                                f.created_at as friendship_date
                         FROM friendships f
                         JOIN users u ON (
                             CASE 
                                 WHEN f.sender_id = ? THEN f.receiver_id
                                 ELSE f.sender_id
                             END = u.id
                         )
                         WHERE (f.sender_id = ? OR f.receiver_id = ?)
                         AND f.status = 'accepted'
                         ORDER BY u.username
                         LIMIT ? OFFSET ?";
                $stmt = $mysqli->prepare($query);
                $stmt->bind_param("iiiii", $user_id, $user_id, $user_id, $limit, $offset);
                break;
                
            case 'requests':
                $query = "SELECT u.id, u.username, u.profile_picture, f.created_at as request_date
                         FROM friendships f
                         JOIN users u ON f.sender_id = u.id
                         WHERE f.receiver_id = ? AND f.status = 'pending'
                         ORDER BY f.created_at DESC
                         LIMIT ? OFFSET ?";
                $stmt = $mysqli->prepare($query);
                $stmt->bind_param("iii", $user_id, $limit, $offset);
                break;
                
            default:
                jsonError('Invalid type parameter');
                break;
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $friends = [];
        while ($friend = $result->fetch_assoc()) {
            $friends[] = $friend;
        }
        
        jsonResponse($friends);
        break;

    case 'PUT':
        $user_id = authenticateRequest();
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['request_id']) || !isset($data['action'])) {
            jsonError('Request ID and action are required');
        }

        $request_id = (int)$data['request_id'];
        $action = $data['action'];
        
        if (!in_array($action, ['accept', 'reject'])) {
            jsonError('Invalid action');
        }
        
        $stmt = $mysqli->prepare("
            SELECT sender_id 
            FROM friendships 
            WHERE id = ? AND receiver_id = ? AND status = 'pending'
        ");
        $stmt->bind_param("ii", $request_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $request = $result->fetch_assoc();
        
        if (!$request) {
            jsonError('Friend request not found', 404);
        }
        
        $status = $action === 'accept' ? 'accepted' : 'rejected';
        $stmt = $mysqli->prepare("UPDATE friendships SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $request_id);
        
        if ($stmt->execute()) {
            if ($action === 'accept') {
                $stmt = $mysqli->prepare("
                    INSERT INTO notifications (user_id, from_user_id, type)
                    VALUES (?, ?, 'friend_accepted')
                ");
                $stmt->bind_param("ii", $request['sender_id'], $user_id);
                $stmt->execute();
            }
            
            jsonResponse(['message' => 'Friend request ' . $action . 'ed successfully']);
        } else {
            jsonError('Failed to ' . $action . ' friend request');
        }
        break;

    case 'DELETE':
        $user_id = authenticateRequest();
        $friendship_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if (!$friendship_id) {
            jsonError('Friendship ID is required');
        }
        
        $stmt = $mysqli->prepare("
            SELECT sender_id, receiver_id, status 
            FROM friendships 
            WHERE id = ? AND (sender_id = ? OR receiver_id = ?)
        ");
        $stmt->bind_param("iii", $friendship_id, $user_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $friendship = $result->fetch_assoc();
        
        if (!$friendship) {
            jsonError('Friendship not found', 404);
        }
        
        $stmt = $mysqli->prepare("DELETE FROM friendships WHERE id = ?");
        $stmt->bind_param("i", $friendship_id);
        
        if ($stmt->execute()) {
            jsonResponse(['message' => 'Friendship removed successfully']);
        } else {
            jsonError('Failed to remove friendship');
        }
        break;

    default:
        jsonError('Method not allowed', 405);
        break;
}
?> 