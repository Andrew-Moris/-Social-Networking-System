<?php

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

session_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'طريقة الطلب غير مدعومة. استخدم POST فقط.']);
    exit;
}

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : (isset($_POST['user_id']) ? intval($_POST['user_id']) : null);

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'غير مصرح به. يرجى تسجيل الدخول.']);
    exit;
}

function log_error($message) {
    error_log("[POST_MEDIA_UPLOAD] " . $message);
}

if (empty($_FILES['media']) || !isset($_FILES['media']['name']) || empty($_FILES['media']['name'])) {
    log_error("No media file uploaded");
    echo json_encode(['success' => false, 'message' => 'لم يتم تحديد ملف للرفع']);
    exit;
}

log_error("Media upload request received. File info: " . print_r($_FILES['media'], true));

if ($_FILES['media']['error'] !== UPLOAD_ERR_OK) {
    $upload_errors = [
        UPLOAD_ERR_INI_SIZE => 'حجم الملف أكبر من الحد المسموح به في إعدادات PHP',
        UPLOAD_ERR_FORM_SIZE => 'حجم الملف أكبر من الحد المسموح به في النموذج',
        UPLOAD_ERR_PARTIAL => 'تم تحميل جزء من الملف فقط',
        UPLOAD_ERR_NO_FILE => 'لم يتم تحميل أي ملف',
        UPLOAD_ERR_NO_TMP_DIR => 'مجلد الملفات المؤقتة مفقود',
        UPLOAD_ERR_CANT_WRITE => 'فشل في كتابة الملف على القرص',
        UPLOAD_ERR_EXTENSION => 'تم إيقاف التحميل بواسطة إضافة PHP'
    ];
    $error_message = $upload_errors[$_FILES['media']['error']] ?? 'خطأ غير معروف في تحميل الملف';
    log_error("Upload error: {$error_message} (Code: {$_FILES['media']['error']})");
    echo json_encode(['success' => false, 'message' => $error_message]);
    exit;
}

$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'video/webm'];
$file_type = $_FILES['media']['type'];
$file_extension = strtolower(pathinfo($_FILES['media']['name'], PATHINFO_EXTENSION));
$allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm'];

if (!in_array($file_type, $allowed_types) && !in_array($file_extension, $allowed_extensions)) {
    log_error("Invalid file type: {$file_type} with extension {$file_extension}");
    echo json_encode(['success' => false, 'message' => 'نوع الملف غير مدعوم. الأنواع المدعومة هي: JPEG, PNG, GIF, WebP, MP4, WebM']);
    exit;
}

$max_size = 10 * 1024 * 1024; 
if ($_FILES['media']['size'] > $max_size) {
    echo json_encode(['success' => false, 'message' => 'حجم الملف كبير جدًا. الحد الأقصى هو 10 ميجابايت']);
    exit;
}

try {
    $upload_dir = __DIR__ . '/../uploads/posts/';
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            log_error("Failed to create directory: {$upload_dir}");
            throw new Exception('فشل إنشاء مجلد الرفع');
        }
        log_error("Created directory: {$upload_dir}");
    }
    
    if (!is_writable($upload_dir)) {
        chmod($upload_dir, 0777);
        log_error("Changed permissions for directory: {$upload_dir}");
    }
    
    $file_name = 'post_' . time() . '_' . uniqid() . '.' . $file_extension;
    $file_path = $upload_dir . $file_name;
    
    $upload_success = false;
    
    if (move_uploaded_file($_FILES['media']['tmp_name'], $file_path)) {
        $upload_success = true;
        log_error("File uploaded successfully using move_uploaded_file");
    } else {
        log_error("move_uploaded_file failed, trying copy");
        if (copy($_FILES['media']['tmp_name'], $file_path)) {
            $upload_success = true;
            log_error("File uploaded successfully using copy");
        } else {
            log_error("Both move_uploaded_file and copy failed");
        }
    }
    
    if (!$upload_success) {
        throw new Exception('فشل في نقل الملف المرفق. الرجاء المحاولة مرة أخرى.');
    }
    
    if (!file_exists($file_path)) {
        log_error("File does not exist after upload: {$file_path}");
        throw new Exception('فشل التحقق من وجود الملف بعد الرفع');
    }
    
    chmod($file_path, 0644);
    
    $media_type = in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']) ? 'image' : 'video';
    
    $media_url = 'uploads/posts/' . $file_name;
    
    log_error("Generated media_url: {$media_url}");
    
    echo json_encode([
        'success' => true,
        'message' => 'تم رفع الملف بنجاح',
        'media_url' => $media_url,
        'media_type' => $media_type,
        'file_name' => $file_name,
        'file_size' => $_FILES['media']['size'],
        'file_type' => $file_type
    ]);
    
} catch (Exception $e) {
    log_error("Error in post_media_upload.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'حدث خطأ: ' . $e->getMessage()]);
}
?>
