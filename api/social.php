<?php

require_once '../config.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

function authenticateUser() {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        sendError('يجب تسجيل الدخول أولاً', 401);
    }
    return (int)$_SESSION['user_id'];
}

function sendSuccess($data = null, $message = null) {
    $response = ['success' => true];
    if ($message) $response['message'] = $message;
    if ($data !== null) $response['data'] = $data;
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit();
}

function sendError($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit();
}

function checkPostAccess($pdo, $post_id, $user_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT p.id, p.user_id, COALESCE(p.is_private, 0) as is_private 
            FROM posts p
            WHERE p.id = ? AND (
                COALESCE(p.is_private, 0) = 0 OR 
                p.user_id = ?
            )
        ");
        $stmt->execute([$post_id, $user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            $stmt = $pdo->prepare("SELECT p.id, p.user_id, COALESCE(p.is_private, 0) as is_private FROM posts p WHERE p.id = ?");
            $stmt->execute([$post_id]);
            $post_exists = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($post_exists) {
                return $post_exists;
            }
        }
        
        return $result;
    } catch (PDOException $e) {
        error_log("checkPostAccess error: " . $e->getMessage());
        return false;
    }
}

function createNotification($pdo, $user_id, $from_user_id, $type, $reference_id) {
    if ($user_id == $from_user_id) return; 
    
    try {
        $check_stmt = $pdo->prepare("
            SELECT id FROM notifications 
            WHERE user_id = ? AND from_user_id = ? AND type = ? AND reference_id = ? 
            AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ");
        $check_stmt->execute([$user_id, $from_user_id, $type, $reference_id]);
        
        if ($check_stmt->rowCount() > 0) {
            return true;
        }
        
        $create_table_sql = "CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            from_user_id INT NOT NULL,
            type ENUM('like', 'comment', 'follow', 'share', 'mention') NOT NULL,
            reference_id INT NULL,
            message TEXT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_created_at (created_at),
            INDEX idx_is_read (is_read)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($create_table_sql);
        
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, from_user_id, type, reference_id, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$user_id, $from_user_id, $type, $reference_id]);
        
        error_log("Notification created: User {$from_user_id} -> User {$user_id}, Type: {$type}, Ref: {$reference_id}");
        
        return true;
    } catch (PDOException $e) {
        error_log("Failed to create notification: " . $e->getMessage());
        return false;
    }
}

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $method = $_SERVER['REQUEST_METHOD'];
    $user_id = authenticateUser();
    
    switch ($method) {
        case 'HEAD':
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Social API is working']);
            exit();
            break;
            
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                error_log("Invalid JSON input in social.php");
                sendError('بيانات غير صحيحة');
            }
            
            $action = $input['action'] ?? '';
            error_log("Social API - POST Action: {$action}");
            
            $action = $input['action'] ?? '';
            
            switch ($action) {
                case 'add_comment':
                    $post_id = (int)($input['post_id'] ?? 0);
                    $content = trim($input['content'] ?? '');
                    
                    if (!$post_id || !$content) {
                        sendError('معرف المنشور والمحتوى مطلوبان');
                    }
                    
                    if (strlen($content) > 1000) {
                        sendError('التعليق طويل جداً (الحد الأقصى 1000 حرف)');
                    }
                    
                    $post = checkPostAccess($pdo, $post_id, $user_id);
                    if (!$post) {
                        sendError('المنشور غير موجود أو لا يمكن الوصول إليه', 404);
                    }
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO comments (post_id, user_id, content, created_at) 
                        VALUES (?, ?, ?, NOW())
                    ");
                    $stmt->execute([$post_id, $user_id, $content]);
                    $comment_id = $pdo->lastInsertId();
                    
                    createNotification($pdo, $post['user_id'], $user_id, 'comment', $post_id);
                    
                    $stmt = $pdo->prepare("
                        SELECT c.*, u.username, u.first_name, u.last_name, u.avatar_url
                        FROM comments c
                        JOIN users u ON c.user_id = u.id
                        WHERE c.id = ?
                    ");
                    $stmt->execute([$comment_id]);
                    $comment = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    sendSuccess($comment, 'تم إضافة التعليق بنجاح');
                    break;
                    
                case 'delete_comment':
                    $comment_id = (int)($input['comment_id'] ?? 0);
                    
                    if (!$comment_id) {
                        sendError('معرف التعليق مطلوب');
                    }
                    
                    $stmt = $pdo->prepare("
                        SELECT c.*, p.user_id as post_owner_id 
                        FROM comments c 
                        JOIN posts p ON c.post_id = p.id 
                        WHERE c.id = ?
                    ");
                    $stmt->execute([$comment_id]);
                    $comment = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$comment) {
                        sendError('التعليق غير موجود', 404);
                    }
                    
                    if ($comment['user_id'] != $user_id && $comment['post_owner_id'] != $user_id) {
                        sendError('غير مصرح لك بحذف هذا التعليق', 403);
                    }
                    
                    try {
                        $pdo->prepare("DELETE FROM comment_likes WHERE comment_id = ?")->execute([$comment_id]);
                        $stmt_check = $pdo->query("SHOW TABLES LIKE 'comment_dislikes'");
                        if ($stmt_check->rowCount() > 0) {
                            $pdo->prepare("DELETE FROM comment_dislikes WHERE comment_id = ?")->execute([$comment_id]);
                        }
                    } catch (PDOException $e) {
                        error_log("Warning: Could not delete related comment data: " . $e->getMessage());
                    }
                    
                    $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
                    $stmt->execute([$comment_id]);
                    
                    if ($stmt->rowCount() > 0) {
                        sendSuccess(null, 'تم حذف التعليق بنجاح');
                    } else {
                        sendError('فشل في حذف التعليق');
                    }
                    break;
                    
                case 'toggle_like':
                    $post_id = (int)($input['post_id'] ?? 0);
                    $type = $input['type'] ?? 'post';
                    
                    if (!$post_id) {
                        sendError('معرف المنشور مطلوب');
                    }
                    
                    $target_id = $post_id;
                    $original_post_id = $post_id;
                    
                    if ($type === 'comment') {
                        $stmt = $pdo->prepare("SELECT post_id FROM comments WHERE id = ?");
                        $stmt->execute([$post_id]);
                        $comment = $stmt->fetch(PDO::FETCH_ASSOC);
                        if (!$comment) {
                            sendError('التعليق غير موجود', 404);
                        }
                        $original_post_id = $comment['post_id'];
                    }
                    
                    $post = checkPostAccess($pdo, $original_post_id, $user_id);
                    if (!$post) {
                        sendError('المنشور غير موجود أو لا يمكن الوصول إليه', 404);
                    }
                    
                    $table = ($type === 'post') ? 'likes' : 'comment_likes';
                    $column = ($type === 'post') ? 'post_id' : 'comment_id';
                    
                    $stmt = $pdo->prepare("SELECT id FROM $table WHERE $column = ? AND user_id = ?");
                    $stmt->execute([$target_id, $user_id]);
                    $existing_like = $stmt->fetch();
                    
                    if ($existing_like) {
                        $stmt = $pdo->prepare("DELETE FROM $table WHERE $column = ? AND user_id = ?");
                        $stmt->execute([$target_id, $user_id]);
                        $is_liked = false;
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO $table ($column, user_id, created_at) VALUES (?, ?, NOW())");
                        $stmt->execute([$target_id, $user_id]);
                        $is_liked = true;
                        
                        if ($type === 'post') {
                            createNotification($pdo, $post['user_id'], $user_id, 'like', $original_post_id);
                        }
                    }
                    
                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM $table WHERE $column = ?");
                    $stmt->execute([$target_id]);
                    $like_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                    
                    sendSuccess([
                        'is_liked' => $is_liked,
                        'like_count' => (int)$like_count
                    ]);
                    break;
                    
                case 'toggle_bookmark':
                    $post_id = (int)($input['post_id'] ?? 0);
                    
                    if (!$post_id) {
                        sendError('معرف المنشور مطلوب');
                    }
                    
                    $post = checkPostAccess($pdo, $post_id, $user_id);
                    if (!$post) {
                        sendError('المنشور غير موجود أو لا يمكن الوصول إليه', 404);
                    }
                    
                    $stmt = $pdo->prepare("SELECT id FROM bookmarks WHERE post_id = ? AND user_id = ?");
                    $stmt->execute([$post_id, $user_id]);
                    $existing_bookmark = $stmt->fetch();
                    
                    if ($existing_bookmark) {
                        $stmt = $pdo->prepare("DELETE FROM bookmarks WHERE post_id = ? AND user_id = ?");
                        $stmt->execute([$post_id, $user_id]);
                        $is_bookmarked = false;
                        $message = 'تم إزالة المنشور من المفضلة';
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO bookmarks (post_id, user_id, created_at) VALUES (?, ?, NOW())");
                        $stmt->execute([$post_id, $user_id]);
                        $is_bookmarked = true;
                        $message = 'تم حفظ المنشور في المفضلة';
                    }
                    
                    sendSuccess([
                        'is_bookmarked' => $is_bookmarked
                    ], $message);
                    break;
                    
                case 'share_post':
                    $post_id = (int)($input['post_id'] ?? 0);
                    $share_content = trim($input['content'] ?? '');
                    
                    if (!$post_id) {
                        sendError('معرف المنشور مطلوب');
                    }
                    
                    $post = checkPostAccess($pdo, $post_id, $user_id);
                    if (!$post) {
                        sendError('المنشور غير موجود أو لا يمكن الوصول إليه', 404);
                    }
                    
                    $stmt = $pdo->prepare("SELECT id FROM shares WHERE post_id = ? AND user_id = ?");
                    $stmt->execute([$post_id, $user_id]);
                    
                    if ($stmt->fetch()) {
                        sendError('لقد قمت بمشاركة هذا المنشور من قبل');
                    }
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO shares (post_id, user_id, content, created_at) 
                        VALUES (?, ?, ?, NOW())
                    ");
                    $stmt->execute([$post_id, $user_id, $share_content]);
                    
                    createNotification($pdo, $post['user_id'], $user_id, 'share', $post_id);
                    
                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM shares WHERE post_id = ?");
                    $stmt->execute([$post_id]);
                    $share_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                    
                    sendSuccess([
                        'share_count' => (int)$share_count
                    ], 'تم مشاركة المنشور بنجاح');
                    break;
                    
                case 'toggle_comment_like':
                    $comment_id = (int)($input['comment_id'] ?? 0);
                    
                    if (!$comment_id) {
                        sendError('معرف التعليق مطلوب');
                    }
                    
                    $stmt = $pdo->prepare("
                        SELECT c.*, p.user_id as post_owner_id 
                        FROM comments c 
                        JOIN posts p ON c.post_id = p.id 
                        WHERE c.id = ?
                    ");
                    $stmt->execute([$comment_id]);
                    $comment = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$comment) {
                        sendError('التعليق غير موجود', 404);
                    }
                    
                    $post = checkPostAccess($pdo, $comment['post_id'], $user_id);
                    if (!$post) {
                        sendError('المنشور غير موجود أو لا يمكن الوصول إليه', 404);
                    }
                    
                    $stmt = $pdo->prepare("SELECT id FROM comment_likes WHERE comment_id = ? AND user_id = ?");
                    $stmt->execute([$comment_id, $user_id]);
                    $existing_like = $stmt->fetch();
                    
                    if ($existing_like) {
                        $stmt = $pdo->prepare("DELETE FROM comment_likes WHERE comment_id = ? AND user_id = ?");
                        $stmt->execute([$comment_id, $user_id]);
                        $is_liked = false;
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO comment_likes (comment_id, user_id, created_at) VALUES (?, ?, NOW())");
                        $stmt->execute([$comment_id, $user_id]);
                        $is_liked = true;
                    }
                    
                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM comment_likes WHERE comment_id = ?");
                    $stmt->execute([$comment_id]);
                    $like_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                    
                    sendSuccess([
                        'is_liked' => $is_liked,
                        'like_count' => (int)$like_count
                    ]);
                    break;
                    
                case 'follow_user':
                    $target_user_id = (int)($input['user_id'] ?? 0);
                    
                    if (!$target_user_id) {
                        sendError('معرف المستخدم مطلوب');
                    }
                    
                    if ($target_user_id == $user_id) {
                        sendError('لا يمكنك متابعة نفسك');
                    }
                    
                    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE id = ?");
                    $stmt->execute([$target_user_id]);
                    $target_user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$target_user) {
                        sendError('المستخدم غير موجود', 404);
                    }
                    
                    $stmt = $pdo->prepare("
                        SELECT id FROM followers 
                        WHERE follower_id = ? AND followed_id = ?
                    ");
                    $stmt->execute([$user_id, $target_user_id]);
                    $existing_relation = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($existing_relation) {
                        sendError('أنت تتابع هذا المستخدم بالفعل');
                    }
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO followers (follower_id, followed_id, created_at) 
                        VALUES (?, ?, NOW())
                    ");
                    $stmt->execute([$user_id, $target_user_id]);
                    
                    createNotification($pdo, $target_user_id, $user_id, 'follow', $user_id);
                    
                    sendSuccess([
                        'followed_user' => $target_user['username']
                    ], 'تم متابعة المستخدم بنجاح');
                    break;
                    
                case 'unfollow_user':
                    $target_user_id = (int)($input['user_id'] ?? 0);
                    
                    if (!$target_user_id) {
                        sendError('معرف المستخدم مطلوب');
                    }
                    
                    $stmt = $pdo->prepare("
                        DELETE FROM followers 
                        WHERE follower_id = ? AND followed_id = ?
                    ");
                    $stmt->execute([$user_id, $target_user_id]);
                    
                    if ($stmt->rowCount() > 0) {
                        sendSuccess(null, 'تم إلغاء المتابعة بنجاح');
                    } else {
                        sendError('أنت لا تتابع هذا المستخدم');
                    }
                    break;
                    
                case 'toggle_follow':
                    $target_user_id = (int)($input['user_id'] ?? 0);
                    
                    if (!$target_user_id) {
                        sendError('معرف المستخدم مطلوب');
                    }
                    
                    if ($target_user_id === $user_id) {
                        sendError('لا يمكنك متابعة نفسك');
                    }
                    
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
                    $stmt->execute([$target_user_id]);
                    if (!$stmt->fetch()) {
                        sendError('المستخدم غير موجود', 404);
                    }
                    
                    $stmt = $pdo->prepare("SELECT id FROM followers WHERE follower_id = ? AND followed_id = ?");
                    $stmt->execute([$user_id, $target_user_id]);
                    $existing_follow = $stmt->fetch();
                    
                    if ($existing_follow) {
                        $stmt = $pdo->prepare("DELETE FROM followers WHERE follower_id = ? AND followed_id = ?");
                        $stmt->execute([$user_id, $target_user_id]);
                        $is_following = false;
                        $message = 'تم إلغاء المتابعة بنجاح';
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO followers (follower_id, followed_id, created_at) VALUES (?, ?, NOW())");
                        $stmt->execute([$user_id, $target_user_id]);
                        $is_following = true;
                        $message = 'تمت المتابعة بنجاح';
                        
                        createNotification($pdo, $target_user_id, $user_id, 'follow', null);
                    }
                    
                    $stmt = $pdo->prepare("
                        SELECT 
                            (SELECT COUNT(*) FROM followers WHERE followed_id = ?) as followers_count,
                            (SELECT COUNT(*) FROM followers WHERE follower_id = ?) as following_count
                    ");
                    $stmt->execute([$target_user_id, $target_user_id]);
                    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    sendSuccess([
                        'is_following' => $is_following,
                        'followers_count' => (int)$stats['followers_count'],
                        'following_count' => (int)$stats['following_count']
                    ], $message);
                    break;
                    
                default:
                    sendError('إجراء غير صحيح');
            }
            break;
            
        case 'GET':
            $action = $_GET['action'] ?? 'get_comments';
            
            switch ($action) {
                case 'get_comments':
                    $post_id = (int)($_GET['post_id'] ?? 0);
                    $page = max(1, (int)($_GET['page'] ?? 1));
                    $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
                    $offset = ($page - 1) * $limit;
                    
                    if (!$post_id) {
                        sendError('معرف المنشور مطلوب');
                    }
                    
                    $post = checkPostAccess($pdo, $post_id, $user_id);
                    if (!$post) {
                        sendError('المنشور غير موجود أو لا يمكن الوصول إليه', 404);
                    }
                    
                    $stmt = $pdo->prepare("
                        SELECT c.*, u.username, u.first_name, u.last_name, u.avatar_url,
                               COALESCE((SELECT COUNT(*) FROM comment_likes cl WHERE cl.comment_id = c.id), 0) as like_count,
                               COALESCE((SELECT COUNT(*) FROM comment_likes cl WHERE cl.comment_id = c.id AND cl.user_id = ?), 0) as user_liked
                        FROM comments c
                        JOIN users u ON c.user_id = u.id
                        WHERE c.post_id = ?
                        ORDER BY c.created_at DESC
                        LIMIT ? OFFSET ?
                    ");
                    $stmt->execute([$user_id, $post_id, $limit, $offset]);
                    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($comments as &$comment) {
                        $comment['user_liked'] = (bool)$comment['user_liked'];
                        $comment['like_count'] = (int)$comment['like_count'];
                        $comment['user_disliked'] = false;
                        $comment['dislike_count'] = 0;
                    }
                    
                    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM comments WHERE post_id = ?");
                    $stmt->execute([$post_id]);
                    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                    
                    sendSuccess([
                        'comments' => $comments,
                        'pagination' => [
                            'current_page' => $page,
                            'total_pages' => ceil($total / $limit),
                            'total_comments' => (int)$total,
                            'per_page' => $limit
                        ]
                    ]);
                    break;
                    
                case 'get_comments_count':
                    $post_id = (int)($_GET['post_id'] ?? 0);
                    
                    if (!$post_id) {
                        sendError('معرف المنشور مطلوب');
                    }
                    
                    try {
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE post_id = ?");
                        $stmt->execute([$post_id]);
                        $count = (int)$stmt->fetchColumn();
                        
                        sendSuccess(['count' => $count]);
                    } catch (Exception $e) {
                        error_log("Error getting comments count: " . $e->getMessage());
                        sendError('حدث خطأ أثناء جلب عدد التعليقات');
                    }
                    break;
                    
                case 'get_post_stats':
                    $post_id = (int)($_GET['post_id'] ?? 0);
                    
                    if (!$post_id) {
                        sendError('معرف المنشور مطلوب');
                    }
                    
                    $post = checkPostAccess($pdo, $post_id, $user_id);
                    if (!$post) {
                        sendError('المنشور غير موجود أو لا يمكن الوصول إليه', 404);
                    }
                    
                    $stmt = $pdo->prepare("
                        SELECT 
                            (SELECT COUNT(*) FROM likes WHERE post_id = ?) as like_count,
                            (SELECT COUNT(*) FROM post_dislikes WHERE post_id = ?) as dislike_count,
                            (SELECT COUNT(*) FROM comments WHERE post_id = ?) as comment_count,
                            (SELECT COUNT(*) FROM shares WHERE post_id = ?) as share_count,
                            (SELECT COUNT(*) FROM likes WHERE post_id = ? AND user_id = ?) as user_liked,
                            (SELECT COUNT(*) FROM post_dislikes WHERE post_id = ? AND user_id = ?) as user_disliked,
                            (SELECT COUNT(*) FROM shares WHERE post_id = ? AND user_id = ?) as user_shared,
                            (SELECT COUNT(*) FROM bookmarks WHERE post_id = ? AND user_id = ?) as user_bookmarked
                    ");
                    $stmt->execute([
                        $post_id, $post_id, $post_id, $post_id, 
                        $post_id, $user_id, $post_id, $user_id, 
                        $post_id, $user_id, $post_id, $user_id
                    ]);
                    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $stats['user_liked'] = (bool)$stats['user_liked'];
                    $stats['user_disliked'] = (bool)$stats['user_disliked'];
                    $stats['user_shared'] = (bool)$stats['user_shared'];
                    $stats['user_bookmarked'] = (bool)$stats['user_bookmarked'];
                    $stats['like_count'] = (int)$stats['like_count'];
                    $stats['dislike_count'] = (int)$stats['dislike_count'];
                    $stats['comment_count'] = (int)$stats['comment_count'];
                    $stats['share_count'] = (int)$stats['share_count'];
                    
                    sendSuccess($stats);
                    break;
                    
                default:
                    sendError('إجراء غير صحيح');
            }
            break;
            
        default:
            sendError('طريقة HTTP غير مدعومة', 405);
    }
    
} catch (PDOException $e) {
    error_log("Database error in social.php: " . $e->getMessage());
    sendError('حدث خطأ في قاعدة البيانات');
} catch (Exception $e) {
    error_log("General error in social.php: " . $e->getMessage());
    sendError('حدث خطأ غير متوقع');
}
?> 