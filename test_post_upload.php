<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['content']) || isset($_FILES['media'])) {
        $content = trim($_POST['content'] ?? '');
        $media_url = isset($_POST['media_url']) ? $_POST['media_url'] : '';
        
        if (isset($_FILES['media']) && $_FILES['media']['error'] === UPLOAD_ERR_OK && empty($media_url)) {
            $upload_dir = __DIR__ . '/uploads/posts/';
            
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['media']['name'], PATHINFO_EXTENSION));
            $file_name = 'post_' . time() . '_' . uniqid() . '.' . $file_extension;
            $file_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['media']['tmp_name'], $file_path)) {
                $media_url = 'uploads/posts/' . $file_name;
                $message = '<div class="alert alert-success">تم رفع الملف بنجاح: ' . htmlspecialchars($file_name) . '</div>';
            } else {
                $message = '<div class="alert alert-danger">فشل في رفع الملف</div>';
            }
        }
        
        if (!empty($content) || !empty($media_url)) {
            $message .= '<div class="alert alert-success">تم إنشاء المنشور بنجاح</div>';
            
            $message .= '<div class="card mt-4">
                <div class="card-header">منشور جديد</div>
                <div class="card-body">';
            
            if (!empty($content)) {
                $message .= '<p>' . nl2br(htmlspecialchars($content)) . '</p>';
            }
            
            if (!empty($media_url)) {
                $message .= '<div class="mt-3"><img src="' . htmlspecialchars($media_url) . '" class="img-fluid rounded" style="max-height: 300px;"></div>';
            }
            
            $message .= '</div></div>';
        }
    } else {
        $message = '<div class="alert alert-danger">يرجى إدخال محتوى أو إرفاق صورة</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اختبار رفع صور المنشورات</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #1a1f2e;
            color: #e5e7eb;
            font-family: 'Cairo', sans-serif;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: rgba(26, 31, 46, 0.7);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
        }
        .form-control {
            background-color: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
            color: white;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .alert-success {
            background-color: rgba(72, 187, 120, 0.2);
            color: #48bb78;
            border-color: rgba(72, 187, 120, 0.3);
        }
        .alert-danger {
            background-color: rgba(245, 101, 101, 0.2);
            color: #f56565;
            border-color: rgba(245, 101, 101, 0.3);
        }
        .card {
            background-color: rgba(30, 36, 53, 0.7);
            border-color: rgba(255, 255, 255, 0.1);
        }
        .card-header {
            background-color: rgba(40, 46, 63, 0.7);
            border-color: rgba(255, 255, 255, 0.1);
        }
        #media-preview {
            max-width: 100%;
            margin-top: 10px;
            display: none;
        }
        #media-preview img {
            max-height: 200px;
            border-radius: 8px;
        }
        #progress-container {
            display: none;
            margin-top: 10px;
        }
        .progress {
            height: 20px;
            background-color: rgba(255, 255, 255, 0.1);
        }
        .progress-bar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4 text-center">اختبار رفع صور المنشورات</h1>
        
        <?php echo $message; ?>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">إنشاء منشور جديد</h5>
            </div>
            <div class="card-body">
                <form id="post-form" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="post-content" class="form-label">محتوى المنشور:</label>
                        <textarea id="post-content" name="content" class="form-control" rows="4" placeholder="ماذا يدور في ذهنك؟"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="post-media" class="form-label">إرفاق صورة أو فيديو:</label>
                        <input type="file" id="post-media" name="media" class="form-control" accept="image/*,video/*">
                    </div>
                    
                    <div id="media-preview" class="mb-3">
                    </div>
                    
                    <div id="progress-container" class="mb-3">
                        <label class="form-label">تقدم الرفع:</label>
                        <div class="progress">
                            <div id="upload-progress" class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <button type="button" id="remove-media" class="btn btn-outline-danger" style="display: none;">
                            <i class="bi bi-x-circle"></i> إزالة الوسائط
                        </button>
                        
                        <button type="submit" id="submit-post" class="btn btn-primary">
                            <i class="bi bi-send-fill"></i> نشر
                        </button>
                    </div>
                    
                    <input type="hidden" id="media-url-input" name="media_url" value="">
                </form>
            </div>
        </div>
        
        <div class="text-center">
            <a href="u.php" class="btn btn-outline-light">العودة إلى الملف الشخصي</a>
        </div>
        
        <div class="mt-4">
            <h5>معلومات النظام:</h5>
            <ul class="list-group bg-transparent">
                <li class="list-group-item bg-transparent">PHP version: <?php echo phpversion(); ?></li>
                <li class="list-group-item bg-transparent">upload_max_filesize: <?php echo ini_get('upload_max_filesize'); ?></li>
                <li class="list-group-item bg-transparent">post_max_size: <?php echo ini_get('post_max_size'); ?></li>
                <li class="list-group-item bg-transparent">max_file_uploads: <?php echo ini_get('max_file_uploads'); ?></li>
                <li class="list-group-item bg-transparent">
                    Uploads directory writable: 
                    <?php 
                    $test_dir = __DIR__ . '/uploads/posts/';
                    if (!file_exists($test_dir)) {
                        echo 'Directory does not exist';
                    } else {
                        echo is_writable($test_dir) ? 'Yes' : 'No';
                    }
                    ?>
                </li>
            </ul>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mediaInput = document.getElementById('post-media');
            const mediaPreview = document.getElementById('media-preview');
            const removeMediaBtn = document.getElementById('remove-media');
            const mediaUrlInput = document.getElementById('media-url-input');
            const progressContainer = document.getElementById('progress-container');
            const progressBar = document.getElementById('upload-progress');
            const submitBtn = document.getElementById('submit-post');
            
            if (mediaInput) {
                mediaInput.addEventListener('change', function(event) {
                    const file = event.target.files[0];
                    if (!file) return;
                    
                    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'video/webm'];
                    if (!allowedTypes.includes(file.type)) {
                        alert('نوع الملف غير مدعوم. الرجاء اختيار صورة أو فيديو.');
                        mediaInput.value = '';
                        return;
                    }
                    
                    const maxSize = 10 * 1024 * 1024;
                    if (file.size > maxSize) {
                        alert('حجم الملف كبير جدًا. الحد الأقصى هو 10 ميجابايت.');
                        mediaInput.value = '';
                        return;
                    }
                    
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        mediaPreview.innerHTML = '';
                        
                        if (file.type.startsWith('image/')) {
                            const img = document.createElement('img');
                            img.src = e.target.result;
                            img.className = 'img-fluid';
                            mediaPreview.appendChild(img);
                        } else if (file.type.startsWith('video/')) {
                            const video = document.createElement('video');
                            video.src = e.target.result;
                            video.className = 'img-fluid';
                            video.controls = true;
                            mediaPreview.appendChild(video);
                        }
                        
                        mediaPreview.style.display = 'block';
                        removeMediaBtn.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                    
                    uploadMedia(file);
                });
            }
            
            if (removeMediaBtn) {
                removeMediaBtn.addEventListener('click', function() {
                    mediaInput.value = '';
                    mediaPreview.innerHTML = '';
                    mediaPreview.style.display = 'none';
                    removeMediaBtn.style.display = 'none';
                    mediaUrlInput.value = '';
                });
            }
            
            function uploadMedia(file) {
                progressContainer.style.display = 'block';
                progressBar.style.width = '0%';
                progressBar.textContent = '0%';
                
                submitBtn.disabled = true;
                
                const formData = new FormData();
                formData.append('media', file);
                
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'api/post_media_upload.php', true);
                
                xhr.upload.onprogress = function(e) {
                    if (e.lengthComputable) {
                        const percentComplete = Math.round((e.loaded / e.total) * 100);
                        progressBar.style.width = percentComplete + '%';
                        progressBar.textContent = percentComplete + '%';
                    }
                };
                
                xhr.onload = function() {
                    progressContainer.style.display = 'none';
                    
                    submitBtn.disabled = false;
                    
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                console.log('تم رفع الوسائط بنجاح:', response);
                                mediaUrlInput.value = response.media_url;
                            } else {
                                console.error('فشل رفع الوسائط:', response.message);
                                alert('فشل رفع الوسائط: ' + response.message);
                                mediaInput.value = '';
                                mediaPreview.innerHTML = '';
                                mediaPreview.style.display = 'none';
                                removeMediaBtn.style.display = 'none';
                            }
                        } catch (e) {
                            console.error('خطأ في تحليل استجابة الخادم:', e);
                            alert('حدث خطأ في تحليل استجابة الخادم');
                        }
                    } else {
                        console.error('خطأ في الاتصال بالخادم:', xhr.status);
                        alert('حدث خطأ في الاتصال بالخادم');
                    }
                };
                
                xhr.onerror = function() {
                    console.error('خطأ في الاتصال بالخادم');
                    alert('حدث خطأ في الاتصال بالخادم');
                    progressContainer.style.display = 'none';
                    submitBtn.disabled = false;
                };
                
                xhr.send(formData);
            }
        });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
