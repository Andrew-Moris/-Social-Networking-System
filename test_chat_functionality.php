<?php

header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    die("❌ يجب تسجيل الدخول أولاً!");
}

$current_user_id = $_SESSION['user_id'];

echo "<h1>🧪 اختبار وظائف الدردشة المحدثة</h1>";

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("SET CHARACTER SET utf8mb4");
    
    echo "<div style='background: #e8f5e8; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h2 style='color: #10b981;'>✅ التحديثات المطبقة:</h2>";
    echo "<ul>";
    echo "<li>✅ إصلاح مسارات الصور الافتراضية</li>";
    echo "<li>✅ تحسين وظيفة selectUser() في JavaScript</li>";
    echo "<li>✅ إضافة وظائف تصحيح الأخطاء</li>";
    echo "<li>✅ تحسين معالجة النقر على المستخدمين</li>";
    echo "<li>✅ ترجمة الرسائل إلى العربية</li>";
    echo "</ul>";
    echo "</div>";
    
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
        echo "<div style='background: #f59e0b; color: white; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
        echo "<h3>⚠️ لا توجد متابعات</h3>";
        echo "<p>تحتاج لمتابعة بعض المستخدمين أولاً لاختبار الدردشة</p>";
        echo "<a href='setup_follows_simple.php' style='background: rgba(255,255,255,0.2); color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>⚡ إعداد متابعات</a>";
        echo "</div>";
    } else {
        echo "<div style='background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
        echo "<h3>🎉 جاهز للاختبار!</h3>";
        echo "<p>عدد المستخدمين المتاحين للدردشة: <strong>" . count($my_following) . "</strong></p>";
        echo "</div>";
        
        echo "<h2>👥 المستخدمين المتاحين للدردشة:</h2>";
        echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 20px 0;'>";
        
        foreach ($my_following as $user) {
            $full_name = !empty($user['first_name']) ? $user['first_name'] . ' ' . $user['last_name'] : $user['username'];
            $avatar_url = !empty($user['avatar_url']) ? $user['avatar_url'] : 'https://ui-avatars.com/api/?name=' . urlencode($user['username']) . '&background=667eea&color=fff&size=100';
            
            echo "<div style='background: rgba(255,255,255,0.1); padding: 15px; border-radius: 10px; border: 1px solid rgba(102, 126, 234, 0.3);'>";
            echo "<div style='display: flex; align-items: center; gap: 10px; margin-bottom: 10px;'>";
            echo "<img src='{$avatar_url}' style='width: 50px; height: 50px; border-radius: 50%; object-fit: cover;'>";
            echo "<div>";
            echo "<strong style='color: white;'>" . htmlspecialchars($full_name) . "</strong>";
            echo "<br><span style='color: #a1a8b3;'>@{$user['username']}</span>";
            echo "</div>";
            echo "</div>";
            
            echo "<div style='display: flex; gap: 10px;'>";
            echo "<a href='chat.php?user_id={$user['id']}' style='background: #3b82f6; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; font-size: 14px; flex: 1; text-align: center;'>💻 دردشة أساسية</a>";
            echo "<a href='chat_simple.php?user_id={$user['id']}' style='background: #10b981; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; font-size: 14px; flex: 1; text-align: center;'>💬 دردشة مبسطة</a>";
            echo "</div>";
            echo "</div>";
        }
        echo "</div>";
    }
    
    echo "<h2>🔧 خطوات الاختبار:</h2>";
    echo "<div style='background: rgba(59, 130, 246, 0.1); padding: 20px; border-radius: 10px; margin: 20px 0; border: 1px solid rgba(59, 130, 246, 0.3);'>";
    echo "<ol style='color: white; padding-right: 20px;'>";
    echo "<li>انقر على أحد المستخدمين أعلاه لفتح الدردشة</li>";
    echo "<li>تأكد من فتح منطقة الرسائل بشكل صحيح</li>";
    echo "<li>جرب إرسال رسالة نصية</li>";
    echo "<li>جرب إرسال صورة</li>";
    echo "<li>تأكد من عرض الصور بشكل صحيح</li>";
    echo "<li>افتح وحدة تحكم المطور (F12) للاطلاع على رسائل التصحيح</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<div style='background: #1e40af; color: white; padding: 20px; border-radius: 10px; margin: 20px 0; text-align: center;'>";
    echo "<h3>🎯 النتيجة المتوقعة</h3>";
    echo "<p>✅ عدم ظهور خطأ 404 في الصور</p>";
    echo "<p>✅ فتح منطقة الدردشة عند النقر على المستخدم</p>";
    echo "<p>✅ إمكانية إرسال واستقبال الرسائل</p>";
    echo "<p>✅ عرض صحيح للأسماء العربية</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #dc2626; color: white; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h3>❌ خطأ في الاختبار</h3>";
    echo "<p>الخطأ: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>

<style>
body { 
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
    margin: 20px; 
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 40px;
    min-height: 100vh;
}
h1, h2, h3, h4 { color: white; margin: 20px 0 10px 0; }
p { margin: 10px 0; line-height: 1.6; }
a { color: white; transition: all 0.3s ease; }
a:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.3); }
ul, ol { margin: 10px 0; padding-right: 20px; }
li { margin: 8px 0; line-height: 1.5; }
</style>

<script>
console.log('🧪 صفحة اختبار الدردشة جاهزة');
console.log('📝 تأكد من فتح وحدة التحكم لمتابعة رسائل التصحيح');

window.addEventListener('beforeunload', () => {
    console.log('📊 تقرير الاختبار:');
    console.log('- عدد الأخطاء في وحدة التحكم:', document.querySelectorAll('[data-error]').length);
    console.log('- حالة الصفحة: مكتملة');
});
</script> 