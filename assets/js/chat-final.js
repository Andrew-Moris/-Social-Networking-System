
if (window.chatInterval) {
    clearInterval(window.chatInterval);
    window.chatInterval = null;
}

window.chatData = {
    isLoading: false,
    displayedMessages: [],
    lastCheck: 0
};

function fetchMessages() {
    if (window.chatData.isLoading) return;
    
    window.chatData.isLoading = true;
    
    fetch('api/chat.php?action=get_messages&user_id=' + (window.currentChatUserId || '') + '&t=' + Date.now())
        .then(response => response.json())
        .then(data => {
            try {
                if (data.success && data.messages) {
                    displayMessagesOnce(data.messages);
                }
            } catch (e) {
                console.error('خطأ في تحليل JSON:', e);
            }
        })
        .catch(error => {
            console.error('خطأ في جلب الرسائل:', error);
        })
        .finally(() => {
            window.chatData.isLoading = false;
        });
}

function displayMessagesOnce(messages) {
    const chatContainer = document.getElementById('chatMessages');
    if (!chatContainer) return;
    
    chatContainer.innerHTML = '';
    
    window.chatData.displayedMessages = [];
    
    if (Array.isArray(messages)) {
        messages.forEach((message, index) => {
            const messageElement = createSingleMessage(message, index);
            chatContainer.appendChild(messageElement);
            window.chatData.displayedMessages.push(message);
        });
    }
    
    chatContainer.scrollTop = chatContainer.scrollHeight;
}

function createSingleMessage(message, index) {
    const messageDiv = document.createElement('div');
    messageDiv.className = 'message';
    messageDiv.setAttribute('data-message-id', message.id || index);
    
    const currentUserId = document.body.dataset.userId;
    const isSent = message.sender_id == currentUserId;
    messageDiv.classList.add(isSent ? 'sent' : 'received');
    
    let messageContent = '';
    let messageTime = '';
    
    if (typeof message === 'object') {
        messageContent = message.content || message.message || message.text || '';
        messageTime = message.timestamp || message.time || message.created_at || '';
    } else {
        messageContent = message;
        messageTime = new Date().toLocaleTimeString('ar-EG', {hour: '2-digit', minute: '2-digit'});
    }
    
    messageDiv.innerHTML = `
        <div class="message-content">
            <div class="message-text">${messageContent}</div>
            <div class="message-time">${messageTime}</div>
        </div>
        ${isSent ? '<button class="delete-message" data-id="' + (message.id || index) + '">×</button>' : ''}
    `;
    
    const deleteBtn = messageDiv.querySelector('.delete-message');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', function() {
            deleteMessage(message.id || index);
        });
    }
    
    return messageDiv;
}

function deleteMessage(messageId) {
    if (!messageId) return;
    
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
        if (!data.success) {
            console.error('Failed to delete message:', data.message);
        }
    })
    .catch(error => {
        console.error('Error deleting message:', error);
    });
}

function sendMessage() {
    const messageInput = document.getElementById('messageInput');
    
    if (!messageInput || !window.currentChatUserId) return;
    
    const message = messageInput.value.trim();
    if (message === '' || window.chatData.isLoading) return;
    
    window.chatData.isLoading = true;
    messageInput.disabled = true;
    
    fetch('api/chat.php?action=send_message', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            receiver_id: window.currentChatUserId,
            content: message
        })
    })
    .then(response => response.json())
    .then(data => {
        messageInput.value = '';
        setTimeout(() => {
            fetchMessages();
        }, 500);
    })
    .catch(error => {
        console.error('خطأ في الإرسال:', error);
    })
    .finally(() => {
        window.chatData.isLoading = false;
        messageInput.disabled = false;
        messageInput.focus();
    });
}

function openChat(userId) {
    if (!userId) return;
    
    window.currentChatUserId = userId;
    
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
    
    fetchMessages();
}

function initializeChat() {
    if (window.chatInterval) {
        clearInterval(window.chatInterval);
    }
    
    window.chatInterval = setInterval(() => {
        if (!window.chatData.isLoading && window.currentChatUserId) {
            fetchMessages();
        }
    }, 5000);
    
    setupEventListeners();
}

function setupEventListeners() {
    document.querySelectorAll('.user-item').forEach(item => {
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
    
    const sendButton = document.getElementById('sendMessageBtn');
    
    if (sendButton) {
        const newSendButton = sendButton.cloneNode(true);
        sendButton.parentNode.replaceChild(newSendButton, sendButton);
        
        document.getElementById('sendMessageBtn').addEventListener('click', function(e) {
            e.preventDefault();
            sendMessage();
        });
    }
    
    const messageInput = document.getElementById('messageInput');
    
    if (messageInput) {
        const newInput = messageInput.cloneNode(true);
        messageInput.parentNode.replaceChild(newInput, messageInput);
        
        document.getElementById('messageInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                sendMessage();
            }
        });
    }
}

window.addEventListener('beforeunload', function() {
    if (window.chatInterval) {
        clearInterval(window.chatInterval);
    }
});

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeChat);
} else {
    initializeChat();
}

window.restartChat = function() {
    if (window.chatInterval) {
        clearInterval(window.chatInterval);
    }
    
    const chatContainer = document.getElementById('chatMessages');
    if (chatContainer) {
        chatContainer.innerHTML = '';
    }
    
    window.chatData = {
        isLoading: false,
        displayedMessages: [],
        lastCheck: 0
    };
    
    setTimeout(initializeChat, 1000);
};

setTimeout(() => {
    if (!document.getElementById('restartChatBtn')) {
        const restartBtn = document.createElement('button');
        restartBtn.id = 'restartChatBtn';
        restartBtn.innerHTML = '🔄';
        restartBtn.style.cssText = 'position:fixed;top:10px;right:10px;z-index:9999;background:red;color:white;border:none;padding:10px;border-radius:5px;cursor:pointer;';
        restartBtn.onclick = window.restartChat;
        document.body.appendChild(restartBtn);
    }
}, 2000);
