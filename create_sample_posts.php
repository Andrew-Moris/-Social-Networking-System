<?php

session_start();
require_once 'config.php';

echo "<h1>📝 إنشاء بوستات تجريبية</h1>";

if (!isset($_SESSION['user_id'])) {
    echo "<p style='color: red;'>❌ يجب تسجيل الدخول أولاً</p>";
    echo "<p><a href='login.php'>تسجيل الدخول</a></p>";
    exit;
}

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $current_user_id = $_SESSION['user_id'];
    
    $check_table = $pdo->query("SHOW TABLES LIKE 'posts'");
    if ($check_table->rowCount() === 0) {
        echo "<p>إنشاء جدول البوستات...</p>";
        $create_table = "
            CREATE TABLE posts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                content TEXT NOT NULL,
                image_url VARCHAR(500) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_created_at (created_at),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ";
        $pdo->exec($create_table);
        echo "<p>✅ تم إنشاء جدول البوستات</p>";
    }
    
    $count_stmt = $pdo->query("SELECT COUNT(*) FROM posts");
    $posts_count = $count_stmt->fetchColumn();
    
    echo "<p>عدد البوستات الحالية: {$posts_count}</p>";
    
    if ($posts_count < 5) {
        echo "<h2>إنشاء بوستات تجريبية...</h2>";
        
        $sample_posts = [
            "مرحباً بكم في موقعنا الجديد! 🎉",
            "يوم جميل للبرمجة والتطوير 💻",
            "أحب تعلم التقنيات الجديدة كل يوم 📚",
            "القهوة والكود - مزيج مثالي ☕️",
            "العمل الجماعي يحقق المعجزات 🤝",
            "التطوير المستمر هو مفتاح النجاح 🚀",
            "شكراً لكل من يدعم هذا المشروع ❤️",
            "البرمجة فن وعلم في نفس الوقت 🎨",
            "كل خطأ في الكود هو فرصة للتعلم 🐛",
            "المستقبل للذكاء الاصطناعي 🤖"
        ];
        
        $created_count = 0;
        
        foreach ($sample_posts as $content) {
            try {
                $stmt = $pdo->prepare("INSERT INTO posts (user_id, content) VALUES (?, ?)");
                $stmt->execute([$current_user_id, $content]);
                $created_count++;
                echo "<p>✅ تم إنشاء بوست: " . substr($content, 0, 30) . "...</p>";
            } catch (Exception $e) {
                echo "<p style='color: orange;'>⚠️ فشل في إنشاء بوست: " . $e->getMessage() . "</p>";
            }
        }
        
        echo "<h3>✅ تم إنشاء {$created_count} بوست جديد</h3>";
    } else {
        echo "<p>✅ يوجد بوستات كافية في النظام</p>";
    }
    
    echo "<h2>البوستات الحالية:</h2>";
    $posts_stmt = $pdo->prepare("
        SELECT p.*, u.username, u.first_name, u.last_name
        FROM posts p
        JOIN users u ON p.user_id = u.id
        ORDER BY p.created_at DESC
        LIMIT 10
    ");
    $posts_stmt->execute();
    $posts = $posts_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($posts) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'><th>ID</th><th>User</th><th>Content</th><th>Created</th></tr>";
        foreach ($posts as $post) {
            echo "<tr>";
            echo "<td>{$post['id']}</td>";
            echo "<td>{$post['username']} ({$post['first_name']} {$post['last_name']})</td>";
            echo "<td>" . substr($post['content'], 0, 100) . "...</td>";
            echo "<td>" . date('Y-m-d H:i', strtotime($post['created_at'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ لا توجد بوستات</p>";
    }
    
    echo "<h2>التحقق من المستخدمين:</h2>";
    $users_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "<p>عدد المستخدمين: {$users_count}</p>";
    
    if ($users_count < 3) {
        echo "<p>إنشاء مستخدمين تجريبيين...</p>";
        
        $sample_users = [
            ['ahmed_test', 'Ahmed', 'Ali', 'ahmed@test.com'],
            ['sara_test', 'Sara', 'Mohamed', 'sara@test.com'],
            ['omar_test', 'Omar', 'Hassan', 'omar@test.com']
        ];
        
        foreach ($sample_users as $user_data) {
            try {
                $check_user = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                $check_user->execute([$user_data[0], $user_data[3]]);
                
                if ($check_user->rowCount() === 0) {
                    $password_hash = password_hash('123456', PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("
                        INSERT INTO users (username, first_name, last_name, email, password_hash) 
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$user_data[0], $user_data[1], $user_data[2], $user_data[3], $password_hash]);
                    echo "<p>✅ تم إنشاء مستخدم: {$user_data[0]}</p>";
                } else {
                    echo "<p>⚠️ المستخدم {$user_data[0]} موجود بالفعل</p>";
                }
            } catch (Exception $e) {
                echo "<p style='color: red;'>❌ فشل في إنشاء مستخدم {$user_data[0]}: " . $e->getMessage() . "</p>";
            }
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ خطأ: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>🔗 روابط مفيدة:</h2>";
echo "<ul>";
echo "<li><a href='home.php'>🏠 الصفحة الرئيسية</a></li>";
echo "<li><a href='chat.php'>💬 المحادثة</a></li>";
echo "<li><a href='debug_posts_issue.php'>🔍 فحص البوستات</a></li>";
echo "<li><a href='debug_chat_issue.php'>🔍 فحص المحادثة</a></li>";
echo "</ul>";
?> 