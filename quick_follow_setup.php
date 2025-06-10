<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "❌ يجب تسجيل الدخول أولاً!";
    exit;
}

$current_user_id = $_SESSION['user_id'];

echo "<h1>إعداد سريع لعلاقات المتابعة</h1>";

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        if ($_POST['action'] === 'follow_all_test_users') {
            $test_usernames = ['john_doe', 'jane_smith', 'mike_wilson', 'sarah_jones', 'alex_brown'];
            $followed_count = 0;
            
            foreach ($test_usernames as $username) {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user && $user['id'] != $current_user_id) {
                    $stmt = $pdo->prepare("SELECT 1 FROM followers WHERE follower_id = ? AND followed_id = ?");
                    $stmt->execute([$current_user_id, $user['id']]);
                    
                    if (!$stmt->fetch()) {
                        $stmt = $pdo->prepare("INSERT INTO followers (follower_id, followed_id) VALUES (?, ?)");
                        $stmt->execute([$current_user_id, $user['id']]);
                        $followed_count++;
                        echo "<p style='color: green;'>✓ تم متابعة $username</p>";
                    } else {
                        echo "<p style='color: orange;'>⚠️ تتابع $username بالفعل</p>";
                    }
                }
            }
            
            echo "<p style='color: blue; font-weight: bold;'>تم متابعة $followed_count مستخدمين جدد!</p>";
            echo "<p><a href='chat.php' style='color: green; font-weight: bold;'>→ اذهب للدردشة الآن</a></p>";
        }
        
        if ($_POST['action'] === 'create_test_users_and_follow') {
            $test_users = [
                ['username' => 'chat_test1', 'first_name' => 'Test', 'last_name' => 'User 1', 'email' => 'test1@example.com'],
                ['username' => 'chat_test2', 'first_name' => 'Test', 'last_name' => 'User 2', 'email' => 'test2@example.com'],
                ['username' => 'chat_test3', 'first_name' => 'Test', 'last_name' => 'User 3', 'email' => 'test3@example.com']
            ];
            
            $password = password_hash('password123', PASSWORD_DEFAULT);
            $created_count = 0;
            $followed_count = 0;
            
            foreach ($test_users as $user_data) {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$user_data['username']]);
                $existing_user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $user_id = null;
                
                if (!$existing_user) {
                    $stmt = $pdo->prepare("
                        INSERT INTO users (username, first_name, last_name, email, password, created_at) 
                        VALUES (?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([
                        $user_data['username'],
                        $user_data['first_name'],
                        $user_data['last_name'],
                        $user_data['email'],
                        $password
                    ]);
                    $user_id = $pdo->lastInsertId();
                    $created_count++;
                    echo "<p style='color: green;'>✓ تم إنشاء المستخدم {$user_data['username']}</p>";
                } else {
                    $user_id = $existing_user['id'];
                    echo "<p style='color: orange;'>⚠️ المستخدم {$user_data['username']} موجود بالفعل</p>";
                }
                
                if ($user_id && $user_id != $current_user_id) {
                    $stmt = $pdo->prepare("SELECT 1 FROM followers WHERE follower_id = ? AND followed_id = ?");
                    $stmt->execute([$current_user_id, $user_id]);
                    
                    if (!$stmt->fetch()) {
                        $stmt = $pdo->prepare("INSERT INTO followers (follower_id, followed_id) VALUES (?, ?)");
                        $stmt->execute([$current_user_id, $user_id]);
                        $followed_count++;
                        echo "<p style='color: blue;'>✓ تم متابعة {$user_data['username']}</p>";
                    }
                }
            }
            
            echo "<p style='color: blue; font-weight: bold;'>تم إنشاء $created_count مستخدمين جدد ومتابعة $followed_count مستخدمين!</p>";
            echo "<p><a href='chat.php' style='color: green; font-weight: bold;'>→ اذهب للدردشة الآن</a></p>";
        }
    }
    
    echo "<h2>الحالة الحالية</h2>";
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$current_user_id]);
    $current_user = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p><strong>المستخدم الحالي:</strong> {$current_user['username']}</p>";
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as following_count
        FROM followers 
        WHERE follower_id = ?
    ");
    $stmt->execute([$current_user_id]);
    $following_count = $stmt->fetchColumn();
    echo "<p><strong>تتابع:</strong> $following_count مستخدمين</p>";
    
    echo "<h2>المستخدمين المتاحين للمتابعة</h2>";
    $stmt = $pdo->prepare("
        SELECT u.*, 
               CASE WHEN f.follower_id IS NOT NULL THEN 1 ELSE 0 END as is_following
        FROM users u
        LEFT JOIN followers f ON u.id = f.followed_id AND f.follower_id = ?
        WHERE u.id != ?
        ORDER BY u.username
    ");
    $stmt->execute([$current_user_id, $current_user_id]);
    $all_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($all_users)) {
        echo "<p style='color: red;'>❌ لا توجد مستخدمين آخرين في النظام!</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>المستخدم</th><th>الاسم</th><th>الحالة</th></tr>";
        foreach ($all_users as $user) {
            $status = $user['is_following'] ? '<span style="color: green;">✓ تتابعه</span>' : '<span style="color: orange;">لا تتابعه</span>';
            echo "<tr>";
            echo "<td>{$user['username']}</td>";
            echo "<td>{$user['first_name']} {$user['last_name']}</td>";
            echo "<td>$status</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h2>إجراءات سريعة</h2>";
    echo "<div style='margin: 20px 0;'>";
    
    if ($following_count == 0) {
        echo "<form method='POST' style='display: inline-block; margin: 5px;'>";
        echo "<input type='hidden' name='action' value='follow_all_test_users'>";
        echo "<button type='submit' style='background: #10b981; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer;'>متابعة جميع المستخدمين التجريبيين</button>";
        echo "</form>";
        
        echo "<form method='POST' style='display: inline-block; margin: 5px;'>";
        echo "<input type='hidden' name='action' value='create_test_users_and_follow'>";
        echo "<button type='submit' style='background: #3b82f6; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer;'>إنشاء مستخدمين تجريبيين ومتابعتهم</button>";
        echo "</form>";
    }
    
    echo "<a href='chat.php' style='display: inline-block; background: #667eea; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>فتح الدردشة</a>";
    echo "<a href='test_chat_simple.php' style='display: inline-block; background: #f59e0b; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>اختبار الدردشة</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>خطأ: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
h1, h2 { color: #333; }
table { background: white; margin: 10px 0; border-radius: 5px; }
th, td { padding: 10px; text-align: left; }
th { background: #f0f0f0; }
button:hover { opacity: 0.9; transform: translateY(-1px); }
</style> 