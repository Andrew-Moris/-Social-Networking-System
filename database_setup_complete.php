<?php

require_once 'config.php';

echo '<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعداد قاعدة البيانات الشاملة</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; direction: rtl; }
        .success { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 10px; margin: 5px 0; border-radius: 5px; }
        .error { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; margin: 5px 0; border-radius: 5px; }
        .warning { color: #856404; background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; margin: 5px 0; border-radius: 5px; }
        .info { color: #0c5460; background-color: #d1ecf1; border: 1px solid #bee5eb; padding: 10px; margin: 5px 0; border-radius: 5px; }
    </style>
</head>
<body>
<h1>🔧 إعداد قاعدة البيانات الشاملة</h1>';

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo '<div class="success">✅ تم الاتصال بقاعدة البيانات بنجاح</div>';

    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        first_name VARCHAR(50),
        last_name VARCHAR(50),
        bio TEXT,
        location VARCHAR(100),
        website VARCHAR(255),
        phone VARCHAR(20),
        avatar_url VARCHAR(255),
        cover_photo VARCHAR(255),
        is_verified BOOLEAN DEFAULT FALSE,
        is_private BOOLEAN DEFAULT FALSE,
        date_of_birth DATE,
        gender ENUM('male', 'female', 'other'),
        last_active TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_username (username),
        INDEX idx_email (email),
        INDEX idx_last_active (last_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo '<div class="success">✅ جدول المستخدمين (users) تم إنشاؤه/تحديثه بنجاح</div>';

    $sql = "CREATE TABLE IF NOT EXISTS posts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        content TEXT,
        image_url VARCHAR(500),
        location VARCHAR(100),
        is_private BOOLEAN DEFAULT FALSE,
        likes_count INT DEFAULT 0,
        comments_count INT DEFAULT 0,
        shares_count INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_id (user_id),
        INDEX idx_created_at (created_at),
        INDEX idx_is_private (is_private),
        FULLTEXT(content)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo '<div class="success">✅ جدول المنشورات (posts) تم إنشاؤه/تحديثه بنجاح</div>';

    $sql = "CREATE TABLE IF NOT EXISTS followers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        follower_id INT NOT NULL,
        followed_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_follow (follower_id, followed_id),
        FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (followed_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_follower (follower_id),
        INDEX idx_followed (followed_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo '<div class="success">✅ جدول المتابعين (followers) تم إنشاؤه/تحديثه بنجاح</div>';

    $sql = "CREATE TABLE IF NOT EXISTS likes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        post_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_like (user_id, post_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
        INDEX idx_user_id (user_id),
        INDEX idx_post_id (post_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo '<div class="success">✅ جدول الإعجابات (likes) تم إنشاؤه/تحديثه بنجاح</div>';

    $sql = "CREATE TABLE IF NOT EXISTS comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        post_id INT NOT NULL,
        user_id INT NOT NULL,
        content TEXT NOT NULL,
        parent_id INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE,
        INDEX idx_post_id (post_id),
        INDEX idx_user_id (user_id),
        INDEX idx_parent_id (parent_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo '<div class="success">✅ جدول التعليقات (comments) تم إنشاؤه/تحديثه بنجاح</div>';

    $sql = "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        from_user_id INT NOT NULL,
        type ENUM('like', 'comment', 'follow', 'share', 'mention', 'friend_request') NOT NULL,
        reference_id INT NULL,
        message TEXT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (from_user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_id (user_id),
        INDEX idx_created_at (created_at),
        INDEX idx_is_read (is_read),
        INDEX idx_type (type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo '<div class="success">✅ جدول الإشعارات (notifications) تم إنشاؤه/تحديثه بنجاح</div>';

    $sql = "CREATE TABLE IF NOT EXISTS bookmarks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        post_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_bookmark (user_id, post_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
        INDEX idx_user_id (user_id),
        INDEX idx_post_id (post_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo '<div class="success">✅ جدول المفضلة (bookmarks) تم إنشاؤه/تحديثه بنجاح</div>';

    $sql = "CREATE TABLE IF NOT EXISTS shares (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        post_id INT NOT NULL,
        content TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_share (user_id, post_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
        INDEX idx_user_id (user_id),
        INDEX idx_post_id (post_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo '<div class="success">✅ جدول المشاركات (shares) تم إنشاؤه/تحديثه بنجاح</div>';

    $sql = "CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        content TEXT NOT NULL,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_sender (sender_id),
        INDEX idx_receiver (receiver_id),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo '<div class="success">✅ جدول الرسائل (messages) تم إنشاؤه/تحديثه بنجاح</div>';

    $sql = "CREATE TABLE IF NOT EXISTS remember_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token VARCHAR(255) NOT NULL UNIQUE,
        expires_at TIMESTAMP NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_token (token),
        INDEX idx_expires_at (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo '<div class="success">✅ جدول جلسات التذكر (remember_tokens) تم إنشاؤه/تحديثه بنجاح</div>';

    $upload_dirs = [
        'uploads',
        'uploads/avatars', 
        'uploads/posts',
        'uploads/covers'
    ];

    foreach ($upload_dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
            echo '<div class="success">✅ تم إنشاء مجلد: ' . $dir . '</div>';
        } else {
            echo '<div class="info">📁 مجلد موجود بالفعل: ' . $dir . '</div>';
        }
        
        $htaccess_file = $dir . '/.htaccess';
        if (!file_exists($htaccess_file)) {
            file_put_contents($htaccess_file, "Options -Indexes\n<Files \"*.php\">\nOrder Allow,Deny\nDeny from all\n</Files>");
            echo '<div class="success">🔒 تم إنشاء ملف الحماية: ' . $htaccess_file . '</div>';
        }
    }

    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $user_count = $stmt->fetchColumn();
    
    if ($user_count == 0) {
        echo '<div class="warning">⚠️ لا توجد بيانات مستخدمين. سيتم إدراج بيانات تجريبية...</div>';
        
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, first_name, last_name, bio) VALUES (?, ?, ?, ?, ?, ?)");
        $password_hash = password_hash('123456', PASSWORD_DEFAULT);
        $stmt->execute(['admin', 'admin@sut.com', $password_hash, 'المدير', 'العام', 'حساب المدير التجريبي']);
        
        $stmt->execute(['user1', 'user1@sut.com', $password_hash, 'أحمد', 'محمد', 'مرحباً، أنا أحمد!']);
        $stmt->execute(['user2', 'user2@sut.com', $password_hash, 'فاطمة', 'علي', 'أحب التصوير والسفر']);
        
        echo '<div class="success">✅ تم إدراج بيانات تجريبية (3 مستخدمين)</div>';
        echo '<div class="info">📝 بيانات الدخول التجريبية:<br>
              - admin / 123456<br>
              - user1 / 123456<br>
              - user2 / 123456</div>';
    }

    echo '<h2>🎉 تم الانتهاء من إعداد قاعدة البيانات بنجاح!</h2>';
    
    echo '<h3>📊 إحصائيات قاعدة البيانات:</h3>';
    
    $tables = ['users', 'posts', 'followers', 'likes', 'comments', 'notifications', 'bookmarks', 'shares', 'messages'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo '<div class="info">📋 جدول ' . $table . ': ' . $count . ' سجل</div>';
        } catch (Exception $e) {
            echo '<div class="error">❌ خطأ في جدول ' . $table . ': ' . $e->getMessage() . '</div>';
        }
    }

} catch (PDOException $e) {
    echo '<div class="error">❌ خطأ في قاعدة البيانات: ' . $e->getMessage() . '</div>';
} catch (Exception $e) {
    echo '<div class="error">❌ خطأ عام: ' . $e->getMessage() . '</div>';
}

echo '<div style="margin-top: 30px; padding: 20px; background-color: #e8f5e8; border: 1px solid #4caf50; border-radius: 5px;">
    <h3>🔗 الخطوات التالية:</h3>
    <ol>
        <li><a href="u.php" target="_blank">اختبر صفحة الملف الشخصي</a></li>
        <li><a href="home.php" target="_blank">اختبر الصفحة الرئيسية</a></li>
        <li><a href="discover.php" target="_blank">اختبر صفحة الاستكشاف</a></li>
        <li><a href="bookmarks.php" target="_blank">اختبر صفحة المفضلة</a></li>
    </ol>
    <p><strong>ملاحظة:</strong> تأكد من تسجيل الدخول أولاً!</p>
</div>';

echo '</body></html>';
?> 