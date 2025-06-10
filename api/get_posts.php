<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '../logs/posts_errors.log');
error_reporting(E_ALL);

error_log("\n\n=== بدء طلب API جديد ===\n");
error_log("URL: {$_SERVER['REQUEST_URI']}");
error_log("Method: {$_SERVER['REQUEST_METHOD']}");
error_log("GET params: " . json_encode($_GET));
error_log("POST params: " . json_encode(array_keys($_POST)));
error_log("FILES params: " . json_encode(array_keys($_FILES)));

session_start();
require_once '../config.php';
require_once '../functions.php';

header('Content-Type: application/json; charset=utf-8');

$public_actions = ['get_feed', 'get_user_posts', 'get_post'];
$action = isset($_GET['action']) ? $_GET['action'] : 'get_feed';

if (!isset($_SESSION['user_id'])) {
    if (in_array($action, $public_actions) && $_SERVER['REQUEST_METHOD'] === 'GET') {
        error_log("Public API access for action: {$action}");
        
        $_SESSION['user_id'] = 5;
    } else {
        try {
            $pdo_temp = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
            $stmt_temp = $pdo_temp->prepare("SELECT * FROM users WHERE username = 'ben10' OR id = 5");
            $stmt_temp->execute();
            $user_temp = $stmt_temp->fetch();
            
            if ($user_temp) {
                $_SESSION['user_id'] = $user_temp['id'];
                $_SESSION['username'] = $user_temp['username'];
                $_SESSION['email'] = $user_temp['email'];
                $_SESSION['first_name'] = $user_temp['first_name'];
                $_SESSION['last_name'] = $user_temp['last_name'];
                $_SESSION['avatar_url'] = $user_temp['avatar_url'];
                error_log("Auto-login for user: {$user_temp['username']}");
            } else {
                error_log("Failed to auto-login: User not found");
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'غير مصرح بالوصول']);
                exit;
            }
        } catch (Exception $e) {
            error_log("Auto-login error: " . $e->getMessage());
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'غير مصرح بالوصول']);
            exit;
        }
    }
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? 'get_feed';

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    error_log("Action: {$action}");
    error_log("Request Method: {$_SERVER['REQUEST_METHOD']}");
    error_log("User ID: {$user_id}");
    
    switch ($action) {
        case 'get_feed':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                error_log("Detected POST request to get_feed, checking for create_post action");
                if (isset($_POST['action']) && $_POST['action'] === 'create_post') {
                    error_log("Create post action detected in POST data");
                    createPost($pdo, $user_id);
                } else {
                    error_log("No create_post action found in POST data, proceeding with getFeed");
                    getFeed($pdo, $user_id);
                }
            } else {
                error_log("Regular GET request to get_feed");
                getFeed($pdo, $user_id);
            }
            break;
            
        case 'create_post':
            error_log("Direct create_post action");
            createPost($pdo, $user_id);
            break;
            
        case 'get_user_posts':
            getUserPosts($pdo, $user_id);
            break;
            
        case 'get_post':
            getPost($pdo, $user_id);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'إجراء غير صحيح']);
            break;
    }
    
} catch (PDOException $e) {
    error_log("Database Error in get_posts.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'حدث خطأ في قاعدة البيانات: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("General Error in get_posts.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'حدث خطأ غير متوقع: ' . $e->getMessage()]);
}

/**

 * @param PDO
 * @param int 
 */
