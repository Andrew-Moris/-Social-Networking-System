<?php

session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'غير مسجل الدخول']);
    exit;
}

$current_user_id = $_SESSION['user_id'];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS messages_enhanced (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sender_id INT NOT NULL,
            receiver_id INT NOT NULL,
            content TEXT,
            image_url VARCHAR(500),
            audio_url VARCHAR(500),
            message_type ENUM('text', 'image', 'audio', 'mixed') DEFAULT 'text',
            is_delivered BOOLEAN DEFAULT FALSE,
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_conversation (sender_id, receiver_id, created_at),
            INDEX idx_unread (receiver_id, is_read)
        )
    ");
    
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    switch ($action) {
        case 'get_messages':
            handleGetMessages($pdo, $current_user_id);
            break;
            
        case 'send_message':
            handleSendMessage($pdo, $current_user_id);
            break;
            
        case 'mark_as_read':
            handleMarkAsRead($pdo, $current_user_id);
            break;
            
        case 'get_user_profile':
            handleGetUserProfile($pdo, $current_user_id);
            break;
            
        case 'upload_image':
            handleImageUpload($pdo, $current_user_id);
            break;
            
        case 'upload_audio':
            handleAudioUpload($pdo, $current_user_id);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'إجراء غير صحيح']);
    }
    
} catch (Exception $e) {
    error_log("Enhanced Chat API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'خطأ في الخادم']);
}

function handleGetMessages($pdo, $current_user_id) {
    $user_id = (int)($_GET['user_id'] ?? 0);
    
    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'معرف المستخدم مطلوب']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT m.*, 
                   u1.username as sender_username,
                   u1.first_name as sender_first_name,
                   u1.last_name as sender_last_name,
                   u2.username as receiver_username,
                   u2.first_name as receiver_first_name,
                   u2.last_name as receiver_last_name
            FROM messages_enhanced m
            JOIN users u1 ON m.sender_id = u1.id
            JOIN users u2 ON m.receiver_id = u2.id
            WHERE (m.sender_id = ? AND m.receiver_id = ?) 
               OR (m.sender_id = ? AND m.receiver_id = ?)
            ORDER BY m.created_at ASC
            LIMIT 100
        ");
        
        $stmt->execute([$current_user_id, $user_id, $user_id, $current_user_id]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $updateStmt = $pdo->prepare("
            UPDATE messages_enhanced 
            SET is_delivered = TRUE 
            WHERE sender_id = ? AND receiver_id = ? AND is_delivered = FALSE
        ");
        $updateStmt->execute([$user_id, $current_user_id]);
        
        $readStmt = $pdo->prepare("
            UPDATE messages_enhanced 
            SET is_read = TRUE 
            WHERE sender_id = ? AND receiver_id = ? AND is_read = FALSE
        ");
        $readStmt->execute([$user_id, $current_user_id]);
        
        echo json_encode([
            'success' => true, 
            'messages' => $messages,
            'count' => count($messages)
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'خطأ في جلب الرسائل']);
    }
}

function handleSendMessage($pdo, $current_user_id) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $receiver_id = (int)($input['receiver_id'] ?? $_POST['receiver_id'] ?? 0);
    $content = trim($input['content'] ?? $_POST['content'] ?? '');
    $image_url = $input['image_url'] ?? '';
    $audio_url = $input['audio_url'] ?? '';
    
    if (!$receiver_id) {
        echo json_encode(['success' => false, 'message' => 'معرف المستلم مطلوب']);
        return;
    }
    
    $image_urls = [];
    if (isset($_FILES['images'])) {
        $upload_dir = '../uploads/images/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                $file_extension = pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION);
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (in_array(strtolower($file_extension), $allowed_extensions)) {
                    $filename = uniqid('chat_') . '.' . $file_extension;
                    $filepath = $upload_dir . $filename;
                    
                    if (move_uploaded_file($tmp_name, $filepath)) {
                        $image_urls[] = 'uploads/images/' . $filename;
                    }
                }
            }
        }
    }
    
    $audio_urls = [];
    if (isset($_FILES['audio'])) {
        $upload_dir = '../uploads/audio/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        foreach ($_FILES['audio']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['audio']['error'][$key] === UPLOAD_ERR_OK) {
                $file_extension = pathinfo($_FILES['audio']['name'][$key], PATHINFO_EXTENSION);
                $allowed_extensions = ['wav', 'mp3', 'ogg', 'webm', 'm4a'];
                
                if (in_array(strtolower($file_extension), $allowed_extensions)) {
                    $filename = uniqid('audio_') . '.' . $file_extension;
                    $filepath = $upload_dir . $filename;
                    
                    if (move_uploaded_file($tmp_name, $filepath)) {
                        $audio_urls[] = 'uploads/audio/' . $filename;
                    }
                }
            }
        }
    }
    
    if (!empty($image_url)) {
        $image_urls[] = $image_url;
    }
    if (!empty($audio_url)) {
        $audio_urls[] = $audio_url;
    }
    
    if (empty($content) && empty($image_urls) && empty($audio_urls)) {
        echo json_encode(['success' => false, 'message' => 'الرسالة فارغة']);
        return;
    }
    
    try {
        $message_type = 'text';
        if ((!empty($image_urls) || !empty($audio_urls)) && !empty($content)) {
            $message_type = 'mixed';
        } elseif (!empty($image_urls)) {
            $message_type = 'image';
        } elseif (!empty($audio_urls)) {
            $message_type = 'audio';
        }
        
        if (!empty($image_urls)) {
            foreach ($image_urls as $image_url) {
                $stmt = $pdo->prepare("
                    INSERT INTO messages_enhanced (sender_id, receiver_id, content, image_url, message_type, is_delivered)
                    VALUES (?, ?, ?, ?, ?, TRUE)
                ");
                $stmt->execute([
                    $current_user_id, 
                    $receiver_id, 
                    $content, 
                    $image_url, 
                    $message_type
                ]);
            }
        } elseif (!empty($audio_urls)) {
            foreach ($audio_urls as $audio_url) {
                $stmt = $pdo->prepare("
                    INSERT INTO messages_enhanced (sender_id, receiver_id, content, audio_url, message_type, is_delivered)
                    VALUES (?, ?, ?, ?, ?, TRUE)
                ");
                $stmt->execute([
                    $current_user_id, 
                    $receiver_id, 
                    $content, 
                    $audio_url, 
                    $message_type
                ]);
            }
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO messages_enhanced (sender_id, receiver_id, content, message_type, is_delivered)
                VALUES (?, ?, ?, ?, TRUE)
            ");
            $stmt->execute([$current_user_id, $receiver_id, $content, $message_type]);
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'تم إرسال الرسالة',
            'images_uploaded' => count($image_urls),
            'audio_uploaded' => count($audio_urls)
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'خطأ في إرسال الرسالة']);
    }
}

