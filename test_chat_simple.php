<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "❌ يجب تسجيل الدخول أولاً!";
    exit;
}

$current_user_id = $_SESSION['user_id'];

echo "<h1>اختبار نظام الدردشة المبسط</h1>";
echo "<p><strong>المستخدم الحالي:</strong> ID $current_user_id</p>";

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$current_user_id]);
    $current_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h2>1. بيانات المستخدم الحالي</h2>";
    echo "<p>الاسم: {$current_user['username']} - {$current_user['first_name']} {$current_user['last_name']}</p>";
    
    echo "<h2>2. المستخدمين الذين أتابعهم</h2>";
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.first_name, u.last_name 
        FROM users u
        INNER JOIN followers f ON u.id = f.followed_id
        WHERE f.follower_id = ?
    ");
    $stmt->execute([$current_user_id]);
    $following = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($following)) {
        echo "<p style='color: orange;'>⚠️ لا تتابع أي مستخدمين!</p>";
        echo "<p><a href='friends.php' style='color: blue;'>→ اذهب لصفحة الأصدقاء لمتابعة مستخدمين</a></p>";
    } else {
        echo "<p style='color: green;'>✓ تتابع " . count($following) . " مستخدمين:</p>";
        echo "<ul>";
        foreach ($following as $user) {
            echo "<li>{$user['username']} (ID: {$user['id']}) - {$user['first_name']} {$user['last_name']}</li>";
        }
        echo "</ul>";
    }
    
    echo "<h2>3. المستخدمين الذين يتابعونني</h2>";
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.first_name, u.last_name 
        FROM users u
        INNER JOIN followers f ON u.id = f.follower_id
        WHERE f.followed_id = ?
    ");
    $stmt->execute([$current_user_id]);
    $followers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($followers)) {
        echo "<p style='color: orange;'>⚠️ لا يتابعك أي مستخدمين!</p>";
    } else {
        echo "<p style='color: green;'>✓ يتابعك " . count($followers) . " مستخدمين:</p>";
        echo "<ul>";
        foreach ($followers as $user) {
            echo "<li>{$user['username']} (ID: {$user['id']}) - {$user['first_name']} {$user['last_name']}</li>";
        }
        echo "</ul>";
    }
    
    echo "<h2>4. اختبار استعلام الدردشة</h2>";
    
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.first_name, u.last_name, 
               'following' as relationship_type
        FROM users u
        INNER JOIN followers f ON u.id = f.followed_id
        WHERE f.follower_id = ? AND u.id != ?
        ORDER BY u.first_name, u.username
    ");
    $stmt->execute([$current_user_id, $current_user_id]);
    $chat_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($chat_users)) {
        echo "<p style='color: red;'>❌ لا توجد مستخدمين في قائمة الدردشة!</p>";
        echo "<p><strong>السبب:</strong> لا تتابع أي مستخدمين أو يوجد خطأ في الاستعلام</p>";
    } else {
        echo "<p style='color: green;'>✓ يجب أن يظهر " . count($chat_users) . " مستخدمين في الدردشة:</p>";
        echo "<ul>";
        foreach ($chat_users as $user) {
            $chat_link = "chat.php?user_id={$user['id']}";
            echo "<li><a href='$chat_link' style='color: blue;'>{$user['username']} - {$user['first_name']} {$user['last_name']}</a> (علاقة: {$user['relationship_type']})</li>";
        }
        echo "</ul>";
    }
    
    echo "<h2>5. روابط مفيدة</h2>";
    echo "<div style='margin: 20px 0;'>";
    echo "<a href='chat.php' style='display: inline-block; background: #10b981; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>فتح الدردشة</a>";
    echo "<a href='friends.php' style='display: inline-block; background: #3b82f6; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>صفحة الأصدقاء</a>";
    echo "<a href='create_test_users.php' style='display: inline-block; background: #f59e0b; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>إنشاء مستخدمين تجريبيين</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>خطأ: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
h1, h2 { color: #333; }
ul { background: white; padding: 15px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
li { margin: 5px 0; }
</style> 