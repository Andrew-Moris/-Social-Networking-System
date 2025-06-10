<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = 11");
        $stmt->execute();
        $user = $stmt->fetch();
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
        }
    } catch (Exception $e) {
        echo "خطأ في تسجيل الدخول: " . $e->getMessage();
        exit;
    }
}

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'><title>اختبار سريع للتعليقات</title></head><body>";
echo "<h1>🧪 اختبار سريع للتعليقات</h1>";

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
    
    $stmt = $pdo->prepare("SELECT id FROM posts WHERE user_id = 11 ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $post = $stmt->fetch();
    
    if ($post) {
        $post_id = $post['id'];
        
        $comment_content = "🎉 تعليق تجريبي جديد - " . date('Y-m-d H:i:s');
        $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$post_id, 11, $comment_content]);
        
        echo "<p>✅ تم إضافة تعليق جديد للمنشور $post_id</p>";
        echo "<p><strong>المحتوى:</strong> $comment_content</p>";
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE post_id = ?");
        $stmt->execute([$post_id]);
        $count = $stmt->fetchColumn();
        
        echo "<p>📊 إجمالي التعليقات للمنشور: $count</p>";
        
        echo "<h2>🔗 اختبار API:</h2>";
        echo "<button onclick='testAPI($post_id)'>اختبار جلب التعليقات</button>";
        echo "<div id='result'></div>";
        
        echo "<script>
        async function testAPI(postId) {
            const resultDiv = document.getElementById('result');
            resultDiv.innerHTML = '<p>🔄 جاري الاختبار...</p>';
            
            try {
                const response = await fetch(`api/social.php?action=get_comments&post_id=\${postId}`);
                const result = await response.json();
                
                if (result.success) {
                    resultDiv.innerHTML = '<p style=\"color:green;\">✅ API يعمل! عدد التعليقات: ' + result.data.comments.length + '</p>';
                } else {
                    resultDiv.innerHTML = '<p style=\"color:red;\">❌ خطأ: ' + result.message + '</p>';
                }
            } catch (error) {
                resultDiv.innerHTML = '<p style=\"color:red;\">❌ خطأ في الاتصال: ' + error.message + '</p>';
            }
        }
        </script>";
        
    } else {
        echo "<p>❌ لا توجد منشورات للاختبار</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ خطأ: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='u.php' target='_blank'>🔗 افتح صفحة u.php للاختبار</a></p>";
echo "<p><a href='debug_comments_display.php' target='_blank'>🔗 افتح صفحة التشخيص المتقدمة</a></p>";

echo "</body></html>";
?> 