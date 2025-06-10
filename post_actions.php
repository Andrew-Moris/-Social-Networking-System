<?php

$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'wep_db';

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    header('Content-Type: application/json');
    http_response_code(500);
    die(json_encode(['error' => "فشل الاتصال بقاعدة البيانات: " . $conn->connect_error], JSON_UNESCAPED_UNICODE));
}

$conn->set_charset("utf8mb4");

function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonError($message, $status = 400) {
    jsonResponse(['error' => $message], $status);
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : 'default';

switch ($method) {
    case 'POST':
        switch ($action) {
            case 'create_post':
                $data = json_decode(file_get_contents('php://input'), true);
                
                if (!isset($data['user_id'])) {
                    jsonError('معرف المستخدم مطلوب');
                }
                
                if (!isset($data['content']) && !isset($data['media_url'])) {
                    jsonError('يجب أن يحتوي المنشور على محتوى أو وسائط');
                }
                
                $user_id = (int)$data['user_id'];
                $content = isset($data['content']) ? $conn->real_escape_string($data['content']) : null;
                $media_url = isset($data['media_url']) ? $conn->real_escape_string($data['media_url']) : null;
                $media_type = isset($data['media_type']) ? $conn->real_escape_string($data['media_type']) : 'none';
                $location = isset($data['location']) ? $conn->real_escape_string($data['location']) : null;
                $is_private = isset($data['is_private']) ? (int)$data['is_private'] : 0;
                
                $user_check = "SELECT id FROM users WHERE id = ?";
                $user_stmt = $conn->prepare($user_check);
                $user_stmt->bind_param("i", $user_id);
                $user_stmt->execute();
                $user_result = $user_stmt->get_result();
                
                if ($user_result->num_rows == 0) {
                    jsonError('المستخدم غير موجود');
                }
                
                $query = "INSERT INTO posts (user_id, content, media_url, media_type, location, is_private) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("issssi", $user_id, $content, $media_url, $media_type, $location, $is_private);
                
                if ($stmt->execute()) {
                    $post_id = $conn->insert_id;
                    
                    if (isset($data['hashtags']) && is_array($data['hashtags'])) {
                        foreach ($data['hashtags'] as $tag) {
                            $tag = trim($tag, '#');
                            
                            $hashtag_query = "INSERT IGNORE INTO hashtags (name) VALUES (?)";
                            $hashtag_stmt = $conn->prepare($hashtag_query);
                            $hashtag_stmt->bind_param("s", $tag);
                            $hashtag_stmt->execute();
                            
                            $tag_id_query = "SELECT id FROM hashtags WHERE name = ?";
                            $tag_id_stmt = $conn->prepare($tag_id_query);
                            $tag_id_stmt->bind_param("s", $tag);
                            $tag_id_stmt->execute();
                            $tag_result = $tag_id_stmt->get_result();
                            $hashtag = $tag_result->fetch_assoc();
                            
                            $link_query = "INSERT INTO post_hashtags (post_id, hashtag_id) VALUES (?, ?)";
                            $link_stmt = $conn->prepare($link_query);
                            $link_stmt->bind_param("ii", $post_id, $hashtag['id']);
                            $link_stmt->execute();
                        }
                    }
                    
                    jsonResponse([
                        'success' => true,
                        'message' => 'تم إنشاء المنشور بنجاح',
                        'post_id' => $post_id
                    ]);
                } else {
                    jsonError('فشل في إنشاء المنشور: ' . $conn->error);
                }
                break;
                
            case 'add_comment':
                $data = json_decode(file_get_contents('php://input'), true);
                
                if (!isset($data['user_id']) || !isset($data['post_id']) || !isset($data['content'])) {
                    jsonError('معرف المستخدم والمنشور والمحتوى مطلوبة');
                }
                
                $user_id = (int)$data['user_id'];
                $post_id = (int)$data['post_id'];
                $content = $conn->real_escape_string($data['content']);
                $parent_id = isset($data['parent_id']) ? (int)$data['parent_id'] : null;
                
                $post_check = "SELECT id FROM posts WHERE id = ?";
                $post_stmt = $conn->prepare($post_check);
                $post_stmt->bind_param("i", $post_id);
                $post_stmt->execute();
                $post_result = $post_stmt->get_result();
                
                if ($post_result->num_rows == 0) {
                    jsonError('المنشور غير موجود');
                }
                
                $query = "INSERT INTO comments (post_id, user_id, content, parent_id) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("iisi", $post_id, $user_id, $content, $parent_id);
                
                if ($stmt->execute()) {
                    $comment_id = $conn->insert_id;
                    
                    $user_query = "SELECT username, profile_picture FROM users WHERE id = ?";
                    $user_stmt = $conn->prepare($user_query);
                    $user_stmt->bind_param("i", $user_id);
                    $user_stmt->execute();
                    $user_result = $user_stmt->get_result();
                    $user = $user_result->fetch_assoc();
                    
                    jsonResponse([
                        'success' => true,
                        'message' => 'تم إضافة التعليق بنجاح',
                        'comment' => [
                            'id' => $comment_id,
                            'post_id' => $post_id,
                            'user_id' => $user_id,
                            'username' => $user['username'],
                            'profile_picture' => $user['profile_picture'],
                            'content' => $content,
                            'parent_id' => $parent_id,
                            'created_at' => date('Y-m-d H:i:s')
                        ]
                    ]);
                } else {
                    jsonError('فشل في إضافة التعليق: ' . $conn->error);
                }
                break;
                
            case 'like_post':
                $data = json_decode(file_get_contents('php://input'), true);
                
                if (!isset($data['user_id']) || !isset($data['post_id'])) {
                    jsonError('معرف المستخدم والمنشور مطلوبان');
                }
                
                $user_id = (int)$data['user_id'];
                $post_id = (int)$data['post_id'];
                
                $check_query = "SELECT id FROM likes WHERE user_id = ? AND post_id = ?";
                $check_stmt = $conn->prepare($check_query);
                $check_stmt->bind_param("ii", $user_id, $post_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    $delete_query = "DELETE FROM likes WHERE user_id = ? AND post_id = ?";
                    $delete_stmt = $conn->prepare($delete_query);
                    $delete_stmt->bind_param("ii", $user_id, $post_id);
                    
                    if ($delete_stmt->execute()) {
                        jsonResponse([
                            'success' => true,
                            'message' => 'تم إلغاء الإعجاب بنجاح',
                            'action' => 'unlike'
                        ]);
                    } else {
                        jsonError('فشل في إلغاء الإعجاب: ' . $conn->error);
                    }
                } else {
                    $insert_query = "INSERT INTO likes (user_id, post_id) VALUES (?, ?)";
                    $insert_stmt = $conn->prepare($insert_query);
                    $insert_stmt->bind_param("ii", $user_id, $post_id);
                    
                    if ($insert_stmt->execute()) {
                        jsonResponse([
                            'success' => true,
                            'message' => 'تم الإعجاب بنجاح',
                            'action' => 'like'
                        ]);
                    } else {
                        jsonError('فشل في الإعجاب: ' . $conn->error);
                    }
                }
                break;
                
            default:
                jsonError('إجراء غير معروف');
                break;
        }
        break;
        
    case 'GET':
        switch ($action) {
            case 'get_post':
                if (!isset($_GET['id'])) {
                    jsonError('معرف المنشور مطلوب');
                }
                
                $post_id = (int)$_GET['id'];
                $query = "SELECT p.*, u.username, u.profile_picture,
                            (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
                            (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count
                         FROM posts p
                         JOIN users u ON p.user_id = u.id
                         WHERE p.id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $post_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($post = $result->fetch_assoc()) {
                    $hashtags_query = "SELECT h.name 
                                      FROM hashtags h
                                      JOIN post_hashtags ph ON h.id = ph.hashtag_id
                                      WHERE ph.post_id = ?";
                    $hashtags_stmt = $conn->prepare($hashtags_query);
                    $hashtags_stmt->bind_param("i", $post_id);
                    $hashtags_stmt->execute();
                    $hashtags_result = $hashtags_stmt->get_result();
                    
                    $hashtags = [];
                    while ($tag = $hashtags_result->fetch_assoc()) {
                        $hashtags[] = $tag['name'];
                    }
                    
                    $post['hashtags'] = $hashtags;
                    
                    $comments_query = "SELECT c.*, u.username, u.profile_picture
                                     FROM comments c
                                     JOIN users u ON c.user_id = u.id
                                     WHERE c.post_id = ?
                                     ORDER BY c.created_at ASC";
                    $comments_stmt = $conn->prepare($comments_query);
                    $comments_stmt->bind_param("i", $post_id);
                    $comments_stmt->execute();
                    $comments_result = $comments_stmt->get_result();
                    
                    $comments = [];
                    while ($comment = $comments_result->fetch_assoc()) {
                        $comments[] = $comment;
                    }
                    
                    $post['comments'] = $comments;
                    
                    jsonResponse([
                        'success' => true,
                        'post' => $post
                    ]);
                } else {
                    jsonError('المنشور غير موجود', 404);
                }
                break;
                
            case 'get_posts':
                $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
                $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
                $offset = ($page - 1) * $limit;
                
                $where_clause = "";
                $params = [];
                $types = "";
                
                if ($user_id) {
                    $where_clause = "WHERE p.user_id = ?";
                    $params[] = $user_id;
                    $types .= "i";
                }
                
                $query = "SELECT p.*, u.username, u.profile_picture,
                            (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
                            (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count
                         FROM posts p
                         JOIN users u ON p.user_id = u.id
                         $where_clause
                         ORDER BY p.created_at DESC
                         LIMIT ? OFFSET ?";
                
                $params[] = $limit;
                $params[] = $offset;
                $types .= "ii";
                
                $stmt = $conn->prepare($query);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $posts = [];
                while ($post = $result->fetch_assoc()) {
                    $hashtags_query = "SELECT h.name 
                                      FROM hashtags h
                                      JOIN post_hashtags ph ON h.id = ph.hashtag_id
                                      WHERE ph.post_id = ?";
                    $hashtags_stmt = $conn->prepare($hashtags_query);
                    $hashtags_stmt->bind_param("i", $post['id']);
                    $hashtags_stmt->execute();
                    $hashtags_result = $hashtags_stmt->get_result();
                    
                    $hashtags = [];
                    while ($tag = $hashtags_result->fetch_assoc()) {
                        $hashtags[] = $tag['name'];
                    }
                    
                    $post['hashtags'] = $hashtags;
                    $posts[] = $post;
                }
                
                jsonResponse([
                    'success' => true,
                    'posts' => $posts,
                    'page' => $page,
                    'limit' => $limit
                ]);
                break;
                
            case 'get_comments':
                if (!isset($_GET['post_id'])) {
                    jsonError('معرف المنشور مطلوب');
                }
                
                $post_id = (int)$_GET['post_id'];
                $query = "SELECT c.*, u.username, u.profile_picture
                         FROM comments c
                         JOIN users u ON c.user_id = u.id
                         WHERE c.post_id = ?
                         ORDER BY c.created_at ASC";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $post_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $comments = [];
                while ($comment = $result->fetch_assoc()) {
                    $comments[] = $comment;
                }
                
                jsonResponse([
                    'success' => true,
                    'comments' => $comments
                ]);
                break;
                
            default:
                jsonResponse([
                    'success' => true,
                    'message' => 'API إجراءات المنشورات',
                    'available_actions' => [
                        'POST: create_post' => 'إنشاء منشور جديد',
                        'POST: add_comment' => 'إضافة تعليق',
                        'POST: like_post' => 'إعجاب بمنشور',
                        'GET: get_post' => 'الحصول على منشور محدد',
                        'GET: get_posts' => 'الحصول على قائمة المنشورات',
                        'GET: get_comments' => 'الحصول على تعليقات منشور'
                    ]
                ]);
                break;
        }
        break;
        
    default:
        jsonError('طريقة غير مسموح بها', 405);
        break;
}

$conn->close();
?>