function handleMarkAsRead($pdo, $current_user_id) {
    $sender_id = (int)($_POST['sender_id'] ?? 0);
    
    if (!$sender_id) {
        echo json_encode(['success' => false, 'message' => 'معرف المرسل مطلوب']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            UPDATE messages_enhanced 
            SET is_read = TRUE 
            WHERE sender_id = ? AND receiver_id = ? AND is_read = FALSE
        ");
        $stmt->execute([$sender_id, $current_user_id]);
        
        echo json_encode(['success' => true, 'message' => 'تم تحديث حالة القراءة']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'خطأ في تحديث حالة القراءة']);
    }
}

function handleGetUserProfile($pdo, $current_user_id) {
    $user_id = (int)($_GET['user_id'] ?? 0);
    
    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'معرف المستخدم مطلوب']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT id, username, first_name, last_name, email, avatar_url, bio, 
                   created_at, last_login,
                   (SELECT COUNT(*) FROM posts WHERE user_id = ?) as posts_count,
                   (SELECT COUNT(*) FROM follows WHERE follower_id = ?) as following_count,
                   (SELECT COUNT(*) FROM follows WHERE following_id = ?) as followers_count
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$user_id, $user_id, $user_id, $user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $followStmt = $pdo->prepare("
                SELECT 1 FROM follows 
                WHERE follower_id = ? AND following_id = ?
            ");
            $followStmt->execute([$current_user_id, $user_id]);
            $user['is_following'] = $followStmt->fetch() ? true : false;
            
            $postsStmt = $pdo->prepare("
                SELECT id, content, image_url, created_at 
                FROM posts 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT 5
            ");
            $postsStmt->execute([$user_id]);
            $user['recent_posts'] = $postsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'user' => $user]);
        } else {
            echo json_encode(['success' => false, 'message' => 'المستخدم غير موجود']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'خطأ في جلب بيانات المستخدم']);
    }
}

function handleImageUpload($pdo, $current_user_id) {
    if (!isset($_FILES['image'])) {
        echo json_encode(['success' => false, 'message' => 'لم يتم اختيار صورة']);
        return;
    }
    
    $upload_dir = '../uploads/images/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file = $_FILES['image'];
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array(strtolower($file_extension), $allowed_extensions)) {
        echo json_encode(['success' => false, 'message' => 'نوع الملف غير مدعوم']);
        return;
    }
    
    if ($file['size'] > 5 * 1024 * 1024) { 
        echo json_encode(['success' => false, 'message' => 'حجم الملف كبير جداً']);
        return;
    }
    
    $filename = uniqid('chat_') . '.' . $file_extension;
    $filepath = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        echo json_encode([
            'success' => true, 
            'image_url' => 'uploads/images/' . $filename,
            'message' => 'تم رفع الصورة بنجاح'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'فشل في رفع الصورة']);
    }
}

function handleAudioUpload($pdo, $current_user_id) {
    if (!isset($_FILES['audio'])) {
        echo json_encode(['success' => false, 'message' => 'لم يتم اختيار ملف صوتي']);
        return;
    }
    
    $upload_dir = '../uploads/audio/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file = $_FILES['audio'];
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $allowed_extensions = ['wav', 'mp3', 'ogg', 'webm', 'm4a'];
    
    if (!in_array(strtolower($file_extension), $allowed_extensions)) {
        echo json_encode(['success' => false, 'message' => 'نوع الملف غير مدعوم']);
        return;
    }
    
    if ($file['size'] > 10 * 1024 * 1024) { 
        echo json_encode(['success' => false, 'message' => 'حجم الملف كبير جداً']);
        return;
    }
    
    $filename = uniqid('audio_') . '.' . $file_extension;
    $filepath = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        echo json_encode([
            'success' => true, 
            'audio_url' => 'uploads/audio/' . $filename,
            'message' => 'تم رفع الملف الصوتي بنجاح'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'فشل في رفع الملف الصوتي']);
    }
}
?> 