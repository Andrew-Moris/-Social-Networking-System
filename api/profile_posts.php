<?php

session_start();
require_once '../config.php';
require_once '../functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'غير مصرح. يرجى تسجيل الدخول.']);
    exit;
}

function log_error($message) {
    error_log("[PROFILE_POSTS] " . $message);
}

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES utf8mb4");
    
    $action = $_SERVER['REQUEST_METHOD'] === 'GET' ? ($_GET['action'] ?? 'get_posts') : ($_POST['action'] ?? '');
    
    log_error("Requested action: {$action}");
    
    switch ($action) {
        case 'get_posts':
            getPosts($pdo);
            break;
            
        case 'create_post':
            createPost($pdo);
            break;
            
        case 'delete_post':
            deletePost($pdo);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'إجراء غير مدعوم']);
            break;
    }
    
} catch (PDOException $e) {
    log_error("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'حدث خطأ في قاعدة البيانات: ' . $e->getMessage()]);
} catch (Exception $e) {
    log_error("General error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'حدث خطأ: ' . $e->getMessage()]);
}

/**
 * 
 * @param PDO 
 */
function getPosts($pdo) {
    $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : $_SESSION['user_id'];
    $current_user_id = $_SESSION['user_id'];
    
    log_error("Getting posts for user ID: {$user_id}");
    
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, 
                   u.username, 
                   u.first_name, 
                   u.last_name,
                   '' as avatar_url,
                   (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
                   (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count,
                   (SELECT COUNT(*) > 0 FROM likes WHERE post_id = p.id AND user_id = ?) as is_liked,
                   (SELECT COUNT(*) > 0 FROM bookmarks WHERE post_id = p.id AND user_id = ?) as is_bookmarked
            FROM posts p 
            JOIN users u ON p.user_id = u.id
            WHERE p.user_id = ? 
            ORDER BY p.created_at DESC
        ");
        $stmt->execute([$current_user_id, $current_user_id, $user_id]);
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($posts as &$post) {
            $post['is_liked'] = (bool)$post['is_liked'];
            $post['is_bookmarked'] = (bool)$post['is_bookmarked'];
            
            $post['user_avatar'] = $post['avatar_url'] ?? '';
            $post['avatar'] = $post['avatar_url'] ?? '';
            
            if (isset($post['created_at'])) {
                $post['created_at'] = date('Y-m-d H:i:s', strtotime($post['created_at']));
            }
        }
        
        echo json_encode(['success' => true, 'posts' => $posts]);
    } catch (Exception $e) {
        log_error("Error getting posts: " . $e->getMessage());
        throw new Exception('فشل في الحصول على المنشورات');
    }
}

/**
 * 
 * @param PDO 
 */
function createPost($pdo) {
    $user_id = $_SESSION['user_id'];
    $content = trim($_POST['content'] ?? '');
    $media_url = trim($_POST['media_url'] ?? '');
    
    log_error("Creating post for user ID: {$user_id}, content length: " . strlen($content) . ", media: {$media_url}");
    
    if (empty($content) && empty($media_url)) {
        echo json_encode(['success' => false, 'message' => 'يجب إدخال نص للمنشور أو إضافة صورة']);
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, media_url, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$user_id, $content, $media_url]);
        $post_id = $pdo->lastInsertId();
        
        $stmt = $pdo->prepare("
            SELECT p.*, 
                   u.username, 
                   u.first_name, 
                   u.last_name,
                   '' as avatar_url,
                   0 as likes_count,
                   0 as comments_count,
                   0 as is_liked,
                   0 as is_bookmarked,
                   u.id as user_id
            FROM posts p 
            JOIN users u ON p.user_id = u.id
            WHERE p.id = ?
        ");
        $stmt->execute([$post_id]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$post) {
            log_error("Post not found after creation: {$post_id}");
            throw new Exception('لم يتم العثور على المنشور بعد إنشائه');
        }
        
        $post['user_avatar'] = $post['avatar_url'] ?? '';
        $post['avatar'] = $post['avatar_url'] ?? ''; 
        $post['liked'] = false;
        $post['bookmarked'] = false;
        
        if (isset($post['created_at'])) {
            $post['created_at'] = date('Y-m-d H:i:s', strtotime($post['created_at']));
        }
        
        $pdo->commit();
        
        echo json_encode(['success' => true, 'message' => 'تم إنشاء المنشور بنجاح', 'post' => $post]);
    } catch (Exception $e) {
        $pdo->rollBack();
        log_error("Error creating post: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'فشل في إنشاء المنشور: ' . $e->getMessage()]);
    }
}

/**
 * 
 * @param PDO 
 */
function deletePost($pdo) {
    $user_id = $_SESSION['user_id'];
    $post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
    
    if (!$post_id) {
        echo json_encode(['success' => false, 'message' => 'معرف المنشور غير صالح']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM posts WHERE id = ? AND user_id = ?");
        $stmt->execute([$post_id, $user_id]);
        
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'لا يمكنك حذف هذا المنشور']);
            return;
        }
        
        $stmt = $pdo->prepare("SELECT media_url FROM posts WHERE id = ?");
        $stmt->execute([$post_id]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
        $media_url = $post['media_url'] ?? '';
        
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("DELETE FROM likes WHERE post_id = ?");
        $stmt->execute([$post_id]);
        
        $stmt = $pdo->prepare("DELETE FROM comments WHERE post_id = ?");
        $stmt->execute([$post_id]);
        
        $stmt = $pdo->prepare("DELETE FROM bookmarks WHERE post_id = ?");
        $stmt->execute([$post_id]);
        
        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
        $stmt->execute([$post_id]);
        
        $pdo->commit();
        
        if (!empty($media_url)) {
            $file_path = __DIR__ . '/../' . $media_url;
            if (file_exists($file_path)) {
                @unlink($file_path);
                log_error("Deleted media file: {$file_path}");
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'تم حذف المنشور بنجاح']);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        log_error("Error deleting post: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'فشل في حذف المنشور: ' . $e->getMessage()]);
    }
}