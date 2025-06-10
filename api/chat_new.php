<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);


session_start();

$host = 'localhost';
$dbname = 'wep_db';
$username = 'root';
$password = '';

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول أولاً']);
    exit;
}

$current_user_id = (int)$_SESSION['user_id'];

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    switch ($action) {
        case 'send_message':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'طريقة الطلب غير مسموح بها']);
                exit;
            }
            
            $receiver_id = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
            $content = isset($_POST['content']) ? trim($_POST['content']) : '';
            $image_url = isset($_POST['image_url']) ? trim($_POST['image_url']) : null;
            
            if ($receiver_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'معرف المستلم غير صالح']);
                exit;
            }
            
            if (empty($content) && empty($image_url)) {
                echo json_encode(['success' => false, 'message' => 'محتوى الرسالة فارغ']);
                exit;
            }
            
            try {
                $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, content, media_url, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$current_user_id, $receiver_id, $content, $image_url]);
                
                $message_id = $pdo->lastInsertId();
                
                $stmt = $pdo->prepare("UPDATE users SET last_active = NOW() WHERE id = ?");
                $stmt->execute([$current_user_id]);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'تم إرسال الرسالة بنجاح',
                    'message_id' => $message_id,
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
            }
            break;
            
        case 'get_messages':
            $other_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
            
            if ($other_user_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'معرف المستخدم غير صالح']);
                exit;
            }
            
            try {
                $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0");
                $stmt->execute([$other_user_id, $current_user_id]);
                
                $stmt = $pdo->prepare("
                    SELECT m.id, m.sender_id, m.receiver_id, m.content, m.media_url, 
                           m.is_read, m.created_at,
                           CASE WHEN m.sender_id = ? THEN 1 ELSE 0 END as is_mine
                    FROM messages m
                    WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
                    ORDER BY STR_TO_DATE(m.created_at, '%Y-%m-%d %H:%i:%s') ASC
                ");
                $stmt->execute([$current_user_id, $current_user_id, $other_user_id, $other_user_id, $current_user_id]);
                
                $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'messages' => $messages]);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
            }
            break;
            
        case 'get_conversations':
            try {
                $stmt = $pdo->prepare("
                    SELECT u.id, u.username, u.first_name, u.last_name, u.profile_picture, u.avatar_url,
                           (SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND sender_id = u.id AND is_read = 0) as unread_count,
                           (SELECT MAX(created_at) FROM messages WHERE (sender_id = ? AND receiver_id = u.id) OR (sender_id = u.id AND receiver_id = ?)) as last_message_time,
                           (SELECT content FROM messages WHERE ((sender_id = ? AND receiver_id = u.id) OR (sender_id = u.id AND receiver_id = ?)) 
                            ORDER BY created_at DESC LIMIT 1) as last_message
                    FROM users u
                    WHERE u.id != ? AND u.id > 0
                    HAVING last_message_time IS NOT NULL
                    ORDER BY last_message_time DESC
                ");
                $stmt->execute([$current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id]);
                
                $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($conversations as &$conv) {
                    if (!empty($conv['last_message_time'])) {
                        $conv['last_message_time_formatted'] = date('Y-m-d H:i:s', strtotime($conv['last_message_time']));
                    }
                    
                    if (!empty($conv['last_message'])) {
                        $conv['last_message'] = mb_strlen($conv['last_message']) > 30 ? 
                            mb_substr($conv['last_message'], 0, 30) . '...' : 
                            $conv['last_message'];
                    }
                }
                
                echo json_encode([
                    'success' => true,
                    'conversations' => $conversations
                ]);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
            }
            break;
            
        case 'delete_message':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'طريقة الطلب غير مسموح بها']);
                exit;
            }
            
            $message_id = isset($_POST['message_id']) ? (int)$_POST['message_id'] : 0;
            
            if ($message_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'معرف الرسالة غير صالح']);
                exit;
            }
            
            try {
                $stmt = $pdo->prepare("SELECT * FROM messages WHERE id = ? AND sender_id = ?");
                $stmt->execute([$message_id, $current_user_id]);
                
                if ($stmt->rowCount() === 0) {
                    echo json_encode(['success' => false, 'message' => 'لا يمكنك حذف هذه الرسالة']);
                    exit;
                }
                
                $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ?");
                $stmt->execute([$message_id]);
                
                echo json_encode(['success' => true, 'message' => 'تم حذف الرسالة بنجاح']);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'الإجراء غير معروف']);
            break;
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
}
?>
