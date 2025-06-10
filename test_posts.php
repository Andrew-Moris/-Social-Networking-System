<?php

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

$host = 'localhost';
$dbname = 'wep_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>اختبار المنشورات</h1>";
    
    echo "<h2>هيكل جدول 'posts'</h2>";
    $stmt = $pdo->query("DESCRIBE posts");
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        foreach ($row as $key => $value) {
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>جميع المنشورات</h2>";
    $stmt = $pdo->query("SELECT * FROM posts ORDER BY created_at DESC");
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($posts) > 0) {
        echo "<p>عدد المنشورات: " . count($posts) . "</p>";
        echo "<table border='1'><tr>";
        foreach (array_keys($posts[0]) as $header) {
            echo "<th>" . htmlspecialchars($header) . "</th>";
        }
        echo "</tr>";
        
        foreach ($posts as $post) {
            echo "<tr>";
            foreach ($post as $key => $value) {
                if ($key == 'media_url' && !empty($value)) {
                    $imgPath = $value;
                    if (strpos($value, '/') !== 0 && strpos($value, 'http') !== 0) {
                        $imgPath = '/WEP/' . $value;
                    }
                    echo "<td>" . htmlspecialchars($value) . "<br>";
                    if (strpos($value, '.mp4') !== false) {
                        echo "<video width='200' controls><source src='" . htmlspecialchars($imgPath) . "' type='video/mp4'></video>";
                    } else {
                        echo "<img src='" . htmlspecialchars($imgPath) . "' width='200'>";
                    }
                    echo "</td>";
                } else {
                    echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                }
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>لا توجد منشورات!</p>";
    }
    
    echo "<h2>اختبار استعلام posts.php</h2>";
    
    $stmt = $pdo->prepare("
        SELECT 
            p.*,
            u.username,
            u.first_name,
            u.last_name,
            u.avatar
        FROM posts p
        JOIN users u ON p.user_id = u.id
        ORDER BY p.created_at DESC
    ");
    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($posts) > 0) {
        echo "<p>عدد المنشورات: " . count($posts) . "</p>";
        echo "<table border='1'><tr>";
        foreach (array_keys($posts[0]) as $header) {
            echo "<th>" . htmlspecialchars($header) . "</th>";
        }
        echo "</tr>";
        
        foreach ($posts as $post) {
            echo "<tr>";
            foreach ($post as $key => $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>لا توجد منشورات من الاستعلام!</p>";
    }
    
    echo "<h2>جدول المستخدمين</h2>";
    $stmt = $pdo->query("DESCRIBE users");
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        foreach ($row as $key => $value) {
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    
} catch (PDOException $e) {
    echo "<h1>خطأ في قاعدة البيانات</h1>";
    echo "<p style='color:red'>" . $e->getMessage() . "</p>";
}
?>
