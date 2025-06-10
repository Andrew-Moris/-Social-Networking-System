<?php

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

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
    if (!headers_sent()) {
        http_response_code($status);
    }
    
    try {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        if ($json === false) {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to encode response: ' . json_last_error_msg(),
                'error_code' => json_last_error()
            ]);
        } else {
            echo $json;
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Exception during response encoding: ' . $e->getMessage()
        ]);
    }
    exit;
}

function jsonError($message, $status = 400) {
    jsonResponse(['success' => false, 'message' => $message], $status);
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $request_type = isset($_GET['type']) ? $_GET['type'] : 'posts';
        
        if ($request_type === 'users') {
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $offset = ($page - 1) * $limit;
            $search = isset($_GET['search']) ? $_GET['search'] : '';
            
            $query = "SELECT id, username, email, first_name, last_name, created_at";
            
            $columns_stmt = $conn->query("SHOW COLUMNS FROM users");
            $columns = [];
            while ($column = $columns_stmt->fetch_assoc()) {
                $columns[] = $column['Field'];
            }
            
            $avatar_column = null;
            foreach(['avatar', 'avatar_url', 'profile_picture', 'image', 'picture'] as $possible_column) {
                if (in_array($possible_column, $columns)) {
                    $avatar_column = $possible_column;
                    break;
                }
            }
            
            if ($avatar_column) {
                $query .= ", {$avatar_column} as avatar";
            } else {
                $query .= ", NULL as avatar";
            }
            
            $query .= " FROM users";
            
            if (!empty($search)) {
                $search = "%{$search}%";
                $query .= " WHERE username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ?";
                $stmt = $conn->prepare($query . " ORDER BY created_at DESC LIMIT ? OFFSET ?");
                $stmt->bind_param("ssssii", $search, $search, $search, $search, $limit, $offset);
            } else {
                $stmt = $conn->prepare($query . " ORDER BY created_at DESC LIMIT ? OFFSET ?");
                $stmt->bind_param("ii", $limit, $offset);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            $users = [];
            while ($user = $result->fetch_assoc()) {
                $user_id = $user['id'];
                
                $posts_query = $conn->prepare("SELECT COUNT(*) as count FROM posts WHERE user_id = ?");
                $posts_query->bind_param("i", $user_id);
                $posts_query->execute();
                $posts_result = $posts_query->get_result()->fetch_assoc();
                $user['posts_count'] = $posts_result['count'];
                
                $user['followers_count'] = rand(5, 100); 
                
                $users[] = $user;
            }
            
            jsonResponse([
                'success' => true,
                'message' => 'Users retrieved successfully',
                'users' => $users,
                'page' => $page,
                'limit' => $limit,
                'has_more' => count($users) === $limit
            ]);
            break;
        } else {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = ($page - 1) * $limit;
        $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
        
        $columns_stmt = $conn->query("SHOW COLUMNS FROM users");
        $columns = [];
        while ($column = $columns_stmt->fetch_assoc()) {
            $columns[] = $column['Field'];
        }
        
        $avatar_column = null;
        foreach(['avatar', 'avatar_url', 'profile_picture', 'image', 'picture'] as $possible_column) {
            if (in_array($possible_column, $columns)) {
                $avatar_column = $possible_column;
                break;
            }
        }
        
        $query = "SELECT p.*, u.username";
        
        if ($avatar_column) {
            $query .= ", u.{$avatar_column} as avatar";
        } else {
            $query .= ", NULL as avatar";
        }
        
        $query .= " FROM posts p JOIN users u ON p.user_id = u.id";
        
        if ($user_id) {
            $query .= " WHERE p.user_id = ?";
            $query .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("iii", $user_id, $limit, $offset);
        } else {
            $query .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $limit, $offset);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $posts = [];
        while ($post = $result->fetch_assoc()) {
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
                    'user_id' => 1,
                    'username' => 'admin',
                    'avatar_url' => '/WEP/assets/images/default-avatar.png',
                    'content' => 'مرحباً بكم في موقع التواصل الاجتماعي الجديد! 👋',
                    'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
                    'likes_count' => 42,
                    'dislikes_count' => 3,
                    'comments_count' => 15,
                    'user_reaction' => null
                ],
                [
                    'id' => 2,
                    'user_id' => 2,
                    'username' => 'محمد',
                    'avatar_url' => '/WEP/assets/images/default-avatar.png',
                    'content' => 'أول منشور لي على المنصة. متحمس للمشاركة معكم! 😊',
                    'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
                    'likes_count' => 23,
                    'dislikes_count' => 1,
                    'comments_count' => 7,
                    'user_reaction' => 'like'
                ],
                [
                    'id' => 3,
                    'user_id' => 3,
                    'username' => 'سارة',
                    'avatar_url' => '/WEP/assets/images/default-avatar.png',
                    'content' => 'مشاركة بعض الصور من رحلتي الأخيرة إلى البحر الأحمر 🌊',
                    'image_url' => 'https://picsum.photos/id/1000/800/600',
                    'created_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
                    'likes_count' => 56,
                    'dislikes_count' => 0,
                    'comments_count' => 12,
                    'user_reaction' => 'like'
                ],
                [
                    'id' => 4,
                    'user_id' => 4,
                    'username' => 'أحمد',
                    'avatar_url' => '/WEP/assets/images/default-avatar.png',
                    'content' => 'هل يعرف أحد مطعماً جيداً في وسط المدينة؟ 🍽️',
                    'created_at' => date('Y-m-d H:i:s', strtotime('-4 days')),
                    'likes_count' => 18,
                    'dislikes_count' => 0,
                    'comments_count' => 25,
                    'user_reaction' => null
                ],
                [
                    'id' => 5,
                    'user_id' => 5,
                    'username' => 'فاطمة',
                    'avatar_url' => '/WEP/assets/images/default-avatar.png',
                    'content' => 'انتهيت للتو من قراءة كتاب رائع! أنصح به بشدة 📚',
                    'created_at' => date('Y-m-d H:i:s', strtotime('-5 days')),
                    'likes_count' => 31,
                    'dislikes_count' => 2,
                    'comments_count' => 9,
                    'user_reaction' => 'dislike'
                ]
            ];
            
            $posts = $dummyPosts;
        }
        
        jsonResponse([
            'success' => true,
            'message' => 'Posts retrieved successfully (Public API - no token required)',
            'posts' => $posts,
            'page' => $page,
            'limit' => $limit,
            'has_more' => count($posts) === $limit
        ]);
        }
        break;
        
    case 'POST':
        try {
            error_log("Public posts API received POST request");
            error_log("POST data: " . print_r($_POST, true));
            error_log("FILES data: " . print_r($_FILES, true));
            error_log("Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));
            
            $tableInfo = $conn->query("DESCRIBE users");
            $columns = [];
            while ($column = $tableInfo->fetch_assoc()) {
                $columns[] = $column['Field'];
            }
            error_log("Users table columns: " . implode(", ", $columns));
            
            $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
        
        if (strpos($contentType, 'application/json') !== false) {
            $data = json_decode(file_get_contents('php://input'), true);
            $content = $data['content'] ?? '';
            $user_id = $data['user_id'] ?? 1;
            error_log("Parsed JSON data: " . print_r($data, true));
        } else {
            $content = $_POST['content'] ?? '';
            $user_id = $_POST['user_id'] ?? 1;
        }
        
        if (empty($content) && empty($_FILES['media']['name'])) {
            jsonError('يجب إدخال محتوى أو إرفاق وسائط');
        }
        
        $media_url = null;
        $media_type = null;
        
        if (!empty($_FILES['media']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/posts/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['media']['name'], PATHINFO_EXTENSION);
            $file_name = uniqid('post_') . '.' . $file_extension;
            $file_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['media']['tmp_name'], $file_path)) {
                $media_url = 'uploads/posts/' . $file_name;
                $media_type = strpos($_FILES['media']['type'], 'image/') === 0 ? 'image' : 'video';
                error_log("Media uploaded successfully: {$media_url}");
            }
        }
        
        $query = "SELECT * FROM users WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $userData = $result->fetch_assoc();
        
        error_log("User data retrieved: " . print_r($userData, true));
        
        if (!$userData) {
            $userData = [
                'id' => $user_id,
                'username' => 'user_' . $user_id,
                'first_name' => '',
                'last_name' => ''
            ];
            error_log("Using default user data for user ID: {$user_id}");
        }
        
        $insert_query = "INSERT INTO posts (user_id, content, media_url, media_type, created_at) VALUES (?, ?, ?, ?, NOW())";        
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("isss", $user_id, $content, $media_url, $media_type);
        
        error_log("Inserting post: user_id={$user_id}, content={$content}, media_url={$media_url}, media_type={$media_type}");
        
        if ($insert_stmt->execute()) {
            $post_id = $conn->insert_id;
            error_log("Post inserted successfully with ID: {$post_id}");
            
            $post_query = "SELECT * FROM posts WHERE id = ?";  
            $post_stmt = $conn->prepare($post_query);
            $post_stmt->bind_param("i", $post_id);
            $post_stmt->execute();
            $post_result = $post_stmt->get_result();
            $post_data = $post_result->fetch_assoc();
            
            if ($post_data) {
                $userDataForPost = [
                    'username' => $userData['username'] ?? '',
                    'first_name' => $userData['first_name'] ?? '',
                    'last_name' => $userData['last_name'] ?? '',
                    'likes_count' => 0,
                    'comments_count' => 0,
                    'is_liked' => false,
                    'is_bookmarked' => false,
                    'user_id' => $user_id
                ];
                
                if (isset($userData['avatar_url']) && !empty($userData['avatar_url'])) {
                    $userDataForPost['avatar'] = $userData['avatar_url'];
                    $userDataForPost['avatar_url'] = $userData['avatar_url'];
                } else if (isset($userData['avatar']) && !empty($userData['avatar'])) {
                    $userDataForPost['avatar'] = $userData['avatar'];
                    $userDataForPost['avatar_url'] = $userData['avatar'];
                } else {
                    $userDataForPost['avatar'] = '/WEP/assets/images/default-avatar.png';
                    $userDataForPost['avatar_url'] = '/WEP/assets/images/default-avatar.png';
                }
                
                $post_data = array_merge($post_data, $userDataForPost);
                
                error_log("Final post data: " . json_encode($post_data));
                
                jsonResponse([
                    'success' => true,
                    'message' => 'تم إنشاء المنشور بنجاح (Public API - no token required)',
                    'post' => $post_data
                ]);
            } else {
                jsonError('تم إنشاء المنشور ولكن فشل استرجاع بياناته');
            }
        } else {
            error_log("Failed to insert post: " . $insert_stmt->error);
            jsonError('فشل إنشاء المنشور: ' . $insert_stmt->error);
        }
    } catch (Exception $e) {
        error_log("Exception in public_posts.php: " . $e->getMessage());
        jsonError('حدث خطأ أثناء معالجة المنشور: ' . $e->getMessage());
    }
        break;
        
    default:
        jsonError('Method not allowed. Only GET and POST requests are supported.');
        break;
}

$conn->close();
?>
