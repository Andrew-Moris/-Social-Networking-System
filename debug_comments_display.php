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
echo "<html><head><meta charset='UTF-8'><title>فحص عرض التعليقات</title>";
echo "<meta name='user-id' content='{$_SESSION['user_id']}'>";
echo "<style>
body{font-family:Arial;padding:20px;background:#1a1a1a;color:white;} 
.container{max-width:600px;margin:0 auto;background:#2a2a2a;padding:20px;border-radius:10px;} 
.post{background:#333;padding:15px;border-radius:8px;margin:20px 0;} 
.comments-section{background:#444;padding:15px;border-radius:8px;margin-top:10px;display:none;} 
.comment-item{background:#555;padding:10px;margin:10px 0;border-radius:5px;} 
.btn{padding:8px 15px;background:#007bff;color:white;border:none;border-radius:5px;cursor:pointer;margin:5px;} 
.btn:hover{background:#0056b3;}
.comment-input{width:100%;padding:8px;border:1px solid #666;border-radius:5px;background:#666;color:white;}
.action-button{background:none;border:none;color:#ccc;cursor:pointer;padding:5px;}
.action-button:hover{color:white;}
.liked{color:#ff6b6b;}
</style>";
echo "</head><body>";

echo "<div class='container'>";
echo "<h1>🔍 فحص عرض التعليقات</h1>";

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE user_id = 11 ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$post) {
        echo "<p>❌ لا توجد منشورات للمستخدم 11</p>";
        exit;
    }
    
    $post_id = $post['id'];
    
    echo "<div class='post' data-post-id='$post_id'>";
    echo "<h3>📝 المنشور (ID: $post_id)</h3>";
    echo "<p>" . htmlspecialchars($post['content']) . "</p>";
    echo "<div class='post-actions'>";
    echo "<button class='btn' onclick='toggleComments($post_id)'>💬 عرض التعليقات</button>";
    echo "<button class='btn' onclick='testAddComment($post_id)'>➕ إضافة تعليق تجريبي</button>";
    echo "</div>";
    
    echo "<div id='comments-$post_id' class='comments-section'>";
    echo "<div id='comments-container-$post_id'></div>";
    echo "<form onsubmit='submitComment(event, $post_id)' style='margin-top:10px;'>";
    echo "<input type='text' class='comment-input' placeholder='اكتب تعليقاً...' required>";
    echo "<button type='submit' class='btn'>إرسال</button>";
    echo "</form>";
    echo "</div>";
    echo "</div>";
    
    echo "<h2>💾 التعليقات في قاعدة البيانات:</h2>";
    $stmt = $pdo->prepare("
        SELECT c.*, u.username, u.first_name, u.last_name, u.avatar_url
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.post_id = ?
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$post_id]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($comments)) {
        echo "<p>❌ لا توجد تعليقات في قاعدة البيانات</p>";
    } else {
        echo "<p>✅ يوجد " . count($comments) . " تعليق في قاعدة البيانات</p>";
        foreach ($comments as $comment) {
            echo "<div class='comment-item'>";
            echo "<strong>{$comment['username']}:</strong> " . htmlspecialchars($comment['content']);
            echo "<br><small>{$comment['created_at']}</small>";
            echo "</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<p>❌ خطأ: " . $e->getMessage() . "</p>";
}

echo "</div>";

echo "<script>
async function toggleComments(postId) {
    console.log('🔄 Toggle comments for post:', postId);
    const commentsSection = document.getElementById(`comments-\${postId}`);
    const commentsContainer = document.getElementById(`comments-container-\${postId}`);
    
    if (commentsSection.style.display === 'none' || commentsSection.style.display === '') {
        commentsSection.style.display = 'block';
        console.log('📂 Comments section opened, loading comments...');
        await loadComments(postId);
    } else {
        commentsSection.style.display = 'none';
        console.log('📁 Comments section closed');
    }
}

async function loadComments(postId) {
    console.log('🔄 Loading comments for post:', postId);
    const container = document.getElementById(`comments-container-\${postId}`);
    
    try {
        const response = await fetch(`api/social.php?action=get_comments&post_id=\${postId}`);
        console.log('📡 API Response status:', response.status);
        
        const result = await response.json();
        console.log('📊 API Result:', result);
        
        if (result.success && result.data.comments) {
            if (result.data.comments.length === 0) {
                container.innerHTML = '<div style=\"text-align:center;padding:20px;color:#ccc;\">لا توجد تعليقات بعد</div>';
                console.log('📭 No comments found');
            } else {
                console.log('💬 Found', result.data.comments.length, 'comments');
                const currentUserId = parseInt(document.querySelector('meta[name=\"user-id\"]').content);
                console.log('👤 Current user ID:', currentUserId);
                
                container.innerHTML = result.data.comments.map(comment => {
                    console.log('🔧 Processing comment:', comment);
                    return `
                        <div class=\"comment-item\" id=\"comment-\${comment.id}\">
                            <div style=\"display:flex;gap:10px;\">
                                <img src=\"\${comment.avatar_url || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(comment.username) + '&background=667eea&color=fff&size=80'}\" 
                                     alt=\"\${comment.username}\" style=\"width:40px;height:40px;border-radius:50%;object-fit:cover;\">
                                <div style=\"flex:1;\">
                                    <div style=\"margin-bottom:5px;\">
                                        <span style=\"font-weight:bold;color:white;\">\${comment.first_name} \${comment.last_name}</span>
                                        <span style=\"color:#ccc;font-size:12px;\">@\${comment.username}</span>
                                        <span style=\"color:#999;font-size:11px;\">\${formatDate(comment.created_at)}</span>
                                        \${(comment.user_id === currentUserId) ? `
                                            <button class=\"action-button\" onclick=\"deleteComment(\${comment.id}, \${postId})\" title=\"حذف التعليق\" style=\"float:right;color:#ff6b6b;\">
                                                🗑️
                                            </button>
                                        ` : ''}
                                    </div>
                                    <p style=\"color:white;margin:5px 0;\">\${comment.content}</p>
                                    <div style=\"display:flex;gap:10px;\">
                                        <button class=\"action-button \${comment.user_liked ? 'liked' : ''}\" onclick=\"toggleCommentLike(\${comment.id}, this)\">
                                            ❤️ <span>\${comment.like_count}</span>
                                        </button>
                                        <button class=\"action-button\" onclick=\"replyToComment(\${postId}, '\${comment.username}')\">
                                            💬 رد
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                }).join('');
                console.log('✅ Comments rendered successfully');
            }
        } else {
            container.innerHTML = '<div style=\"text-align:center;padding:20px;color:#ff6b6b;\">خطأ في تحميل التعليقات</div>';
            console.log('❌ API returned error:', result.message);
        }
    } catch (error) {
        console.error('❌ Error loading comments:', error);
        container.innerHTML = '<div style=\"text-align:center;padding:20px;color:#ff6b6b;\">خطأ في الاتصال</div>';
    }
}

async function submitComment(event, postId) {
    event.preventDefault();
    console.log('📝 Submitting comment for post:', postId);
    
    const form = event.target;
    const input = form.querySelector('input[type=\"text\"]');
    const content = input.value.trim();
    
    if (!content) {
        console.log('❌ Empty comment content');
        return;
    }
    
    const success = await addComment(postId, content);
    if (success) {
        input.value = '';
        console.log('✅ Comment form cleared');
    }
}

async function addComment(postId, content) {
    console.log('➕ Adding comment:', content);
    
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
        console.log('📊 Add comment result:', result);
        
        if (result.success) {
            console.log('✅ Comment added successfully!');
            await loadComments(postId);
            return true;
        } else {
            console.log('❌ Comment not added:', result.message);
            return false;
        }
    } catch (error) {
        console.error('❌ Error adding comment:', error);
        return false;
    }
}

async function testAddComment(postId) {
    const testContent = '🧪 تعليق تجريبي - ' + new Date().toLocaleString();
    console.log('🧪 Adding test comment:', testContent);
    await addComment(postId, testContent);
}

function formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);
    
    if (diffInSeconds < 60) {
        return 'الآن';
    } else if (diffInSeconds < 3600) {
        const minutes = Math.floor(diffInSeconds / 60);
        return `منذ \${minutes} دقيقة`;
    } else if (diffInSeconds < 86400) {
        const hours = Math.floor(diffInSeconds / 3600);
        return `منذ \${hours} ساعة`;
    } else {
        const days = Math.floor(diffInSeconds / 86400);
        return `منذ \${days} يوم`;
    }
}

async function toggleCommentLike(commentId, element) {
    console.log('❤️ Toggling like for comment:', commentId);
    // تنفيذ مبسط للاختبار
    element.classList.toggle('liked');
}

function replyToComment(postId, username) {
    console.log('💬 Reply to:', username);
    const input = document.querySelector(`[data-post-id=\"\${postId}\"] .comment-input`);
    if (input) {
        input.value = `@\${username} `;
        input.focus();
    }
}

async function deleteComment(commentId, postId) {
    if (!confirm('هل تريد حذف هذا التعليق؟')) return;
    console.log('🗑️ Deleting comment:', commentId);
    // تنفيذ مبسط للاختبار
    document.getElementById(`comment-\${commentId}`).remove();
}

console.log('🚀 Debug script loaded');
</script>";

echo "</body></html>";
?> 