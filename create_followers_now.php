<?php
require_once 'config.php';

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
    
    echo "🔧 إنشاء متابعين للمستخدم 5...\n\n";
    
    $users = $pdo->query("SELECT id, username FROM users")->fetchAll();
    echo "عدد المستخدمين: " . count($users) . "\n";
    
    foreach ($users as $user) {
        echo "- {$user['username']} (ID: {$user['id']})\n";
    }
    
    echo "\n";
    
    $pdo->exec("DELETE FROM followers");
    echo "✅ تم حذف المتابعات القديمة\n";
    
    $user5_followers = [1, 2, 3, 4]; 
    $user5_following = [1, 2, 6, 7];
    
    echo "\n📥 إضافة متابعين للمستخدم 5:\n";
    foreach ($user5_followers as $follower_id) {
        try {
            $stmt = $pdo->prepare("INSERT INTO followers (follower_id, followed_id, created_at) VALUES (?, 5, NOW())");
            $result = $stmt->execute([$follower_id]);
            if ($result) {
                echo "✅ المستخدم $follower_id يتابع الآن المستخدم 5\n";
            }
        } catch (Exception $e) {
            echo "❌ خطأ: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n📤 إضافة متابعات للمستخدم 5:\n";
    foreach ($user5_following as $followed_id) {
        if ($followed_id <= count($users)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO followers (follower_id, followed_id, created_at) VALUES (5, ?, NOW())");
                $result = $stmt->execute([$followed_id]);
                if ($result) {
                    echo "✅ المستخدم 5 يتابع الآن المستخدم $followed_id\n";
                }
            } catch (Exception $e) {
                echo "❌ خطأ: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n📊 النتائج النهائية:\n";
    
    $followers_count = $pdo->query("SELECT COUNT(*) FROM followers WHERE followed_id = 5")->fetchColumn();
    $following_count = $pdo->query("SELECT COUNT(*) FROM followers WHERE follower_id = 5")->fetchColumn();
    
    echo "المتابعون للمستخدم 5: $followers_count\n";
    echo "يتابع المستخدم 5: $following_count\n";
    
    if ($followers_count > 0) {
        echo "\nقائمة المتابعين:\n";
        $stmt = $pdo->query("
            SELECT f.follower_id, u.username 
            FROM followers f 
            JOIN users u ON f.follower_id = u.id 
            WHERE f.followed_id = 5
        ");
        while ($row = $stmt->fetch()) {
            echo "- {$row['username']} (ID: {$row['follower_id']})\n";
        }
    }
    
    echo "\n✨ تم الانتهاء!\n";
    
} catch (Exception $e) {
    echo "❌ خطأ: " . $e->getMessage() . "\n";
}
?> 