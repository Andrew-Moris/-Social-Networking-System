<?php

require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    die("❌ يجب تسجيل الدخول أولاً!");
}

$current_user_id = $_SESSION['user_id'];

echo "<h1>🚀 إعداد سريع للمتابعات والدردشة</h1>";

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
    
    $test_users = [
        [
            'username' => 'ahmad_2024',
            'first_name' => 'أحمد',
            'last_name' => 'محمد',
            'email' => 'ahmad@example.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT)
        ],
        [
            'username' => 'fatima_salem',
            'first_name' => 'فاطمة',
            'last_name' => 'سالم',
            'email' => 'fatima@example.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT)
        ],
        [
            'username' => 'omar_ali',
            'first_name' => 'عمر',
            'last_name' => 'علي',
            'email' => 'omar@example.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT)
        ],
        [
            'username' => 'sara_hassan',
            'first_name' => 'سارة',
            'last_name' => 'حسان',
            'email' => 'sara@example.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT)
        ]
    ];
    
    echo "<h2>1. إنشاء المستخدمين التجريبيين</h2>";
    
    foreach ($test_users as $user_data) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$user_data['username'], $user_data['email']]);
        
        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare("
                INSERT INTO users (username, first_name, last_name, email, password, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $user_data['username'],
                $user_data['first_name'],
                $user_data['last_name'],
                $user_data['email'],
                $user_data['password']
            ]);
            echo "<p style='color: green;'>✅ تم إنشاء المستخدم: <strong>{$user_data['first_name']} {$user_data['last_name']}</strong> (@{$user_data['username']})</p>";
        } else {
            echo "<p style='color: blue;'>ℹ️ المستخدم موجود بالفعل: <strong>{$user_data['first_name']} {$user_data['last_name']}</strong> (@{$user_data['username']})</p>";
        }
    }
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS followers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            follower_id INT NOT NULL,
            followed_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (followed_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_follow (follower_id, followed_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    echo "<h2>2. إعداد المتابعات التلقائية</h2>";
    
    $stmt = $pdo->prepare("SELECT id, username, first_name, last_name FROM users WHERE id != ?");
    $stmt->execute([$current_user_id]);
    $other_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($other_users as $user) {
        $stmt = $pdo->prepare("SELECT id FROM followers WHERE follower_id = ? AND followed_id = ?");
        $stmt->execute([$current_user_id, $user['id']]);
        
        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare("
                INSERT INTO followers (follower_id, followed_id, created_at) 
                VALUES (?, ?, NOW())
            ");
            $stmt->execute([$current_user_id, $user['id']]);
            echo "<p style='color: green;'>✅ أصبحت تتابع: <strong>{$user['first_name']} {$user['last_name']}</strong> (@{$user['username']})</p>";
        } else {
            echo "<p style='color: blue;'>ℹ️ تتابع بالفعل: <strong>{$user['first_name']} {$user['last_name']}</strong> (@{$user['username']})</p>";
        }
    }
    
    echo "<h2>3. إنشاء متابعات متبادلة</h2>";
    $follow_back_users = array_slice($other_users, 0, 2);
    
    foreach ($follow_back_users as $user) {
        $stmt = $pdo->prepare("SELECT id FROM followers WHERE follower_id = ? AND followed_id = ?");
        $stmt->execute([$user['id'], $current_user_id]);
        
        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare("
                INSERT INTO followers (follower_id, followed_id, created_at) 
                VALUES (?, ?, NOW())
            ");
            $stmt->execute([$user['id'], $current_user_id]);
            echo "<p style='color: green;'>✅ <strong>{$user['first_name']} {$user['last_name']}</strong> أصبح يتابعك أيضاً!</p>";
        } else {
            echo "<p style='color: blue;'>ℹ️ <strong>{$user['first_name']} {$user['last_name']}</strong> يتابعك بالفعل</p>";
        }
    }
    
    echo "<h2>4. إحصائيات نهائية</h2>";
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE follower_id = ?");
    $stmt->execute([$current_user_id]);
    $following_count = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE followed_id = ?");
    $stmt->execute([$current_user_id]);
    $followers_count = $stmt->fetchColumn();
    
    echo "<div style='background: #f0f8f0; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<p><strong>👥 تتابع:</strong> $following_count مستخدمين</p>";
    echo "<p><strong>👥 يتابعك:</strong> $followers_count مستخدمين</p>";
    echo "<p><strong>📱 إجمالي المستخدمين:</strong> " . count($other_users) + 1 . " مستخدمين</p>";
    echo "</div>";
    
    if ($following_count > 0) {
        echo "<h2>✅ تم الإعداد بنجاح!</h2>";
        echo "<p style='color: green; font-size: 1.2em; font-weight: bold;'>🎉 الآن يمكنك استخدام الدردشة بنجاح!</p>";
        
        echo "<div style='margin: 30px 0; text-align: center;'>";
        echo "<a href='chat_simple.php' style='background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 15px 30px; text-decoration: none; border-radius: 10px; font-weight: bold; margin: 10px; display: inline-block;'>💬 اذهب للدردشة</a>";
        echo "<a href='friends.php' style='background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: white; padding: 15px 30px; text-decoration: none; border-radius: 10px; font-weight: bold; margin: 10px; display: inline-block;'>👥 صفحة الأصدقاء</a>";
        echo "<a href='debug_chat_issue.php' style='background: linear-gradient(135deg, #6366f1, #4f46e5); color: white; padding: 15px 30px; text-decoration: none; border-radius: 10px; font-weight: bold; margin: 10px; display: inline-block;'>🔍 تشخيص الدردشة</a>";
        echo "</div>";
    } else {
        echo "<p style='color: red;'>❌ لم يتم إنشاء أي متابعات. تحقق من وجود مستخدمين آخرين.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>خطأ: " . $e->getMessage() . "</p>";
}
?>

<style>
body { 
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
    margin: 20px; 
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 40px;
}
h1, h2 { color: white; margin: 20px 0; }
p { margin: 10px 0; }
a { color: white; }
div { margin: 20px 0; }
</style> 