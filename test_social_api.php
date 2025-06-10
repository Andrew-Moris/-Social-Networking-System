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
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['avatar_url'] = $user['avatar_url'];
        }
    } catch (Exception $e) {
        echo "خطأ في تسجيل الدخول: " . $e->getMessage();
        exit;
    }
}

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'><title>اختبار API الاجتماعي</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f0f2f5;} .container{max-width:800px;margin:0 auto;background:white;padding:20px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);} .btn{padding:10px 20px;background:#007bff;color:white;border:none;border-radius:5px;cursor:pointer;margin:5px;} .btn:hover{background:#0056b3;} .result{margin:10px 0;padding:10px;border-radius:5px;} .success{background:#d4edda;color:#155724;} .error{background:#f8d7da;color:#721c24;}</style>";
echo "</head><body>";

echo "<div class='container'>";
echo "<h1>🧪 اختبار API الاجتماعي</h1>";
echo "<p>المستخدم: {$_SESSION['username']} (ID: {$_SESSION['user_id']})</p>";

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
    $stmt = $pdo->prepare("SELECT id, content FROM posts WHERE user_id = 11 LIMIT 1");
    $stmt->execute();
    $post = $stmt->fetch();
    
    if ($post) {
        echo "<h2>📝 منشور للاختبار:</h2>";
        echo "<div style='border:1px solid #ddd;padding:15px;margin:10px 0;border-radius:8px;background:#f9f9f9;'>";
        echo "<p><strong>ID:</strong> {$post['id']}</p>";
        echo "<p><strong>المحتوى:</strong> " . htmlspecialchars($post['content']) . "</p>";
        echo "</div>";
        
        echo "<h2>🔧 اختبار الوظائف:</h2>";
        echo "<button class='btn' onclick='testLike({$post['id']})'>❤️ اختبار الإعجاب</button>";
        echo "<button class='btn' onclick='testBookmark({$post['id']})'>🔖 اختبار الحفظ</button>";
        echo "<button class='btn' onclick='testComment({$post['id']})'>💬 اختبار التعليق</button>";
        echo "<button class='btn' onclick='getComments({$post['id']})'>📋 جلب التعليقات</button>";
        
        echo "<div id='results'></div>";
        
    } else {
        echo "<p style='color:red;'>❌ لا توجد منشورات للاختبار</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ خطأ: " . $e->getMessage() . "</p>";
}

echo "</div>";

echo "<script>
async function testLike(postId) {
    const resultsDiv = document.getElementById('results');
    resultsDiv.innerHTML += '<p>🔄 اختبار الإعجاب...</p>';
    
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
        
        if (result.success) {
            resultsDiv.innerHTML += `<div class='result success'>✅ الإعجاب: \${result.data.is_liked ? 'تم' : 'تم الإلغاء'} - العدد: \${result.data.like_count}</div>`;
        } else {
            resultsDiv.innerHTML += `<div class='result error'>❌ فشل الإعجاب: \${result.message}</div>`;
        }
    } catch (error) {
        resultsDiv.innerHTML += `<div class='result error'>❌ خطأ: \${error.message}</div>`;
    }
}

async function testBookmark(postId) {
    const resultsDiv = document.getElementById('results');
    resultsDiv.innerHTML += '<p>🔄 اختبار الحفظ...</p>';
    
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
        
        if (result.success) {
            resultsDiv.innerHTML += `<div class='result success'>✅ الحفظ: \${result.data.is_bookmarked ? 'تم' : 'تم الإلغاء'}</div>`;
        } else {
            resultsDiv.innerHTML += `<div class='result error'>❌ فشل الحفظ: \${result.message}</div>`;
        }
    } catch (error) {
        resultsDiv.innerHTML += `<div class='result error'>❌ خطأ: \${error.message}</div>`;
    }
}

async function testComment(postId) {
    const resultsDiv = document.getElementById('results');
    resultsDiv.innerHTML += '<p>🔄 اختبار التعليق...</p>';
    
    try {
        const response = await fetch('api/social.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'add_comment',
                post_id: postId,
                content: 'تعليق تجريبي من الاختبار 🎉'
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            resultsDiv.innerHTML += `<div class='result success'>✅ تم إضافة التعليق بنجاح</div>`;
        } else {
            resultsDiv.innerHTML += `<div class='result error'>❌ فشل التعليق: \${result.message}</div>`;
        }
    } catch (error) {
        resultsDiv.innerHTML += `<div class='result error'>❌ خطأ: \${error.message}</div>`;
    }
}

async function getComments(postId) {
    const resultsDiv = document.getElementById('results');
    resultsDiv.innerHTML += '<p>🔄 جلب التعليقات...</p>';
    
    try {
        const response = await fetch(`api/social.php?action=get_comments&post_id=\${postId}`);
        const result = await response.json();
        
        if (result.success) {
            const comments = result.data.comments;
            resultsDiv.innerHTML += `<div class='result success'>✅ تم جلب \${comments.length} تعليق</div>`;
            
            comments.forEach(comment => {
                resultsDiv.innerHTML += `<div style='border:1px solid #ddd;padding:10px;margin:5px 0;border-radius:5px;background:#f9f9f9;'>
                    <strong>\${comment.username}:</strong> \${comment.content}
                    <br><small>\${comment.created_at}</small>
                </div>`;
            });
        } else {
            resultsDiv.innerHTML += `<div class='result error'>❌ فشل جلب التعليقات: \${result.message}</div>`;
        }
    } catch (error) {
        resultsDiv.innerHTML += `<div class='result error'>❌ خطأ: \${error.message}</div>`;
    }
}
</script>";

echo "</body></html>";
?> 