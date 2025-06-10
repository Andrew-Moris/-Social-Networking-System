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
    }
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute(['admin']);
    $admin_exists = $stmt->fetch();
    
    if ($admin_exists) {
        $hashed_password = password_hash('admin', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ?, is_admin = TRUE WHERE username = ?");
        $stmt->execute([$hashed_password, 'admin']);
        echo "تم تحديث بيانات المستخدم admin بنجاح<br>";
    } else {
        $hashed_password = password_hash('admin', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, first_name, last_name, is_admin) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute(['admin', 'admin@wep.com', $hashed_password, 'مدير', 'النظام', true]);
        echo "تم إنشاء مستخدم admin جديد بنجاح<br>";
    }
    
    echo "بيانات الدخول للمسؤول:<br>";
    echo "اسم المستخدم: admin<br>";
    echo "كلمة المرور: admin<br>";
    echo '<br><a href="login.php" style="color: blue;">انتقل إلى صفحة تسجيل الدخول</a>';
    
} catch (PDOException $e) {
    echo "حدث خطأ: " . $e->getMessage();
}
?>
