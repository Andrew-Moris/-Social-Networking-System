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
            case 'register':
                $data = json_decode(file_get_contents('php://input'), true);
                
                if (!isset($data['username']) || !isset($data['email']) || !isset($data['password'])) {
                    jsonError('اسم المستخدم والبريد الإلكتروني وكلمة المرور مطلوبة');
                }
                
                if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    jsonError('صيغة البريد الإلكتروني غير صحيحة');
                }
                
                $check_query = "SELECT id FROM users WHERE username = ? OR email = ?";
                $check_stmt = $conn->prepare($check_query);
                $check_stmt->bind_param("ss", $data['username'], $data['email']);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                
                if ($result->num_rows > 0) {
                    jsonError('اسم المستخدم أو البريد الإلكتروني موجود بالفعل');
                }
                
                $username = $conn->real_escape_string($data['username']);
                $email = $conn->real_escape_string($data['email']);
                $password = password_hash($data['password'], PASSWORD_DEFAULT);
                $first_name = isset($data['first_name']) ? $conn->real_escape_string($data['first_name']) : '';
                $last_name = isset($data['last_name']) ? $conn->real_escape_string($data['last_name']) : '';
                
                $query = "INSERT INTO users (username, email, password, first_name, last_name) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("sssss", $username, $email, $password, $first_name, $last_name);
                
                if ($stmt->execute()) {
                    $user_id = $conn->insert_id;
                    
                    $settings_query = "INSERT INTO user_settings (user_id) VALUES (?)";
                    $settings_stmt = $conn->prepare($settings_query);
                    $settings_stmt->bind_param("i", $user_id);
                    $settings_stmt->execute();
                    
                    jsonResponse([
                        'success' => true,
                        'message' => 'تم تسجيل المستخدم بنجاح',
                        'user_id' => $user_id,
                        'username' => $username
                    ]);
                } else {
                    jsonError('فشل في تسجيل المستخدم: ' . $conn->error);
                }
                break;
                
            case 'login':
                $data = json_decode(file_get_contents('php://input'), true);
                
                if (!isset($data['username']) || !isset($data['password'])) {
                    jsonError('اسم المستخدم وكلمة المرور مطلوبة');
                }
                
                $username = $conn->real_escape_string($data['username']);
                $password = $data['password'];
                
                $query = "SELECT id, username, password, email, first_name, last_name, profile_picture FROM users WHERE username = ? OR email = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ss", $username, $username);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                
                if (!$user || !password_verify($password, $user['password'])) {
                    jsonError('اسم المستخدم أو كلمة المرور غير صحيحة');
                }
                
                $update_query = "UPDATE users SET last_active = CURRENT_TIMESTAMP WHERE id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("i", $user['id']);
                $update_stmt->execute();
                
                unset($user['password']);
                
                jsonResponse([
                    'success' => true,
                    'message' => 'تم تسجيل الدخول بنجاح',
                    'user' => $user
                ]);
                break;
                
            case 'update_profile':
                $data = json_decode(file_get_contents('php://input'), true);
                
                if (!isset($data['user_id'])) {
                    jsonError('معرف المستخدم مطلوب');
                }
                
                $user_id = (int)$data['user_id'];
                $updates = [];
                $params = [];
                $types = "";
                
                $allowed_fields = ['first_name', 'last_name', 'profile_picture', 'bio', 'email'];
                
                foreach ($allowed_fields as $field) {
                    if (isset($data[$field])) {
                        $updates[] = "$field = ?";
                        $params[] = $data[$field];
                        $types .= "s";
                    }
                }
                
                if (empty($updates)) {
                    jsonError('لم يتم تحديد أي حقول للتحديث');
                }
                
                $update_sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
                $types .= "i";
                $params[] = $user_id;
                
                $stmt = $conn->prepare($update_sql);
                $stmt->bind_param($types, ...$params);
                
                if ($stmt->execute()) {
                    jsonResponse([
                        'success' => true,
                        'message' => 'تم تحديث الملف الشخصي بنجاح',
                        'user_id' => $user_id
                    ]);
                } else {
                    jsonError('فشل في تحديث الملف الشخصي: ' . $conn->error);
                }
                break;
                
            default:
                jsonError('إجراء غير معروف');
                break;
        }
        break;
        
    case 'GET':
        switch ($action) {
            case 'user':
                if (!isset($_GET['id'])) {
                    jsonError('معرف المستخدم مطلوب');
                }
                
                $user_id = (int)$_GET['id'];
                $query = "SELECT id, username, email, first_name, last_name, profile_picture, bio, created_at, last_active FROM users WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($user = $result->fetch_assoc()) {
                    $stats_query = "SELECT 
                        (SELECT COUNT(*) FROM posts WHERE user_id = ?) AS posts_count,
                        (SELECT COUNT(*) FROM friendships WHERE (sender_id = ? OR receiver_id = ?) AND status = 'accepted') AS friends_count";
                    $stats_stmt = $conn->prepare($stats_query);
                    $stats_stmt->bind_param("iii", $user_id, $user_id, $user_id);
                    $stats_stmt->execute();
                    $stats = $stats_stmt->get_result()->fetch_assoc();
                    
                    $user['stats'] = $stats;
                    
                    jsonResponse([
                        'success' => true,
                        'user' => $user
                    ]);
                } else {
                    jsonError('المستخدم غير موجود', 404);
                }
                break;
                
            case 'search':
                if (!isset($_GET['query'])) {
                    jsonError('عبارة البحث مطلوبة');
                }
                
                $search_query = '%' . $conn->real_escape_string($_GET['query']) . '%';
                $query = "SELECT id, username, first_name, last_name, profile_picture FROM users 
                          WHERE username LIKE ? OR first_name LIKE ? OR last_name LIKE ? 
                          LIMIT 20";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("sss", $search_query, $search_query, $search_query);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $users = [];
                while ($user = $result->fetch_assoc()) {
                    $users[] = $user;
                }
                
                jsonResponse([
                    'success' => true,
                    'users' => $users
                ]);
                break;
                
            default:
                jsonResponse([
                    'success' => true,
                    'message' => 'API إجراءات المستخدمين',
                    'available_actions' => [
                        'POST: register' => 'تسجيل مستخدم جديد',
                        'POST: login' => 'تسجيل الدخول',
                        'POST: update_profile' => 'تحديث الملف الشخصي',
                        'GET: user' => 'الحصول على بيانات مستخدم',
                        'GET: search' => 'البحث عن مستخدمين'
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
