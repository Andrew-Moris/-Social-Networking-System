<?php
require_once 'config.php';

echo "🔧 إصلاح بيانات المستخدم 11...\n\n";

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = 11");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "❌ المستخدم 11 غير موجود\n";
        exit;
    }
    
    echo "✅ المستخدم موجود: {$user['username']}\n";
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = 11");
    $stmt->execute();
    $posts_count = $stmt->fetchColumn();
    
    echo "📝 عدد المنشورات الحالي: $posts_count\n";
    
    if ($posts_count == 0) {
        echo "💡 إنشاء منشورات تجريبية...\n";
        
        $sample_posts = [
            "مرحباً! هذا أول منشور لي 🎉",
            "أحب البرمجة والتطوير 💻",
            "يوم جميل للتعلم والإبداع ✨",
            "شاركوني أفكاركم وتجاربكم 💭",
            "الحياة جميلة مع الأصدقاء ❤️"
        ];
        
        foreach ($sample_posts as $content) {
            $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, created_at) VALUES (?, ?, ?)");
            $created_at = date('Y-m-d H:i:s', strtotime("-" . rand(1, 10) . " hours"));
            $stmt->execute([11, $content, $created_at]);
        }
        
        echo "✅ تم إنشاء " . count($sample_posts) . " منشورات\n";
    }
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE followed_id = 11");
    $stmt->execute();
    $followers_count = $stmt->fetchColumn();
    
    echo "👥 عدد المتابعين الحالي: $followers_count\n";
    
    if ($followers_count == 0) {
        echo "💡 إنشاء متابعين تجريبيين...\n";
        
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id != 11 LIMIT 3");
        $stmt->execute();
        $other_users = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($other_users)) {
            foreach ($other_users as $follower_id) {
                try {
                    $stmt = $pdo->prepare("INSERT IGNORE INTO followers (follower_id, followed_id) VALUES (?, ?)");
                    $stmt->execute([$follower_id, 11]);
                } catch (Exception $e) {
                }
            }
            echo "✅ تم إنشاء " . count($other_users) . " متابعين\n";
        }
    }
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE follower_id = 11");
    $stmt->execute();
    $following_count = $stmt->fetchColumn();
    
    echo "➡️ عدد المتابَعين الحالي: $following_count\n";
    
    if ($following_count == 0) {
        echo "💡 إنشاء متابعات تجريبية...\n";
        
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id != 11 LIMIT 2");
        $stmt->execute();
        $other_users = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($other_users)) {
            foreach ($other_users as $followed_id) {
                try {
                    $stmt = $pdo->prepare("INSERT IGNORE INTO followers (follower_id, followed_id) VALUES (?, ?)");
                    $stmt->execute([11, $followed_id]);
                } catch (Exception $e) {
                }
            }
            echo "✅ تم إنشاء " . count($other_users) . " متابعات\n";
        }
    }
    
    echo "\n📊 الإحصائيات النهائية:\n";
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = 11");
    $stmt->execute();
    $final_posts = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE followed_id = 11");
    $stmt->execute();
    $final_followers = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE follower_id = 11");
    $stmt->execute();
    $final_following = $stmt->fetchColumn();
    
    echo "📝 المنشورات: $final_posts\n";
    echo "👥 المتابعون: $final_followers\n";
    echo "➡️ المتابَعون: $final_following\n";
    
    echo "\n🎉 تم إصلاح البيانات بنجاح!\n";
    echo "يمكنك الآن زيارة u.php لرؤية النتائج\n";
    
} catch (Exception $e) {
    echo "❌ خطأ: " . $e->getMessage() . "\n";
}
?> 