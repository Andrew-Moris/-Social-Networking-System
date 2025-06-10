<?php
session_start();
require_once 'config.php';

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'><title>إصلاح عرض التعليقات</title>";
echo "<style>
body{font-family:Arial;padding:20px;background:#1a1a1a;color:white;} 
.container{max-width:800px;margin:0 auto;background:#2a2a2a;padding:20px;border-radius:10px;} 
.success{color:#4CAF50;} 
.error{color:#f44336;} 
.info{color:#2196F3;} 
.btn{padding:10px 20px;background:#007bff;color:white;border:none;border-radius:5px;cursor:pointer;margin:5px;} 
.btn:hover{background:#0056b3;}
</style>";
echo "</head><body>";

echo "<div class='container'>";
echo "<h1>🔧 إصلاح عرض التعليقات في u.php</h1>";

$js_fix = "
// إصلاح التعليقات في u.php
console.log('🔧 تطبيق إصلاح التعليقات...');

// إصلاح دالة toggleComments
window.toggleComments = async function(postId) {
    console.log('🔄 Toggling comments for post:', postId);
    
    const commentsSection = document.getElementById(`comments-\${postId}`);
    console.log('📦 Comments section found:', !!commentsSection);
    
    if (commentsSection) {
        const isHidden = commentsSection.style.display === 'none' || commentsSection.style.display === '';
        console.log('👁️ Is hidden:', isHidden);
        
        if (isHidden) {
            console.log('👁️ Showing comments section');
            commentsSection.style.display = 'block';
            await loadComments(postId);
        } else {
            console.log('🙈 Hiding comments section');
            commentsSection.style.display = 'none';
        }
    } else {
        console.error('❌ Comments section not found for post:', postId);
    }
};

// إصلاح دالة loadComments
window.loadComments = async function(postId) {
    console.log('📥 Loading comments for post:', postId);
    
    const container = document.getElementById(`comments-container-\${postId}`);
    console.log('📦 Container found:', !!container);
    
    if (!container) {
        console.error('❌ Comments container not found for post:', postId);
        return;
    }
    
    // عرض مؤشر التحميل
    container.innerHTML = '<div style=\"text-align:center;padding:20px;color:#ccc;\"><i class=\"bi bi-hourglass-split\"></i> Loading comments...</div>';
    
    try {
        const response = await fetch(`api/social.php?action=get_comments&post_id=\${postId}`);
        console.log('📡 API Response status:', response.status);
        
        const result = await response.json();
        console.log('📊 API Result:', result);
        
        if (result.success && result.data && result.data.comments) {
            console.log('💬 Found comments:', result.data.comments.length);
            
            if (result.data.comments.length === 0) {
                container.innerHTML = '<div style=\"text-align:center;padding:20px;color:#ccc;\">No comments yet</div>';
            } else {
                const currentUserId = parseInt(document.querySelector('meta[name=\"user-id\"]').content) || 0;
                console.log('👤 Current user ID:', currentUserId);
                
                container.innerHTML = result.data.comments.map(comment => `
                    <div style='padding:15px;border-bottom:1px solid rgba(255,255,255,0.1);'>
                        <div style='display:flex;gap:10px;'>
                            <img src='\${comment.avatar_url || \"https://ui-avatars.com/api/?name=\" + encodeURIComponent(comment.username) + \"&background=667eea&color=fff&size=40\"}' 
                                 alt='\${comment.username}' style='width:40px;height:40px;border-radius:50%;object-fit:cover;'>
                            <div style='flex:1;'>
                                <div style='display:flex;align-items:center;gap:10px;margin-bottom:5px;'>
                                    <span style='font-weight:600;color:white;'>\${comment.first_name} \${comment.last_name}</span>
                                    <span style='color:#999;font-size:0.9rem;'>@\${comment.username}</span>
                                    <span style='color:#666;font-size:0.8rem;'>\${comment.created_at}</span>
                                </div>
                                <p style='color:white;margin:5px 0;'>\${comment.content}</p>
                                <div style='display:flex;gap:10px;margin-top:8px;'>
                                    <button onclick='toggleCommentLike(\${comment.id}, this)' style='background:none;border:none;color:#999;cursor:pointer;font-size:0.8rem;'>
                                        <i class='bi bi-heart\${comment.user_liked ? \"-fill\" : \"\"}'></i>
                                        <span>\${comment.like_count}</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `).join('');
            }
        } else {
            console.log('❌ API returned error or no data:', result.message || 'Unknown error');
            container.innerHTML = '<div style=\"text-align:center;padding:20px;color:#f44;\">❌ Failed to load comments</div>';
        }
    } catch (error) {
        console.error('❌ Error loading comments:', error);
        container.innerHTML = '<div style=\"text-align:center;padding:20px;color:#f44;\">❌ Error loading comments</div>';
    }
};

// إصلاح event listeners
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 Setting up comment button listeners...');
    
    // إزالة event listeners القديمة وإضافة جديدة
    const commentButtons = document.querySelectorAll('[data-action=\"comment\"]');
    console.log('🔍 Found comment buttons:', commentButtons.length);
    
    commentButtons.forEach((btn, index) => {
        console.log(`🔘 Setting up comment button \${index + 1}`);
        
        // إزالة onclick القديم
        btn.removeAttribute('onclick');
        
        // إضافة event listener جديد
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const postCard = this.closest('[data-post-id]');
            if (!postCard) {
                console.error('❌ Could not find post card for comment button');
                return;
            }
            
            const postId = postCard.getAttribute('data-post-id');
            console.log('🔄 Opening comments for post:', postId);
            
            toggleComments(postId);
        });
    });
    
    console.log('✅ Comment buttons setup complete!');
});

console.log('✅ إصلاح التعليقات تم تحميله بنجاح!');
";

file_put_contents('js/comments-fix.js', $js_fix);

echo "<p class='success'>✅ تم إنشاء ملف الإصلاح: js/comments-fix.js</p>";

$injection_script = "
<script>
// حقن إصلاح التعليقات مباشرة
(function() {
    console.log('🚀 بدء حقن إصلاح التعليقات...');
    
    // التأكد من وجود meta tag
    if (!document.querySelector('meta[name=\"user-id\"]')) {
        const meta = document.createElement('meta');
        meta.name = 'user-id';
        meta.content = '" . ($_SESSION['user_id'] ?? '0') . "';
        document.head.appendChild(meta);
        console.log('✅ تم إضافة meta tag للمستخدم');
    }
    
    // إصلاح دالة toggleComments
    window.toggleComments = async function(postId) {
        console.log('🔄 Toggling comments for post:', postId);
        
        const commentsSection = document.getElementById(`comments-\${postId}`);
        console.log('📦 Comments section found:', !!commentsSection);
        
        if (commentsSection) {
            const isHidden = commentsSection.style.display === 'none' || commentsSection.style.display === '';
            console.log('👁️ Is hidden:', isHidden);
            
            if (isHidden) {
                console.log('👁️ Showing comments section');
                commentsSection.style.display = 'block';
                await window.loadComments(postId);
            } else {
                console.log('🙈 Hiding comments section');
                commentsSection.style.display = 'none';
            }
        } else {
            console.error('❌ Comments section not found for post:', postId);
        }
    };
    
    // إصلاح دالة loadComments
    window.loadComments = async function(postId) {
        console.log('📥 Loading comments for post:', postId);
        
        const container = document.getElementById(`comments-container-\${postId}`);
        console.log('📦 Container found:', !!container);
        
        if (!container) {
            console.error('❌ Comments container not found for post:', postId);
            return;
        }
        
        // عرض مؤشر التحميل
        container.innerHTML = '<div style=\"text-align:center;padding:20px;color:#ccc;\"><i class=\"bi bi-hourglass-split\"></i> Loading comments...</div>';
        
        try {
            const response = await fetch(`api/social.php?action=get_comments&post_id=\${postId}`);
            console.log('📡 API Response status:', response.status);
            
            const result = await response.json();
            console.log('📊 API Result:', result);
            
            if (result.success && result.data && result.data.comments) {
                console.log('💬 Found comments:', result.data.comments.length);
                
                if (result.data.comments.length === 0) {
                    container.innerHTML = '<div style=\"text-align:center;padding:20px;color:#ccc;\">No comments yet</div>';
                } else {
                    const currentUserId = parseInt(document.querySelector('meta[name=\"user-id\"]').content) || 0;
                    console.log('👤 Current user ID:', currentUserId);
                    
                    container.innerHTML = result.data.comments.map(comment => `
                        <div style='padding:15px;border-bottom:1px solid rgba(255,255,255,0.1);'>
                            <div style='display:flex;gap:10px;'>
                                <img src='\${comment.avatar_url || \"https://ui-avatars.com/api/?name=\" + encodeURIComponent(comment.username) + \"&background=667eea&color=fff&size=40\"}' 
                                     alt='\${comment.username}' style='width:40px;height:40px;border-radius:50%;object-fit:cover;'>
                                <div style='flex:1;'>
                                    <div style='display:flex;align-items:center;gap:10px;margin-bottom:5px;'>
                                        <span style='font-weight:600;color:white;'>\${comment.first_name} \${comment.last_name}</span>
                                        <span style='color:#999;font-size:0.9rem;'>@\${comment.username}</span>
                                        <span style='color:#666;font-size:0.8rem;'>\${comment.created_at}</span>
                                    </div>
                                    <p style='color:white;margin:5px 0;'>\${comment.content}</p>
                                    <div style='display:flex;gap:10px;margin-top:8px;'>
                                        <button onclick='toggleCommentLike(\${comment.id}, this)' style='background:none;border:none;color:#999;cursor:pointer;font-size:0.8rem;'>
                                            <i class='bi bi-heart\${comment.user_liked ? \"-fill\" : \"\"}'></i>
                                            <span>\${comment.like_count}</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `).join('');
                }
            } else {
                console.log('❌ API returned error or no data:', result.message || 'Unknown error');
                container.innerHTML = '<div style=\"text-align:center;padding:20px;color:#f44;\">❌ Failed to load comments</div>';
            }
        } catch (error) {
            console.error('❌ Error loading comments:', error);
            container.innerHTML = '<div style=\"text-align:center;padding:20px;color:#f44;\">❌ Error loading comments</div>';
        }
    };
    
    // إعداد event listeners للأزرار
    function setupCommentButtons() {
        const commentButtons = document.querySelectorAll('[data-action=\"comment\"]');
        console.log('🔍 Found comment buttons:', commentButtons.length);
        
        commentButtons.forEach((btn, index) => {
            console.log(`🔘 Setting up comment button \${index + 1}`);
            
            // إزالة onclick القديم
            btn.removeAttribute('onclick');
            
            // إضافة event listener جديد
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const postCard = this.closest('[data-post-id]');
                if (!postCard) {
                    console.error('❌ Could not find post card for comment button');
                    return;
                }
                
                const postId = postCard.getAttribute('data-post-id');
                console.log('🔄 Opening comments for post:', postId);
                
                window.toggleComments(postId);
            });
        });
        
        console.log('✅ Comment buttons setup complete!');
    }
    
    // تشغيل الإعداد عند تحميل الصفحة
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setupCommentButtons);
    } else {
        setupCommentButtons();
    }
    
    console.log('✅ إصلاح التعليقات تم تحميله بنجاح!');
})();
</script>
";

