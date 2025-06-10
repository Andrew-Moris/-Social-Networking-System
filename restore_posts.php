<?php
require_once 'config.php';

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    $backup_tables = [
        'posts_backup',
        'posts_old',
        'posts_archive',
        'post',
        'user_posts'
    ];
    
    $found_backup = false;
    $backup_table = '';
    
    foreach ($backup_tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            $found_backup = true;
            $backup_table = $table;
            echo "وجدت نسخة احتياطية في جدول: $table\n";
            break;
        }
    }
    
    $pdo->exec("DROP TABLE IF EXISTS likes");
    $pdo->exec("DROP TABLE IF EXISTS comments");
    $pdo->exec("DROP TABLE IF EXISTS bookmarks");
    $pdo->exec("DROP TABLE IF EXISTS posts");
    
    $create_posts_table = "
        CREATE TABLE posts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            content TEXT,
            image_url VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($create_posts_table);
    echo "تم إنشاء جدول المنشورات بنجاح\n";
    
    $pdo->exec("
        CREATE TABLE likes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            post_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    
    $pdo->exec("
        CREATE TABLE comments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            post_id INT NOT NULL,
            content TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    
    $pdo->exec("
        CREATE TABLE bookmarks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            post_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    
    if ($found_backup) {
        $pdo->exec("INSERT INTO posts (user_id, content, image_url, created_at, updated_at) 
                    SELECT user_id, content, image_url, created_at, updated_at 
                    FROM $backup_table");
        echo "تم استعادة البيانات من النسخة الاحتياطية\n";
    } else {
        $user_id = $pdo->query("SELECT id FROM users LIMIT 1")->fetchColumn();
        
        if (!$user_id) {
            $pdo->exec("INSERT INTO users (username, password, email) VALUES ('test_user', '" . password_hash('123456', PASSWORD_DEFAULT) . "', 'test@example.com')");
            $user_id = $pdo->lastInsertId();
        }
        
        $sample_posts = [
            "مرحباً بكم في موقعنا! 🌟",
            "يوم جديد مليء بالإنجازات 💪",
            "شكراً لكل المتابعين ❤️",
            "نحو مستقبل أفضل 🚀",
            "معاً نحو النجاح 🌈"
        ];
        
        $stmt = $pdo->prepare("INSERT INTO posts (user_id, content) VALUES (?, ?)");
        foreach ($sample_posts as $content) {
            $stmt->execute([$user_id, $content]);
        }
        echo "تم إنشاء منشورات تجريبية\n";
    }
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    $count = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
    echo "عدد المنشورات في الجدول: $count\n";
    
    $posts = $pdo->query("
        SELECT p.*, u.username 
        FROM posts p 
        JOIN users u ON p.user_id = u.id 
        ORDER BY p.created_at DESC 
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nآخر 5 منشورات:\n";
    foreach ($posts as $post) {
        echo "المستخدم: {$post['username']}\n";
        echo "المحتوى: {$post['content']}\n";
        echo "التاريخ: {$post['created_at']}\n";
        echo "-------------------\n";
    }
    
    echo "\nتم الانتهاء بنجاح! يمكنك الآن العودة إلى الصفحة الرئيسية.\n";
    
} catch (PDOException $e) {
    echo "حدث خطأ: " . $e->getMessage() . "\n";
    
    try {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    } catch (Exception $e2) {
    }
}
?> 