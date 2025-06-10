<?php
session_start();
require_once '../config.php';
require_once '../functions.php';

if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
    
    $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'is_admin'");
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN is_admin BOOLEAN DEFAULT FALSE");
        echo "✅ تم إضافة حقل is_admin إلى جدول المستخدمين بنجاح";
        
        $pdo->exec("UPDATE users SET is_admin = TRUE WHERE id = 1");
        echo "<br>✅ تم تعيين المستخدم الأول كمسؤول";
    } else {
        echo "ℹ️ حقل is_admin موجود بالفعل في جدول المستخدمين";
    }
    
} catch (PDOException $e) {
    echo "❌ حدث خطأ: " . $e->getMessage();
}
?>
