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
echo "<html><head><meta charset='UTF-8'><title>تشخيص خطأ التعليقات</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f0f2f5;} .container{max-width:1000px;margin:0 auto;background:white;padding:20px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);} .success{color:#28a745;} .error{color:#dc3545;} .info{color:#007bff;} pre{background:#f8f9fa;padding:15px;border-radius:5px;overflow-x:auto;}</style>";
echo "</head><body>";

echo "<div class='container'>";
echo "<h1>🔍 تشخيص خطأ التعليقات</h1>";

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>📊 معلومات المستخدم الحالي:</h2>";
    echo "<p><strong>ID:</strong> {$_SESSION['user_id']}</p>";
    echo "<p><strong>Username:</strong> {$_SESSION['username']}</p>";
    
    echo "<h2>🔍 فحص جدول التعليقات:</h2>";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'comments'");
    if ($stmt->rowCount() > 0) {
        echo "<p class='success'>✅ جدول comments موجود</p>";
        
        echo "<h3>هيكل جدول comments:</h3>";
        $stmt = $pdo->query("DESCRIBE comments");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse:collapse;width:100%;'>";
        echo "<tr><th>العمود</th><th>النوع</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>{$col['Field']}</td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "<td>{$col['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM comments");
        $count = $stmt->fetchColumn();
        echo "<p class='info'>📊 إجمالي التعليقات: $count</p>";
        
        $stmt = $pdo->prepare("SELECT * FROM posts WHERE user_id = 11 ORDER BY id DESC LIMIT 1");
        $stmt->execute();
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($post) {
            $post_id = $post['id'];
            echo "<h3>📝 المنشور المختار للاختبار:</h3>";
            echo "<p><strong>ID:</strong> {$post['id']}</p>";
            echo "<p><strong>Content:</strong> " . htmlspecialchars($post['content']) . "</p>";
            
            echo "<h3>🧪 اختبار الاستعلام المباشر:</h3>";
            try {
                $stmt = $pdo->prepare("
                    SELECT c.*, u.username, u.first_name, u.last_name, u.avatar_url
                    FROM comments c
                    JOIN users u ON c.user_id = u.id
                    WHERE c.post_id = ?
                    ORDER BY c.created_at DESC
                ");
                $stmt->execute([$post_id]);
                $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<p class='success'>✅ الاستعلام نجح! عدد التعليقات: " . count($comments) . "</p>";
                
                if (!empty($comments)) {
                    echo "<h4>التعليقات الموجودة:</h4>";
                    foreach ($comments as $comment) {
                        echo "<div style='border:1px solid #ddd;padding:10px;margin:10px 0;border-radius:5px;'>";
                        echo "<p><strong>ID:</strong> {$comment['id']}</p>";
                        echo "<p><strong>User:</strong> {$comment['username']}</p>";
                        echo "<p><strong>Content:</strong> " . htmlspecialchars($comment['content']) . "</p>";
                        echo "<p><strong>Created:</strong> {$comment['created_at']}</p>";
                        echo "</div>";
                    }
                }
                
            } catch (Exception $e) {
                echo "<p class='error'>❌ خطأ في الاستعلام المباشر: " . $e->getMessage() . "</p>";
            }
            
            echo "<h3>🔗 اختبار API مع تفاصيل الخطأ:</h3>";
            echo "<button onclick='testAPIWithDetails($post_id)' style='padding:10px 20px;background:#007bff;color:white;border:none;border-radius:5px;cursor:pointer;'>اختبار API</button>";
            echo "<div id='api-result' style='margin-top:20px;'></div>";
            
        } else {
            echo "<p class='error'>❌ لا توجد منشورات للمستخدم 11</p>";
        }
        
    } else {
        echo "<p class='error'>❌ جدول comments غير موجود!</p>";
        
        echo "<p>💡 إنشاء جدول التعليقات...</p>";
        $create_sql = "CREATE TABLE IF NOT EXISTS comments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            user_id INT NOT NULL,
            content TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_post_id (post_id),
            INDEX idx_user_id (user_id),
            INDEX idx_created_at (created_at),
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($create_sql);
        echo "<p class='success'>✅ تم إنشاء جدول التعليقات</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ خطأ عام: " . $e->getMessage() . "</p>";
}

echo "</div>";

echo "<script>
async function testAPIWithDetails(postId) {
    const resultDiv = document.getElementById('api-result');
    resultDiv.innerHTML = '<p>🔄 اختبار API...</p>';
    
    try {
        const response = await fetch(`api/social.php?action=get_comments&post_id=\${postId}`);
        
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        const responseText = await response.text();
        console.log('Response text:', responseText);
        
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (parseError) {
            resultDiv.innerHTML = '<p style=\"color:red;\">❌ خطأ في تحليل JSON:</p><pre>' + responseText + '</pre>';
            return;
        }
        
        if (result.success) {
            resultDiv.innerHTML = '<p style=\"color:green;\">✅ تم جلب التعليقات بنجاح!</p><pre>' + JSON.stringify(result, null, 2) + '</pre>';
        } else {
            resultDiv.innerHTML = '<p style=\"color:red;\">❌ فشل جلب التعليقات: ' + result.message + '</p><pre>' + JSON.stringify(result, null, 2) + '</pre>';
        }
    } catch (error) {
        resultDiv.innerHTML = '<p style=\"color:red;\">❌ خطأ في الشبكة: ' + error.message + '</p>';
        console.error('Network error:', error);
    }
}
</script>";

echo "</body></html>";
?> 