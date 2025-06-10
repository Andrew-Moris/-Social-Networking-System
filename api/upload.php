<?php


session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول أولاً']);
    exit;
}

$current_user_id = $_SESSION['user_id'];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('طريقة الطلب غير صحيحة');
    }
    
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('يجب تسجيل الدخول أولاً');
    }
    
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'upload_image':
            handleImageUpload();
            break;
        case 'upload_audio':
            handleAudioUpload();
            break;
        default:
            throw new Exception('إجراء غير صحيح');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function handleImageUpload() {
    global $current_user_id;
    
    if (!isset($_FILES['image'])) {
        throw new Exception('لم يتم اختيار صورة');
    }
    
    $file = $_FILES['image'];
    
    $uploadErrors = array(
        UPLOAD_ERR_INI_SIZE => 'حجم الملف أكبر من الحد المسموح به في إعدادات PHP',
        UPLOAD_ERR_FORM_SIZE => 'حجم الملف أكبر من الحد المسموح به',
        UPLOAD_ERR_PARTIAL => 'تم رفع جزء من الملف فقط',
        UPLOAD_ERR_NO_FILE => 'لم يتم اختيار ملف',
        UPLOAD_ERR_NO_TMP_DIR => 'مجلد التحميل المؤقت غير موجود',
        UPLOAD_ERR_CANT_WRITE => 'فشل في حفظ الملف',
        UPLOAD_ERR_EXTENSION => 'تم إيقاف رفع الملف بواسطة إضافة PHP'
    );
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception($uploadErrors[$file['error']] ?? 'خطأ غير معروف في رفع الملف');
    }
    
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('حجم الملف كبير جداً. الحد الأقصى هو 5 ميجابايت');
    }
    
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        throw new Exception('نوع الملف غير مسموح به. يجب أن تكون الصورة بصيغة JPG, PNG, أو GIF');
    }
    
    $baseUploadDir = __DIR__ . '/../uploads';
    $yearMonth = date('Y/m');
    $uploadDir = $baseUploadDir . '/chat_images/' . $yearMonth;
    
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception('فشل في إنشاء مجلد التحميل');
        }
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
        $extension = 'jpg';
    }
    
    $filename = uniqid('img_') . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . '/' . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('فشل في حفظ الصورة. يرجى المحاولة مرة أخرى');
    }
    
    $urlPath = 'uploads/chat_images/' . $yearMonth . '/' . $filename;
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => true,
        'url' => $urlPath,
        'message' => 'تم رفع الصورة بنجاح',
        'filename' => $filename
    ]);
    exit;
}

function handleAudioUpload() {
    global $current_user_id;
    
    if (!isset($_FILES['audio'])) {
        throw new Exception('No audio file provided');
    }
    
    $file = $_FILES['audio'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload error: ' . $file['error']);
    }
    
    if ($file['size'] > 10 * 1024 * 1024) {
        throw new Exception('File size too large. Maximum 10MB allowed');
    }
    
    $allowedTypes = ['audio/wav', 'audio/mpeg', 'audio/mp3', 'audio/ogg', 'audio/webm'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $isValidAudio = in_array($mimeType, $allowedTypes) || 
                   strpos($mimeType, 'audio/') === 0 ||
                   $mimeType === 'application/octet-stream'; 
    
    if (!$isValidAudio) {
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['wav', 'mp3', 'ogg', 'webm', 'm4a'];
        
        if (!in_array($extension, $allowedExtensions)) {
            throw new Exception('Invalid file type. Only WAV, MP3, OGG, WebM, and M4A audio files are allowed');
        }
    }
    
    $uploadDir = __DIR__ . '/../uploads/audio/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception('Failed to create upload directory');
        }
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    if (empty($extension)) {
        $extension = 'wav';
    }
    $filename = 'audio_' . $current_user_id . '_' . time() . '_' . uniqid() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Failed to save uploaded file');
    }
    
    $fileUrl = 'uploads/audio/' . $filename;
    
    echo json_encode([
        'success' => true,
        'message' => 'Audio uploaded successfully',
        'url' => $fileUrl,
        'filename' => $filename
    ]);
}
?> 