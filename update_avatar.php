<?php

session_start();
require_once 'config.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'يرجى تسجيل الدخول'
    ]);
    exit;
}

if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] != UPLOAD_ERR_OK) {
    echo json_encode([
        'success' => false,
        'message' => 'لم يتم اختيار ملف أو حدث خطأ أثناء الرفع'
    ]);
    exit;
}

$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$file_type = $_FILES['avatar']['type'];

if (!in_array($file_type, $allowed_types)) {
    echo json_encode([
        'success' => false,
        'message' => 'يرجى اختيار صورة بتنسيق JPG، PNG، GIF، أو WEBP'
    ]);
    exit;
}

if ($_FILES['avatar']['size'] > MAX_FILE_SIZE) {
    echo json_encode([
        'success' => false,
        'message' => 'حجم الملف كبير جدًا. الحد الأقصى هو 5 ميجابايت'
    ]);
    exit;
}

$debug_messages = [];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
    $debug_messages[] = "تم الاتصال بقاعدة البيانات بنجاح";
    
    $debug_messages[] = "UPLOAD_DIR = " . UPLOAD_DIR;
    $debug_messages[] = "AVATAR_DIR = " . AVATAR_DIR;
    
    if (!is_dir(UPLOAD_DIR)) {
        if (mkdir(UPLOAD_DIR, 0777, true)) {
            $debug_messages[] = "تم إنشاء مجلد التحميلات";
        } else {
            $debug_messages[] = "فشل في إنشاء مجلد التحميلات";
            throw new Exception("لا يمكن إنشاء مجلد التحميلات");
        }
    } else {
        $debug_messages[] = "مجلد التحميلات موجود بالفعل";
    }
    
    if (!is_dir(AVATAR_DIR)) {
        if (mkdir(AVATAR_DIR, 0777, true)) {
            $debug_messages[] = "تم إنشاء مجلد الصور الشخصية";
        } else {
            $debug_messages[] = "فشل في إنشاء مجلد الصور الشخصية";
            throw new Exception("لا يمكن إنشاء مجلد الصور الشخصية");
        }
    } else {
        $debug_messages[] = "مجلد الصور الشخصية موجود بالفعل";
    }
    
    if (!is_writable(AVATAR_DIR)) {
        chmod(AVATAR_DIR, 0777);
        $debug_messages[] = "تم تغيير صلاحيات مجلد الصور الشخصية";
        
        if (!is_writable(AVATAR_DIR)) {
            $debug_messages[] = "مجلد الصور الشخصية غير قابل للكتابة";
            throw new Exception("لا يمكن الكتابة في مجلد الصور الشخصية");
        }
    } else {
        $debug_messages[] = "مجلد الصور الشخصية قابل للكتابة";
    }
    
    $user_id = $_SESSION['user_id'];
    $file_extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
    $new_filename = 'avatar_' . $user_id . '_' . time() . '.' . $file_extension;
    $upload_path = AVATAR_DIR . '/' . $new_filename;
    $debug_messages[] = "مسار التحميل: " . $upload_path;
    
    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_path)) {
        $debug_messages[] = "تم نقل الملف بنجاح";
        
        $avatar_url = APP_URL . '/uploads/avatars/' . $new_filename; 
        $relative_path = 'uploads/avatars/' . $new_filename; 
        
        $stmt = $pdo->prepare("UPDATE users SET avatar_url = :avatar_url WHERE id = :user_id");
        $result = $stmt->execute([
            'avatar_url' => $relative_path,
            'user_id' => $user_id
        ]);
        
        if ($result) {
            $debug_messages[] = "تم تحديث قاعدة البيانات بنجاح";
            
            echo json_encode([
                'success' => true,
                'avatar_url' => $relative_path,
                'debug' => $debug_messages 
            ]);
        } else {
            $debug_messages[] = "فشل تحديث قاعدة البيانات";
            throw new Exception("فشل تحديث قاعدة البيانات");
        }
    } else {
        $debug_messages[] = "فشل نقل الملف";
        throw new Exception("فشل نقل الملف");
    }
} catch (PDOException $e) {
    if (function_exists('log_error')) {
        log_error("خطأ في تحديث الصورة الشخصية (PDO): " . $e->getMessage());
    }
    
    $debug_messages[] = "خطأ قاعدة البيانات: " . $e->getMessage();
    
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ في قاعدة البيانات',
        'debug' => $debug_messages,
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    if (function_exists('log_error')) {
        log_error("خطأ في تحديث الصورة الشخصية: " . $e->getMessage());
    }
    
    $debug_messages[] = "خطأ: " . $e->getMessage();
    
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ أثناء تحديث الصورة الشخصية',
        'debug' => $debug_messages,
        'error' => $e->getMessage()
    ]);
}
?>
