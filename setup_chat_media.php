<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$host = 'localhost';
$dbname = 'wep_db';
$username = 'root';
$password = '';

echo "<h1>إعداد جدول الرسائل مع دعم الصور</h1>";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>تم الاتصال بقاعدة البيانات بنجاح.</p>";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'messages'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        $sql = "CREATE TABLE messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sender_id INT NOT NULL,
            receiver_id INT NOT NULL,
            content TEXT,
            media_url VARCHAR(255) DEFAULT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (sender_id),
            INDEX (receiver_id),
            INDEX (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        echo "<p>تم إنشاء جدول الرسائل بنجاح.</p>";
    } else {
        echo "<p>جدول الرسائل موجود بالفعل.</p>";
        
        $stmt = $pdo->query("SHOW COLUMNS FROM messages LIKE 'media_url'");
        $mediaColumnExists = $stmt->rowCount() > 0;
        
        if (!$mediaColumnExists) {
            $sql = "ALTER TABLE messages ADD COLUMN media_url VARCHAR(255) DEFAULT NULL AFTER content";
            $pdo->exec($sql);
            echo "<p>تم إضافة عمود media_url إلى جدول الرسائل.</p>";
        } else {
            echo "<p>عمود media_url موجود بالفعل في جدول الرسائل.</p>";
        }
    }
    
    $upload_dir = 'uploads/chat_images/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
        echo "<p>تم إنشاء مجلد {$upload_dir} لتخزين صور المحادثة.</p>";
    } else {
        echo "<p>مجلد {$upload_dir} موجود بالفعل.</p>";
    }
    
    echo "<p>تم إعداد قاعدة البيانات ومجلد الصور بنجاح لدعم إرسال الصور في المحادثة.</p>";
    echo "<p><a href='chat_fixed.php'>العودة إلى صفحة المحادثة</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>خطأ في قاعدة البيانات: " . $e->getMessage() . "</p>";
}
?>
