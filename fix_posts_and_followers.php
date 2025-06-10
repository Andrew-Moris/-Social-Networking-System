<?php

session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    die("يرجى تسجيل الدخول أولاً");
}

$current_user_id = $_SESSION['user_id'];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>🔧 إصلاح مشكلة المنشورات والمتابعين</h2>";
    echo "<pre style='background: #f0f0f0; padding: 10px; border-radius: 5px;'>";
    
    echo "📊 التحقق من الجداول...\n";
    
    $tables = ['users', 'posts', 'followers', 'likes', 'comments', 'bookmarks', 'shares'];
    $missing_tables = [];
    
    foreach ($tables as $table) {
        $check = $pdo->query("SHOW TABLES LIKE '$table'")->rowCount();
        if ($check == 0) {
            $missing_tables[] = $table;
            echo "❌ الجدول '$table' غير موجود\n";
        } else {
            echo "✅ الجدول '$table' موجود\n";
        }
    }
    
    if (!empty($missing_tables)) {
        echo "\n📦 إنشاء الجداول المفقودة...\n";
        
        if (in_array('posts', $missing_tables)) {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS posts (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    content TEXT,
                    image_url VARCHAR(255),
                    location VARCHAR(255),
                    is_private TINYINT DEFAULT 0,
                    likes_count INT DEFAULT 0,
                    comments_count INT DEFAULT 0,
                    shares_count INT DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    INDEX idx_user_id (user_id),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            echo "✅ تم إنشاء جدول posts\n";
        }
        
        if (in_array('followers', $missing_tables)) {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS followers (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    follower_id INT NOT NULL,
                    followed_id INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_follow (follower_id, followed_id),
                    FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (followed_id) REFERENCES users(id) ON DELETE CASCADE,
                    INDEX idx_follower (follower_id),
                    INDEX idx_followed (followed_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            echo "✅ تم إنشاء جدول followers\n";
        }
        
        if (in_array('likes', $missing_tables)) {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS likes (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    post_id INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_like (user_id, post_id),
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
                    INDEX idx_post_id (post_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            echo "✅ تم إنشاء جدول likes\n";
        }
        
        if (in_array('comments', $missing_tables)) {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS comments (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    post_id INT NOT NULL,
                    content TEXT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
                    INDEX idx_post_id (post_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            echo "✅ تم إنشاء جدول comments\n";
        }
        
        if (in_array('bookmarks', $missing_tables)) {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS bookmarks (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    post_id INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_bookmark (user_id, post_id),
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            echo "✅ تم إنشاء جدول bookmarks\n";
        }
        
        if (in_array('shares', $missing_tables)) {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS shares (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    post_id INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            echo "✅ تم إنشاء جدول shares\n";
        }
    }
    
    echo "\n📈 إحصائيات البيانات الحالية:\n";
    
    $users_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "👥 عدد المستخدمين: $users_count\n";
    
    $posts_count = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
    echo "📝 عدد المنشورات: $posts_count\n";
    
    $followers_count = $pdo->query("SELECT COUNT(*) FROM followers")->fetchColumn();
    echo "🔗 عدد المتابعات: $followers_count\n";
    
    if ($posts_count < 5) {
        echo "\n🎯 إنشاء منشورات تجريبية...\n";
        
        $sample_posts = [
            "مرحباً بكم في منصتنا الاجتماعية الجديدة! 🎉",
            "اليوم يوم جميل للبرمجة والتطوير 💻",
            "شاركونا أفكاركم وتجاربكم هنا 💡",
            "التواصل الاجتماعي يجمعنا جميعاً 🌍",
            "نتطلع لرؤية إبداعاتكم ومشاركاتكم 🚀"
        ];
        
        foreach ($sample_posts as $content) {
            $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$current_user_id, $content]);
        }
        
        echo "✅ تم إنشاء 5 منشورات تجريبية\n";
    }
    
    if ($followers_count < 3 && $users_count > 1) {
        echo "\n🤝 إنشاء متابعات تجريبية...\n";
        
        $other_users = $pdo->prepare("SELECT id FROM users WHERE id != ? LIMIT 3");
        $other_users->execute([$current_user_id]);
        $users = $other_users->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($users as $user_id) {
            try {
                $stmt = $pdo->prepare("INSERT IGNORE INTO followers (follower_id, followed_id) VALUES (?, ?)");
                $stmt->execute([$current_user_id, $user_id]);
                
                $stmt->execute([$user_id, $current_user_id]);
            } catch (Exception $e) {
            }
        }
        
        echo "✅ تم إنشاء متابعات تجريبية\n";
    }
    
    echo "\n🔄 تحديث عدادات المنشورات...\n";
    
    $pdo->exec("
        UPDATE posts p
        SET 
            likes_count = (SELECT COUNT(*) FROM likes WHERE post_id = p.id),
            comments_count = (SELECT COUNT(*) FROM comments WHERE post_id = p.id),
            shares_count = (SELECT COUNT(*) FROM shares WHERE post_id = p.id)
    ");
    
    echo "✅ تم تحديث جميع العدادات\n";
    
    $upload_dirs = ['uploads', 'uploads/posts', 'uploads/avatars'];
    foreach ($upload_dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
            echo "✅ تم إنشاء مجلد: $dir\n";
        }
    }
    
    echo "\n✨ تم الانتهاء من الإصلاحات!\n";
    echo "\n🔗 الروابط:\n";
    echo "- <a href='home.php'>الصفحة الرئيسية</a>\n";
    echo "- <a href='u.php'>صفحة الملف الشخصي</a>\n";
    echo "- <a href='discover.php'>اكتشف المستخدمين</a>\n";
    
} catch (PDOException $e) {
    echo "❌ خطأ في قاعدة البيانات: " . $e->getMessage() . "\n";
    error_log("Database error in fix_posts_and_followers.php: " . $e->getMessage());
} catch (Exception $e) {
    echo "❌ خطأ عام: " . $e->getMessage() . "\n";
    error_log("General error in fix_posts_and_followers.php: " . $e->getMessage());
}

echo "</pre>";
?> 