<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$dbname = 'wep_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ تم الاتصال بخادم MySQL بنجاح<br>";
    
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ تم إنشاء/التحقق من قاعدة البيانات $dbname<br>";
    
    $pdo->exec("USE $dbname");
    echo "✅ تم اختيار قاعدة البيانات $dbname<br>";
    
    $tables_to_drop = [
        'messages',
        'messages_enhanced',
        'notifications',
        'likes',
        'comments',
        'comment_likes',
        'comment_dislikes',
        'post_likes',
        'post_dislikes',
        'post_hashtags',
        'posts',
        'followers',
        'follows',
        'friendships',
        'friend_requests',
        'remember_tokens',
        'users'
    ];
    
    foreach ($tables_to_drop as $table) {
        try {
            $pdo->exec("DROP TABLE IF EXISTS $table CASCADE");
            echo "✅ تم حذف جدول $table<br>";
        } catch (PDOException $e) {
            echo "⚠️ تعذر حذف جدول $table: " . $e->getMessage() . "<br>";
        }
    }
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        role ENUM('user', 'admin') DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    echo "✅ تم إنشاء جدول المستخدمين الجديد<br>";
    
    $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $check_stmt->execute(['test']);
    $user_exists = (int)$check_stmt->fetchColumn() > 0;
    
    if (!$user_exists) {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, first_name, last_name) VALUES (?, ?, ?, ?, ?)");
        $password_hash = password_hash('123456', PASSWORD_DEFAULT);
        $stmt->execute(['test', 'test@example.com', $password_hash, 'Test', 'User']);
        echo "✅ تم إنشاء مستخدم تجريبي<br>";
    } else {
        echo "✅ المستخدم التجريبي موجود بالفعل<br>";
    }
    
    echo "<br>معلومات تسجيل الدخول التجريبية:<br>";
    echo "اسم المستخدم: test<br>";
    echo "كلمة المرور: 123456<br>";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $count = $stmt->fetchColumn();
    echo "<br>عدد المستخدمين في قاعدة البيانات: " . $count . "<br>";
    
    echo "<hr>";
    echo "<h3>معلومات الاتصال بقاعدة البيانات:</h3>";
    echo "نوع قاعدة البيانات: MySQL<br>";
    echo "المضيف: $host<br>";
    echo "اسم قاعدة البيانات: $dbname<br>";
    echo "اسم المستخدم: $username<br>";
    
} catch(PDOException $e) {
    die("❌ خطأ: " . $e->getMessage());
}
?> 