<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; 
}

$current_user_id = (int)$_SESSION['user_id'];
$error_message = '';

$host = 'localhost';
$dbname = 'wep_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$current_user_id]);
    $current_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$current_user) {
        $error_message = "لم يتم العثور على المستخدم الحالي (ID: $current_user_id)";
    } else {
        $stmt = $pdo->prepare("
            SELECT u.*, 
                   1 as is_online,
                   (SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND sender_id = u.id AND is_read = 0) as unread_count
            FROM users u 
            WHERE u.id != ? AND u.id > 0
            ORDER BY u.id ASC
        ");
        $stmt->execute([$current_user_id, $current_user_id]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $error_message = "خطأ في قاعدة البيانات: " . $e->getMessage();
}

function getUserAvatar($user) {
    if (!empty($user['avatar_url'])) {
        return $user['avatar_url'];
    } elseif (!empty($user['profile_picture'])) {
        return $user['profile_picture'];
    } else {
        $name = !empty($user['first_name']) ? $user['first_name'] : $user['username'];
        $name = mb_substr($name, 0, 2, 'UTF-8');
        return "https://ui-avatars.com/api/?name=" . urlencode($name) . "&background=random&color=fff&size=32";
    }
}

function getDisplayName($user) {
    if (!empty($user['first_name']) && !empty($user['last_name'])) {
        return $user['first_name'] . ' ' . $user['last_name'];
    } elseif (!empty($user['first_name'])) {
        return $user['first_name'];
    } else {
        return $user['username'];
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المحادثات الخاصة - نسخة مبسطة</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: 20px auto;
            display: flex;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            height: 80vh;
        }
        .sidebar {
            width: 300px;
            background-color: #f8f9fa;
            border-left: 1px solid #dee2e6;
            overflow-y: auto;
        }
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .header {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
            background-color: #fff;
        }
        .messages-area {
            flex: 1;
            overflow-y: auto;
            padding: 15px;
            background-color: #f8f9fa;
        }
        .input-area {
            padding: 15px;
            border-top: 1px solid #dee2e6;
            background-color: #fff;
            display: flex;
        }
        .user-item {
            padding: 10px 15px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            align-items: center;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .user-item:hover {
            background-color: #e9ecef;
        }
        .user-item.active {
            background-color: #e2e6ea;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-left: 10px;
            object-fit: cover;
        }
        .user-info {
            flex: 1;
        }
        .user-name {
            font-weight: bold;
            margin-bottom: 3px;
        }
        .user-status {
            font-size: 0.8em;
            color: #6c757d;
        }
        .online {
            color: #28a745;
        }
        .message-input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            margin-left: 10px;
        }
        .send-button {
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .welcome-screen {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            text-align: center;
            padding: 20px;
        }
        .welcome-icon {
            font-size: 3rem;
            color: #6c757d;
            margin-bottom: 15px;
        }
        .debug-info {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
        }
        pre {
            background-color: #f1f1f1;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (!empty($error_message)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <div class="sidebar">
            <div style="padding: 15px; border-bottom: 1px solid #dee2e6;">
                <h3 style="margin: 0;">المحادثات</h3>
            </div>
            
            <?php if (isset($users) && !empty($users)): ?>
                <?php foreach ($users as $user): ?>
                    <?php 
                        $avatar = getUserAvatar($user);
                        $displayName = getDisplayName($user);
                        $isOnline = $user['is_online'] ? true : false;
                    ?>
                    <div class="user-item" data-user-id="<?php echo $user['id']; ?>">
                        <img src="<?php echo htmlspecialchars($avatar); ?>" alt="<?php echo htmlspecialchars($displayName); ?>" class="user-avatar">
                        <div class="user-info">
                            <div class="user-name"><?php echo htmlspecialchars($displayName); ?></div>
                            <div class="user-status <?php echo $isOnline ? 'online' : ''; ?>">
                                <?php echo $isOnline ? 'متصل الآن' : 'غير متصل'; ?>
                            </div>
                        </div>
                        <?php if (isset($user['unread_count']) && $user['unread_count'] > 0): ?>
                            <div style="background-color: #007bff; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 0.8em;">
                                <?php echo $user['unread_count']; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="padding: 15px; text-align: center; color: #6c757d;">
                    لا يوجد مستخدمين متاحين للمحادثة.
                </div>
            <?php endif; ?>
        </div>
        
        <div class="main-content">
            <div class="welcome-screen">
                <div class="welcome-icon">
                    <i class="bi bi-chat-dots"></i>
                </div>
                <h2>مرحباً بك في نظام المحادثات</h2>
                <p>اختر محادثة من القائمة الجانبية لبدء الدردشة.</p>
                
                <?php if (isset($current_user)): ?>
                    <div class="debug-info">
                        <h3>معلومات المستخدم الحالي</h3>
                        <pre><?php print_r($current_user); ?></pre>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const userItems = document.querySelectorAll('.user-item');
            
            userItems.forEach(item => {
                item.addEventListener('click', function() {
                    userItems.forEach(i => i.classList.remove('active'));
                    
                    this.classList.add('active');
                    
                    const userId = this.dataset.userId;
                    alert('تم اختيار المستخدم رقم: ' + userId);
                });
            });
        });
    </script>
</body>
</html>
