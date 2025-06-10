<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

$host = 'localhost';
$dbname = 'wep_db';
$username = 'root';
$password = '';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; 
}

$current_user_id = (int)$_SESSION['user_id'];
$db_error = false;
$users = [];
$current_user = null;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$current_user_id]);
    $current_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("
        SELECT u.*, 
               (TIMESTAMPDIFF(SECOND, u.last_active, NOW()) < 300) as is_online,
               (SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND sender_id = u.id AND is_read = 0) as unread_count
        FROM users u 
        WHERE u.id != ?
        ORDER BY is_online DESC, u.last_active DESC
    ");
    $stmt->execute([$current_user_id, $current_user_id]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error_message = "خطأ في قاعدة البيانات: " . $e->getMessage();
    $db_error = true;
}

function formatTime($timestamp) {
    if (!$timestamp) return '';
    
    $time = strtotime($timestamp);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'الآن';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return "منذ {$minutes} " . ($minutes == 1 ? 'دقيقة' : 'دقائق');
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return "منذ {$hours} " . ($hours == 1 ? 'ساعة' : 'ساعات');
    } else {
        return date('h:i A', $time) . ' ' . date('Y-m-d', $time);
    }
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
    <title>نظام المحادثات المبسط - للتشخيص</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 20px;
        }
        .status {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .status.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .user-list {
            margin-top: 20px;
        }
        .user-item {
            display: flex;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-left: 10px;
        }
        .user-info {
            flex-grow: 1;
        }
        .user-name {
            font-weight: bold;
            margin-bottom: 3px;
        }
        .user-status {
            font-size: 0.8em;
            color: #666;
        }
        .online-indicator {
            color: #28a745;
            font-weight: bold;
        }
        .debug-info {
            margin-top: 30px;
            padding: 15px;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .debug-info h3 {
            margin-top: 0;
            color: #333;
        }
        pre {
            background-color: #eee;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>نظام المحادثات المبسط - للتشخيص</h1>
        
        <?php if ($db_error): ?>
            <div class="status error">
                <h3>خطأ في الاتصال بقاعدة البيانات</h3>
                <p><?php echo $error_message; ?></p>
            </div>
        <?php else: ?>
            <div class="status success">
                <h3>تم الاتصال بقاعدة البيانات بنجاح</h3>
                <?php if ($current_user): ?>
                    <p>مرحباً، <?php echo htmlspecialchars(getDisplayName($current_user)); ?></p>
                <?php else: ?>
                    <p>لم يتم العثور على المستخدم الحالي (ID: <?php echo $current_user_id; ?>)</p>
                <?php endif; ?>
            </div>
            
            <h2>قائمة المستخدمين</h2>
            <div class="user-list">
                <?php if (!empty($users)): ?>
                    <?php foreach ($users as $user): ?>
                        <?php 
                            $avatar = getUserAvatar($user);
                            $displayName = getDisplayName($user);
                            $isOnline = $user['is_online'] ? true : false;
                        ?>
                        <div class="user-item">
                            <img src="<?php echo htmlspecialchars($avatar); ?>" alt="<?php echo htmlspecialchars($displayName); ?>" class="user-avatar">
                            <div class="user-info">
                                <div class="user-name"><?php echo htmlspecialchars($displayName); ?></div>
                                <div class="user-status">
                                    <?php if ($isOnline): ?>
                                        <span class="online-indicator">متصل الآن</span>
                                    <?php else: ?>
                                        آخر ظهور: <?php echo formatTime($user['last_active']); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if (isset($user['unread_count']) && $user['unread_count'] > 0): ?>
                                <div class="unread-badge"><?php echo $user['unread_count']; ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>لا يوجد مستخدمين متاحين للمحادثة.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="debug-info">
            <h3>معلومات التشخيص</h3>
            <p>معرف المستخدم الحالي: <?php echo $current_user_id; ?></p>
            <p>عدد المستخدمين المسترجعين: <?php echo count($users); ?></p>
            <p>معلومات الاتصال بقاعدة البيانات:</p>
            <ul>
                <li>المضيف: <?php echo $host; ?></li>
                <li>اسم قاعدة البيانات: <?php echo $dbname; ?></li>
                <li>اسم المستخدم: <?php echo $username; ?></li>
            </ul>
            
            <?php if ($current_user): ?>
                <h4>بيانات المستخدم الحالي:</h4>
                <pre><?php print_r($current_user); ?></pre>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
