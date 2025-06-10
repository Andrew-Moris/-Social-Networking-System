<?php

session_start();

$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'wep_db';

$log_file = __DIR__ . '/login_errors.log';
function log_error($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[{$timestamp}] {$message}" . PHP_EOL;
    file_put_contents($log_file, $log_message, FILE_APPEND);
}

$username = isset($_GET['username']) ? trim($_GET['username']) : '';
$password = isset($_GET['password']) ? $_GET['password'] : '';

if (empty($username) || empty($password)) {
    log_error("محاولة تسجيل دخول مع بيانات غير كاملة");
    header('Location: frontend/login.html?error=' . urlencode('يرجى إدخال اسم المستخدم وكلمة المرور'));
    exit;
}

try {
    $conn = new mysqli($host, $user, $password, $dbname);
    
    if ($conn->connect_error) {
        log_error("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
        header('Location: frontend/login.html?error=' . urlencode('خطأ في الاتصال بقاعدة البيانات'));
        exit;
    }
    
    $conn->set_charset("utf8mb4");
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
    
    if (!$stmt) {
        log_error("خطأ في إعداد استعلام البحث: " . $conn->error);
        header('Location: frontend/login.html?error=' . urlencode('خطأ في الخادم. يرجى المحاولة مرة أخرى'));
        exit;
    }
    
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        log_error("محاولة تسجيل دخول لمستخدم غير موجود: " . $username);
        header('Location: frontend/login.html?error=' . urlencode('اسم المستخدم أو البريد الإلكتروني غير موجود'));
        exit;
    }
    
    $user = $result->fetch_assoc();
    
    if (!password_verify($password, $user['password'])) {
        log_error("محاولة تسجيل دخول مع كلمة مرور غير صحيحة لـ: " . $username);
        header('Location: frontend/login.html?error=' . urlencode('كلمة المرور غير صحيحة'));
        exit;
    }
    
    $update = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    
    if ($update) {
        $update->bind_param("i", $user['id']);
        $update->execute();
    }
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    
    $conn->close();
    
    header('Location: home.php');
    exit;
    
} catch (Exception $e) {
    log_error("استثناء غير متوقع: " . $e->getMessage());
    header('Location: frontend/login.html?error=' . urlencode('حدث خطأ غير متوقع. يرجى المحاولة مرة أخرى'));
    exit;
}
?>
