<?php


session_start();
require_once '../config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'غير مصرح بالوصول']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'طريقة غير مدعومة']);
        exit;
    }

    if (!isset($_FILES['cover']) || $_FILES['cover']['error'] !== UPLOAD_ERR_OK) {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'الملف كبير جداً (حد الخادم)',
            UPLOAD_ERR_FORM_SIZE => 'الملف كبير جداً (حد النموذج)',
            UPLOAD_ERR_PARTIAL => 'تم رفع الملف جزئياً فقط',
            UPLOAD_ERR_NO_FILE => 'لم يتم رفع أي ملف',
            UPLOAD_ERR_NO_TMP_DIR => 'مجلد مؤقت مفقود',
            UPLOAD_ERR_CANT_WRITE => 'فشل في كتابة الملف',
            UPLOAD_ERR_EXTENSION => 'امتداد PHP منع رفع الملف'
        ];
        
        $error = $_FILES['cover']['error'] ?? UPLOAD_ERR_NO_FILE;
        echo json_encode([
            'success' => false,
            'message' => $error_messages[$error] ?? 'خطأ غير معروف في رفع الملف'
        ]);
        exit;
    }

    $file = $_FILES['cover'];
    $upload_dir = '../uploads/covers/';
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $file_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($file_type, $allowed_types)) {
        echo json_encode([
            'success' => false,
            'message' => 'نوع الملف غير مدعوم. يرجى رفع صورة (JPEG, PNG, GIF, WebP)'
        ]);
        exit;
    }
    
    $max_size = 10 * 1024 * 1024; 
    if ($file['size'] > $max_size) {
        echo json_encode([
            'success' => false,
            'message' => 'الصورة كبيرة جداً. الحد الأقصى 10MB'
        ]);
        exit;
    }
    
    $image_info = getimagesize($file['tmp_name']);
    if ($image_info === false) {
        echo json_encode([
            'success' => false,
            'message' => 'الملف ليس صورة صحيحة'
        ]);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT cover_url FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $old_cover = $stmt->fetchColumn();
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'cover_' . $user_id . '_' . time() . '.' . strtolower($extension);
    $filepath = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        $cover_url = 'uploads/covers/' . $filename;
        $stmt = $pdo->prepare("UPDATE users SET cover_url = ?, updated_at = NOW() WHERE id = ?");
        
        if ($stmt->execute([$cover_url, $user_id])) {
            if ($old_cover && 
                strpos($old_cover, 'default-cover') === false &&
                file_exists('../' . $old_cover)) {
                unlink('../' . $old_cover);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'تم تحديث صورة الغلاف بنجاح',
                'cover_url' => $cover_url
            ]);
        } else {
            unlink($filepath); 
            echo json_encode([
                'success' => false,
                'message' => 'فشل في تحديث قاعدة البيانات'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'فشل في رفع الملف'
        ]);
    }
} catch (Exception $e) {
    error_log('Cover upload error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ في الخادم'
    ]);
}
?> 