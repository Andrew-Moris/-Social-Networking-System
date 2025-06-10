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
    if (isset($_FILES['media']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/uploads/test/';
        
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['media']['name'], PATHINFO_EXTENSION));
        $file_name = 'test_' . time() . '_' . uniqid() . '.' . $file_extension;
        $file_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['media']['tmp_name'], $file_path)) {
            $message = '<div class="alert alert-success">تم رفع الملف بنجاح: ' . htmlspecialchars($file_name) . '</div>';
            $message .= '<div><img src="uploads/test/' . htmlspecialchars($file_name) . '" style="max-width: 300px;"></div>';
        } else {
            $message = '<div class="alert alert-danger">فشل في رفع الملف</div>';
        }
    } else if (isset($_FILES['media']) && $_FILES['media']['error'] !== UPLOAD_ERR_OK) {
        $upload_errors = [
            UPLOAD_ERR_INI_SIZE => 'حجم الملف أكبر من الحد المسموح به في إعدادات PHP',
            UPLOAD_ERR_FORM_SIZE => 'حجم الملف أكبر من الحد المسموح به في النموذج',
            UPLOAD_ERR_PARTIAL => 'تم تحميل جزء من الملف فقط',
            UPLOAD_ERR_NO_FILE => 'لم يتم تحميل أي ملف',
            UPLOAD_ERR_NO_TMP_DIR => 'مجلد الملفات المؤقتة مفقود',
            UPLOAD_ERR_CANT_WRITE => 'فشل في كتابة الملف على القرص',
            UPLOAD_ERR_EXTENSION => 'تم إيقاف التحميل بواسطة إضافة PHP'
        ];
        $error_code = $_FILES['media']['error'];
        $error_message = isset($upload_errors[$error_code]) ? $upload_errors[$error_code] : 'خطأ غير معروف';
        $message = '<div class="alert alert-danger">خطأ في رفع الملف: ' . $error_message . ' (رمز الخطأ: ' . $error_code . ')</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اختبار رفع الصور</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #1a1f2e;
            color: #e5e7eb;
            font-family: 'Cairo', sans-serif;
            padding: 20px;
        }
        .container {
            max-width: 600px;
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
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4 text-center">اختبار رفع الصور</h1>
        
        <?php echo $message; ?>
        
        <form method="post" enctype="multipart/form-data" class="mb-4">
            <div class="mb-3">
                <label for="media" class="form-label">اختر صورة للرفع:</label>
                <input type="file" name="media" id="media" class="form-control" accept="image/*">
            </div>
            <button type="submit" class="btn btn-primary w-100">رفع الصورة</button>
        </form>
        
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
                <li class="list-group-item bg-transparent">upload_tmp_dir: <?php echo ini_get('upload_tmp_dir') ?: 'Default system temp directory'; ?></li>
                <li class="list-group-item bg-transparent">
                    Uploads directory writable: 
                    <?php 
                    $test_dir = __DIR__ . '/uploads/test/';
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
