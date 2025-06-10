<?php

session_start();
require_once 'config.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: frontend/login.html');
    exit;
}

$current_user_id = $_SESSION['user_id'];
$current_username = $_SESSION['username'];

$target_user_id = isset($_GET['user']) ? (int)$_GET['user'] : null;
$target_user = null;

if (isset($_POST['create_test_user'])) {
    try {
        $check_stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'test_user'");
        $check_stmt->execute();
        $test_user = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$test_user) {
            $test_password = password_hash('test123', PASSWORD_DEFAULT);
            $insert_stmt = $pdo->prepare("INSERT INTO users (username, password, email, first_name, last_name) VALUES (?, ?, ?, ?, ?)");
            $insert_stmt->execute(['test_user', $test_password, 'test@example.com', 'مستخدم', 'اختباري']);
            
            error_log("Test user created successfully");
            
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } else {
            error_log("Test user already exists");
        }
    } catch (Exception $e) {
        error_log("Error creating test user: " . $e->getMessage());
    }
}

try {
    $host = 'localhost';
    $dbname = 'wep_db';
    $user = 'root';
    $password = '';
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $password, $options);
    
    error_log("Chat.php: Database connection successful");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $users_count = $pdo->query("SELECT COUNT(*) as count FROM users")->fetch()['count'];
    error_log("Total users in database: {$users_count}");
    
    if ($users_count < 2) {
        echo "<script>alert('لا يوجد مستخدمين كافيين في قاعدة البيانات. سيتم توجيهك إلى صفحة إصلاح المستخدمين.');</script>";
        echo "<script>window.location.href = 'fix_users.php';</script>";
        exit;
    }
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100) UNIQUE,
        first_name VARCHAR(50),
        last_name VARCHAR(50),
        avatar_url VARCHAR(255),
        last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        content TEXT NOT NULL,
        is_read BOOLEAN DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sender_id) REFERENCES users(id),
        FOREIGN KEY (receiver_id) REFERENCES users(id)
    )");
    
    error_log("Retrieving users for chat for user ID: {$current_user_id}");
    
    $all_users = [];
    $processed_user_ids = [];
    
    try {
        $users_query = "SELECT DISTINCT id, username, first_name, last_name, email, avatar_url, last_active FROM users WHERE id != {$current_user_id} ORDER BY username ASC";
        error_log("Direct query: {$users_query}");
        
        $all_users = $pdo->query($users_query)->fetchAll(PDO::FETCH_ASSOC);
        error_log("Successfully retrieved " . count($all_users) . " users");
        
        foreach ($all_users as $user) {
            error_log("User found: ID={$user['id']}, Username={$user['username']}");
        }
    } catch (Exception $e) {
        error_log("Error retrieving users: " . $e->getMessage());
        
        try {
            $stmt = $pdo->prepare("SELECT DISTINCT id, username, first_name, last_name, email, avatar_url, last_active FROM users WHERE id != ? LIMIT 10");
            $stmt->execute([$current_user_id]);
            $all_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Fallback query retrieved " . count($all_users) . " users");
        } catch (Exception $e2) {
            error_log("Fallback query also failed: " . $e2->getMessage());
        }
    }
    
    $unique_users = [];
    $unique_user_ids = [];
    
    foreach ($all_users as $user) {
        if (!in_array($user['id'], $unique_user_ids)) {
            $unique_users[] = $user;
            $unique_user_ids[] = $user['id'];
        }
    }
    
    $all_users = $unique_users;
    
    if (empty($all_users)) {
        error_log("No users found, creating a test user");
        
        try {
            $test_password = password_hash('test123', PASSWORD_DEFAULT);
            $insert_stmt = $pdo->prepare("INSERT INTO users (username, password, email, first_name, last_name) VALUES (?, ?, ?, ?, ?)");
            $insert_stmt->execute(['test_user', $test_password, 'test@example.com', 'مستخدم', 'اختباري']);
            $test_user_id = $pdo->lastInsertId();
            
            $all_users[] = [
                'id' => $test_user_id,
                'username' => 'test_user',
                'first_name' => 'مستخدم',
                'last_name' => 'اختباري',
                'email' => 'test@example.com'
            ];
            
            error_log("Created test user with ID: {$test_user_id}");
        } catch (Exception $e) {
            error_log("Failed to create test user: " . $e->getMessage());
        }
    }
    
    error_log("Found " . count($all_users) . " users for chat");
    
    foreach ($all_users as &$user) {
        if (empty($user['avatar_url'])) {
            $user['avatar_url'] = 'assets/images/default-avatar.png';
        }
    }
    
    if ($target_user_id) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$target_user_id]);
        $target_user = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Database connection error: " . $e->getMessage());
    echo "<div class='alert alert-danger'>خطأ في الاتصال بقاعدة البيانات</div>";
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SUT Premium - المحادثات</title>
    <link rel="stylesheet" href="assets/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/chat.css">
