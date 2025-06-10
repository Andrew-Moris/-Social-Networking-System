<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$current_user_id = (int)$_SESSION['user_id'];

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
        session_destroy();
        header('Location: login.php');
        exit;
    }
    
    $stmt = $pdo->prepare("
        SELECT u.*, 
               1 as is_online,
               (SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND sender_id = u.id AND is_read = 0) as unread_count,
               (SELECT MAX(created_at) FROM messages WHERE (sender_id = ? AND receiver_id = u.id) OR (sender_id = u.id AND receiver_id = ?)) as last_message_time
        FROM users u 
        WHERE u.id != ? AND u.id > 0
        ORDER BY CASE WHEN last_message_time IS NULL THEN 0 ELSE 1 END DESC, last_message_time DESC, u.id ASC
    ");
    $stmt->execute([$current_user_id, $current_user_id, $current_user_id, $current_user_id]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("خطأ في قاعدة البيانات: " . $e->getMessage());
    $db_error = true;
}

function formatChatTime($timestamp) {
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

function getChatUserAvatar($user) {
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

function getChatDisplayName($user) {
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
    <title>المحادثات الخاصة - SUT Premium</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        <?php include 'assets/css/chat-styles.css'; ?>
    </style>
</head>
<body data-user-id="<?php echo htmlspecialchars($current_user_id); ?>">
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
            
            <div class="users-list flex-grow overflow-y-auto p-0.5" id="usersList">
                <?php if (!empty($users)): ?>
                    <?php foreach ($users as $user): ?>
                        <?php 
                            $avatar = getChatUserAvatar($user);
                            $displayName = getChatDisplayName($user);
                            $isOnline = $user['is_online'] ? true : false;
                            $statusText = $isOnline ? 'متصل الآن' : 'غير متصل';
                            $statusClass = $isOnline ? 'text-[var(--green-online)]' : 'text-[var(--text-muted)]';
                            $nameClass = $isOnline ? 'text-[var(--text-primary)]' : 'text-[var(--text-secondary)]';
                            $fontWeight = $isOnline ? 'font-semibold' : 'font-medium';
                        ?>
                        <div class="user-item-chat flex items-center justify-between" 
                             data-user-id="<?php echo $user['id']; ?>" 
                             data-username="<?php echo htmlspecialchars($user['username']); ?>"
                             data-avatar="<?php echo htmlspecialchars($avatar); ?>"
                             data-displayname="<?php echo htmlspecialchars($displayName); ?>">
                            <div class="flex items-center flex-grow">
                                <div class="relative ml-2">
                                    <img src="<?php echo htmlspecialchars($avatar); ?>" alt="<?php echo htmlspecialchars($displayName); ?>" class="user-avatar-chat rounded-full object-cover">
                                    <?php if ($isOnline): ?>
                                        <span class="online-indicator absolute bottom-0 left-0 block rounded-full"></span>
                                    <?php endif; ?>
                                </div>
                                <div class="user-details overflow-hidden flex-grow">
                                    <span class="block text-xs <?php echo $fontWeight; ?> <?php echo $nameClass; ?> truncate"><?php echo htmlspecialchars($displayName); ?></span>
                                    <span class="block text-[0.65rem] <?php echo $statusClass; ?> truncate"><?php echo $statusText; ?></span>
                                </div>
                            </div>
                            <?php if (isset($user['unread_count']) && $user['unread_count'] > 0): ?>
                                <div class="unread-badge"><?php echo $user['unread_count']; ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
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

        <div class="chat-main-area flex-grow" id="chatMainArea">
            <div class="chat-header-custom flex items-center justify-between" id="chatHeader" style="display: none;">
                <div class="flex items-center"> 
                    <img id="chattingWithAvatar" src="" alt="" class="w-7 h-7 rounded-full ml-2 object-cover border-2 border-[var(--active-indicator)]">
                    <div>
                        <h6 id="chattingWithName" class="text-xs font-semibold text-[var(--text-primary)]"></h6>
                        <small id="chattingWithStatus" class="text-[0.65rem] text-[var(--green-online)]"></small>
                    </div>
                </div>
                <div class="flex items-center space-x-0.5 space-x-reverse">
                    <button class="chat-action-btn" title="معلومات المحادثة" id="chatInfoBtn"><i class="bi bi-info-circle text-xs"></i></button>
                    <button class="chat-action-btn" title="حذف المحادثة" id="deleteChatBtn"><i class="bi bi-trash3 text-xs"></i></button>
                    <button class="chat-action-btn" title="إغلاق المحادثة" onclick="closeChat()"><i class="bi bi-x-lg text-xs"></i></button>
                </div>
            </div>

            <div class="chat-messages flex-grow overflow-y-auto p-2.5 space-y-2" id="chatMessages">
            </div>

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
                <div id="filePreviewContainer" class="px-1 pt-1" style="display: none;"></div>
                <div class="flex items-center space-x-1 space-x-reverse p-1">
                    <input type="file" id="fileInput" class="hidden" accept="image/*">
                    <button class="chat-input-btn" id="attachFileBtn" title="إرفاق صورة">
                        <i class="bi bi-image text-md"></i>
                    </button>
                    <button class="chat-input-btn" id="emojiBtn" title="إضافة إيموجي">
                        <i class="bi bi-emoji-smile text-md"></i>
                    </button>
                    <textarea class="chat-textarea-custom flex-grow resize-none focus:ring-0" id="messageInput" placeholder="اكتب رسالتك هنا..." rows="1"></textarea>
                    <button class="chat-input-btn chat-send-btn" id="sendMessageBtn" title="إرسال">
                        <i class="bi bi-send-fill text-sm"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="fixed inset-0 modal-custom-bg flex items-center justify-center p-4 z-[100]" id="imageModal" tabindex="-1" style="display: none;">
        <div class="modal-content-custom w-full max-w-xl max-h-[80vh]">
            <div class="flex items-center justify-between p-2.5 border-b border-[var(--border-color)]">
                <h5 class="text-xs font-semibold text-[var(--text-primary)]">عرض الصورة</h5>
                <button type="button" class="text-[var(--text-muted)] hover:text-[var(--text-primary)] transition-colors p-1" id="imageModalCloseBtn" aria-label="Close">
                    <i class="bi bi-x-lg text-sm"></i>
                </button>
            </div>
            <div class="text-center p-2.5 overflow-y-auto">
                <img id="modalImage" src="" alt="صورة مكبرة" class="inline-block max-w-full max-h-[calc(80vh-70px)] rounded-md">
            </div>
        </div>
    </div>

    <script>
        const APP_URL = '<?php echo rtrim(dirname($_SERVER['PHP_SELF']), '/\\'); ?>';
        const CURRENT_USER_ID = <?php echo json_encode($current_user_id); ?>;
        const API_ENDPOINT = '<?php echo isset($_GET["api"]) && $_GET["api"] === "fixed" ? "api/chat_fixed.php" : "api/chat_new.php"; ?>';
    </script>
    <script src="assets/js/chat.js"></script>
    <script src="assets/js/chat_upload.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let currentChatUserId = null;
            
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
            const imageModal = document.getElementById('imageModal');
            const modalImage = document.getElementById('modalImage');
            const imageModalCloseBtn = document.getElementById('imageModalCloseBtn');
            
            userItems.forEach(item => {
                item.addEventListener('click', function() {
                    userItems.forEach(i => i.classList.remove('active'));
                    this.classList.add('active');
                    
                    currentChatUserId = this.dataset.userId;
                    const targetAvatar = this.dataset.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(this.dataset.displayname || 'U')}&background=random&color=fff&size=32`;
                    const targetDisplayName = this.dataset.displayname;
                    const statusElement = this.querySelector('.text-\\[0\\.65rem\\]');
                    const targetStatusText = statusElement ? statusElement.textContent : 'غير معروف';
                    const isOnline = statusElement ? statusElement.classList.contains('text-\\[var\\(--green-online\\)\\]') : false;
                    
                    if (chattingWithAvatar) chattingWithAvatar.src = targetAvatar;
                    if (chattingWithName) chattingWithName.textContent = targetDisplayName;
                    if (chattingWithStatus) {
                        chattingWithStatus.textContent = targetStatusText;
                        chattingWithStatus.className = `text-[0.65rem] truncate ${isOnline ? 'text-[var(--green-online)]' : 'text-[var(--text-muted)]'}`;
                    }
                    
                    if (chatHeader) chatHeader.style.display = 'flex';
                    if (chatMessagesContainer) chatMessagesContainer.innerHTML = ''; 
                    if (chatInputArea) chatInputArea.style.display = 'block';
                    if (welcomeScreen) welcomeScreen.style.display = 'none';
                    
                    const unreadBadge = this.querySelector('.unread-badge');
                    if (unreadBadge) unreadBadge.remove();
                    
                    loadMessages(currentChatUserId);
                    
                    if (messageInput) messageInput.focus();
                });
            });
            
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    let foundUsers = false;
                    
                    userItems.forEach(item => {
                        const username = item.dataset.username.toLowerCase();
                        const displayName = item.dataset.displayname.toLowerCase();
                        
                        if (username.includes(searchTerm) || displayName.includes(searchTerm)) {
                            item.style.display = 'flex';
                            foundUsers = true;
                        } else {
                            item.style.display = 'none';
                        }
                    });
                });
            }
            
            if (messageInput) {
                messageInput.addEventListener('input', function() {
                    this.style.height = 'auto';
                    let newHeight = this.scrollHeight;
                    const maxHeight = 90;
                    if (newHeight > maxHeight) newHeight = maxHeight;
                    this.style.height = newHeight + 'px';
                });
                
                messageInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        if (sendMessageBtn) sendMessageBtn.click();
                    }
                });
            }
            
            if (sendMessageBtn && messageInput) {
                sendMessageBtn.addEventListener('click', function() {
                    sendMessage();
                });
            }
            
            if (imageModal && imageModalCloseBtn) {
                imageModalCloseBtn.addEventListener('click', () => {
                    imageModal.style.display = 'none';
                });
                
                imageModal.addEventListener('click', (event) => {
                    if (event.target === imageModal) {
                        imageModal.style.display = 'none';
                    }
                });
            }
            
            function loadMessages(userId) {
                if (!chatMessagesContainer) return;
                
                chatMessagesContainer.innerHTML = '<div class="flex justify-center p-4"><div class="loader"></div></div>';
                
                fetch(`api/chat.php?action=get_messages&user_id=${userId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.messages && data.messages.length > 0) {
                            chatMessagesContainer.innerHTML = '';
                            
                            data.messages.forEach(message => {
                                const isOwn = message.sender_id == CURRENT_USER_ID;
                                addMessageToUI(message.content, isOwn, message.created_at, message.media_url, message.id);
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
                                </div>
                            `;
                        }
                    })
                    .catch(error => {
                        console.error('Error loading messages:', error);
                        chatMessagesContainer.innerHTML = `
                            <div class="flex justify-center items-center h-full">
                                <div class="text-center p-4">
                                    <i class="bi bi-exclamation-triangle text-4xl text-red-500 mb-2"></i>
                                    <p class="text-sm text-[var(--text-secondary)]">حدث خطأ في تحميل الرسائل</p>
                                </div>
                            </div>
                        `;
                    });
            }
            
            function addMessageToUI(text, isOwn, timestamp, mediaUrl = null, messageId = null) {
                if (!chatMessagesContainer) return;
                
                const messageTime = formatTime(timestamp);
                const messageElement = document.createElement('div');
                messageElement.className = `message flex ${isOwn ? 'justify-start' : 'justify-end'} items-end group`;
                if (messageId) messageElement.dataset.messageId = messageId;
                
                let mediaHtml = '';
                if (mediaUrl) {
                    mediaHtml = `
                        <div class="message-media-container">
                            <img src="${mediaUrl}" alt="Media" class="message-media" onclick="showImageModal('${mediaUrl}')">
                        </div>
                    `;
                }
                
                const deleteButtonHtml = `
                    <button class="delete-message-btn ${isOwn ? 'mr-1' : 'ml-1 order-first'} mb-0.5 text-[var(--text-muted)]" onclick="deleteMessage(this, ${messageId})" title="حذف الرسالة">
                        <i class="bi bi-trash"></i>
                    </button>
                `;
                
                messageElement.innerHTML = `
                    ${isOwn ? '' : deleteButtonHtml}
                    <div class="message-bubble ${isOwn ? 'own' : 'other'}">
                        <div>${text.replace(/\n/g, '<br>')}</div>
                        ${mediaHtml}
                        <div class="message-time-custom text-${isOwn ? 'right' : 'left'} opacity-75">${messageTime}</div>
                    </div>
                    ${isOwn ? deleteButtonHtml : ''}
                `;
                
                chatMessagesContainer.appendChild(messageElement);
            }
            
            function sendMessage() {
                if (!messageInput || !currentChatUserId) return;
                
                const messageText = messageInput.value.trim();
                if (!messageText) return;
                
                messageInput.value = '';
                messageInput.style.height = 'auto';
                messageInput.focus();
                
                const tempId = 'temp-' + Date.now();
                addMessageToUI(messageText, true, new Date().toISOString(), null, tempId);
                chatMessagesContainer.scrollTop = chatMessagesContainer.scrollHeight;
                
                fetch('api/chat.php?action=send_message', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        receiver_id: currentChatUserId,
                        content: messageText
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const tempElement = document.querySelector(`.message[data-message-id="${tempId}"]`);
                        if (tempElement) {
                            tempElement.dataset.messageId = data.message.id;
                        }
                    } else {
                        const tempElement = document.querySelector(`.message[data-message-id="${tempId}"]`);
                        if (tempElement) {
                            tempElement.remove();
                        }
                        
                        alert('فشل في إرسال الرسالة');
                    }
                })
                .catch(error => {
                    console.error('Error sending message:', error);
                    const tempElement = document.querySelector(`.message[data-message-id="${tempId}"]`);
                    if (tempElement) {
                        tempElement.remove();
                    }
                    
                    alert('فشل في إرسال الرسالة');
                });
            }
            
            function formatTime(dateString) {
                if (!dateString) return 'الآن';
                
                const date = new Date(dateString);
                const now = new Date();
                const diff = now - date;
                
                if (diff < 60000) { 
                    return 'الآن';
                } else if (diff < 3600000) { 
                    const minutes = Math.floor(diff / 60000);
                    return `منذ ${minutes} ${minutes === 1 ? 'دقيقة' : 'دقائق'}`;
                } else if (diff < 86400000) {
                    const hours = Math.floor(diff / 3600000);
                    return `منذ ${hours} ${hours === 1 ? 'ساعة' : 'ساعات'}`;
                } else {
                    return date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                }
            }
            
            if (userItems.length > 0) {
                userItems[0].click();
            }
        });
        
        function closeChat() {
            const chatHeader = document.getElementById('chatHeader');
            const chatInputArea = document.getElementById('chatInputArea');
            const chatMessages = document.getElementById('chatMessages');
            const welcomeScreen = document.getElementById('welcomeScreen');
            
            if (chatHeader) chatHeader.style.display = 'none';
            if (chatMessages) chatMessages.innerHTML = '';
            if (chatInputArea) chatInputArea.style.display = 'none';
            if (welcomeScreen) welcomeScreen.style.display = 'flex';

            document.querySelectorAll('.user-item-chat.active').forEach(item => item.classList.remove('active'));
        }
        
        function deleteMessage(buttonElement, messageId) {
            if (!messageId) return;
            
            if (confirm('هل أنت متأكد من حذف هذه الرسالة؟')) {
                const messageElement = buttonElement.closest('.message');
                
                if (messageElement) {
                    messageElement.remove();
                }
                
                fetch(`api/chat.php?action=delete_message`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        message_id: messageId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        console.error('Failed to delete message:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error deleting message:', error);
                });
            }
        }
        
        function showImageModal(imageUrl) {
            const imageModal = document.getElementById('imageModal');
            const modalImage = document.getElementById('modalImage');
            
            if (imageModal && modalImage) {
                modalImage.src = imageUrl;
                imageModal.style.display = 'flex';
            }
        }
    </script>
</body>
</html>
