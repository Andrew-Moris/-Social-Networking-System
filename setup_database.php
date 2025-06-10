<?php
require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $pdo->exec("DROP DATABASE IF EXISTS " . DB_NAME);
    echo "تم حذف قاعدة البيانات القديمة بنجاح\n";
    
    $pdo->exec("CREATE DATABASE " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "تم إنشاء قاعدة البيانات الجديدة بنجاح\n";
    
    $pdo->exec("USE " . DB_NAME);
    
    $sql = "CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        email VARCHAR(255),
        bio TEXT,
        avatar VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "تم إنشاء جدول المستخدمين بنجاح\n";

    $sql = "CREATE TABLE posts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        content TEXT,
        media_url VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "تم إنشاء جدول المنشورات بنجاح\n";

    $sql = "CREATE TABLE followers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        follower_id INT NOT NULL,
        following_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_follow (follower_id, following_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "تم إنشاء جدول المتابعين بنجاح\n";
    
    $test_username = "test";
    $test_password = password_hash("123456", PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password, first_name, last_name) VALUES (?, ?, 'مستخدم', 'تجريبي')");
    $stmt->execute([$test_username, $test_password]);
    echo "تم إنشاء مستخدم تجريبي بنجاح\n";
    
    $user_id = $pdo->lastInsertId();
    $posts = [
        'مرحباً بكم في موقعنا!',
        'هذا منشور تجريبي',
        'نتمنى لكم تجربة ممتعة'
    ];
    
    foreach ($posts as $content) {
        $stmt = $pdo->prepare("INSERT INTO posts (user_id, content) VALUES (?, ?)");
        $stmt->execute([$user_id, $content]);
    }
    echo "تم إضافة منشورات تجريبية بنجاح\n";
    
    echo "\nتم إعداد قاعدة البيانات بنجاح!";
    
} catch(PDOException $e) {
    die("خطأ في إعداد قاعدة البيانات: " . $e->getMessage() . "\n");
}
?> 