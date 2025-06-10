<?php
echo "🔧 إصلاح مشكلة عرض التعليقات في u.php\n\n";

$u_content = file_get_contents('u.php');

if (!$u_content) {
    echo "❌ خطأ: لا يمكن قراءة ملف u.php\n";
    exit;
}

echo "✅ تم قراءة ملف u.php بنجاح\n";

if (!strpos($u_content, 'meta name="user-id"')) {
    echo "❌ meta tag للمستخدم مفقود\n";
} else {
    echo "✅ meta tag للمستخدم موجود\n";
}

if (strpos($u_content, 'async function loadComments(postId)') === false) {
    echo "❌ دالة loadComments مفقودة\n";
} else {
    echo "✅ دالة loadComments موجودة\n";
}

if (strpos($u_content, 'function formatDate(dateString)') === false) {
    echo "❌ دالة formatDate مفقودة\n";
} else {
    echo "✅ دالة formatDate موجودة\n";
}

$comments_html_pattern = '/id=["\']comments-\$\{postId\}["\'].*?class=["\']comments-section["\'].*?style=["\']display:\s*none["\'].*?>/';
if (!preg_match($comments_html_pattern, $u_content)) {
    echo "⚠️ تحذير: قد تكون هناك مشكلة في HTML الخاص بالتعليقات\n";
} else {
    echo "✅ HTML التعليقات يبدو صحيحاً\n";
}

if (strpos($u_content, 'async function toggleComments(postId)') === false) {
    echo "❌ دالة toggleComments مفقودة\n";
} else {
    echo "✅ دالة toggleComments موجودة\n";
}

$simple_fix = '
<!-- إضافة هذا الكود في نهاية u.php قبل </body> -->
<script>
// إصلاح مشكلة عرض التعليقات
document.addEventListener("DOMContentLoaded", function() {
    console.log("🔧 تطبيق إصلاح التعليقات...");
    
    // التأكد من وجود meta tag
    if (!document.querySelector(\'meta[name="user-id"]\')) {
        const meta = document.createElement("meta");
        meta.name = "user-id";
        meta.content = "' . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '11') . '";
        document.head.appendChild(meta);
        console.log("✅ تم إضافة meta tag للمستخدم");
    }
    
    // إصلاح دالة loadComments إذا كانت معطلة
    if (typeof loadComments === "undefined") {
        window.loadComments = async function(postId) {
            console.log("🔄 تحميل التعليقات للمنشور:", postId);
            const container = document.getElementById(`comments-container-${postId}`);
            
            if (!container) {
                console.log("❌ حاوية التعليقات غير موجودة");
                return;
            }
            
            try {
                const response = await fetch(`api/social.php?action=get_comments&post_id=${postId}`);
                const result = await response.json();
                
                if (result.success && result.data.comments) {
                    if (result.data.comments.length === 0) {
                        container.innerHTML = \'<div class="text-center py-4 text-gray-400">لا توجد تعليقات بعد</div>\';
                    } else {
                        const currentUserId = parseInt(document.querySelector(\'meta[name="user-id"]\').content);
                        container.innerHTML = result.data.comments.map(comment => `
                            <div class="comment-item" id="comment-${comment.id}">
                                <div class="flex gap-3">
                                    <img src="${comment.avatar_url || \'https://ui-avatars.com/api/?name=\' + encodeURIComponent(comment.username) + \'&background=667eea&color=fff&size=80\'}" 
                                         alt="${comment.username}" class="w-10 h-10 rounded-full object-cover">
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between mb-1">
                                            <div class="flex items-center gap-2">
                                                <span class="font-semibold text-white">${comment.first_name} ${comment.last_name}</span>
                                                <span class="text-gray-400 text-sm">@${comment.username}</span>
                                                <span class="text-gray-500 text-xs">${formatDate ? formatDate(comment.created_at) : comment.created_at}</span>
                                            </div>
                                        </div>
                                        <p class="text-white mb-2">${comment.content}</p>
                                        <div class="flex gap-2">
                                            <button class="action-button text-xs">
                                                <i class="bi bi-heart"></i>
                                                <span>${comment.like_count || 0}</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `).join(\'\');
                    }
                } else {
                    container.innerHTML = \'<div class="text-center py-4 text-gray-400">خطأ في تحميل التعليقات</div>\';
                }
            } catch (error) {
                console.error("❌ خطأ في تحميل التعليقات:", error);
                container.innerHTML = \'<div class="text-center py-4 text-gray-400">خطأ في الاتصال</div>\';
            }
        };
        console.log("✅ تم إصلاح دالة loadComments");
    }
    
    // إصلاح دالة toggleComments إذا كانت معطلة
    if (typeof toggleComments === "undefined") {
        window.toggleComments = async function(postId) {
            console.log("🔄 تبديل عرض التعليقات للمنشور:", postId);
            const commentsSection = document.getElementById(`comments-${postId}`);
            
            if (!commentsSection) {
                console.log("❌ قسم التعليقات غير موجود");
                return;
            }
            
            if (commentsSection.style.display === "none" || commentsSection.style.display === "") {
                commentsSection.style.display = "block";
                await loadComments(postId);
            } else {
                commentsSection.style.display = "none";
            }
        };
        console.log("✅ تم إصلاح دالة toggleComments");
    }
    
    console.log("🎉 تم تطبيق جميع الإصلاحات بنجاح!");
});
</script>
';

file_put_contents('comments_fix_snippet.html', $simple_fix);
echo "\n✅ تم إنشاء ملف الإصلاح: comments_fix_snippet.html\n";

echo "\n📋 ملخص الإصلاحات المطلوبة:\n";
echo "1. ✅ التأكد من وجود meta tag للمستخدم\n";
echo "2. ✅ إصلاح دالة loadComments\n";
echo "3. ✅ إصلاح دالة toggleComments\n";
echo "4. ✅ إضافة معالجة أخطاء محسنة\n";
echo "5. ✅ تحسين عرض التعليقات\n";

echo "\n🎯 الخطوات التالية:\n";
echo "1. افتح u.php\n";
echo "2. ابحث عن </body>\n";
echo "3. أضف محتوى ملف comments_fix_snippet.html قبل </body>\n";
echo "4. احفظ الملف واختبر التعليقات\n";

echo "\n🔗 أو استخدم الرابط للاختبار المباشر:\n";
echo "http://localhost/WEP/debug_comments_display.php\n";
?> 