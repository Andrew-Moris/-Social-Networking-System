

document.addEventListener('DOMContentLoaded', function() {
    loadUserPosts();
    
    setInterval(loadUserPosts, 60000);
    
    const postForm = document.getElementById('postForm');
    if (postForm) {
        postForm.addEventListener('submit', handlePostSubmit);
    }
});


function loadUserPosts() {
    const postsContainer = document.getElementById('userPostsContainer');
    const noPostsMessage = document.getElementById('noPostsMessage');
    
    if (!postsContainer) return;
    
    const userId = document.body.getAttribute('data-user-id') || 
                  document.querySelector('meta[name="user-id"]')?.getAttribute('content');
    
    if (!userId) {
        console.error('User ID not found');
        return;
    }
    
    postsContainer.innerHTML = `
        <div class="loading-indicator">
            <div class="spinner"></div>
            <p>جاري تحميل المنشورات...</p>
        </div>`;
    
    fetch(`api/profile_posts.php?action=get_posts&user_id=${userId}`)
        .then(response => response.json())
        .then(data => {
            console.log('Posts data:', data); 
            
            if (data.success && data.posts && data.posts.length > 0) {
                if (noPostsMessage) noPostsMessage.style.display = 'none';
                
                postsContainer.innerHTML = '';
                data.posts.forEach(post => {
                    postsContainer.appendChild(createPostElement(post));
                });
            } else {
                postsContainer.innerHTML = '';
                if (noPostsMessage) noPostsMessage.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error loading posts:', error);
            postsContainer.innerHTML = `
                <div class="error-message">
                    <i class="bi bi-exclamation-triangle"></i>
                    <p>حدث خطأ أثناء تحميل المنشورات. يرجى المحاولة مرة أخرى.</p>
                </div>`;
        });
}

/**
 * @param {Object} post 
 * @returns {HTMLElement} 
 */
function createPostElement(post) {
    const postElement = document.createElement('div');
    postElement.className = 'post-card';
    postElement.setAttribute('data-post-id', post.id);
    
    const postDate = new Date(post.created_at);
    const formattedDate = formatTimeAgo(post.created_at);
    
    const isCurrentUserPost = post.user_id === parseInt(document.body.getAttribute('data-user-id'));
    
    let postContent = '';
    
    const userInitial = post.username ? post.username.charAt(0).toUpperCase() : 'U';
    const defaultAvatar = `https://ui-avatars.com/api/?name=${userInitial}&background=667eea&color=fff&size=100`;
    
    let avatarUrl = defaultAvatar;
    if (post.user_avatar && post.user_avatar.trim() !== '') {
        avatarUrl = post.user_avatar;
    }
    
    postContent += `
        <div class="post-header">
            <div class="post-user-info">
                <img src="${avatarUrl}" alt="${post.username}" class="post-avatar" onerror="this.onerror=null; this.src='${defaultAvatar}';">
                <div>
                    <h4 class="post-username">${post.username}</h4>
                    <span class="post-time" title="${postDate.toLocaleString()}">${formattedDate}</span>
                </div>
            </div>
            ${isCurrentUserPost ? `
                <div class="post-actions">
                    <button class="action-button" onclick="editPost(${post.id})">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="action-button" onclick="deletePost(${post.id})">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            ` : ''}
        </div>`;
    
    postContent += `<div class="post-content">`;
    
    if (post.content) {
        postContent += `<p>${post.content.replace(/\n/g, '<br>')}</p>`;
    }
    
    if (post.image_url || post.media_url) {
        let mediaUrl = post.media_url || post.image_url;
        console.log('Original Media URL:', mediaUrl); 
        
        if (mediaUrl) {
            if (mediaUrl.startsWith('uploads/')) {
                mediaUrl = '/WEP/' + mediaUrl;
            } 
            else if (!mediaUrl.startsWith('/') && !mediaUrl.startsWith('http')) {
                mediaUrl = '/WEP/' + mediaUrl;
            }
        } else {
            console.log('No media URL found');
            postContent += `</div>`;
            return;
        }
        
        console.log('Fixed Media URL:', mediaUrl);
        
        const isVideo = mediaUrl.match(/\.(mp4|webm|ogg|mov)$/i);
        
        if (isVideo) {
            postContent += `
                <div class="media-container">
                    <video src="${mediaUrl}" controls class="post-media" 
                           onerror="this.onerror=null; this.innerHTML='<div class=\'error-message\'><i class=\'bi bi-exclamation-triangle\'></i><p>لا يمكن تحميل الفيديو</p></div>';"></video>
                </div>`;
        } else {
            postContent += `
                <div class="media-container">
                    <img src="${mediaUrl}" alt="صورة المنشور" class="post-media" 
                         onclick="showImageModal('${mediaUrl}')" 
                         onerror="this.onerror=null; this.src='assets/images/image-placeholder.svg'; this.classList.add('error-image');">
                </div>`;
        }
    }
    
    postContent += `</div>`;
    
    postContent += `
        <div class="post-interactions">
            <button class="interaction-button ${post.liked ? 'active' : ''}" onclick="likePost(${post.id})">
                <i class="bi bi-heart${post.liked ? '-fill' : ''}"></i>
                <span>${post.likes_count || 0}</span>
            </button>
            <button class="interaction-button" onclick="showComments(${post.id})">
                <i class="bi bi-chat"></i>
                <span>${post.comments_count || 0}</span>
            </button>
            <button class="interaction-button" onclick="sharePost(${post.id})">
                <i class="bi bi-share"></i>
            </button>
            <button class="interaction-button ${post.bookmarked ? 'active' : ''}" onclick="bookmarkPost(${post.id})">
                <i class="bi bi-bookmark${post.bookmarked ? '-fill' : ''}"></i>
            </button>
        </div>`;
    
    postElement.innerHTML = postContent;
    return postElement;
}

/**
 * @param {Event} event 
 */
function handlePostSubmit(event) {
    event.preventDefault();
    
    const form = event.target;
    const contentElement = form.querySelector('#content, #postContent');
    const content = contentElement ? contentElement.value.trim() : '';
    const mediaInput = form.querySelector('#media');
    const mediaPreview = document.getElementById('mediaPreview');
    
    if (!content && (!mediaInput || !mediaInput.files.length)) {
        showUIMessage('يرجى إدخال نص أو إرفاق صورة/فيديو للمنشور', 'error');
        return;
    }
    
    const formData = new FormData(form);
    formData.append('action', 'create_post');
    
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="bi bi-arrow-repeat animate-spin"></i> جار النشر...';
    
    fetch('api/profile_posts.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            form.reset();
            if (mediaPreview) {
                mediaPreview.innerHTML = '';
                mediaPreview.classList.add('hidden');
            }
            
            if (data.post) {
                const postsContainer = document.getElementById('userPostsContainer');
                const noPostsMessage = document.getElementById('noPostsMessage');
                
                if (postsContainer) {
                    if (noPostsMessage) {
                        noPostsMessage.style.display = 'none';
                    }
                    
                    const newPost = createPostElement(data.post);
                    
                    newPost.style.opacity = '0';
                    newPost.style.transform = 'translateY(-20px)';
                    
                    if (postsContainer.firstChild) {
                        postsContainer.insertBefore(newPost, postsContainer.firstChild);
                    } else {
                        postsContainer.appendChild(newPost);
                    }
                    
                    setTimeout(() => {
                        newPost.style.transition = 'all 0.5s ease';
                        newPost.style.opacity = '1';
                        newPost.style.transform = 'translateY(0)';
                    }, 10);
                    
                    const statsElement = document.querySelector('.stat-number');
                    if (statsElement) {
                        const currentCount = parseInt(statsElement.textContent.replace(/,/g, '')) || 0;
                        statsElement.textContent = (currentCount + 1).toLocaleString();
                    }
                } else {
                    loadUserPosts();
                }
            } else {
                loadUserPosts();
            }
            
            showUIMessage('تم نشر المنشور بنجاح', 'success');
        } else {
            throw new Error(data.message || 'فشل في نشر المنشور');
        }
    })
    .catch(error => {
        console.error('Error creating post:', error);
        showUIMessage(error.message || 'حدث خطأ أثناء نشر المنشور', 'error');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
    });
}

