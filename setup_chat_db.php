<?php

$host = 'localhost';
$dbname = 'socialmedia';
$username = 'root';
$password = '';


try {
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "تم إنشاء قاعدة البيانات $dbname بنجاح أو كانت موجودة مسبقاً.<br>";
    
    $pdo->exec("USE `$dbname`");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `username` VARCHAR(50) NOT NULL,
        `email` VARCHAR(100) NOT NULL,
        `password` VARCHAR(255) NOT NULL,
        `first_name` VARCHAR(50) DEFAULT NULL,
        `last_name` VARCHAR(50) DEFAULT NULL,
        `avatar_url` VARCHAR(255) DEFAULT NULL,
        `profile_picture` VARCHAR(255) DEFAULT NULL,
        `bio` TEXT DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `last_active` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `username` (`username`),
        UNIQUE KEY `email` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "تم إنشاء جدول المستخدمين بنجاح.<br>";
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS `messages` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `sender_id` INT(11) NOT NULL,
        `receiver_id` INT(11) NOT NULL,
        `content` TEXT NOT NULL,
        `media_url` VARCHAR(255) DEFAULT NULL,
        `is_read` TINYINT(1) DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `sender_id` (`sender_id`),
        KEY `receiver_id` (`receiver_id`),
        CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
        CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "تم إنشاء جدول الرسائل بنجاح.<br>";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM `users`");
    $userCount = $stmt->fetchColumn();
    
    if ($userCount == 0) {
        $hashedPassword = password_hash('password123', PASSWORD_DEFAULT);
        
        $pdo->exec("INSERT INTO `users` (`username`, `email`, `password`, `first_name`, `last_name`) VALUES 
            ('user1', 'user1@example.com', '$hashedPassword', 'المستخدم', 'الأول'),
            ('user2', 'user2@example.com', '$hashedPassword', 'المستخدم', 'الثاني'),
            ('user3', 'user3@example.com', '$hashedPassword', 'المستخدم', 'الثالث')");
        
        echo "تم إضافة مستخدمين للاختبار.<br>";
    }
    
    echo "<br>تم إعداد قاعدة البيانات بنجاح! يمكنك الآن استخدام نظام المحادثات.";
    
} catch(PDOException $e) {
    die("خطأ في إعداد قاعدة البيانات: " . $e->getMessage());
}
?>
