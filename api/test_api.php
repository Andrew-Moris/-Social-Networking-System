<?php

require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$db_status = [];
if (isset($conn) && $conn instanceof mysqli) {
    if ($conn->connect_error) {
        $db_status = [
            'connected' => false,
            'error' => $conn->connect_error
        ];
    } else {
        $test_query = "SHOW TABLES";
        $result = $conn->query($test_query);
        
        if ($result) {
            $tables = [];
            while ($row = $result->fetch_array()) {
                $tables[] = $row[0];
            }
            
            $db_status = [
                'connected' => true,
                'server_info' => $conn->server_info,
                'charset' => $conn->character_set_name(),
                'tables' => $tables
            ];
        } else {
            $db_status = [
                'connected' => true,
                'query_error' => $conn->error
            ];
        }
    }
} else {
    $db_status = [
        'connected' => false,
        'error' => 'Database connection not established'
    ];
}

$system_info = [
    'php_version' => PHP_VERSION,
    'server_software' => $_SERVER['SERVER_SOFTWARE'],
    'server_name' => $_SERVER['SERVER_NAME'],
    'document_root' => $_SERVER['DOCUMENT_ROOT'],
    'request_time' => date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']),
    'remote_addr' => $_SERVER['REMOTE_ADDR']
];

$config_info = [
    'base_url' => defined('BASE_URL') ? BASE_URL : 'Not defined',
    'upload_path' => defined('UPLOAD_PATH') ? UPLOAD_PATH : 'Not defined',
    'jwt_configured' => defined('JWT_SECRET_KEY') ? true : false
];

echo json_encode([
    'status' => 'success',
    'message' => 'API test successful',
    'database' => $db_status,
    'system' => $system_info,
    'config' => $config_info,
    'timestamp' => date('Y-m-d H:i:s')
], JSON_PRETTY_PRINT);
?>
