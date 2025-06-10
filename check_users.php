<?php
require_once 'config.php';

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
    
    $stmt = $pdo->query("SELECT id, username, email, first_name, last_name, created_at FROM users ORDER BY id DESC");
    $users = $stmt->fetchAll();
    
    echo "<h2>قائمة المستخدمين في قاعدة البيانات</h2>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f2f2f2;'>";
    echo "<th>ID</th>";
    echo "<th>اسم المستخدم</th>";
    echo "<th>البريد الإلكتروني</th>";
    echo "<th>الاسم الأول</th>";
    echo "<th>اسم العائلة</th>";
    echo "<th>تاريخ الإنشاء</th>";
    echo "</tr>";
    
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($user['id']) . "</td>";
        echo "<td>" . htmlspecialchars($user['username']) . "</td>";
        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
        echo "<td>" . htmlspecialchars($user['first_name']) . "</td>";
        echo "<td>" . htmlspecialchars($user['last_name']) . "</td>";
        echo "<td>" . htmlspecialchars($user['created_at']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $total = $stmt->fetch()['total'];
    echo "<p>إجمالي عدد المستخدمين: " . $total . "</p>";
    
} catch(PDOException $e) {
    echo "خطأ: " . $e->getMessage();
}
?> 