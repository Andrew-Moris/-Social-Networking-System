<?php

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

function handleError($errno, $errstr, $errfile, $errline) {
    error_log("PHP Error [$errno]: $errstr in $errfile on line $errline");
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ في الخادم: ' . $errstr,
        'error_details' => "Error [$errno]: $errstr in $errfile on line $errline"
    ]);
    exit;
}

set_error_handler('handleError');

session_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

function json_response($success, $message, $data = []) {
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if (!empty($data)) {
        $response = array_merge($response, $data);
    }
    
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'طريقة الطلب غير مدعومة. استخدم POST فقط.');
}

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

if (!$user_id) {
    json_response(false, 'غير مصرح به. يرجى تسجيل الدخول.');
}

if (!function_exists('log_error')) {
    function log_error($message) {
        error_log("[PROFILE_MEDIA_UPLOAD] " . $message);
    }
}

if (empty($_FILES['media']) || !isset($_FILES['media']['name']) || empty($_FILES['media']['name'])) {
    log_error("No media file uploaded");
    json_response(false, 'لم يتم تقديم ملف وسائط');
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
    json_response(false, "خطأ في رفع الملف: {$error_message}");
}

$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'video/webm'];
$file_type = $_FILES['media']['type'];
$file_extension = strtolower(pathinfo($_FILES['media']['name'], PATHINFO_EXTENSION));
$allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm'];

if (!in_array($file_type, $allowed_types) && !in_array($file_extension, $allowed_extensions)) {
    log_error("Invalid file type: {$file_type} with extension {$file_extension}");
    json_response(false, 'نوع الملف غير مدعوم. الأنواع المدعومة هي: JPEG, PNG, GIF, WebP, MP4, WebM');
}

$max_size = 10 * 1024 * 1024; 
if ($_FILES['media']['size'] > $max_size) {
    json_response(false, 'حجم الملف كبير جدًا. الحد الأقصى هو 10 ميجابايت');
}

try {
    $upload_dir = __DIR__ . '/../uploads/profile_posts/';
    
    $base_upload_dir = __DIR__ . '/../uploads/';
    if (!file_exists($base_upload_dir)) {
        if (!mkdir($base_upload_dir, 0777)) {
            log_error("Failed to create base upload directory: {$base_upload_dir}");
            throw new Exception('فشل إنشاء مجلد الرفع الرئيسي');
        }
        chmod($base_upload_dir, 0777);
        log_error("Created base upload directory: {$base_upload_dir}");
    }
    
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            log_error("Failed to create directory: {$upload_dir}");
            throw new Exception('فشل إنشاء مجلد الرفع');
        }
        chmod($upload_dir, 0777);
        log_error("Created directory: {$upload_dir}");
    }
    
    if (!is_writable($upload_dir)) {
        chmod($upload_dir, 0777);
        log_error("Changed permissions for directory: {$upload_dir}");
    }
    
    $file_name = 'profile_' . $user_id . '_' . time() . '_' . uniqid() . '.' . $file_extension;
    $file_path = $upload_dir . $file_name;
    
    log_error("Temporary file info: exists=" . (file_exists($_FILES['media']['tmp_name']) ? 'yes' : 'no') . 
              ", size=" . filesize($_FILES['media']['tmp_name']) . 
              ", permissions=" . substr(sprintf('%o', fileperms($_FILES['media']['tmp_name'])), -4));
    
    log_error("Destination directory info: exists=" . (file_exists(dirname($file_path)) ? 'yes' : 'no') . 
              ", writable=" . (is_writable(dirname($file_path)) ? 'yes' : 'no') . 
              ", permissions=" . substr(sprintf('%o', fileperms(dirname($file_path))), -4));
    
    $upload_success = false;
    
    if (!file_exists($_FILES['media']['tmp_name']) || !is_readable($_FILES['media']['tmp_name'])) {
        log_error("Temporary file does not exist or is not readable");
        throw new Exception('الملف المؤقت غير موجود أو غير قابل للقراءة');
    }
    
    $move_result = move_uploaded_file($_FILES['media']['tmp_name'], $file_path);
    log_error("move_uploaded_file result: " . ($move_result ? 'success' : 'failed') . ", PHP error: " . error_get_last()['message'] ?? 'none');
    
    if ($move_result) {
        $upload_success = true;
        log_error("File uploaded successfully using move_uploaded_file");
    } else {
        log_error("move_uploaded_file failed, trying copy");
        $copy_result = copy($_FILES['media']['tmp_name'], $file_path);
        log_error("copy result: " . ($copy_result ? 'success' : 'failed') . ", PHP error: " . error_get_last()['message'] ?? 'none');
        
        if ($copy_result) {
            $upload_success = true;
            log_error("File uploaded successfully using copy");
        } else {
            log_error("Both move_uploaded_file and copy failed, trying file_put_contents");
            $content = file_get_contents($_FILES['media']['tmp_name']);
            if ($content !== false) {
                $put_result = file_put_contents($file_path, $content);
                if ($put_result !== false) {
                    $upload_success = true;
                    log_error("File uploaded successfully using file_put_contents");
                } else {
                    log_error("file_put_contents failed: " . error_get_last()['message'] ?? 'unknown error');
                }
            } else {
                log_error("file_get_contents failed: " . error_get_last()['message'] ?? 'unknown error');
            }
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
    
    $media_url = 'uploads/profile_posts/' . $file_name;
    
    log_error("Generated media_url: {$media_url}");
    
    json_response(true, 'تم رفع الملف بنجاح', [
        'media_url' => $media_url,
        'media_type' => $media_type,
        'file_name' => $file_name,
        'file_size' => $_FILES['media']['size'],
        'file_type' => $file_type
    ]);
    
} catch (Exception $e) {
    log_error("Error in profile_media_upload.php: " . $e->getMessage());
    json_response(false, 'حدث خطأ: ' . $e->getMessage());
}
?>
