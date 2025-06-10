<?php


session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 11; 
    $_SESSION['username'] = 'yoyo1';
}

echo "<!DOCTYPE html>
<html lang='ar' dir='rtl'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>🧪 اختبار حذف التعليقات</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; direction: rtl; }
        .test-section { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .info { color: #17a2b8; font-weight: bold; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
        button { padding: 10px 20px; margin: 5px; border: none; border-radius: 5px; cursor: pointer; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-primary { background: #007bff; color: white; }
        .comment-item { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 8px; background: #f9f9f9; }
    </style>
</head>
<body>";

echo "<h1>🧪 اختبار حذف التعليقات</h1>";

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $user_id = $_SESSION['user_id'];
    
    echo "<div class='test-section'>";
    echo "<h2>📋 معلومات الجلسة</h2>";
    echo "<p><strong>User ID:</strong> $user_id</p>";
    echo "<p><strong>Username:</strong> " . $_SESSION['username'] . "</p>";
    echo "</div>";
    
    echo "<div class='test-section'>";
    echo "<h2>💬 التعليقات الخاصة بك</h2>";
    
    $stmt = $pdo->prepare("
        SELECT c.*, p.id as post_id, p.content as post_content 
        FROM comments c 
        JOIN posts p ON c.post_id = p.id 
        WHERE c.user_id = ? 
        ORDER BY c.created_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$user_id]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($comments)) {
        echo "<p class='warning'>⚠️ لا توجد تعليقات لهذا المستخدم</p>";
        
        echo "<h3>🛠️ إنشاء تعليق تجريبي:</h3>";
        
        $post_stmt = $pdo->prepare("SELECT id FROM posts LIMIT 1");
        $post_stmt->execute();
        $post = $post_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($post) {
            $test_comment = "تعليق تجريبي للاختبار - " . date('Y-m-d H:i:s');
            $insert_stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())");
            $insert_stmt->execute([$post['id'], $user_id, $test_comment]);
            
            echo "<p class='success'>✅ تم إنشاء تعليق تجريبي</p>";
            
            $stmt->execute([$user_id]);
            $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            echo "<p class='error'>❌ لا توجد منشورات للتعليق عليها</p>";
        }
    }
    
    foreach ($comments as $comment) {
        echo "<div class='comment-item'>";
        echo "<p><strong>التعليق:</strong> " . htmlspecialchars($comment['content']) . "</p>";
        echo "<p><strong>على المنشور:</strong> " . htmlspecialchars(substr($comment['post_content'], 0, 50)) . "...</p>";
        echo "<p><strong>تاريخ الإنشاء:</strong> " . $comment['created_at'] . "</p>";
        echo "<button class='btn-danger' onclick='deleteComment(" . $comment['id'] . ")'>🗑️ حذف التعليق</button>";
        echo "<div id='result-" . $comment['id'] . "'></div>";
        echo "</div>";
    }
    echo "</div>";
    
    echo "<div class='test-section'>";
    echo "<h2>🔍 فحص الجداول</h2>";
    
    $tables_to_check = ['comments', 'comment_likes', 'comment_dislikes'];
    foreach ($tables_to_check as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p class='success'>✅ الجدول $table موجود</p>";
            
            $count_stmt = $pdo->query("SELECT COUNT(*) FROM $table");
            $count = $count_stmt->fetchColumn();
            echo "<p class='info'>📊 عدد السجلات في $table: $count</p>";
        } else {
            echo "<p class='warning'>⚠️ الجدول $table غير موجود</p>";
        }
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='test-section'>";
    echo "<h2 class='error'>❌ خطأ</h2>";
    echo "<p class='error'>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "
<script>
console.log('🧪 اختبار حذف التعليقات جاهز');

async function deleteComment(commentId) {
    const resultDiv = document.getElementById('result-' + commentId);
    resultDiv.innerHTML = '<p style=\"color: #007bff;\">🔄 جاري الحذف...</p>';
    
    console.log('🗑️ محاولة حذف التعليق:', commentId);
    
    try {
        const response = await fetch('api/social.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'delete_comment',
                comment_id: parseInt(commentId)
            })
        });
        
        console.log('📡 استجابة الخادم:', response.status);
        
        const result = await response.json();
        console.log('📊 نتيجة الحذف:', result);
        
        if (result.success) {
            resultDiv.innerHTML = '<p style=\"color: #28a745;\">✅ تم حذف التعليق بنجاح!</p>';
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            resultDiv.innerHTML = '<p style=\"color: #dc3545;\">❌ فشل الحذف: ' + result.message + '</p>';
        }
    } catch (error) {
        console.error('❌ خطأ في الحذف:', error);
        resultDiv.innerHTML = '<p style=\"color: #dc3545;\">❌ خطأ في الاتصال: ' + error.message + '</p>';
    }
}

// اختبار API
async function testAPI() {
    console.log('🔍 اختبار API...');
    
    try {
        const response = await fetch('api/social.php', {
            method: 'HEAD'
        });
        
        console.log('📡 حالة API:', response.status);
        
        if (response.ok) {
            console.log('✅ API يعمل بشكل صحيح');
        } else {
            console.log('⚠️ مشكلة في API');
        }
    } catch (error) {
        console.error('❌ خطأ في API:', error);
    }
}

// تشغيل اختبار API عند تحميل الصفحة
testAPI();
</script>

<div class='test-section'>
    <h2>🔗 روابط مفيدة</h2>
    <a href='u.php' target='_blank'>🏠 الذهاب إلى u.php</a> |
    <a href='debug_home_complete.php' target='_blank'>🔍 تشخيص شامل</a> |
    <a href='home.php' target='_blank'>🏡 الصفحة الرئيسية</a>
</div>

</body>
</html>";
?> 