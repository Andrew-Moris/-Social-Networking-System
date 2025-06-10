<?php


session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

echo "<h1>🔧 إصلاح روابط المحادثة</h1>";

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $homeContent = file_get_contents('home.php');
    
    if (strpos($homeContent, 'chat.php') !== false) {
        echo "<h2>📝 تحديث الصفحة الرئيسية...</h2>";
        
        $homeContent = str_replace(
            '<a href="chat.php" class="text-gray-300 hover:text-white transition-colors">',
            '<a href="chat_fixed_final.php" class="text-gray-300 hover:text-white transition-colors">',
            $homeContent
        );
        
        if (file_put_contents('home.php', $homeContent)) {
            echo "<p>✅ تم تحديث رابط المحادثة في الصفحة الرئيسية</p>";
        } else {
            echo "<p style='color: red;'>❌ فشل في تحديث الصفحة الرئيسية</p>";
        }
    } else {
        echo "<p>⚠️ الصفحة الرئيسية محدثة بالفعل</p>";
    }
    
    $redirectContent = '<?php
/**
 * Chat Redirect - إعادة توجيه للمحادثة المصلحة
 */
header("Location: chat_fixed_final.php" . (isset($_GET["user"]) ? "?user=" . $_GET["user"] : ""));
exit;
?>';
    
    if (file_exists('chat.php') && !file_exists('chat_original_backup.php')) {
        copy('chat.php', 'chat_original_backup.php');
        echo "<p>✅ تم إنشاء نسخة احتياطية من chat.php</p>";
    }
    
    if (file_put_contents('chat_redirect.php', $redirectContent)) {
        echo "<p>✅ تم إنشاء ملف إعادة التوجيه</p>";
        

    }
    
    echo "<hr>";
    echo "<h2>🎯 الحل السريع:</h2>";
    echo "<p>استخدم الروابط التالية:</p>";
    echo "<ul>";
    echo "<li><a href='chat_fixed_final.php' style='color: #007bff; font-weight: bold;'>المحادثة المصلحة (مباشرة)</a></li>";
    echo "<li><a href='test_chat.php' style='color: #28a745; font-weight: bold;'>صفحة الاختبار</a></li>";
    echo "</ul>";
    
    echo "<hr>";
    echo "<h2>📌 إضافة اختصار في سطح المكتب:</h2>";
    echo "<p>يمكنك حفظ هذا الرابط في المفضلة:</p>";
    echo "<code style='background: #f8f9fa; padding: 10px; display: block; margin: 10px 0;'>";
    echo "http://localhost:8001/chat_fixed_final.php";
    echo "</code>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ خطأ: " . $e->getMessage() . "</p>";
}

echo "<p><a href='home.php'>← العودة للصفحة الرئيسية</a></p>";
?>

<style>
body { 
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
    margin: 20px; 
    background: #f8f9fa; 
    direction: rtl;
}
h1, h2 { color: #2c3e50; }
a { text-decoration: none; }
a:hover { opacity: 0.8; }
code {
    font-family: 'Courier New', monospace;
    direction: ltr;
}
</style> 