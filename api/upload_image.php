<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'غير مصرح به']);
    exit;
}

$current_user_id = (int)$_SESSION['user_id'];

$host = 'localhost';
$dbname = 'wep_db';
$username = 'root';
$password = '';

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'لم يتم تحميل أي ملف أو حدث خطأ أثناء التحميل']);
    exit;
}

$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$file_type = $_FILES['image']['type'];

if (!in_array($file_type, $allowed_types)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'نوع الملف غير مسموح به. يرجى تحميل صورة بتنسيق JPEG أو PNG أو GIF أو WebP']);
    exit;
}

$max_size = 5 * 1024 * 1024; 
if ($_FILES['image']['size'] > $max_size) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'حجم الملف كبير جدًا. الحد الأقصى هو 5 ميجابايت']);
    exit;
}

$upload_dir = '../uploads/chat_images/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
$new_file_name = uniqid('chat_img_') . '_' . time() . '.' . $file_extension;
$upload_path = $upload_dir . $new_file_name;

if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
    $image_url = 'uploads/chat_images/' . $new_file_name;
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'تم رفع الصورة بنجاح',
            'image_url' => $image_url
        ]);
        
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'فشل في نقل الملف المرفوع']);
}
?>
