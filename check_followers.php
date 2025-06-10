<?php
require_once 'config.php';

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
    
    echo "فحص المتابعين للمستخدم رقم 5:\n\n";
    
    $stmt = $pdo->query("SELECT * FROM followers WHERE followed_id = 5");
    $followers = $stmt->fetchAll();
    
    echo "المتابعون:\n";
    print_r($followers);
    
    echo "\n\nعدد المتابعين: " . count($followers) . "\n";
    
    $stats_stmt = $pdo->prepare("
        SELECT 
            (SELECT COUNT(*) FROM posts WHERE user_id = ?) as posts_count,
            (SELECT COUNT(*) FROM followers WHERE followed_id = ?) as followers_count,
            (SELECT COUNT(*) FROM followers WHERE follower_id = ?) as following_count
    ");
    $stats_stmt->execute([5, 5, 5]);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "\nالإحصائيات من الاستعلام:\n";
    print_r($stats);
    
} catch (Exception $e) {
    echo "خطأ: " . $e->getMessage();
}
?> 