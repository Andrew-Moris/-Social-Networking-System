<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$current_user_id = $_SESSION['user_id'];

try {
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
    
    foreach ($users as &$user) {
        $stmt = $pdo->prepare("SELECT content, created_at FROM messages 
                              WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) 
                              ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$user['id'], $current_user_id, $current_user_id, $user['id']]);
        $message = $stmt->fetch();
        
        if ($message) {
            $user['last_message'] = $message['content'];
            $user['last_message_time'] = $message['created_at'];
        } else {
            $user['last_message'] = '';
            $user['last_message_time'] = null;
        }
    }
    
    error_log("Found " . count($users) . " users");
    foreach ($users as $user) {
        error_log("User: " . $user['username'] . ", ID: " . $user['id'] . ", Online: " . ($user['is_online'] ? 'Yes' : 'No'));
    }
} catch (PDOException $e) {
    error_log("Error fetching users: " . $e->getMessage());
    $users = [];
}

function isUserOnline($lastActive) {
    if (!$lastActive) return false;
    $lastActiveTime = strtotime($lastActive);
    $currentTime = time();
    return ($currentTime - $lastActiveTime) < 300;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المحادثات الخاصة - SUT Premium</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="assets/css/emoji-simple.css" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #89f7fe 0%, #66a6ff 100%);
            --bg-primary: #0a0f1c;
            --bg-secondary-opaque: #1a1f2e; 
            --bg-glass-dark: rgba(16, 22, 39, 0.75);
            --bg-glass-medium: rgba(26, 31, 46, 0.7);
            --bg-glass-light: rgba(36, 41, 56, 0.65); 
            --bg-glass-hover: rgba(40, 46, 62, 0.85);
            --border-color: rgba(255, 255, 255, 0.07);
            --text-primary: #e5e7eb;
            --text-secondary: #9ca3af;
            --text-muted: #6b7280;
            --blur-glass: blur(18px);
            --active-indicator: #667eea;
            --green-online: #4ade80;
            --red-action: #f43f5e;
            --input-bg: rgba(255, 255, 255, 0.04);
            --input-bg-focus: rgba(255, 255, 255, 0.07);
            --button-shadow: 0 4px 12px rgba(0,0,0,0.2);
            --button-shadow-hover: 0 6px 15px rgba(0,0,0,0.3);
        }
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
            font-family: 'Cairo', sans-serif; 
        }

        body { 
            background: var(--bg-primary); 
            color: var(--text-primary); 
            min-height: 100vh; 
            line-height: 1.6; 
            overflow: hidden;
            direction: rtl; 
        }

        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.3); }

        .nav-header { 
            background: var(--bg-glass-dark); 
            backdrop-filter: var(--blur-glass); 
            border-bottom: 1px solid var(--border-color); 
            position: fixed; top: 0; left: 0; right: 0; z-index: 1000; 
            padding: 0.5rem 0; height: 52px;
        }

        .nav-header .container { max-width: 1320px; margin: 0 auto; padding: 0 1rem; }
        .nav-header a i { font-size: 1.15rem; }
        .nav-header a { transition: background-color 0.2s ease, color 0.2s ease; }
        .nav-header .active-nav-link { background: rgba(102, 126, 234, 0.2); }
        .nav-header .active-nav-link i { color: var(--active-indicator); }
        .nav-header a:hover:not(.active-nav-link) { background: rgba(255,255,255,0.07); }

        .chat-container-main { display: flex; height: 100vh; padding-top: 52px; }

        .chat-sidebar {
            width: 280px;
            background: var(--bg-glass-dark);
            backdrop-filter: var(--blur-glass);
            border-left: 1px solid var(--border-color);
            display: flex; flex-direction: column;
            transition: width 0.3s ease, transform 0.3s ease; height: 100%;
        }

        @media (max-width: 768px) { .chat-sidebar { width: 240px; } }

        .search-input-custom {
            background: var(--input-bg);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            border-radius: 0.5rem;
            font-size: 0.8rem;
            padding: 0.5rem 0.75rem;
            padding-right: 2.25rem;
        }

        .search-input-custom:focus {
            border-color: var(--active-indicator);
            background: var(--input-bg-focus);
            box-shadow: 0 0 0 2.5px rgba(102, 126, 234, 0.2);
        }

        .search-input-custom::placeholder { color: var(--text-muted); }

        .user-item-chat {
            transition: background-color 0.2s ease, border-color 0.2s ease;
            border-radius: 0.5rem;
            margin: 0.15rem 0.25rem;
            padding: 0.5rem;
        }

        .user-item-chat:not(:last-child) { border-bottom: 1px solid var(--border-color); }
        .user-item-chat:hover { background: var(--bg-glass-hover); }
        .user-item-chat.active {
            background: linear-gradient(to left, rgba(102,126,234,0.25), rgba(102,126,234,0.1));
            border-right: 3px solid var(--active-indicator);
        }

        .user-avatar-chat {
            border: 2px solid transparent;
            width: 2rem;
            height: 2rem;
        }

        .user-item-chat.active .user-avatar-chat {
            border-color: var(--active-indicator);
            box-shadow: 0 0 8px rgba(102, 126, 234, 0.4);
        }

        .online-indicator {
            background-color: var(--green-online);
            border: 1.5px solid var(--bg-glass-dark);
            width: 0.5rem;
            height: 0.5rem;
        }

        .chat-main-area {
            background-image: linear-gradient(to bottom, var(--bg-primary) 0%, #0e1525 100%);
            height: 100%;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .chat-header-custom {
            background: var(--bg-glass-medium);
            backdrop-filter: var(--blur-glass);
            border-bottom: 1px solid var(--border-color);
            min-height: 52px;
            padding: 0.4rem 0.75rem;
        }

        .chat-action-btn {
            background: rgba(255,255,255,0.06);
            color: var(--text-secondary);
            border: 1px solid transparent;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            transition: all 0.2s ease;
            box-shadow: var(--button-shadow);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .chat-action-btn:hover {
            background: rgba(255,255,255,0.12);
            color: var(--text-primary);
            transform: scale(1.05);
            box-shadow: var(--button-shadow-hover);
        }

        .chat-action-btn:active { transform: scale(0.95); }

        .message-bubble {
            padding: 0.55rem 0.85rem;
            border-radius: 0.9rem;
            max-width: 75%;
            box-shadow: 0 2px 4px rgba(0,0,0,0.35);
            word-wrap: break-word;
            font-size: 0.85rem;
        }

        .message-bubble.own {
            background: var(--primary-gradient);
            color: white;
            border-bottom-left-radius: 0.3rem;
        }

        .message-bubble.other {
            background: var(--bg-glass-light);
            backdrop-filter: var(--blur-glass);
            color: var(--text-primary);
            border-bottom-right-radius: 0.3rem;
        }

        .message-time-custom {
            color: var(--text-muted);
            font-size: 0.6rem;
            margin-top: 0.1rem;
        }

        .delete-message-btn {
            opacity: 0;
            transition: opacity 0.2s ease, color 0.2s ease;
        }

        .message:hover .delete-message-btn { opacity: 0.5; }
        .delete-message-btn:hover { opacity: 1; color: var(--red-action); }
        .delete-message-btn i { font-size: 0.75rem; }

        .chat-input-area-custom {
            background: var(--bg-glass-dark);
            backdrop-filter: var(--blur-glass);
            border-top: 1px solid var(--border-color);
            padding: 0.4rem 0.5rem;
        }

        .chat-textarea-custom {
            background: var(--input-bg);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            border-radius: 0.8rem;
            padding: 0.5rem 0.8rem;
            min-height: 38px;
            font-size: 0.85rem;
            width: 100%;
            resize: none;
        }

        .chat-textarea-custom:focus {
            border-color: var(--active-indicator);
            background: var(--input-bg-focus);
            box-shadow: 0 0 0 2.5px rgba(102, 126, 234, 0.2);
        }

        .chat-input-btn {
            color: var(--text-secondary);
            padding: 0.4rem;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .chat-input-btn:hover {
            background: rgba(255,255,255,0.15);
            color: var(--text-primary);
            transform: translateY(-1px);
        }

        .chat-input-btn:active { transform: translateY(0px) scale(0.95); }

        .chat-send-btn {
            background: var(--primary-gradient);
            color: white;
            box-shadow: var(--button-shadow);
        }

        .chat-send-btn:hover {
            filter: brightness(1.15);
            box-shadow: var(--button-shadow-hover);
        }

        .welcome-logo-custom i {
            font-size: 3.8rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            filter: drop-shadow(0 0 10px rgba(102, 126, 234, 0.5));
        }

        .modal-custom-bg {
            background-color: rgba(10, 15, 28, 0.7);
            backdrop-filter: blur(8px);
        }

        .modal-content-custom {
            background-color: var(--bg-secondary-opaque);
            border: 1px solid var(--border-color);
            box-shadow: 0 10px 30px rgba(0,0,0,0.4);
            border-radius: 0.6rem;
        }

        @media (max-width: 768px) {
            .chat-sidebar {
                position: fixed;
                right: 0;
                top: 52px;
                bottom: 0;
                transform: translateX(100%);
                z-index: 50;
            }

            .chat-sidebar.active {
                transform: translateX(0);
            }

            .chat-container-main {
                position: relative;
            }
        }
    </style>
</head>
<body data-user-id="<?php echo $current_user_id; ?>">
    <header class="nav-header">
        <div class="container mx-auto px-4 sm:px-6 flex justify-between items-center h-full">
            <div class="text-lg font-bold">
                <a href="home.php" class="text-white hover:text-opacity-90 transition-colors">
                    <span style="background: var(--primary-gradient); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;">SUT</span>
                    <span class="text-gray-100">Premium</span>
                </a>
            </div>
            <nav class="flex items-center gap-1 md:gap-2">
                <a href="home.php" class="text-gray-300 hover:text-white p-1.5 rounded-full hover:bg-white/10" title="الرئيسية"><i class="bi bi-house-door"></i></a>
                <a href="discover.php" class="text-gray-300 hover:text-white p-1.5 rounded-full hover:bg-white/10" title="اكتشف"><i class="bi bi-compass"></i></a>
                <a href="chat.php" class="text-white active-nav-link p-1.5 rounded-full" title="الدردشات"><i class="bi bi-chat-dots-fill"></i></a>
                <a href="friends.php" class="text-gray-300 hover:text-white p-1.5 rounded-full hover:bg-white/10" title="الأصدقاء"><i class="bi bi-people"></i></a>
                <a href="bookmarks.php" class="text-gray-300 hover:text-white p-1.5 rounded-full hover:bg-white/10" title="المحفوظات"><i class="bi bi-bookmark"></i></a>
                <a href="u.php" class="text-gray-300 hover:text-white p-1.5 rounded-full hover:bg-white/10" title="الملف الشخصي"><i class="bi bi-person"></i></a>
                <a href="logout.php" class="text-gray-300 hover:text-red-400 p-1.5 rounded-full hover:bg-red-500/15" title="تسجيل الخروج"><i class="bi bi-box-arrow-right"></i></a>
            </nav>
        </div>
    </header>

    <div class="chat-container-main">
        <div class="chat-sidebar">
            <div class="search-container p-2 border-b border-[var(--border-color)]">
                <div class="relative">
                    <input type="text" class="search-input-custom w-full" placeholder="البحث عن المستخدمين..." id="searchUsers">
                    <span class="absolute top-1/2 right-2.5 transform -translate-y-1/2 text-[var(--text-muted)] pointer-events-none">
                        <i class="bi bi-search text-xs"></i>
                    </span>
                </div>
            </div>
            
            <div class="users-list flex-grow overflow-y-auto p-0.5">
                <?php foreach ($users as $user): 
                    $isOnline = isset($user['is_online']) ? $user['is_online'] : false;
                    
                    $username = isset($user['username']) ? $user['username'] : 'user';
                    
                    if (isset($user['avatar']) && !empty($user['avatar'])) {
                        $avatar = $user['avatar'];
                    } else if (isset($user['profile_picture']) && !empty($user['profile_picture'])) {
                        $avatar = $user['profile_picture'];
                    } else {
                        $avatar = "https://ui-avatars.com/api/?name=" . urlencode(substr($username, 0, 2)) . "&background=random&color=fff&size=32";
                    }
                    
                    if (isset($user['display_name']) && !empty($user['display_name'])) {
                        $displayName = $user['display_name'];
                    } else if (isset($user['first_name']) && !empty($user['first_name'])) {
                        if (isset($user['last_name']) && !empty($user['last_name'])) {
                            $displayName = $user['first_name'] . ' ' . $user['last_name'];
                        } else {
                            $displayName = $user['first_name'];
                        }
                    } else {
                        $displayName = $username;
                    }
                    
                    $lastMessage = isset($user['last_message']) ? $user['last_message'] : '';
                    $lastMessageTime = isset($user['last_message_time']) && $user['last_message_time'] ? date('h:i a', strtotime($user['last_message_time'])) : '';
                ?>
                <div class="user-item-chat flex items-center" 
                     data-user-id="<?php echo htmlspecialchars($user['id']); ?>" 
                     data-username="<?php echo htmlspecialchars($user['username']); ?>"
                     data-avatar="<?php echo htmlspecialchars($avatar); ?>"
                     data-displayname="<?php echo htmlspecialchars($displayName); ?>">
                    <div class="relative ml-2">
                        <img src="<?php echo htmlspecialchars($avatar); ?>" alt="<?php echo htmlspecialchars($displayName); ?>" class="user-avatar-chat rounded-full object-cover">
                        <?php if ($isOnline): ?>
                            <span class="online-indicator absolute bottom-0 left-0 block rounded-full"></span>
                        <?php endif; ?>
                        <?php if (isset($user['unread_count']) && $user['unread_count'] > 0): ?>
                            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-bold rounded-full w-4 h-4 flex items-center justify-center"><?php echo $user['unread_count']; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="user-details overflow-hidden flex-grow">
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-semibold text-[var(--text-primary)] truncate"><?php echo htmlspecialchars($displayName); ?></span>
                            <?php if ($lastMessageTime): ?>
                            <span class="text-[10px] text-[var(--text-muted)]"><?php echo $lastMessageTime; ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-[0.65rem] text-[var(--text-muted)] truncate">
                                <?php echo $lastMessage ? htmlspecialchars(mb_substr($lastMessage, 0, 20)) . (mb_strlen($lastMessage) > 20 ? '...' : '') : ($isOnline ? 'متصل الآن' : 'غير نشط'); ?>
                            </span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

                <?php if (empty($users)): ?>
                <div class="no-users-container flex flex-col items-center justify-center h-full text-center p-4">
                    <i class="bi bi-people-fill text-3xl text-[var(--text-muted)] mb-2"></i>
                    <div>
                        <p class="text-sm font-semibold text-[var(--text-secondary)]">لا يوجد مستخدمين</p>
                        <p class="text-xs text-[var(--text-muted)]"><small>ابحث عن مستخدمين لبدء المحادثة.</small></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="chat-main-area" id="chatMainArea">
            <div class="chat-header-custom flex items-center justify-between" id="chatHeader" style="display: none;">
                <div class="flex items-center">
                    <img id="chattingWithAvatar" src="" alt="Chatting with" class="w-7 h-7 rounded-full ml-2 object-cover border-2 border-[var(--active-indicator)]">
                    <div>
                        <h6 id="chattingWithName" class="text-xs font-semibold text-[var(--text-primary)]"></h6>
                        <small id="chattingWithStatus" class="text-[0.65rem] text-[var(--green-online)]"></small>
                    </div>
                </div>
                <div class="flex items-center space-x-0.5 space-x-reverse">
                    <button class="chat-action-btn" title="معلومات المحادثة"><i class="bi bi-info-circle text-xs"></i></button>
                    <button class="chat-action-btn" title="حذف المحادثة"><i class="bi bi-trash3 text-xs"></i></button>
                    <button class="chat-action-btn" title="إغلاق المحادثة" onclick="closeChat()"><i class="bi bi-x-lg text-xs"></i></button>
                </div>
            </div>

            <div class="chat-messages flex-grow overflow-y-auto p-2.5 space-y-2" id="chatMessages"></div>

            <div class="flex-grow flex flex-col items-center justify-center text-center p-6" id="welcomeScreen">
                <div class="welcome-container">
                    <div class="welcome-logo-custom mb-3">
                        <i class="bi bi-chat-quote-fill"></i>
                    </div>
                    <h1 class="text-md font-semibold text-[var(--text-primary)] mb-0.5">SUT Premium للمحادثات</h1>
                    <p class="text-xs text-[var(--text-secondary)]">
                        اختر محادثة من القائمة الجانبية لبدء الدردشة.
                    </p>
                </div>
            </div>

            <div class="chat-input-area-custom" id="chatInputArea" style="display: none;">
                <div id="filePreviewContainer" class="px-1 pt-1 empty:hidden"></div>
                <div class="flex items-center space-x-1 space-x-reverse p-1">
                    <input type="file" id="fileInput" class="hidden" multiple>
                    <button class="chat-input-btn" id="attachFileBtn" title="إرفاق ملف">
                        <i class="bi bi-paperclip text-md"></i>
                    </button>
                    <button class="chat-input-btn" id="emojiBtn" title="إضافة إيموجي">
                        <i class="bi bi-emoji-smile text-md"></i>
                    </button>
                    <textarea class="chat-textarea-custom flex-grow" id="messageInput" placeholder="اكتب رسالتك هنا..." rows="1"></textarea>
                    <button class="chat-input-btn chat-send-btn" id="sendMessageBtn" title="إرسال">
                        <i class="bi bi-send-fill text-sm"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="fixed inset-0 modal-custom-bg flex items-center justify-center p-4 z-[100]" id="imageModal" style="display: none;">
        <div class="modal-content-custom w-full max-w-xl max-h-[80vh]">
            <div class="flex items-center justify-between p-2.5 border-b border-[var(--border-color)]">
                <h5 class="text-xs font-semibold text-[var(--text-primary)]">عرض الصورة</h5>
                <button type="button" class="text-[var(--text-muted)] hover:text-[var(--text-primary)] transition-colors p-1" id="imageModalCloseBtn">
                    <i class="bi bi-x-lg text-sm"></i>
                </button>
            </div>
            <div class="text-center p-2.5 overflow-y-auto">
                <img id="modalImage" src="" alt="صورة مكبرة" class="inline-block max-w-full max-h-[calc(80vh-70px)] rounded-md">
            </div>
        </div>
    </div>

    <script>
        // تكوين النظام
        const API_ENDPOINT = '<?php echo rtrim(dirname($_SERVER['PHP_SELF']), '/\\'); ?>/api/chat.php';
        const CURRENT_USER_ID = <?php echo json_encode($current_user_id); ?>;
    </script>
    <script src="assets/js/emoji.min.js"></script>
    <script src="assets/js/emoji-config.js"></script>
    <script src="assets/js/chat.js"></script>
    <script src="assets/js/chat_upload.js"></script>
    <script src="assets/js/emoji-simple.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const currentUserId = <?php echo $current_user_id; ?>;
            console.log('Current User ID:', currentUserId); 
            const userItems = document.querySelectorAll('.user-item-chat');
            const chatHeader = document.getElementById('chatHeader');
            const chatMessagesContainer = document.getElementById('chatMessages');
            const chatInputArea = document.getElementById('chatInputArea');
            const welcomeScreen = document.getElementById('welcomeScreen');
            const chattingWithAvatar = document.getElementById('chattingWithAvatar');
            const chattingWithName = document.getElementById('chattingWithName');
            const chattingWithStatus = document.getElementById('chattingWithStatus');
            const messageInput = document.getElementById('messageInput');
            const sendMessageBtn = document.getElementById('sendMessageBtn');
            const searchInput = document.getElementById('searchUsers');
            const noUsersView = document.querySelector('.no-users-container');
            const fileInput = document.getElementById('fileInput');
            const attachFileBtn = document.getElementById('attachFileBtn');
            const emojiBtn = document.getElementById('emojiBtn');
            const imageModal = document.getElementById('imageModal');
            const modalImage = document.getElementById('modalImage');
            const imageModalCloseBtn = document.getElementById('imageModalCloseBtn');

            let currentChatUserId = null;

            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    let hasVisibleUsers = false;

                    userItems.forEach(item => {
                        const userName = item.dataset.displayname.toLowerCase();
                        const isVisible = userName.includes(searchTerm);
                        item.style.display = isVisible ? '' : 'none';
                        if (isVisible) hasVisibleUsers = true;
                    });

                    if (noUsersView) {
                        noUsersView.style.display = hasVisibleUsers ? 'none' : 'flex';
                    }
                });
            }

            userItems.forEach(item => {
                item.addEventListener('click', function() {
                    userItems.forEach(i => i.classList.remove('active'));
                    this.classList.add('active');
                    currentChatUserId = this.dataset.userId;

                    if (chattingWithAvatar) chattingWithAvatar.src = this.dataset.avatar;
                    if (chattingWithName) chattingWithName.textContent = this.dataset.displayname;
                    if (chattingWithStatus) {
                        const isOnline = this.querySelector('.online-indicator') !== null;
                        chattingWithStatus.textContent = isOnline ? 'متصل الآن' : 'غير نشط';
                        chattingWithStatus.className = `text-[0.65rem] ${isOnline ? 'text-[var(--green-online)]' : 'text-[var(--text-muted)]'}`;
                    }

                    if (chatHeader) chatHeader.style.display = 'flex';
                    if (chatMessagesContainer) {
                        chatMessagesContainer.style.display = 'block';
                        loadMessages(currentChatUserId);
                    }
                    if (chatInputArea) chatInputArea.style.display = 'block';
                    if (welcomeScreen) welcomeScreen.style.display = 'none';

                    if (messageInput) messageInput.focus();
                    
                    const unreadBadge = this.querySelector('.absolute.bg-red-500');
                    if (unreadBadge) {
                        unreadBadge.remove();
                        
                        fetch(`api/chat.php?action=get_messages&user_id=${currentChatUserId}`, {
                            method: 'GET'
                        }).catch(error => {
                            console.error('Error marking messages as read:', error);
                        });
                    }
                });
            });

            if (messageInput) {
                messageInput.addEventListener('input', function() {
                    this.style.height = 'auto';
                    const newHeight = Math.min(this.scrollHeight, 90); 
                    this.style.height = newHeight + 'px';
                });

                messageInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        if (sendMessageBtn) sendMessageBtn.click();
                    }
                });
            }

            if (sendMessageBtn && messageInput && chatMessagesContainer) {
                sendMessageBtn.addEventListener('click', function() {
                    const messageText = messageInput.value.trim();
                    if (messageText && currentChatUserId) {
                        const tempId = 'msg_' + Date.now();
                        const messageHTML = `
                            <div id="${tempId}" class="message flex justify-end items-end group">
                                <button class="delete-message-btn mr-1 mb-0.5 text-[var(--text-muted)]" onclick="deleteMessage(this)" title="حذف الرسالة">
                                    <i class="bi bi-trash"></i>
                                </button>
                                <div class="message-bubble own">
                                    <div>${messageText.replace(/\n/g, '<br>')}</div>
                                    <div class="message-time-custom text-right opacity-75">الآن</div>
                                </div>
                            </div>`;
                        chatMessagesContainer.insertAdjacentHTML('beforeend', messageHTML);
                        messageInput.value = '';
                        messageInput.style.height = 'auto';
                        chatMessagesContainer.scrollTop = chatMessagesContainer.scrollHeight;
                        
                        fetch('api/chat.php?action=send_message', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                receiver_id: currentChatUserId,
                                content: messageText,
                                is_mine: 1 
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                loadMessages(currentChatUserId);
                            } else {
                                alert('فشل إرسال الرسالة: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error sending message:', error);
                            const msgElement = document.getElementById(tempId);
                            if (msgElement) {
                                const timeElement = msgElement.querySelector('.message-time-custom');
                                if (timeElement) {
                                    timeElement.innerHTML = '<span class="text-red-500">فشل الإرسال</span>';
                                }
                            }
                        });
                        
                        messageInput.focus();
                    }
                });
            }

            if (attachFileBtn && fileInput) {
                attachFileBtn.addEventListener('click', () => fileInput.click());
                fileInput.addEventListener('change', handleFileSelect);
            }

         
            if (imageModalCloseBtn) {
                imageModalCloseBtn.addEventListener('click', () => {
                    if (imageModal) imageModal.style.display = 'none';
                });
            }

            if (imageModal) {
                imageModal.addEventListener('click', (e) => {
                    if (e.target === imageModal) {
                        imageModal.style.display = 'none';
                    }
                });
            }

            let isRefreshingAfterSend = false;
            
            let skipMessageUpdate = false;
            
            let messageUpdateInterval;
            
            function startMessageUpdates() {
                if (messageUpdateInterval) clearInterval(messageUpdateInterval);
                messageUpdateInterval = setInterval(() => {
                    if (currentChatUserId && !skipMessageUpdate) loadMessages(currentChatUserId);
                }, 5000); 
            }
            
            function loadMessages(userId) {
                if (!chatMessagesContainer || !userId) return;
                
                console.log('Loading messages for user ID:', userId, 'Current user ID:', currentUserId); 
                
                chatMessagesContainer.innerHTML = `
                    <div class="flex justify-center items-center p-4">
                        <div class="animate-spin rounded-full h-6 w-6 border-t-2 border-b-2 border-[var(--active-indicator)]"></div>
                        <span class="mr-2 text-sm text-[var(--text-secondary)]">جاري تحميل الرسائل...</span>
                    </div>`;
                
                fetch(`api/chat.php?action=get_messages&user_id=${userId}`)
                    .then(response => response.json())
                    .then(data => {
                        console.log('Messages data:', data);
                        if (data.success && data.messages && data.messages.length > 0) {
                            chatMessagesContainer.innerHTML = '';
                            
                            data.messages.forEach(message => {
                                const isOwn = message.is_mine == 1;
                                console.log('Message:', message.id, 'Sender ID:', message.sender_id, 'Current User ID:', currentUserId, 'Is Mine:', message.is_mine, 'Is Own:', isOwn); 
                                
                                const messageTime = new Date(message.created_at);
                                const formattedTime = messageTime.toLocaleTimeString('ar', { hour: '2-digit', minute: '2-digit' });
                                
                                let messageHTML = `
                                    <div id="msg_${message.id}" class="message flex ${isOwn ? 'justify-end' : 'justify-start'} items-end group">
                                        ${!isOwn ? '' : `<button class="delete-message-btn mr-1 mb-0.5 text-[var(--text-muted)]" onclick="deleteMessage(this, ${message.id})" title="حذف الرسالة">
                                            <i class="bi bi-trash"></i>
                                        </button>`}
                                        <div class="message-bubble ${isOwn ? 'own' : 'other'}">`;
                                
                                if (message.media_url) {
                                    messageHTML += `<img src="${message.media_url}" alt="صورة" class="max-w-full rounded cursor-pointer" onclick="showImageModal(this.src)">`;
                                }
                                
                                if (message.content) {
                                    messageHTML += `<div>${message.content.replace(/\n/g, '<br>')}</div>`;
                                }
                                
                                messageHTML += `<div class="message-time-custom text-right opacity-75">${formattedTime}</div>
                                        </div>
                                        ${isOwn ? '' : `<button class="delete-message-btn ml-1 mb-0.5 text-[var(--text-muted)]" onclick="reportMessage(this, ${message.id})" title="الإبلاغ عن الرسالة">
                                            <i class="bi bi-flag"></i>
                                        </button>`}
                                    </div>`;
                                
                                chatMessagesContainer.insertAdjacentHTML('beforeend', messageHTML);
                            });
                            
                            chatMessagesContainer.scrollTop = chatMessagesContainer.scrollHeight;
                        } else {
                            chatMessagesContainer.innerHTML = `
                                <div class="flex justify-center items-center h-full">
                                    <div class="text-center p-4">
                                        <i class="bi bi-chat-dots text-4xl text-[var(--text-muted)] mb-2"></i>
                                        <p class="text-sm text-[var(--text-secondary)]">لا توجد رسائل بعد</p>
                                        <p class="text-xs text-[var(--text-muted)]">ابدأ محادثة جديدة مع هذا المستخدم</p>
                                    </div>
                                </div>`;
                        }
                    })
                    .catch(error => {
                        console.error('Error loading messages:', error);
                        chatMessagesContainer.innerHTML = `
                            <div class="flex justify-center items-center h-full">
                                <div class="text-center p-4">
                                    <i class="bi bi-exclamation-triangle text-4xl text-red-500 mb-2"></i>
                                    <p class="text-sm text-[var(--text-secondary)]">حدث خطأ في تحميل الرسائل</p>
                                    <p class="text-xs text-[var(--text-muted)]">يرجى المحاولة مرة أخرى</p>
                                </div>
                            </div>`;
                    });
                chatMessagesContainer.scrollTop = chatMessagesContainer.scrollHeight;
            }

            function handleFileSelect(event) {
                const files = event.target.files;
                if (!files.length) return;

                Array.from(files).forEach(file => {
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const loadingId = 'loading_' + Date.now();
                            const loadingHTML = `
                                <div id="${loadingId}" class="message flex justify-end items-end group">
                                    <div class="message-bubble own">
                                        <div class="flex items-center">
                                            <div class="animate-spin rounded-full h-4 w-4 border-t-2 border-b-2 border-[var(--active-indicator)] ml-2"></div>
                                            <span>جاري إرسال الصورة...</span>
                                        </div>
                                    </div>
                                </div>`;
                            chatMessagesContainer.insertAdjacentHTML('beforeend', loadingHTML);
                            chatMessagesContainer.scrollTop = chatMessagesContainer.scrollHeight;
                            
                            if (currentChatUserId) {
                                const formData = new FormData();
                                formData.append('receiver_id', currentChatUserId);
                                formData.append('image', file);
                                formData.append('content', ''); 
                                
                                fetch('api/chat_upload.php', {
                                    method: 'POST',
                                    body: formData
                                })
                                .then(response => response.json())
                                .then(data => {
                                    console.log('Image upload response:', data);
                                    const loadingElement = document.getElementById(loadingId);
                                    if (loadingElement) loadingElement.remove();
                                    
                                    if (data.success) {
                                        const messageHTML = `
                                            <div id="msg_${data.message_id}" class="message flex justify-end items-end group">
                                                <button class="delete-message-btn mr-1 mb-0.5 text-[var(--text-muted)]" onclick="deleteMessage(this, ${data.message_id})" title="حذف الرسالة">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                                <div class="message-bubble own">
                                                    <img src="${data.media_url}" alt="صورة" class="max-w-full rounded cursor-pointer" onclick="showImageModal(this.src)">
                                                    <div class="message-time-custom text-right opacity-75">الآن</div>
                                                </div>
                                            </div>`;
                                        chatMessagesContainer.insertAdjacentHTML('beforeend', messageHTML);
                                        chatMessagesContainer.scrollTop = chatMessagesContainer.scrollHeight;
                                    } else {
                                        console.error('Failed to save image:', data.message);
                                        alert('فشل في حفظ الصورة: ' + data.message);
                                    }
                                })
                                .catch(error => {
                                    const loadingElement = document.getElementById(loadingId);
                                    if (loadingElement) loadingElement.remove();
                                    
                                    console.error('Error saving image:', error);
                                    alert('حدث خطأ أثناء رفع الصورة');
                                });
                            }
                        };
                        reader.readAsDataURL(file);
                    }
                });
                event.target.value = '';
            }
        });

        function closeChat() {
            const chatHeader = document.getElementById('chatHeader');
            const chatInputArea = document.getElementById('chatInputArea');
            const chatMessages = document.getElementById('chatMessages');
            const welcomeScreen = document.getElementById('welcomeScreen');
            
            if (chatHeader) chatHeader.style.display = 'none';
            if (chatMessages) chatMessages.style.display = 'none';
            if (chatInputArea) chatInputArea.style.display = 'none';
            if (welcomeScreen) welcomeScreen.style.display = 'flex';

            document.querySelectorAll('.user-item-chat.active').forEach(item => item.classList.remove('active'));
        }

        function deleteMessage(buttonElement, messageId) {
            const messageElement = buttonElement.closest('.message');
            if (messageElement) {
                if (messageId) {
                    fetch('api/chat.php?action=delete_message', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `message_id=${messageId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            messageElement.remove();
                        } else {
                            alert('فشل حذف الرسالة: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error deleting message:', error);
                        alert('حدث خطأ أثناء حذف الرسالة');
                    });
                } else {
                    messageElement.remove();
                }
            }
        }
        
        function reportMessage(buttonElement, messageId) {
            if (confirm('هل تريد الإبلاغ عن هذه الرسالة؟')) {
                alert('تم الإبلاغ عن الرسالة بنجاح');
            }
        }

        function showImageModal(imageSrc) {
            const imageModal = document.getElementById('imageModal');
            const modalImage = document.getElementById('modalImage');
            if (imageModal && modalImage) {
                modalImage.src = imageSrc;
                imageModal.style.display = 'flex';
            }
        }
    </script>
</body>
</html>
