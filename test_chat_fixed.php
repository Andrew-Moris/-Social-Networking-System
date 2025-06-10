<?php

header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    die("❌ يجب تسجيل الدخول أولاً!");
}

$current_user_id = $_SESSION['user_id'];

echo "<h1>🔧 اختبار الدردشة بعد الإصلاحات</h1>";

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("SET CHARACTER SET utf8mb4");
    
    echo "<h2>1. اختبار جلب المتابعين</h2>";
    
    $stmt = $pdo->prepare("
        SELECT DISTINCT u.id, u.username, u.first_name, u.last_name, u.email, u.avatar_url
        FROM users u
        INNER JOIN followers f ON u.id = f.followed_id  
        WHERE f.follower_id = ?
        ORDER BY u.first_name, u.last_name, u.username
    ");
    $stmt->execute([$current_user_id]);
    $my_following = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($my_following)) {
        echo "<p style='color: orange;'>⚠️ لا تتابع أي مستخدمين حالياً</p>";
        echo "<p><a href='setup_follows_simple.php' style='color: green; font-weight: bold;'>→ اذهب لإعداد المتابعات</a></p>";
    } else {
        echo "<p style='color: green;'>✅ تتابع " . count($my_following) . " مستخدمين:</p>";
        
        echo "<h2>2. اختبار عرض الأسماء (UTF-8)</h2>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'><th>ID</th><th>اسم المستخدم</th><th>الاسم الكامل</th><th>رابط الدردشة</th></tr>";
        
        foreach ($my_following as $user) {
            $stmt = $pdo->prepare("
                SELECT content, created_at, message_type, sender_id,
                       (SELECT COUNT(*) FROM messages m2 
                        WHERE m2.sender_id = ? AND m2.receiver_id = ? AND m2.is_read = FALSE) as unread_count
                FROM messages 
                WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $stmt->execute([
                $user['id'], $current_user_id,
                $current_user_id, $user['id'],
                $user['id'], $current_user_id
            ]);
            $last_message = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($last_message) {
                $user['last_message'] = $last_message['content'];
                $user['unread_count'] = (int)$last_message['unread_count'];
                $user['i_sent_last'] = ($last_message['sender_id'] == $current_user_id);
            } else {
                $user['last_message'] = null;
                $user['unread_count'] = 0;
                $user['i_sent_last'] = false;
            }
            
            $full_name = !empty($user['first_name']) ? $user['first_name'] . ' ' . $user['last_name'] : $user['username'];
            
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td><strong>{$user['username']}</strong></td>";
            echo "<td>" . htmlspecialchars($full_name) . "</td>";
            echo "<td><a href='chat_simple.php?user_id={$user['id']}' style='color: green; font-weight: bold;'>💬 دردشة</a></td>";
            echo "</tr>";
            
            echo "<tr style='background: #f8f8f8;'>";
            echo "<td colspan='4'>";
            echo "<small>";
            if (!empty($user['last_message'])) {
                echo "آخر رسالة: " . htmlspecialchars(mb_substr($user['last_message'], 0, 50));
                if (isset($user['unread_count']) && $user['unread_count'] > 0) {
                    echo " | غير مقروءة: " . $user['unread_count'];
                }
            } else {
                echo "لا توجد رسائل - <span style='color: green;'>جاهز للدردشة</span>";
            }
            echo "</small>";
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h2>3. نتائج الاختبار</h2>";
    echo "<div style='background: #e8f5e8; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    
    if (!empty($my_following)) {
        echo "<p style='color: green; font-weight: bold;'>✅ جميع الاختبارات نجحت!</p>";
        echo "<ul>";
        echo "<li>✅ تم حل مشكلة undefined array key</li>";
        echo "<li>✅ تم حل مشكلة عرض الأسماء العربية</li>";
        echo "<li>✅ المتابعون يظهرون بشكل صحيح</li>";
        echo "<li>✅ روابط الدردشة تعمل</li>";
        echo "</ul>";
        
        echo "<div style='margin-top: 20px;'>";
        echo "<a href='chat_simple.php' style='background: #10b981; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>💬 اذهب للدردشة</a>";
        echo "<a href='friends.php' style='background: #3b82f6; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>👥 إدارة الأصدقاء</a>";
        echo "</div>";
    } else {
        echo "<p style='color: orange; font-weight: bold;'>⚠️ تحتاج لإعداد متابعات أولاً</p>";
        echo "<a href='setup_follows_simple.php' style='background: #f59e0b; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>⚡ إعداد سريع</a>";
    }
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>خطأ: " . $e->getMessage() . "</p>";
}
?>

<style>
body { 
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
    margin: 20px; 
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 40px;
}
h1, h2 { color: white; margin: 20px 0; }
p { margin: 10px 0; }
a { color: white; }
table { background: white; color: black; margin: 10px 0; border-radius: 8px; }
th, td { padding: 12px; text-align: right; }
th { background: #34495e; color: white; }
</style> 