<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$host = 'localhost';
$dbname = 'wep_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'last_message_time'");
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        $stmt = $pdo->prepare("ALTER TABLE users ADD COLUMN last_message_time DATETIME NULL DEFAULT NULL");
        $stmt->execute();
        echo "تم إضافة عمود last_message_time إلى جدول users بنجاح.<br>";
        
        $stmt = $pdo->prepare("
            UPDATE users u
            LEFT JOIN (
                SELECT 
                    CASE 
                        WHEN sender_id = receiver_id THEN sender_id
                        ELSE 
                            CASE 
                                WHEN sender_id > receiver_id THEN sender_id
                                ELSE receiver_id
                            END
                    END as user_id,
                    MAX(created_at) as last_time
                FROM messages
                GROUP BY user_id
            ) m ON u.id = m.user_id
            SET u.last_message_time = m.last_time
        ");
        $stmt->execute();
        echo "تم تحديث قيم last_message_time لجميع المستخدمين بنجاح.<br>";
    } else {
        echo "عمود last_message_time موجود بالفعل في جدول users.<br>";
    }
    
    echo "تم إصلاح قاعدة البيانات بنجاح!";
    
} catch (PDOException $e) {
    echo "خطأ في قاعدة البيانات: " . $e->getMessage();
}
?>
