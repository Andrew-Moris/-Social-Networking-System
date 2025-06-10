<?php
require_once 'config.php';

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
    
    echo "=== فحص جدول المتابعين ===\n";
    
    $stmt = $pdo->query("SELECT * FROM followers ORDER BY id DESC LIMIT 10");
    $followers = $stmt->fetchAll();
    
    echo "عدد المتابعات الكلي: " . count($followers) . "\n\n";
    
    if (!empty($followers)) {
        echo "آخر 10 متابعات:\n";
        foreach ($followers as $follow) {
            echo "ID: {$follow['id']} | المتابع: {$follow['follower_id']} | المتابَع: {$follow['followed_id']}\n";
        }
    } else {
        echo "لا توجد متابعات في الجدول\n";
    }
    
    echo "\n=== إحصائيات المستخدم 5 ===\n";
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE followed_id = 5");
    $stmt->execute();
    $followers_count = $stmt->fetchColumn();
    echo "المتابعون: $followers_count\n";
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE follower_id = 5");
    $stmt->execute();
    $following_count = $stmt->fetchColumn();
    echo "يتابع: $following_count\n";
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = 5");
    $stmt->execute();
    $posts_count = $stmt->fetchColumn();
    echo "المنشورات: $posts_count\n";
    
} catch (Exception $e) {
    echo "خطأ: " . $e->getMessage() . "\n";
}
?> 