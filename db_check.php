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
    
    echo "<h2>تم الاتصال بقاعدة البيانات بنجاح!</h2>";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "<p>جدول المستخدمين موجود.</p>";
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM users");
        $userCount = $stmt->fetchColumn();
        echo "<p>عدد المستخدمين: $userCount</p>";
        
        $stmt = $pdo->query("SELECT id, username, first_name, last_name FROM users LIMIT 10");
        echo "<h3>قائمة المستخدمين:</h3>";
        echo "<ul>";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<li>ID: {$row['id']} - {$row['username']} ({$row['first_name']} {$row['last_name']})</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color:red;'>جدول المستخدمين غير موجود!</p>";
    }
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'messages'");
    if ($stmt->rowCount() > 0) {
        echo "<p>جدول الرسائل موجود.</p>";
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM messages");
        $messageCount = $stmt->fetchColumn();
        echo "<p>عدد الرسائل: $messageCount</p>";
    } else {
        echo "<p style='color:red;'>جدول الرسائل غير موجود!</p>";
    }
    
} catch(PDOException $e) {
    echo "<h2 style='color:red;'>خطأ في الاتصال بقاعدة البيانات:</h2>";
    echo "<p>{$e->getMessage()}</p>";
}
?>
