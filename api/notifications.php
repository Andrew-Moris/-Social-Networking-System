<?php


session_start();
require_once '../config.php';
require_once '../functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'غير مصرح بالوصول'], JSON_UNESCAPED_UNICODE);
    exit;
}

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    createNotificationsTable($pdo);
    
    switch ($method) {
        case 'GET':
            handleGetNotifications($pdo, $user_id);
            break;
            
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            handleCreateNotification($pdo, $user_id, $input);
            break;
            
        case 'PUT':
            $input = json_decode(file_get_contents('php://input'), true);
            handleMarkAsRead($pdo, $user_id, $input);
            break;
            
        case 'DELETE':
            $input = json_decode(file_get_contents('php://input'), true);
            handleDeleteNotification($pdo, $user_id, $input);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'طريقة غير مدعومة'], JSON_UNESCAPED_UNICODE);
            break;
    }
    
} catch (PDOException $e) {
    error_log("Database Error in notifications.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'حدث خطأ في قاعدة البيانات'], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log("General Error in notifications.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'حدث خطأ غير متوقع'], JSON_UNESCAPED_UNICODE);
}

function createNotificationsTable($pdo) {
    $sql = "CREATE TABLE IF NOT EXISTS notifications (
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
        INDEX idx_is_read (is_read),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (from_user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
}

function handleGetNotifications($pdo, $user_id) {
    $page = (int)($_GET['page'] ?? 1);
    $limit = min(50, (int)($_GET['limit'] ?? 20));
    $offset = ($page - 1) * $limit;
    $unread_only = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';
    
    $where_clause = "n.user_id = ?";
    $params = [$user_id];
    
    if ($unread_only) {
        $where_clause .= " AND n.is_read = 0";
    }
    
    $sql = "
        SELECT n.*, 
               u.username, u.first_name, u.last_name, u.avatar_url,
               CASE 
                   WHEN n.type = 'like' THEN 'أعجب بمنشورك'
                   WHEN n.type = 'comment' THEN 'علق على منشورك'
                   WHEN n.type = 'follow' THEN 'بدأ في متابعتك'
                   WHEN n.type = 'share' THEN 'شارك منشورك'
                   WHEN n.type = 'mention' THEN 'ذكرك في منشور'
                   ELSE 'إشعار جديد'
               END as action_text
        FROM notifications n
        JOIN users u ON n.from_user_id = u.id
        WHERE {$where_clause}
        ORDER BY n.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($notifications as &$notification) {
        $notification['is_read'] = (bool)$notification['is_read'];
        $notification['time_ago'] = timeAgo($notification['created_at']);
        $notification['avatar'] = $notification['avatar_url'] ?: 
            "https://ui-avatars.com/api/?name=" . urlencode($notification['username']) . "&background=3B82F6&color=fff";
        $notification['display_name'] = trim($notification['first_name'] . ' ' . $notification['last_name']) ?: $notification['username'];
    }
    
    $unread_stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $unread_stmt->execute([$user_id]);
    $unread_count = $unread_stmt->fetchColumn();
    
    $total_stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ?");
    $total_stmt->execute([$user_id]);
    $total_count = $total_stmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => (int)$unread_count,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => ceil($total_count / $limit),
            'total_count' => (int)$total_count,
            'per_page' => $limit
        ]
    ], JSON_UNESCAPED_UNICODE);
}

function handleCreateNotification($pdo, $user_id, $input) {
    $action = $input['action'] ?? '';
    
    if ($action !== 'create_notification') {
        echo json_encode(['success' => false, 'message' => 'إجراء غير صحيح'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $target_user_id = (int)($input['target_user_id'] ?? 0);
    $type = $input['type'] ?? '';
    $reference_id = (int)($input['reference_id'] ?? 0);
    $message = trim($input['message'] ?? '');
    
    if (!$target_user_id || !$type) {
        echo json_encode(['success' => false, 'message' => 'معرف المستخدم ونوع الإشعار مطلوبان'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    if ($target_user_id == $user_id) {
        echo json_encode(['success' => true, 'message' => 'تم تجاهل الإشعار (نفس المستخدم)'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $check_stmt = $pdo->prepare("
        SELECT id FROM notifications 
        WHERE user_id = ? AND from_user_id = ? AND type = ? AND reference_id = ? 
        AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
    ");
    $check_stmt->execute([$target_user_id, $user_id, $type, $reference_id]);
    
    if ($check_stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'الإشعار موجود بالفعل'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $insert_stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, from_user_id, type, reference_id, message, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    
    if ($insert_stmt->execute([$target_user_id, $user_id, $type, $reference_id, $message])) {
        echo json_encode(['success' => true, 'message' => 'تم إنشاء الإشعار بنجاح'], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['success' => false, 'message' => 'فشل في إنشاء الإشعار'], JSON_UNESCAPED_UNICODE);
    }
}

function handleMarkAsRead($pdo, $user_id, $input) {
    $action = $input['action'] ?? '';
    
    if ($action === 'mark_all_read') {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
        if ($stmt->execute([$user_id])) {
            echo json_encode(['success' => true, 'message' => 'تم تحديد جميع الإشعارات كمقروءة'], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['success' => false, 'message' => 'فشل في تحديث الإشعارات'], JSON_UNESCAPED_UNICODE);
        }
    } elseif ($action === 'mark_read') {
        $notification_id = (int)($input['notification_id'] ?? 0);
        
        if (!$notification_id) {
            echo json_encode(['success' => false, 'message' => 'معرف الإشعار مطلوب'], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$notification_id, $user_id])) {
            echo json_encode(['success' => true, 'message' => 'تم تحديد الإشعار كمقروء'], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['success' => false, 'message' => 'فشل في تحديث الإشعار'], JSON_UNESCAPED_UNICODE);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'إجراء غير صحيح'], JSON_UNESCAPED_UNICODE);
    }
}

function handleDeleteNotification($pdo, $user_id, $input) {
    $action = $input['action'] ?? '';
    $notification_id = (int)($input['notification_id'] ?? 0);
    
    if ($action !== 'delete_notification' || !$notification_id) {
        echo json_encode(['success' => false, 'message' => 'معرف الإشعار مطلوب'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
    
    if ($stmt->execute([$notification_id, $user_id])) {
        echo json_encode(['success' => true, 'message' => 'تم حذف الإشعار بنجاح'], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['success' => false, 'message' => 'فشل في حذف الإشعار'], JSON_UNESCAPED_UNICODE);
    }
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'الآن';
    if ($time < 3600) return floor($time / 60) . ' د';
    if ($time < 86400) return floor($time / 3600) . ' س';
    if ($time < 2592000) return floor($time / 86400) . ' يوم';
    if ($time < 31536000) return floor($time / 2592000) . ' شهر';
    
    return floor($time / 31536000) . ' سنة';
}
?> 