/**
 * @param {string} datetime 
 * @returns {string} 
 */
function formatTimeAgo(datetime) {
    const now = new Date();
    const date = new Date(datetime);
    const seconds = Math.floor((now - date) / 1000);
    
    if (seconds < 60) return 'منذ لحظات';
    
    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) return `منذ ${minutes} دقيقة`;
    
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `منذ ${hours} ساعة`;
    
    const days = Math.floor(hours / 24);
    if (days < 30) return `منذ ${days} يوم`;
    
    const months = Math.floor(days / 30);
    if (months < 12) return `منذ ${months} شهر`;
    
    const years = Math.floor(months / 12);
    return `منذ ${years} سنة`;
}

/**
 * @param {string} message 
 * @param {string} type 
 */
function showUIMessage(message, type = 'success') {
    if (window.showUIMessage && window.showUIMessage !== showUIMessage) {
        window.showUIMessage(message, type);
        return;
    }
    
    const messageContainerId = 'uiMessageContainer';
    let messageContainer = document.getElementById(messageContainerId);
    
    if (!messageContainer) {
        messageContainer = document.createElement('div');
        messageContainer.id = messageContainerId;
        Object.assign(messageContainer.style, {
            position: 'fixed',
            top: '20px',
            left: '50%',
            transform: 'translateX(-50%)',
            padding: '12px 25px',
            borderRadius: '8px',
            zIndex: '2000',
            boxShadow: '0 5px 20px rgba(0,0,0,0.25)',
            transition: 'opacity 0.5s ease, top 0.5s ease',
            opacity: '0',
            textAlign: 'center',
            minWidth: '280px',
            maxWidth: '90%'
        });
        document.body.appendChild(messageContainer);
    }
    
    messageContainer.textContent = message;
    
    const colors = {
        success: 'linear-gradient(135deg, #4ade80 0%, #22c55e 100%)',
        error: 'linear-gradient(135deg, #f87171 0%, #ef4444 100%)',
        info: 'linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%)'
    };
    
    messageContainer.style.background = colors[type] || colors.info;
    messageContainer.style.color = 'white';
    
    messageContainer.style.display = 'block';
    setTimeout(() => {
        messageContainer.style.opacity = '1';
        messageContainer.style.top = '30px';
    }, 10);
    
    setTimeout(() => {
        messageContainer.style.opacity = '0';
        messageContainer.style.top = '0px';
        setTimeout(() => {
            messageContainer.style.display = 'none';
        }, 500);
    }, 3500);
}

