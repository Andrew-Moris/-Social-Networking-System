<?php
require_once '../config.php';
require_once '../functions.php';

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
    
    $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'is_admin'");
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN is_admin BOOLEAN DEFAULT FALSE");
        echo "تم إضافة حقل is_admin إلى جدول المستخدمين بنجاح<br>";
        
        $pdo->exec("UPDATE users SET is_admin = TRUE WHERE id = 1");
        echo "تم تعيين المستخدم الأول كمسؤول<br>";
        
        echo '<a href="login.php" class="text-blue-500">انتقل إلى صفحة تسجيل الدخول</a>';
    } else {
        echo "حقل is_admin موجود بالفعل في جدول المستخدمين<br>";
        echo '<a href="login.php" class="text-blue-500">انتقل إلى صفحة تسجيل الدخول</a>';
    }
    
} catch (PDOException $e) {
    echo "حدث خطأ: " . $e->getMessage();
}
?>
