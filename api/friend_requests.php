<?php


header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start();

require_once dirname(__DIR__) . '/config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'يجب تسجيل الدخول أولاً'
    ]);
    exit;
}

$current_user_id = $_SESSION['user_id'];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS friend_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_request (sender_id, receiver_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['action'])) {
        throw new Exception('بيانات الطلب غير مكتملة');
    }
    
    $action = $data['action'];
    $response = ['success' => false, 'message' => '', 'data' => []];
    
    switch ($action) {
        case 'send':
            handleSendRequest($pdo, $current_user_id, $data, $response);
            break;
            
        case 'accept':
            handleAcceptRequest($pdo, $current_user_id, $data, $response);
            break;
            
        case 'reject':
            handleRejectRequest($pdo, $current_user_id, $data, $response);
            break;
            
        case 'cancel':
            handleCancelRequest($pdo, $current_user_id, $data, $response);
            break;
            
        case 'list':
            handleListRequests($pdo, $current_user_id, $data, $response);
            break;
            
        default:
            throw new Exception('عملية غير مدعومة');
    }
    
} catch (PDOException $e) {
    error_log("خطأ قاعدة البيانات في friend_requests.php: " . $e->getMessage());
    $response = [
        'success' => false,
        'message' => 'حدث خطأ في قاعدة البيانات'
    ];
    http_response_code(500);
} catch (Exception $e) {
    error_log("خطأ في friend_requests.php: " . $e->getMessage());
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
    http_response_code(400);
}

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);


function handleSendRequest($pdo, $sender_id, $data, &$response) {
    if (!isset($data['user_id'])) {
        throw new Exception('معرف المستخدم المستهدف مطلوب');
    }
    
    $receiver_id = intval($data['user_id']);
    
    if ($receiver_id <= 0) {
        throw new Exception('معرف المستخدم غير صحيح');
    }
    
    if ($sender_id == $receiver_id) {
        throw new Exception('لا يمكنك إرسال طلب صداقة لنفسك');
    }
    
    $stmt = $pdo->prepare("SELECT id, username, first_name, last_name FROM users WHERE id = ?");
    $stmt->execute([$receiver_id]);
    $target_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$target_user) {
        throw new Exception('المستخدم المحدد غير موجود');
    }
    
    $stmt = $pdo->prepare("SELECT id, status FROM friend_requests WHERE sender_id = ? AND receiver_id = ?");
    $stmt->execute([$sender_id, $receiver_id]);
    $existing_request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing_request) {
        if ($existing_request['status'] == 'pending') {
            throw new Exception('لديك طلب صداقة معلق مع هذا المستخدم');
        } else {
            $stmt = $pdo->prepare("DELETE FROM friend_requests WHERE id = ?");
            $stmt->execute([$existing_request['id']]);
        }
    }
    
    $stmt = $pdo->prepare("SELECT id FROM friend_requests WHERE sender_id = ? AND receiver_id = ? AND status = 'pending'");
    $stmt->execute([$receiver_id, $sender_id]);
    if ($stmt->fetch()) {
        throw new Exception('هذا المستخدم أرسل لك طلب صداقة بالفعل. تحقق من الطلبات الواردة');
    }
    
    $stmt = $pdo->prepare("INSERT INTO friend_requests (sender_id, receiver_id, status) VALUES (?, ?, 'pending')");
    $result = $stmt->execute([$sender_id, $receiver_id]);
    
    if ($result) {
        $response = [
            'success' => true,
            'message' => 'تم إرسال طلب الصداقة بنجاح',
            'data' => [
                'target_user' => $target_user['username'],
                'request_id' => $pdo->lastInsertId()
            ]
        ];
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, type, title, message, data, created_at) 
                VALUES (?, 'friend_request', 'طلب صداقة جديد', ?, ?, NOW())
            ");
            $stmt->execute([
                $receiver_id,
                'لديك طلب صداقة جديد',
                json_encode(['sender_id' => $sender_id])
            ]);
        } catch (Exception $e) {
        }
    } else {
        throw new Exception('فشل في إرسال طلب الصداقة');
    }
}


