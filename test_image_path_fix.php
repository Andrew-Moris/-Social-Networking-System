<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
}

$posts = [];
try {
    $host = 'localhost';
    $dbname = 'wep_db';
    $user_db = 'root';
    $password = '';
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $user_db, $password, $options);
    
    $stmt = $pdo->prepare("
        SELECT p.*, u.username, u.avatar_url 
        FROM posts p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.media_url IS NOT NULL AND p.media_url != '' 
        ORDER BY p.created_at DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Found " . count($posts) . " posts with media");
    
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اختبار إصلاح مسارات الصور</title>
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
        .card {
            background-color: rgba(30, 36, 53, 0.7);
            border-color: rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }
        .card-header {
            background-color: rgba(40, 46, 63, 0.7);
            border-color: rgba(255, 255, 255, 0.1);
        }
        .post-media {
            max-width: 100%;
            border-radius: 8px;
            margin-top: 10px;
        }
        .media-info {
            background-color: rgba(0, 0, 0, 0.2);
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            font-family: monospace;
            font-size: 12px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .test-section {
            margin-bottom: 30px;
            padding: 15px;
            background-color: rgba(0, 0, 0, 0.2);
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4 text-center">اختبار إصلاح مسارات الصور</h1>
        
        <div class="test-section">
            <h2>اختبار رفع صورة جديدة</h2>
            <form id="upload-form" enctype="multipart/form-data" class="mb-4">
                <div class="mb-3">
                    <label for="media" class="form-label">اختر صورة للرفع:</label>
                    <input type="file" id="media" name="media" class="form-control" accept="image/*">
                </div>
                <div id="preview-container" style="display: none;" class="mb-3">
                    <h5>معاينة:</h5>
                    <img id="image-preview" class="img-fluid rounded" style="max-height: 200px;">
                </div>
                <div id="progress-container" style="display: none;" class="mb-3">
                    <div class="progress">
                        <div id="upload-progress" class="progress-bar" role="progressbar" style="width: 0%"></div>
                    </div>
                </div>
                <div id="result-container" style="display: none;" class="mb-3 media-info">
                </div>
                <button type="submit" id="upload-btn" class="btn btn-primary">
                    <i class="bi bi-cloud-upload"></i> رفع الصورة
                </button>
            </form>
        </div>
        
        <div class="test-section">
            <h2>المنشورات الحالية مع صور</h2>
            <div id="posts-container">
                <?php if (empty($posts)): ?>
                    <div class="alert alert-info">لا توجد منشورات تحتوي على صور</div>
                <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                        <div class="card mb-3">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <img src="<?= htmlspecialchars($post['avatar_url'] ?? 'assets/images/default-avatar.png') ?>" 
                                         alt="<?= htmlspecialchars($post['username']) ?>" 
                                         class="rounded-circle" style="width: 30px; height: 30px;">
                                    <span class="ms-2"><?= htmlspecialchars($post['username']) ?></span>
                                </div>
                                <small><?= date('Y-m-d H:i', strtotime($post['created_at'])) ?></small>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($post['content'])): ?>
                                    <p><?= nl2br(htmlspecialchars($post['content'])) ?></p>
                                <?php endif; ?>
                                
                                <?php if (!empty($post['media_url'])): ?>
                                    <div>
                                        <h6>الصورة الأصلية (كما هي في قاعدة البيانات):</h6>
                                        <img src="<?= htmlspecialchars($post['media_url']) ?>" class="post-media">
                                        <div class="media-info">
                                            مسار الصورة: <?= htmlspecialchars($post['media_url']) ?>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-4">
                                        <h6>الصورة مع إصلاح المسار:</h6>
                                        <img src="<?= !str_starts_with($post['media_url'], '/') && !str_starts_with($post['media_url'], 'http') ? '/WEP/' . $post['media_url'] : $post['media_url'] ?>" 
                                             class="post-media fixed-image">
                                        <div class="media-info">
                                            مسار الصورة المصحح: <?= !str_starts_with($post['media_url'], '/') && !str_starts_with($post['media_url'], 'http') ? '/WEP/' . $post['media_url'] : $post['media_url'] ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="text-center">
            <a href="u.php" class="btn btn-outline-light">العودة إلى الملف الشخصي</a>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const uploadForm = document.getElementById('upload-form');
            const mediaInput = document.getElementById('media');
            const previewContainer = document.getElementById('preview-container');
            const imagePreview = document.getElementById('image-preview');
            const progressContainer = document.getElementById('progress-container');
            const uploadProgress = document.getElementById('upload-progress');
            const resultContainer = document.getElementById('result-container');
            const uploadBtn = document.getElementById('upload-btn');
            
            mediaInput.addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        imagePreview.src = e.target.result;
                        previewContainer.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                } else {
                    previewContainer.style.display = 'none';
                }
            });
            
            uploadForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const file = mediaInput.files[0];
                if (!file) {
                    alert('الرجاء اختيار صورة أولاً');
                    return;
                }
                
                const formData = new FormData();
                formData.append('media', file);
                
                // إظهار شريط التقدم
                progressContainer.style.display = 'block';
                uploadProgress.style.width = '0%';
                uploadProgress.textContent = '0%';
                
                uploadBtn.disabled = true;
                uploadBtn.innerHTML = '<i class="bi bi-arrow-repeat"></i> جار الرفع...';
                
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'api/post_media_upload.php', true);
                
                xhr.upload.onprogress = function(e) {
                    if (e.lengthComputable) {
                        const percentComplete = Math.round((e.loaded / e.total) * 100);
                        uploadProgress.style.width = percentComplete + '%';
                        uploadProgress.textContent = percentComplete + '%';
                    }
                };
                
                xhr.onload = function() {
                    uploadBtn.disabled = false;
                    uploadBtn.innerHTML = '<i class="bi bi-cloud-upload"></i> رفع الصورة';
                    
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            resultContainer.style.display = 'block';
                            
                            if (response.success) {
                                resultContainer.innerHTML = `
                                    <div class="alert alert-success">تم رفع الصورة بنجاح!</div>
                                    <p><strong>مسار الصورة:</strong> ${response.media_url}</p>
                                    <p><strong>نوع الوسائط:</strong> ${response.media_type}</p>
                                    <p><strong>اسم الملف:</strong> ${response.file_name}</p>
                                    <p><strong>حجم الملف:</strong> ${formatFileSize(response.file_size)}</p>
                                    <div class="mt-3">
                                        <h6>معاينة الصورة بالمسار الأصلي:</h6>
                                        <img src="${response.media_url}" class="img-fluid rounded" style="max-height: 200px;">
                                        <div class="mt-2 media-info">مسار الصورة: ${response.media_url}</div>
                                    </div>
                                    <div class="mt-3">
                                        <h6>معاينة الصورة بالمسار المصحح:</h6>
                                        <img src="${!response.media_url.startsWith('/') && !response.media_url.startsWith('http') ? '/WEP/' + response.media_url : response.media_url}" 
                                             class="img-fluid rounded" style="max-height: 200px;">
                                        <div class="mt-2 media-info">
                                            مسار الصورة المصحح: ${!response.media_url.startsWith('/') && !response.media_url.startsWith('http') ? '/WEP/' + response.media_url : response.media_url}
                                        </div>
                                    </div>
                                `;
                            } else {
                                resultContainer.innerHTML = `
                                    <div class="alert alert-danger">فشل رفع الصورة: ${response.message}</div>
                                `;
                            }
                        } catch (e) {
                            resultContainer.innerHTML = `
                                <div class="alert alert-danger">خطأ في تحليل استجابة الخادم</div>
                                <pre>${xhr.responseText}</pre>
                            `;
                        }
                    } else {
                        resultContainer.innerHTML = `
                            <div class="alert alert-danger">خطأ في الاتصال بالخادم (${xhr.status})</div>
                        `;
                    }
                };
                
                xhr.onerror = function() {
                    uploadBtn.disabled = false;
                    uploadBtn.innerHTML = '<i class="bi bi-cloud-upload"></i> رفع الصورة';
                    resultContainer.style.display = 'block';
                    resultContainer.innerHTML = `
                        <div class="alert alert-danger">خطأ في الاتصال بالخادم</div>
                    `;
                };
                
                xhr.send(formData);
            });
            
            function formatFileSize(bytes) {
                if (bytes < 1024) return bytes + ' bytes';
                else if (bytes < 1048576) return (bytes / 1024).toFixed(2) + ' KB';
                else return (bytes / 1048576).toFixed(2) + ' MB';
            }
        });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/fix-post-images.js"></script>
</body>
</html>
