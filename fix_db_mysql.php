<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'wep_db';

echo '<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعداد قاعدة البيانات</title>
    <style>
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
            color: #333;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1, h2, h3 {
            color: #1a73e8;
        }
        .success {
            color: #0d8527;
            background-color: #e8f5e9;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .error {
            color: #c62828;
            background-color: #ffebee;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .info {
            color: #01579b;
            background-color: #e1f5fe;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 12px;
            text-align: right;
        }
        th {
            background-color: #f2f2f2;
        }
        a {
            color: #1a73e8;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        .btn {
            display: inline-block;
            background-color: #1a73e8;
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            margin-top: 10px;
        }
        .btn:hover {
            background-color: #0d47a1;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>أداة إعداد قاعدة البيانات WEP</h1>';

try {
    $conn = new PDO("mysql:host=$host", $user, $password);
    
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo '<div class="success">✓ تم الاتصال بخادم MySQL بنجاح</div>';
    
    $result = $conn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbname'")->fetch();
    
    if (!$result) {
        $conn->exec("CREATE DATABASE $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo '<div class="success">✓ تم إنشاء قاعدة البيانات بنجاح</div>';
    } else {
        echo '<div class="info">ℹ قاعدة البيانات موجودة بالفعل</div>';
    }
    
    $conn = null;
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        first_name VARCHAR(50),
        last_name VARCHAR(50),
        profile_picture VARCHAR(255) DEFAULT 'assets/img/default-avatar.png',
        bio TEXT,
        is_verified BOOLEAN DEFAULT FALSE,
        is_private BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $conn->exec($sql);
    echo '<div class="success">✓ تم إنشاء جدول المستخدمين بنجاح</div>';
    
    $username = 'test_user';
    $email = 'test@example.com';
    $password = password_hash('password123', PASSWORD_DEFAULT);

    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, first_name, last_name) VALUES (?, ?, ?, 'مستخدم', 'تجريبي')");
        $stmt->execute([$username, $email, $password]);
        
        echo '<div class="success">✓ تم إنشاء مستخدم تجريبي بنجاح</div>';
        echo '<div class="info">
            <strong>بيانات الدخول:</strong><br>
            اسم المستخدم: test_user<br>
            كلمة المرور: password123
        </div>';
    } else {
        echo '<div class="info">ℹ المستخدم التجريبي موجود بالفعل</div>';
    }
    
    $sql = "CREATE TABLE IF NOT EXISTS posts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        content TEXT NOT NULL,
        image_url VARCHAR(255) DEFAULT NULL,
        likes_count INT DEFAULT 0,
        dislikes_count INT DEFAULT 0,
        comments_count INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql);
    echo '<div class="success">✓ تم إنشاء جدول المنشورات بنجاح</div>';
    
    $sql = "CREATE TABLE IF NOT EXISTS followers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        follower_id INT NOT NULL,
        followed_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_followers (follower_id, followed_id),
        FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (followed_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql);
    echo '<div class="success">✓ تم إنشاء جدول المتابعين بنجاح</div>';
    
    $sql = "CREATE TABLE IF NOT EXISTS likes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        post_id INT NOT NULL,
        is_like BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_like (user_id, post_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql);
    echo '<div class="success">✓ تم إنشاء جدول الإعجابات بنجاح</div>';
    
    $sql = "CREATE TABLE IF NOT EXISTS comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        post_id INT NOT NULL,
        content TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql);
    echo '<div class="success">✓ تم إنشاء جدول التعليقات بنجاح</div>';
    
    $sql = "CREATE TABLE IF NOT EXISTS api_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token VARCHAR(255) NOT NULL UNIQUE,
        expires_at TIMESTAMP NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql);
    echo '<div class="success">✓ تم إنشاء جدول توكنات API بنجاح</div>';
    
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $test_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($test_user) {
        $user_id = $test_user['id'];
        
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM posts WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $post_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($post_count < 3) {
            $sample_posts = [
                "مرحباً بالجميع في منصتنا الاجتماعية! أنا متحمس للتواصل معكم والمشاركة في هذا المجتمع الرائع. 😊 #مرحبا #منصة_جديدة",
                "اليوم كان يوماً رائعاً! قمت بزيارة المكتبة واشتريت بعض الكتب الجديدة عن التكنولوجيا والذكاء الاصطناعي. هل لديكم اقتراحات لكتب أخرى؟ 📚 #كتب #تكنولوجيا",
                "أحب التصوير كثيراً، خاصة تصوير الطبيعة. هذه هي إحدى الصور التي التقطتها في رحلتي الأخيرة إلى الجبال. أتمنى أن تنال إعجابكم! 🏞️ #تصوير #طبيعة"
            ];
            
            foreach ($sample_posts as $content) {
                $stmt = $conn->prepare("INSERT INTO posts (user_id, content) VALUES (?, ?)");
                $stmt->execute([$user_id, $content]);
            }
            
            echo '<div class="success">✓ تم إضافة منشورات تجريبية للمستخدم</div>';
        } else {
            echo '<div class="info">ℹ المنشورات التجريبية موجودة بالفعل</div>';
        }
    }
    
    echo '<div class="info" style="margin-top: 20px;">
        <h3>تم إعداد قاعدة البيانات بنجاح!</h3>
        <p>يمكنك الآن استخدام التطبيق بشكل كامل. إليك بعض الروابط المفيدة:</p>
        <ul>
            <li><a href="/WEP/u.php" class="btn">صفحة الملف الشخصي</a></li>
            <li><a href="/WEP/login.php" class="btn">صفحة تسجيل الدخول</a></li>
        </ul>
    </div>';

} catch (PDOException $e) {
    echo '<div class="error">
        <h3>خطأ في قاعدة البيانات:</h3>
        <p>' . $e->getMessage() . '</p>
    </div>';
}

echo '
    </div>
</body>
</html>';
?>
