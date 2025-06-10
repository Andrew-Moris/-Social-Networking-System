<?php
session_start();
require_once 'config.php';

echo "<h1>🔍 اختبار إحصائيات الصفحة الرئيسية</h1>";

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $_SESSION['user_id'] = 5;
    $_SESSION['username'] = 'ben10';
    $user_id = $_SESSION['user_id'];
    
    echo "<h2>📊 معلومات المستخدم:</h2>";
    echo "<p><strong>User ID:</strong> $user_id</p>";
    echo "<p><strong>Username:</strong> " . $_SESSION['username'] . "</p>";
    
    $user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $user_stmt->execute([$user_id]);
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "<p style='color: green;'>✅ المستخدم موجود في قاعدة البيانات</p>";
        echo "<p><strong>الاسم:</strong> " . $user['first_name'] . " " . $user['last_name'] . "</p>";
    } else {
        echo "<p style='color: red;'>❌ المستخدم غير موجود في قاعدة البيانات!</p>";
        exit;
    }
    
    echo "<h2>📈 اختبار الإحصائيات:</h2>";
    
    $posts_stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
    $posts_stmt->execute([$user_id]);
    $posts_count = $posts_stmt->fetchColumn();
    echo "<p><strong>عدد المنشورات:</strong> $posts_count</p>";
    
    $followers_stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE followed_id = ?");
    $followers_stmt->execute([$user_id]);
    $followers_count = $followers_stmt->fetchColumn();
    echo "<p><strong>عدد المتابعين:</strong> $followers_count</p>";
    
    $following_stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE follower_id = ?");
    $following_stmt->execute([$user_id]);
    $following_count = $following_stmt->fetchColumn();
    echo "<p><strong>عدد المتابَعين:</strong> $following_count</p>";
    
    echo "<h2>🔬 اختبار الاستعلام المدمج:</h2>";
    $stats_stmt = $pdo->prepare("
        SELECT 
            (SELECT COUNT(*) FROM posts WHERE user_id = ?) as posts_count,
            (SELECT COUNT(*) FROM followers WHERE followed_id = ?) as followers_count,
            (SELECT COUNT(*) FROM followers WHERE follower_id = ?) as following_count
    ");
    $stats_stmt->execute([$user_id, $user_id, $user_id]);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<div style='background: #f0f0f0; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>نتيجة الاستعلام المدمج:</strong><br>";
    echo "<pre>" . json_encode($stats, JSON_PRETTY_PRINT) . "</pre>";
    echo "</div>";
    
    if ($posts_count == 0) {
        echo "<h2>🛠️ إنشاء بيانات تجريبية:</h2>";
        
        for ($i = 1; $i <= 3; $i++) {
            $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$user_id, "منشور تجريبي رقم $i من المستخدم ben10"]);
        }
        echo "<p style='color: green;'>✅ تم إنشاء 3 منشورات تجريبية</p>";
        
        $pdo->exec("INSERT IGNORE INTO users (id, username, email, first_name, last_name, password) VALUES 
                    (10, 'follower1', 'follower1@test.com', 'Follower', 'One', 'dummy'),
                    (11, 'follower2', 'follower2@test.com', 'Follower', 'Two', 'dummy')");
        
        $pdo->exec("INSERT IGNORE INTO followers (follower_id, followed_id, created_at) VALUES 
                    (10, $user_id, NOW()),
                    (11, $user_id, NOW())");
        echo "<p style='color: green;'>✅ تم إنشاء 2 متابعين تجريبيين</p>";
        
        $pdo->exec("INSERT IGNORE INTO followers (follower_id, followed_id, created_at) VALUES 
                    ($user_id, 10, NOW()),
                    ($user_id, 11, NOW())");
        echo "<p style='color: green;'>✅ تم إنشاء 2 متابَعين تجريبيين</p>";
        
        echo "<h2>📊 الإحصائيات بعد إنشاء البيانات:</h2>";
        $stats_stmt->execute([$user_id, $user_id, $user_id]);
        $new_stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<div style='background: #e8f5e8; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong>الإحصائيات الجديدة:</strong><br>";
        echo "<pre>" . json_encode($new_stats, JSON_PRETTY_PRINT) . "</pre>";
        echo "</div>";
    }
    
    echo "<h2>✅ الخلاصة:</h2>";
    echo "<p>يمكنك الآن الذهاب إلى <a href='home.php' target='_blank'>home.php</a> ومشاهدة الإحصائيات المحدثة!</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ خطأ: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
h1, h2 { color: #333; }
pre { background: white; padding: 10px; border-radius: 5px; overflow-x: auto; }
</style> 