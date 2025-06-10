<?php

require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo "يجب تسجيل الدخول أولاً";
    exit;
}

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>🔧 إعداد جداول النظام الاجتماعي</h1><br>";
    
    echo "📝 إنشاء جدول الإعجابات (likes)...<br>";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS likes (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            post_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_post_id (post_id),
            INDEX idx_user_id (user_id),
            INDEX idx_created_at (created_at),
            UNIQUE KEY unique_like (post_id, user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ تم إنشاء جدول likes بنجاح<br><br>";
    
    echo "📝 إنشاء جدول المفضلة (bookmarks)...<br>";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS bookmarks (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            post_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_post_id (post_id),
            INDEX idx_user_id (user_id),
            INDEX idx_created_at (created_at),
            UNIQUE KEY unique_bookmark (post_id, user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ تم إنشاء جدول bookmarks بنجاح<br><br>";
    
    echo "📝 إنشاء جدول التعليقات (comments)...<br>";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS comments (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            post_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            content TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_post_id (post_id),
            INDEX idx_user_id (user_id),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ تم إنشاء جدول comments بنجاح<br><br>";
    
    echo "📝 إنشاء جدول المشاركات (shares)...<br>";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS shares (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            post_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            content TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_post_id (post_id),
            INDEX idx_user_id (user_id),
            INDEX idx_created_at (created_at),
            UNIQUE KEY unique_share (post_id, user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ تم إنشاء جدول shares بنجاح<br><br>";
    
    echo "📝 إنشاء جدول إعجابات التعليقات (comment_likes)...<br>";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS comment_likes (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            comment_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_comment_id (comment_id),
            INDEX idx_user_id (user_id),
            INDEX idx_created_at (created_at),
            UNIQUE KEY unique_comment_like (comment_id, user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ تم إنشاء جدول comment_likes بنجاح<br><br>";
    
    echo "📝 إنشاء جدول الإشعارات (notifications)...<br>";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS notifications (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            from_user_id INT UNSIGNED NOT NULL,
            type ENUM('like', 'comment', 'follow', 'share', 'mention') NOT NULL,
            reference_id INT UNSIGNED NULL,
            message TEXT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_created_at (created_at),
            INDEX idx_is_read (is_read),
            INDEX idx_type (type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ تم إنشاء جدول notifications بنجاح<br><br>";
    
    echo "<h2>📊 تقرير الجداول المنشأة:</h2>";
    $tables = ['likes', 'bookmarks', 'comments', 'shares', 'comment_likes', 'notifications'];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            $count_stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $count = $count_stmt->fetch()['count'];
            echo "✅ $table - موجود ($count سجل)<br>";
        } else {
            echo "❌ $table - غير موجود<br>";
        }
    }
    
    echo "<br><h2>🎉 تم إعداد جميع الجداول بنجاح!</h2>";
    echo "<p>يمكنك الآن استخدام جميع ميزات النظام الاجتماعي:</p>";
    echo "<ul>";
    echo "<li>✅ الإعجاب بالمنشورات</li>";
    echo "<li>✅ حفظ المنشورات في المفضلة</li>";
    echo "<li>✅ التعليق على المنشورات</li>";
    echo "<li>✅ مشاركة المنشورات</li>";
    echo "<li>✅ الإعجاب بالتعليقات</li>";
    echo "<li>✅ نظام الإشعارات</li>";
    echo "</ul>";
    
    echo "<br><p><strong>🔗 روابط مفيدة:</strong></p>";
    echo "<a href='discover.php' style='color: #3B82F6; text-decoration: underline;'>صفحة الاستكشاف</a> | ";
    echo "<a href='bookmarks.php' style='color: #3B82F6; text-decoration: underline;'>صفحة المفضلة</a> | ";
    echo "<a href='home.php' style='color: #3B82F6; text-decoration: underline;'>الصفحة الرئيسية</a>";
    
} catch (PDOException $e) {
    echo "<h2 style='color: red;'>❌ خطأ في قاعدة البيانات:</h2>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
    echo "<p>تأكد من:</p>";
    echo "<ul>";
    echo "<li>تشغيل خادم MySQL</li>";
    echo "<li>صحة بيانات الاتصال في config.php</li>";
    echo "<li>وجود قاعدة البيانات</li>";
    echo "</ul>";
}
?>

<style>
    body {
        font-family: 'Arial', sans-serif;
        background: #0A0F1E;
        color: white;
        padding: 20px;
        line-height: 1.6;
    }
    h1, h2 {
        color: #3B82F6;
    }
    ul {
        background: rgba(255,255,255,0.1);
        padding: 15px;
        border-radius: 10px;
        margin: 10px 0;
    }
    a {
        color: #3B82F6;
        text-decoration: none;
    }
    a:hover {
        text-decoration: underline;
    }
</style> 