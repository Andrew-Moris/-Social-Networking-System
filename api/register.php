<?php
ini_set('display_errors', 0);
error_reporting(0);

ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$debug_mode = true;

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

try {
    $jsonInput = file_get_contents('php://input');
    $data = json_decode($jsonInput, true);
    
    if (empty($data) && !empty($_POST)) {
        $data = $_POST;
    }

    if (empty($data)) {
        echo json_encode(['error' => 'لم يتم استلام أي بيانات للتسجيل']);
        exit;
    }
} catch (Exception $e) {
    error_log("Error parsing input data in register.php: " . $e->getMessage());
    echo json_encode(['error' => 'خطأ في معالجة البيانات المدخلة']);
    exit;
}

if (empty($data['username']) || empty($data['password'])) {
    echo json_encode(['error' => 'اسم المستخدم وكلمة المرور مطلوبان']);
    exit;
}

try {
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ];
    
    error_log("Registration attempt: Connecting to database with credentials from config.php");
    error_log("DB_HOST: " . DB_HOST . ", DB_NAME: " . DB_NAME . ", DB_USER: " . DB_USER);
    
    if (defined('DB_CHARSET')) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    } 
    else {
        $dsn = "pgsql:host=" . DB_HOST . ";port=" . (defined('DB_PORT') ? DB_PORT : '5432') . ";dbname=" . DB_NAME;
    }
    
    error_log("DSN: " . $dsn);
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
    $email = $data['email'] ?? $data['username'] . '@wep.com';
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute([$data['username']]);
    $exists = (int)$stmt->fetchColumn();
    
    if ($exists > 0) {
        echo json_encode(['error' => 'اسم المستخدم موجود بالفعل']);
        exit;
    }
    
    if (!empty($data['email'])) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);
        $exists = (int)$stmt->fetchColumn();
        
        if ($exists > 0) {
            echo json_encode(['error' => 'البريد الإلكتروني مستخدم بالفعل']);
            exit;
        }
    }
    
    $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
    
    try {
        $tableInfo = $pdo->query("DESCRIBE users");
        $columns = $tableInfo->fetchAll(PDO::FETCH_COLUMN);
        error_log("Available columns in users table: " . implode(", ", $columns));
    } catch (PDOException $e) {
        try {
            $tableInfo = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'users'");
            $columns = $tableInfo->fetchAll(PDO::FETCH_COLUMN);
            error_log("Available columns in users table (PostgreSQL): " . implode(", ", $columns));
        } catch (PDOException $e2) {
            error_log("Could not determine table structure: " . $e2->getMessage());
            $columns = ['username', 'email', 'password'];
        }
    }
    
    $insertColumns = ['username', 'email', 'password'];
    $insertValues = [$data['username'], $email, $hashed_password];
    
    if (in_array('first_name', $columns) && isset($data['first_name'])) {
        $insertColumns[] = 'first_name';
        $insertValues[] = $data['first_name'] ?? '';
    }
    
    if (in_array('last_name', $columns) && isset($data['last_name'])) {
        $insertColumns[] = 'last_name';
        $insertValues[] = $data['last_name'] ?? '';
    }
    
    $sql = "INSERT INTO users (" . implode(", ", $insertColumns) . ") VALUES (" . implode(", ", array_fill(0, count($insertColumns), "?")) . ")";
    error_log("Registration SQL: " . $sql);
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($insertValues);
    
    $user_id = $pdo->lastInsertId();
    
    session_start();
    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $data['username'];
    
    $is_form_submit = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' ? false : (!empty($_POST));
    
    if ($is_form_submit) {
        $redirect_url = isset($_POST['redirect']) ? $_POST['redirect'] : '../frontend/login.html?registered=success';
        header('Location: ' . $redirect_url);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Registration successful',
        'user' => [
            'id' => $user_id,
            'username' => $data['username'],
            'email' => $email
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Registration error: " . $e->getMessage());
    
    echo json_encode([
        'error' => 'Registration failed',
        'details' => $e->getMessage(),
        'connection_info' => [
            'host' => DB_HOST,
            'database' => DB_NAME,
            'user' => DB_USER
        ]
    ]);
}
?>
