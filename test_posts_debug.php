<?php
session_start();
require_once 'config.php';

echo '<h1>🔧 تشخيص مشكلة تحميل المنشورات</h1>';

if (!isset($_SESSION['user_id'])) {
    echo '<div style="color: red;">❌ المستخدم غير مسجل دخول</div>';
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

echo "<div style='color: green;'>✅ المستخدم: $username (ID: $user_id)</div>";

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo '<div style="color: green;">✅ الاتصال بقاعدة البيانات ناجح</div>';
    
    $tables = ['users', 'posts', 'likes', 'comments', 'bookmarks'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<div style='color: green;'>✅ الجدول $table موجود</div>";
        } else {
            echo "<div style='color: red;'>❌ الجدول $table مفقود</div>";
        }
    }
    
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
    $count_stmt->execute([$user_id]);
    $post_count = $count_stmt->fetchColumn();
    
    echo "<div style='color: blue;'>📊 عدد منشورات المستخدم: $post_count</div>";
    
    if ($post_count == 0) {
        echo '<div style="color: orange;">⚠️ لا توجد منشورات للمستخدم</div>';
        
        $test_stmt = $pdo->prepare("INSERT INTO posts (user_id, content, created_at) VALUES (?, ?, NOW())");
        $test_stmt->execute([$user_id, 'منشور تجريبي - مرحبا بكم في موقعي!']);
        
        echo '<div style="color: green;">✅ تم إنشاء منشور تجريبي</div>';
        
        $post_count = 1;
    }
    
    echo '<hr><h2>🔍 اختبار جلب المنشورات (مبسط)</h2>';
    
    $simple_stmt = $pdo->prepare("
        SELECT p.*, u.username, u.first_name, u.last_name 
        FROM posts p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.user_id = ? 
        ORDER BY p.created_at DESC 
        LIMIT 5
    ");
    $simple_stmt->execute([$user_id]);
    $simple_posts = $simple_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div style='color: blue;'>📝 تم جلب " . count($simple_posts) . " منشور</div>";
    
    if (!empty($simple_posts)) {
        echo '<h3>المنشورات المجلبة:</h3>';
        foreach ($simple_posts as $post) {
            echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
            echo "<strong>ID:</strong> " . $post['id'] . "<br>";
            echo "<strong>المحتوى:</strong> " . htmlspecialchars($post['content']) . "<br>";
            echo "<strong>المستخدم:</strong> " . $post['username'] . "<br>";
            echo "<strong>التاريخ:</strong> " . $post['created_at'] . "<br>";
            echo "</div>";
        }
    }
    
    echo '<hr><h2>🧪 اختبار الاستعلام المعقد</h2>';
    
    try {
        $complex_stmt = $pdo->prepare("
            SELECT p.*, u.username, u.first_name, u.last_name, u.avatar_url as user_avatar,
                   COALESCE(l.likes_count, 0) as likes_count,
                   COALESCE(c.comments_count, 0) as comments_count,
                   CASE WHEN ul.id IS NOT NULL THEN 1 ELSE 0 END as is_liked,
                   CASE WHEN ub.id IS NOT NULL THEN 1 ELSE 0 END as is_bookmarked
            FROM posts p 
            JOIN users u ON p.user_id = u.id 
            LEFT JOIN (
                SELECT post_id, COUNT(*) as likes_count 
                FROM likes 
                GROUP BY post_id
            ) l ON p.id = l.post_id
            LEFT JOIN (
                SELECT post_id, COUNT(*) as comments_count 
                FROM comments 
                GROUP BY post_id
            ) c ON p.id = c.post_id
            LEFT JOIN likes ul ON p.id = ul.post_id AND ul.user_id = ?
            LEFT JOIN bookmarks ub ON p.id = ub.post_id AND ub.user_id = ?
            WHERE p.user_id = ? 
            ORDER BY p.created_at DESC 
            LIMIT 5
        ");
        $complex_stmt->execute([$user_id, $user_id, $user_id]);
        $complex_posts = $complex_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<div style='color: green;'>✅ الاستعلام المعقد نجح - تم جلب " . count($complex_posts) . " منشور</div>";
        
        if (!empty($complex_posts)) {
            echo '<h3>المنشورات مع التفاصيل:</h3>';
            foreach ($complex_posts as $post) {
                echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0; background: #f9f9f9;'>";
                echo "<strong>ID:</strong> " . $post['id'] . "<br>";
                echo "<strong>المحتوى:</strong> " . htmlspecialchars($post['content']) . "<br>";
                echo "<strong>إعجابات:</strong> " . $post['likes_count'] . "<br>";
                echo "<strong>تعليقات:</strong> " . $post['comments_count'] . "<br>";
                echo "<strong>معجب؟:</strong> " . ($post['is_liked'] ? 'نعم' : 'لا') . "<br>";
                echo "<strong>محفوظ؟:</strong> " . ($post['is_bookmarked'] ? 'نعم' : 'لا') . "<br>";
                echo "</div>";
            }
        }
        
    } catch (Exception $e) {
        echo "<div style='color: red;'>❌ خطأ في الاستعلام المعقد: " . $e->getMessage() . "</div>";
    }
    
    echo '<hr><div style="background: #e8f5e8; padding: 15px; border-radius: 8px;">';
    echo '<h2 style="color: #2d5930;">🎯 التشخيص مكتمل!</h2>';
    echo '<p>تحقق من النتائج أعلاه لتحديد سبب المشكلة.</p>';
    echo '</div>';
    
} catch (Exception $e) {
    echo '<div style="color: red;">❌ خطأ عام: ' . $e->getMessage() . '</div>';
    echo '<div style="color: red;">تفاصيل الخطأ: ' . $e->getTraceAsString() . '</div>';
}
?> 