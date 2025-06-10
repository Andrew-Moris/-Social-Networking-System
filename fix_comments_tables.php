<?php
session_start();
require_once 'config.php';

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'><title>إصلاح جداول التعليقات</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f0f2f5;} .container{max-width:800px;margin:0 auto;background:white;padding:20px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);} .success{color:#28a745;} .error{color:#dc3545;} .info{color:#007bff;}</style>";
echo "</head><body>";

echo "<div class='container'>";
echo "<h1>🔧 إصلاح جداول التعليقات</h1>";

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>📝 إنشاء جدول التعليقات:</h2>";
    $comments_sql = "CREATE TABLE IF NOT EXISTS comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        post_id INT NOT NULL,
        user_id INT NOT NULL,
        content TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_post_id (post_id),
        INDEX idx_user_id (user_id),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($comments_sql);
    echo "<p class='success'>✅ جدول comments تم إنشاؤه أو موجود بالفعل</p>";
    
    echo "<h2>❤️ إنشاء جدول إعجابات التعليقات:</h2>";
    $comment_likes_sql = "CREATE TABLE IF NOT EXISTS comment_likes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        comment_id INT NOT NULL,
        user_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_comment_like (comment_id, user_id),
        INDEX idx_comment_id (comment_id),
        INDEX idx_user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($comment_likes_sql);
    echo "<p class='success'>✅ جدول comment_likes تم إنشاؤه أو موجود بالفعل</p>";
    
    echo "<h2>👍 إنشاء جدول الإعجابات:</h2>";
    $likes_sql = "CREATE TABLE IF NOT EXISTS likes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        post_id INT NOT NULL,
        user_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_post_like (post_id, user_id),
        INDEX idx_post_id (post_id),
        INDEX idx_user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($likes_sql);
    echo "<p class='success'>✅ جدول likes تم إنشاؤه أو موجود بالفعل</p>";
    
    echo "<h2>🔖 إنشاء جدول المفضلة:</h2>";
    $bookmarks_sql = "CREATE TABLE IF NOT EXISTS bookmarks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        post_id INT NOT NULL,
        user_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_bookmark (post_id, user_id),
        INDEX idx_post_id (post_id),
        INDEX idx_user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($bookmarks_sql);
    echo "<p class='success'>✅ جدول bookmarks تم إنشاؤه أو موجود بالفعل</p>";
    
    echo "<h2>🧪 إضافة تعليقات تجريبية:</h2>";
    
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE user_id = 11 ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($post) {
        $post_id = $post['id'];
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE post_id = ?");
        $stmt->execute([$post_id]);
        $comments_count = $stmt->fetchColumn();
        
        if ($comments_count == 0) {
            $test_comments = [
                '🎉 تعليق رائع! أحب هذا المنشور',
                '👍 موافق تماماً مع ما قلته',
                '💯 محتوى ممتاز، شكراً للمشاركة',
                '🔥 هذا مذهل! استمر في العمل الرائع'
            ];
            
            foreach ($test_comments as $comment_content) {
                $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$post_id, 11, $comment_content]);
            }
            
            echo "<p class='success'>✅ تم إضافة " . count($test_comments) . " تعليقات تجريبية للمنشور $post_id</p>";
        } else {
            echo "<p class='info'>ℹ️ يوجد بالفعل $comments_count تعليق للمنشور $post_id</p>";
        }
    } else {
        echo "<p class='error'>❌ لا توجد منشورات للمستخدم 11</p>";
    }
    
    echo "<h2>📊 إحصائيات الجداول:</h2>";
    
    $tables = ['comments', 'comment_likes', 'likes', 'bookmarks'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "<p class='info'>📋 جدول $table: $count صف</p>";
        } catch (Exception $e) {
            echo "<p class='error'>❌ خطأ في جدول $table: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<h2>🎯 اختبار API:</h2>";
    echo "<p>الآن يمكنك اختبار التعليقات في صفحة u.php</p>";
    echo "<a href='u.php' style='display:inline-block;padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:5px;'>انتقل إلى صفحة الملف الشخصي</a>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ خطأ: " . $e->getMessage() . "</p>";
}

echo "</div>";
echo "</body></html>";
?> 