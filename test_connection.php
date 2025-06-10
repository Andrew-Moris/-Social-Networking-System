<?php
require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
    echo "✅ تم الاتصال بخادم MySQL بنجاح<br>";
    
    $result = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . DB_NAME . "'")->fetch();
    
    if ($result) {
        echo "✅ قاعدة البيانات " . DB_NAME . " موجودة<br>";
        
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASS, $pdo_options);
        echo "✅ تم الاتصال بقاعدة البيانات " . DB_NAME . " بنجاح<br>";
        
        $tables = ['users', 'posts', 'comments', 'likes', 'followers', 'notifications', 'messages', 'shares', 'bookmarks'];
        foreach ($tables as $table) {
            $result = $pdo->query("SHOW TABLES LIKE '$table'")->fetch();
            if ($result) {
                echo "✅ جدول $table موجود<br>";
            } else {
                echo "❌ جدول $table غير موجود<br>";
            }
        }
    } else {
        echo "❌ قاعدة البيانات " . DB_NAME . " غير موجودة<br>";
    }
    
} catch (PDOException $e) {
    echo "❌ خطأ في الاتصال: " . $e->getMessage() . "<br>";
    echo "معلومات الاتصال:<br>";
    echo "المضيف: " . DB_HOST . "<br>";
    echo "المستخدم: " . DB_USER . "<br>";
    echo "قاعدة البيانات: " . DB_NAME . "<br>";
} 