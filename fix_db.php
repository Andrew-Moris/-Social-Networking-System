<?php

$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'wep_db';

echo "<!DOCTYPE html>";
echo "<html dir='rtl'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<title>إصلاح قاعدة البيانات</title>";
echo "<style>";
echo "body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; margin: 0; padding: 20px; color: #333; background-color: #f5f7fa; }";
echo "h1, h2, h3 { color: #2563eb; }";
echo "h1 { border-bottom: 2px solid #e5e7eb; padding-bottom: 10px; }";
echo ".container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }";
echo ".success { color: #047857; background: #ecfdf5; padding: 10px; border-radius: 4px; margin: 10px 0; }";
echo ".error { color: #b91c1c; background: #fee2e2; padding: 10px; border-radius: 4px; margin: 10px 0; }";
echo ".warning { color: #92400e; background: #fff7ed; padding: 10px; border-radius: 4px; margin: 10px 0; }";
echo ".user-card { background: #f1f5f9; border-radius: 4px; padding: 10px; margin: 10px 0; border-left: 4px solid #3b82f6; }";
echo ".user-info { display: flex; justify-content: space-between; }";
echo ".buttons { margin-top: 20px; }";
echo ".btn { display: inline-block; background: #3b82f6; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; margin-right: 10px; }";
echo ".btn:hover { background: #2563eb; }";
echo "</style>";
echo "</head>";
echo "<body>";
echo "<div class='container'>";
echo "<h1>إصلاح قاعدة البيانات وإنشاء مستخدمين تجريبيين</h1>";

echo "<div class='buttons'>";
echo "<a href='?action=add_missing_columns' class='btn'>إضافة الأعمدة المفقودة إلى جدول المستخدمين</a>";
echo "</div>";

if (isset($_GET['action']) && $_GET['action'] === 'add_missing_columns') {
    try {
        $dsn = "mysql:host=$host;dbname=$dbname";
        $pdo = new PDO($dsn, $user, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $columns = [];
        $stmt = $pdo->query("SHOW COLUMNS FROM users");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $columns[] = $row['Field'];
        }
        
        if (!in_array('first_name', $columns)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN first_name VARCHAR(50) DEFAULT ''");
            echo "<div class='success'>تمت إضافة عمود first_name إلى جدول users بنجاح</div>";
        } else {
            echo "<div class='warning'>عمود first_name موجود بالفعل في جدول users</div>";
        }
        
        if (!in_array('last_name', $columns)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN last_name VARCHAR(50) DEFAULT ''");
            echo "<div class='success'>تمت إضافة عمود last_name إلى جدول users بنجاح</div>";
        } else {
            echo "<div class='warning'>عمود last_name موجود بالفعل في جدول users</div>";
        }
        
        if (!in_array('avatar_url', $columns)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN avatar_url VARCHAR(255) DEFAULT '/WEP/assets/images/default-avatar.png'");
            echo "<div class='success'>تمت إضافة عمود avatar_url إلى جدول users بنجاح</div>";
        } else {
            echo "<div class='warning'>عمود avatar_url موجود بالفعل في جدول users</div>";
        }
        
        $columns = [];
        $stmt = $pdo->query("SHOW COLUMNS FROM posts");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $columns[] = $row['Field'];
        }
        
        if (!in_array('media_type', $columns)) {
            $pdo->exec("ALTER TABLE posts ADD COLUMN media_type VARCHAR(10) DEFAULT NULL");
            echo "<div class='success'>تمت إضافة عمود media_type إلى جدول posts بنجاح</div>";
        } else {
            echo "<div class='warning'>عمود media_type موجود بالفعل في جدول posts</div>";
        }
        
        echo "<div class='success'>تم إصلاح قاعدة البيانات بنجاح</div>";
        echo "<div class='buttons'><a href='home.php' class='btn'>العودة إلى الصفحة الرئيسية</a></div>";
        
    } catch (PDOException $e) {
        echo "<div class='error'>خطأ في قاعدة البيانات: " . $e->getMessage() . "</div>";
    }
}

