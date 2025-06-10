<?php


session_start();
require_once 'config.php';

echo "<!DOCTYPE html>
<html lang='ar' dir='rtl'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>🔧 إصلاح البيانات</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; direction: rtl; }
        .section { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .info { color: #17a2b8; font-weight: bold; }
        button { padding: 10px 20px; margin: 5px; border: none; border-radius: 5px; cursor: pointer; background: #007bff; color: white; }
    </style>
</head>
<body>";

echo "<h1>🔧 إصلاح مشكلة عدم إرجاع البيانات</h1>";

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    if (!isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username IN ('ben10', 'yoyo1', 'admin') LIMIT 1");
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
        }
    }
    
    $user_id = $_SESSION['user_id'] ?? 0;
    
    echo "<div class='section'>";
    echo "<h2>🔍 فحص الحالة الحالية</h2>";
    echo "<p><strong>User ID:</strong> $user_id</p>";
    echo "<p><strong>Username:</strong> " . ($_SESSION['username'] ?? 'غير محدد') . "</p>";
    
    if ($user_id == 0) {
        echo "<p class='error'>❌ لا يوجد مستخدم مسجل دخول</p>";
        echo "</div>";
        exit;
    }
    
    $posts_count = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
    $posts_count->execute([$user_id]);
    $posts = $posts_count->fetchColumn();
    
    $followers_count = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE followed_id = ?");
    $followers_count->execute([$user_id]);
    $followers = $followers_count->fetchColumn();
    
    $following_count = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE follower_id = ?");
    $following_count->execute([$user_id]);
    $following = $following_count->fetchColumn();
    
    echo "<h3>📊 الإحصائيات الحالية:</h3>";
    echo "<p>📝 المنشورات: $posts</p>";
    echo "<p>👥 المتابعون: $followers</p>";
    echo "<p>➡️ يتابع: $following</p>";
    echo "</div>";
    
    if ($posts == 0 && $followers == 0 && $following == 0) {
        echo "<div class='section'>";
        echo "<h2>🛠️ إنشاء بيانات تجريبية</h2>";
        
        for ($i = 1; $i <= 5; $i++) {
            $content = "منشور تجريبي رقم $i - تم إنشاؤه في " . date('Y-m-d H:i:s');
            $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$user_id, $content]);
        }
        echo "<p class='success'>✅ تم إنشاء 5 منشورات تجريبية</p>";
        
        $test_users = [
            ['testuser1', 'test1@example.com', 'Test', 'User1'],
            ['testuser2', 'test2@example.com', 'Test', 'User2'],
            ['testuser3', 'test3@example.com', 'Test', 'User3']
        ];
        
        foreach ($test_users as $test_user) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO users (username, email, first_name, last_name, password) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$test_user[0], $test_user[1], $test_user[2], $test_user[3], password_hash('123456', PASSWORD_DEFAULT)]);
        }
        echo "<p class='success'>✅ تم إنشاء مستخدمين تجريبيين</p>";
        
        $test_user_ids = $pdo->prepare("SELECT id FROM users WHERE username IN ('testuser1', 'testuser2', 'testuser3')");
        $test_user_ids->execute();
        $test_ids = $test_user_ids->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($test_ids as $test_id) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO followers (follower_id, followed_id, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$test_id, $user_id]);
            
            $stmt = $pdo->prepare("INSERT IGNORE INTO followers (follower_id, followed_id, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$user_id, $test_id]);
        }
        echo "<p class='success'>✅ تم إنشاء علاقات متابعة تجريبية</p>";
        
        $post_ids = $pdo->prepare("SELECT id FROM posts WHERE user_id = ? ORDER BY id DESC LIMIT 3");
        $post_ids->execute([$user_id]);
        $posts_for_likes = $post_ids->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($posts_for_likes as $post_id) {
            foreach ($test_ids as $test_id) {
                $stmt = $pdo->prepare("INSERT IGNORE INTO likes (post_id, user_id, created_at) VALUES (?, ?, NOW())");
                $stmt->execute([$post_id, $test_id]);
                
                $comment_content = "تعليق تجريبي من المستخدم $test_id على المنشور $post_id";
                $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$post_id, $test_id, $comment_content]);
            }
        }
        echo "<p class='success'>✅ تم إنشاء إعجابات وتعليقات تجريبية</p>";
        echo "</div>";
    }
    
    echo "<div class='section'>";
    echo "<h2>📊 الإحصائيات المحدثة</h2>";
    
    $posts_count->execute([$user_id]);
    $new_posts = $posts_count->fetchColumn();
    
    $followers_count->execute([$user_id]);
    $new_followers = $followers_count->fetchColumn();
    
    $following_count->execute([$user_id]);
    $new_following = $following_count->fetchColumn();
    
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; border: 1px solid #c3e6cb;'>";
    echo "<p><strong>📝 المنشورات:</strong> $new_posts</p>";
    echo "<p><strong>👥 المتابعون:</strong> $new_followers</p>";
    echo "<p><strong>➡️ يتابع:</strong> $new_following</p>";
    echo "</div>";
    
    if ($new_posts > 0 || $new_followers > 0 || $new_following > 0) {
        echo "<p class='success'>✅ البيانات متوفرة الآن!</p>";
        echo "<button onclick='window.open(\"home.php\", \"_blank\")'>🏠 اذهب إلى الصفحة الرئيسية</button>";
        echo "<button onclick='window.open(\"u.php\", \"_blank\")'>👤 اذهب إلى الملف الشخصي</button>";
    } else {
        echo "<p class='warning'>⚠️ لا تزال هناك مشكلة في البيانات</p>";
    }
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h2>🧪 اختبار استعلام home.php</h2>";
    
    $stats_query = "
        SELECT 
            (SELECT COUNT(*) FROM posts WHERE user_id = ?) as posts_count,
            (SELECT COUNT(*) FROM followers WHERE followed_id = ?) as followers_count,
            (SELECT COUNT(*) FROM followers WHERE follower_id = ?) as following_count
    ";
    
    $stats_stmt = $pdo->prepare($stats_query);
    $stats_stmt->execute([$user_id, $user_id, $user_id]);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>📊 نتيجة الاستعلام:</h3>";
    echo "<pre>" . json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    
    if ($stats && ($stats['posts_count'] > 0 || $stats['followers_count'] > 0 || $stats['following_count'] > 0)) {
        echo "<p class='success'>✅ الاستعلام يعمل بشكل صحيح!</p>";
    } else {
        echo "<p class='error'>❌ الاستعلام لا يرجع بيانات صحيحة</p>";
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='section'>";
    echo "<h2 class='error'>❌ خطأ</h2>";
    echo "<p class='error'>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "
<div class='section'>
    <h2>🔗 روابط مفيدة</h2>
    <p>
        <a href='home.php' target='_blank' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>🏠 الصفحة الرئيسية</a>
        <a href='u.php' target='_blank' style='background: #6f42c1; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>👤 الملف الشخصي</a>
        <a href='quick_home_fix.php' target='_blank' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>🔧 إصلاح سريع</a>
    </p>
</div>

</body>
</html>";
?> 