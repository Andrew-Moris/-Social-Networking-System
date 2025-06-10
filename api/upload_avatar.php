<?php


session_start();
require_once '../config.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'غير مصرح بالوصول']);
    exit;
}

$user_id = $_SESSION['user_id'];

if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'لم يتم رفع أي ملف']);
    exit;
}

$file = $_FILES['avatar'];

$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$file_type = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($file_type, $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'نوع الملف غير مدعوم']);
    exit;
}

$max_size = 2 * 1024 * 1024;
if ($file['size'] > $max_size) {
    echo json_encode(['success' => false, 'message' => 'حجم الصورة كبير جداً (الحد الأقصى 2MB)']);
    exit;
}

$upload_dir = '../uploads/avatars/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'avatar_' . $user_id . '_' . time() . '.' . strtolower($extension);
$filepath = $upload_dir . $filename;

try {
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
        
        $stmt = $pdo->prepare("SELECT avatar_url FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $old_avatar = $stmt->fetchColumn();
        
        $avatar_url = 'uploads/avatars/' . $filename;
        $stmt = $pdo->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
        $stmt->execute([$avatar_url, $user_id]);
        
        if ($old_avatar && file_exists('../' . $old_avatar) && strpos($old_avatar, 'uploads/avatars/') === 0) {
            unlink('../' . $old_avatar);
        }
        
        $_SESSION['avatar_url'] = $avatar_url;
        
        echo json_encode([
            'success' => true,
            'message' => 'تم رفع الصورة بنجاح',
            'avatar_url' => $avatar_url
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'فشل في رفع الصورة']);
    }
} catch (Exception $e) {
    error_log("Error uploading avatar: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء رفع الصورة']);
}
?> 