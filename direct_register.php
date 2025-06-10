<?php

$host = 'localhost';
$port = '5432';
$dbname = 'socialmedia';
$user = 'postgres';
$password = '20043110';

$log_file = __DIR__ . '/register_errors.log';
function log_error($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[{$timestamp}] {$message}" . PHP_EOL;
    file_put_contents($log_file, $log_message, FILE_APPEND);
}

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

function json_response($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'طريقة الطلب غير مسموح بها. يجب استخدام POST.'], 405);
}

$input_data = file_get_contents('php://input');
$is_json = false;

if (!empty($input_data)) {
    $data = json_decode($input_data, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $is_json = true;
    }
}

if (!$is_json) {
    $data = $_POST;
}

log_error("البيانات المستلمة: " . print_r($data, true));

if (empty($data['username']) || empty($data['password'])) {
    json_response(['error' => 'اسم المستخدم وكلمة المرور مطلوبة'], 400);
}

$email = !empty($data['email']) ? $data['email'] : $data['username'] . '@wep.com';

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ];
    
    $pdo = new PDO($dsn, $user, $password, $options);
    log_error("تم الاتصال بقاعدة البيانات PostgreSQL بنجاح");
    
    $username = $data['username'];
    $email = $email;
    $password = password_hash($data['password'], PASSWORD_DEFAULT);
    $first_name = isset($data['first_name']) ? $data['first_name'] : '';
    $last_name = isset($data['last_name']) ? $data['last_name'] : '';
    
    $check_sql = "SELECT id FROM users WHERE username = ? OR email = ?";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$username, $email]);
    $existing_user = $check_stmt->fetch();
    
    if ($existing_user) {
        json_response(['error' => 'اسم المستخدم أو البريد الإلكتروني موجود بالفعل'], 400);
    }
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id SERIAL PRIMARY KEY,
        username VARCHAR(100) UNIQUE NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        first_name VARCHAR(100),
        last_name VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    $insert_sql = "INSERT INTO users (username, email, password, first_name, last_name) 
                  VALUES (?, ?, ?, ?, ?) RETURNING id";
    $insert_stmt = $pdo->prepare($insert_sql);
    $insert_stmt->execute([$username, $email, $password, $first_name, $last_name]);
    
    $user_id = $insert_stmt->fetchColumn();
    
    if (!$user_id) {
        log_error("تم إدخال المستخدم ولكن لم يتم استرجاع ID");
        $user_id = 0; 
    }
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_settings (
        id SERIAL PRIMARY KEY,
        user_id INTEGER REFERENCES users(id),
        theme VARCHAR(20) DEFAULT 'dark',
        notifications BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    try {
        $settings_sql = "INSERT INTO user_settings (user_id) VALUES (?)";
        $settings_stmt = $pdo->prepare($settings_sql);
        $settings_stmt->execute([$user_id]);
    } catch (Exception $e) {
        log_error("خطأ في إنشاء إعدادات المستخدم: " . $e->getMessage());
    }
    
    json_response([
        'success' => true,
        'message' => 'تم تسجيل المستخدم بنجاح',
        'user_id' => $user_id,
        'username' => $username,
        'user' => [
            'id' => $user_id,
            'username' => $username,
            'first_name' => $first_name,
            'last_name' => $last_name
        ]
    ]);
    
} catch (PDOException $e) {
    log_error("خطأ في قاعدة البيانات: " . $e->getMessage());
    json_response(['error' => 'فشل في تسجيل المستخدم: ' . $e->getMessage()], 500);
} catch (Exception $e) {
    log_error("استثناء غير متوقع: " . $e->getMessage());
    json_response(['error' => 'حدث خطأ غير متوقع: ' . $e->getMessage()], 500);
}
?>
