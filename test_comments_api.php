<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = 11");
        $stmt->execute();
        $user = $stmt->fetch();
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
        }
    } catch (Exception $e) {
        echo "خطأ في تسجيل الدخول: " . $e->getMessage();
        exit;
    }
}

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'><title>اختبار API التعليقات</title>";
echo "<style>
body{font-family:Arial;padding:20px;background:#1a1a1a;color:white;} 
.container{max-width:800px;margin:0 auto;background:#2a2a2a;padding:20px;border-radius:10px;} 
.success{color:#4CAF50;} 
.error{color:#f44336;} 
.info{color:#2196F3;} 
.btn{padding:10px 20px;background:#007bff;color:white;border:none;border-radius:5px;cursor:pointer;margin:5px;} 
.btn:hover{background:#0056b3;}
pre{background:#333;padding:15px;border-radius:5px;overflow-x:auto;color:#fff;}
.test-section{margin:20px 0;padding:15px;border:1px solid #444;border-radius:8px;}
</style>";
echo "</head><body>";

echo "<div class='container'>";
echo "<h1>🧪 اختبار API التعليقات</h1>";

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div class='test-section'>";
    echo "<h2>📊 معلومات المستخدم:</h2>";
    echo "<p><strong>ID:</strong> {$_SESSION['user_id']}</p>";
    echo "<p><strong>Username:</strong> {$_SESSION['username']}</p>";
    echo "</div>";
    
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE user_id = 11 ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($post) {
        $post_id = $post['id'];
        
        echo "<div class='test-section'>";
        echo "<h2>📝 المنشور المختار:</h2>";
        echo "<p><strong>ID:</strong> {$post['id']}</p>";
        echo "<p><strong>Content:</strong> " . htmlspecialchars($post['content']) . "</p>";
        echo "</div>";
        
        echo "<div class='test-section'>";
        echo "<h2>🔗 اختبارات API:</h2>";
        echo "<button onclick='testGetComments($post_id)' class='btn'>📥 جلب التعليقات</button>";
        echo "<button onclick='testAddComment($post_id)' class='btn'>➕ إضافة تعليق</button>";
        echo "<button onclick='testToggleLike($post_id)' class='btn'>❤️ تبديل الإعجاب</button>";
        echo "<button onclick='testToggleBookmark($post_id)' class='btn'>🔖 تبديل المفضلة</button>";
        echo "<div id='test-results' style='margin-top:20px;'></div>";
        echo "</div>";
        
    } else {
        echo "<div class='test-section'>";
        echo "<p class='error'>❌ لا توجد منشورات للمستخدم 11</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='test-section'>";
    echo "<p class='error'>❌ خطأ: " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "</div>";

echo "<script>
let testResults = document.getElementById('test-results');

function logResult(test, success, data) {
    const timestamp = new Date().toLocaleTimeString();
    const status = success ? '✅' : '❌';
    const color = success ? '#4CAF50' : '#f44336';
    
    testResults.innerHTML += `
        <div style='margin:10px 0;padding:10px;border-left:4px solid \${color};background:#333;'>
            <strong>[\${timestamp}] \${status} \${test}</strong>
            <pre>\${JSON.stringify(data, null, 2)}</pre>
        </div>
    `;
    testResults.scrollTop = testResults.scrollHeight;
}

async function testGetComments(postId) {
    try {
        const response = await fetch(`api/social.php?action=get_comments&post_id=\${postId}`);
        const result = await response.json();
        
        logResult('جلب التعليقات', result.success, result);
    } catch (error) {
        logResult('جلب التعليقات', false, { error: error.message });
    }
}

async function testAddComment(postId) {
    try {
        const response = await fetch('api/social.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'add_comment',
                post_id: postId,
                content: '🧪 تعليق تجريبي من API - ' + new Date().toLocaleString()
            })
        });
        
        const result = await response.json();
        logResult('إضافة تعليق', result.success, result);
        
        if (result.success) {
            // إعادة جلب التعليقات لعرض التحديث
            setTimeout(() => testGetComments(postId), 1000);
        }
    } catch (error) {
        logResult('إضافة تعليق', false, { error: error.message });
    }
}

async function testToggleLike(postId) {
    try {
        const response = await fetch('api/social.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'toggle_like',
                post_id: postId
            })
        });
        
        const result = await response.json();
        logResult('تبديل الإعجاب', result.success, result);
    } catch (error) {
        logResult('تبديل الإعجاب', false, { error: error.message });
    }
}

async function testToggleBookmark(postId) {
    try {
        const response = await fetch('api/social.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'toggle_bookmark',
                post_id: postId
            })
        });
        
        const result = await response.json();
        logResult('تبديل المفضلة', result.success, result);
    } catch (error) {
        logResult('تبديل المفضلة', false, { error: error.message });
    }
}

// اختبار تلقائي عند تحميل الصفحة
window.onload = function() {
    const postId = " . ($post['id'] ?? 0) . ";
    if (postId > 0) {
        setTimeout(() => testGetComments(postId), 500);
    }
};
</script>";

echo "</body></html>";
?> 