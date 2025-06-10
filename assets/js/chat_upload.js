
let isFileDialogOpen = false;

const ChatImageUpload = {
    init: function() {
        this.setupEventListeners();
    },
    
    setupEventListeners: function() {
        const fileInput = document.getElementById('fileInput');
        const attachFileBtn = document.getElementById('attachFileBtn');
        const filePreviewContainer = document.getElementById('filePreviewContainer');
        const messageInput = document.getElementById('messageInput');
        const sendMessageBtn = document.getElementById('sendMessageBtn');
        
        if (attachFileBtn && fileInput) {
            attachFileBtn.onclick = function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                if (!isFileDialogOpen) {
                    isFileDialogOpen = true;
                    setTimeout(function() {
                        fileInput.click();
                    }, 100);
                }
                
                return false;
            };
            
            fileInput.onchange = function(e) {
                isFileDialogOpen = false;
                if (this.files.length > 0) {
                    ChatImageUpload.handleFileSelect(this.files);
                }
            };
            
            if (sendMessageBtn && messageInput) {
                const originalClickHandler = sendMessageBtn.onclick;
                
                sendMessageBtn.onclick = function(e) {
                    const messageText = messageInput.value.trim();
                    
                    if (fileInput.files && fileInput.files.length > 0) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        if (window.currentChatUserId) {
                            ChatImageUpload.sendMessageWithImage(window.currentChatUserId, messageText);
                        }
                        
                        return false;
                    } else if (originalClickHandler) {
                        return originalClickHandler.call(this, e);
                    }
                };
            }
        }
        
        document.addEventListener('click', function() {
            setTimeout(function() {
                isFileDialogOpen = false;
            }, 500);
        });
    }
        
        const chatMessagesContainer = document.getElementById('chatMessages');
        if (chatMessagesContainer) {
            chatMessagesContainer.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.add('drag-over');
            });
            
            chatMessagesContainer.addEventListener('dragleave', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.remove('drag-over');
            });
            
            chatMessagesContainer.addEventListener('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.remove('drag-over');
                
                if (e.dataTransfer.files.length > 0) {
                    ChatImageUpload.handleFileSelect(e.dataTransfer.files);
                }
            });
        }
    },
    
    handleFileSelect: function(files) {
        const filePreviewContainer = document.getElementById('filePreviewContainer');
        
        if (!filePreviewContainer) return;
        
        filePreviewContainer.innerHTML = '';
        filePreviewContainer.style.display = 'block';
        
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            
            if (!file.type.match('image.*')) {
                alert('يرجى اختيار صورة فقط');
                continue;
            }
            
            if (file.size > 5 * 1024 * 1024) {
                alert('حجم الملف كبير جدًا. الحد الأقصى هو 5 ميجابايت');
                continue;
            }
            
            const reader = new FileReader();
            
            const previewItem = document.createElement('div');
            previewItem.className = 'file-preview-item';
            
            reader.onload = function(e) {
                previewItem.innerHTML = `
                    <div class="relative inline-block m-1">
                        <img src="${e.target.result}" alt="معاينة" class="w-16 h-16 object-cover rounded-md border border-[var(--border-color)]">
                        <button type="button" class="remove-file-btn absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center">
                            <i class="bi bi-x-lg text-[0.6rem]"></i>
                        </button>
                    </div>
                `;
                
                const removeBtn = previewItem.querySelector('.remove-file-btn');
                if (removeBtn) {
                    removeBtn.addEventListener('click', function() {
                        previewItem.remove();
                        if (filePreviewContainer.children.length === 0) {
                            filePreviewContainer.style.display = 'none';
                        }
                    });
                }
                
                filePreviewContainer.appendChild(previewItem);
            };
            
            reader.readAsDataURL(file);
        }
    },
    
    uploadImage: function(file, callback) {
        const formData = new FormData();
        formData.append('image', file);
        
        const chatMessagesContainer = document.getElementById('chatMessages');
        const loadingIndicator = document.createElement('div');
        loadingIndicator.className = 'message flex justify-end items-end';
        loadingIndicator.innerHTML = `
            <div class="message-bubble own">
                <div class="flex items-center">
                    <div class="animate-spin rounded-full h-4 w-4 border-t-2 border-b-2 border-[var(--active-indicator)] mr-2"></div>
                    <span>جاري رفع الصورة...</span>
                </div>
            </div>
        `;
        
        if (chatMessagesContainer) {
            chatMessagesContainer.appendChild(loadingIndicator);
            chatMessagesContainer.scrollTop = chatMessagesContainer.scrollHeight;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            if (loadingIndicator && loadingIndicator.parentNode) {
                loadingIndicator.parentNode.removeChild(loadingIndicator);
            }
            callback(null, e.target.result);
        };
        reader.onerror = function() {
            if (loadingIndicator && loadingIndicator.parentNode) {
                loadingIndicator.parentNode.removeChild(loadingIndicator);
            }
            callback('فشل قراءة الملف');
        };
        reader.readAsDataURL(file);
    },
    
    sendMessageWithImage: function(receiverId, messageText) {
        const fileInput = document.getElementById('fileInput');
        const filePreviewContainer = document.getElementById('filePreviewContainer');
        
        if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
            this.sendTextMessage(receiverId, messageText);
            return;
        }
        
        const file = fileInput.files[0];
        
        const loadingIndicator = document.createElement('div');
        loadingIndicator.className = 'loading-indicator flex justify-center items-center p-2 mb-2';
        loadingIndicator.innerHTML = `
            <div class="animate-spin rounded-full h-4 w-4 border-t-2 border-b-2 border-[var(--active-indicator)]"></div>
            <span class="mr-2 text-xs text-[var(--text-secondary)]">جاري رفع الصورة...</span>
        `;
        
        const chatMessagesContainer = document.getElementById('chatMessages');
        if (chatMessagesContainer) {
            chatMessagesContainer.appendChild(loadingIndicator);
            chatMessagesContainer.scrollTop = chatMessagesContainer.scrollHeight;
        }
        
        this.uploadImage(file, (error, imageUrl) => {
            if (loadingIndicator && loadingIndicator.parentNode) {
                loadingIndicator.parentNode.removeChild(loadingIndicator);
            }
            
            if (error) {
                alert(error);
                return;
            }
            
            fetch('api/chat.php?action=send_message', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    receiver_id: receiverId,
                    content: messageText,
                    image_url: imageUrl,
                    is_mine: 1 
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (typeof loadMessages === 'function' && window.currentChatUserId) {
                        loadMessages(window.currentChatUserId);
                    }
                    
                    const messageInput = document.getElementById('messageInput');
                    if (messageInput) messageInput.value = '';
                    
                    if (filePreviewContainer) {
                        filePreviewContainer.innerHTML = '';
                        filePreviewContainer.style.display = 'none';
                    }
                    
                    if (fileInput) fileInput.value = '';
                } else {
                    alert(data.message || 'حدث خطأ أثناء إرسال الرسالة');
                }
            })
            .catch(error => {
                alert('حدث خطأ في الاتصال: ' + error.message);
            });
        });
    },
    
    sendTextMessage: function(receiverId, messageText) {
        fetch('api/chat.php?action=send_message', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                receiver_id: receiverId,
                content: messageText,
                is_mine: 1 
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (typeof loadMessages === 'function' && window.currentChatUserId) {
                    loadMessages(window.currentChatUserId);
                }
                
                const messageInput = document.getElementById('messageInput');
                if (messageInput) messageInput.value = '';
            } else {
                alert(data.message || 'حدث خطأ أثناء إرسال الرسالة');
            }
        })
        .catch(error => {
            alert('حدث خطأ في الاتصال: ' + error.message);
        });
    },
    
    addMessageToUI: function(text, isOwn, timestamp, imageUrl = null) {
        const chatMessagesContainer = document.getElementById('chatMessages');
        if (!chatMessagesContainer) return;
        
        const messageTime = this.formatTime(timestamp);
        const messageElement = document.createElement('div');
        messageElement.className = `message flex ${isOwn ? 'justify-end' : 'justify-start'} items-end group`;
        
        let mediaHtml = '';
        if (imageUrl) {
            mediaHtml = `
                <div class="message-media-container mt-2">
                    <img src="${imageUrl}" alt="صورة" class="message-media max-w-[200px] max-h-[200px] rounded-md cursor-pointer" onclick="showImageModal('${imageUrl}')">
                </div>
            `;
        }
        
        const deleteButtonHtml = `
            <button class="delete-message-btn ${isOwn ? 'mr-1' : 'ml-1 order-first'} mb-0.5 text-[var(--text-muted)]" onclick="deleteMessage(this)" title="حذف الرسالة">
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
        chatMessagesContainer.scrollTop = chatMessagesContainer.scrollHeight;
    },
    
    formatTime: function(timestamp) {
        if (!timestamp) return 'الآن';
        
        const date = new Date(timestamp);
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
            return date.toLocaleString('ar-SA');
        }
    }
};

function showImageModal(imageUrl) {
    const imageModal = document.getElementById('imageModal');
    const modalImage = document.getElementById('modalImage');
    
    if (imageModal && modalImage) {
        modalImage.src = imageUrl;
        imageModal.style.display = 'flex';
    }
}

function deleteMessage(buttonElement) {
    const messageElement = buttonElement.closest('.message');
    if (messageElement) {
        messageElement.remove();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    ChatImageUpload.init();
    
    const sendMessageBtn = document.getElementById('sendMessageBtn');
    const messageInput = document.getElementById('messageInput');
    
    if (sendMessageBtn && messageInput) {
        sendMessageBtn.addEventListener('click', function() {
            const messageText = messageInput.value.trim();
            const currentChatUserId = document.querySelector('.user-item-chat.active')?.dataset.userId;
            
            if (currentChatUserId && (messageText || document.getElementById('fileInput').files.length > 0)) {
                ChatImageUpload.sendMessageWithImage(currentChatUserId, messageText);
            }
        });
        
        messageInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessageBtn.click();
            }
        });
    }
});
