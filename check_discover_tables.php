<?php

require_once 'config.php';

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>🔍 فحص جداول التعليقات لصفحة Discover</h2>";
    
    echo "<h3>📝 فحص جدول comments</h3>";
    try {
        $stmt = $pdo->query("DESCRIBE comments");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p style='color: green;'>✅ جدول comments موجود</p>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>{$col['Field']}</td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "<td>{$col['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ جدول comments غير موجود</p>";
        echo "<p>إنشاء جدول comments...</p>";
        
        $pdo->exec("CREATE TABLE comments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            user_id INT NOT NULL,
            content TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_post_id (post_id),
            INDEX idx_user_id (user_id),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        echo "<p style='color: green;'>✅ تم إنشاء جدول comments</p>";
    }
    
    echo "<h3>❤️ فحص جدول comment_likes</h3>";
    try {
        $stmt = $pdo->query("DESCRIBE comment_likes");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p style='color: green;'>✅ جدول comment_likes موجود</p>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>{$col['Field']}</td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "<td>{$col['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ جدول comment_likes غير موجود</p>";
        echo "<p>إنشاء جدول comment_likes...</p>";
        
        $pdo->exec("CREATE TABLE comment_likes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            comment_id INT NOT NULL,
            user_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (comment_id) REFERENCES comments(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_comment_like (comment_id, user_id),
            INDEX idx_comment_id (comment_id),
            INDEX idx_user_id (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        echo "<p style='color: green;'>✅ تم إنشاء جدول comment_likes</p>";
    }
    
    echo "<h3>👎 فحص جدول comment_dislikes</h3>";
    try {
        $stmt = $pdo->query("DESCRIBE comment_dislikes");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p style='color: green;'>✅ جدول comment_dislikes موجود</p>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>{$col['Field']}</td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "<td>{$col['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ جدول comment_dislikes غير موجود</p>";
        echo "<p>إنشاء جدول comment_dislikes...</p>";
        
        $pdo->exec("CREATE TABLE comment_dislikes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            comment_id INT NOT NULL,
            user_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (comment_id) REFERENCES comments(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_comment_dislike (comment_id, user_id),
            INDEX idx_comment_id (comment_id),
            INDEX idx_user_id (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        echo "<p style='color: green;'>✅ تم إنشاء جدول comment_dislikes</p>";
    }
    
    echo "<h3>💖 فحص جدول likes</h3>";
    try {
        $stmt = $pdo->query("DESCRIBE likes");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p style='color: green;'>✅ جدول likes موجود</p>";
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM likes");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<p>📊 عدد الإعجابات: {$count}</p>";
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ جدول likes غير موجود</p>";
        echo "<p>إنشاء جدول likes...</p>";
        
        $pdo->exec("CREATE TABLE likes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            user_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_like (post_id, user_id),
            INDEX idx_post_id (post_id),
            INDEX idx_user_id (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        echo "<p style='color: green;'>✅ تم إنشاء جدول likes</p>";
    }
    
    echo "<h3>🔖 فحص جدول bookmarks</h3>";
    try {
        $stmt = $pdo->query("DESCRIBE bookmarks");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p style='color: green;'>✅ جدول bookmarks موجود</p>";
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM bookmarks");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<p>📊 عدد المفضلات: {$count}</p>";
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ جدول bookmarks غير موجود</p>";
        echo "<p>إنشاء جدول bookmarks...</p>";
        
        $pdo->exec("CREATE TABLE bookmarks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            user_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_bookmark (post_id, user_id),
            INDEX idx_post_id (post_id),
            INDEX idx_user_id (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        echo "<p style='color: green;'>✅ تم إنشاء جدول bookmarks</p>";
    }
    
    echo "<h3>📊 إحصائيات عامة</h3>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM posts");
    $posts_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "<p>📝 عدد المنشورات: {$posts_count}</p>";
    
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM comments");
        $comments_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<p>💬 عدد التعليقات: {$comments_count}</p>";
    } catch (PDOException $e) {
        echo "<p>💬 عدد التعليقات: 0 (جدول غير موجود)</p>";
    }
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $users_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "<p>👥 عدد المستخدمين: {$users_count}</p>";
    
    echo "<h3>✅ تم الانتهاء من فحص جميع الجداول</h3>";
    echo "<p><a href='discover.php' style='background: #10b981; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔍 اختبار صفحة Discover</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ خطأ في قاعدة البيانات: " . $e->getMessage() . "</p>";
}
?> 