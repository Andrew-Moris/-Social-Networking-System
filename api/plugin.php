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

$action = isset($_GET['action']) ? $_GET['action'] : 'list';

switch ($method) {
    case 'GET':
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
                    'message' => 'Plugin API access - no token required',
                    'plugins' => $plugins
                ]);
                break;
                
            case 'get':
                if (!isset($_GET['id'])) {
                    jsonError('Plugin ID is required');
                }
                
                $plugin_id = (int)$_GET['id'];
                
                $plugin = null;
                switch ($plugin_id) {
                    case 1:
                        $plugin = [
                            'id' => 1,
                            'name' => 'محرر متقدم',
                            'description' => 'إضافة محرر نصوص متقدم للمنشورات',
                            'version' => '1.0.0',
                            'active' => true,
                            'author' => 'فريق التطوير',
                            'settings' => [
                                'enable_markdown' => true,
                                'enable_html' => false
                            ]
                        ];
                        break;
                    case 2:
                        $plugin = [
                            'id' => 2,
                            'name' => 'معرض الصور',
                            'description' => 'إضافة لعرض الصور بطريقة جذابة',
                            'version' => '2.1.0',
                            'active' => true,
                            'author' => 'فريق التطوير',
                            'settings' => [
                                'thumbnail_size' => 'medium',
                                'animation' => true
                            ]
                        ];
                        break;
                    case 3:
                        $plugin = [
                            'id' => 3,
                            'name' => 'نظام التعليقات المتقدم',
                            'description' => 'إضافة ميزات متقدمة للتعليقات',
                            'version' => '1.5.0',
                            'active' => false,
                            'author' => 'فريق التطوير',
                            'settings' => [
                                'nested_comments' => true,
                                'max_depth' => 5
                            ]
                        ];
                        break;
                }
                
                if ($plugin) {
                    jsonResponse([
                        'success' => true,
                        'message' => 'Plugin details retrieved without token',
                        'plugin' => $plugin
                    ]);
                } else {
                    jsonError('Plugin not found', 404);
                }
                break;
                
            case 'test':
            default:
                jsonResponse([
                    'success' => true,
                    'message' => 'Plugin API is working correctly',
                    'version' => 'WEP Plugin API v1.0',
                    'timestamp' => date('Y-m-d H:i:s'),
                    'database_connection' => 'Connected',
                    'available_actions' => [
                        'list' => 'عرض قائمة الإضافات',
                        'get' => 'عرض بيانات إضافة محددة (يتطلب معرف الإضافة)',
                        'test' => 'اختبار الاتصال'
                    ]
                ]);
                break;
        }
        break;
        
    default:
        jsonError('Method not allowed. Only GET requests are supported.');
        break;
}

$conn->close();
?>
