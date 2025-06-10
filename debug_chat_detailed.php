<?php


session_start();
require_once 'config.php';

echo "<h1>🔍 فحص مفصل لمشكلة المحادثة</h1>";

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>1. التحقق من المستخدم الحالي:</h2>";
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
        echo "<p><a href='login.php'>تسجيل الدخول</a></p>";
        exit;
    }
    
    echo "<h2>2. المستخدمين المتاحين للمحادثة:</h2>";
    $users_stmt = $pdo->prepare("SELECT id, username, first_name, last_name FROM users WHERE id != ? LIMIT 10");
    $users_stmt->execute([$user_id]);
    $users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($users) > 0) {
        echo "<p>✅ يوجد " . count($users) . " مستخدمين متاحين للمحادثة</p>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; width: 100%;'>";
        echo "<tr><th>ID</th><th>Username</th><th>Name</th><th>Test Chat</th></tr>";
        foreach ($users as $test_user) {
            echo "<tr>";
            echo "<td>{$test_user['id']}</td>";
            echo "<td>{$test_user['username']}</td>";
            echo "<td>{$test_user['first_name']} {$test_user['last_name']}</td>";
            echo "<td>";
            echo "<button onclick=\"testOpenChat({$test_user['id']}, '{$test_user['username']}')\">اختبار فتح المحادثة</button>";
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ لا يوجد مستخدمين آخرين للمحادثة معهم</p>";
    }
    
    echo "<h2>3. اختبار API المحادثة:</h2>";
    
    if (file_exists('api/chat.php')) {
        echo "<p>✅ ملف API المحادثة موجود</p>";
        
        echo "<p>اختبار API:</p>";
        echo "<ul>";
        echo "<li><a href='api/chat.php?action=get_conversations' target='_blank'>عرض المحادثات</a></li>";
        if (!empty($users)) {
            $test_user = $users[0];
            echo "<li><a href='api/chat.php?action=get_messages&user_id={$test_user['id']}' target='_blank'>عرض رسائل مع {$test_user['username']}</a></li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>❌ ملف API المحادثة غير موجود</p>";
    }
    
    echo "<h2>4. اختبار JavaScript:</h2>";
    echo "<div id='jsTestResults'>";
    echo "<p>⏳ جاري اختبار JavaScript...</p>";
    echo "</div>";
    
    echo "<h2>5. اختبار عناصر HTML:</h2>";
    echo "<div id='htmlTestResults'>";
    echo "<p>⏳ جاري اختبار عناصر HTML...</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ خطأ: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>🔧 الحلول المقترحة:</h2>";
echo "<ol>";
echo "<li><strong>إذا لم تعمل دالة openChat:</strong>";
echo "<ul>";
echo "<li>تحقق من وجود أخطاء JavaScript في console المتصفح</li>";
echo "<li>تأكد من أن المعرف (user_id) صحيح</li>";
echo "<li>تأكد من أن دالة openChat موجودة ومحملة</li>";
echo "</ul></li>";
echo "<li><strong>إذا لم تظهر واجهة المحادثة:</strong>";
echo "<ul>";
echo "<li>تحقق من أن عنصر chatContent موجود</li>";
echo "<li>تحقق من CSS وأن العناصر ظاهرة</li>";
echo "<li>تأكد من أن API يعمل بشكل صحيح</li>";
echo "</ul></li>";
echo "<li><strong>إذا لم تعمل الرسائل:</strong>";
echo "<ul>";
echo "<li>تحقق من جدول messages في قاعدة البيانات</li>";
echo "<li>تأكد من أن API إرسال الرسائل يعمل</li>";
echo "<li>تحقق من أذونات الملفات</li>";
echo "</ul></li>";
echo "</ol>";

echo "<p><a href='home.php'>← العودة للصفحة الرئيسية</a> | <a href='chat.php'>💬 فتح المحادثة</a></p>";
?>

<style>
body { 
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
    margin: 20px; 
    background: #f8f9fa; 
    direction: rtl;
}
h1, h2, h3 { color: #2c3e50; }
table { 
    background: white; 
    margin: 10px 0; 
    border-radius: 8px; 
    box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
}
th, td { padding: 12px; text-align: right; }
th { background: #34495e; color: white; }
tr:nth-child(even) { background: #f8f9fa; }
p { margin: 10px 0; }
a { text-decoration: none; border-radius: 4px; }
a:hover { opacity: 0.8; }
button {
    background: #007bff;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
}
button:hover {
    background: #0056b3;
}
.test-result {
    padding: 10px;
    margin: 5px 0;
    border-radius: 4px;
}
.test-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}
.test-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
.test-warning {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔍 بدء اختبار JavaScript للمحادثة');
    
    const jsResults = document.getElementById('jsTestResults');
    const htmlResults = document.getElementById('htmlTestResults');
    
    let jsTests = [];
    let htmlTests = [];
    
    try {
        if (typeof openChat === 'function') {
            jsTests.push({type: 'success', message: '✅ دالة openChat موجودة'});
        } else {
            jsTests.push({type: 'error', message: '❌ دالة openChat غير موجودة'});
        }
    } catch (e) {
        jsTests.push({type: 'error', message: '❌ خطأ في فحص دالة openChat: ' + e.message});
    }
    
    if (typeof fetch === 'function') {
        jsTests.push({type: 'success', message: '✅ fetch API متاح'});
    } else {
        jsTests.push({type: 'error', message: '❌ fetch API غير متاح'});
    }
    
    if (typeof console === 'object' && typeof console.log === 'function') {
        jsTests.push({type: 'success', message: '✅ console متاح'});
    } else {
        jsTests.push({type: 'warning', message: '⚠️ console غير متاح'});
    }
    
    if (typeof JSON === 'object' && typeof JSON.parse === 'function') {
        jsTests.push({type: 'success', message: '✅ JSON متاح'});
    } else {
        jsTests.push({type: 'error', message: '❌ JSON غير متاح'});
    }
    
    jsResults.innerHTML = jsTests.map(test => 
        `<div class="test-result test-${test.type}">${test.message}</div>`
    ).join('');
    
    setTimeout(() => {
        const chatContent = document.getElementById('chatContent');
        if (chatContent) {
            htmlTests.push({type: 'success', message: '✅ عنصر chatContent موجود'});
        } else {
            htmlTests.push({type: 'error', message: '❌ عنصر chatContent غير موجود'});
        }
        
        const conversationItems = document.querySelectorAll('.conversation-item');
        if (conversationItems.length > 0) {
            htmlTests.push({type: 'success', message: `✅ يوجد ${conversationItems.length} عنصر محادثة`});
        } else {
            htmlTests.push({type: 'warning', message: '⚠️ لا توجد عناصر محادثة'});
        }
        
        htmlResults.innerHTML = htmlTests.map(test => 
            `<div class="test-result test-${test.type}">${test.message}</div>`
        ).join('');
    }, 1000);
});

function testOpenChat(userId, username) {
    console.log('🧪 اختبار فتح المحادثة مع:', userId, username);
    
    if (!userId || !username) {
        alert('❌ معاملات غير صحيحة: userId=' + userId + ', username=' + username);
        return;
    }
    
    try {
        if (typeof openChat === 'function') {
            console.log('✅ استدعاء دالة openChat');
            openChat(userId, username);
            alert('✅ تم استدعاء دالة openChat بنجاح');
        } else {
            alert('❌ دالة openChat غير موجودة');
        }
    } catch (error) {
        console.error('❌ خطأ في استدعاء openChat:', error);
        alert('❌ خطأ في استدعاء openChat: ' + error.message);
    }
    
    setTimeout(() => {
        const chatContent = document.getElementById('chatContent');
        if (chatContent && chatContent.innerHTML.includes(username)) {
            alert('✅ تم تحديث واجهة المحادثة بنجاح');
        } else {
            alert('⚠️ لم يتم تحديث واجهة المحادثة');
        }
    }, 2000);
}

if (typeof openChat !== 'function') {
    function openChat(userId, username) {
        console.log('🔧 دالة openChat تجريبية - userId:', userId, 'username:', username);
        
        const chatContent = document.getElementById('chatContent');
        if (chatContent) {
            chatContent.innerHTML = `
                <div style="padding: 20px; text-align: center;">
                    <h3>اختبار المحادثة مع ${username}</h3>
                    <p>معرف المستخدم: ${userId}</p>
                    <p>هذه واجهة اختبار - إذا ظهرت هذه الرسالة فإن دالة openChat تعمل</p>
                </div>
            `;
            console.log('✅ تم تحديث chatContent');
        } else {
            console.error('❌ لم يتم العثور على chatContent');
            alert('❌ لم يتم العثور على عنصر chatContent');
        }
    }
}
</script> 