<?php
header('Content-Type: text/html; charset=utf-8');

$host = 'localhost';
$dbname = 'wep_db';
$user = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>هيكل جدول 'users'</h2>";
    $stmt = $pdo->prepare("DESCRIBE users");
    $stmt->execute();
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>عينة من المستخدمين</h2>";
    $stmt = $pdo->prepare("SELECT id, username, first_name, last_name, avatar, avatar_url FROM users LIMIT 1");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "<table border='1'>";
        echo "<tr>";
        foreach ($user as $key => $value) {
            echo "<th>{$key}</th>";
        }
        echo "</tr>";
        echo "<tr>";
        foreach ($user as $value) {
            echo "<td>{$value}</td>";
        }
        echo "</tr>";
        echo "</table>";
    } else {
        echo "<p>لا يوجد مستخدمين في قاعدة البيانات</p>";
    }
    
    echo "<h2>هيكل جدول 'posts'</h2>";
    $stmt = $pdo->prepare("DESCRIBE posts");
    $stmt->execute();
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch(PDOException $e) {
    die('Error de conexión: ' . $e->getMessage());
}
?>
