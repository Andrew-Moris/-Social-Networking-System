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
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : 'test';
$action = isset($_GET['action']) ? $_GET['action'] : 'default';

$method = $_SERVER['REQUEST_METHOD'];

function handleUsers($action, $conn) {
    switch ($action) {
        case 'list':
            $query = "SELECT id, username, email, created_at FROM users LIMIT 10";
            $result = $conn->query($query);
            
            if ($result) {
                $users = [];
                while ($row = $result->fetch_assoc()) {
                    $users[] = $row;
                }
                
                jsonResponse([
                    'success' => true,
                    'message' => 'قائمة المستخدمين',
                    'users' => $users
                ]);
            } else {
                jsonError('فشل في الحصول على قائمة المستخدمين: ' . $conn->error);
            }
            break;
            
        case 'get':
            if (!isset($_GET['id'])) {
                jsonError('معرف المستخدم مطلوب');
            }
            
            $user_id = (int)$_GET['id'];
            $query = "SELECT id, username, email, created_at FROM users WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($user = $result->fetch_assoc()) {
                jsonResponse([
                    'success' => true,
                    'message' => 'بيانات المستخدم',
                    'user' => $user
                ]);
            } else {
                jsonError('المستخدم غير موجود', 404);
            }
            break;
            
        default:
            jsonResponse([
                'success' => true,
                'message' => 'API المستخدمين',
                'actions' => [
                    'list' => 'الحصول على قائمة المستخدمين',
                    'get' => 'الحصول على مستخدم محدد (يتطلب معرف)'
                ]
            ]);
            break;
    }
}

function handlePosts($action, $conn) {
    switch ($action) {
        case 'list':
            $posts = [
                [
                    'id' => 1,
                    'user_id' => 1,
                    'title' => 'منشور تجريبي #1',
                    'content' => 'هذا هو محتوى المنشور التجريبي الأول',
                    'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
                ],
                [
                    'id' => 2,
                    'user_id' => 1,
                    'title' => 'منشور تجريبي #2',
                    'content' => 'هذا هو محتوى المنشور التجريبي الثاني',
                    'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
                ]
            ];
            
            jsonResponse([
                'success' => true,
                'message' => 'قائمة المنشورات',
                'posts' => $posts
            ]);
            break;
            
        case 'get':
            if (!isset($_GET['id'])) {
                jsonError('معرف المنشور مطلوب');
            }
            
            $post_id = (int)$_GET['id'];
            
            if ($post_id === 1) {
                jsonResponse([
                    'success' => true,
                    'message' => 'بيانات المنشور',
                    'post' => [
                        'id' => 1,
                        'user_id' => 1,
                        'title' => 'منشور تجريبي #1',
                        'content' => 'هذا هو محتوى المنشور التجريبي الأول',
                        'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
                        'comments' => [
                            [
                                'id' => 1,
                                'user_id' => 1,
                                'content' => 'تعليق تجريبي على المنشور',
                                'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
                            ]
                        ]
                    ]
                ]);
            } else if ($post_id === 2) {
                jsonResponse([
                    'success' => true,
                    'message' => 'بيانات المنشور',
                    'post' => [
                        'id' => 2,
                        'user_id' => 1,
                        'title' => 'منشور تجريبي #2',
                        'content' => 'هذا هو محتوى المنشور التجريبي الثاني',
                        'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
                        'comments' => []
                    ]
                ]);
            } else {
                jsonError('المنشور غير موجود', 404);
            }
            break;
            
        default:
            jsonResponse([
                'success' => true,
                'message' => 'API المنشورات',
                'actions' => [
                    'list' => 'الحصول على قائمة المنشورات',
                    'get' => 'الحصول على منشور محدد (يتطلب معرف)'
                ]
            ]);
            break;
    }
}

