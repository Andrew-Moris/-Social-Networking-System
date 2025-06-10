<?php

$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'wep_db';

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    header('Content-Type: application/json');
    http_response_code(500);
    die(json_encode(['error' => "فشل الاتصال بقاعدة البيانات: " . $conn->connect_error]));
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
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['username'])) {
            $username = $conn->real_escape_string($_GET['username']);
            
            $query = "SELECT id, username, avatar_url, bio, created_at FROM users WHERE username = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($user = $result->fetch_assoc()) {
                $userId = $user['id'];
                
                $stmt = $conn->prepare("SELECT COUNT(*) FROM followers WHERE followed_id = ?");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $followersCount = $stmt->get_result()->fetch_row()[0];
                
                $stmt = $conn->prepare("SELECT COUNT(*) FROM followers WHERE follower_id = ?");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $followingCount = $stmt->get_result()->fetch_row()[0];
                
                $stmt = $conn->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $postsCount = $stmt->get_result()->fetch_row()[0];
                
                $user['followers_count'] = $followersCount;
                $user['following_count'] = $followingCount;
                $user['posts_count'] = $postsCount;
                
                $stmt = $conn->prepare("
                    SELECT p.*, u.username, u.avatar_url 
                    FROM posts p 
                    JOIN users u ON p.user_id = u.id 
                    WHERE p.user_id = ? 
                    ORDER BY p.created_at DESC 
                    LIMIT 10
                ");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $postsResult = $stmt->get_result();
                
                $posts = [];
                while ($post = $postsResult->fetch_assoc()) {
                    $post['likes_count'] = rand(5, 50);
                    $post['dislikes_count'] = rand(0, 10);
                    $post['comments_count'] = rand(0, 20);
                    $post['user_reaction'] = rand(0, 2) === 0 ? 'like' : (rand(0, 1) === 0 ? 'dislike' : null);
                    
                    $posts[] = $post;
                }
                
                if (empty($posts)) {
                    $dummyPosts = [
                        [
                            'id' => 1,
                            'user_id' => $userId,
                            'username' => $username,
                            'avatar_url' => $user['avatar_url'] ?: '/WEP/assets/images/default-avatar.png',
                            'content' => 'هذا أول منشور لي على المنصة! 👋',
                            'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
                            'likes_count' => 12,
                            'dislikes_count' => 0,
                            'comments_count' => 3,
                            'user_reaction' => null
                        ],
                        [
                            'id' => 2,
                            'user_id' => $userId,
                            'username' => $username,
                            'avatar_url' => $user['avatar_url'] ?: '/WEP/assets/images/default-avatar.png',
                            'content' => 'أتمنى لكم يوماً سعيداً! 😊',
                            'created_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
                            'likes_count' => 8,
                            'dislikes_count' => 1,
                            'comments_count' => 2,
                            'user_reaction' => 'like'
                        ]
                    ];
                    
                    $posts = $dummyPosts;
                }
                
                jsonResponse([
                    'success' => true,
                    'message' => 'User data retrieved successfully (Public API - no token required)',
                    'user' => $user,
                    'posts' => $posts
                ]);
            } else {
                jsonError('User not found', 404);
            }
        } else if (isset($_GET['id'])) {
            $userId = (int)$_GET['id'];
            
            $query = "SELECT id, username, avatar_url, bio, created_at FROM users WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($user = $result->fetch_assoc()) {
                jsonResponse([
                    'success' => true,
                    'message' => 'User data retrieved successfully (Public API - no token required)',
                    'user' => $user
                ]);
            } else {
                jsonError('User not found', 404);
            }
        } else {
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
            
            $query = "SELECT id, username, avatar_url, bio, created_at FROM users LIMIT ?, ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $offset, $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $users = [];
            while ($user = $result->fetch_assoc()) {
                $users[] = $user;
            }
            
            jsonResponse([
                'success' => true,
                'message' => 'Users list retrieved successfully (Public API - no token required)',
                'users' => $users,
                'total' => count($users),
                'limit' => $limit,
                'offset' => $offset
            ]);
        }
        break;
        
    default:
        jsonError('Method not allowed. Only GET requests are supported.', 405);
        break;
}

$conn->close();
?>
