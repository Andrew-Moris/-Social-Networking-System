<?php
require_once 'config.php';

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
    
    echo "🔧 فحص وإصلاح جدول المتابعين...\n\n";
    
    echo "📋 بنية الجدول الحالية:\n";
    $stmt = $pdo->query("DESCRIBE followers");
    $columns = $stmt->fetchAll();
    
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']}) - {$column['Null']} - {$column['Default']}\n";
    }
    
    echo "\n";
    
    echo "🗑️ حذف الجدول القديم...\n";
    $pdo->exec("DROP TABLE IF EXISTS followers");
    
    echo "🔨 إنشاء جدول جديد...\n";
    $create_table = "
        CREATE TABLE followers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            follower_id INT NOT NULL,
            followed_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_follow (follower_id, followed_id),
            FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (followed_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $pdo->exec($create_table);
    echo "✅ تم إنشاء الجدول بنجاح\n\n";
    
    echo "📥 إضافة بيانات تجريبية...\n";
    
    $followers_data = [
        [1, 5], 
        [2, 5], 
        [3, 5], 
        [4, 5], 
        [5, 1], 
        [5, 2],
        [5, 6], 
        [5, 7], 
    ];
    
    $stmt = $pdo->prepare("INSERT INTO followers (follower_id, followed_id) VALUES (?, ?)");
    
    foreach ($followers_data as $follow) {
        try {
            $stmt->execute($follow);
            echo "✅ المستخدم {$follow[0]} يتابع المستخدم {$follow[1]}\n";
        } catch (Exception $e) {
            echo "❌ خطأ: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n📊 النتائج النهائية:\n";
    
    $followers_count = $pdo->query("SELECT COUNT(*) FROM followers WHERE followed_id = 5")->fetchColumn();
    $following_count = $pdo->query("SELECT COUNT(*) FROM followers WHERE follower_id = 5")->fetchColumn();
    
    echo "المتابعون للمستخدم 5: $followers_count\n";
    echo "يتابع المستخدم 5: $following_count\n";
    
    if ($followers_count > 0) {
        echo "\nقائمة المتابعين للمستخدم 5:\n";
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
    
    if ($following_count > 0) {
        echo "\nقائمة من يتابعهم المستخدم 5:\n";
        $stmt = $pdo->query("
            SELECT f.followed_id, u.username 
            FROM followers f 
            JOIN users u ON f.followed_id = u.id 
            WHERE f.follower_id = 5
        ");
        while ($row = $stmt->fetch()) {
            echo "- {$row['username']} (ID: {$row['followed_id']})\n";
        }
    }
    
    echo "\n✨ تم إصلاح جدول المتابعين بنجاح!\n";
    echo "🔗 يمكنك الآن الذهاب إلى: http://localhost/WEP/u.php\n";
    
} catch (Exception $e) {
    echo "❌ خطأ: " . $e->getMessage() . "\n";
}
?> 