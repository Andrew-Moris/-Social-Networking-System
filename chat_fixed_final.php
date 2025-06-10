<?php


session_start();
require_once 'config.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$current_user_id = $_SESSION['user_id'];
$current_username = $_SESSION['username'];

$target_user_id = isset($_GET['user']) ? (int)$_GET['user'] : null;
$target_user = null;

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $user_stmt->execute([$current_user_id]);
    $current_user = $user_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($target_user_id) {
        $target_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $target_stmt->execute([$target_user_id]);
        $target_user = $target_stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    $users_stmt = $pdo->prepare("
        SELECT id, username, first_name, last_name, avatar_url
        FROM users 
        WHERE id != ? 
        ORDER BY username ASC
        LIMIT 20
    ");
    $users_stmt->execute([$current_user_id]);
    $all_users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Error in chat.php: " . $e->getMessage());
    $current_user = ['username' => $current_username, 'first_name' => '', 'last_name' => '', 'avatar_url' => ''];
    $all_users = [];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> | المحادثة</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }
        
        .chat-container {
            max-width: 1200px;
            margin: 20px auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            height: calc(100vh - 40px);
            display: flex;
        }
        
        .chat-sidebar {
            width: 350px;
            background: #f8f9fa;
            border-right: 1px solid #e9ecef;
            display: flex;
            flex-direction: column;
        }
        
        .sidebar-header {
            padding: 20px;
            background: #fff;
            border-bottom: 1px solid #e9ecef;
        }
        
        .sidebar-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .users-list {
            flex: 1;
            overflow-y: auto;
            padding: 10px;
        }
        
        .user-item {
            display: flex;
            align-items: center;
            padding: 15px;
            margin-bottom: 5px;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #fff;
            border: 1px solid #e9ecef;
        }
        
        .user-item:hover {
            background: #e3f2fd;
            transform: translateX(-5px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .user-item.active {
            background: #2196f3;
            color: white;
            transform: translateX(-5px);
            box-shadow: 0 4px 12px rgba(33, 150, 243, 0.3);
        }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-left: 15px;
            border: 3px solid #e9ecef;
        }
        
        .user-item.active .user-avatar {
            border-color: white;
        }
        
        .user-info {
            flex: 1;
        }
        
        .user-name {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .user-username {
            font-size: 0.9rem;
            opacity: 0.7;
        }
        
        .chat-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #fff;
        }
        
        .chat-header {
            padding: 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            display: none;
        }
        
        .chat-header.active {
            display: block;
        }
        
        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: #f8f9fa;
        }
        
        .message {
            margin-bottom: 15px;
            display: flex;
        }
        
        .message.sent {
            justify-content: flex-end;
        }
        
        .message.received {
            justify-content: flex-start;
        }
        
        .message-bubble {
            max-width: 70%;
            padding: 12px 16px;
            border-radius: 18px;
            word-wrap: break-word;
        }
        
        .message.sent .message-bubble {
            background: #2196f3;
            color: white;
        }
        
        .message.received .message-bubble {
            background: #e9ecef;
            color: #2c3e50;
        }
        
        .message-time {
            font-size: 0.8rem;
            opacity: 0.7;
            margin-top: 5px;
        }
        
        .chat-input-area {
            padding: 20px;
            background: #fff;
            border-top: 1px solid #e9ecef;
            display: none;
        }
        
        .chat-input-area.active {
            display: block;
        }
        
        .input-form {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }
        
        .message-input {
            flex: 1;
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 25px;
            resize: none;
            font-family: inherit;
            font-size: 1rem;
            outline: none;
            transition: border-color 0.3s ease;
        }
        
        .message-input:focus {
            border-color: #2196f3;
        }
        
        .send-button {
            background: #2196f3;
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .send-button:hover {
            background: #1976d2;
            transform: scale(1.05);
        }
        
        .empty-chat {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #6c757d;
        }
        
        .empty-chat i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .empty-chat h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }
        
        .empty-chat p {
            opacity: 0.7;
        }
        
        .nav-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
    </style>
</head>
<body>
    <header class="nav-header">
        <div class="container mx-auto px-6 flex justify-between items-center">
            <div class="text-2xl font-bold">
                <a href="home.php" class="text-blue-600 hover:text-blue-800 transition-colors">
                    <span class="text-blue-600">SUT</span> 
                    <span class="text-gray-700">Premium</span>
                </a>
            </div>
            
            <nav class="flex items-center space-x-6">
                <a href="home.php" class="text-gray-600 hover:text-blue-600 transition-colors">
                    <i class="bi bi-house-door text-xl"></i>
                </a>
                <a href="discover.php" class="text-gray-600 hover:text-blue-600 transition-colors">
                    <i class="bi bi-compass text-xl"></i>
                </a>
                <a href="chat.php" class="text-blue-600 transition-colors">
                    <i class="bi bi-chat text-xl"></i>
                </a>
                <a href="friends.php" class="text-gray-600 hover:text-blue-600 transition-colors">
                    <i class="bi bi-people text-xl"></i>
                </a>
                <a href="u.php" class="text-gray-600 hover:text-blue-600 transition-colors">
                    <i class="bi bi-person text-xl"></i>
                </a>
                <a href="logout.php" class="text-gray-600 hover:text-red-500 transition-colors">
                    <i class="bi bi-box-arrow-right text-xl"></i>
                </a>
            </nav>
        </div>
    </header>

    <div class="chat-container">
        <div class="chat-sidebar">
            <div class="sidebar-header">
                <h2 class="sidebar-title">
                    <i class="bi bi-chat-dots"></i>
                    المحادثات
                </h2>
                <p class="text-gray-600">اختر مستخدم لبدء المحادثة</p>
            </div>
            
            <div class="users-list">
                <?php if (empty($all_users)): ?>
                    <div class="text-center py-8">
                        <i class="bi bi-people text-4xl text-gray-400 mb-3"></i>
                        <p class="text-gray-500">لا يوجد مستخدمين متاحين</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($all_users as $user): ?>
                        <div class="user-item" onclick="openChat(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>', '<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>')">
                            <img src="<?php echo !empty($user['avatar_url']) ? htmlspecialchars($user['avatar_url']) : 'https://ui-avatars.com/api/?name=' . urlencode($user['username']) . '&background=2196f3&color=fff&size=100'; ?>" 
                                 alt="<?php echo htmlspecialchars($user['username']); ?>" 
                                 class="user-avatar">
                            <div class="user-info">
                                <div class="user-name">
                                    <?php echo !empty($user['first_name']) ? htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) : htmlspecialchars($user['username']); ?>
                                </div>
                                <div class="user-username">@<?php echo htmlspecialchars($user['username']); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="chat-main">
            <div class="chat-header" id="chatHeader">
                <div class="flex items-center">
                    <img id="chatUserAvatar" src="" alt="" class="w-12 h-12 rounded-full ml-3">
                    <div>
                        <h3 id="chatUserName" class="font-semibold text-lg"></h3>
                        <p id="chatUserUsername" class="text-gray-600 text-sm"></p>
                    </div>
                </div>
            </div>
            
            <div class="chat-messages" id="chatMessages">
                <div class="empty-chat">
                    <i class="bi bi-chat-heart"></i>
                    <h3>مرحباً بك في المحادثة</h3>
                    <p>اختر مستخدم من القائمة لبدء المحادثة</p>
                </div>
            </div>
            
            <div class="chat-input-area" id="chatInputArea">
                <form class="input-form" id="messageForm">
                    <textarea class="message-input" id="messageInput" placeholder="اكتب رسالتك هنا..." rows="1"></textarea>
                    <button type="submit" class="send-button">
                        <i class="bi bi-send-fill"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        let currentChatUser = null;
        let messagePolling = null;
        
        console.log('🚀 Chat system initialized');
        
        function openChat(userId, username, fullName) {
            console.log('🔥 Opening chat with:', userId, username, fullName);
            
            if (!userId || !username) {
                console.error('❌ Invalid parameters:', userId, username);
                alert('خطأ في البيانات');
                return;
            }
            
            try {
                currentChatUser = parseInt(userId);
                
                document.querySelectorAll('.user-item').forEach(item => {
                    item.classList.remove('active');
                });
                event.currentTarget.classList.add('active');
                
                const chatHeader = document.getElementById('chatHeader');
                const chatUserAvatar = document.getElementById('chatUserAvatar');
                const chatUserName = document.getElementById('chatUserName');
                const chatUserUsername = document.getElementById('chatUserUsername');
                const chatInputArea = document.getElementById('chatInputArea');
                
                if (chatHeader && chatUserAvatar && chatUserName && chatUserUsername && chatInputArea) {
                    chatHeader.classList.add('active');
                    chatInputArea.classList.add('active');
                    
                    chatUserAvatar.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(username)}&background=2196f3&color=fff&size=100`;
                    chatUserName.textContent = fullName || username;
                    chatUserUsername.textContent = '@' + username;
                    
                    console.log('✅ Chat header updated');
                } else {
                    console.error('❌ Chat elements not found');
                }
                
                loadMessages(userId);
                
                if (messagePolling) {
                    clearInterval(messagePolling);
                }
                messagePolling = setInterval(() => loadMessages(userId, true), 3000);
                
                console.log('✅ Chat opened successfully');
                
            } catch (error) {
                console.error('❌ Error opening chat:', error);
                alert('حدث خطأ في فتح المحادثة');
            }
        }
        
        async function loadMessages(userId, silent = false) {
            if (!silent) console.log('📨 Loading messages for user:', userId);
            
            try {
                const response = await fetch(`api/chat.php?action=get_messages&user_id=${userId}`);
                const data = await response.json();
                
                if (data.success && data.messages) {
                    displayMessages(data.messages);
                    if (!silent) console.log('✅ Messages loaded:', data.messages.length);
                } else {
                    console.error('❌ Failed to load messages:', data.message);
                    if (!silent) displayMessages([]);
                }
            } catch (error) {
                console.error('❌ Error loading messages:', error);
                if (!silent) displayMessages([]);
            }
        }
        
        function displayMessages(messages) {
            const container = document.getElementById('chatMessages');
            
            if (!container) {
                console.error('❌ Messages container not found');
                return;
            }
            
            if (!messages || messages.length === 0) {
                container.innerHTML = `
                    <div class="empty-chat">
                        <i class="bi bi-chat-heart"></i>
                        <h3>لا توجد رسائل بعد</h3>
                        <p>ابدأ المحادثة الآن!</p>
                    </div>
                `;
                return;
            }
            
            const currentUserId = <?php echo $current_user_id; ?>;
            
            container.innerHTML = messages.map(message => {
                const isSent = message.sender_id == currentUserId;
                const messageClass = isSent ? 'sent' : 'received';
                
                return `
                    <div class="message ${messageClass}">
                        <div class="message-bubble">
                            <div>${message.content.replace(/\n/g, '<br>')}</div>
                            <div class="message-time">${formatTime(message.created_at)}</div>
                        </div>
                    </div>
                `;
            }).join('');
            
            container.scrollTop = container.scrollHeight;
        }
        
        async function sendMessage(e) {
            e.preventDefault();
            
            const input = document.getElementById('messageInput');
            const content = input.value.trim();
            
            if (!content) {
                alert('يرجى كتابة رسالة');
                return;
            }
            
            if (!currentChatUser) {
                alert('يرجى اختيار محادثة أولاً');
                return;
            }
            
            try {
                const response = await fetch('api/chat.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'send_message',
                        receiver_id: currentChatUser,
                        content: content
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    input.value = '';
                    loadMessages(currentChatUser);
                    console.log('✅ Message sent successfully');
                } else {
                    console.error('❌ Failed to send message:', result.message);
                    alert('فشل في إرسال الرسالة');
                }
            } catch (error) {
                console.error('❌ Error sending message:', error);
                alert('حدث خطأ في إرسال الرسالة');
            }
        }
        
        function formatTime(timestamp) {
            const date = new Date(timestamp);
            return date.toLocaleTimeString('ar-SA', {
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        function autoResize(textarea) {
            textarea.style.height = 'auto';
            textarea.style.height = textarea.scrollHeight + 'px';
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            console.log('✅ DOM loaded, setting up event listeners');
            
            const messageForm = document.getElementById('messageForm');
            const messageInput = document.getElementById('messageInput');
            
            if (messageForm) {
                messageForm.addEventListener('submit', sendMessage);
                console.log('✅ Message form event listener added');
            }
            
            if (messageInput) {
                messageInput.addEventListener('input', function() {
                    autoResize(this);
                });
                
                messageInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        sendMessage(e);
                    }
                });
                console.log('✅ Message input event listeners added');
            }
            
            <?php if ($target_user_id && $target_user): ?>
                setTimeout(() => {
                    openChat(<?php echo $target_user_id; ?>, '<?php echo htmlspecialchars($target_user['username']); ?>', '<?php echo htmlspecialchars($target_user['first_name'] . ' ' . $target_user['last_name']); ?>');
                }, 500);
            <?php endif; ?>
        });
        
        console.log('🎉 Chat system ready!');
    </script>
</body>
</html> 