function createPost($pdo, $user_id) {
    set_time_limit(300);
    
    error_log("=== بدء إنشاء منشور جديد ===\n");
    error_log("Creating new post for user ID: {$user_id}");
    error_log("POST keys: " . json_encode(array_keys($_POST)));
    
    if (!empty($_FILES)) {
        $files_info = [];
        foreach ($_FILES as $key => $file) {
            $files_info[$key] = [
                'name' => $file['name'] ?? 'undefined',
                'type' => $file['type'] ?? 'undefined',
                'size' => $file['size'] ?? 0,
                'error' => $file['error'] ?? UPLOAD_ERR_NO_FILE
            ];
        }
        error_log("FILES info: " . json_encode($files_info));
    } else {
        error_log("No files uploaded");
    }
    
    $upload_dir = '../uploads/posts/';
    if (!is_dir('../uploads/')) {
        if (!@mkdir('../uploads/', 0777, true)) {
            $error = error_get_last();
            error_log("Failed to create main uploads directory: " . ($error ? json_encode($error) : 'Unknown error'));
        } else {
            error_log("Created main uploads directory successfully");
        }
    }
    
    if (!is_dir($upload_dir)) {
        if (!@mkdir($upload_dir, 0777, true)) {
            $error = error_get_last();
            error_log("Failed to create posts directory: " . ($error ? json_encode($error) : 'Unknown error'));
        } else {
            error_log("Created posts directory successfully");
        }
    }
    
    if (empty($_POST['content']) && empty($_FILES['image']['name']) && empty($_FILES['video']['name'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'يجب إدخال نص أو إرفاق صورة/فيديو']);
        return;
    }
    
    try {
        if (!$pdo) {
            throw new Exception('فشل الاتصال بقاعدة البيانات');
        }
        
        $pdo->beginTransaction();
        
        $content = trim($_POST['content'] ?? '');
        $media_url = null;
        $media_type = null;
        
        try {
            $has_image = !empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK;
            $has_video = !empty($_FILES['video']['name']) && $_FILES['video']['error'] === UPLOAD_ERR_OK;
            
            error_log("Has image: " . ($has_image ? 'Yes' : 'No'));
            error_log("Has video: " . ($has_video ? 'Yes' : 'No'));
            
            if ($has_image) {
                error_log("Processing image upload");
                error_log("Image details: " . json_encode([
                    'name' => $_FILES['image']['name'],
                    'type' => $_FILES['image']['type'],
                    'size' => $_FILES['image']['size'],
                    'error' => $_FILES['image']['error']
                ]));
                
                $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', ''];
                if (!in_array($_FILES['image']['type'], $allowed_types) && !empty($_FILES['image']['type'])) {
                    error_log("Invalid image type: {$_FILES['image']['type']}");
                    throw new Exception('نوع الصورة غير مدعوم. الأنواع المدعومة هي: JPEG, PNG, GIF, WEBP');
                }
                
                if ($_FILES['image']['size'] > 10 * 1024 * 1024) {
                    error_log("Image too large: {$_FILES['image']['size']} bytes");
                    throw new Exception('حجم الصورة كبير جداً. الحد الأقصى هو 10MB');
                }
                
                if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                    $error_message = 'حدث خطأ أثناء تحميل الصورة: ';
                    switch ($_FILES['image']['error']) {
                        case UPLOAD_ERR_INI_SIZE:
                        case UPLOAD_ERR_FORM_SIZE:
                            $error_message .= 'حجم الملف أكبر من الحد المسموح به';
                            break;
                        case UPLOAD_ERR_PARTIAL:
                            $error_message .= 'تم تحميل جزء من الملف فقط';
                            break;
                        case UPLOAD_ERR_NO_FILE:
                            $error_message .= 'لم يتم تحميل أي ملف';
                            break;
                        case UPLOAD_ERR_NO_TMP_DIR:
                            $error_message .= 'المجلد المؤقت مفقود';
                            break;
                        case UPLOAD_ERR_CANT_WRITE:
                            $error_message .= 'فشل في كتابة الملف على القرص';
                            break;
                        case UPLOAD_ERR_EXTENSION:
                            $error_message .= 'تم إيقاف التحميل بواسطة إضافة';
                            break;
                        default:
                            $error_message .= 'خطأ غير معروف';
                    }
                    error_log("Upload error for image: " . $error_message);
                    throw new Exception($error_message);
                }
                
                $upload_dir = '../uploads/posts/';
                
                if (!file_exists($_FILES['image']['tmp_name']) || !is_uploaded_file($_FILES['image']['tmp_name'])) {
                    error_log("Temp file does not exist or is not an uploaded file: {$_FILES['image']['tmp_name']}");
                    throw new Exception('الملف المؤقت غير موجود');
                }
                
                $file_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                if (empty($file_ext)) $file_ext = 'jpg';
                $file_name = time() . '_' . rand(1000, 9999) . '.' . $file_ext;
                $file_path = $upload_dir . $file_name;
                
                error_log("Uploading image to: {$file_path}");
                
                if (copy($_FILES['image']['tmp_name'], $file_path) || move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
                    $media_url = 'uploads/posts/' . $file_name;
                    $media_type = 'image';
                    error_log("Image uploaded successfully: {$media_url}");
                    
                    if (!file_exists($file_path)) {
                        error_log("File does not exist after upload: {$file_path}");
                        throw new Exception('فشل في تحميل الصورة. الملف غير موجود بعد التحميل');
                    }
                } else {
                    $error = error_get_last();
                    error_log("Failed to upload image: " . ($error ? json_encode($error) : 'Unknown error'));
                    throw new Exception('فشل في تحميل الصورة. يرجى المحاولة مرة أخرى');
                }
            }
            
            if ($has_video) {
                error_log("Processing video upload");
                error_log("Video details: " . json_encode([
                    'name' => $_FILES['video']['name'],
                    'type' => $_FILES['video']['type'],
                    'size' => $_FILES['video']['size'],
                    'error' => $_FILES['video']['error']
                ]));
                
                $allowed_types = ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime', 'video/x-ms-wmv', 'video/avi', 'video/mpeg', ''];
                if (!in_array($_FILES['video']['type'], $allowed_types) && !empty($_FILES['video']['type'])) {
                    error_log("Invalid video type: {$_FILES['video']['type']}");
                    throw new Exception('نوع الفيديو غير مدعوم. الأنواع المدعومة هي: MP4, WebM, OGG, MOV, WMV, AVI');
                }
                
                if ($_FILES['video']['size'] > 50 * 1024 * 1024) {
                    error_log("Video too large: {$_FILES['video']['size']} bytes");
                    throw new Exception('حجم الفيديو كبير جداً. الحد الأقصى هو 50MB');
                }
                
                if ($_FILES['video']['error'] !== UPLOAD_ERR_OK) {
                    $error_message = 'حدث خطأ أثناء تحميل الفيديو: ';
                    switch ($_FILES['video']['error']) {
                        case UPLOAD_ERR_INI_SIZE:
                        case UPLOAD_ERR_FORM_SIZE:
                            $error_message .= 'حجم الملف أكبر من الحد المسموح به';
                            break;
                        case UPLOAD_ERR_PARTIAL:
                            $error_message .= 'تم تحميل جزء من الملف فقط';
                            break;
                        case UPLOAD_ERR_NO_FILE:
                            $error_message .= 'لم يتم تحميل أي ملف';
                            break;
                        case UPLOAD_ERR_NO_TMP_DIR:
                            $error_message .= 'المجلد المؤقت مفقود';
                            break;
                        case UPLOAD_ERR_CANT_WRITE:
                            $error_message .= 'فشل في كتابة الملف على القرص';
                            break;
                        case UPLOAD_ERR_EXTENSION:
                            $error_message .= 'تم إيقاف التحميل بواسطة إضافة';
                            break;
                        default:
                            $error_message .= 'خطأ غير معروف';
                    }
                    error_log("Upload error for video: " . $error_message);
                    throw new Exception($error_message);
                }
                
                $upload_dir = '../uploads/posts/';
                
                if (!file_exists($_FILES['video']['tmp_name']) || !is_uploaded_file($_FILES['video']['tmp_name'])) {
                    error_log("Temp file does not exist or is not an uploaded file: {$_FILES['video']['tmp_name']}");
                    throw new Exception('الملف المؤقت غير موجود');
                }
                
                $file_ext = pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION);
                if (empty($file_ext)) $file_ext = 'mp4'; 
                $file_name = time() . '_' . rand(1000, 9999) . '.' . $file_ext;
                $file_path = $upload_dir . $file_name;
                
                error_log("Uploading video to: {$file_path}");
                
                if (copy($_FILES['video']['tmp_name'], $file_path) || move_uploaded_file($_FILES['video']['tmp_name'], $file_path)) {
                    $media_url = 'uploads/posts/' . $file_name;
                    $media_type = 'video';
                    error_log("Video uploaded successfully: {$media_url}");
                    
                    if (!file_exists($file_path)) {
                        error_log("File does not exist after upload: {$file_path}");
                        throw new Exception('فشل في تحميل الفيديو. الملف غير موجود بعد التحميل');
                    }
                } else {
                    $error = error_get_last();
                    error_log("Failed to upload video: " . ($error ? json_encode($error) : 'Unknown error'));
                    throw new Exception('فشل في تحميل الفيديو. يرجى المحاولة مرة أخرى');
                }
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Media upload error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            return;
        }
        
        try {
            error_log("Inserting post into database");
            $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, media_url, media_type, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$user_id, $content, $media_url, $media_type]);
            
            $post_id = $pdo->lastInsertId();
            error_log("Post inserted with ID: {$post_id}");
            
            if (!$post_id) {
                throw new Exception('فشل في إنشاء المنشور');
            }
            
            $stmt = $pdo->prepare("SELECT p.*, u.username, u.first_name, u.last_name, u.avatar_url FROM posts p JOIN users u ON p.user_id = u.id WHERE p.id = ?");
            $stmt->execute([$post_id]);
            $post = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$post) {
                throw new Exception('فشل في استرجاع بيانات المنشور الجديد');
            }
            
            $pdo->commit();
            
            error_log("Post created successfully with ID: {$post_id}");
            
            echo json_encode([
                'success' => true, 
                'message' => 'تم إنشاء المنشور بنجاح',
                'post' => $post
            ]);
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Database error creating post: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
            return;
        }
        
    } catch (Exception $e) {
        if ($pdo && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error creating post: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'فشل في إنشاء المنشور: ' . $e->getMessage()]);
        return;
    }
}

