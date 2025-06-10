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
echo "<html><head><meta charset='UTF-8'><title>فحص المنشورات</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f0f2f5;} .container{max-width:1000px;margin:0 auto;background:white;padding:20px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);} table{width:100%;border-collapse:collapse;margin:20px 0;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#f2f2f2;} .success{color:#28a745;} .error{color:#dc3545;}</style>";
echo "</head><body>";

echo "<div class='container'>";
echo "<h1>🔍 فحص المنشورات والجداول</h1>";

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>📊 معلومات المستخدم الحالي:</h2>";
    echo "<p><strong>ID:</strong> {$_SESSION['user_id']}</p>";
    echo "<p><strong>Username:</strong> {$_SESSION['username']}</p>";
    
    echo "<h2>📝 جدول المنشورات:</h2>";
    
    $stmt = $pdo->query("DESCRIBE posts");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>هيكل جدول posts:</h3>";
    echo "<table>";
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
    
    $stmt = $pdo->query("SELECT * FROM posts ORDER BY id DESC LIMIT 10");
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>آخر 10 منشورات:</h3>";
    if (empty($posts)) {
        echo "<p class='error'>❌ لا توجد منشورات</p>";
    } else {
        echo "<table>";
        echo "<tr><th>ID</th><th>User ID</th><th>Content</th><th>is_private</th><th>Created At</th></tr>";
        foreach ($posts as $post) {
            echo "<tr>";
            echo "<td>{$post['id']}</td>";
            echo "<td>{$post['user_id']}</td>";
            echo "<td>" . htmlspecialchars(substr($post['content'], 0, 50)) . "...</td>";
            echo "<td>" . ($post['is_private'] ?? 'NULL') . "</td>";
            echo "<td>{$post['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h3>منشورات المستخدم 11:</h3>";
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE user_id = 11 ORDER BY id DESC");
    $stmt->execute();
    $user_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($user_posts)) {
        echo "<p class='error'>❌ لا توجد منشورات للمستخدم 11</p>";
        
        echo "<p>💡 إنشاء منشور تجريبي...</p>";
        $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, created_at) VALUES (11, ?, NOW())");
        $stmt->execute(['🎉 منشور تجريبي للاختبار - تم إنشاؤه الآن!']);
        echo "<p class='success'>✅ تم إنشاء منشور تجريبي</p>";
        
        $stmt = $pdo->prepare("SELECT * FROM posts WHERE user_id = 11 ORDER BY id DESC");
        $stmt->execute();
        $user_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Content</th><th>is_private</th><th>Created At</th></tr>";
    foreach ($user_posts as $post) {
        echo "<tr>";
        echo "<td>{$post['id']}</td>";
        echo "<td>" . htmlspecialchars($post['content']) . "</td>";
        echo "<td>" . ($post['is_private'] ?? 'NULL') . "</td>";
        echo "<td>{$post['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    if (!empty($user_posts)) {
        $test_post_id = $user_posts[0]['id'];
        echo "<h3>🧪 اختبار دالة checkPostAccess للمنشور ID: $test_post_id</h3>";
        
        $stmt = $pdo->prepare("
            SELECT p.id, p.user_id, COALESCE(p.is_private, 0) as is_private 
            FROM posts p
            WHERE p.id = ? AND (
                COALESCE(p.is_private, 0) = 0 OR 
                p.user_id = ?
            )
        ");
        $stmt->execute([$test_post_id, 11]);
        $access_result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($access_result) {
            echo "<p class='success'>✅ يمكن الوصول للمنشور</p>";
            echo "<pre>" . json_encode($access_result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
        } else {
            echo "<p class='error'>❌ لا يمكن الوصول للمنشور</p>";
        }
        
        echo "<h3>🔗 اختبار API:</h3>";
        echo "<button onclick='testAPI($test_post_id)' style='padding:10px 20px;background:#007bff;color:white;border:none;border-radius:5px;cursor:pointer;'>اختبار API للمنشور $test_post_id</button>";
        echo "<div id='api-result' style='margin-top:10px;'></div>";
    }
    
    echo "<h2>📋 فحص الجداول الأخرى:</h2>";
    $tables = ['likes', 'comments', 'bookmarks'];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            $count_stmt = $pdo->query("SELECT COUNT(*) FROM $table");
            $count = $count_stmt->fetchColumn();
            echo "<p class='success'>✅ جدول $table موجود ($count صف)</p>";
        } else {
            echo "<p class='error'>❌ جدول $table مفقود</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ خطأ: " . $e->getMessage() . "</p>";
}

echo "</div>";

echo "<script>
async function testAPI(postId) {
    const resultDiv = document.getElementById('api-result');
    resultDiv.innerHTML = '<p>🔄 اختبار API...</p>';
    
    try {
        const response = await fetch('api/social.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'toggle_like',
                post_id: postId
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            resultDiv.innerHTML = '<p style=\"color:green;\">✅ API يعمل بنجاح!</p><pre>' + JSON.stringify(result, null, 2) + '</pre>';
        } else {
            resultDiv.innerHTML = '<p style=\"color:red;\">❌ فشل API: ' + result.message + '</p>';
        }
    } catch (error) {
        resultDiv.innerHTML = '<p style=\"color:red;\">❌ خطأ: ' + error.message + '</p>';
    }
}
</script>";

echo "</body></html>";
?> 