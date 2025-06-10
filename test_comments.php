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
echo "<html><head><meta charset='UTF-8'><title>اختبار التعليقات</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f0f2f5;} .container{max-width:800px;margin:0 auto;background:white;padding:20px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);} .success{color:#28a745;} .error{color:#dc3545;} .comment{border:1px solid #ddd;padding:10px;margin:10px 0;border-radius:5px;} .btn{padding:10px 20px;background:#007bff;color:white;border:none;border-radius:5px;cursor:pointer;margin:5px;} .btn:hover{background:#0056b3;}</style>";
echo "</head><body>";

echo "<div class='container'>";
echo "<h1>🔍 اختبار التعليقات</h1>";

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>📊 معلومات المستخدم الحالي:</h2>";
    echo "<p><strong>ID:</strong> {$_SESSION['user_id']}</p>";
    echo "<p><strong>Username:</strong> {$_SESSION['username']}</p>";
    
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE user_id = 11 ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$post) {
        echo "<p class='error'>❌ لا توجد منشورات للمستخدم 11</p>";
        exit;
    }
    
    $post_id = $post['id'];
    echo "<h2>📝 المنشور المختار للاختبار:</h2>";
    echo "<div class='comment'>";
    echo "<p><strong>ID:</strong> {$post['id']}</p>";
    echo "<p><strong>Content:</strong> " . htmlspecialchars($post['content']) . "</p>";
    echo "<p><strong>Created:</strong> {$post['created_at']}</p>";
    echo "</div>";
    
    echo "<h2>💬 التعليقات الموجودة:</h2>";
    $stmt = $pdo->prepare("
        SELECT c.*, u.username, u.first_name, u.last_name, u.avatar_url
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.post_id = ?
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$post_id]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($comments)) {
        echo "<p class='error'>❌ لا توجد تعليقات لهذا المنشور</p>";
        
        echo "<p>💡 إضافة تعليق تجريبي...</p>";
        $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$post_id, 11, '🎉 تعليق تجريبي للاختبار!']);
        echo "<p class='success'>✅ تم إضافة تعليق تجريبي</p>";
        
        $stmt->execute([$post_id]);
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    foreach ($comments as $comment) {
        echo "<div class='comment'>";
        echo "<p><strong>ID:</strong> {$comment['id']}</p>";
        echo "<p><strong>User:</strong> {$comment['username']} ({$comment['first_name']} {$comment['last_name']})</p>";
        echo "<p><strong>Content:</strong> " . htmlspecialchars($comment['content']) . "</p>";
        echo "<p><strong>Created:</strong> {$comment['created_at']}</p>";
        echo "</div>";
    }
    
    echo "<h2>🔗 اختبار API للتعليقات:</h2>";
    echo "<button onclick='testGetComments($post_id)' class='btn'>جلب التعليقات عبر API</button>";
    echo "<button onclick='testAddComment($post_id)' class='btn'>إضافة تعليق عبر API</button>";
    echo "<div id='api-result' style='margin-top:20px;'></div>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ خطأ: " . $e->getMessage() . "</p>";
}

echo "</div>";

echo "<script>
async function testGetComments(postId) {
    const resultDiv = document.getElementById('api-result');
    resultDiv.innerHTML = '<p>🔄 جلب التعليقات...</p>';
    
    try {
        const response = await fetch(`api/social.php?action=get_comments&post_id=\${postId}`);
        const result = await response.json();
        
        if (result.success) {
            resultDiv.innerHTML = '<p style=\"color:green;\">✅ تم جلب التعليقات بنجاح!</p><pre>' + JSON.stringify(result, null, 2) + '</pre>';
        } else {
            resultDiv.innerHTML = '<p style=\"color:red;\">❌ فشل جلب التعليقات: ' + result.message + '</p>';
        }
    } catch (error) {
        resultDiv.innerHTML = '<p style=\"color:red;\">❌ خطأ: ' + error.message + '</p>';
    }
}

async function testAddComment(postId) {
    const resultDiv = document.getElementById('api-result');
    resultDiv.innerHTML = '<p>🔄 إضافة تعليق...</p>';
    
    try {
        const response = await fetch('api/social.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'add_comment',
                post_id: postId,
                content: '🚀 تعليق جديد من الاختبار - ' + new Date().toLocaleString()
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            resultDiv.innerHTML = '<p style=\"color:green;\">✅ تم إضافة التعليق بنجاح!</p><pre>' + JSON.stringify(result, null, 2) + '</pre>';
        } else {
            resultDiv.innerHTML = '<p style=\"color:red;\">❌ فشل إضافة التعليق: ' + result.message + '</p>';
        }
    } catch (error) {
        resultDiv.innerHTML = '<p style=\"color:red;\">❌ خطأ: ' + error.message + '</p>';
    }
}
</script>";

echo "</body></html>";
?> 