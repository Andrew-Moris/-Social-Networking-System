
document.addEventListener('DOMContentLoaded', function() {
    console.log('تم تحميل نظام المحادثة البسيط');
    
    const chatMessages = document.getElementById('chatMessages');
    const messageInput = document.getElementById('messageInput');
    const sendButton = document.getElementById('sendMessageBtn');
    const userItems = document.querySelectorAll('.user-item');
    const chatMainArea = document.getElementById('chatMainArea');
    const welcomeScreen = document.getElementById('welcomeScreen');
    const chatHeader = document.getElementById('chatHeader');
    const chatInputArea = document.getElementById('chatInputArea');
    
    let currentChatUserId = null;
    let lastMessageId = 0;
    let isLoading = false;
    let updateInterval = null;
    let displayedMessageIds = new Set();
    
    userItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const userId = this.dataset.userId;
            if (userId) {
                openChat(userId);
            }
        });
    });
    
    if (sendButton) {
        sendButton.addEventListener('click', function(e) {
            e.preventDefault();
            sendMessage();
        });
    }
    
    if (messageInput) {
        messageInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
    }
    
    function openChat(userId) {
        console.log('فتح محادثة مع المستخدم:', userId);
        
        currentChatUserId = userId;
        
        userItems.forEach(item => {
            item.classList.remove('active');
            if (item.dataset.userId === userId) {
                item.classList.add('active');
            }
        });
        
        if (chatMainArea) chatMainArea.style.display = 'flex';
        if (welcomeScreen) welcomeScreen.style.display = 'none';
        if (chatHeader) chatHeader.style.display = 'flex';
        if (chatInputArea) chatInputArea.style.display = 'flex';
        
        const userName = document.querySelector(`.user-item[data-user-id="${userId}"] .user-name`).textContent;
        const chattingWithName = document.getElementById('chattingWithName');
        if (chattingWithName) {
            chattingWithName.textContent = userName;
        }
        
        lastMessageId = 0;
        displayedMessageIds.clear();
        
        if (chatMessages) {
            chatMessages.innerHTML = '';
        }
        
        loadMessages();
        
        if (updateInterval) {
            clearInterval(updateInterval);
        }
        updateInterval = setInterval(loadMessages, 5000);
    }
    
    function loadMessages() {
        if (!currentChatUserId || isLoading) return;
        
        console.log('تحميل الرسائل للمستخدم:', currentChatUserId, 'آخر معرف:', lastMessageId);
        isLoading = true;
        
        const timestamp = new Date().getTime();
        
        fetch(`api/chat.php?action=get_messages&user_id=${currentChatUserId}&last_id=${lastMessageId}&t=${timestamp}`)
            .then(response => response.json())
            .then(data => {
                console.log('تم استلام البيانات:', data);
                
                if (data.success && data.messages && data.messages.length > 0) {
                    const newMessages = data.messages.filter(msg => !displayedMessageIds.has(msg.id));
                    
                    console.log('الرسائل الجديدة:', newMessages.length);
                    
                    newMessages.forEach(message => {
                        addMessageToUI(message);
                        displayedMessageIds.add(message.id);
                        
                        if (message.id > lastMessageId) {
                            lastMessageId = message.id;
                        }
                    });
                    
                    scrollToBottom();
                }
                
                isLoading = false;
            })
            .catch(error => {
                console.error('خطأ في تحميل الرسائل:', error);
                isLoading = false;
            });
    }
    
    function addMessageToUI(message) {
        if (!chatMessages) return;
        
        if (document.querySelector(`.message[data-message-id="${message.id}"]`)) {
            return;
        }
        
        const messageDiv = document.createElement('div');
        messageDiv.className = 'message';
        messageDiv.dataset.messageId = message.id;
        
        const currentUserId = document.body.dataset.userId;
        const isSent = message.sender_id == currentUserId;
        messageDiv.classList.add(isSent ? 'sent' : 'received');
        
        messageDiv.innerHTML = `
            <div class="message-content">
                <div class="message-text">${message.content}</div>
                <div class="message-time">${message.timestamp || formatTime(message.created_at)}</div>
            </div>
            ${isSent ? '<button class="delete-message" data-id="' + message.id + '">×</button>' : ''}
        `;
        
        const deleteBtn = messageDiv.querySelector('.delete-message');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', function() {
                deleteMessage(message.id);
            });
        }
        
        chatMessages.appendChild(messageDiv);
    }
    
    function sendMessage() {
        if (!currentChatUserId || !messageInput || !messageInput.value.trim()) return;
        
        const messageText = messageInput.value.trim();
        console.log('إرسال رسالة:', messageText);
        
        messageInput.disabled = true;
        if (sendButton) sendButton.disabled = true;
        
        const tempId = 'temp_' + Date.now();
        
        const tempMessage = {
            id: tempId,
            content: messageText,
            sender_id: document.body.dataset.userId,
            created_at: new Date().toISOString()
        };
        
        addMessageToUI(tempMessage);
        scrollToBottom();
        
        messageInput.value = '';
        
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'api/chat.php?action=send_message', true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                let data = { success: false };
                try {
                    data = JSON.parse(xhr.responseText);
                } catch (e) {
                    console.error('خطأ في تحليل الاستجابة:', e);
                }
                
                console.log('استجابة الإرسال:', data);
                
                if (data.success) {
                    const tempElement = document.querySelector(`.message[data-message-id="${tempId}"]`);
                    if (tempElement && data.message_id) {
                        tempElement.dataset.messageId = data.message_id;
                        displayedMessageIds.add(data.message_id);
                        
                        if (data.message_id > lastMessageId) {
                            lastMessageId = data.message_id;
                        }
                    }
                } else {
                    const tempElement = document.querySelector(`.message[data-message-id="${tempId}"]`);
                    if (tempElement) {
                        tempElement.remove();
                    }
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'error-notification';
                    errorDiv.textContent = data.message || 'فشل في إرسال الرسالة';
                    errorDiv.style.cssText = 'position:fixed;top:20px;right:20px;background-color:#f44336;color:white;padding:15px;border-radius:5px;z-index:9999;';
                    document.body.appendChild(errorDiv);
                    
                    setTimeout(() => {
                        errorDiv.style.opacity = '0';
                        errorDiv.style.transition = 'opacity 0.5s';
                        setTimeout(() => errorDiv.remove(), 500);
                    }, 3000);
                }
                
                messageInput.disabled = false;
                if (sendButton) sendButton.disabled = false;
                messageInput.focus();
            }
        } else {
                    displayedMessageIds.add(data.message_id);
                    
                    if (data.message_id > lastMessageId) {
                        lastMessageId = data.message_id;
                    }
                }
            } else {
                const tempElement = document.querySelector(`.message[data-message-id="${tempId}"]`);
                if (tempElement) {
                    tempElement.remove();
                }
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-notification';
                errorDiv.textContent = 'فشل في إرسال الرسالة';
                errorDiv.style.cssText = 'position:fixed;top:20px;right:20px;background-color:#f44336;color:white;padding:15px;border-radius:5px;z-index:9999;';
                document.body.appendChild(errorDiv);
                
                setTimeout(() => {
                    errorDiv.style.opacity = '0';
                    errorDiv.style.transition = 'opacity 0.5s';
                    setTimeout(() => errorDiv.remove(), 500);
                }, 3000);
            }
            
            messageInput.disabled = false;
            if (sendButton) sendButton.disabled = false;
            messageInput.focus();
        })
        .catch(error => {
            console.error('خطأ في إرسال الرسالة:', error);
            
            const tempElement = document.querySelector(`.message[data-message-id="${tempId}"]`);
            if (tempElement) {
                tempElement.remove();
            }
            
            messageInput.disabled = false;
            if (sendButton) sendButton.disabled = false;
        });
    }
    
    function deleteMessage(messageId) {
        console.log('حذف الرسالة:', messageId);
        
        const messageElement = document.querySelector(`.message[data-message-id="${messageId}"]`);
        if (messageElement) {
            messageElement.remove();
        }
        
        fetch('api/chat.php?action=delete_message', {
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
            console.log('استجابة الحذف:', data);
        })
        .catch(error => {
            console.error('خطأ في حذف الرسالة:', error);
        });
    }
    
    function scrollToBottom() {
        if (chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    }
    
    function formatTime(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    }
    
    const restartBtn = document.createElement('button');
    restartBtn.innerHTML = '🔄 إعادة تشغيل المحادثة';
    restartBtn.style.cssText = 'position:fixed;top:10px;right:10px;z-index:9999;background:red;color:white;border:none;padding:10px;border-radius:5px;cursor:pointer;';
    restartBtn.onclick = function() {
        console.log('إعادة تشغيل المحادثة');
        
        if (updateInterval) {
            clearInterval(updateInterval);
        }
        
        if (chatMessages) {
            chatMessages.innerHTML = '';
        }
        
        currentChatUserId = null;
        lastMessageId = 0;
        isLoading = false;
        displayedMessageIds.clear();
        
        window.location.reload();
    };
    document.body.appendChild(restartBtn);
    
    console.log('تم تهيئة نظام المحادثة بنجاح');
});
