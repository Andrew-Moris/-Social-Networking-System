<?php

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'غير مصرح به. يرجى تسجيل الدخول.']);
    exit;
}

$current_user_id = (int)$_SESSION['user_id'];

function log_error($message) {
    error_log("[CHAT_UPLOAD] " . $message);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'طريقة الطلب غير مدعومة. استخدم POST فقط.']);
    exit;
}

if (empty($_FILES['image']) || !isset($_FILES['image']['name']) || empty($_FILES['image']['name'])) {
    log_error("No image file uploaded");
    echo json_encode(['success' => false, 'message' => 'لم يتم تحديد ملف للرفع']);
    exit;
}

log_error("Image upload request received. File info: " . print_r($_FILES['image'], true));

if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    $upload_errors = [
        UPLOAD_ERR_INI_SIZE => 'حجم الملف أكبر من الحد المسموح به في إعدادات PHP',
        UPLOAD_ERR_FORM_SIZE => 'حجم الملف أكبر من الحد المسموح به في النموذج',
        UPLOAD_ERR_PARTIAL => 'تم تحميل جزء من الملف فقط',
        UPLOAD_ERR_NO_FILE => 'لم يتم تحميل أي ملف',
        UPLOAD_ERR_NO_TMP_DIR => 'مجلد الملفات المؤقتة مفقود',
        UPLOAD_ERR_CANT_WRITE => 'فشل في كتابة الملف على القرص',
        UPLOAD_ERR_EXTENSION => 'تم إيقاف التحميل بواسطة إضافة PHP'
    ];
    $error_message = $upload_errors[$_FILES['image']['error']] ?? 'خطأ غير معروف في تحميل الملف';
    log_error("Upload error: {$error_message} (Code: {$_FILES['image']['error']})");
    echo json_encode(['success' => false, 'message' => $error_message]);
    exit;
}

$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
$file_type = $_FILES['image']['type'];
$file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
$allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

if (!in_array($file_type, $allowed_types) && !in_array($file_extension, $allowed_extensions)) {
    log_error("Invalid file type: {$file_type} with extension {$file_extension}");
    echo json_encode(['success' => false, 'message' => 'نوع الملف غير مدعوم. الأنواع المدعومة هي: JPEG, PNG, GIF, WebP']);
    exit;
}

$max_size = 5 * 1024 * 1024; 
if ($_FILES['image']['size'] > $max_size) {
    log_error("File too large: {$_FILES['image']['size']} bytes");
    echo json_encode(['success' => false, 'message' => 'حجم الملف كبير جدًا. الحد الأقصى هو 5 ميجابايت']);
    exit;
}

$receiver_id = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
if ($receiver_id <= 0) {
    log_error("Invalid receiver ID: {$receiver_id}");
    echo json_encode(['success' => false, 'message' => 'معرف المستلم غير صالح']);
    exit;
}

$message_content = isset($_POST['content']) ? trim($_POST['content']) : '';

try {
    require_once '../config/database.php';
    
    $upload_dir = __DIR__ . '/../uploads/chat_images/';
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
    
    $file_name = 'chat_' . time() . '_' . uniqid() . '.' . $file_extension;
    $file_path = $upload_dir . $file_name;
    
    $upload_success = false;
    
    if (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
        $upload_success = true;
        log_error("File uploaded successfully using move_uploaded_file");
    } else {
        log_error("move_uploaded_file failed, trying copy");
        if (copy($_FILES['image']['tmp_name'], $file_path)) {
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
    
    $media_url = 'uploads/chat_images/' . $file_name;
    
    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, content, media_url, created_at) VALUES (?, ?, ?, ?, NOW())");
    $result = $stmt->execute([$current_user_id, $receiver_id, $message_content, $media_url]);
    
    if (!$result) {
        log_error("Failed to insert message into database");
        throw new Exception('فشل في حفظ الرسالة في قاعدة البيانات');
    }
    
    $message_id = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'تم رفع الصورة وإرسال الرسالة بنجاح',
        'message_id' => $message_id,
        'media_url' => $media_url,
        'content' => $message_content,
        'sender_id' => $current_user_id,
        'receiver_id' => $receiver_id
    ]);
    
} catch (Exception $e) {
    log_error("Error in chat_upload.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'حدث خطأ: ' . $e->getMessage()]);
}
?>
