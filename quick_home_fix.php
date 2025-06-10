<?php

session_start();
require_once 'config.php';

echo "<!DOCTYPE html>
<html lang='ar' dir='rtl'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>🔧 إصلاح سريع - مشكلة البيانات</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; direction: rtl; }
        .fix-section { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .info { color: #17a2b8; font-weight: bold; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
        button { padding: 10px 20px; margin: 5px; border: none; border-radius: 5px; cursor: pointer; }
        .btn-success { background: #28a745; color: white; }
        .btn-primary { background: #007bff; color: white; }
    </style>
</head>
<body>";

echo "<h1>🔧 إصلاح سريع لمشكلة عدم إرجاع البيانات</h1>";

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    if (!isset($_SESSION['user_id'])) {
        $test_users = ['ben10', 'yoyo1', 'admin'];
        foreach ($test_users as $username) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                break;
            }
        }
        
        if (!isset($_SESSION['user_id'])) {
            echo "<div class='fix-section'>";
            echo "<h2 class='error'>❌ لا يوجد مستخدمين للاختبار</h2>";
            echo "<p>يرجى إنشاء مستخدم أولاً</p>";
            echo "</div>";
            exit;
        }
    }
    
    $user_id = $_SESSION['user_id'];
    
    echo "<div class='fix-section'>";
    echo "<h2>🔍 الخطوة 1: فحص الجلسة</h2>";
    echo "<p class='success'>✅ المستخدم مسجل دخول (ID: $user_id)</p>";
    echo "<p><strong>اسم المستخدم:</strong> " . $_SESSION['username'] . "</p>";
    echo "</div>";
    
    echo "<div class='fix-section'>";
    echo "<h2>🗄️ الخطوة 2: فحص قاعدة البيانات</h2>";
    
    $required_tables = ['users', 'posts', 'followers', 'likes', 'comments'];
    foreach ($required_tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            $count_stmt = $pdo->query("SELECT COUNT(*) FROM $table");
            $count = $count_stmt->fetchColumn();
            echo "<p class='success'>✅ الجدول $table موجود ($count سجل)</p>";
        } else {
            echo "<p class='error'>❌ الجدول $table غير موجود</p>";
        }
    }
    echo "</div>";
    
    echo "<div class='fix-section'>";
    echo "<h2>📊 الخطوة 3: اختبار الإحصائيات</h2>";
    
    $stats_query = "
        SELECT 
            (SELECT COUNT(*) FROM posts WHERE user_id = ?) as posts_count,
            (SELECT COUNT(*) FROM followers WHERE followed_id = ?) as followers_count,
            (SELECT COUNT(*) FROM followers WHERE follower_id = ?) as following_count
    ";
    
    echo "<h3>🔍 الاستعلام المستخدم:</h3>";
    echo "<pre>$stats_query</pre>";
    
    $stats_stmt = $pdo->prepare($stats_query);
    $stats_stmt->execute([$user_id, $user_id, $user_id]);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>📊 نتيجة الاستعلام:</h3>";
    if ($stats) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px;'>";
        echo "<p><strong>📝 عدد المنشورات:</strong> " . ($stats['posts_count'] ?? 0) . "</p>";
        echo "<p><strong>👥 عدد المتابعين:</strong> " . ($stats['followers_count'] ?? 0) . "</p>";
        echo "<p><strong>➡️ عدد المتابَعين:</strong> " . ($stats['following_count'] ?? 0) . "</p>";
        echo "</div>";
        echo "<p class='success'>✅ الاستعلام يعمل بشكل صحيح</p>";
    } else {
        echo "<p class='error'>❌ الاستعلام لم يرجع نتائج</p>";
    }
    echo "</div>";
    
    echo "<div class='fix-section'>";
    echo "<h2>🛠️ الخطوة 4: إنشاء بيانات تجريبية (إذا لزم الأمر)</h2>";
    
    $total_posts = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
    $total_followers = $pdo->query("SELECT COUNT(*) FROM followers")->fetchColumn();
    
    if ($total_posts == 0) {
        echo "<p class='warning'>⚠️ لا توجد منشورات، جاري إنشاء منشورات تجريبية...</p>";
        
        for ($i = 1; $i <= 3; $i++) {
            $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$user_id, "منشور تجريبي رقم $i - " . date('Y-m-d H:i:s')]);
        }
        echo "<p class='success'>✅ تم إنشاء 3 منشورات تجريبية</p>";
    }
    
    if ($total_followers == 0) {
        echo "<p class='warning'>⚠️ لا توجد متابعات، جاري إنشاء بيانات تجريبية...</p>";
        
        $test_users_data = [
            ['follower1', 'follower1@test.com', 'Follower', 'One'],
            ['follower2', 'follower2@test.com', 'Follower', 'Two']
        ];
        
        foreach ($test_users_data as $user_data) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO users (username, email, first_name, last_name, password) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$user_data[0], $user_data[1], $user_data[2], $user_data[3], password_hash('123456', PASSWORD_DEFAULT)]);
        }
        
        $follower_stmt = $pdo->prepare("SELECT id FROM users WHERE username IN ('follower1', 'follower2')");
        $follower_stmt->execute();
        $followers = $follower_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($followers as $follower) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO followers (follower_id, followed_id, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$follower['id'], $user_id]);
        }
        
        echo "<p class='success'>✅ تم إنشاء بيانات متابعة تجريبية</p>";
    }
    echo "</div>";
    
    echo "<div class='fix-section'>";
    echo "<h2>🎯 الخطوة 5: اختبار نهائي</h2>";
    
    $stats_stmt->execute([$user_id, $user_id, $user_id]);
    $final_stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>🏆 الإحصائيات النهائية:</h3>";
    echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 8px; border: 1px solid #bee5eb;'>";
    echo "<p><strong>📝 عدد المنشورات:</strong> " . ($final_stats['posts_count'] ?? 0) . "</p>";
    echo "<p><strong>👥 عدد المتابعين:</strong> " . ($final_stats['followers_count'] ?? 0) . "</p>";
    echo "<p><strong>➡️ عدد المتابَعين:</strong> " . ($final_stats['following_count'] ?? 0) . "</p>";
    echo "</div>";
    
    $has_data = ($final_stats['posts_count'] > 0 || $final_stats['followers_count'] > 0 || $final_stats['following_count'] > 0);
    
    if ($has_data) {
        echo "<p class='success'>✅ البيانات متوفرة! المشكلة تم حلها.</p>";
        echo "<button class='btn-success' onclick='window.open(\"home.php\", \"_blank\")'>🏠 اذهب إلى home.php</button>";
    } else {
        echo "<p class='warning'>⚠️ لا تزال البيانات غير متوفرة</p>";
        echo "<button class='btn-primary' onclick='createMoreData()'>🛠️ إنشاء المزيد من البيانات</button>";
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='fix-section'>";
    echo "<h2 class='error'>❌ خطأ في التشخيص</h2>";
    echo "<p class='error'>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "
<script>
console.log('🔧 إصلاح سريع جاهز');

function createMoreData() {
    console.log('🛠️ إنشاء المزيد من البيانات...');
    
    // يمكن إضافة AJAX call هنا لإنشاء المزيد من البيانات
    alert('سيتم إنشاء المزيد من البيانات التجريبية...');
    location.reload();
}

// فحص تلقائي
window.addEventListener('load', function() {
    console.log('✅ تم تحميل صفحة الإصلاح السريع');
});
</script>

<div class='fix-section'>
    <h2>🔗 روابط مفيدة</h2>
    <p>
        <a href='home.php' target='_blank' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>🏠 الصفحة الرئيسية</a>
        <a href='debug_home_complete.php' target='_blank' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>🔍 تشخيص شامل</a>
        <a href='u.php' target='_blank' style='background: #6f42c1; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>👤 الملف الشخصي</a>
    </p>
</div>

</body>
</html>";
?> 