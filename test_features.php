<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اختبار الميزات الجديدة</title>
    <meta name="user-id" content="<?php echo $user_id; ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { direction: rtl; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .test-section { margin-bottom: 30px; padding: 20px; border: 1px solid #ddd; border-radius: 10px; }
        .image-preview-container { max-width: 300px; margin: 10px 0; position: relative; }
        .preview-image { width: 100%; border-radius: 5px; }
        .remove-image { position: absolute; top: 5px; right: 5px; background: red; color: white; border: none; border-radius: 50%; width: 25px; height: 25px; }
        .image-info { margin-top: 5px; font-size: 0.9em; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="text-center mb-4">🧪 اختبار الميزات الجديدة</h1>
        
        <div class="test-section">
            <h3><i class="bi bi-plus-circle"></i> اختبار نشر المنشورات</h3>
            <form id="postForm">
                <div class="mb-3">
                    <textarea class="form-control" id="postContent" name="content" rows="3" placeholder="ما الذي تفكر فيه؟"></textarea>
                </div>
                
                <div id="imagePreview" style="display: none;"></div>
                
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('postImage').click()">
                            <i class="bi bi-image"></i> صورة
                        </button>
                        <input type="file" id="postImage" name="image" accept="image/*" style="display: none;">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send"></i> نشر
                    </button>
                </div>
            </form>
        </div>

        <div class="test-section">
            <h3><i class="bi bi-people"></i> اختبار المتابعة</h3>
            <div class="d-flex align-items-center justify-content-between p-3 border rounded">
                <div class="d-flex align-items-center">
                    <img src="https://ui-avatars.com/api/?name=Test+User&background=random" class="rounded-circle me-3" width="50" height="50">
                    <div>
                        <h6 class="mb-0">مستخدم تجريبي</h6>
                        <small class="text-muted">@testuser</small>
                    </div>
                </div>
                <button class="btn btn-primary follow-btn" data-user-id="2">
                    <i class="bi bi-person-plus"></i> <span class="follow-text">متابعة</span>
                </button>
            </div>
        </div>

        <div class="test-section">
            <h3><i class="bi bi-heart"></i> اختبار الإعجاب</h3>
            <div class="card">
                <div class="card-body">
                    <p class="card-text">هذا منشور تجريبي لاختبار ميزة الإعجاب</p>
                    <div class="d-flex gap-3">
                        <button class="btn btn-outline-danger like-btn" data-post-id="1">
                            <i class="bi bi-heart"></i> <span class="like-count">0</span>
                        </button>
                        <button class="btn btn-outline-primary bookmark-btn" data-post-id="1">
                            <i class="bi bi-bookmark"></i> حفظ
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="test-section">
            <h3><i class="bi bi-collection"></i> المنشورات</h3>
            <div id="postsContainer">
                <p class="text-muted text-center">لا توجد منشورات حتى الآن. جرب نشر منشور أعلاه!</p>
            </div>
        </div>

        <div id="messageContainer"></div>
    </div>

    <script src="assets/js/app-enhanced.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 صفحة اختبار الميزات جاهزة!');
            console.log('معرف المستخدم الحالي:', window.currentUserId);
            
            if (window.socialApp) {
                console.log('✅ SocialApp متوفر ومُهيأ');
            } else {
                console.log('❌ SocialApp غير متوفر');
            }
        });

        function testFollow() {
            const followBtn = document.querySelector('.follow-btn');
            if (window.socialApp) {
                window.socialApp.toggleFollow(2, followBtn);
            } else {
                alert('SocialApp غير متوفر');
            }
        }

        function testLike() {
            const likeBtn = document.querySelector('.like-btn');
            if (window.socialApp) {
                window.socialApp.toggleLike(1, likeBtn);
            } else {
                alert('SocialApp غير متوفر');
            }
        }

        document.querySelector('.follow-btn').addEventListener('click', testFollow);
        document.querySelector('.like-btn').addEventListener('click', testLike);
    </script>
</body>
</html> 