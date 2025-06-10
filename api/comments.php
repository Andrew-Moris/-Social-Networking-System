<?php
    session_start();
require_once '../config.php';
require_once '../functions.php';

$publicActions = ['get_comments', 'get_post_stats'];
$action = $_SERVER['REQUEST_METHOD'] === 'GET' ? ($_GET['action'] ?? '') : ($_POST['action'] ?? '');

if (!isset($_SESSION['user_id']) && !in_array($action, $publicActions)) {
    echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول أولاً']);
    exit;
}

header('Content-Type: application/json');

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES utf8mb4");
    
    error_log("Comments API - Action: {$action}");
    
    switch ($action) {
        case 'get_comments':
            getComments($pdo);
            break;
            
        case 'add_comment':
            addComment($pdo);
            break;
        
        case 'delete_comment':
            deleteComment($pdo);
            break;
            
        case 'edit_comment':
            editComment($pdo);
            break;
            
        case 'toggle_comment_like':
            toggleCommentLike($pdo);
            break;
            
        case 'get_post_stats':
            getPostStats($pdo);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'إجراء غير مدعوم']);
    }
    
} catch (PDOException $e) {
    error_log("Database Error in comments.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'حدث خطأ في قاعدة البيانات: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("General Error in comments.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * الحصول على تعليقات المنشور
 * @param PDO $pdo - اتصال قاعدة البيانات
 */
function getComments($pdo) {
    $postId = filter_input(INPUT_GET, 'post_id', FILTER_VALIDATE_INT);
    $userId = $_SESSION['user_id'] ?? 0;
    
    if (!$postId) {
        echo json_encode(['success' => false, 'message' => 'معرف المنشور غير صالح']);
        return;
    }
    
    try {
        error_log("Getting comments for post ID: {$postId}");
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS comment_likes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            comment_id INT NOT NULL,
            user_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_comment_like (comment_id, user_id),
            FOREIGN KEY (comment_id) REFERENCES comments(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");
        
        $userColumnsQuery = "SHOW COLUMNS FROM users";
        $userColumnsStmt = $pdo->query($userColumnsQuery);
        $userColumns = $userColumnsStmt->fetchAll(PDO::FETCH_COLUMN, 0);
        
        $avatarColumn = 'username'; 
        $firstNameColumn = in_array('first_name', $userColumns) ? 'first_name' : 'username';
        $lastNameColumn = in_array('last_name', $userColumns) ? 'last_name' : "''";
        
        foreach(['avatar', 'avatar_url', 'profile_picture', 'image', 'picture'] as $possibleColumn) {
            if (in_array($possibleColumn, $userColumns)) {
                $avatarColumn = $possibleColumn;
                break;
            }
        }
        
        $query = "SELECT 
                c.*,
                u.username,
                u.{$firstNameColumn} as first_name,
                u.{$lastNameColumn} as last_name,
                u.{$avatarColumn} as avatar_url,
                c.user_id = ? as is_owner,
                (SELECT COUNT(*) FROM comment_likes WHERE comment_id = c.id) as like_count,
                (SELECT COUNT(*) > 0 FROM comment_likes WHERE comment_id = c.id AND user_id = ?) as user_liked
            FROM comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.post_id = ?
            ORDER BY c.created_at DESC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$userId, $userId, $postId]);
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($comments as &$comment) {
            $comment['created_at'] = date('Y-m-d H:i', strtotime($comment['created_at']));
            
            $comment['user_liked'] = (bool)$comment['user_liked'];
            $comment['is_owner'] = (bool)$comment['is_owner'];
            $comment['like_count'] = (int)$comment['like_count'];
            $comment['user_id'] = (int)$comment['user_id'];
            $comment['id'] = (int)$comment['id'];
            $comment['post_id'] = (int)$comment['post_id'];
        }
        
        error_log("Found " . count($comments) . " comments for post ID: {$postId}");
        
        echo json_encode([
            'success' => true,
            'comments' => $comments
        ]);
        
    } catch (Exception $e) {
        error_log("Error getting comments: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'فشل في تحميل التعليقات: ' . $e->getMessage()]);
    }
}

function addComment($pdo) {
    $postId = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
    $content = trim($_POST['content'] ?? '');
    
    if (!$postId || empty($content)) {
        echo json_encode(['success' => false, 'message' => 'بيانات غير صالحة']);
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("
            INSERT INTO comments (post_id, user_id, content)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$postId, $_SESSION['user_id'], $content]);
        $commentId = $pdo->lastInsertId();
        
        $userColumnsQuery = "SHOW COLUMNS FROM users";
        $userColumnsStmt = $pdo->query($userColumnsQuery);
        $userColumns = $userColumnsStmt->fetchAll(PDO::FETCH_COLUMN, 0);
        
        $avatarColumn = 'username'; 
        foreach(['avatar', 'avatar_url', 'profile_picture', 'image', 'picture'] as $possibleColumn) {
            if (in_array($possibleColumn, $userColumns)) {
                $avatarColumn = $possibleColumn;
                break;
            }
        }
        
        $query = "SELECT 
                c.*,
                u.username,
                u.{$avatarColumn} as avatar,
                c.user_id = ? as is_owner
            FROM comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.id = ?";
            
        $stmt = $pdo->prepare($query);
        $stmt->execute([$_SESSION['user_id'], $commentId]);
        $comment = $stmt->fetch();
        
        if ($comment) {
            $comment['created_at'] = date('Y-m-d H:i', strtotime($comment['created_at']));
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'comment' => $comment
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw new Exception('فشل في إضافة التعليق');
    }
}

function deleteComment($pdo) {
    $commentId = filter_input(INPUT_POST, 'comment_id', FILTER_VALIDATE_INT);
    
    if (!$commentId) {
        echo json_encode(['success' => false, 'message' => 'معرف التعليق غير صالح']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM comments WHERE id = ? AND user_id = ?");
        $stmt->execute([$commentId, $_SESSION['user_id']]);
        
        if (!$stmt->fetch()) {
            throw new Exception('لا يمكنك حذف هذا التعليق');
        }
        
        $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
        $stmt->execute([$commentId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'تم حذف التعليق بنجاح'
        ]);
        
    } catch (Exception $e) {
        throw new Exception('فشل في حذف التعليق');
    }
}

/**
 * تبديل حالة الإعجاب بالتعليق
 * @param PDO $pdo - اتصال قاعدة البيانات
 */
function toggleCommentLike($pdo) {
    $commentId = filter_input(INPUT_POST, 'comment_id', FILTER_VALIDATE_INT);
    $userId = $_SESSION['user_id'];
    
    if (!$commentId) {
        echo json_encode(['success' => false, 'message' => 'معرف التعليق غير صالح']);
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        // التحقق من وجود التعليق
        $stmt = $pdo->prepare("SELECT id FROM comments WHERE id = ?");
        $stmt->execute([$commentId]);
        
        if (!$stmt->fetch()) {
            throw new Exception('التعليق غير موجود');
        }
        
        // التحقق من وجود إعجاب مسبق
        $stmt = $pdo->prepare("SELECT id FROM comment_likes WHERE comment_id = ? AND user_id = ?");
        $stmt->execute([$commentId, $userId]);
        $existingLike = $stmt->fetch();
        
        if ($existingLike) {
            // إزالة الإعجاب
            $stmt = $pdo->prepare("DELETE FROM comment_likes WHERE comment_id = ? AND user_id = ?");
            $stmt->execute([$commentId, $userId]);
            $action = 'unlike';
        } else {
            // إضافة إعجاب
            $stmt = $pdo->prepare("INSERT INTO comment_likes (comment_id, user_id) VALUES (?, ?)");
            $stmt->execute([$commentId, $userId]);
            $action = 'like';
        }
        
        // حساب عدد الإعجابات
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM comment_likes WHERE comment_id = ?");
        $stmt->execute([$commentId]);
        $likeCount = $stmt->fetchColumn();
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'action' => $action,
            'like_count' => $likeCount
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error toggling comment like: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'فشل في تحديث حالة الإعجاب']);
    }
}

/**
 * الحصول على إحصائيات المنشور
 * @param PDO $pdo - اتصال قاعدة البيانات
 */
function getPostStats($pdo) {
    $postId = filter_input(INPUT_GET, 'post_id', FILTER_VALIDATE_INT);
    
    if (!$postId) {
        echo json_encode(['success' => false, 'message' => 'معرف المنشور غير صالح']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE post_id = ?");
        $stmt->execute([$postId]);
        $commentCount = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM post_likes WHERE post_id = ?");
        $stmt->execute([$postId]);
        $likeCount = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM post_shares WHERE post_id = ?");
        $stmt->execute([$postId]);
        $shareCount = $stmt->fetchColumn();
        
        $isLiked = false;
        if (isset($_SESSION['user_id'])) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM post_likes WHERE post_id = ? AND user_id = ?");
            $stmt->execute([$postId, $_SESSION['user_id']]);
            $isLiked = $stmt->fetchColumn() > 0;
        }
        
        echo json_encode([
            'success' => true,
            'comment_count' => $commentCount,
            'like_count' => $likeCount,
            'share_count' => $shareCount,
            'is_liked' => $isLiked
        ]);
        
    } catch (Exception $e) {
        error_log("Error getting post stats: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'فشل في الحصول على إحصائيات المنشور']);
    }
}

function editComment($pdo) {
    $commentId = filter_input(INPUT_POST, 'comment_id', FILTER_VALIDATE_INT);
    $content = trim($_POST['content'] ?? '');
    
    if (!$commentId || empty($content)) {
        echo json_encode(['success' => false, 'message' => 'بيانات غير صالحة']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM comments WHERE id = ? AND user_id = ?");
        $stmt->execute([$commentId, $_SESSION['user_id']]);
        
        if (!$stmt->fetch()) {
            throw new Exception('لا يمكنك تعديل هذا التعليق');
        }
        
        $stmt = $pdo->prepare("UPDATE comments SET content = ? WHERE id = ?");
        $stmt->execute([$content, $commentId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'تم تحديث التعليق بنجاح'
        ]);
        
    } catch (Exception $e) {
        throw new Exception('فشل في تحديث التعليق');
    }
}
?> 