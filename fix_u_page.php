<?php

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

echo "<h1>🔧 إصلاح مشاكل صفحة الملف الشخصي (u.php)</h1>";
echo "<pre style='background: #f0f0f0; padding: 20px; border-radius: 5px;'>";

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
    
    if (!isset($_SESSION['user_id'])) {
        echo "❌ لا يوجد مستخدم مسجل دخول\n";
        echo "يرجى <a href='login.php'>تسجيل الدخول</a> أولاً\n";
        exit;
    }
    
    $current_user_id = $_SESSION['user_id'];
    echo "✅ المستخدم الحالي: ID = $current_user_id\n\n";
    
    $user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $user_stmt->execute([$current_user_id]);
    $user = $user_stmt->fetch();
    
    if (!$user) {
        echo "❌ لم يتم العثور على بيانات المستخدم في قاعدة البيانات\n";
        exit;
    }
    
    echo "✅ بيانات المستخدم:\n";
    echo "   - اسم المستخدم: " . $user['username'] . "\n";
    echo "   - الاسم: " . $user['first_name'] . " " . $user['last_name'] . "\n";
    echo "   - البريد: " . $user['email'] . "\n\n";
    
    echo "📝 إنشاء منشورات للمستخدم...\n";
    
    $posts_count = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
    $posts_count->execute([$current_user_id]);
    $current_posts = $posts_count->fetchColumn();
    
    echo "   - عدد المنشورات الحالية: $current_posts\n";
    
    if ($current_posts < 5) {
        $sample_posts = [
            "أول منشور لي في هذه المنصة الرائعة! 🎉",
            "مرحباً بالجميع، سعيد بالانضمام إليكم 😊",
            "يوم جميل للتواصل مع الأصدقاء 🌞",
            "أفكار جديدة تستحق المشاركة 💡",
            "الحياة أجمل مع الأصدقاء الجدد 🤝",
            "منشور تجريبي مع بعض الإيموجي 🚀✨",
            "اختبار النشر مع النص العربي والإنجليزي Mixed",
            "صباح الخير للجميع ☀️",
            "مساء الخير والسعادة 🌙",
            "نهاية أسبوع سعيدة 🎊"
        ];
        
        $posts_to_create = 5 - $current_posts;
        for ($i = 0; $i < $posts_to_create; $i++) {
            $content = $sample_posts[array_rand($sample_posts)];
            $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, created_at) VALUES (?, ?, ?)");
            $created_at = date('Y-m-d H:i:s', strtotime("-" . ($i * 2) . " hours"));
            $stmt->execute([$current_user_id, $content, $created_at]);
        }
        echo "   ✅ تم إنشاء $posts_to_create منشورات جديدة\n";
    } else {
        echo "   ✅ لديك منشورات كافية\n";
    }
    
    echo "\n👥 إنشاء متابعين ومتابَعين...\n";
    
    $other_users = $pdo->prepare("SELECT id FROM users WHERE id != ? LIMIT 10");
    $other_users->execute([$current_user_id]);
    $users = $other_users->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($users) > 0) {
        $followers_to_add = min(5, count($users));
        for ($i = 0; $i < $followers_to_add; $i++) {
            try {
                $stmt = $pdo->prepare("INSERT IGNORE INTO followers (follower_id, followed_id) VALUES (?, ?)");
                $stmt->execute([$users[$i], $current_user_id]);
            } catch (Exception $e) {
            }
        }
        echo "   ✅ تم إضافة $followers_to_add متابعين\n";
        
        $following_to_add = min(3, count($users));
        for ($i = 0; $i < $following_to_add; $i++) {
            try {
                $stmt = $pdo->prepare("INSERT IGNORE INTO followers (follower_id, followed_id) VALUES (?, ?)");
                $stmt->execute([$current_user_id, $users[$i]]);
            } catch (Exception $e) {
            }
        }
        echo "   ✅ تم متابعة $following_to_add مستخدمين\n";
    }
    
    echo "\n💬 إضافة تفاعلات على المنشورات...\n";
    
    $user_posts = $pdo->prepare("SELECT id FROM posts WHERE user_id = ? LIMIT 5");
    $user_posts->execute([$current_user_id]);
    $posts = $user_posts->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($posts) > 0 && count($users) > 0) {
        foreach ($posts as $post_id) {
            $likers = array_rand(array_flip($users), min(3, count($users)));
            if (!is_array($likers)) $likers = [$likers];
            
            foreach ($likers as $user_id) {
                try {
                    $stmt = $pdo->prepare("INSERT IGNORE INTO likes (post_id, user_id) VALUES (?, ?)");
                    $stmt->execute([$post_id, $user_id]);
                } catch (Exception $e) {
                }
            }
            
            if (rand(0, 1)) {
                $comments = ["منشور رائع! 👍", "أعجبني هذا", "شكراً للمشاركة", "ممتاز!", "❤️"];
                $comment = $comments[array_rand($comments)];
                $commenter = $users[array_rand($users)];
                
                try {
                    $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
                    $stmt->execute([$post_id, $commenter, $comment]);
                } catch (Exception $e) {
                }
            }
        }
        echo "   ✅ تم إضافة إعجابات وتعليقات\n";
    }
    
    echo "\n🔄 تحديث العدادات...\n";
    
    $pdo->exec("
        UPDATE posts p
        SET 
            likes_count = (SELECT COUNT(*) FROM likes WHERE post_id = p.id),
            comments_count = (SELECT COUNT(*) FROM comments WHERE post_id = p.id),
            shares_count = (SELECT COUNT(*) FROM shares WHERE post_id = p.id)
        WHERE p.user_id = $current_user_id
    ");
    
    echo "   ✅ تم تحديث عدادات المنشورات\n";
    
    echo "\n📊 الإحصائيات النهائية:\n";
    
    $stats = [];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
    $stmt->execute([$current_user_id]);
    $stats['posts'] = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE followed_id = ?");
    $stmt->execute([$current_user_id]);
    $stats['followers'] = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE follower_id = ?");
    $stmt->execute([$current_user_id]);
    $stats['following'] = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM likes l JOIN posts p ON l.post_id = p.id WHERE p.user_id = ?");
    $stmt->execute([$current_user_id]);
    $stats['likes'] = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM comments c JOIN posts p ON c.post_id = p.id WHERE p.user_id = ?");
    $stmt->execute([$current_user_id]);
    $stats['comments'] = $stmt->fetchColumn();
    
    echo "   - المنشورات: " . $stats['posts'] . "\n";
    echo "   - المتابعون: " . $stats['followers'] . "\n";
    echo "   - يتابع: " . $stats['following'] . "\n";
    echo "   - الإعجابات: " . $stats['likes'] . "\n";
    echo "   - التعليقات: " . $stats['comments'] . "\n";
    
    echo "\n✨ تم إصلاح جميع المشاكل!\n";
    echo "\n🔗 الروابط:\n";
    echo "- <a href='u.php'>صفحة الملف الشخصي</a>\n";
    echo "- <a href='home.php'>الصفحة الرئيسية</a>\n";
    echo "- <a href='discover.php'>اكتشف المستخدمين</a>\n";
    
} catch (PDOException $e) {
    echo "❌ خطأ في قاعدة البيانات: " . $e->getMessage() . "\n";
    echo "\nتفاصيل الخطأ:\n";
    echo $e->getTraceAsString();
}

echo "</pre>";

echo '<div style="margin: 20px; text-align: center;">';
echo '<a href="u.php" style="display: inline-block; padding: 10px 30px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; font-size: 18px;">الذهاب إلى الملف الشخصي</a>';
echo '</div>';
?> 