/**
 * @param {string} imageUrl 
 */
function showImageModal(imageUrl) {
    let imageModal = document.getElementById('imageModal');
    
    if (!imageModal) {
        imageModal = document.createElement('div');
        imageModal.id = 'imageModal';
        imageModal.className = 'image-modal';
        imageModal.innerHTML = `
            <div class="image-modal-content">
                <span class="image-modal-close">&times;</span>
                <img id="modalImage" src="" alt="صورة كبيرة">
            </div>`;
        document.body.appendChild(imageModal);
        
        const closeBtn = imageModal.querySelector('.image-modal-close');
        closeBtn.addEventListener('click', () => {
            imageModal.style.display = 'none';
        });
        
        imageModal.addEventListener('click', (event) => {
            if (event.target === imageModal) {
                imageModal.style.display = 'none';
            }
        });
        
        const style = document.createElement('style');
        style.textContent = `
            .image-modal {
                display: none;
                position: fixed;
                z-index: 9999;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.9);
                overflow: auto;
            }
            .image-modal-content {
                position: relative;
                margin: auto;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100%;
                max-width: 90%;
            }
            .image-modal-close {
                position: absolute;
                top: 20px;
                right: 20px;
                color: white;
                font-size: 40px;
                font-weight: bold;
                cursor: pointer;
                z-index: 10000;
            }
            #modalImage {
                max-width: 90%;
                max-height: 90%;
                object-fit: contain;
            }`;
        document.head.appendChild(style);
    }
    
    const modalImage = document.getElementById('modalImage');
    modalImage.src = imageUrl;
    imageModal.style.display = 'block';
}
