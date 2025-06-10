<?php
session_start();
require_once 'config.php';

echo "🔒 اختبار أمان حذف التعليقات\n";
echo "================================\n\n";

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $_SESSION['user_id'] = 5;
    $_SESSION['username'] = 'ben10';
    
    echo "المستخدم الحالي: " . $_SESSION['user_id'] . " (ben10)\n\n";
    
    $stmt = $pdo->prepare("
        SELECT c.*, u.username 
        FROM comments c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.user_id != ? 
        LIMIT 1
    ");
    $stmt->execute([5]);
    $other_comment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$other_comment) {
        echo "❌ لا توجد تعليقات من مستخدمين آخرين للاختبار\n";
        
        $pdo->exec("INSERT IGNORE INTO users (id, username, email, first_name, last_name, password) VALUES (99, 'testuser', 'test@test.com', 'Test', 'User', 'dummy')");
        
        $stmt = $pdo->prepare("INSERT INTO posts (user_id, content) VALUES (99, 'منشور تجريبي')");
        $stmt->execute();
        $post_id = $pdo->lastInsertId();
        
        $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, 99, 'تعليق تجريبي من مستخدم آخر')");
        $stmt->execute([$post_id]);
        $comment_id = $pdo->lastInsertId();
        
        echo "✅ تم إنشاء تعليق تجريبي (ID: $comment_id) من المستخدم 99\n\n";
    } else {
        $comment_id = $other_comment['id'];
        echo "✅ وجد تعليق (ID: $comment_id) من المستخدم " . $other_comment['user_id'] . " (" . $other_comment['username'] . ")\n\n";
    }
    
    echo "🧪 اختبار: محاولة حذف تعليق مستخدم آخر...\n";
    
    $delete_data = [
        'action' => 'delete_comment',
        'comment_id' => $comment_id
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/WEP/api/social.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($delete_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Code: $http_code\n";
    echo "Response: $response\n\n";
    
    $result = json_decode($response, true);
    
    if ($result && !$result['success'] && strpos($result['message'], 'غير مصرح') !== false) {
        echo "✅ الأمان يعمل بشكل صحيح! النظام منع حذف التعليق.\n";
        echo "رسالة الخطأ: " . $result['message'] . "\n\n";
    } else {
        echo "❌ مشكلة أمنية! النظام سمح بحذف التعليق أو لم يرد بالرسالة المتوقعة.\n\n";
    }
    
    $stmt = $pdo->prepare("SELECT id FROM comments WHERE id = ?");
    $stmt->execute([$comment_id]);
    
    if ($stmt->fetch()) {
        echo "✅ التعليق ما زال موجود في قاعدة البيانات - الأمان محفوظ!\n";
    } else {
        echo "❌ التعليق محذوف من قاعدة البيانات - مشكلة أمنية!\n";
    }
    
} catch (Exception $e) {
    echo "❌ خطأ: " . $e->getMessage() . "\n";
}
?> 