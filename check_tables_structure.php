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
    
    echo "<h1>هيكل جداول قاعدة البيانات wep_db</h1>";
    
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h2>قائمة الجداول:</h2>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
    if (in_array('users', $tables)) {
        echo "<h2>هيكل جدول المستخدمين (users):</h2>";
        $stmt = $pdo->query("DESCRIBE users");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>الحقل</th><th>النوع</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "<td>{$column['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM users");
        $userCount = $stmt->fetchColumn();
        echo "<p>عدد المستخدمين: $userCount</p>";
        
        $stmt = $pdo->query("SELECT * FROM users LIMIT 3");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($users) > 0) {
            echo "<h3>عينة من بيانات المستخدمين:</h3>";
            echo "<pre>";
            print_r($users);
            echo "</pre>";
        }
    }
    
    if (in_array('messages', $tables)) {
        echo "<h2>هيكل جدول الرسائل (messages):</h2>";
        $stmt = $pdo->query("DESCRIBE messages");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>الحقل</th><th>النوع</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "<td>{$column['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM messages");
        $messageCount = $stmt->fetchColumn();
        echo "<p>عدد الرسائل: $messageCount</p>";
        
        if ($messageCount > 0) {
            $stmt = $pdo->query("SELECT * FROM messages LIMIT 3");
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h3>عينة من بيانات الرسائل:</h3>";
            echo "<pre>";
            print_r($messages);
            echo "</pre>";
        }
    }
    
} catch(PDOException $e) {
    echo "<h2 style='color:red;'>خطأ في الاتصال بقاعدة البيانات:</h2>";
    echo "<p>{$e->getMessage()}</p>";
}
?>
