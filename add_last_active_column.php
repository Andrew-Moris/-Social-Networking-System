<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$host = 'localhost';
$dbname = 'wep_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>إضافة عمود last_active إلى جدول المستخدمين</h1>";
    
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'last_active'");
    $columnExists = $stmt->rowCount() > 0;
    
    if (!$columnExists) {
        $pdo->exec("ALTER TABLE users ADD COLUMN last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        echo "<p style='color:green;'>تم إضافة عمود last_active بنجاح!</p>";
        
        $pdo->exec("UPDATE users SET last_active = NOW()");
        echo "<p>تم تحديث قيم last_active للمستخدمين الحاليين.</p>";
    } else {
        echo "<p>العمود last_active موجود بالفعل في جدول المستخدمين.</p>";
    }
    
    echo "<p><a href='chat.php'>العودة إلى صفحة المحادثة</a></p>";
    
} catch(PDOException $e) {
    echo "<h2 style='color:red;'>خطأ في الاتصال بقاعدة البيانات:</h2>";
    echo "<p>{$e->getMessage()}</p>";
}
?>
