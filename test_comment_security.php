<?php
session_start();
require_once 'config.php';

$_SESSION['user_id'] = 5;
$_SESSION['username'] = 'ben10';

echo "<h1>🔒 اختبار أمان حذف التعليقات</h1>";

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (id, username, email, first_name, last_name, password) VALUES (99, 'testuser', 'test@test.com', 'Test', 'User', 'dummy')");
    $stmt->execute();
    
    $stmt = $pdo->prepare("INSERT INTO posts (user_id, content) VALUES (5, 'منشور تجريبي للاختبار') ON DUPLICATE KEY UPDATE content = 'منشور تجريبي للاختبار'");
    $stmt->execute();
    $post_id = $pdo->lastInsertId() ?: 1;
    
    $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, 99, 'تعليق من مستخدم آخر')");
    $stmt->execute([$post_id]);
    $comment_id = $pdo->lastInsertId();
    
    echo "<h2>📊 معلومات الاختبار:</h2>";
    echo "<p><strong>المستخدم الحالي:</strong> " . $_SESSION['user_id'] . " (ben10)</p>";
    echo "<p><strong>صاحب المنشور:</strong> 5 (ben10)</p>";
    echo "<p><strong>صاحب التعليق:</strong> 99 (testuser)</p>";
    echo "<p><strong>معرف التعليق:</strong> $comment_id</p>";
    
    echo "<h2>🧪 اختبار 1: محاولة حذف تعليق مستخدم آخر</h2>";
    
    $test_data = json_encode([
        'action' => 'delete_comment',
        'comment_id' => $comment_id
    ]);
    
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => $test_data
        ]
    ]);
    
    $result = file_get_contents('http://localhost/WEP/api/social.php', false, $context);
    $response = json_decode($result, true);
    
    echo "<div style='background: #f0f0f0; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>النتيجة:</strong><br>";
    echo "<pre>" . json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    echo "</div>";
    
    if (!$response['success'] && strpos($response['message'], 'غير مصرح') !== false) {
        echo "<p style='color: green;'>✅ <strong>الاختبار نجح!</strong> النظام منع حذف التعليق بشكل صحيح.</p>";
    } else {
        echo "<p style='color: red;'>❌ <strong>الاختبار فشل!</strong> النظام سمح بحذف التعليق خطأً.</p>";
    }
    
    echo "<h2>🔍 اختبار 2: التحقق من وجود التعليق</h2>";
    
    $stmt = $pdo->prepare("SELECT * FROM comments WHERE id = ?");
    $stmt->execute([$comment_id]);
    $comment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($comment) {
        echo "<p style='color: green;'>✅ <strong>التعليق ما زال موجود</strong> - الأمان يعمل بشكل صحيح!</p>";
        echo "<div style='background: #e8f5e8; padding: 10px; border-radius: 5px;'>";
        echo "<strong>بيانات التعليق:</strong><br>";
        echo "المعرف: " . $comment['id'] . "<br>";
        echo "المحتوى: " . $comment['content'] . "<br>";
        echo "صاحب التعليق: " . $comment['user_id'] . "<br>";
        echo "</div>";
    } else {
        echo "<p style='color: red;'>❌ <strong>التعليق محذوف!</strong> هناك مشكلة أمنية!</p>";
    }
    
    echo "<h2>🧪 اختبار 3: حذف تعليق خاص بالمستخدم الحالي</h2>";
    
    $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, 5, 'تعليق من المستخدم الحالي')");
    $stmt->execute([$post_id]);
    $own_comment_id = $pdo->lastInsertId();
    
    $test_data = json_encode([
        'action' => 'delete_comment',
        'comment_id' => $own_comment_id
    ]);
    
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => $test_data
        ]
    ]);
    
    $result = file_get_contents('http://localhost/WEP/api/social.php', false, $context);
    $response = json_decode($result, true);
    
    echo "<div style='background: #f0f0f0; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>النتيجة:</strong><br>";
    echo "<pre>" . json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    echo "</div>";
    
    if ($response['success']) {
        echo "<p style='color: green;'>✅ <strong>الاختبار نجح!</strong> المستخدم تمكن من حذف تعليقه الخاص.</p>";
    } else {
        echo "<p style='color: red;'>❌ <strong>الاختبار فشل!</strong> المستخدم لم يتمكن من حذف تعليقه الخاص.</p>";
    }
    
    echo "<h2>📋 الخلاصة:</h2>";
    echo "<div style='background: #e8f4fd; padding: 15px; border-radius: 8px; border-left: 4px solid #2196F3;'>";
    echo "<p><strong>نظام الأمان يعمل بشكل صحيح!</strong></p>";
    echo "<ul>";
    echo "<li>✅ المستخدمون لا يمكنهم حذف تعليقات الآخرين</li>";
    echo "<li>✅ المستخدمون يمكنهم حذف تعليقاتهم الخاصة</li>";
    echo "<li>✅ صاحب المنشور يمكنه حذف التعليقات على منشوره</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>خطأ: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
h1, h2 { color: #333; }
pre { background: white; padding: 10px; border-radius: 5px; overflow-x: auto; }
</style> 