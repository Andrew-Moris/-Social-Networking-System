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
echo "<html><head><meta charset='UTF-8'><title>تشخيص التعليقات في u.php</title>";
echo "<style>
body{font-family:Arial;padding:20px;background:#1a1a1a;color:white;} 
.container{max-width:1000px;margin:0 auto;background:#2a2a2a;padding:20px;border-radius:10px;} 
.success{color:#4CAF50;} 
.error{color:#f44336;} 
.info{color:#2196F3;} 
.warning{color:#ff9800;}
.btn{padding:10px 20px;background:#007bff;color:white;border:none;border-radius:5px;cursor:pointer;margin:5px;} 
.btn:hover{background:#0056b3;}
pre{background:#333;padding:15px;border-radius:5px;overflow-x:auto;color:#fff;max-height:300px;overflow-y:auto;}
.test-section{margin:20px 0;padding:15px;border:1px solid #444;border-radius:8px;}
.comment-test{background:#333;padding:10px;margin:10px 0;border-radius:5px;}
</style>";
echo "</head><body>";

echo "<div class='container'>";
echo "<h1>🔍 تشخيص التعليقات في u.php</h1>";

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div class='test-section'>";
    echo "<h2>👤 معلومات المستخدم الحالي:</h2>";
    echo "<p><strong>ID:</strong> {$_SESSION['user_id']}</p>";
    echo "<p><strong>Username:</strong> {$_SESSION['username']}</p>";
    echo "</div>";
    
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE user_id = 11 ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($post) {
        $post_id = $post['id'];
        
        echo "<div class='test-section'>";
        echo "<h2>📝 المنشور المختار للاختبار:</h2>";
        echo "<p><strong>ID:</strong> {$post['id']}</p>";
        echo "<p><strong>Content:</strong> " . htmlspecialchars($post['content']) . "</p>";
        echo "<p><strong>Created:</strong> {$post['created_at']}</p>";
        echo "</div>";
        
        echo "<div class='test-section'>";
        echo "<h2>💬 التعليقات الموجودة:</h2>";
        
        $stmt = $pdo->prepare("SELECT c.*, u.username, u.first_name, u.last_name FROM comments c JOIN users u ON c.user_id = u.id WHERE c.post_id = ? ORDER BY c.created_at DESC");
        $stmt->execute([$post_id]);
        $existing_comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($existing_comments)) {
            echo "<p class='warning'>⚠️ لا توجد تعليقات حالياً</p>";
            
            $test_comment = "🧪 تعليق تجريبي للاختبار - " . date('Y-m-d H:i:s');
            $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$post_id, 11, $test_comment]);
            
            echo "<p class='success'>✅ تم إضافة تعليق تجريبي</p>";
            
            $stmt = $pdo->prepare("SELECT c.*, u.username, u.first_name, u.last_name FROM comments c JOIN users u ON c.user_id = u.id WHERE c.post_id = ? ORDER BY c.created_at DESC");
            $stmt->execute([$post_id]);
            $existing_comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        echo "<p class='info'>📊 عدد التعليقات: " . count($existing_comments) . "</p>";
        
        foreach ($existing_comments as $comment) {
            echo "<div class='comment-test'>";
            echo "<strong>{$comment['username']}:</strong> " . htmlspecialchars($comment['content']);
            echo "<br><small>{$comment['created_at']}</small>";
            echo "</div>";
        }
        echo "</div>";
        
        echo "<div class='test-section'>";
        echo "<h2>🔗 اختبار API:</h2>";
        echo "<button onclick='testGetComments($post_id)' class='btn'>📥 جلب التعليقات</button>";
        echo "<button onclick='testAddComment($post_id)' class='btn'>➕ إضافة تعليق</button>";
        echo "<button onclick='testCommentDisplay($post_id)' class='btn'>🖥️ اختبار العرض</button>";
        echo "<div id='api-results' style='margin-top:20px;'></div>";
        echo "</div>";
        
        echo "<div class='test-section'>";
        echo "<h2>🎭 محاكاة واجهة u.php:</h2>";
        echo "<div style='background:#1a1a1a;padding:20px;border-radius:10px;'>";
        
        echo "<div class='post-card' data-post-id='$post_id' style='background:#2a2a2a;padding:15px;border-radius:8px;margin:10px 0;'>";
        echo "<div class='post-content'>";
        echo "<p>" . htmlspecialchars($post['content']) . "</p>";
        echo "</div>";
        
        echo "<div class='post-actions' style='margin-top:10px;'>";
        echo "<button class='action-button' data-action='comment' onclick='toggleComments($post_id)' style='background:#007bff;color:white;border:none;padding:8px 12px;border-radius:5px;margin-right:10px;cursor:pointer;'>";
        echo "<i class='bi bi-chat-dots'></i> <span class='comment-count'>" . count($existing_comments) . "</span> Comments";
        echo "</button>";
        echo "</div>";
        
        echo "<div id='comments-$post_id' class='comments-section' style='display:none;border-top:1px solid #444;margin-top:15px;padding-top:15px;'>";
        
        echo "<div class='comment-form' style='margin-bottom:15px;'>";
        echo "<form onsubmit='submitComment(event, $post_id)' style='display:flex;gap:10px;'>";
        echo "<input type='text' placeholder='اكتب تعليق...' style='flex:1;padding:8px;border:1px solid #555;border-radius:5px;background:#333;color:white;' required>";
        echo "<button type='submit' style='background:#28a745;color:white;border:none;padding:8px 15px;border-radius:5px;cursor:pointer;'>إرسال</button>";
        echo "</form>";
        echo "</div>";
        
        echo "<div id='comments-container-$post_id' class='comments-container'>";
        echo "<!-- التعليقات ستظهر هنا -->";
        echo "</div>";
        
        echo "</div>";
        echo "</div>";
        echo "</div>";
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
let apiResults = document.getElementById('api-results');

function logResult(test, success, data) {
    const timestamp = new Date().toLocaleTimeString();
    const status = success ? '✅' : '❌';
    const color = success ? '#4CAF50' : '#f44336';
    
    apiResults.innerHTML += `
        <div style='margin:10px 0;padding:10px;border-left:4px solid \${color};background:#333;'>
            <strong>[\${timestamp}] \${status} \${test}</strong>
            <pre>\${JSON.stringify(data, null, 2)}</pre>
        </div>
    `;
    apiResults.scrollTop = apiResults.scrollHeight;
}

async function testGetComments(postId) {
    try {
        console.log('🔄 Testing get comments for post:', postId);
        
        const response = await fetch(`api/social.php?action=get_comments&post_id=\${postId}`);
        console.log('📡 Response status:', response.status);
        
        const result = await response.json();
        console.log('📊 API Result:', result);
        
        logResult('جلب التعليقات', result.success, result);
        
        if (result.success && result.data && result.data.comments) {
            console.log('💬 Found comments:', result.data.comments.length);
        }
    } catch (error) {
        console.error('❌ Error:', error);
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
                content: '🧪 تعليق تجريبي من واجهة التشخيص - ' + new Date().toLocaleString()
            })
        });
        
        const result = await response.json();
        logResult('إضافة تعليق', result.success, result);
        
        if (result.success) {
            // إعادة جلب التعليقات
            setTimeout(() => testGetComments(postId), 1000);
        }
    } catch (error) {
        logResult('إضافة تعليق', false, { error: error.message });
    }
}

async function testCommentDisplay(postId) {
    try {
        // محاكاة تحميل التعليقات في الواجهة
        const commentsSection = document.getElementById(`comments-\${postId}`);
        const commentsContainer = document.getElementById(`comments-container-\${postId}`);
        
        if (commentsSection.style.display === 'none') {
            commentsSection.style.display = 'block';
            
            // تحميل التعليقات
            const response = await fetch(`api/social.php?action=get_comments&post_id=\${postId}`);
            const result = await response.json();
            
            if (result.success && result.data.comments) {
                if (result.data.comments.length === 0) {
                    commentsContainer.innerHTML = '<div style=\"text-align:center;padding:20px;color:#ccc;\">لا توجد تعليقات بعد</div>';
                } else {
                    commentsContainer.innerHTML = result.data.comments.map(comment => `
                        <div style='padding:10px;border-bottom:1px solid #444;'>
                            <strong>\${comment.username}:</strong> \${comment.content}
                            <br><small style='color:#ccc;'>\${comment.created_at}</small>
                        </div>
                    `).join('');
                }
                
                logResult('عرض التعليقات', true, { comments_count: result.data.comments.length });
            } else {
                commentsContainer.innerHTML = '<div style=\"text-align:center;padding:20px;color:#f44336;\">فشل في تحميل التعليقات</div>';
                logResult('عرض التعليقات', false, result);
            }
        } else {
            commentsSection.style.display = 'none';
            logResult('إخفاء التعليقات', true, { action: 'hidden' });
        }
    } catch (error) {
        logResult('عرض التعليقات', false, { error: error.message });
    }
}

// دوال محاكاة u.php
async function toggleComments(postId) {
    const commentsSection = document.getElementById(`comments-\${postId}`);
    
    if (commentsSection.style.display === 'none') {
        commentsSection.style.display = 'block';
        await loadComments(postId);
    } else {
        commentsSection.style.display = 'none';
    }
}

async function loadComments(postId) {
    const container = document.getElementById(`comments-container-\${postId}`);
    
    try {
        const response = await fetch(`api/social.php?action=get_comments&post_id=\${postId}`);
        const result = await response.json();
        
        if (result.success && result.data.comments) {
            if (result.data.comments.length === 0) {
                container.innerHTML = '<div style=\"text-align:center;padding:20px;color:#ccc;\">لا توجد تعليقات بعد</div>';
            } else {
                container.innerHTML = result.data.comments.map(comment => `
                    <div style='padding:10px;border-bottom:1px solid #444;'>
                        <strong>\${comment.username}:</strong> \${comment.content}
                        <br><small style='color:#ccc;'>\${comment.created_at}</small>
                    </div>
                `).join('');
            }
        } else {
            container.innerHTML = '<div style=\"text-align:center;padding:20px;color:#f44336;\">فشل في تحميل التعليقات</div>';
        }
    } catch (error) {
        console.error('Error loading comments:', error);
        container.innerHTML = '<div style=\"text-align:center;padding:20px;color:#f44336;\">خطأ في تحميل التعليقات</div>';
    }
}

async function submitComment(event, postId) {
    event.preventDefault();
    const form = event.target;
    const input = form.querySelector('input[type=\"text\"]');
    const content = input.value.trim();
    
    if (!content) return;
    
    try {
        const response = await fetch('api/social.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'add_comment',
                post_id: postId,
                content: content
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            input.value = '';
            await loadComments(postId);
            
            // تحديث عداد التعليقات
            const commentBtn = document.querySelector(`[data-post-id=\"\${postId}\"] .comment-count`);
            if (commentBtn) {
                const currentCount = parseInt(commentBtn.textContent) || 0;
                commentBtn.textContent = currentCount + 1;
            }
        } else {
            alert('فشل في إضافة التعليق: ' + (result.message || 'خطأ غير معروف'));
        }
    } catch (error) {
        console.error('Error adding comment:', error);
        alert('خطأ في إضافة التعليق');
    }
}

// اختبار تلقائي عند تحميل الصفحة
window.onload = function() {
    const postId = " . ($post['id'] ?? 0) . ";
    if (postId > 0) {
        console.log('🎯 بدء الاختبار التلقائي للمنشور:', postId);
        setTimeout(() => testGetComments(postId), 1000);
    }
};
</script>";

echo "</body></html>";
?> 