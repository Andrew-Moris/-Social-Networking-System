<?php

require_once __DIR__ . '/../config/config.php';

function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
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
        $action = isset($_GET['action']) ? $_GET['action'] : 'test';
        
        switch ($action) {
            case 'users':
                $query = "SELECT id, username, email, created_at FROM users LIMIT 10";
                $result = $conn->query($query);
                
                if ($result) {
                    $users = [];
                    while ($row = $result->fetch_assoc()) {
                        $users[] = $row;
                    }
                    
                    jsonResponse([
                        'success' => true,
                        'message' => 'Public API access - no token required',
                        'users' => $users
                    ]);
                } else {
                    jsonError('Failed to fetch users: ' . $conn->error);
                }
                break;
                
            case 'user':
                if (!isset($_GET['id'])) {
                    jsonError('User ID is required');
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
                        'message' => 'User details retrieved without token',
                        'user' => $user
                    ]);
                } else {
                    jsonError('User not found', 404);
                }
                break;
                
            case 'test':
                jsonResponse([
                    'success' => true,
                    'message' => 'Public API is working correctly',
                    'version' => 'WEP API v1.0',
                    'timestamp' => date('Y-m-d H:i:s'),
                    'endpoints' => [
                        'GET /api/public_api_new.php?action=users - عرض قائمة المستخدمين',
                        'GET /api/public_api_new.php?action=user&id={user_id} - عرض بيانات مستخدم محدد',
                        'GET /api/public_api_new.php?action=test - اختبار الاتصال'
                    ]
                ]);
                break;
                
            default:
                jsonError('Unknown action. Available actions: users, user, test');
                break;
        }
        break;
        
    default:
        jsonError('Method not allowed. Only GET requests are supported.');
        break;
}
?>
