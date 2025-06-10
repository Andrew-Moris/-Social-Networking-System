<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

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

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تشخيص وإصلاح المشاكل</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 20px;
            direction: rtl;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #007bff;
            padding-bottom: 10px;
        }
        .section {
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 5px;
            border-right: 5px solid #007bff;
        }
        .success {
            color: #28a745;
            font-weight: bold;
        }
        .error {
            color: #dc3545;
            font-weight: bold;
        }
        .warning {
            color: #ffc107;
            font-weight: bold;
        }
        .info {
            color: #17a2b8;
            font-weight: bold;
        }
        pre {
            background: #e9ecef;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 5px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #0056b3;
        }
        .btn-success {
            background: #28a745;
        }
        .btn-success:hover {
            background: #218838;
        }
        .btn-danger {
            background: #dc3545;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: right;
        }
        th {
            background: #007bff;
            color: white;
        }
        tr:nth-child(even) {
            background: #f2f2f2;
        }
        .fix-button {
            background: #28a745;
            color: white;
            border: none;
            padding: 5px 15px;
            border-radius: 3px;
            cursor: pointer;
        }
        .fix-button:hover {
            background: #218838;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 تشخيص وإصلاح شامل للمشاكل</h1>
        
        <?php
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
            
            echo '<div class="section">';
            echo '<h2>1️⃣ فحص الاتصال بقاعدة البيانات</h2>';
            echo '<p class="success">✅ تم الاتصال بقاعدة البيانات بنجاح</p>';
            echo '</div>';
            
            echo '<div class="section">';
            echo '<h2>2️⃣ فحص الجداول المطلوبة</h2>';
            
            $required_tables = [
                'users' => 'جدول المستخدمين',
                'posts' => 'جدول المنشورات',
                'followers' => 'جدول المتابعين',
                'likes' => 'جدول الإعجابات',
                'comments' => 'جدول التعليقات',
                'bookmarks' => 'جدول المحفوظات',
                'shares' => 'جدول المشاركات',
                'messages' => 'جدول الرسائل',
                'notifications' => 'جدول الإشعارات'
            ];
            
            $missing_tables = [];
            
            echo '<table>';
            echo '<tr><th>الجدول</th><th>الوصف</th><th>الحالة</th><th>الإجراء</th></tr>';
            
            foreach ($required_tables as $table => $description) {
                $check = $pdo->query("SHOW TABLES LIKE '$table'")->rowCount();
                echo '<tr>';
                echo '<td>' . $table . '</td>';
                echo '<td>' . $description . '</td>';
                if ($check > 0) {
                    echo '<td class="success">✅ موجود</td>';
                    echo '<td>-</td>';
                } else {
                    echo '<td class="error">❌ مفقود</td>';
                    echo '<td><button class="fix-button" onclick="createTable(\'' . $table . '\')">إنشاء</button></td>';
                    $missing_tables[] = $table;
                }
                echo '</tr>';
            }
            echo '</table>';
            
            if (!empty($missing_tables)) {
                echo '<p class="warning">⚠️ يتم إنشاء الجداول المفقودة...</p>';
                
                foreach ($missing_tables as $table) {
                    switch ($table) {
                        case 'posts':
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
                            break;
                            
                        case 'followers':
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
                            break;
                            
                        case 'likes':
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
                            break;
                            
                        case 'comments':
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
                            break;
                            
                        case 'bookmarks':
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
                            break;
                            
                        case 'shares':
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
                            break;
                            
                        case 'messages':
                            $pdo->exec("
                                CREATE TABLE IF NOT EXISTS messages (
                                    id INT AUTO_INCREMENT PRIMARY KEY,
                                    sender_id INT NOT NULL,
                                    receiver_id INT NOT NULL,
                                    content TEXT NOT NULL,
                                    is_read TINYINT DEFAULT 0,
                                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
                                    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
                                    INDEX idx_sender (sender_id),
                                    INDEX idx_receiver (receiver_id)
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                            ");
                            break;
                            
                        case 'notifications':
                            $pdo->exec("
                                CREATE TABLE IF NOT EXISTS notifications (
                                    id INT AUTO_INCREMENT PRIMARY KEY,
                                    user_id INT NOT NULL,
                                    from_user_id INT,
                                    type VARCHAR(50),
                                    reference_id INT,
                                    message TEXT,
                                    is_read TINYINT DEFAULT 0,
                                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                                    FOREIGN KEY (from_user_id) REFERENCES users(id) ON DELETE CASCADE,
                                    INDEX idx_user (user_id)
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                            ");
                            break;
                    }
                    echo '<p class="success">✅ تم إنشاء جدول ' . $table . '</p>';
                }
            }
            echo '</div>';
            
            echo '<div class="section">';
            echo '<h2>3️⃣ إحصائيات البيانات الحالية</h2>';
            
            $stats = [];
            $stats['users'] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
            $stats['posts'] = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
            $stats['followers'] = $pdo->query("SELECT COUNT(*) FROM followers")->fetchColumn();
            $stats['likes'] = $pdo->query("SELECT COUNT(*) FROM likes")->fetchColumn();
            $stats['comments'] = $pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn();
            
            echo '<table>';
            echo '<tr><th>النوع</th><th>العدد</th><th>الحالة</th></tr>';
            foreach ($stats as $type => $count) {
                echo '<tr>';
                echo '<td>' . $type . '</td>';
                echo '<td>' . $count . '</td>';
                echo '<td class="' . ($count > 0 ? 'success' : 'warning') . '">' . 
                     ($count > 0 ? '✅ يحتوي على بيانات' : '⚠️ فارغ') . '</td>';
                echo '</tr>';
            }
            echo '</table>';
            echo '</div>';
            
            echo '<div class="section">';
            echo '<h2>4️⃣ فحص المستخدم الحالي</h2>';
            
            if (isset($_SESSION['user_id'])) {
                $user_id = $_SESSION['user_id'];
                echo '<p class="success">✅ المستخدم مسجل دخول (ID: ' . $user_id . ')</p>';
                
                $user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $user_stmt->execute([$user_id]);
                $user = $user_stmt->fetch();
                
                if ($user) {
                    echo '<table>';
                    echo '<tr><th>المعلومة</th><th>القيمة</th></tr>';
                    echo '<tr><td>اسم المستخدم</td><td>' . htmlspecialchars($user['username']) . '</td></tr>';
                    echo '<tr><td>الاسم</td><td>' . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . '</td></tr>';
                    echo '<tr><td>البريد الإلكتروني</td><td>' . htmlspecialchars($user['email']) . '</td></tr>';
                    echo '</table>';
                    
                    $user_posts = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
                    $user_posts->execute([$user_id]);
                    $posts_count = $user_posts->fetchColumn();
                    
                    $user_followers = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE followed_id = ?");
                    $user_followers->execute([$user_id]);
                    $followers_count = $user_followers->fetchColumn();
                    
                    $user_following = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE follower_id = ?");
                    $user_following->execute([$user_id]);
                    $following_count = $user_following->fetchColumn();
                    
                    echo '<h3>إحصائيات المستخدم:</h3>';
                    echo '<table>';
                    echo '<tr><th>النوع</th><th>العدد</th></tr>';
                    echo '<tr><td>المنشورات</td><td>' . $posts_count . '</td></tr>';
                    echo '<tr><td>المتابعون</td><td>' . $followers_count . '</td></tr>';
                    echo '<tr><td>يتابع</td><td>' . $following_count . '</td></tr>';
                    echo '</table>';
                }
            } else {
                echo '<p class="error">❌ لا يوجد مستخدم مسجل دخول</p>';
                echo '<a href="login.php" class="btn">تسجيل الدخول</a>';
            }
            echo '</div>';
            
            echo '<div class="section">';
            echo '<h2>5️⃣ إنشاء بيانات تجريبية</h2>';
            
            if (isset($_POST['create_sample_data'])) {
                if ($stats['users'] > 0 && $stats['posts'] < 10) {
                    $users = $pdo->query("SELECT id FROM users LIMIT 5")->fetchAll(PDO::FETCH_COLUMN);
                    
                    $sample_posts = [
                        "مرحباً بكم في منصتنا الاجتماعية! 🎉",
                        "يوم جميل للبرمجة والتطوير 💻",
                        "شاركونا أفكاركم وتجاربكم 💡",
                        "التواصل الاجتماعي يجمعنا 🌍",
                        "نتطلع لرؤية إبداعاتكم 🚀",
                        "الحياة أجمل مع الأصدقاء ❤️",
                        "كل يوم فرصة جديدة للتعلم 🌱",
                        "السعادة في المشاركة 📸",
                        "معاً نصنع مجتمعاً أفضل 🤝",
                        "الإبداع لا حدود له 🎨"
                    ];
                    
                    foreach ($users as $user_id) {
                        for ($i = 0; $i < 2; $i++) {
                            $content = $sample_posts[array_rand($sample_posts)];
                            $stmt = $pdo->prepare("INSERT INTO posts (user_id, content) VALUES (?, ?)");
                            $stmt->execute([$user_id, $content]);
                        }
                    }
                    echo '<p class="success">✅ تم إنشاء منشورات تجريبية</p>';
                }
                
                if ($stats['users'] > 1 && $stats['followers'] < 10) {
                    $users = $pdo->query("SELECT id FROM users")->fetchAll(PDO::FETCH_COLUMN);
                    
                    foreach ($users as $follower) {
                        $to_follow = array_diff($users, [$follower]);
                        shuffle($to_follow);
                        $to_follow = array_slice($to_follow, 0, rand(2, 4));
                        
                        foreach ($to_follow as $followed) {
                            try {
                                $stmt = $pdo->prepare("INSERT IGNORE INTO followers (follower_id, followed_id) VALUES (?, ?)");
                                $stmt->execute([$follower, $followed]);
                            } catch (Exception $e) {
                            }
                        }
                    }
                    echo '<p class="success">✅ تم إنشاء متابعات تجريبية</p>';
                }
                
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
                    
                    if (rand(0, 1)) {
                        $comments = ["رائع! 👍", "أوافقك الرأي", "شكراً للمشاركة", "ممتاز!", "أعجبني ❤️"];
                        $comment = $comments[array_rand($comments)];
                        $commenter = $users[array_rand($users)];
                        
                        try {
                            $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
                            $stmt->execute([$post_id, $commenter, $comment]);
                        } catch (Exception $e) {
                        }
                    }
                }
                
                echo '<p class="success">✅ تم إنشاء تفاعلات تجريبية</p>';
                
                $pdo->exec("
                    UPDATE posts p
                    SET 
                        likes_count = (SELECT COUNT(*) FROM likes WHERE post_id = p.id),
                        comments_count = (SELECT COUNT(*) FROM comments WHERE post_id = p.id),
                        shares_count = (SELECT COUNT(*) FROM shares WHERE post_id = p.id)
                ");
                
                echo '<p class="success">✅ تم تحديث جميع العدادات</p>';
            }
            
            echo '<form method="post">';
            echo '<button type="submit" name="create_sample_data" class="btn btn-success">إنشاء بيانات تجريبية</button>';
            echo '</form>';
            echo '</div>';
            
            echo '<div class="section">';
            echo '<h2>6️⃣ فحص المجلدات المطلوبة</h2>';
            
            $required_dirs = [
                'uploads' => 'مجلد الرفع الرئيسي',
                'uploads/posts' => 'مجلد صور المنشورات',
                'uploads/avatars' => 'مجلد الصور الشخصية',
                'uploads/chat' => 'مجلد صور المحادثات'
            ];
            
            echo '<table>';
            echo '<tr><th>المجلد</th><th>الوصف</th><th>الحالة</th></tr>';
            
            foreach ($required_dirs as $dir => $description) {
                echo '<tr>';
                echo '<td>' . $dir . '</td>';
                echo '<td>' . $description . '</td>';
                
                if (is_dir($dir)) {
                    echo '<td class="success">✅ موجود</td>';
                } else {
                    if (mkdir($dir, 0755, true)) {
                        echo '<td class="success">✅ تم إنشاؤه</td>';
                    } else {
                        echo '<td class="error">❌ فشل الإنشاء</td>';
                    }
                }
                echo '</tr>';
            }
            echo '</table>';
            echo '</div>';
            
            echo '<div class="section">';
            echo '<h2>7️⃣ فحص الملفات المهمة</h2>';
            
            $important_files = [
                'config.php' => 'ملف التكوين',
                'functions.php' => 'ملف الوظائف',
                'api/posts_fixed.php' => 'API المنشورات',
                'api/social.php' => 'API التفاعلات',
                'api/upload_avatar.php' => 'API رفع الصور'
            ];
            
            echo '<table>';
            echo '<tr><th>الملف</th><th>الوصف</th><th>الحالة</th></tr>';
            
            foreach ($important_files as $file => $description) {
                echo '<tr>';
                echo '<td>' . $file . '</td>';
                echo '<td>' . $description . '</td>';
                echo '<td class="' . (file_exists($file) ? 'success' : 'error') . '">' . 
                     (file_exists($file) ? '✅ موجود' : '❌ مفقود') . '</td>';
                echo '</tr>';
            }
            echo '</table>';
            echo '</div>';
            
            echo '<div class="section">';
            echo '<h2>8️⃣ إجراءات سريعة</h2>';
            
            echo '<div style="display: flex; gap: 10px; flex-wrap: wrap;">';
            echo '<a href="home.php" class="btn">الصفحة الرئيسية</a>';
            echo '<a href="u.php" class="btn">الملف الشخصي</a>';
            echo '<a href="discover.php" class="btn">اكتشف</a>';
            echo '<a href="friends.php" class="btn">الأصدقاء</a>';
            echo '<a href="chat.php" class="btn">المحادثات</a>';
            echo '<a href="settings.php" class="btn">الإعدادات</a>';
            
            if (isset($_SESSION['user_id'])) {
                echo '<a href="logout.php" class="btn btn-danger">تسجيل الخروج</a>';
            } else {
                echo '<a href="login.php" class="btn btn-success">تسجيل الدخول</a>';
            }
            echo '</div>';
            echo '</div>';
            
        } catch (PDOException $e) {
            echo '<div class="section">';
            echo '<h2 class="error">❌ خطأ في قاعدة البيانات</h2>';
            echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
            echo '<p>تأكد من:</p>';
            echo '<ul>';
            echo '<li>تشغيل خادم MySQL/MariaDB</li>';
            echo '<li>وجود قاعدة بيانات wep_db</li>';
            echo '<li>صحة بيانات الاتصال</li>';
            echo '</ul>';
            echo '</div>';
        }
        ?>
        
        <div class="section">
            <h2>📋 ملخص الحالة</h2>
            <p>هذه الصفحة تقوم بفحص وإصلاح جميع المشاكل المحتملة في النظام.</p>
            <p>إذا استمرت المشاكل، يرجى:</p>
            <ol>
                <li>التأكد من تشغيل XAMPP بشكل صحيح</li>
                <li>مراجعة ملف error.log للأخطاء</li>
                <li>التحقق من صلاحيات المجلدات</li>
                <li>تجربة تسجيل الخروج والدخول مرة أخرى</li>
            </ol>
        </div>
    </div>
    
    <script>
    function createTable(tableName) {
        if (confirm('هل تريد إنشاء جدول ' + tableName + '؟')) {
            window.location.href = '?create_table=' + tableName;
        }
    }
    </script>
</body>
</html> 