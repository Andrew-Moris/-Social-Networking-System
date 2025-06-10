<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/config.php';

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
    
    echo json_encode([
        'success' => true,
        'message' => 'Database connection successful',
        'db_info' => [
            'driver' => $pdo->getAttribute(PDO::ATTR_DRIVER_NAME),
            'server_version' => $pdo->getAttribute(PDO::ATTR_SERVER_VERSION),
            'dsn' => preg_replace('/pass=[^;]*/', 'pass=******', $dsn) 
        ],
        'api_config' => [
            'php_version' => PHP_VERSION,
            'extensions' => get_loaded_extensions()
        ]
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed',
        'error' => $e->getMessage(),
        'dsn' => preg_replace('/pass=[^;]*/', 'pass=******', $dsn) 
    ]);
}
?>
