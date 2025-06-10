<?php

session_start();
require_once '../config.php';
require_once '../functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, DELETE, PUT');
header('Access-Control-Allow-Headers: Content-Type');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'غير مصرح بالوصول']);
    exit;
}

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    switch ($method) {
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            $action = $input['action'] ?? '';
            
            if ($action === 'delete_post') {
                handlePostDeletion($pdo, $user_id);
            } else {
                handlePostCreation($pdo, $user_id);
            }
            break;
            
        case 'DELETE':
            handlePostDeletion($pdo, $user_id);
            break;
            
        case 'PUT':
            handlePostUpdate($pdo, $user_id);
            break;
            
        case 'GET':
            handlePostRetrieval($pdo, $user_id);
            break;
            
        case 'HEAD':
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'API is working']);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'طريقة غير مدعومة']);
            break;
    }
    
} catch (PDOException $e) {
    error_log("Database Error in posts_fixed.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'حدث خطأ في قاعدة البيانات: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("General Error in posts_fixed.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'حدث خطأ غير متوقع: ' . $e->getMessage()]);
}


function handlePostCreation($pdo, $user_id) {
    $action = $_POST['action'] ?? '';
    
    if ($action !== 'create_post') {
        echo json_encode(['success' => false, 'message' => 'إجراء غير صحيح']);
        return;
    }
    
    $content = trim($_POST['content'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $is_private = isset($_POST['is_private']) ? (int)(bool)$_POST['is_private'] : 0;
    $image_url = null;
    
    if (empty($content) && empty($_FILES['image']['name'])) {
        echo json_encode(['success' => false, 'message' => 'يرجى إدخال محتوى أو صورة']);
        return;
    }
    
    if (strlen($content) > 5000) {
        echo json_encode(['success' => false, 'message' => 'المحتوى طويل جداً (الحد الأقصى 5000 حرف)']);
        return;
    }
    
    if (!empty($_FILES['image']['name'])) {
        $upload_result = handleImageUpload($_FILES['image']);
        if ($upload_result['success']) {
            $image_url = $upload_result['url'];
        } else {
            echo json_encode(['success' => false, 'message' => $upload_result['message']]);
            return;
        }
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO posts (user_id, content, media_url, location, is_private, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $location_value = empty($location) ? null : $location;
        
        if ($stmt->execute([$user_id, $content, $image_url, $location_value, $is_private])) {
            $post_id = $pdo->lastInsertId();
            
            $post_stmt = $pdo->prepare("
                SELECT p.*, u.username, u.first_name, u.last_name, u.avatar_url,
                       0 as likes_count, 0 as comments_count, 0 as shares_count
                FROM posts p
                JOIN users u ON p.user_id = u.id
                WHERE p.id = ?
            ");
            $post_stmt->execute([$post_id]);
            $post = $post_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($post) {
                $post['time_ago'] = formatTimeAgo($post['created_at']);
                $post['formatted_date'] = date('d/m/Y H:i', strtotime($post['created_at']));
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'تم نشر المنشور بنجاح',
                'post' => $post,
                'post_id' => $post_id
            ]);
        } else {
            if ($image_url && file_exists('../' . $image_url)) {
                unlink('../' . $image_url);
            }
            echo json_encode(['success' => false, 'message' => 'فشل في إنشاء المنشور']);
        }
    } catch (PDOException $e) {
        if ($image_url && file_exists('../' . $image_url)) {
            unlink('../' . $image_url);
        }
        throw $e;
    }
}


function handlePostDeletion($pdo, $user_id) {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    $post_id = $input['post_id'] ?? 0;
    
    if ($action !== 'delete_post') {
        echo json_encode(['success' => false, 'message' => 'إجراء غير صحيح']);
        return;
    }
    
    if (!$post_id) {
        echo json_encode(['success' => false, 'message' => 'معرف المنشور مطلوب']);
        return;
    }
    
    $check_stmt = $pdo->prepare("SELECT user_id, media_url FROM posts WHERE id = ?");
    $check_stmt->execute([$post_id]);
    $post = $check_stmt->fetch();
    
    if (!$post) {
        echo json_encode(['success' => false, 'message' => 'المنشور غير موجود']);
        return;
    }
    
    if ($post['user_id'] != $user_id) {
        echo json_encode(['success' => false, 'message' => 'غير مصرح لك بحذف هذا المنشور']);
        return;
    }
    
    $pdo->beginTransaction();
    
    try {
        $pdo->prepare("DELETE FROM likes WHERE post_id = ?")->execute([$post_id]);
        $pdo->prepare("DELETE FROM comments WHERE post_id = ?")->execute([$post_id]);
        $pdo->prepare("DELETE FROM bookmarks WHERE post_id = ?")->execute([$post_id]);
        $pdo->prepare("DELETE FROM shares WHERE post_id = ?")->execute([$post_id]);
        $pdo->prepare("DELETE FROM notifications WHERE reference_id = ? AND type IN ('like', 'comment', 'share')")->execute([$post_id]);
        
        $delete_stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
        $delete_stmt->execute([$post_id]);
        
        $pdo->commit();
        
        if ($post['media_url'] && file_exists('../' . $post['media_url'])) {
            unlink('../' . $post['media_url']);
        }
        
        echo json_encode(['success' => true, 'message' => 'تم حذف المنشور بنجاح']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}


function handlePostUpdate($pdo, $user_id) {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    $post_id = $input['post_id'] ?? 0;
    $content = trim($input['content'] ?? '');
    
    if ($action !== 'update_post') {
        echo json_encode(['success' => false, 'message' => 'إجراء غير صحيح']);
        return;
    }
    
    if (!$post_id || empty($content)) {
        echo json_encode(['success' => false, 'message' => 'معرف المنشور والمحتوى مطلوبان']);
        return;
    }
    
    if (strlen($content) > 5000) {
        echo json_encode(['success' => false, 'message' => 'المحتوى طويل جداً (الحد الأقصى 5000 حرف)']);
        return;
    }
    
    $check_stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
    $check_stmt->execute([$post_id]);
    $post = $check_stmt->fetch();
    
    if (!$post) {
        echo json_encode(['success' => false, 'message' => 'المنشور غير موجود']);
        return;
    }
    
    if ($post['user_id'] != $user_id) {
        echo json_encode(['success' => false, 'message' => 'غير مصرح لك بتعديل هذا المنشور']);
        return;
    }
    
    $update_stmt = $pdo->prepare("UPDATE posts SET content = ?, updated_at = NOW() WHERE id = ?");
    
    if ($update_stmt->execute([$content, $post_id])) {
        echo json_encode(['success' => true, 'message' => 'تم تحديث المنشور بنجاح']);
    } else {
        echo json_encode(['success' => false, 'message' => 'فشل في تحديث المنشور']);
    }
}

function handlePostRetrieval($pdo, $user_id) {
    $filter = $_GET['filter'] ?? 'recent';
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = min(50, max(5, (int)($_GET['limit'] ?? 10)));
    $offset = ($page - 1) * $limit;
    $target_user_id = $_GET['user_id'] ?? null;
    
    $base_query = "
        SELECT p.*, u.username, u.first_name, u.last_name, u.avatar_url,
               COALESCE(p.likes_count, 0) as likes_count,
               COALESCE(p.comments_count, 0) as comments_count,
               COALESCE(p.shares_count, 0) as shares_count,
               EXISTS(SELECT 1 FROM likes WHERE post_id = p.id AND user_id = ?) as user_liked,
               EXISTS(SELECT 1 FROM bookmarks WHERE post_id = p.id AND user_id = ?) as user_bookmarked
        FROM posts p 
        JOIN users u ON p.user_id = u.id 
    ";
    
    $params = [$user_id, $user_id];
    
    switch ($filter) {
        case 'user':
            if ($target_user_id) {
                $query = $base_query . " WHERE p.user_id = ? ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
                $params[] = $target_user_id;
            } else {
                $query = $base_query . " WHERE p.user_id = ? ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
                $params[] = $user_id;
            }
            break;
            
        case 'popular':
            $query = $base_query . " WHERE p.is_private = 0 ORDER BY p.likes_count DESC, p.created_at DESC LIMIT ? OFFSET ?";
            break;
            
        case 'images':
            $query = $base_query . " WHERE p.media_url IS NOT NULL AND p.is_private = 0 ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
            break;
            
        case 'following':
            $query = $base_query . " 
                WHERE (p.user_id IN (
                    SELECT followed_id FROM followers WHERE follower_id = ?
                ) OR p.user_id = ?) 
                AND (p.is_private = 0 OR p.user_id = ?)
                ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
            $params[] = $user_id;
            $params[] = $user_id;
            $params[] = $user_id;
            break;
            
        default: 
            $query = $base_query . " WHERE p.is_private = 0 ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
            break;
    }
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($posts as &$post) {
        $post['time_ago'] = formatTimeAgo($post['created_at']);
        $post['formatted_date'] = date('d/m/Y H:i', strtotime($post['created_at']));
    }
    
    echo json_encode(['success' => true, 'posts' => $posts, 'page' => $page, 'limit' => $limit]);
}

function handleImageUpload($file) {
    $upload_dir = '../uploads/posts/';
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'الملف كبير جداً (حد الخادم)',
            UPLOAD_ERR_FORM_SIZE => 'الملف كبير جداً (حد النموذج)',
            UPLOAD_ERR_PARTIAL => 'تم رفع الملف جزئياً فقط',
            UPLOAD_ERR_NO_FILE => 'لم يتم رفع أي ملف',
            UPLOAD_ERR_NO_TMP_DIR => 'مجلد مؤقت مفقود',
            UPLOAD_ERR_CANT_WRITE => 'فشل في كتابة الملف',
            UPLOAD_ERR_EXTENSION => 'امتداد PHP منع رفع الملف'
        ];
        
        return [
            'success' => false,
            'message' => $error_messages[$file['error']] ?? 'خطأ غير معروف في رفع الملف'
        ];
    }
    
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $file_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($file_type, $allowed_types)) {
        return [
            'success' => false,
            'message' => 'نوع الملف غير مدعوم. يرجى رفع صورة (JPEG, PNG, GIF, WebP)'
        ];
    }
    
    $max_size = 5 * 1024 * 1024; 
    if ($file['size'] > $max_size) {
        return [
            'success' => false,
            'message' => 'الصورة كبيرة جداً. الحد الأقصى 5MB'
        ];
    }
    
    $image_info = getimagesize($file['tmp_name']);
    if ($image_info === false) {
        return [
            'success' => false,
            'message' => 'الملف ليس صورة صحيحة'
        ];
    }
    
    $max_width = 2048;
    $max_height = 2048;
    if ($image_info[0] > $max_width || $image_info[1] > $max_height) {
        return [
            'success' => false,
            'message' => 'أبعاد الصورة كبيرة جداً. الحد الأقصى ' . $max_width . 'x' . $max_height . ' بكسل'
        ];
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'post_' . time() . '_' . uniqid() . '.' . strtolower($extension);
    $filepath = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        optimizeImage($filepath, $file_type);
        
        return [
            'success' => true,
            'url' => 'uploads/posts/' . $filename,
            'filename' => $filename
        ];
    } else {
        return [
            'success' => false,
            'message' => 'فشل في حفظ الصورة'
        ];
    }
}


function optimizeImage($filepath, $file_type) {
    $max_width = 1200;
    $max_height = 1200;
    $quality = 85;
    
    $image_info = getimagesize($filepath);
    $width = $image_info[0];
    $height = $image_info[1];
    
    if ($width <= $max_width && $height <= $max_height) {
        return;
    }
    
    $ratio = min($max_width / $width, $max_height / $height);
    $new_width = round($width * $ratio);
    $new_height = round($height * $ratio);
    
    switch ($file_type) {
        case 'image/jpeg':
        case 'image/jpg':
            $source = imagecreatefromjpeg($filepath);
            break;
        case 'image/png':
            $source = imagecreatefrompng($filepath);
            break;
        case 'image/gif':
            $source = imagecreatefromgif($filepath);
            break;
        case 'image/webp':
            $source = imagecreatefromwebp($filepath);
            break;
        default:
            return;
    }
    
    if (!$source) return;
    
    $destination = imagecreatetruecolor($new_width, $new_height);
    
    if ($file_type === 'image/png') {
        imagealphablending($destination, false);
        imagesavealpha($destination, true);
        $transparent = imagecolorallocatealpha($destination, 255, 255, 255, 127);
        imagefilledrectangle($destination, 0, 0, $new_width, $new_height, $transparent);
    }
    
    imagecopyresampled($destination, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    
    switch ($file_type) {
        case 'image/jpeg':
        case 'image/jpg':
            imagejpeg($destination, $filepath, $quality);
            break;
        case 'image/png':
            imagepng($destination, $filepath, round(9 * (100 - $quality) / 100));
            break;
        case 'image/gif':
            imagegif($destination, $filepath);
            break;
        case 'image/webp':
            imagewebp($destination, $filepath, $quality);
            break;
    }
    
    imagedestroy($source);
    imagedestroy($destination);
}


function formatTimeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'منذ لحظات';
    elseif ($time < 3600) return 'منذ ' . floor($time/60) . ' دقيقة';
    elseif ($time < 86400) return 'منذ ' . floor($time/3600) . ' ساعة';
    elseif ($time < 2592000) return 'منذ ' . floor($time/86400) . ' يوم';
    elseif ($time < 31536000) return 'منذ ' . floor($time/2592000) . ' شهر';
    else return 'منذ ' . floor($time/31536000) . ' سنة';
}
?> 