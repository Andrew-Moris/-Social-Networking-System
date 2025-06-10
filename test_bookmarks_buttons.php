<?php


require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo "يجب تسجيل الدخول أولاً<br>";
    echo "<a href='login.php'>Login</a>";
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>🔧 فحص نظام المفضلة</h1><br>";
    
    $tables_stmt = $pdo->query("SHOW TABLES LIKE 'bookmarks'");
    if ($tables_stmt->rowCount() == 0) {
        echo "❌ جدول bookmarks غير موجود - سيتم إنشاؤه الآن...<br>";
        $pdo->exec("
            CREATE TABLE bookmarks (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                post_id INT UNSIGNED NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_post_id (post_id),
                UNIQUE KEY unique_bookmark (user_id, post_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "✅ تم إنشاء جدول bookmarks<br>";
    } else {
        echo "✅ جدول bookmarks موجود<br>";
    }
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM bookmarks WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $bookmark_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "📊 عدد المنشورات المحفوظة في مفضلتك: <strong>{$bookmark_count}</strong><br><br>";
    
    if ($bookmark_count == 0) {
        echo "❗ لا توجد منشورات محفوظة في المفضلة<br>";
        echo "💡 لإختبار الزر، قم أولاً بحفظ بعض المنشورات من صفحة <a href='discover.php' target='_blank'>Discover</a><br><br>";
        
        $stmt = $pdo->prepare("SELECT id FROM posts LIMIT 1");
        $stmt->execute();
        $test_post = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($test_post) {
            $post_id = $test_post['id'];
            echo "🧪 إضافة منشور تجريبي للمفضلة (Post ID: {$post_id})...<br>";
            
            try {
                $stmt = $pdo->prepare("INSERT IGNORE INTO bookmarks (user_id, post_id) VALUES (?, ?)");
                $stmt->execute([$user_id, $post_id]);
                echo "✅ تم إضافة منشور تجريبي للمفضلة<br>";
            } catch (PDOException $e) {
                echo "❌ خطأ في إضافة المنشور التجريبي: " . $e->getMessage() . "<br>";
            }
        }
    }
    
    echo "<br>📄 فحص صفحة bookmarks.php:<br>";
    if (file_exists('bookmarks.php')) {
        echo "✅ ملف bookmarks.php موجود<br>";
        
        $content = file_get_contents('bookmarks.php');
        
        if (strpos($content, 'removeBookmark') !== false) {
            echo "✅ دالة removeBookmark موجودة<br>";
        } else {
            echo "❌ دالة removeBookmark غير موجودة<br>";
        }
        
        if (strpos($content, 'remove-bookmark-btn') !== false) {
            echo "✅ CSS class remove-bookmark-btn موجود<br>";
        } else {
            echo "❌ CSS class remove-bookmark-btn غير موجود<br>";
        }
        
        if (strpos($content, 'toggle_bookmark') !== false) {
            echo "✅ AJAX call toggle_bookmark موجود<br>";
        } else {
            echo "❌ AJAX call toggle_bookmark غير موجود<br>";
        }
    } else {
        echo "❌ ملف bookmarks.php غير موجود<br>";
    }
    
    echo "<br>📡 فحص API (api/social.php):<br>";
    if (file_exists('api/social.php')) {
        echo "✅ ملف api/social.php موجود<br>";
        
        $api_content = file_get_contents('api/social.php');
        if (strpos($api_content, 'toggle_bookmark') !== false) {
            echo "✅ toggle_bookmark endpoint موجود في API<br>";
        } else {
            echo "❌ toggle_bookmark endpoint غير موجود في API<br>";
        }
    } else {
        echo "❌ ملف api/social.php غير موجود<br>";
    }
    
    echo "<br><hr><br>";
    echo "🔗 <a href='bookmarks.php' target='_blank'>افتح صفحة المفضلة</a><br>";
    echo "🔗 <a href='discover.php' target='_blank'>افتح صفحة الاستكشاف</a><br>";
    echo "🔗 <a href='view_logs.php' target='_blank'>عرض سجل الأخطاء</a><br>";
    
} catch (PDOException $e) {
    echo "❌ خطأ في قاعدة البيانات: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Test Bookmarks Buttons</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #f5f5f5;
        }
        
        h1 {
            color: #333;
        }
        
        a {
            color: #007bff;
            text-decoration: none;
            padding: 8px 16px;
            background: #e7f3ff;
            border-radius: 4px;
            margin: 5px;
            display: inline-block;
        }
        
        a:hover {
            background: #007bff;
            color: white;
        }
        
        strong {
            color: #28a745;
        }
    </style>
</head>
<body>
</body>
</html> 