file_put_contents('comments-injection.html', $injection_script);

echo "<p class='success'>✅ تم إنشاء ملف الحقن: comments-injection.html</p>";

echo "<h2>📋 تعليمات الاستخدام:</h2>";
echo "<ol>";
echo "<li>انسخ محتوى ملف comments-injection.html</li>";
echo "<li>الصقه في نهاية صفحة u.php قبل إغلاق tag body</li>";
echo "<li>أو افتح صفحة u.php وأضف هذا السكريبت في Developer Tools Console</li>";
echo "</ol>";

echo "<h2>🔗 اختبار سريع:</h2>";
echo "<button onclick='testInBrowser()' class='btn'>اختبار في المتصفح</button>";

echo "<script>
function testInBrowser() {
    // فتح صفحة u.php مع حقن الإصلاح
    const newWindow = window.open('u.php', '_blank');
    
    newWindow.addEventListener('load', function() {
        // حقن الإصلاح
        const script = document.createElement('script');
        script.textContent = `$injection_script`;
        newWindow.document.head.appendChild(script);
        
        console.log('✅ تم حقن الإصلاح في صفحة u.php');
    });
}
</script>";

echo "<hr>";
echo "<h2>🔗 روابط مفيدة:</h2>";
echo "<p><a href='u.php' target='_blank' style='color:#007bff;'>🔗 افتح صفحة u.php</a></p>";
echo "<p><a href='comments-injection.html' target='_blank' style='color:#007bff;'>🔗 عرض ملف الحقن</a></p>";

echo "</div>";
echo "</body></html>";
?> 