</head>
<body data-user-id="<?php echo $current_user_id; ?>">
    <div class="chat-container">
        <div class="chat-sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <a href="index.php">SUT Premium</a>
                </div>
                <div class="user-menu">
                    <div class="dropdown">
                        <button class="btn dropdown-toggle" type="button" id="userMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="1"></circle>
                                <circle cx="19" cy="12" r="1"></circle>
                                <circle cx="5" cy="12" r="1"></circle>
                            </svg>
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="userMenuButton">
                            <li><a class="dropdown-item" href="profile.php">الملف الشخصي</a></li>
                            <li><a class="dropdown-item" href="settings.php">الإعدادات</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">تسجيل الخروج</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="search-container">
                <div class="search-box">
                    <input type="text" class="search-input" placeholder="البحث عن المستخدمين..." id="searchUsers">
                    <span class="search-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </span>
                </div>
            </div>
            
            <div class="users-list">
                <?php if (empty($all_users)): ?>
                    <div class="no-users-container">
                        <div class="no-users-message">
                            <p>لا يوجد مستخدمين متاحين للمحادثة</p>
                            <p><small>يرجى التأكد من وجود مستخدمين آخرين في النظام</small></p>
                            
                            <form method="post" action="">
                                <input type="hidden" name="create_test_user" value="1">
                                <button type="submit" class="btn btn-primary mt-3">إنشاء مستخدم اختباري</button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <?php
                    error_log("Rendering " . count($all_users) . " users");
                    
                    foreach ($all_users as $user) {
                        error_log("Rendering user: {$user['id']} - {$user['username']}");
                        ?>
                        <div class="user-item" data-user-id="<?php echo $user['id']; ?>">
                            <div class="user-item-content">
                                <?php if (!empty($user['avatar_url'])): ?>
                                    <img src="<?php echo $user['avatar_url']; ?>" alt="<?php echo $user['username']; ?>" class="user-avatar">
                                <?php else: ?>
                                    <div class="default-avatar"><?php echo strtoupper(substr($user['username'], 0, 1)); ?></div>
                                <?php endif; ?>
                                <div class="user-info">
                                    <div class="user-name-container">
                                        <span class="user-name">
                                            <?php 
                                            if (!empty($user['first_name']) && !empty($user['last_name'])) {
                                                echo $user['first_name'] . ' ' . $user['last_name'];
                                            } else {
                                                echo $user['username'];
                                            }
                                            ?>
                                        </span>
                                        <div class="chat-time" data-user-id="<?php echo $user['id']; ?>"></div>
                                    </div>
                                    <div class="chat-preview">
                                        <div class="last-message" data-user-id="<?php echo $user['id']; ?>"></div>
                                    </div>
                                </div>
                            </div>
                            <a href="profile.php?username=<?php echo urlencode($user['username']); ?>" 
                               class="view-profile-link" 
                               title="عرض الملف الشخصي">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                            </a>
                        </div>
                    <?php } ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="chat-main" id="chatMainArea">
            <div class="chat-header" id="chatHeader" style="display: none;">
                <div class="user-info"> 
                    <div class="default-avatar" id="chattingWithAvatar">A</div>
                </div>
                <div class="chat-header-info">
                    <h6 id="chattingWithName">andrew moris</h6>
                    <small id="chattingWithStatus">متصل الآن</small>
                </div>
                <div class="chat-actions">
                    <button class="action-btn" title="معلومات المحادثة">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                    </button>
                    <button class="action-btn" title="حذف المحادثة">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                    </button>
                    <button class="action-btn" title="إغلاق المحادثة" onclick="closeChat()">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>
            </div>

            <div class="chat-messages" id="chatMessages">
                <div class="message own">
                    <div class="message-content">
                        <div>صباح الخير</div>
                    </div>
                    <div class="message-time">
                        <span>May 25, 2025 1:02 AM</span>
                        <button class="delete-message" onclick="deleteMessage(this)" title="حذف الرسالة">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                        </button>
                    </div>
                </div>

                <div class="message">
                    <div class="message-content">
                        <div>صباح النور</div>
                    </div>
                    <div class="message-time">
                        <span>May 25, 2025 3:41 AM</span>
                    </div>
                </div>
            </div>

            <div class="chat-main no-chat" id="welcomeScreen" style="display: flex;">
                <div class="welcome-container">
                    <div class="welcome-logo"></div>
                    <h1 class="welcome-title">SUT Premium للمحادثات</h1>
                    <p class="welcome-text">
                        اختر محادثة من القائمة الجانبية لبدء الدردشة.
                    </p>
                </div>
            </div>

            <div class="chat-input" id="chatInputArea" style="display: none;">
                <div id="filePreview"></div>
                <div class="input-group">
                    <button class="file-btn" id="attachFileBtn" title="إرفاق ملف">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path>
                        </svg>
                    </button>
                    <input type="file" id="fileInput" class="file-input" multiple>
                    <textarea class="form-control" id="messageInput" placeholder="اكتب رسالتك هنا..." rows="1"></textarea>
                    <button class="btn-send" id="sendMessageBtn" title="إرسال">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="22" y1="2" x2="11" y2="13"></line>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal" id="imageModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">عرض الصورة</h5>
                    <button type="button" class="btn-close" id="imageModalCloseBtn"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" src="" alt="صورة" class="img-fluid">
                </div>
            </div>
        </div>
    </div>

    <script>
        document.body.dataset.userId = '<?php echo $current_user_id; ?>';
    </script>
    
    <script src="assets/js/fix-duplicates.js?v=<?php echo time(); ?>"></script>
    
    <script src="assets/js/chat-fixed.js?v=<?php echo time(); ?>"></script>
</body>
</html>
