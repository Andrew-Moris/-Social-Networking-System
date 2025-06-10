<?php


session_start();
require_once 'config.php';

echo "<h1>🧪 اختبار سريع للمحادثة</h1>";

if (!isset($_SESSION['user_id'])) {
    echo "<p style='color: red;'>❌ يجب تسجيل الدخول أولاً</p>";
    echo "<p><a href='login.php'>تسجيل الدخول</a></p>";
    exit;
}

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $user_id = $_SESSION['user_id'];
    
    $users_stmt = $pdo->prepare("SELECT id, username, first_name, last_name FROM users WHERE id != ? LIMIT 5");
    $users_stmt->execute([$user_id]);
    $users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>✅ المستخدم الحالي: " . $_SESSION['username'] . " (ID: $user_id)</h2>";
    
    if (count($users) > 0) {
        echo "<h2>👥 المستخدمين المتاحين للمحادثة:</h2>";
        echo "<div style='display: flex; gap: 20px; flex-wrap: wrap;'>";
        
        foreach ($users as $user) {
            $fullName = trim($user['first_name'] . ' ' . $user['last_name']) ?: $user['username'];
            echo "<div style='border: 2px solid #007bff; border-radius: 12px; padding: 20px; text-align: center; background: #f8f9fa; min-width: 200px;'>";
            echo "<h3>{$fullName}</h3>";
            echo "<p>@{$user['username']}</p>";
            echo "<p><strong>ID:</strong> {$user['id']}</p>";
            
            echo "<div style='margin-top: 15px;'>";
            echo "<a href='chat_fixed_final.php?user={$user['id']}' style='display: inline-block; background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 8px; margin: 5px;'>فتح المحادثة الجديدة</a><br>";
            echo "<a href='chat.php?user={$user['id']}' style='display: inline-block; background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 8px; margin: 5px;'>فتح المحادثة الأصلية</a><br>";
            echo "<a href='debug_chat_detailed.php' style='display: inline-block; background: #ffc107; color: black; padding: 10px 20px; text-decoration: none; border-radius: 8px; margin: 5px;'>فحص مفصل</a>";
            echo "</div>";
            echo "</div>";
        }
        echo "</div>";
    } else {
        echo "<p style='color: orange;'>⚠️ لا يوجد مستخدمين آخرين في النظام</p>";
        echo "<p>يمكنك إنشاء مستخدمين تجريبيين باستخدام <a href='create_sample_posts.php'>هذا الرابط</a></p>";
    }
    
    echo "<hr>";
    echo "<h2>🔧 اختبار API المحادثة:</h2>";
    
    if (file_exists('api/chat.php')) {
        echo "<p>✅ ملف API موجود</p>";
        
        if (!empty($users)) {
            $test_user = $users[0];
            echo "<div style='background: #e9ecef; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
            echo "<h3>اختبار مع المستخدم: {$test_user['username']}</h3>";
            echo "<p><a href='api/chat.php?action=get_messages&user_id={$test_user['id']}' target='_blank'>عرض الرسائل</a></p>";
            echo "<p><a href='api/chat.php?action=get_conversations' target='_blank'>عرض جميع المحادثات</a></p>";
            echo "</div>";
        }
    } else {
        echo "<p style='color: red;'>❌ ملف API غير موجود</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ خطأ: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>📋 الخطوات التالية:</h2>";
echo "<ol>";
echo "<li><strong>جرب المحادثة الجديدة:</strong> اضغط على 'فتح المحادثة الجديدة' لأي مستخدم</li>";
echo "<li><strong>قارن مع الأصلية:</strong> جرب 'فتح المحادثة الأصلية' لترى الفرق</li>";
echo "<li><strong>فحص مفصل:</strong> استخدم 'فحص مفصل' إذا واجهت مشاكل</li>";
echo "<li><strong>اختبر الإرسال:</strong> جرب إرسال رسائل في المحادثة الجديدة</li>";
echo "</ol>";

echo "<p><a href='home.php'>← العودة للصفحة الرئيسية</a></p>";
?>

<style>
body { 
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
    margin: 20px; 
    background: #f8f9fa; 
    direction: rtl;
}
h1, h2, h3 { color: #2c3e50; }
a { text-decoration: none; }
a:hover { opacity: 0.8; }
</style> 