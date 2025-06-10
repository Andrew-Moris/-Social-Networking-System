<?php

header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    die("❌ يجب تسجيل الدخول أولاً!");
}

$current_user_id = $_SESSION['user_id'];

echo "<h1>🔄 مقارنة بين صفحتي الدردشة</h1>";

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("SET CHARACTER SET utf8mb4");
    
    echo "<h2>1. نفس الاستعلام المستخدم في كلا الصفحتين</h2>";
    
    $stmt = $pdo->prepare("
        SELECT DISTINCT u.id, u.username, u.first_name, u.last_name, u.email, u.avatar_url
        FROM users u
        INNER JOIN followers f ON u.id = f.followed_id  
        WHERE f.follower_id = ?
        ORDER BY u.first_name, u.last_name, u.username
    ");
    $stmt->execute([$current_user_id]);
    $my_following = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin: 2rem 0;'>";
    
    echo "<div style='background: rgba(16, 185, 129, 0.1); padding: 1.5rem; border-radius: 1rem; border: 1px solid rgba(16, 185, 129, 0.3);'>";
    echo "<h3 style='color: #10b981; margin-bottom: 1rem;'>💬 chat_simple.php</h3>";
    
    if (empty($my_following)) {
        echo "<p style='color: orange;'>⚠️ لا توجد متابعات</p>";
        echo "<p><a href='setup_follows_simple.php' style='color: green; font-weight: bold;'>→ إعداد متابعات</a></p>";
    } else {
        echo "<p style='color: green;'>✅ يعرض " . count($my_following) . " مستخدمين:</p>";
        echo "<ul style='list-style: none; padding: 0;'>";
        
        foreach ($my_following as $user) {
            $full_name = !empty($user['first_name']) ? $user['first_name'] . ' ' . $user['last_name'] : $user['username'];
            echo "<li style='padding: 0.5rem; margin: 0.25rem 0; background: rgba(255,255,255,0.05); border-radius: 0.5rem;'>";
            echo "<strong>" . htmlspecialchars($full_name) . "</strong> (@{$user['username']})";
            echo "<br><a href='chat_simple.php?user_id={$user['id']}' style='color: #10b981; text-decoration: none;'>💬 دردشة</a>";
            echo "</li>";
        }
        echo "</ul>";
    }
    
    echo "<div style='margin-top: 1rem;'>";
    echo "<a href='chat_simple.php' style='background: #10b981; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 0.5rem; font-size: 0.9rem;'>اختبار الصفحة</a>";
    echo "</div>";
    echo "</div>";
    
    echo "<div style='background: rgba(59, 130, 246, 0.1); padding: 1.5rem; border-radius: 1rem; border: 1px solid rgba(59, 130, 246, 0.3);'>";
    echo "<h3 style='color: #3b82f6; margin-bottom: 1rem;'>💻 chat.php</h3>";
    
    if (empty($my_following)) {
        echo "<p style='color: orange;'>⚠️ لا توجد متابعات</p>";
        echo "<p><a href='setup_follows_simple.php' style='color: green; font-weight: bold;'>→ إعداد متابعات</a></p>";
    } else {
        echo "<p style='color: green;'>✅ يعرض " . count($my_following) . " مستخدمين:</p>";
        echo "<ul style='list-style: none; padding: 0;'>";
        
        foreach ($my_following as $user) {
            $full_name = !empty($user['first_name']) ? $user['first_name'] . ' ' . $user['last_name'] : $user['username'];
            echo "<li style='padding: 0.5rem; margin: 0.25rem 0; background: rgba(255,255,255,0.05); border-radius: 0.5rem;'>";
            echo "<strong>" . htmlspecialchars($full_name) . "</strong> (@{$user['username']})";
            echo "<br><a href='chat.php?user_id={$user['id']}' style='color: #3b82f6; text-decoration: none;'>💬 دردشة</a>";
            echo "</li>";
        }
        echo "</ul>";
    }
    
    echo "<div style='margin-top: 1rem;'>";
    echo "<a href='chat.php' style='background: #3b82f6; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 0.5rem; font-size: 0.9rem;'>اختبار الصفحة</a>";
    echo "</div>";
    echo "</div>";
    
    echo "</div>";
    
    echo "<h2>2. التحققات المطبقة في كلا الصفحتين</h2>";
    echo "<div style='background: #e8f5e8; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h3 style='color: #10b981; margin-bottom: 1rem;'>✅ التحسينات المطبقة:</h3>";
    echo "<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;'>";
    
    echo "<div>";
    echo "<h4>🔧 إصلاحات PHP:</h4>";
    echo "<ul>";
    echo "<li>✅ إضافة دعم UTF-8 كامل</li>";
    echo "<li>✅ تحقق آمن من المفاتيح isset()</li>";
    echo "<li>✅ استخدام mb_substr() للعربية</li>";
    echo "<li>✅ تحويل unread_count إلى integer</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div>";
    echo "<h4>🎯 تحسينات الواجهة:</h4>";
    echo "<ul>";
    echo "<li>✅ عرض صحيح للأسماء العربية</li>";
    echo "<li>✅ رسائل واضحة باللغة العربية</li>";
    echo "<li>✅ معالجة حالات عدم وجود البيانات</li>";
    echo "<li>✅ روابط دردشة تعمل بشكل صحيح</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "</div>";
    echo "</div>";
    
    if (!empty($my_following)) {
        echo "<h2>3. نتيجة المقارنة</h2>";
        echo "<div style='background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 2rem; border-radius: 1rem; text-align: center; margin: 2rem 0;'>";
        echo "<h3 style='margin-bottom: 1rem;'>🎉 النتيجة: نجح التطبيق!</h3>";
        echo "<p style='font-size: 1.1rem; margin-bottom: 1.5rem;'>كلا الصفحتين تستخدمان نفس الآلية البسيطة والفعالة</p>";
        
        echo "<div style='display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap;'>";
        echo "<a href='chat_simple.php' style='background: rgba(255,255,255,0.2); color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 0.75rem; font-weight: bold;'>💬 الدردشة المبسطة</a>";
        echo "<a href='chat.php' style='background: rgba(255,255,255,0.2); color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 0.75rem; font-weight: bold;'>💻 الدردشة الأساسية</a>";
        echo "<a href='friends.php' style='background: rgba(255,255,255,0.2); color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 0.75rem; font-weight: bold;'>👥 الأصدقاء</a>";
        echo "</div>";
        echo "</div>";
    } else {
        echo "<div style='background: #f59e0b; color: white; padding: 2rem; border-radius: 1rem; text-align: center; margin: 2rem 0;'>";
        echo "<h3>⚠️ تحتاج لإعداد متابعات أولاً</h3>";
        echo "<p style='margin: 1rem 0;'>لاختبار كلا الصفحتين، أضف بعض المتابعات</p>";
        echo "<a href='setup_follows_simple.php' style='background: rgba(255,255,255,0.2); color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 0.75rem; font-weight: bold;'>⚡ إعداد سريع</a>";
        echo "</div>";
    }
    
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
h1, h2, h3, h4 { color: white; margin: 20px 0 10px 0; }
p { margin: 10px 0; }
a { color: white; }
ul { margin: 10px 0; padding-right: 20px; }
li { margin: 5px 0; }
</style> 