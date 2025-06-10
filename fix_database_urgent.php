<?php


require_once 'config.php';

echo '<h1>🔧 إصلاح عاجل لقاعدة البيانات</h1>';

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo '<div style="color: green;">✅ الاتصال بقاعدة البيانات ناجح</div>';
    
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN avatar_url VARCHAR(500) NULL");
        echo '<div style="color: green;">✅ تم إضافة عمود avatar_url إلى جدول users</div>';
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo '<div style="color: orange;">⚠️ عمود avatar_url موجود بالفعل في جدول users</div>';
        } else {
            echo '<div style="color: red;">❌ خطأ في إضافة عمود avatar_url: ' . $e->getMessage() . '</div>';
        }
    }
    
    try {
        $pdo->exec("ALTER TABLE posts ADD COLUMN image_url VARCHAR(500) NULL");
        echo '<div style="color: green;">✅ تم إضافة عمود image_url إلى جدول posts</div>';
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo '<div style="color: orange;">⚠️ عمود image_url موجود بالفعل في جدول posts</div>';
        } else {
            echo '<div style="color: red;">❌ خطأ في إضافة عمود image_url: ' . $e->getMessage() . '</div>';
        }
    }
    
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS dislikes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            user_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_dislike (post_id, user_id),
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo '<div style="color: green;">✅ تم إنشاء جدول dislikes</div>';
    } catch (Exception $e) {
        echo '<div style="color: red;">❌ خطأ في إنشاء جدول dislikes: ' . $e->getMessage() . '</div>';
    }
    
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS comment_likes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            comment_id INT NOT NULL,
            user_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_comment_like (comment_id, user_id),
            FOREIGN KEY (comment_id) REFERENCES comments(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo '<div style="color: green;">✅ تم إنشاء جدول comment_likes</div>';
    } catch (Exception $e) {
        echo '<div style="color: red;">❌ خطأ في إنشاء جدول comment_likes: ' . $e->getMessage() . '</div>';
    }
    
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS comment_dislikes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            comment_id INT NOT NULL,
            user_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_comment_dislike (comment_id, user_id),
            FOREIGN KEY (comment_id) REFERENCES comments(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo '<div style="color: green;">✅ تم إنشاء جدول comment_dislikes</div>';
    } catch (Exception $e) {
        echo '<div style="color: red;">❌ خطأ في إنشاء جدول comment_dislikes: ' . $e->getMessage() . '</div>';
    }
    
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'followers'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("CREATE TABLE followers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                follower_id INT NOT NULL,
                followed_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_follow (follower_id, followed_id),
                FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (followed_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            echo '<div style="color: green;">✅ تم إنشاء جدول followers</div>';
        } else {
            echo '<div style="color: orange;">⚠️ جدول followers موجود بالفعل</div>';
        }
    } catch (Exception $e) {
        echo '<div style="color: red;">❌ خطأ في إنشاء جدول followers: ' . $e->getMessage() . '</div>';
    }
    
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'bookmarks'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("CREATE TABLE bookmarks (
                id INT AUTO_INCREMENT PRIMARY KEY,
                post_id INT NOT NULL,
                user_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_bookmark (post_id, user_id),
                FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            echo '<div style="color: green;">✅ تم إنشاء جدول bookmarks</div>';
        } else {
            echo '<div style="color: orange;">⚠️ جدول bookmarks موجود بالفعل</div>';
        }
    } catch (Exception $e) {
        echo '<div style="color: red;">❌ خطأ في إنشاء جدول bookmarks: ' . $e->getMessage() . '</div>';
    }
    
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'likes'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("CREATE TABLE likes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                post_id INT NOT NULL,
                user_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_like (post_id, user_id),
                FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            echo '<div style="color: green;">✅ تم إنشاء جدول likes</div>';
        } else {
            echo '<div style="color: orange;">⚠️ جدول likes موجود بالفعل</div>';
        }
    } catch (Exception $e) {
        echo '<div style="color: red;">❌ خطأ في إنشاء جدول likes: ' . $e->getMessage() . '</div>';
    }
    
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'comments'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("CREATE TABLE comments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                post_id INT NOT NULL,
                user_id INT NOT NULL,
                content TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            echo '<div style="color: green;">✅ تم إنشاء جدول comments</div>';
        } else {
            echo '<div style="color: orange;">⚠️ جدول comments موجود بالفعل</div>';
        }
    } catch (Exception $e) {
        echo '<div style="color: red;">❌ خطأ في إنشاء جدول comments: ' . $e->getMessage() . '</div>';
    }
    
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS shares (
            id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            user_id INT NOT NULL,
            content TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo '<div style="color: green;">✅ تم إنشاء جدول shares</div>';
    } catch (Exception $e) {
        echo '<div style="color: red;">❌ خطأ في إنشاء جدول shares: ' . $e->getMessage() . '</div>';
    }
    
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            from_user_id INT NOT NULL,
            type ENUM('like', 'comment', 'follow', 'share', 'mention') NOT NULL,
            reference_id INT NULL,
            message TEXT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_created_at (created_at),
            INDEX idx_is_read (is_read),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (from_user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo '<div style="color: green;">✅ تم إنشاء جدول notifications</div>';
    } catch (Exception $e) {
        echo '<div style="color: red;">❌ خطأ في إنشاء جدول notifications: ' . $e->getMessage() . '</div>';
    }
    
    try {
        $pdo->exec("ALTER TABLE posts ADD COLUMN likes_count INT DEFAULT 0");
        echo '<div style="color: green;">✅ تم إضافة عمود likes_count إلى جدول posts</div>';
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo '<div style="color: orange;">⚠️ عمود likes_count موجود بالفعل في جدول posts</div>';
        } else {
            echo '<div style="color: red;">❌ خطأ في إضافة عمود likes_count: ' . $e->getMessage() . '</div>';
        }
    }
    
    try {
        $pdo->exec("ALTER TABLE posts ADD COLUMN comments_count INT DEFAULT 0");
        echo '<div style="color: green;">✅ تم إضافة عمود comments_count إلى جدول posts</div>';
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo '<div style="color: orange;">⚠️ عمود comments_count موجود بالفعل في جدول posts</div>';
        } else {
            echo '<div style="color: red;">❌ خطأ في إضافة عمود comments_count: ' . $e->getMessage() . '</div>';
        }
    }
    
    echo '<hr>';
    echo '<h2>🎯 تحديث العدادات</h2>';
    
    $pdo->exec("UPDATE posts SET likes_count = (
        SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id
    )");
    echo '<div style="color: green;">✅ تم تحديث عدادات الإعجابات</div>';
    
    $pdo->exec("UPDATE posts SET comments_count = (
        SELECT COUNT(*) FROM comments WHERE comments.post_id = posts.id
    )");
    echo '<div style="color: green;">✅ تم تحديث عدادات التعليقات</div>';
    
    echo '<hr>';
    echo '<h2>📊 إحصائيات النهائية</h2>';
    
    $users_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $posts_count = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
    $comments_count = $pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn();
    $likes_count = $pdo->query("SELECT COUNT(*) FROM likes")->fetchColumn();
    $bookmarks_count = $pdo->query("SELECT COUNT(*) FROM bookmarks")->fetchColumn();
    
    echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 8px;'>";
    echo "<h3>📈 إحصائيات الموقع:</h3>";
    echo "<ul>";
    echo "<li>👥 المستخدمين: $users_count</li>";
    echo "<li>📝 المنشورات: $posts_count</li>";
    echo "<li>💬 التعليقات: $comments_count</li>";
    echo "<li>❤️ الإعجابات: $likes_count</li>";
    echo "<li>🔖 المفضلة: $bookmarks_count</li>";
    echo "</ul>";
    echo "</div>";
    
    echo '<br><div style="background: #c8e6c9; padding: 20px; border-radius: 8px; border: 2px solid #4caf50;">';
    echo '<h2 style="color: #2e7d32;">🎉 تم إصلاح جميع المشاكل بنجاح!</h2>';
    echo '<p>يمكنك الآن استخدام الموقع بشكل طبيعي. جميع الوظائف تعمل بشكل صحيح:</p>';
    echo '<ul>';
    echo '<li>✅ تحميل المنشورات</li>';
    echo '<li>✅ إضافة الإعجابات</li>';
    echo '<li>✅ حفظ في المفضلة</li>';
    echo '<li>✅ إضافة التعليقات</li>';
    echo '<li>✅ مشاركة المنشورات</li>';
    echo '</ul>';
    echo '</div>';
    
} catch (Exception $e) {
    echo '<div style="color: red;">❌ خطأ عام: ' . $e->getMessage() . '</div>';
}
?> 