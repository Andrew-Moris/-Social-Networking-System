<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST', 'localhost');
define('DB_NAME', 'wep_db');
define('DB_USER', 'root');
define('DB_PASS', '');     
define('DB_CHARSET', 'utf8mb4');

define('JWT_SECRET_KEY', 'wep_secure_jwt_secret_key_2025');

$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

$pdo_options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
} catch (PDOException $e) {
    try {
        $temp_pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
        $temp_pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
    } catch (PDOException $e2) {
        error_log("Database connection error: " . $e2->getMessage());
        die("فشل الاتصال بقاعدة البيانات. يرجى التحقق من إعدادات الاتصال.");
    }
}

define('APP_NAME', 'WEP Social');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/WEP');

define('UPLOADS_DIR', __DIR__ . '/uploads');
define('AVATAR_MAX_SIZE', 5 * 1024 * 1024); 
define('POST_MEDIA_MAX_SIZE', 100 * 1024 * 1024); 
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_VIDEO_TYPES', ['video/mp4', 'video/webm', 'video/ogg']);

define('PASSWORD_MIN_LENGTH', 6);
define('USERNAME_MIN_LENGTH', 3);
define('USERNAME_MAX_LENGTH', 20);

error_reporting(E_ALL);
ini_set('display_errors', 0); 
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

date_default_timezone_set('Asia/Riyadh');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if (file_exists(__DIR__ . '/functions.php')) {
    require_once __DIR__ . '/functions.php';
}