function handleAcceptRequest($pdo, $current_user_id, $data, &$response) {
    $request_id = null;
    $sender_id = null;
    
    if (isset($data['request_id'])) {
        $request_id = intval($data['request_id']);
    }
    
    if (isset($data['user_id'])) {
        $sender_id = intval($data['user_id']);
    }
    
    if (!$request_id && !$sender_id) {
        throw new Exception('معرف الطلب أو معرف المستخدم مطلوب');
    }
    
    if ($request_id) {
        $stmt = $pdo->prepare("
            SELECT fr.*, u.username, u.first_name, u.last_name 
            FROM friend_requests fr 
            JOIN users u ON fr.sender_id = u.id 
            WHERE fr.id = ? AND fr.receiver_id = ? AND fr.status = 'pending'
        ");
        $stmt->execute([$request_id, $current_user_id]);
    } else {
        $stmt = $pdo->prepare("
            SELECT fr.*, u.username, u.first_name, u.last_name 
            FROM friend_requests fr 
            JOIN users u ON fr.sender_id = u.id 
            WHERE fr.sender_id = ? AND fr.receiver_id = ? AND fr.status = 'pending'
        ");
        $stmt->execute([$sender_id, $current_user_id]);
    }
    
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$request) {
        throw new Exception('طلب الصداقة غير موجود أو تم معالجته بالفعل');
    }
    
    $pdo->beginTransaction();
    
    try {
        $stmt = $pdo->prepare("UPDATE friend_requests SET status = 'accepted', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$request['id']]);
        
        $stmt = $pdo->prepare("INSERT IGNORE INTO followers (follower_id, followed_id) VALUES (?, ?)");
        $stmt->execute([$request['sender_id'], $current_user_id]);
        
        $stmt = $pdo->prepare("INSERT IGNORE INTO followers (follower_id, followed_id) VALUES (?, ?)");
        $stmt->execute([$current_user_id, $request['sender_id']]);
        
        $pdo->commit();
        
        $response = [
            'success' => true,
            'message' => 'تم قبول طلب الصداقة بنجاح',
            'data' => [
                'friend_user' => $request['username'],
                'mutual_follow' => true
            ]
        ];
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, type, title, message, data, created_at) 
                VALUES (?, 'friend_accepted', 'تم قبول طلب الصداقة', ?, ?, NOW())
            ");
            $stmt->execute([
                $request['sender_id'],
                'تم قبول طلب صداقتك',
                json_encode(['accepter_id' => $current_user_id])
            ]);
        } catch (Exception $e) {
        }
        
    } catch (Exception $e) {
        $pdo->rollback();
        throw $e;
    }
}


