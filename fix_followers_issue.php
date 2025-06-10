<?php
session_start();
require_once 'config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 إصلاح مشكلة المتابعين</h1>";
echo "<pre style='background: #f0f0f0; padding: 20px; border-radius: 5px; direction: rtl;'>";

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
    
    $users_stmt = $pdo->query("SELECT id, username FROM users");
    $users = $users_stmt->fetchAll();
    
    echo "📊 عدد المستخدمين الكلي: " . count($users) . "\n\n";
    
    if (count($users) < 2) {
        echo "❌ يجب أن يكون هناك مستخدمين على الأقل لإنشاء متابعات\n";
        exit;
    }
    
    echo "🗑️ حذف المتابعات القديمة...\n";
    $pdo->exec("DELETE FROM followers");
    echo "✅ تم حذف جميع المتابعات القديمة\n\n";
    
    echo "👥 إنشاء متابعات جديدة...\n";
    $follow_count = 0;
    
    foreach ($users as $follower) {
        $others = array_filter($users, function($u) use ($follower) {
            return $u['id'] != $follower['id'];
        });
        
        shuffle($others);
        $to_follow = array_slice($others, 0, rand(2, min(4, count($others))));
        
        foreach ($to_follow as $followed) {
            try {
                $stmt = $pdo->prepare("INSERT INTO followers (follower_id, followed_id, created_at) VALUES (?, ?, NOW())");
                $stmt->execute([$follower['id'], $followed['id']]);
                $follow_count++;
                echo "✅ {$follower['username']} يتابع الآن {$followed['username']}\n";
            } catch (Exception $e) {
                echo "⚠️ خطأ في إنشاء متابعة: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n✅ تم إنشاء {$follow_count} متابعة جديدة\n\n";
    
    echo "📊 إحصائيات المتابعين لكل مستخدم:\n";
    echo str_repeat("-", 60) . "\n";
    
    foreach ($users as $user) {
        $followers_stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE followed_id = ?");
        $followers_stmt->execute([$user['id']]);
        $followers_count = $followers_stmt->fetchColumn();
        
        $following_stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE follower_id = ?");
        $following_stmt->execute([$user['id']]);
        $following_count = $following_stmt->fetchColumn();
        
        echo sprintf("%-20s | المتابعون: %-3d | يتابع: %-3d\n", 
            $user['username'], 
            $followers_count, 
            $following_count
        );
    }
    
    echo str_repeat("-", 60) . "\n\n";
    
    if (isset($_SESSION['user_id'])) {
        $current_user_id = $_SESSION['user_id'];
        echo "🔍 إحصائيات المستخدم الحالي (ID: {$current_user_id}):\n";
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE followed_id = ?");
        $stmt->execute([$current_user_id]);
        $my_followers = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE follower_id = ?");
        $stmt->execute([$current_user_id]);
        $my_following = $stmt->fetchColumn();
        
        echo "   - المتابعون: {$my_followers}\n";
        echo "   - يتابع: {$my_following}\n\n";
        
        if ($my_followers > 0) {
            echo "👥 قائمة المتابعين:\n";
            $stmt = $pdo->prepare("
                SELECT u.username, u.first_name, u.last_name 
                FROM followers f 
                JOIN users u ON f.follower_id = u.id 
                WHERE f.followed_id = ?
            ");
            $stmt->execute([$current_user_id]);
            $followers_list = $stmt->fetchAll();
            
            foreach ($followers_list as $follower) {
                $name = trim($follower['first_name'] . ' ' . $follower['last_name']) ?: $follower['username'];
                echo "   - {$name} (@{$follower['username']})\n";
            }
        }
    }
    
    echo "\n✨ تم إصلاح مشكلة المتابعين بنجاح!\n";
    
} catch (PDOException $e) {
    echo "❌ خطأ في قاعدة البيانات: " . $e->getMessage() . "\n";
}

echo "</pre>";

echo '<div style="margin: 20px; text-align: center;">';
echo '<a href="u.php" style="display: inline-block; padding: 10px 30px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; font-size: 18px; margin: 5px;">الملف الشخصي</a>';
echo '<a href="home.php" style="display: inline-block; padding: 10px 30px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; font-size: 18px; margin: 5px;">الصفحة الرئيسية</a>';
echo '<a href="discover.php" style="display: inline-block; padding: 10px 30px; background: #17a2b8; color: white; text-decoration: none; border-radius: 5px; font-size: 18px; margin: 5px;">اكتشف</a>';
echo '</div>';
?> 