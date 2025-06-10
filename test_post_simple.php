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
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['avatar_url'] = $user['avatar_url'];
        }
    } catch (Exception $e) {
        echo "خطأ في تسجيل الدخول: " . $e->getMessage();
        exit;
    }
}

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'><title>اختبار إنشاء المنشورات</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f0f2f5;} .container{max-width:600px;margin:0 auto;background:white;padding:20px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);} .btn{padding:12px 24px;background:#1877f2;color:white;border:none;border-radius:6px;cursor:pointer;font-size:16px;} .btn:hover{background:#166fe5;} .form-group{margin:15px 0;} .form-control{width:100%;padding:12px;border:1px solid #ddd;border-radius:6px;font-size:14px;} .result{margin:15px 0;padding:12px;border-radius:6px;} .success{background:#d4edda;color:#155724;border:1px solid #c3e6cb;} .error{background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;}</style>";
echo "</head><body>";

echo "<div class='container'>";
echo "<h1>🧪 اختبار إنشاء المنشورات</h1>";
echo "<p>المستخدم: {$_SESSION['username']} (ID: {$_SESSION['user_id']})</p>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_post'])) {
    $content = trim($_POST['content']);
    
    if (!empty($content)) {
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, created_at) VALUES (?, ?, NOW())");
            $result = $stmt->execute([$_SESSION['user_id'], $content]);
            
            if ($result) {
                $post_id = $pdo->lastInsertId();
                echo "<div class='result success'>✅ تم إنشاء المنشور بنجاح! (ID: {$post_id})</div>";
            } else {
                echo "<div class='result error'>❌ فشل في إنشاء المنشور</div>";
            }
        } catch (Exception $e) {
            echo "<div class='result error'>❌ خطأ: " . $e->getMessage() . "</div>";
        }
    } else {
        echo "<div class='result error'>❌ يرجى إدخال محتوى المنشور</div>";
    }
}

echo "<form method='POST'>";
echo "<div class='form-group'>";
echo "<label for='content'>محتوى المنشور:</label>";
echo "<textarea name='content' id='content' class='form-control' rows='4' placeholder='ماذا تفكر؟'></textarea>";
echo "</div>";
echo "<button type='submit' name='create_post' class='btn'>نشر المنشور</button>";
echo "</form>";

echo "<h2>📝 آخر المنشورات:</h2>";
try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
    $stmt = $pdo->prepare("SELECT p.*, u.username FROM posts p JOIN users u ON p.user_id = u.id WHERE p.user_id = ? ORDER BY p.created_at DESC LIMIT 5");
    $stmt->execute([$_SESSION['user_id']]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($posts) {
        foreach ($posts as $post) {
            echo "<div style='border:1px solid #ddd;padding:15px;margin:10px 0;border-radius:8px;background:#f9f9f9;'>";
            echo "<p><strong>@{$post['username']}</strong> - " . date('Y-m-d H:i', strtotime($post['created_at'])) . "</p>";
            echo "<p>" . nl2br(htmlspecialchars($post['content'])) . "</p>";
            echo "</div>";
        }
    } else {
        echo "<p>لا توجد منشورات بعد.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>خطأ في جلب المنشورات: " . $e->getMessage() . "</p>";
}

echo "<h2>🔗 روابط مفيدة:</h2>";
echo "<p><a href='u.php' target='_blank' style='color:#1877f2;'>الصفحة الشخصية</a></p>";
echo "<p><a href='home.php' target='_blank' style='color:#1877f2;'>الصفحة الرئيسية</a></p>";

echo "</div></body></html>";
?> 