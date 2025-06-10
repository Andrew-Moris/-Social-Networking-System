<?php

$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'wep_db';

$conn = new mysqli($host, $user, $password);

if ($conn->connect_error) {
    die("فشل الاتصال بالخادم: " . $conn->connect_error);
}

$sql = "CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if (!$conn->query($sql)) {
    die("فشل إنشاء قاعدة البيانات: " . $conn->error);
}

$conn->select_db($dbname);

$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    profile_picture VARCHAR(255),
    bio TEXT,
    is_verified BOOLEAN DEFAULT 0,
    is_private BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!$conn->query($sql)) {
    die("فشل إنشاء جدول المستخدمين: " . $conn->error);
}

$sql = "CREATE TABLE IF NOT EXISTS user_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    notification_email BOOLEAN DEFAULT 1,
    notification_push BOOLEAN DEFAULT 1,
    theme VARCHAR(20) DEFAULT 'light',
    language VARCHAR(10) DEFAULT 'ar',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if (!$conn->query($sql)) {
    die("فشل إنشاء جدول إعدادات المستخدمين: " . $conn->error);
}

$username = 'test_user';
$email = 'test@example.com';
$password = password_hash('password123', PASSWORD_DEFAULT);

$stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
$stmt->bind_param("ss", $username, $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, first_name, last_name) VALUES (?, ?, ?, 'مستخدم', 'تجريبي')");
    $stmt->bind_param("sss", $username, $email, $password);
    
    if (!$stmt->execute()) {
        echo "فشل إنشاء المستخدم التجريبي: " . $stmt->error . "<br>";
    } else {
        $user_id = $conn->insert_id;
        
        $stmt = $conn->prepare("INSERT INTO user_settings (user_id) VALUES (?)");
        $stmt->bind_param("i", $user_id);
        
        if (!$stmt->execute()) {
            echo "فشل إنشاء إعدادات المستخدم التجريبي: " . $stmt->error . "<br>";
        }
    }
}

$sql = "CREATE TABLE IF NOT EXISTS follows (
    id INT AUTO_INCREMENT PRIMARY KEY,
    follower_id INT NOT NULL,
    followed_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_follow (follower_id, followed_id),
    FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (followed_id) REFERENCES users(id) ON DELETE CASCADE
)";

if (!$conn->query($sql)) {
    die("فشل إنشاء جدول المتابعين: " . $conn->error);
}

$sql = "CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    image_url VARCHAR(255),
    likes_count INT DEFAULT 0,
    comments_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if (!$conn->query($sql)) {
    die("فشل إنشاء جدول المنشورات: " . $conn->error);
}

$conn->close();

echo '<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعداد قاعدة البيانات</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            text-align: center;
            direction: rtl;
        }
        .message {
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .links {
            margin-top: 30px;
        }
        .links a {
            display: inline-block;
            margin: 10px;
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .links a:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <h1>إعداد قاعدة البيانات</h1>
    
    <div class="message">
        تم إعداد قاعدة البيانات بنجاح!<br>
        تم إنشاء مستخدم تجريبي للاختبار:<br>
        اسم المستخدم: test_user<br>
        كلمة المرور: password123<br>
        البريد الإلكتروني: test@example.com
    </div>
    
    <div class="links">
        <h2>روابط مفيدة للاختبار:</h2>
        <a href="/WEP/api/login.php">صفحة تسجيل الدخول</a>
        <a href="/WEP/api/public_api.php?action=users">عرض المستخدمين (API)</a>
        <a href="/WEP/api/users.php">واجهة المستخدمين (API)</a>
    </div>
</body>
</html>';
?>
