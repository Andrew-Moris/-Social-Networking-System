
const ChatApp = {
    config: {
        updateInterval: 5000, 
        maxMessages: 100,     
        scrollThreshold: 50,   
        apiEndpoint: typeof API_ENDPOINT !== 'undefined' ? API_ENDPOINT : 'api/chat.php'
    },
    
    state: {
        currentUserId: null,
        currentChatUserId: null,
        lastMessageId: null,
        messageIdsInDOM: new Set(), 
        deletedMessageIds: new Set(),
        attachedFiles: [],
        messageUpdateInterval: null,
        lastUserInteraction: 0,
        isInitialized: false,
        isUpdating: false, 
        pendingMessages: new Map() 
    },
    
    elements: {},
    
    init: function() {
        if (this.state.isInitialized) {
            console.log('Chat application already initialized');
            return;
        }
        
        console.log('Initializing chat application...');
        this.state.isInitialized = true;
        
        this.state.currentUserId = document.body.dataset.userId;
        console.log('Current user ID:', this.state.currentUserId);
        
        this.cacheElements();
        this.bindEvents();
        this.loadUsers();
        this.startAutoRefresh();
        this.initEmojiPicker();
        
        this.loadConversations();
        
        if (this.state.messageUpdateInterval) {
            clearInterval(this.state.messageUpdateInterval);
        }
        
        this.state.messageUpdateInterval = setInterval(() => {
            this.periodicUpdate();
        }, this.config.updateInterval);
        
        console.log('Chat application initialized successfully');
    },
    
    cacheElements: function() {
        console.log('Caching DOM elements...');
        this.elements = {
            userItems: document.querySelectorAll('.user-item'),
            chatMainArea: document.getElementById('chatMainArea'),
            welcomeScreen: document.getElementById('welcomeScreen'),
            chatHeader: document.getElementById('chatHeader'),
            chatMessagesContainer: document.getElementById('chatMessages'),
            chatInputArea: document.getElementById('chatInputArea'),
            chattingWithName: document.getElementById('chattingWithName'),
            chattingWithAvatar: document.getElementById('chattingWithAvatar'),
            chattingWithStatus: document.getElementById('chattingWithStatus'),
            messageInput: document.getElementById('messageInput'),
            sendMessageBtn: document.getElementById('sendMessageBtn'),
            attachFileBtn: document.getElementById('attachFileBtn'),
            fileInput: document.getElementById('fileInput'),
            filePreviewContainer: document.getElementById('filePreview'),
            sidebar: document.querySelector('.chat-sidebar'),
            imageModal: document.getElementById('imageModal'),
            modalImage: document.getElementById('modalImage'),
            imageModalCloseBtn: document.getElementById('imageModalCloseBtn'),
            usersList: document.querySelector('.users-list'),
            usersSection: document.querySelector('.users-section')
        };
    },
    
    setupEventListeners: function() {
        console.log('Setting up event listeners...');
        
        const trackInteraction = () => {
            this.state.lastUserInteraction = Date.now();
        };
        
        document.addEventListener('click', trackInteraction, { passive: true });
        document.addEventListener('keydown', trackInteraction, { passive: true });
        document.addEventListener('touchstart', trackInteraction, { passive: true });
        
        if (this.elements.userItems && this.elements.userItems.length > 0) {
            this.elements.userItems.forEach(item => {
                const chatArea = item.querySelector('.user-item-link');
                if (chatArea) {
                    const newChatArea = chatArea.cloneNode(true);
                    chatArea.parentNode.replaceChild(newChatArea, chatArea);
                    
                    newChatArea.addEventListener('click', (event) => {
                        event.preventDefault();
                        this.handleUserItemClick(item);
                    });
                }
                
                const profileLink = item.querySelector('.view-profile-link');
                if (profileLink) {
                    const newProfileLink = profileLink.cloneNode(true);
                    profileLink.parentNode.replaceChild(newProfileLink, profileLink);
                    
                    newProfileLink.addEventListener('click', (event) => {
                        event.stopPropagation();
                    });
                }
            });
        }
    },
    
    loadConversations: function() {
        fetch('api/chat.php?action=get_conversations')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.conversations) {
                    const usersList = document.querySelector('.users-list');
                    const usersSection = usersList.querySelector('.users-section');
                    
                    const activeChat = document.querySelector('.user-item.active');
                    const activeChatId = activeChat ? activeChat.dataset.userId : null;
                    
                    while (usersList.lastChild && usersList.lastChild !== usersSection) {
                        usersList.removeChild(usersList.lastChild);
                    }
                    
                    data.conversations.sort((a, b) => {
                        const timeA = a.last_message_time ? new Date(a.last_message_time).getTime() : 0;
                        const timeB = b.last_message_time ? new Date(b.last_message_time).getTime() : 0;
                        return timeB - timeA;
                    });
                    
                    data.conversations.forEach(conv => {
                        const userItem = this.createUserItem(conv);
                        
                        if (conv.user_id === activeChatId) {
                            userItem.classList.add('active');
                        }
                        
                        usersList.appendChild(userItem);
                    });
                }
            })
            .catch(error => console.error('Error loading conversations:', error));
    },
    
    createUserItem: function(conv) {
        const userItem = document.createElement('div');
        userItem.className = 'user-item';
        userItem.dataset.userId = conv.user_id;
        
        const chatArea = document.createElement('div');
        chatArea.className = 'user-item-link';
        
        const userInfo = document.createElement('div');
        userInfo.className = 'user-info';
        
        const avatar = document.createElement('div');
        avatar.className = 'default-avatar';
        if (conv.avatar_url) {
            const img = document.createElement('img');
            img.src = conv.avatar_url;
            img.alt = conv.username;
            avatar.appendChild(img);
        } else {
            avatar.textContent = conv.username.charAt(0).toUpperCase();
        }
        userInfo.appendChild(avatar);
        
        const onlineIndicator = document.createElement('div');
        onlineIndicator.className = 'online-indicator';
        userInfo.appendChild(onlineIndicator);
        
        if (conv.unread_count > 0) {
            const unreadBadge = document.createElement('div');
            unreadBadge.className = 'unread-badge';
            unreadBadge.textContent = conv.unread_count;
            userInfo.appendChild(unreadBadge);
        }
        
        const userDetails = document.createElement('div');
        userDetails.className = 'user-details';
        
        const userHeader = document.createElement('div');
        userHeader.className = 'user-header';
        
        const userName = document.createElement('span');
        userName.className = 'user-name';
        userName.textContent = conv.first_name ? `${conv.first_name} ${conv.last_name}` : conv.username;
        
        const chatTime = document.createElement('div');
        chatTime.className = 'chat-time';
        chatTime.dataset.userId = conv.user_id;
        chatTime.textContent = conv.time_text || '';
        
        userHeader.appendChild(userName);
        userHeader.appendChild(chatTime);
        
        const chatPreview = document.createElement('div');
        chatPreview.className = 'chat-preview';
        
        const lastMessage = document.createElement('div');
        lastMessage.className = 'last-message';
        lastMessage.dataset.userId = conv.user_id;
        lastMessage.textContent = conv.last_message || '';
        
        chatPreview.appendChild(lastMessage);
        
        userDetails.appendChild(userHeader);
        userDetails.appendChild(chatPreview);
        
        chatArea.appendChild(userInfo);
        chatArea.appendChild(userDetails);
        
        const profileLink = document.createElement('a');
        profileLink.href = `profile.php?username=${conv.username}`;
        profileLink.className = 'view-profile-link';
        profileLink.title = 'عرض الملف الشخصي';
        profileLink.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>`;
        
        userItem.appendChild(chatArea);
        userItem.appendChild(profileLink);
        
        chatArea.addEventListener('click', function(event) {
            event.preventDefault();
            document.querySelectorAll('.user-item').forEach(i => i.classList.remove('active'));
            userItem.classList.add('active');
            this.showChatView(conv.user_id, userName.textContent, avatar.textContent.trim(), avatar.className);
        });
        
        profileLink.addEventListener('click', function(event) {
            event.stopPropagation();
        });
        
        return userItem;
    },
    
    showChatView: function(userId, userName, avatarInitial, avatarClass) {
        this.state.lastMessageId = null; 
        this.state.currentChatUserId = userId;
        
        if (this.elements.welcomeScreen) this.elements.welcomeScreen.style.display = 'none';
        if (this.elements.chatMainArea) this.elements.chatMainArea.classList.remove('no-chat');
        
        if (this.elements.chatHeader) this.elements.chatHeader.style.display = 'flex';
        if (this.elements.chatMessagesContainer) this.elements.chatMessagesContainer.style.display = 'flex';
        if (this.elements.chatInputArea) this.elements.chatInputArea.style.display = 'block';

        if (this.elements.chattingWithName) this.elements.chattingWithName.textContent = userName;
        if (this.elements.chattingWithAvatar) {
            this.elements.chattingWithAvatar.textContent = avatarInitial;
            let classes = ['default-avatar'];
            if (avatarClass && avatarClass !== 'default-avatar') {
                classes.push(avatarClass);
            }
            this.elements.chattingWithAvatar.className = classes.join(' ');
        }
        if (this.elements.chattingWithStatus) this.elements.chattingWithStatus.textContent = "متصل الآن";
        
        this.loadMessagesForUser(userId);
        this.startMessageUpdates();

        if (window.innerWidth <= 768 && this.elements.sidebar.classList.contains('show')) {
            this.elements.sidebar.classList.remove('show');
        }
    },
    
    loadMessagesForUser: function(userId, lastMessageId = null) {
        if (!userId || this.state.isUpdating) return;
        
        this.state.isUpdating = true;
        
        this.state.currentChatUserId = userId;
        
        if (!lastMessageId) {
            this.elements.chatMessagesContainer.innerHTML = '';
            this.state.messageIdsInDOM = new Set();
            this.state.lastMessageId = null;
        }
        
        let messageIdsInDOM = [];
        document.querySelectorAll('.message').forEach(el => {
            if (el.dataset.messageId) {
                messageIdsInDOM.push(el.dataset.messageId);
            }
        });
        
        console.log('Current messages in DOM:', messageIdsInDOM);
        
        fetch(`${this.config.apiEndpoint}?action=get_messages&user_id=${userId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`خطأ في الاستجابة: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.messages && data.messages.length > 0) {
                    const scrollPos = this.elements.chatMessagesContainer.scrollTop;
                    const isScrolledToBottom = this.elements.chatMessagesContainer.scrollHeight - this.elements.chatMessagesContainer.clientHeight <= this.elements.chatMessagesContainer.scrollTop + 50;
                    
                    data.messages.sort((a, b) => {
                        const dateA = new Date(a.created_at);
                        const dateB = new Date(b.created_at);
                        return dateA - dateB;
                    });
                    
                    console.log(`تم استلام ${data.messages.length} رسالة من المستخدم ${userId}`);
                    
                    if (!lastMessageId) {
                        this.elements.chatMessagesContainer.innerHTML = '';
                        messageIdsInDOM = [];
                    }
                    
                    data.messages.forEach(msg => {
                        if (messageIdsInDOM.includes(msg.id)) {
                            return;
                        }
                        
                        if (this.state.deletedMessageIds.includes(msg.id)) {
                            return;
                        }
                        
                        const isOwn = parseInt(msg.sender_id) === parseInt(this.state.currentUserId);
                        this.addMessageToUI(msg.content, isOwn, new Date(msg.created_at), msg.media_url, false, msg.id);
                        
                        messageIdsInDOM.push(msg.id);
                    });
                    
                    if (data.messages.length > 0) {
                        this.state.lastMessageId = Math.max(...data.messages.map(msg => parseInt(msg.id)));
                    }
                    
                    if (isScrolledToBottom) {
                        this.elements.chatMessagesContainer.scrollTop = this.elements.chatMessagesContainer.scrollHeight;
                    } else {
                        this.elements.chatMessagesContainer.scrollTop = scrollPos;
                    }
                    
                    this.startMessageUpdates();
                }
            })
            .catch(error => {
                console.error('Error loading messages:', error);
                this.startMessageUpdates();
                
                if (!this.state.lastMessageId) {
                    this.elements.chatMessagesContainer.innerHTML = '<div class="error-message">فشل تحميل الرسائل. حاول تحديث الصفحة.</div>';
                }
            });
    },
    
    startMessageUpdates: function() {
        if (this.state.messageUpdateInterval) {
            clearInterval(this.state.messageUpdateInterval);
        }
        this.state.messageUpdateInterval = setInterval(() => {
            if (this.state.currentChatUserId && !this.state.isUpdating) {
                const now = Date.now();
                if (!this.state.lastUserInteraction || (now - this.state.lastUserInteraction) > 2000) {
                    this.loadMessagesForUser(this.state.currentChatUserId);
                }
            }
        }, 10000);
    },
    
    stopMessageUpdates: function() {
        if (this.state.messageUpdateInterval) {
            clearInterval(this.state.messageUpdateInterval);
            this.state.messageUpdateInterval = null;
        }
    },
    
    addMessageToUI: function(text, isOwn, time, mediaUrl = null, addToTop = true, messageId = null) {
        if (!this.elements.chatMessagesContainer) return;
        
        const messageDiv = document.createElement('div');
        messageDiv.classList.add('message');
        if (isOwn) {
            messageDiv.classList.add('own');
        }

        const contentDiv = document.createElement('div');
        contentDiv.classList.add('message-content');
        
        if (text) {
            const sanitizedText = this.sanitizeText(text);
            
            const textDiv = document.createElement('div');
            
            textDiv.innerHTML = this.convertTextEmoji(sanitizedText);
            
            contentDiv.appendChild(textDiv);
        }

        if (mediaUrl) {
            const mediaContainer = document.createElement('div');
            mediaContainer.classList.add('message-media-container');
            
            const img = document.createElement('img');
            img.src = mediaUrl;
            img.alt = "صورة";
            img.classList.add('message-media');
            
            img.style.opacity = '0.5';
            const loader = document.createElement('div');
            loader.classList.add('media-loader');
            mediaContainer.appendChild(loader);
            
            img.onload = function() {
                img.style.opacity = '1';
                loader.remove();
            };
            
            img.onerror = function() {
                mediaContainer.innerHTML = '<div class="media-error">فشل تحميل الصورة</div>';
            };
            
            mediaContainer.appendChild(img);
            contentDiv.appendChild(mediaContainer);
            
            img.addEventListener('click', function() {
                if (this.elements.modalImage && this.elements.imageModal) {
                    this.elements.modalImage.src = this.src;
                    this.elements.imageModal.style.display = 'flex';
                }
            });
        }

        const timeDiv = document.createElement('div');
        timeDiv.classList.add('message-time');
        const timeSpan = document.createElement('span');
        timeSpan.textContent = typeof time === 'string' ? time : this.formatMessageTime(time);
        timeDiv.appendChild(timeSpan);

        if (isOwn) {
            const deleteBtn = document.createElement('button');
            deleteBtn.classList.add('delete-message');
            deleteBtn.title = 'حذف الرسالة';
            deleteBtn.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    <line x1="10" y1="11" x2="10" y2="17"></line>
                    <line x1="14" y1="11" x2="14" y2="17"></line>
                </svg>`;
            deleteBtn.onclick = function() { this.deleteMessage(this); };
            timeDiv.appendChild(deleteBtn);
        }

        messageDiv.appendChild(contentDiv);
        messageDiv.appendChild(timeDiv);
        
        if (addToTop) {
            this.elements.chatMessagesContainer.insertBefore(messageDiv, this.elements.chatMessagesContainer.firstChild);
        } else {
            this.elements.chatMessagesContainer.appendChild(messageDiv);
        }

        if (messageId) {
            messageDiv.dataset.messageId = messageId;
        }
    },
    
    formatMessageTime: function(date) {
        const now = new Date();
        const diff = now - date;
        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
        
        if (days > 7) {
            return date.toLocaleDateString('ar-SA');
        } else if (days > 0) {
            return days + ' يوم' + (days > 2 ? ' مضت' : ' مضى');
        } else {
            return date.toLocaleTimeString('ar-SA', { hour: '2-digit', minute: '2-digit' });
        }
    },
    
    deleteMessage: function(buttonElement) {
        if (confirm('هل أنت متأكد من حذف هذه الرسالة؟')) {
            const messageElement = buttonElement.closest('.message');
            if (messageElement) {
                const messageId = messageElement.dataset.messageId;
                
                if (!messageId) {
                    console.error('Message ID not found');
                    return;
                }
                
                messageElement.style.opacity = '0.5';
                messageElement.style.pointerEvents = 'none';
                
                fetch(`${this.config.apiEndpoint}?action=delete_message`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ message_id: messageId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        messageElement.remove();
                        
                        this.state.deletedMessageIds.add(messageId);
                        
                        const displayIndex = Array.from(this.elements.chatMessagesContainer.children).findIndex(el => el.dataset.messageId === messageId);
                        if (displayIndex !== -1) {
                            this.elements.chatMessagesContainer.children[displayIndex].remove();
                        }
                        
                        console.log('Message deleted successfully, ID:', messageId);
                    } else {
                        messageElement.style.opacity = '1';
                        messageElement.style.pointerEvents = 'auto';
                        console.error('Failed to delete message:', data.message);
                        alert('فشل حذف الرسالة: ' + data.message);
                    }
                })
                .catch(error => {
                    messageElement.style.opacity = '1';
                    messageElement.style.pointerEvents = 'auto';
                    console.error('Error deleting message:', error);
                    alert('حدث خطأ أثناء حذف الرسالة');
                });
            }
        }
    },
    
    closeChat: function() {
        this.stopMessageUpdates(); 
        if (this.elements.welcomeScreen) this.elements.welcomeScreen.style.display = 'flex';
        if (this.elements.chatMainArea) this.elements.chatMainArea.classList.add('no-chat');
        if (this.elements.chatHeader) this.elements.chatHeader.style.display = 'none';
        if (this.elements.chatMessagesContainer) this.elements.chatMessagesContainer.style.display = 'none';
        if (this.elements.chatInputArea) this.elements.chatInputArea.style.display = 'none';
        document.querySelectorAll('.user-item').forEach(item => {
            item.classList.remove('active');
        });
    },
    
    formatFileSize: function(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    },
    
    sanitizeText: function(text) {
        if (!text) return '';
        const tempDiv = document.createElement('div');
        tempDiv.textContent = text;
        return tempDiv.textContent;
    },
    
    convertTextEmoji: function(text) {
        if (!text) return '';
        
        if (typeof emoji !== 'undefined' && emoji.convert) {
            return emoji.convert(text);
        }
        
        return text;
    },
    
    escapeRegExp: function(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    },
    
    initEmojiPicker: function() {
        if (typeof EmojiPicker === 'undefined') {
            console.warn('EmojiPicker library not loaded');
            return;
        }
        
        const emojiButton = document.getElementById('emojiBtn');
        if (!emojiButton) {
            console.warn('Emoji button not found');
            return;
        }
        
        const messageInput = document.getElementById('messageInput');
        if (!messageInput) {
            console.warn('Message input not found');
            return;
        }
        
        this.emojiPicker = new EmojiPicker({
            onSelect: (emoji) => {
                const cursorPos = messageInput.selectionStart;
                const textBeforeCursor = messageInput.value.substring(0, cursorPos);
                const textAfterCursor = messageInput.value.substring(cursorPos);
                
                messageInput.value = textBeforeCursor + emoji + textAfterCursor;
                
                const newCursorPos = cursorPos + emoji.length;
                messageInput.setSelectionRange(newCursorPos, newCursorPos);
                messageInput.focus();
            }
        });
        
        emojiButton.addEventListener('click', (e) => {
            e.preventDefault();
            this.emojiPicker.toggle();
        });
    },
    
    sendMessage: function(text, mediaUrl = null) {
        if (!this.state.currentChatUserId || (!text && !mediaUrl)) return;

        const tempId = 'temp_' + Date.now();
        
        this.addMessageToUI(text, true, new Date(), mediaUrl, false, tempId);
        
        this.state.messageIdsInDOM.add(tempId);
        
        const messageData = {
            receiver_id: this.state.currentChatUserId,
            content: text,
            media_url: mediaUrl
        };

        fetch(`${this.config.apiEndpoint}?action=send_message`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(messageData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.message && data.message.id) {
                    const tempElement = document.querySelector(`.message[data-message-id="${tempId}"]`);
                    if (tempElement) {
                        tempElement.dataset.messageId = data.message.id;
                        
                        this.state.messageIdsInDOM.add(data.message.id);
                    }
                }
                
                const usersList = document.querySelector('.users-list');
                const currentChat = document.querySelector(`.user-item[data-user-id="${this.state.currentChatUserId}"]`);
                const usersSection = usersList.querySelector('.users-section');
                
                if (currentChat && usersSection) {
                    usersList.insertBefore(currentChat, usersSection.nextSibling);
                }
                
                const lastMessageElement = currentChat.querySelector('.last-message');
                if (lastMessageElement) {
                    if (mediaUrl) {
                        lastMessageElement.innerHTML = '&#128247; صورة';
                    } else {
                        const sanitizedText = this.sanitizeText(text);
                        lastMessageElement.textContent = sanitizedText.length > 30 ? sanitizedText.substring(0, 30) + '...' : sanitizedText;
                    }
                }
                
                const timeElement = currentChat.querySelector('.chat-time');
                if (timeElement) {
                    timeElement.textContent = 'الآن';
                }
                
                this.loadConversations();
            } else {
                console.error('Failed to send message:', data.message);
                alert('فشل إرسال الرسالة. حاول مرة أخرى.');
            }
        })
        .catch(error => {
            console.error('Error sending message:', error);
            alert('حدث خطأ أثناء إرسال الرسالة. حاول مرة أخرى.');
        });
    },
    
    addStyle: function() {
        const style = document.createElement('style');
        style.textContent = `
        .file-preview-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px;
            background: var(--bg-card);
            border-radius: 8px;
            margin-bottom: 8px;
        }

        .preview-image {
            position: relative;
            width: 60px;
            height: 60px;
            border-radius: 8px;
            overflow: hidden;
            background: rgba(0,0,0,0.1);
        }

        .preview-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .upload-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0,0,0,0.7);
            color: white;
            font-size: 11px;
            padding: 2px 6px;
            text-align: center;
        }

        .preview-info {
            flex: 1;
            min-width: 0;
        }

        .preview-name {
            color: var(--text-primary);
            font-size: 14px;
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .preview-size {
            color: var(--text-secondary);
            font-size: 12px;
        }

        .cancel-upload {
            background: none;
            border: none;
            color: var(--text-secondary);
            padding: 6px;
            cursor: pointer;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .cancel-upload:hover {
            background: var(--danger-hover-bg);
            color: var(--danger-color);
        }

        .cancel-upload svg {
            width: 18px;
            height: 18px;
            stroke: currentColor;
        }

        .upload-error .upload-progress {
            background-color: var(--danger-color) !important;
            color: white;
            padding: 4px 8px;
        }

        .file-preview-item {
            position: relative;
            margin-bottom: 10px;
        }

        .preview-image {
            position: relative;
            width: 100%;
            max-width: 300px;
            margin: 0 auto;
        }

        .preview-image img {
            width: 100%;
            height: auto;
            border-radius: 8px;
        }

        .upload-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 4px 8px;
            font-size: 12px;
            text-align: center;
            border-bottom-left-radius: 8px;
            border-bottom-right-radius: 8px;
        }

        .message-media-container {
            position: relative;
            margin-top: 8px;
            border-radius: 8px;
            overflow: hidden;
            background: rgba(0,0,0,0.1);
            max-width: 300px;
        }

        .message-media {
            width: 100%;
            max-height: 300px;
            display: block;
            object-fit: contain;
            transition: opacity 0.3s ease;
        }

        .media-loader {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 30px;
            height: 30px;
            border: 3px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s linear infinite;
        }

        .media-error {
            padding: 20px;
            text-align: center;
            color: var(--danger-color);
        }

        @keyframes spin {
            to { transform: translate(-50%, -50%) rotate(360deg); }
        }
        `;
        document.head.appendChild(style);
    },
    
    addModalCloseHandlers: function() {
        if (this.elements.imageModalCloseBtn) {
            this.elements.imageModalCloseBtn.addEventListener('click', this.closeImageModal.bind(this));
        }

        if (this.elements.imageModal) {
            this.elements.imageModal.addEventListener('click', function(e) {
                if (e.target === this.elements.imageModal) {
                    this.closeImageModal();
                }
            });

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && this.elements.imageModal.style.display === 'flex') {
                    this.closeImageModal();
                }
            });
        }
    },
    
    closeImageModal: function() {
        if (this.elements.imageModal) {
            this.elements.imageModal.style.display = 'none';
            if (this.elements.modalImage) {
                this.elements.modalImage.src = '';
            }
        }
    },
    
    addModalStyle: function() {
        const modalStyle = document.createElement('style');
        modalStyle.textContent = `
        .image-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .modal-content {
            position: relative;
            max-width: 90%;
            max-height: 90vh;
            background: var(--bg-card);
            border-radius: 12px;
            overflow: hidden;
        }

        .modal-header {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            padding: 10px;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            color: white;
            font-size: 16px;
            margin: 0;
        }

        .modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 5px;
            line-height: 1;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.2s;
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .modal-image {
            display: block;
            max-width: 100%;
            max-height: 90vh;
            object-fit: contain;
        }
        `;
        document.head.appendChild(modalStyle);
    },
    
    addModalHTML: function() {
        if (!document.getElementById('imageModal')) {
            const modalHTML = `
                <div id="imageModal" class="image-modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3 class="modal-title">عرض الصورة</h3>
                            <button id="imageModalCloseBtn" class="modal-close" title="إغلاق">×</button>
                        </div>
                        <img id="modalImage" class="modal-image" alt="صورة مكبرة" />
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modalHTML);
        }
    },
    
    handleUserItemClick: function(item) {
        const userId = item.dataset.userId;
        if (!userId) return;

        document.querySelectorAll('.user-item').forEach(i => i.classList.remove('active'));
        item.classList.add('active');
        
        this.state.currentChatUserId = userId;
        
        this.showChatView(userId, item.querySelector('.user-name').textContent, item.querySelector('.default-avatar').textContent.trim(), item.querySelector('.default-avatar').className);
        
        this.loadMessagesForUser(userId);
    },
    
    periodicUpdate: function() {
        if (this.state.currentChatUserId && !this.state.isUpdating) {
            this.loadMessagesForUser(this.state.currentChatUserId);
        }
    }
};