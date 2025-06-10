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
echo "<html><head><meta charset='UTF-8'><title>إصلاح طارئ للتعليقات</title>";
echo "<style>
body{font-family:Arial;padding:20px;background:#1a1a1a;color:white;} 
.container{max-width:800px;margin:0 auto;background:#2a2a2a;padding:20px;border-radius:10px;} 
.success{color:#4CAF50;} 
.error{color:#f44336;} 
.info{color:#2196F3;} 
.btn{padding:10px 20px;background:#007bff;color:white;border:none;border-radius:5px;cursor:pointer;margin:5px;} 
.btn:hover{background:#0056b3;}
.code{background:#333;padding:10px;border-radius:5px;margin:10px 0;font-family:monospace;white-space:pre-wrap;}
</style>";
echo "</head><body>";

echo "<div class='container'>";
echo "<h1>🚨 إصلاح طارئ للتعليقات</h1>";

$emergency_script = '
// إصلاح طارئ للتعليقات - تجاوز كل المشاكل
console.log("🚨 بدء الإصلاح الطارئ للتعليقات...");

// حذف كل الدوال القديمة وإعادة إنشائها
delete window.toggleComments;
delete window.loadComments;

// دالة إصلاح قوية لفتح/إغلاق التعليقات
window.toggleComments = function(postId) {
    console.log("🔥 EMERGENCY: Toggling comments for post:", postId);
    
    const commentsSection = document.getElementById("comments-" + postId);
    console.log("📦 Comments section element:", commentsSection);
    
    if (!commentsSection) {
        console.error("❌ EMERGENCY: Comments section not found!");
        alert("خطأ: قسم التعليقات غير موجود للمنشور " + postId);
        return;
    }
    
    // فحص الحالة الحالية
    const currentDisplay = window.getComputedStyle(commentsSection).display;
    const isHidden = currentDisplay === "none" || commentsSection.style.display === "none";
    
    console.log("👁️ Current display:", currentDisplay);
    console.log("🔍 Is hidden:", isHidden);
    
    if (isHidden) {
        console.log("👁️ EMERGENCY: Showing comments");
        commentsSection.style.display = "block";
        commentsSection.style.visibility = "visible";
        commentsSection.style.opacity = "1";
        
        // تحميل التعليقات
        window.loadComments(postId);
    } else {
        console.log("🙈 EMERGENCY: Hiding comments");
        commentsSection.style.display = "none";
    }
};

// دالة تحميل التعليقات المحسنة
window.loadComments = function(postId) {
    console.log("📥 EMERGENCY: Loading comments for post:", postId);
    
    const container = document.getElementById("comments-container-" + postId);
    if (!container) {
        console.error("❌ EMERGENCY: Comments container not found!");
        return;
    }
    
    // عرض مؤشر التحميل
    container.innerHTML = "<div style=\"text-align:center;padding:20px;color:#fff;background:#333;border-radius:5px;\">🔄 Loading comments...</div>";
    
    // استدعاء API
    fetch("api/social.php?action=get_comments&post_id=" + postId)
        .then(response => {
            console.log("📡 EMERGENCY: API response status:", response.status);
            return response.json();
        })
        .then(result => {
            console.log("📊 EMERGENCY: API result:", result);
            
            if (result.success && result.data && result.data.comments) {
                const comments = result.data.comments;
                console.log("💬 EMERGENCY: Found comments:", comments.length);
                
                if (comments.length === 0) {
                    container.innerHTML = "<div style=\"text-align:center;padding:20px;color:#ccc;\">No comments yet</div>";
                } else {
                    let html = "";
                    comments.forEach(comment => {
                        html += `
                            <div style="padding:15px;border-bottom:1px solid #444;background:#2a2a2a;margin:5px 0;border-radius:5px;">
                                <div style="display:flex;gap:10px;">
                                    <img src="${comment.avatar_url || "https://ui-avatars.com/api/?name=" + encodeURIComponent(comment.username) + "&background=667eea&color=fff&size=40"}" 
                                         alt="${comment.username}" style="width:40px;height:40px;border-radius:50%;object-fit:cover;">
                                    <div style="flex:1;">
                                        <div style="margin-bottom:5px;">
                                            <span style="font-weight:600;color:#fff;">${comment.first_name} ${comment.last_name}</span>
                                            <span style="color:#999;font-size:0.9rem;margin-left:10px;">@${comment.username}</span>
                                            <span style="color:#666;font-size:0.8rem;margin-left:10px;">${comment.created_at}</span>
                                        </div>
                                        <p style="color:#fff;margin:5px 0;">${comment.content}</p>
                                        <div style="margin-top:8px;">
                                            <button onclick="alert(\'Like feature coming soon!\')" style="background:none;border:none;color:#999;cursor:pointer;font-size:0.8rem;">
                                                ❤️ ${comment.like_count || 0}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    container.innerHTML = html;
                }
            } else {
                console.error("❌ EMERGENCY: API error:", result.message);
                container.innerHTML = "<div style=\"text-align:center;padding:20px;color:#f44;background:#333;border-radius:5px;\">❌ Failed to load comments: " + (result.message || "Unknown error") + "</div>";
            }
        })
        .catch(error => {
            console.error("❌ EMERGENCY: Network error:", error);
            container.innerHTML = "<div style=\"text-align:center;padding:20px;color:#f44;background:#333;border-radius:5px;\">❌ Network error: " + error.message + "</div>";
        });
};

// إعداد أزرار التعليقات بقوة
function setupEmergencyCommentButtons() {
    console.log("🔧 EMERGENCY: Setting up comment buttons...");
    
    // البحث عن جميع أزرار التعليقات
    const commentButtons = document.querySelectorAll("[data-action=\"comment\"]");
    console.log("🔍 EMERGENCY: Found comment buttons:", commentButtons.length);
    
    if (commentButtons.length === 0) {
        console.warn("⚠️ EMERGENCY: No comment buttons found! Searching alternatives...");
        
        // البحث البديل
        const alternativeButtons = document.querySelectorAll("button:contains(\"comment\"), .action-button");
        console.log("🔍 EMERGENCY: Alternative buttons found:", alternativeButtons.length);
    }
    
    commentButtons.forEach((btn, index) => {
        console.log(`🔘 EMERGENCY: Setting up button ${index + 1}`);
        
        // إزالة كل event listeners القديمة
        const newBtn = btn.cloneNode(true);
        btn.parentNode.replaceChild(newBtn, btn);
        
        // إضافة event listener جديد
        newBtn.addEventListener("click", function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            console.log("🖱️ EMERGENCY: Comment button clicked!");
            
            const postCard = this.closest("[data-post-id]");
            if (!postCard) {
                console.error("❌ EMERGENCY: Could not find post card!");
                alert("خطأ: لا يمكن العثور على المنشور");
                return;
            }
            
            const postId = postCard.getAttribute("data-post-id");
            console.log("🎯 EMERGENCY: Post ID found:", postId);
            
            if (!postId) {
                console.error("❌ EMERGENCY: Post ID is empty!");
                alert("خطأ: معرف المنشور فارغ");
                return;
            }
            
            // استدعاء دالة التبديل
            window.toggleComments(postId);
        });
    });
    
    console.log("✅ EMERGENCY: Comment buttons setup complete!");
}

// تشغيل الإعداد فوراً ومع تأخير
setupEmergencyCommentButtons();
setTimeout(setupEmergencyCommentButtons, 1000);
setTimeout(setupEmergencyCommentButtons, 3000);

// مراقبة التغييرات في DOM
if (window.MutationObserver) {
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === "childList") {
                setupEmergencyCommentButtons();
            }
        });
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
}

console.log("✅ EMERGENCY: إصلاح التعليقات الطارئ تم تحميله بنجاح!");

// إضافة أزرار اختبار مرئية
setTimeout(function() {
    const posts = document.querySelectorAll("[data-post-id]");
    posts.forEach(post => {
        const postId = post.getAttribute("data-post-id");
        if (postId) {
            const testBtn = document.createElement("button");
            testBtn.innerHTML = "🧪 Test Comments";
            testBtn.style.cssText = "background:#ff6b6b;color:white;border:none;padding:5px 10px;border-radius:3px;margin:5px;cursor:pointer;";
            testBtn.onclick = function() {
                console.log("🧪 TEST: Manual comment toggle for post", postId);
                window.toggleComments(postId);
            };
            
            const actionsDiv = post.querySelector(".post-actions");
            if (actionsDiv) {
                actionsDiv.appendChild(testBtn);
            }
        }
    });
}, 2000);
';

// حفظ السكريبت في ملف
file_put_contents('emergency-comments-fix.js', $emergency_script);

echo "<p class='success'>✅ تم إنشاء ملف الإصلاح الطارئ: emergency-comments-fix.js</p>";

echo "<h2>🚨 الإصلاح الطارئ:</h2>";
echo "<p>انسخ الكود التالي والصقه في Console في صفحة u.php:</p>";

echo "<div class='code'>" . htmlspecialchars($emergency_script) . "</div>";

echo "<h2>📋 خطوات الإصلاح:</h2>";
echo "<ol>";
echo "<li>افتح صفحة u.php</li>";
echo "<li>اضغط F12 لفتح Developer Tools</li>";
echo "<li>انتقل إلى تبويب Console</li>";
echo "<li>انسخ والصق الكود أعلاه</li>";
echo "<li>اضغط Enter</li>";
echo "<li>جرب النقر على زر التعليقات أو الزر الأحمر 🧪 Test Comments</li>";
echo "</ol>";

echo "<h2>🔗 اختبار مباشر:</h2>";
echo "<button onclick='openWithFix()' class='btn'>فتح u.php مع الإصلاح</button>";

echo "<script>
function openWithFix() {
    const script = `$emergency_script`;
    
    // فتح صفحة جديدة
    const newWindow = window.open('u.php', '_blank');
    
    // انتظار تحميل الصفحة ثم حقن الإصلاح
    newWindow.onload = function() {
        setTimeout(function() {
            try {
                newWindow.eval(script);
                console.log('✅ تم حقن الإصلاح الطارئ بنجاح!');
            } catch (error) {
                console.error('❌ فشل حقن الإصلاح:', error);
            }
        }, 1000);
    };
}
</script>";

echo "<hr>";
echo "<h2>🔗 روابط مفيدة:</h2>";
echo "<p><a href='u.php' target='_blank' style='color:#007bff;'>🔗 افتح صفحة u.php</a></p>";
echo "<p><a href='emergency-comments-fix.js' target='_blank' style='color:#007bff;'>🔗 عرض ملف الإصلاح</a></p>";

echo "</div>";
echo "</body></html>";
?> 