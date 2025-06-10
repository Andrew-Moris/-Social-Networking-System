<?php

session_start();
require_once 'config.php';

echo "<h1>💬 فحص مشكلة المحادثة</h1>";

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>1. التحقق من جدول الرسائل:</h2>";
    $check_messages_table = $pdo->query("SHOW TABLES LIKE 'messages'");
    if ($check_messages_table->rowCount() > 0) {
        echo "✅ جدول الرسائل موجود<br>";
        
        $describe = $pdo->query("DESCRIBE messages");
        echo "<h3>هيكل جدول الرسائل:</h3>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($row = $describe->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>{$row['Field']}</td>";
            echo "<td>{$row['Type']}</td>";
            echo "<td>{$row['Null']}</td>";
            echo "<td>{$row['Key']}</td>";
            echo "<td>{$row['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        $count_stmt = $pdo->query("SELECT COUNT(*) FROM messages");
        $messages_count = $count_stmt->fetchColumn();
        echo "<p><strong>عدد الرسائل الكلي:</strong> {$messages_count}</p>";
        
        if ($messages_count > 0) {
            echo "<h3>أحدث 5 رسائل:</h3>";
            $messages_stmt = $pdo->query("
                SELECT m.*, 
                       u1.username as sender_username,
                       u2.username as receiver_username
                FROM messages m
                LEFT JOIN users u1 ON m.sender_id = u1.id
                LEFT JOIN users u2 ON m.receiver_id = u2.id
                ORDER BY m.created_at DESC 
                LIMIT 5
            ");
            $messages = $messages_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; width: 100%;'>";
            echo "<tr><th>ID</th><th>From</th><th>To</th><th>Content</th><th>Created</th></tr>";
            foreach ($messages as $message) {
                echo "<tr>";
                echo "<td>{$message['id']}</td>";
                echo "<td>{$message['sender_username']}</td>";
                echo "<td>{$message['receiver_username']}</td>";
                echo "<td>" . substr($message['content'], 0, 50) . "...</td>";
                echo "<td>{$message['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<p style='color: red;'>❌ جدول الرسائل غير موجود</p>";
        echo "<p>سيتم إنشاؤه تلقائياً عند إرسال أول رسالة</p>";
    }
    
    echo "<h2>2. التحقق من المستخدم الحالي:</h2>";
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        echo "<p>✅ المستخدم مسجل دخول - ID: {$user_id}</p>";
        
        $user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $user_stmt->execute([$user_id]);
        $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "<p>✅ بيانات المستخدم: {$user['username']} ({$user['first_name']} {$user['last_name']})</p>";
        } else {
            echo "<p style='color: red;'>❌ لا يمكن العثور على بيانات المستخدم</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ المستخدم غير مسجل دخول</p>";
    }
    
    echo "<h2>3. المستخدمين المتاحين للمحادثة:</h2>";
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        
        $users_stmt = $pdo->prepare("SELECT id, username, first_name, last_name FROM users WHERE id != ? LIMIT 10");
        $users_stmt->execute([$user_id]);
        $users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($users) > 0) {
            echo "<p>✅ يوجد " . count($users) . " مستخدمين متاحين للمحادثة</p>";
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr><th>ID</th><th>Username</th><th>Name</th><th>Test Chat</th></tr>";
            foreach ($users as $user) {
                echo "<tr>";
                echo "<td>{$user['id']}</td>";
                echo "<td>{$user['username']}</td>";
                echo "<td>{$user['first_name']} {$user['last_name']}</td>";
                echo "<td><a href='chat.php?user={$user['id']}' target='_blank'>فتح محادثة</a></td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: red;'>❌ لا يوجد مستخدمين آخرين للمحادثة معهم</p>";
        }
    }
    
    echo "<h2>4. اختبار API المحادثة:</h2>";
    
    if (file_exists('api/chat.php')) {
        echo "<p>✅ ملف API المحادثة موجود</p>";
        
        if (isset($_SESSION['user_id'])) {
            echo "<p>يمكنك اختبار API المحادثة من خلال:</p>";
            echo "<ul>";
            echo "<li><a href='api/chat.php?action=get_conversations' target='_blank'>عرض المحادثات</a></li>";
            if (!empty($users)) {
                $test_user = $users[0];
                echo "<li><a href='api/chat.php?action=get_messages&user_id={$test_user['id']}' target='_blank'>عرض رسائل مع {$test_user['username']}</a></li>";
            }
            echo "</ul>";
        }
    } else {
        echo "<p style='color: red;'>❌ ملف API المحادثة غير موجود</p>";
    }
    
    echo "<h2>5. اختبار صفحة المحادثة:</h2>";
    if (file_exists('chat.php')) {
        echo "<p>✅ صفحة المحادثة موجودة</p>";
        echo "<p><a href='chat.php' target='_blank'>فتح صفحة المحادثة</a></p>";
    } else {
        echo "<p style='color: red;'>❌ صفحة المحادثة غير موجودة</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ خطأ: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>🔧 الحلول المقترحة لمشكلة المحادثة:</h2>";
echo "<ol>";
echo "<li><strong>إذا لم تظهر المحادثة عند الضغط على المستخدم:</strong>";
echo "<ul>";
echo "<li>تحقق من وجود JavaScript errors في console المتصفح</li>";
echo "<li>تحقق من أن API المحادثة يعمل بشكل صحيح</li>";
echo "<li>تحقق من أن المستخدم مسجل دخول</li>";
echo "</ul></li>";
echo "<li><strong>إذا لم تستطع الكتابة في المحادثة:</strong>";
echo "<ul>";
echo "<li>تحقق من أن حقل النص يظهر بشكل صحيح</li>";
echo "<li>تحقق من أن زر الإرسال يعمل</li>";
echo "<li>تحقق من أن API إرسال الرسائل يعمل</li>";
echo "</ul></li>";
echo "<li><strong>إذا كانت المشكلة في عدم ظهور البوستات:</strong>";
echo "<ul>";
echo "<li>قم بتشغيل <a href='debug_posts_issue.php'>فحص البوستات</a></li>";
echo "<li>تأكد من وجود بوستات في قاعدة البيانات</li>";
echo "<li>تأكد من أن المستخدم يتابع أشخاص لديهم بوستات</li>";
echo "</ul></li>";
echo "</ol>";

echo "<p><a href='home.php'>← العودة للصفحة الرئيسية</a> | <a href='chat.php'>💬 فتح المحادثة</a></p>";
?>

<style>
body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 20px; background: #f8f9fa; }
h1, h2, h3 { color: #2c3e50; }
table { background: white; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
th, td { padding: 12px; text-align: left; }
th { background: #34495e; color: white; }
tr:nth-child(even) { background: #f8f9fa; }
p { margin: 10px 0; }
a { text-decoration: none; border-radius: 4px; }
a:hover { opacity: 0.8; }
</style> 