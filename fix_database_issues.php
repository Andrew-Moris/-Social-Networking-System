<?php

define('DB_HOST', 'localhost');
define('DB_NAME', 'wep_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

$pdo_options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
    
    echo "<h2>🔧 إصلاح مشاكل قاعدة البيانات</h2>";
    echo "<pre style='background: #f0f0f0; padding: 10px; border-radius: 5px;'>";
    
    echo "📊 إنشاء/التحقق من الجداول...\n\n";
    
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
    echo "✅ جدول posts جاهز\n";
    
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
    echo "✅ جدول followers جاهز\n";
    
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
    echo "✅ جدول likes جاهز\n";
    
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
    echo "✅ جدول comments جاهز\n";
    
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
    echo "✅ جدول bookmarks جاهز\n";
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS shares (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            post_id INT NOT NULL,
            content TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ جدول shares جاهز\n";
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS comment_likes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            comment_id INT NOT NULL,
            user_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_comment_like (comment_id, user_id),
            FOREIGN KEY (comment_id) REFERENCES comments(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ جدول comment_likes جاهز\n";
    
    echo "\n📈 الإحصائيات الحالية:\n";
    
    $users_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "👥 عدد المستخدمين: $users_count\n";
    
    $posts_count = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
    echo "📝 عدد المنشورات: $posts_count\n";
    
    $followers_count = $pdo->query("SELECT COUNT(*) FROM followers")->fetchColumn();
    echo "🔗 عدد المتابعات: $followers_count\n";
    
    if ($users_count > 0 && $posts_count < 10) {
        echo "\n🎯 إنشاء منشورات تجريبية...\n";
        
        $users = $pdo->query("SELECT id, username FROM users LIMIT 3")->fetchAll();
        
        $sample_posts = [
            "مرحباً بكم في منصتنا الاجتماعية الجديدة! 🎉",
            "اليوم يوم جميل للبرمجة والتطوير 💻",
            "شاركونا أفكاركم وتجاربكم هنا 💡",
            "التواصل الاجتماعي يجمعنا جميعاً 🌍",
            "نتطلع لرؤية إبداعاتكم ومشاركاتكم 🚀",
            "الحياة أجمل مع الأصدقاء والعائلة ❤️",
            "كل يوم هو فرصة جديدة للتعلم والنمو 🌱",
            "السعادة في مشاركة اللحظات الجميلة 📸",
            "معاً نصنع مجتمعاً أفضل 🤝",
            "الإبداع لا حدود له عندما نعمل معاً 🎨"
        ];
        
        $post_index = 0;
        foreach ($users as $user) {
            $num_posts = rand(3, 4);
            for ($i = 0; $i < $num_posts && $post_index < count($sample_posts); $i++) {
                $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, created_at) VALUES (?, ?, ?)");
                $created_at = date('Y-m-d H:i:s', strtotime("-" . rand(1, 30) . " days"));
                $stmt->execute([$user['id'], $sample_posts[$post_index], $created_at]);
                $post_index++;
            }
            echo "✅ تم إنشاء منشورات للمستخدم {$user['username']}\n";
        }
    }
    
    if ($users_count > 1 && $followers_count < 5) {
        echo "\n🤝 إنشاء متابعات تجريبية...\n";
        
        $users = $pdo->query("SELECT id FROM users")->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($users as $follower_id) {
            $other_users = array_diff($users, [$follower_id]);
            shuffle($other_users);
            $to_follow = array_slice($other_users, 0, rand(2, 3));
            
            foreach ($to_follow as $followed_id) {
                try {
                    $stmt = $pdo->prepare("INSERT IGNORE INTO followers (follower_id, followed_id) VALUES (?, ?)");
                    $stmt->execute([$follower_id, $followed_id]);
                } catch (Exception $e) {
                }
            }
        }
        echo "✅ تم إنشاء متابعات تجريبية\n";
    }
    
    if ($posts_count > 0) {
        echo "\n💬 إنشاء تفاعلات تجريبية...\n";
        
        $posts = $pdo->query("SELECT id FROM posts")->fetchAll(PDO::FETCH_COLUMN);
        $users = $pdo->query("SELECT id FROM users")->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($posts as $post_id) {
            $likers = array_rand(array_flip($users), rand(1, min(3, count($users))));
            if (!is_array($likers)) $likers = [$likers];
            
            foreach ($likers as $user_id) {
                try {
                    $stmt = $pdo->prepare("INSERT IGNORE INTO likes (post_id, user_id) VALUES (?, ?)");
                    $stmt->execute([$post_id, $user_id]);
                } catch (Exception $e) {
                }
            }
        }
        
        $sample_comments = [
            "منشور رائع! 👍",
            "أوافقك الرأي تماماً",
            "شكراً على المشاركة",
            "ممتاز، استمر!",
            "أعجبني هذا كثيراً ❤️"
        ];
        
        foreach (array_slice($posts, 0, 5) as $post_id) {
            $commenter = $users[array_rand($users)];
            $comment = $sample_comments[array_rand($sample_comments)];
            
            try {
                $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
                $stmt->execute([$post_id, $commenter, $comment]);
            } catch (Exception $e) {
            }
        }
        
        echo "✅ تم إنشاء تفاعلات تجريبية\n";
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
    
    echo "\n📊 الإحصائيات النهائية:\n";
    
    $final_posts = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
    $final_followers = $pdo->query("SELECT COUNT(*) FROM followers")->fetchColumn();
    $final_likes = $pdo->query("SELECT COUNT(*) FROM likes")->fetchColumn();
    $final_comments = $pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn();
    
    echo "📝 المنشورات: $final_posts\n";
    echo "🔗 المتابعات: $final_followers\n";
    echo "❤️ الإعجابات: $final_likes\n";
    echo "💬 التعليقات: $final_comments\n";
    
    echo "\n✨ تم الانتهاء بنجاح!\n";
    echo "\n🔗 يمكنك الآن زيارة:\n";
    echo "- <a href='home.php'>الصفحة الرئيسية</a>\n";
    echo "- <a href='u.php'>صفحة الملف الشخصي</a>\n";
    echo "- <a href='discover.php'>اكتشف المستخدمين</a>\n";
    
} catch (PDOException $e) {
    echo "❌ خطأ في قاعدة البيانات: " . $e->getMessage() . "\n";
    echo "التفاصيل: " . $e->getTraceAsString() . "\n";
} catch (Exception $e) {
    echo "❌ خطأ عام: " . $e->getMessage() . "\n";
}

echo "</pre>";
?> 