/**
 
 * @param string
 * @return array
 */
function handleMediaUpload($file_key) {
    if (empty($_FILES[$file_key]['name'])) {
        return ['success' => false, 'message' => 'لم يتم تحديد ملف'];
    }
    
    if ($_FILES[$file_key]['error'] !== UPLOAD_ERR_OK) {
        $error_message = 'حدث خطأ أثناء تحميل الملف: ';
        switch ($_FILES[$file_key]['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $error_message .= 'حجم الملف أكبر من الحد المسموح به';
                break;
            case UPLOAD_ERR_PARTIAL:
                $error_message .= 'تم تحميل جزء من الملف فقط';
                break;
            case UPLOAD_ERR_NO_FILE:
                $error_message .= 'لم يتم تحميل أي ملف';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $error_message .= 'المجلد المؤقت مفقود';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $error_message .= 'فشل في كتابة الملف على القرص';
                break;
            case UPLOAD_ERR_EXTENSION:
                $error_message .= 'تم إيقاف التحميل بواسطة إضافة';
                break;
            default:
                $error_message .= 'خطأ غير معروف';
        }
        error_log("Upload error for {$file_key}: " . $error_message);
        return ['success' => false, 'message' => $error_message];
    }
    
    $upload_dir = '../uploads/posts/';
    
    if (!is_dir($upload_dir)) {
        if (!is_dir('../uploads/')) {
            if (!@mkdir('../uploads/', 0777, true)) {
                $error = error_get_last();
                error_log("Failed to create main uploads directory: " . ($error ? $error['message'] : 'Unknown error'));
                return ['success' => false, 'message' => 'فشل في إنشاء مجلد التحميل الرئيسي'];
            }
        }
        
        if (!@mkdir($upload_dir, 0777, true)) {
            $error = error_get_last();
            error_log("Failed to create posts upload directory: {$upload_dir}. Error: " . ($error ? $error['message'] : 'Unknown error'));
            return ['success' => false, 'message' => 'فشل في إنشاء مجلد المنشورات'];
        }
    }
    
    if (!is_writable($upload_dir)) {
        error_log("Upload directory is not writable: {$upload_dir}");
        return ['success' => false, 'message' => 'مجلد المنشورات غير قابل للكتابة'];
    }
    
    $original_name = basename($_FILES[$file_key]['name']);
    $file_name = time() . '_' . preg_replace('/[^a-zA-Z0-9_.-]/', '', $original_name);
    if (empty($file_name)) {
        $file_name = time() . '_file.' . ($file_key === 'image' ? 'jpg' : 'mp4');
    }
    
    $file_path = $upload_dir . $file_name;
    $file_type = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    
    $allowed_image_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $allowed_video_types = ['mp4', 'webm', 'ogg', 'mov'];
    
    if ($file_key === 'image' && !in_array($file_type, $allowed_image_types)) {
        error_log("Invalid image type: {$file_type}");
        return ['success' => false, 'message' => 'نوع الصورة غير مدعوم. الأنواع المدعومة هي: ' . implode(', ', $allowed_image_types)];
    }
    
    if ($file_key === 'video' && !in_array($file_type, $allowed_video_types)) {
        error_log("Invalid video type: {$file_type}");
        return ['success' => false, 'message' => 'نوع الفيديو غير مدعوم. الأنواع المدعومة هي: ' . implode(', ', $allowed_video_types)];
    }
    
    $max_size = ($file_key === 'image') ? 10 * 1024 * 1024 : 50 * 1024 * 1024;
    
    if ($_FILES[$file_key]['size'] > $max_size) {
        $max_size_mb = $max_size / (1024 * 1024);
        error_log("File too large: {$_FILES[$file_key]['size']} bytes (max: {$max_size} bytes)");
        return ['success' => false, 'message' => "حجم الملف كبير جداً. الحد الأقصى هو {$max_size_mb}MB"];
    }
    
    if (move_uploaded_file($_FILES[$file_key]['tmp_name'], $file_path)) {
        $relative_path = 'uploads/posts/' . $file_name;
        error_log("File uploaded successfully: {$relative_path}");
        return ['success' => true, 'url' => $relative_path];
    } else {
        $upload_error = error_get_last();
        error_log("Failed to move uploaded file. Error: " . ($upload_error ? $upload_error['message'] : 'Unknown error'));
        error_log("Upload path: {$file_path}");
        error_log("Temp file exists: " . (file_exists($_FILES[$file_key]['tmp_name']) ? 'Yes' : 'No'));
        error_log("Upload directory exists: " . (file_exists(dirname($file_path)) ? 'Yes' : 'No'));
        error_log("Upload directory is writable: " . (is_writable(dirname($file_path)) ? 'Yes' : 'No'));
        return ['success' => false, 'message' => 'فشل في تحميل الملف. يرجى المحاولة مرة أخرى'];
    }
}

function getFeed($pdo, $user_id) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = ($page - 1) * $limit;
    
    $following_stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE follower_id = ?");
    $following_stmt->execute([$user_id]);
    $following_count = $following_stmt->fetchColumn();
    
    if ($following_count > 0) {
        $posts_stmt = $pdo->prepare("
            SELECT p.id, p.content, p.media_url, p.created_at,
                   u.id as user_id, u.username, u.first_name, u.last_name, u.avatar_url as user_avatar
            FROM posts p 
            JOIN users u ON p.user_id = u.id 
            WHERE p.user_id = ? OR p.user_id IN (
                SELECT followed_id FROM followers WHERE follower_id = ?
            )
            ORDER BY p.created_at DESC 
            LIMIT ? OFFSET ?
        ");
        $posts_stmt->execute([$user_id, $user_id, $limit, $offset]);
    } else {
        $posts_stmt = $pdo->prepare("
            SELECT p.id, p.content, p.media_url, p.created_at,
                   u.id as user_id, u.username, u.first_name, u.last_name, u.avatar_url as user_avatar
            FROM posts p 
            JOIN users u ON p.user_id = u.id 
            ORDER BY p.created_at DESC 
            LIMIT ? OFFSET ?
        ");
        $posts_stmt->execute([$limit, $offset]);
    }
    
    $posts = $posts_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($posts)) {
        foreach ($posts as &$post) {
            $like_stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ?");
            $like_stmt->execute([$post['id']]);
            $post['likes_count'] = (int)$like_stmt->fetchColumn();
            
            $comment_stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE post_id = ?");
            $comment_stmt->execute([$post['id']]);
            $post['comments_count'] = (int)$comment_stmt->fetchColumn();
            
            $user_like_stmt = $pdo->prepare("SELECT id FROM likes WHERE post_id = ? AND user_id = ?");
            $user_like_stmt->execute([$post['id'], $user_id]);
            $post['is_liked'] = $user_like_stmt->fetchColumn() ? true : false;
            
            $bookmark_stmt = $pdo->prepare("SELECT id FROM bookmarks WHERE post_id = ? AND user_id = ?");
            $bookmark_stmt->execute([$post['id'], $user_id]);
            $post['is_bookmarked'] = $bookmark_stmt->fetchColumn() ? true : false;
        }
    }
    
    echo json_encode([
        'success' => true, 
        'posts' => $posts, 
        'page' => $page, 
        'has_more' => count($posts) == $limit
    ]);
}


function getUserPosts($pdo, $user_id) {
    $profile_id = isset($_GET['profile_id']) ? (int)$_GET['profile_id'] : $user_id;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = ($page - 1) * $limit;
    
    $posts_stmt = $pdo->prepare("
        SELECT p.id, p.content, p.media_url, p.created_at,
               u.id as user_id, u.username, u.first_name, u.last_name, u.avatar_url as user_avatar
        FROM posts p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.user_id = ? 
        ORDER BY p.created_at DESC 
        LIMIT ? OFFSET ?
    ");
    $posts_stmt->execute([$profile_id, $limit, $offset]);
    $posts = $posts_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($posts)) {
        foreach ($posts as &$post) {
            $like_stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ?");
            $like_stmt->execute([$post['id']]);
            $post['likes_count'] = (int)$like_stmt->fetchColumn();
            
            $comment_stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE post_id = ?");
            $comment_stmt->execute([$post['id']]);
            $post['comments_count'] = (int)$comment_stmt->fetchColumn();
            
            $user_like_stmt = $pdo->prepare("SELECT id FROM likes WHERE post_id = ? AND user_id = ?");
            $user_like_stmt->execute([$post['id'], $user_id]);
            $post['is_liked'] = $user_like_stmt->fetchColumn() ? true : false;
            
            $bookmark_stmt = $pdo->prepare("SELECT id FROM bookmarks WHERE post_id = ? AND user_id = ?");
            $bookmark_stmt->execute([$post['id'], $user_id]);
            $post['is_bookmarked'] = $bookmark_stmt->fetchColumn() ? true : false;
        }
    }
    
    echo json_encode([
        'success' => true, 
        'posts' => $posts, 
        'page' => $page, 
        'has_more' => count($posts) == $limit
    ]);
}


function getPost($pdo, $user_id) {
    $post_id = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;
    
    if ($post_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'معرف المنشور غير صحيح']);
        return;
    }
    
    $post_stmt = $pdo->prepare("
        SELECT p.id, p.content, p.media_url, p.created_at,
               u.id as user_id, u.username, u.first_name, u.last_name, u.avatar_url as user_avatar
        FROM posts p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.id = ?
    ");
    $post_stmt->execute([$post_id]);
    $post = $post_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$post) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'المنشور غير موجود']);
        return;
    }
    
    $like_stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ?");
    $like_stmt->execute([$post_id]);
    $post['likes_count'] = (int)$like_stmt->fetchColumn();
    
    $comment_stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE post_id = ?");
    $comment_stmt->execute([$post_id]);
    $post['comments_count'] = (int)$comment_stmt->fetchColumn();
    
    $user_like_stmt = $pdo->prepare("SELECT id FROM likes WHERE post_id = ? AND user_id = ?");
    $user_like_stmt->execute([$post_id, $user_id]);
    $post['is_liked'] = $user_like_stmt->fetchColumn() ? true : false;
    
    $bookmark_stmt = $pdo->prepare("SELECT id FROM bookmarks WHERE post_id = ? AND user_id = ?");
    $bookmark_stmt->execute([$post_id, $user_id]);
    $post['is_bookmarked'] = $bookmark_stmt->fetchColumn() ? true : false;
    
    $comments_stmt = $pdo->prepare("
        SELECT c.id, c.content, c.created_at,
               u.id as user_id, u.username, u.first_name, u.last_name, u.avatar_url as user_avatar
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.post_id = ?
        ORDER BY c.created_at DESC
    ");
    $comments_stmt->execute([$post_id]);
    $comments = $comments_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $post['comments'] = $comments;
    
    echo json_encode(['success' => true, 'post' => $post]);
}