function handlePlugins($action, $conn) {
    switch ($action) {
        case 'list':
            $plugins = [
                [
                    'id' => 1,
                    'name' => 'محرر متقدم',
                    'description' => 'إضافة محرر نصوص متقدم للمنشورات',
                    'version' => '1.0.0',
                    'active' => true
                ],
                [
                    'id' => 2,
                    'name' => 'معرض الصور',
                    'description' => 'إضافة لعرض الصور بطريقة جذابة',
                    'version' => '2.1.0',
                    'active' => true
                ],
                [
                    'id' => 3,
                    'name' => 'نظام التعليقات المتقدم',
                    'description' => 'إضافة ميزات متقدمة للتعليقات',
                    'version' => '1.5.0',
                    'active' => false
                ]
            ];
            
            jsonResponse([
                'success' => true,
                'message' => 'قائمة الإضافات',
                'plugins' => $plugins
            ]);
            break;
            
        case 'get':
            if (!isset($_GET['id'])) {
                jsonError('معرف الإضافة مطلوب');
            }
            
            $plugin_id = (int)$_GET['id'];
            
            if ($plugin_id === 1) {
                jsonResponse([
                    'success' => true,
                    'message' => 'بيانات الإضافة',
                    'plugin' => [
                        'id' => 1,
                        'name' => 'محرر متقدم',
                        'description' => 'إضافة محرر نصوص متقدم للمنشورات',
                        'version' => '1.0.0',
                        'active' => true,
                        'settings' => [
                            'enable_markdown' => true,
                            'enable_html' => false
                        ]
                    ]
                ]);
            } else if ($plugin_id === 2) {
                jsonResponse([
                    'success' => true,
                    'message' => 'بيانات الإضافة',
                    'plugin' => [
                        'id' => 2,
                        'name' => 'معرض الصور',
                        'description' => 'إضافة لعرض الصور بطريقة جذابة',
                        'version' => '2.1.0',
                        'active' => true,
                        'settings' => [
                            'thumbnail_size' => 'medium',
                            'animation' => true
                        ]
                    ]
                ]);
            } else if ($plugin_id === 3) {
                jsonResponse([
                    'success' => true,
                    'message' => 'بيانات الإضافة',
                    'plugin' => [
                        'id' => 3,
                        'name' => 'نظام التعليقات المتقدم',
                        'description' => 'إضافة ميزات متقدمة للتعليقات',
                        'version' => '1.5.0',
                        'active' => false,
                        'settings' => [
                            'nested_comments' => true,
                            'max_depth' => 5
                        ]
                    ]
                ]);
            } else {
                jsonError('الإضافة غير موجودة', 404);
            }
            break;
            
        default:
            jsonResponse([
                'success' => true,
                'message' => 'API الإضافات',
                'actions' => [
                    'list' => 'الحصول على قائمة الإضافات',
                    'get' => 'الحصول على إضافة محددة (يتطلب معرف)'
                ]
            ]);
            break;
    }
}

switch ($endpoint) {
    case 'users':
        handleUsers($action, $conn);
        break;
        
    case 'posts':
        handlePosts($action, $conn);
        break;
        
    case 'plugins':
        handlePlugins($action, $conn);
        break;
        
    case 'test':
    default:
        jsonResponse([
            'success' => true,
            'message' => 'بوابة API تعمل بشكل صحيح',
            'version' => 'WEP API Gateway v1.0',
            'timestamp' => date('Y-m-d H:i:s'),
            'database_connection' => 'متصل',
            'endpoints' => [
                'users' => [
                    'description' => 'API المستخدمين',
                    'url' => '/WEP/api_gateway.php?endpoint=users',
                    'actions' => [
                        'list' => '/WEP/api_gateway.php?endpoint=users&action=list',
                        'get' => '/WEP/api_gateway.php?endpoint=users&action=get&id=1'
                    ]
                ],
                'posts' => [
                    'description' => 'API المنشورات',
                    'url' => '/WEP/api_gateway.php?endpoint=posts',
                    'actions' => [
                        'list' => '/WEP/api_gateway.php?endpoint=posts&action=list',
                        'get' => '/WEP/api_gateway.php?endpoint=posts&action=get&id=1'
                    ]
                ],
                'plugins' => [
                    'description' => 'API الإضافات',
                    'url' => '/WEP/api_gateway.php?endpoint=plugins',
                    'actions' => [
                        'list' => '/WEP/api_gateway.php?endpoint=plugins&action=list',
                        'get' => '/WEP/api_gateway.php?endpoint=plugins&action=get&id=1'
                    ]
                ],
                'test' => [
                    'description' => 'اختبار API',
                    'url' => '/WEP/api_gateway.php?endpoint=test'
                ]
            ]
        ]);
        break;
}

$conn->close();
?>
