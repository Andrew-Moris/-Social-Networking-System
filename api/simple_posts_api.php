<?php

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

function jsonResponse($data, $status = 200) {
    if (!headers_sent()) {
        http_response_code($status);
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

$host = 'localhost';
$dbname = 'wep_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'خطأ في الاتصال بقاعدة البيانات: ' . $e->getMessage()]);
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        error_log("Simple posts API received POST request");
        error_log("POST data: " . print_r($_POST, true));
        error_log("FILES data: " . print_r($_FILES, true));
        
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 1;
        $content = isset($_POST['content']) ? trim($_POST['content']) : '';
        
        if (empty($content) && empty($_FILES['media']['name'])) {
            jsonResponse(['success' => false, 'message' => 'يجب إدخال محتوى أو إرفاق وسائط']);
        }
        
        $media_url = null;
        
        if (!empty($_FILES['media']['name'])) {
            error_log("Processing media file: " . print_r($_FILES['media'], true));
            
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
                error_log("Upload error: {$error_message} (Code: {$_FILES['media']['error']})");
                throw new Exception($error_message);
            }
            
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'video/webm'];
            $file_type = $_FILES['media']['type'];
            
            $file_extension = strtolower(pathinfo($_FILES['media']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm'];
            
            if (!in_array($file_type, $allowed_types) && !in_array($file_extension, $allowed_extensions)) {
                error_log("Invalid file type: {$file_type} with extension {$file_extension}");
                throw new Exception('نوع الملف غير مدعوم. الأنواع المدعومة هي: JPEG, PNG, GIF, WebP, MP4, WebM');
            }
            
            $upload_dir = __DIR__ . '/../uploads/posts/';
            if (!file_exists($upload_dir)) {
                if (!mkdir($upload_dir, 0777, true)) {
                    error_log("Failed to create directory: {$upload_dir}");
                    throw new Exception('فشل إنشاء مجلد التحميل');
                }
                error_log("Created directory: {$upload_dir}");
            }
            
            if (!is_writable($upload_dir)) {
                chmod($upload_dir, 0777);
                error_log("Changed permissions for directory: {$upload_dir}");
            }
            
            $file_name = 'post_' . time() . '_' . uniqid() . '.' . $file_extension;
            $file_path = $upload_dir . $file_name;
            
            if (!is_uploaded_file($_FILES['media']['tmp_name'])) {
                error_log("File is not a valid uploaded file");
                throw new Exception('الملف المرفق غير صالح');
            }
            
            if (!move_uploaded_file($_FILES['media']['tmp_name'], $file_path)) {
                error_log("move_uploaded_file failed, trying copy");
                if (!copy($_FILES['media']['tmp_name'], $file_path)) {
                    error_log("Both move_uploaded_file and copy failed");
                    throw new Exception('فشل نقل الملف المرفق. الرجاء المحاولة مرة أخرى.');
                }
            }
            
            if (!file_exists($file_path)) {
                error_log("File does not exist after upload: {$file_path}");
                throw new Exception('فشل التحقق من وجود الملف بعد الرفع');
            }
            
            chmod($file_path, 0644);
            
            $media_url = 'uploads/posts/' . $file_name;
            
            $image_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            error_log("Media uploaded successfully to: {$file_path}");
            error_log("Media URL for database: {$media_url}");
        }
        
        try {
            $table_info = $pdo->query("DESCRIBE posts");
            $columns = [];
            while ($column = $table_info->fetch(PDO::FETCH_ASSOC)) {
                $columns[] = $column['Field'];
            }
            error_log("Posts table columns: " . implode(', ', $columns));
            
            $fields = ['user_id', 'content', 'created_at'];
            $values = [$user_id, $content, 'NOW()'];
            $placeholders = ['?', '?', 'NOW()'];
            
            if (in_array('media_url', $columns)) {
                $fields[] = 'media_url';
                $values[] = $media_url;
                $placeholders[] = '?';
            } else if (in_array('image_url', $columns)) {
                $fields[] = 'image_url';
                $values[] = $media_url;
                $placeholders[] = '?';
            }
            
            
            array_pop($values);
            
            $sql = "INSERT INTO posts (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
            error_log("SQL Query: {$sql}");
            error_log("SQL Values: " . print_r($values, true));
            
            $stmt = $pdo->prepare($sql);
            if (!$stmt->execute($values)) {
                throw new Exception("فشل إنشاء المنشور: " . implode(', ', $stmt->errorInfo()));
            }
        } catch (PDOException $e) {
            error_log("Failed to get table structure, using default query: " . $e->getMessage());
            
            $sql = "INSERT INTO posts (user_id, content, media_url, created_at) VALUES (?, ?, ?, NOW())";
            $stmt = $pdo->prepare($sql);
            if (!$stmt->execute([$user_id, $content, $media_url])) {
                throw new Exception("فشل إنشاء المنشور: " . implode(', ', $stmt->errorInfo()));
            }
        }
        
        $post_id = $pdo->lastInsertId();
        error_log("Post created with ID: {$post_id}");
        
        $post_data = [
            'id' => $post_id,
            'user_id' => $user_id,
            'content' => $content,
            'media_url' => $media_url,
            'created_at' => date('Y-m-d H:i:s'),
            'username' => 'user_' . $user_id,
            'first_name' => '',
            'last_name' => '',
            'avatar' => '/WEP/assets/images/default-avatar.png',
            'avatar_url' => '/WEP/assets/images/default-avatar.png',
            'likes_count' => 0,
            'comments_count' => 0,
            'is_liked' => false,
            'is_bookmarked' => false
        ];
        
        try {
            $userColumns = [];
            $tableStructure = $pdo->query("DESCRIBE users");
            while ($row = $tableStructure->fetch(PDO::FETCH_ASSOC)) {
                $userColumns[] = $row['Field'];
            }
            error_log("User table structure: " . print_r($userColumns, true));
            
            $user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $user_stmt->execute([$user_id]);
            $user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user_data) {
                $post_data['username'] = $user_data['username'] ?? $post_data['username'];
                
                if (in_array('first_name', $userColumns) && isset($user_data['first_name'])) {
                    $post_data['first_name'] = $user_data['first_name'];
                }
                
                if (in_array('last_name', $userColumns) && isset($user_data['last_name'])) {
                    $post_data['last_name'] = $user_data['last_name'];
                }
                
                foreach(['avatar_url', 'avatar', 'profile_picture', 'image'] as $possible_field) {
                    if (in_array($possible_field, $userColumns) && isset($user_data[$possible_field]) && !empty($user_data[$possible_field])) {
                        $post_data['avatar'] = $user_data[$possible_field];
                        $post_data['avatar_url'] = $user_data[$possible_field];
                        break;
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Warning: Failed to get user data: " . $e->getMessage());
        }
        
        jsonResponse([
            'success' => true,
            'message' => 'تم إنشاء المنشور بنجاح',
            'post' => $post_data
        ]);
        
    } catch (Exception $e) {
        error_log("Error in simple_posts_api.php: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'حدث خطأ: ' . $e->getMessage()]);
    }
} else {
    jsonResponse(['success' => false, 'message' => 'طريقة الطلب غير مدعومة. استخدم POST فقط.']);
}