try {
    $dsn = "mysql:host=$host";
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div class='success'>تم الاتصال بخادم MySQL بنجاح</div>";
    
    $stmt = $pdo->query("SHOW DATABASES LIKE '$dbname'");
    $dbExists = $stmt->rowCount() > 0;
    
    if (!$dbExists) {
        $pdo->exec("CREATE DATABASE `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "<div class='success'>تم إنشاء قاعدة البيانات <strong>$dbname</strong> بنجاح</div>";
    } else {
        echo "<div class='warning'>قاعدة البيانات <strong>$dbname</strong> موجودة بالفعل</div>";
    }
    
    $pdo = null;
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        first_name VARCHAR(50),
        last_name VARCHAR(50),
        bio TEXT,
        avatar_url VARCHAR(255),
        cover_url VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        last_active TIMESTAMP NULL,
        is_active TINYINT(1) DEFAULT 1,
        role ENUM('user', 'admin') DEFAULT 'user'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    try {
        $pdo->exec($sql);
        echo "<div class='success'>تم إنشاء جدول المستخدمين بنجاح</div>";
        
        $testUsers = [
            ['ahmed', 'ahmed@example.com', 'أحمد', 'محمد', 'مهندس برمجيات في شركة تقنية | مهتم بالذكاء الاصطناعي'],
            ['sara', 'sara@example.com', 'سارة', 'أحمد', 'طالبة علوم حاسب | مطوّرة ويب'],
            ['omar', 'omar@example.com', 'عمر', 'خالد', 'مصمم واجهات مستخدم | مهتم بتجربة المستخدم'],
            ['nora', 'nora@example.com', 'نورا', 'فهد', 'مبرمجة | مهتمة بتطوير تطبيقات الهاتف'],
            ['youssef', 'youssef@example.com', 'يوسف', 'عبدالله', 'مطور ألعاب | عاشق للتكنولوجيا والابتكار']
        ];
        
        $usersCreated = 0;
        
        foreach ($testUsers as $user) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$user[0], $user[1]]);
            $userExists = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$userExists) {
                $hashedPassword = password_hash('password123', PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, first_name, last_name, bio) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$user[0], $user[1], $hashedPassword, $user[2], $user[3], $user[4]]);
                $usersCreated++;
                
                echo "<div class='user-card'>";
                echo "<div class='user-info'>";
                echo "<div>";
                echo "<strong>اسم المستخدم:</strong> {$user[0]}<br>";
                echo "<strong>الاسم:</strong> {$user[2]} {$user[3]}<br>";
                echo "<strong>البريد الإلكتروني:</strong> {$user[1]}<br>";
                echo "<strong>كلمة المرور:</strong> password123";
                echo "</div>";
                echo "</div>";
                echo "</div>";
            }
        }
        
        if ($usersCreated > 0) {
            echo "<div class='success'>تم إنشاء $usersCreated مستخدمين تجريبيين بنجاح</div>";
        } else {
            echo "<div class='warning'>المستخدمين التجريبيين موجودون بالفعل</div>";
        }
        
        $sql = "CREATE TABLE IF NOT EXISTS posts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            content TEXT NOT NULL,
            media_url VARCHAR(255),
            media_type ENUM('image', 'video', 'audio', 'file') NULL,
            is_private TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        echo "<div class='success'>تم إنشاء جدول المنشورات بنجاح</div>";
        
        $sql = "CREATE TABLE IF NOT EXISTS likes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            post_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_like (user_id, post_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        echo "<div class='success'>تم إنشاء جدول الإعجابات بنجاح</div>";
        
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
        
        $pdo->exec($sql);
        echo "<div class='success'>تم إنشاء جدول التعليقات بنجاح</div>";
        
        $sql = "CREATE TABLE IF NOT EXISTS bookmarks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            post_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_bookmark (user_id, post_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        echo "<div class='success'>تم إنشاء جدول المنشورات المحفوظة بنجاح</div>";
        
        $sql = "CREATE TABLE IF NOT EXISTS shares (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            post_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_share (user_id, post_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        echo "<div class='success'>تم إنشاء جدول المشاركات بنجاح</div>";
        
        $sql = "CREATE TABLE IF NOT EXISTS followers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            follower_id INT NOT NULL,
            followed_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (followed_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_follow (follower_id, followed_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        echo "<div class='success'>تم إنشاء جدول المتابعين بنجاح</div>";
        
        $sql = "CREATE TABLE IF NOT EXISTS messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sender_id INT NOT NULL,
            receiver_id INT NOT NULL,
            content TEXT NOT NULL,
            media_url VARCHAR(255) NULL,
            media_type ENUM('image', 'video', 'audio', 'file') NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_sender (sender_id),
            INDEX idx_receiver (receiver_id),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        echo "<div class='success'>تم إنشاء جدول الرسائل بنجاح</div>";
        
       
        
        echo "<div class='success'>تم إنشاء قاعدة البيانات والجداول بنجاح</div>";
        
    } catch (PDOException $e) {
        echo "<div class='error'>فشل إنشاء الجداول: " . $e->getMessage() . "</div>";
    }
    
    function createSamplePosts($pdo) {
        try {
            $stmt = $pdo->query("SELECT id, username FROM users LIMIT 5");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($users) == 0) {
                echo "<div class='warning'>لا يوجد مستخدمون لإنشاء منشورات تجريبية</div>";
                return;
            }
            
            $samplePosts = [
                ["مرحباً بالجميع في المنصة الجديدة! أتمنى لكم تجربة ممتعة ومفيدة. 😊"],
                ["اليوم بدأت مشروعًا جديدًا في مجال البرمجة، متحمس جداً لمشاركة النتائج معكم قريباً! 💻"],
                ["هل تعلمون أن الذكاء الاصطناعي يمكن أن يغير العالم بشكل جذري خلال السنوات القادمة؟ ما رأيكم؟ 🤖"],
                ["قرأت مؤخراً كتاباً رائعاً عن تطوير الذات، أنصح الجميع بقراءته! 📚"],
                ["أفضل مطعم جربته هذا الأسبوع! الطعام لذيذ والخدمة ممتازة. أنصح الجميع بزيارته. 🍕"],
                ["سافرت مؤخراً إلى مدينة جديدة واكتشفت الكثير من الأماكن الرائعة. سأشارك الصور قريباً! 🏙️"],
                ["أبحث عن مطورين للعمل معي على مشروع جديد في مجال الويب. هل هناك مهتمون؟ 💼"],
                ["أفضل لغة برمجة للمبتدئين برأيكم؟ أنا أفكر في بدء رحلتي في عالم البرمجة 👨‍💻"],
                ["اليوم قررت بدء عادة القراءة يومياً. ما هي الكتب التي تنصحون بها؟ 📖"],
                ["كم أنا متحمس للمشاركة في هذه المنصة والتفاعل مع الجميع! 😍"]
            ];
            
            $postsCreated = 0;
            
            foreach ($users as $user) {
                $randomPosts = array_rand($samplePosts, min(2, count($samplePosts)));
                if (!is_array($randomPosts)) {
                    $randomPosts = [$randomPosts];
                }
                
                foreach ($randomPosts as $index) {
                    $content = $samplePosts[$index][0];
                    
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ? AND content = ?");
                    $stmt->execute([$user['id'], $content]);
                    $exists = $stmt->fetchColumn() > 0;
                    
                    if (!$exists) {
                        $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, created_at) VALUES (?, ?, ?)");
                        $randomDate = date('Y-m-d H:i:s', strtotime('-' . rand(1, 10) . ' days'));
                        $stmt->execute([$user['id'], $content, $randomDate]);
                        $postsCreated++;
                    }
                }
            }
            
            if ($postsCreated > 0) {
                echo "<div class='success'>تم إنشاء $postsCreated منشورات تجريبية بنجاح</div>";
            } else {
                echo "<div class='warning'>المنشورات التجريبية موجودة بالفعل</div>";
            }
        } catch (PDOException $e) {
            echo "<div class='error'>فشل إنشاء المنشورات التجريبية: " . $e->getMessage() . "</div>";
        }
    }
    
    function createFollowRelationships($pdo) {
        try {
            $stmt = $pdo->query("SELECT id, username FROM users LIMIT 10");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($users) < 2) {
                echo "<div class='warning'>عدد المستخدمين غير كافٍ لإنشاء علاقات متابعة</div>";
                return;
            }
            
            $followsCreated = 0;
            
            foreach ($users as $follower) {
                foreach ($users as $followed) {
                    if ($follower['id'] != $followed['id']) {
                        $stmt = $pdo->prepare("SELECT id FROM followers WHERE follower_id = ? AND followed_id = ?");
                        $stmt->execute([$follower['id'], $followed['id']]);
                        $exists = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if (!$exists && rand(1, 100) <= 70) {
                            $stmt = $pdo->prepare("INSERT INTO followers (follower_id, followed_id) VALUES (?, ?)");
                            $stmt->execute([$follower['id'], $followed['id']]);
                            $followsCreated++;
                        }
                    }
                }
            }
            
            if ($followsCreated > 0) {
                echo "<div class='success'>تم إنشاء $followsCreated علاقات متابعة بنجاح</div>";
            } else {
                echo "<div class='warning'>علاقات المتابعة موجودة بالفعل</div>";
            }
        } catch (PDOException $e) {
            echo "<div class='error'>فشل إنشاء علاقات المتابعة: " . $e->getMessage() . "</div>";
        }
    }
    
    if (file_exists(__DIR__ . '/config.php')) {
        $config_file = __DIR__ . '/config.php';
        $config_content = file_get_contents($config_file);
        
        if (strpos($config_content, 'JWT_SECRET_KEY') === false) {
            echo "<div class='warning'>لم يتم العثور على تعريف JWT_SECRET_KEY في ملف config.php</div>";
            echo "<div class='success'>يمكنك إضافة السطر التالي إلى ملف config.php:</div>";
            echo "<pre>define('JWT_SECRET_KEY', 'wep_secure_jwt_secret_key_2025');</pre>";
        } else {
            echo "<div class='success'>تم العثور على تعريف JWT_SECRET_KEY في ملف config.php</div>";
        }
    }
    
    echo "<div class='buttons'>";
    echo "<h2>روابط مفيدة للاختبار:</h2>";
    echo "<a href='/WEP/frontend/login.html' class='btn' target='_blank'>صفحة تسجيل الدخول</a>";
    echo "<a href='/WEP/frontend/register.html' class='btn' target='_blank'>صفحة التسجيل</a>";
    echo "<a href='/WEP/chat.php' class='btn' target='_blank'>صفحة الدردشة</a>";
    echo "<a href='/WEP/friends.php' class='btn' target='_blank'>صفحة الأصدقاء</a>";
    echo "</div>";
} catch (PDOException $e) {
    echo "<div class='error'>خطأ في قاعدة البيانات: " . $e->getMessage() . "</div>";
}

echo "</div>";
echo "</body>";
echo "</html>";
?>
