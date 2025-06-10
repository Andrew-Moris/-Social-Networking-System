
document.addEventListener('DOMContentLoaded', function() {
    let currentUserId = document.body.dataset.userId;
    let currentChatUserId = null;
    let lastMessageId = 0;
    let isLoading = false;
    let chatInterval = null;
    
    const loadedMessages = new Set(); 
    const deletedMessages = new Set(); 
    
    const chatMessagesContainer = document.getElementById('chatMessages');
    const messageInput = document.getElementById('messageInput');
    const sendMessageBtn = document.getElementById('sendMessageBtn');
    const userItems = document.querySelectorAll('.user-item');
    
    function setupEventListeners() {
        userItems.forEach(item => {
            const newItem = item.cloneNode(true);
            item.parentNode.replaceChild(newItem, item);
            
            newItem.addEventListener('click', function(e) {
                e.preventDefault();
                const userId = this.dataset.userId;
                if (userId) {
                    openChat(userId);
                }
            });
        });
        
        if (sendMessageBtn) {
            const newBtn = sendMessageBtn.cloneNode(true);
            sendMessageBtn.parentNode.replaceChild(newBtn, sendMessageBtn);
            
            newBtn.addEventListener('click', function(e) {
                e.preventDefault();
                sendMessage();
            });
        }
        
        if (messageInput) {
            const newInput = messageInput.cloneNode(true);
            messageInput.parentNode.replaceChild(newInput, messageInput);
            
            newInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });
        }
    }
    
    function openChat(userId) {
        if (!userId || currentChatUserId === userId) return;
        
        currentChatUserId = userId;
        
        lastMessageId = 0;
        loadedMessages.clear();
        
        if (chatInterval) {
            clearInterval(chatInterval);
            chatInterval = null;
        }
        
        loadMessages();
        
        chatInterval = setInterval(loadMessages, 10000);
        
        document.querySelectorAll('.user-item').forEach(item => {
            item.classList.remove('active');
            if (item.dataset.userId === userId) {
                item.classList.add('active');
            }
        });
        
        document.getElementById('chatMainArea').style.display = 'flex';
        document.getElementById('welcomeScreen').style.display = 'none';
        
        const userName = document.querySelector(`.user-item[data-user-id="${userId}"] .user-name`).textContent;
        document.getElementById('chattingWithName').textContent = userName;
    }
    
    function loadMessages() {
        if (!currentChatUserId || isLoading) return;
        
        isLoading = true;
        
        const timestamp = new Date().getTime();
        
        fetch(`api/chat.php?action=get_messages&user_id=${currentChatUserId}&last_id=${lastMessageId}&_t=${timestamp}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.messages && data.messages.length > 0) {
                    const newMessages = data.messages.filter(msg => {
                        return !loadedMessages.has(msg.id) && !deletedMessages.has(msg.id);
                    });
                    
                    if (newMessages.length > 0) {
                        const maxId = Math.max(...newMessages.map(msg => msg.id));
                        if (maxId > lastMessageId) {
                            lastMessageId = maxId;
                        }
                        
                        newMessages.forEach(msg => {
                            addMessageToUI(msg);
                            loadedMessages.add(msg.id);
                        });
                        
                        scrollToBottom();
                    }
                }
                
                isLoading = false;
            })
            .catch(error => {
                console.error('Error loading messages:', error);
                isLoading = false;
            });
    }
    
    function addMessageToUI(message) {
        if (document.getElementById(`message_${message.id}`)) {
            return;
        }
        
        const messageDiv = document.createElement('div');
        messageDiv.id = `message_${message.id}`;
        messageDiv.className = 'message';
        messageDiv.dataset.messageId = message.id;
        
        const isSent = message.sender_id == currentUserId || message.user_id == currentUserId;
        messageDiv.classList.add(isSent ? 'sent' : 'received');
        
        messageDiv.innerHTML = `
            <div class="message-content">
                <div class="message-text">${message.content || message.message}</div>
                <div class="message-time">${message.timestamp || message.created_at}</div>
            </div>
            ${isSent ? '<button class="delete-message" data-id="' + message.id + '">×</button>' : ''}
        `;
        
        const deleteBtn = messageDiv.querySelector('.delete-message');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', function() {
                deleteMessage(message.id);
            });
        }
        
        chatMessagesContainer.appendChild(messageDiv);
    }
    
    function sendMessage() {
        if (!currentChatUserId || !messageInput.value.trim()) return;
        
        if (sendMessageBtn) sendMessageBtn.disabled = true;
        
        const messageText = messageInput.value.trim();
        
        const tempId = 'temp_' + Date.now();
        
        const tempMessage = {
            id: tempId,
            content: messageText,
            sender_id: currentUserId,
            timestamp: new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})
        };
        
        addMessageToUI(tempMessage);
        scrollToBottom();
        
        messageInput.value = '';
        
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
            if (data.success && data.message_id) {
                const tempElement = document.getElementById(`message_${tempId}`);
                if (tempElement) {
                    tempElement.id = `message_${data.message_id}`;
                    tempElement.dataset.messageId = data.message_id;
                    
                    const deleteBtn = tempElement.querySelector('.delete-message');
                    if (deleteBtn) {
                        deleteBtn.dataset.id = data.message_id;
                    }
                    
                    loadedMessages.add(data.message_id);
                    
                    if (data.message_id > lastMessageId) {
                        lastMessageId = data.message_id;
                    }
                }
            } else {
                const tempElement = document.getElementById(`message_${tempId}`);
                if (tempElement) {
                    tempElement.remove();
                }
                
                alert('فشل في إرسال الرسالة');
            }
            
            if (sendMessageBtn) sendMessageBtn.disabled = false;
        })
        .catch(error => {
            console.error('Error sending message:', error);
            
            const tempElement = document.getElementById(`message_${tempId}`);
            if (tempElement) {
                tempElement.remove();
            }
            
            if (sendMessageBtn) sendMessageBtn.disabled = false;
        });
    }
    
    function deleteMessage(messageId) {
        if (!messageId) return;
        
        deletedMessages.add(messageId);
        
        const messageElement = document.getElementById(`message_${messageId}`);
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
            if (!data.success) {
                console.error('Failed to delete message:', data.message);
            }
        })
        .catch(error => {
            console.error('Error deleting message:', error);
        });
    }
    
    function scrollToBottom() {
        if (chatMessagesContainer) {
            chatMessagesContainer.scrollTop = chatMessagesContainer.scrollHeight;
        }
    }
    
    window.addEventListener('beforeunload', function() {
        if (chatInterval) {
            clearInterval(chatInterval);
        }
    });
    
    setupEventListeners();
});