function handleRejectRequest($pdo, $current_user_id, $data, &$response) {
    $request_id = null;
    $sender_id = null;
    
    if (isset($data['request_id'])) {
        $request_id = intval($data['request_id']);
    }
    
    if (isset($data['user_id'])) {
        $sender_id = intval($data['user_id']);
    }
    
    if (!$request_id && !$sender_id) {
        throw new Exception('معرف الطلب أو معرف المستخدم مطلوب');
    }
    
    if ($request_id) {
        $stmt = $pdo->prepare("
            SELECT fr.*, u.username 
            FROM friend_requests fr 
            JOIN users u ON fr.sender_id = u.id 
            WHERE fr.id = ? AND fr.receiver_id = ? AND fr.status = 'pending'
        ");
        $stmt->execute([$request_id, $current_user_id]);
    } else {
        $stmt = $pdo->prepare("
            SELECT fr.*, u.username 
            FROM friend_requests fr 
            JOIN users u ON fr.sender_id = u.id 
            WHERE fr.sender_id = ? AND fr.receiver_id = ? AND fr.status = 'pending'
        ");
        $stmt->execute([$sender_id, $current_user_id]);
    }
    
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$request) {
        throw new Exception('طلب الصداقة غير موجود أو تم معالجته بالفعل');
    }
    
    $stmt = $pdo->prepare("UPDATE friend_requests SET status = 'rejected', updated_at = NOW() WHERE id = ?");
    $result = $stmt->execute([$request['id']]);
    
    if ($result) {
        $response = [
            'success' => true,
            'message' => 'تم رفض طلب الصداقة',
            'data' => [
                'rejected_user' => $request['username']
            ]
        ];
    } else {
        throw new Exception('فشل في رفض طلب الصداقة');
    }
}

function handleCancelRequest($pdo, $current_user_id, $data, &$response) {
    $request_id = null;
    $receiver_id = null;
    
    if (isset($data['request_id'])) {
        $request_id = intval($data['request_id']);
    }
    
    if (isset($data['user_id'])) {
        $receiver_id = intval($data['user_id']);
    }
    
    if (!$request_id && !$receiver_id) {
        throw new Exception('معرف الطلب أو معرف المستخدم مطلوب');
    }
    
    if ($request_id) {
        $stmt = $pdo->prepare("
            SELECT fr.*, u.username 
            FROM friend_requests fr 
            JOIN users u ON fr.receiver_id = u.id 
            WHERE fr.id = ? AND fr.sender_id = ? AND fr.status = 'pending'
        ");
        $stmt->execute([$request_id, $current_user_id]);
    } else {
        $stmt = $pdo->prepare("
            SELECT fr.*, u.username 
            FROM friend_requests fr 
            JOIN users u ON fr.receiver_id = u.id 
            WHERE fr.receiver_id = ? AND fr.sender_id = ? AND fr.status = 'pending'
        ");
        $stmt->execute([$receiver_id, $current_user_id]);
    }
    
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$request) {
        throw new Exception('طلب الصداقة غير موجود أو لا يمكن إلغاؤه');
    }
    
    $stmt = $pdo->prepare("DELETE FROM friend_requests WHERE id = ?");
    $result = $stmt->execute([$request['id']]);
    
    if ($result) {
        $response = [
            'success' => true,
            'message' => 'تم إلغاء طلب الصداقة بنجاح',
            'data' => [
                'cancelled_user' => $request['username']
            ]
        ];
    } else {
        throw new Exception('فشل في إلغاء طلب الصداقة');
    }
}


function handleListRequests($pdo, $current_user_id, $data, &$response) {
    $type = $data['type'] ?? 'received'; 
    $page = max(1, intval($data['page'] ?? 1));
    $limit = min(50, max(5, intval($data['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;
    
    switch ($type) {
        case 'received':
            $stmt = $pdo->prepare("
                SELECT fr.*, u.id as user_id, u.username, u.first_name, u.last_name, u.avatar_url
                FROM friend_requests fr
                JOIN users u ON fr.sender_id = u.id
                WHERE fr.receiver_id = ? AND fr.status = 'pending'
                ORDER BY fr.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$current_user_id, $limit, $offset]);
            break;
            
        case 'sent':
            $stmt = $pdo->prepare("
                SELECT fr.*, u.id as user_id, u.username, u.first_name, u.last_name, u.avatar_url
                FROM friend_requests fr
                JOIN users u ON fr.receiver_id = u.id
                WHERE fr.sender_id = ?
                ORDER BY fr.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$current_user_id, $limit, $offset]);
            break;
            
        case 'all':
            $stmt = $pdo->prepare("
                (SELECT fr.*, u.id as user_id, u.username, u.first_name, u.last_name, u.avatar_url, 'received' as direction
                 FROM friend_requests fr
                 JOIN users u ON fr.sender_id = u.id
                 WHERE fr.receiver_id = ?)
                UNION
                (SELECT fr.*, u.id as user_id, u.username, u.first_name, u.last_name, u.avatar_url, 'sent' as direction
                 FROM friend_requests fr
                 JOIN users u ON fr.receiver_id = u.id
                 WHERE fr.sender_id = ?)
                ORDER BY created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$current_user_id, $current_user_id, $limit, $offset]);
            break;
            
        default:
            throw new Exception('نوع القائمة غير مدعوم');
    }
    
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    switch ($type) {
        case 'received':
            $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM friend_requests WHERE receiver_id = ? AND status = 'pending'");
            $count_stmt->execute([$current_user_id]);
            break;
            
        case 'sent':
            $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM friend_requests WHERE sender_id = ?");
            $count_stmt->execute([$current_user_id]);
            break;
            
        case 'all':
            $count_stmt = $pdo->prepare("
                SELECT COUNT(*) FROM (
                    SELECT id FROM friend_requests WHERE receiver_id = ?
                    UNION
                    SELECT id FROM friend_requests WHERE sender_id = ?
                ) as total
            ");
            $count_stmt->execute([$current_user_id, $current_user_id]);
            break;
    }
    
    $total_count = $count_stmt->fetchColumn();
    
    $response = [
        'success' => true,
        'message' => 'تم جلب قائمة الطلبات بنجاح',
        'data' => [
            'requests' => $requests,
            'type' => $type,
            'page' => $page,
            'limit' => $limit,
            'total_count' => $total_count,
            'total_pages' => ceil($total_count / $limit)
        ]
    ];
}
?> 