<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $user_id = authenticateRequest();
        $query = isset($_GET['q']) ? trim($_GET['q']) : '';
        $type = isset($_GET['type']) ? $_GET['type'] : 'all';
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $offset = ($page - 1) * $limit;
        
        if (empty($query)) {
            jsonError('Search query is required');
        }
        
        $search_term = '%' . $mysqli->real_escape_string($query) . '%';
        $results = [];
        
        switch ($type) {
            case 'users':
                $sql = "SELECT id, username, profile_picture, bio, 
                              (SELECT COUNT(*) FROM friendships 
                               WHERE ((sender_id = ? AND receiver_id = users.id) OR 
                                     (sender_id = users.id AND receiver_id = ?))
                               AND status = 'accepted') as is_friend
                       FROM users 
                       WHERE (username LIKE ? OR first_name LIKE ? OR last_name LIKE ?)
                       AND id != ?
                       ORDER BY username
                       LIMIT ? OFFSET ?";
                
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param("iissssii", 
                    $user_id, $user_id, $search_term, $search_term, $search_term, 
                    $user_id, $limit, $offset
                );
                $stmt->execute();
                $result = $stmt->get_result();
                
                while ($user = $result->fetch_assoc()) {
                    $results[] = $user;
                }
                break;
                
            case 'posts':
                $sql = "SELECT p.*, u.username, u.profile_picture,
                              (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
                              (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count,
                              EXISTS(SELECT 1 FROM likes WHERE post_id = p.id AND user_id = ?) as is_liked
                       FROM posts p
                       JOIN users u ON p.user_id = u.id
                       WHERE (p.content LIKE ? OR p.location LIKE ?)
                       AND (p.is_private = 0 OR p.user_id = ? OR
                            EXISTS (
                                SELECT 1 FROM friendships f 
                                WHERE ((f.sender_id = ? AND f.receiver_id = p.user_id) OR
                                      (f.sender_id = p.user_id AND f.receiver_id = ?))
                                AND f.status = 'accepted'
                            ))
                       ORDER BY p.created_at DESC
                       LIMIT ? OFFSET ?";
                
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param("isssiiii", 
                    $user_id, $search_term, $search_term, $user_id, 
                    $user_id, $user_id, $limit, $offset
                );
                $stmt->execute();
                $result = $stmt->get_result();
                
                while ($post = $result->fetch_assoc()) {
                    $stmt2 = $mysqli->prepare("
                        SELECT h.name 
                        FROM hashtags h
                        JOIN post_hashtags ph ON h.id = ph.hashtag_id
                        WHERE ph.post_id = ?
                    ");
                    $stmt2->bind_param("i", $post['id']);
                    $stmt2->execute();
                    $hashtags_result = $stmt2->get_result();
                    
                    $hashtags = [];
                    while ($tag = $hashtags_result->fetch_assoc()) {
                        $hashtags[] = $tag['name'];
                    }
                    
                    $post['hashtags'] = $hashtags;
                    $results[] = $post;
                }
                break;
                
            case 'hashtags':
                $sql = "SELECT h.*, COUNT(ph.post_id) as post_count
                       FROM hashtags h
                       LEFT JOIN post_hashtags ph ON h.id = ph.hashtag_id
                       WHERE h.name LIKE ?
                       GROUP BY h.id
                       ORDER BY post_count DESC
                       LIMIT ? OFFSET ?";
                
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param("sii", $search_term, $limit, $offset);
                $stmt->execute();
                $result = $stmt->get_result();
                
                while ($hashtag = $result->fetch_assoc()) {
                    $results[] = $hashtag;
                }
                break;
                
            case 'all':
                $all_results = [];
                
                $sql = "SELECT 'user' as type, id, username, profile_picture, bio,
                              (SELECT COUNT(*) FROM friendships 
                               WHERE ((sender_id = ? AND receiver_id = users.id) OR 
                                     (sender_id = users.id AND receiver_id = ?))
                               AND status = 'accepted') as is_friend
                       FROM users 
                       WHERE (username LIKE ? OR first_name LIKE ? OR last_name LIKE ?)
                       AND id != ?
                       LIMIT ?";
                
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param("iissssi", 
                    $user_id, $user_id, $search_term, $search_term, $search_term, 
                    $user_id, $limit
                );
                $stmt->execute();
                $result = $stmt->get_result();
                
                while ($user = $result->fetch_assoc()) {
                    $all_results[] = $user;
                }
                
                $sql = "SELECT 'post' as type, p.*, u.username, u.profile_picture,
                              (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
                              (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count
                       FROM posts p
                       JOIN users u ON p.user_id = u.id
                       WHERE (p.content LIKE ? OR p.location LIKE ?)
                       AND (p.is_private = 0 OR p.user_id = ? OR
                            EXISTS (
                                SELECT 1 FROM friendships f 
                                WHERE ((f.sender_id = ? AND f.receiver_id = p.user_id) OR
                                      (f.sender_id = p.user_id AND f.receiver_id = ?))
                                AND f.status = 'accepted'
                            ))
                       LIMIT ?";
                
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param("sssiii", 
                    $search_term, $search_term, $user_id, 
                    $user_id, $user_id, $limit
                );
                $stmt->execute();
                $result = $stmt->get_result();
                
                while ($post = $result->fetch_assoc()) {
                    $all_results[] = $post;
                }
                
                $sql = "SELECT 'hashtag' as type, h.*, COUNT(ph.post_id) as post_count
                       FROM hashtags h
                       LEFT JOIN post_hashtags ph ON h.id = ph.hashtag_id
                       WHERE h.name LIKE ?
                       GROUP BY h.id
                       LIMIT ?";
                
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param("si", $search_term, $limit);
                $stmt->execute();
                $result = $stmt->get_result();
                
                while ($hashtag = $result->fetch_assoc()) {
                    $all_results[] = $hashtag;
                }
                
                usort($all_results, function($a, $b) {
                    return strcmp($a['type'], $b['type']);
                });
                
                $results = array_slice($all_results, $offset, $limit);
                break;
                
            default:
                jsonError('Invalid search type');
                break;
        }
        
        jsonResponse($results);
        break;

    default:
        jsonError('Method not allowed', 405);
        break;
}
?> 