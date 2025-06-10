
document.addEventListener('DOMContentLoaded', function() {
    window.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    initNavigation();
    
    initScrollHeader();
    
    initProfileEdit();
    
    initPostInteractions();

    const avatarUpload = document.getElementById('avatar-upload');
    if (avatarUpload) {
        avatarUpload.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;

            if (!file.type.startsWith('image/')) {
                showToast('يرجى اختيار ملف صورة صالح', 3000, 'error');
                return;
            }

            if (file.size > 2 * 1024 * 1024) { 
                showToast('حجم الصورة كبير جداً. الحد الأقصى 2 ميجابايت', 3000, 'error');
                return;
            }

            const formData = new FormData();
            formData.append('avatar', file);

            showToast('جاري رفع الصورة...', 3000);

            fetch('api/upload_avatar.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const avatarImages = document.querySelectorAll('.profile-avatar');
                    avatarImages.forEach(img => {
                        img.src = data.avatar_url + '?t=' + new Date().getTime();
                    });
                    showToast('تم تحديث الصورة الشخصية بنجاح', 3000, 'success');
                } else {
                    showToast(data.message || 'حدث خطأ أثناء رفع الصورة', 3000, 'error');
                }
            })
            .catch(error => {
                console.error('Error uploading avatar:', error);
                showToast('حدث خطأ أثناء رفع الصورة', 3000, 'error');
            });
        });
    }

    const coverUpload = document.getElementById('cover-upload');
    if (coverUpload) {
        coverUpload.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;

            if (!file.type.startsWith('image/')) {
                showToast('يرجى اختيار ملف صورة صالح', 3000, 'error');
                return;
            }

            if (file.size > 10 * 1024 * 1024) {
                showToast('حجم الصورة كبير جداً. الحد الأقصى 10 ميجابايت', 3000, 'error');
                return;
            }

            const formData = new FormData();
            formData.append('cover', file);

            fetch('api/upload_cover.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.querySelector('.profile-cover').style.backgroundImage = `url('${data.cover_url}')`;
                    showToast(data.message, 3000, 'success');
                } else {
                    showToast(data.message, 3000, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('حدث خطأ أثناء رفع الصورة', 3000, 'error');
            });
        });
    }
});


function initNavigation() {
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (this.getAttribute('id') === 'logout-button') {
                return; 
            }
            
            e.preventDefault();
            const targetId = this.getAttribute('data-target');
            
            document.querySelectorAll('.content-section').forEach(section => {
                section.classList.remove('active');
            });
            
            const targetSection = document.getElementById(targetId);
            if (targetSection) {
                targetSection.classList.add('active');
            }
            
            navLinks.forEach(navLink => {
                navLink.classList.remove('active');
            });
            this.classList.add('active');
        });
    });
    
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    if (mobileMenuButton) {
        mobileMenuButton.addEventListener('click', function() {
            document.querySelector('.vertical-nav').classList.toggle('mobile-nav-open');
        });
    }
}


function initScrollHeader() {
    window.addEventListener('scroll', function() {
        const header = document.querySelector('.nav-header');
        if (window.scrollY > 10) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    });
}


function initProfileEdit() {
    const editProfileButton = document.getElementById('edit-profile-button');
    const cancelEditButton = document.getElementById('cancel-edit-button');
    const profileDisplayContainer = document.getElementById('profile-display-container');
    const editProfileFormContainer = document.getElementById('edit-profile-form-container');
    const profileForm = document.getElementById('profile-edit-form');
    
    if (editProfileButton && cancelEditButton) {
        editProfileButton.addEventListener('click', function() {
            profileDisplayContainer.style.display = 'none';
            editProfileFormContainer.style.display = 'block';
        });
        
        cancelEditButton.addEventListener('click', function() {
            profileDisplayContainer.style.display = 'block';
            editProfileFormContainer.style.display = 'none';
        });
    }
    
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            e.preventDefault();
            updateProfile();
        });
    }
}


function updateProfile() {
    const form = document.getElementById('profile-edit-form');
    if (!form) return;
    
    const formData = new FormData(form);
    
    if (window.csrfToken) {
        formData.append('csrf_token', window.csrfToken);
    }
    
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.textContent;
    submitBtn.textContent = 'جاري الحفظ...';
    submitBtn.disabled = true;
    
    fetch('api/profile_update.php', {
        method: 'POST',
        body: formData,
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(data => {
                throw new Error(data.message || 'خطأ في الخادم');
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showToast('تم تحديث الملف الشخصي بنجاح!', 3000);
            
            const displayName = document.getElementById('profile-display-name');
            const displayBio = document.getElementById('profile-display-bio');
            
            if (displayName && data.user.first_name) {
                const fullName = `${data.user.first_name} ${data.user.last_name || ''}`;
                displayName.textContent = fullName.trim();
            }
            
            if (displayBio && data.user.bio) {
                displayBio.textContent = data.user.bio;
            }
            
            if (data.user.profile_picture) {
                const profileImages = document.querySelectorAll('.profile-picture');
                profileImages.forEach(img => {
                    img.src = data.user.profile_picture + '?t=' + new Date().getTime();
                });
            }
            
            document.getElementById('profile-display-container').style.display = 'block';
            document.getElementById('edit-profile-form-container').style.display = 'none';
        } else {
            showToast(data.message || 'حدث خطأ أثناء تحديث الملف الشخصي', 3000);
        }
    })
    .catch(error => {
        console.error('Profile update error:', error);
        showToast(error.message || 'حدث خطأ أثناء تحديث الملف الشخصي', 3000);
    })
    .finally(() => {
        submitBtn.textContent = originalBtnText;
        submitBtn.disabled = false;
    });
}

function initPostInteractions() {
    const likeButtons = document.querySelectorAll('.like-button');
    likeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const postId = this.getAttribute('data-post-id');
            likePost(postId);
        });
    });
    
    const dislikeButtons = document.querySelectorAll('.dislike-button');
    dislikeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const postId = this.getAttribute('data-post-id');
            dislikePost(postId);
        });
    });
    
    const createPostForm = document.getElementById('create-post-form');
    if (createPostForm) {
        createPostForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitNewPost();
        });
    }
}

/**
 * @param {string} postId 
 */
function likePost(postId) {
    console.log('Liking post:', postId);
    const likeButton = document.querySelector(`.like-button[data-post-id="${postId}"]`);
    if (likeButton) {
        likeButton.classList.add('liked');
        likeButton.classList.add('animate-like');
        
        setTimeout(() => {
            likeButton.classList.remove('animate-like');
        }, 1000);
        
        const dislikeButton = document.querySelector(`.dislike-button[data-post-id="${postId}"]`);
        if (dislikeButton) {
            dislikeButton.classList.remove('disliked');
        }
        
        fetch('api/like_post.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                post_id: postId,
                action: 'like',
                csrf_token: window.csrfToken
            })
        })
        .then(response => {
            if (!response.ok) {
                if (response.status === 401) {
                    throw new Error('جلستك انتهت. يرجى تسجيل الدخول مرة أخرى.');
                }
                return response.json().then(data => {
                    throw new Error(data.message || 'حدث خطأ عند الإعجاب بالمنشور');
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('Like success:', data);
            const likeCount = document.querySelector(`.like-count[data-post-id="${postId}"]`);
            if (likeCount && data.likes_count !== undefined) {
                likeCount.textContent = data.likes_count;
            }
            
            const dislikeCount = document.querySelector(`.dislike-count[data-post-id="${postId}"]`);
            if (dislikeCount && data.dislikes_count !== undefined) {
                dislikeCount.textContent = data.dislikes_count;
            }
        })
        .catch(error => {
            console.error('Like error:', error);
            likeButton.classList.remove('liked');
            showToast(error.message || 'حدث خطأ عند الإعجاب بالمنشور', 3000, 'error');
            
            if (error.message.includes('جلستك انتهت')) {
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 2000);
            }
        });
    }
}

/**
 * @param {string} postId
 */
function dislikePost(postId) {
    console.log('Disliking post:', postId);
    const dislikeButton = document.querySelector(`.dislike-button[data-post-id="${postId}"]`);
    if (dislikeButton) {
        dislikeButton.classList.add('disliked');
        
        const likeButton = document.querySelector(`.like-button[data-post-id="${postId}"]`);
        if (likeButton) {
            likeButton.classList.remove('liked');
        }
        
        fetch('api/like_post.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                post_id: postId,
                action: 'dislike',
                csrf_token: window.csrfToken
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log('Dislike success:', data);
        })
        .catch(error => {
            console.error('Dislike error:', error);
        });
    }
}

function submitNewPost() {
    const form = document.getElementById('create-post-form');
    const contentInput = document.getElementById('post-content');
    const imageInput = document.getElementById('post-image');
    
    if (!form || !contentInput) return;
    
    const content = contentInput.value.trim();
    const hasImage = imageInput && imageInput.files && imageInput.files.length > 0;
    
    if (!content && !hasImage) {
        showToast('يرجى كتابة محتوى المنشور أو إضافة صورة', 3000, 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('content', content);
    
    if (window.csrfToken) {
        formData.append('csrf_token', window.csrfToken);
    }
    
    if (hasImage) {
        formData.append('image', imageInput.files[0]);
    }
    
    const submitButton = form.querySelector('button[type="submit"]');
    const originalButtonText = submitButton.textContent;
    submitButton.disabled = true;
    submitButton.textContent = 'جاري النشر...';
    
    fetch('api/create_post.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            if (response.status === 401) {
                throw new Error('جلستك انتهت. يرجى تسجيل الدخول مرة أخرى.');
            }
            return response.json().then(data => {
                throw new Error(data.message || 'حدث خطأ أثناء إنشاء المنشور');
            });
        }
        return response.json();
    })
    .then(data => {
        console.log('Post created:', data);
        
        if (data.success) {
            contentInput.value = '';
            if (imageInput) {
                imageInput.value = '';
            }
            
            showToast('تم نشر المنشور بنجاح!', 3000, 'success');
            
            addNewPostToFeed(data);
        } else {
            showToast(data.message || 'حدث خطأ أثناء إنشاء المنشور', 3000, 'error');
        }
    })
    .catch(error => {
        console.error('Create post error:', error);
        showToast(error.message || 'حدث خطأ أثناء إنشاء المنشور', 3000, 'error');
        
        if (error.message.includes('جلستك انتهت')) {
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 2000);
        }
    });
}

/**
 * @param {Object} post 
 */
function addNewPostToFeed(post) {
    const postsContainer = document.getElementById('posts-container');
    
    const postElement = document.createElement('div');
    postElement.className = 'card-base glass-effect post-card';
    postElement.setAttribute('data-post-id', post.id);
    
    postElement.innerHTML = `
        <div class="flex items-center mb-6">
            <img src="${post.avatar_url || 'assets/img/default-avatar.png'}" 
                 alt="User Avatar" class="w-14 h-14 rounded-full ml-4 border-2 border-slate-600">
            <div>
                <p class="font-bold text-lg text-slate-50">${post.display_name || post.username}</p>
                <p class="text-slate-400 text-sm">${post.created_at}</p>
            </div>
        </div>
        <div class="post-content text-slate-100">
            ${post.content}
        </div>
        ${post.image_url ? `<img src="${post.image_url}" alt="Post Image" class="post-image">` : ''}
        <div class="post-actions">
            <button class="post-action-btn like-button" data-post-id="${post.id}">
                <i class="bi bi-hand-thumbs-up"></i> <span class="like-count" data-post-id="${post.id}">${post.likes_count || 0}</span>
            </button>
            <button class="post-action-btn dislike-button" data-post-id="${post.id}">
                <i class="bi bi-hand-thumbs-down"></i>
            </button>
            <button class="post-action-btn">
                <i class="bi bi-chat-dots"></i> <span>${post.comments_count || 0}</span>
            </button>
            <button class="post-action-btn">
                <i class="bi bi-share"></i>
            </button>
        </div>
    `;
    
    if (postsContainer.firstChild) {
        postsContainer.insertBefore(postElement, postsContainer.firstChild);
    } else {
        postsContainer.appendChild(postElement);
    }
    
    const likeButton = postElement.querySelector('.like-button');
    likeButton.addEventListener('click', function() {
        likePost(post.id);
    });
    
    const dislikeButton = postElement.querySelector('.dislike-button');
    dislikeButton.addEventListener('click', function() {
        dislikePost(post.id);
    });
}

/**
 * @param {string} message 
 * @param {number} duration
 */
function showToast(message, duration = 3000) {
    let toast = document.querySelector('.toast-notification');
    
    if (!toast) {
        toast = document.createElement('div');
        toast.className = 'toast-notification';
        document.body.appendChild(toast);
    }
    
    toast.textContent = message;
    toast.classList.add('show');
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, duration);
}

function showUIMessage(message, type = 'success') {
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
    const gradients = {
        success: 'var(--success-gradient, linear-gradient(135deg, #43e97b 0%, #38f9d7 100%))',
        error: 'var(--danger-gradient, linear-gradient(135deg, #fa709a 0%, #fee140 100%))',
        info: 'var(--primary-gradient, linear-gradient(135deg, #667eea 0%, #764ba2 100%))'
    };
    
    messageContainer.style.background = gradients[type] || gradients.info;
    messageContainer.style.color = (type === 'success' || type === 'error') ? 'var(--bg-primary, #0a0f1c)' : 'white';
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

function handleMediaFiles(files) {
    const mediaPreview = document.getElementById('mediaPreview');
    const mediaInput = document.getElementById('media');
    
    if (!mediaPreview || !mediaInput || !files || !files[0]) return;
    
    const file = files[0];
    const reader = new FileReader();
    
    reader.onload = function(e) {
        mediaPreview.style.display = 'block';
        mediaPreview.innerHTML = '';
        
        if (file.type.startsWith('image/')) {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.className = 'max-w-full h-auto rounded-lg';
            mediaPreview.appendChild(img);
        } else if (file.type.startsWith('video/')) {
            const video = document.createElement('video');
            video.src = e.target.result;
            video.controls = true;
            video.className = 'max-w-full h-auto rounded-lg';
            mediaPreview.appendChild(video);
        }
        
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'absolute top-2 right-2 bg-red-500 text-white rounded-full p-2 hover:bg-red-600 transition-colors';
        removeBtn.innerHTML = '<i class="bi bi-x-lg"></i>';
        removeBtn.onclick = function() {
            mediaPreview.style.display = 'none';
            mediaPreview.innerHTML = '';
            mediaInput.value = '';
        };
        mediaPreview.appendChild(removeBtn);
    };
    
    reader.readAsDataURL(file);
}

const emojis = ['😀', '😃', '😄', '😁', '😆', '😅', '😂', '🤣', '😊', '😇', '🙂', '🙃', '😉', '😌', '😍', '🥰', '😘', '😗', '😙', '😚', '😋', '😛', '😝', '😜', '🤪', '🤨', '🧐', '🤓', '😎', '🤩', '🥳', '😏', '😒', '😞', '😔', '😟', '😕', '🙁', '☹️', '😣', '😖', '😫', '😩', '🥺', '😢', '😭', '😤', '😠', '😡', '🤬', '🤯', '😳', '🥵', '🥶', '😱', '😨', '😰', '😥', '😓', '🤗', '🤔', '🤭', '🤫', '🤥', '❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '🤎', '💔', '❣️', '💕', '💞', '💓', '💗', '💖', '👍', '👎', '👌', '🤌', '🤏', '✌️', '🤞', '🤟', '🤘', '🤙', '👈', '👉', '👆', '🖕', '👇', '☝️', '🔥', '⭐', '🌟', '✨', '💫', '💥', '💢', '💨', '💦', '💤', '🎉', '🎊', '🎈', '🎁', '🏆', '🥇'];
let emojiPickerInstance = null;

function toggleEmojiPicker() {
    const container = document.getElementById('emojiPickerContainer');
    
    if (!emojiPickerInstance) {
        emojiPickerInstance = document.createElement('div');
        emojiPickerInstance.id = 'emojiPicker';
        emojiPickerInstance.className = 'emoji-picker';
        
        const grid = document.createElement('div');
        grid.className = 'emoji-grid';
        
        emojis.forEach(emoji => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'emoji-item';
            button.textContent = emoji;
            button.onclick = () => insertEmojiToPost(emoji);
            grid.appendChild(button);
        });
        
        emojiPickerInstance.appendChild(grid);
        container.appendChild(emojiPickerInstance);
        emojiPickerInstance.style.display = 'block';
        
        document.addEventListener('click', closeEmojiPickerOutside, true);
    } else {
        emojiPickerInstance.style.display = emojiPickerInstance.style.display === 'block' ? 'none' : 'block';
    }
}

function closeEmojiPickerOutside(event) {
    const emojiButton = document.querySelector('#emojiPickerContainer button');
    if (emojiPickerInstance && emojiPickerInstance.style.display === 'block') {
        if (!emojiPickerInstance.contains(event.target) && !emojiButton.contains(event.target)) {
            emojiPickerInstance.style.display = 'none';
        }
    }
}

function insertEmojiToPost(emoji) {
    const textarea = document.getElementById('postContent');
    const cursorPos = textarea.selectionStart;
    const textBefore = textarea.value.substring(0, cursorPos);
    const textAfter = textarea.value.substring(textarea.selectionEnd);
    
    textarea.value = textBefore + emoji + textAfter;
    textarea.focus();
    
    const newCursorPos = cursorPos + emoji.length;
    textarea.setSelectionRange(newCursorPos, newCursorPos);
    
    if (emojiPickerInstance) {
        emojiPickerInstance.style.display = 'none';
    }
}

function openAddLocationModal() {
    const inputId = 'locationInput';
    const inputElement = document.createElement('input');
    inputElement.type = 'text';
    inputElement.id = inputId;
    inputElement.className = 'form-control';
    inputElement.placeholder = 'مثال: القاهرة, مصر';
    
    setupModal(
        'إضافة موقع للمنشور',
        inputElement,
        'إضافة الموقع',
        'إلغاء',
        () => {
            const locationValue = document.getElementById(inputId).value.trim();
            if (locationValue) {
                const textarea = document.getElementById('postContent');
                const currentText = textarea.value;
                const newText = currentText + (currentText.length > 0 ? "\n" : "") + `📍 ${locationValue}`;
                textarea.value = newText;
                textarea.focus();
            } else {
                showUIMessage("الرجاء إدخال اسم موقع صالح.", "error");
            }
        }
    );
    
    setTimeout(() => document.getElementById(inputId).focus(), 100);
}

function setupModal(title, bodyContent, confirmText, cancelText, confirmAction, cancelAction) {
    document.getElementById('modalTitle').textContent = title;
    
    const modalBody = document.getElementById('modalBody');
    if (typeof bodyContent === 'string') {
        modalBody.innerHTML = `<p>${bodyContent}</p>`;
    } else {
        modalBody.innerHTML = '';
        modalBody.appendChild(bodyContent);
    }
    
    const confirmBtn = document.getElementById('modalConfirmBtn');
    confirmBtn.textContent = confirmText || 'موافق';
    confirmBtn.onclick = function() {
        if(confirmAction) confirmAction();
        closeModal('genericModal');
    };
    
    const cancelBtn = document.getElementById('modalCancelBtn');
    if (cancelText) {
        cancelBtn.textContent = cancelText;
        cancelBtn.style.display = 'inline-flex';
        cancelBtn.onclick = function() {
            if(cancelAction) cancelAction();
            closeModal('genericModal');
        };
    } else {
        cancelBtn.style.display = 'none';
    }
    
    openModal('genericModal');
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        modal.addEventListener('click', function(e) {
            if (e.target === modal) closeModal(modalId);
        });
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.remove('active');
}

async function toggleLike(postId, element) {
    const icon = element.querySelector('i');
    const originalIconClass = icon.className;
    icon.className = 'bi bi-arrow-repeat animate-spin';
    element.disabled = true;
    
    await new Promise(resolve => setTimeout(resolve, 500));
    
    const likeCountSpan = element.querySelector('.like-count');
    let currentLikes = parseInt(likeCountSpan.textContent);
    const isLiked = element.classList.toggle('liked');
    
    if (isLiked) {
        icon.className = 'bi bi-heart-fill';
        likeCountSpan.textContent = currentLikes + 1;
    } else {
        icon.className = 'bi bi-heart';
        likeCountSpan.textContent = currentLikes - 1;
    }
    
    element.disabled = false;
}

async function toggleBookmark(postId, element) {
    const icon = element.querySelector('i');
    const originalIconClass = icon.className;
    icon.className = 'bi bi-arrow-repeat animate-spin';
    element.disabled = true;
    
    await new Promise(resolve => setTimeout(resolve, 500));
    
    const textElement = element.querySelector('span.hidden');
    const isBookmarked = element.classList.toggle('bookmarked');
    
    if (isBookmarked) {
        icon.className = 'bi bi-bookmark-star-fill';
        if (textElement) textElement.textContent = ' محفوظ';
    } else {
        icon.className = 'bi bi-bookmark-star';
        if (textElement) textElement.textContent = ' حفظ';
    }
    
    element.disabled = false;
}

function sharePost(postId) {
    const postUrl = `${window.location.origin}/post.php?id=${postId}`;
    
    if (navigator.share) {
        navigator.share({
            title: 'اطلع على هذا المنشور الرائع!',
            text: `شاهد هذا المنشور على SUT Premium:`,
            url: postUrl,
        })
        .then(() => showUIMessage('تمت مشاركة الرابط بنجاح!', 'success'))
        .catch((error) => {
            if (error.name !== 'AbortError') {
                showUIMessage('فشلت المشاركة.', 'error');
            }
        });
    } else {
        navigator.clipboard.writeText(postUrl)
            .then(() => {
                showUIMessage('تم نسخ رابط المنشور إلى الحافظة!', 'success');
            })
            .catch(err => {
                showUIMessage('لم نتمكن من نسخ الرابط.', 'error');
            });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const postForm = document.getElementById('postForm');
    if (postForm) {
        postForm.addEventListener('submit', handlePostSubmit);
    }
    
    document.querySelectorAll('textarea.form-control').forEach(textarea => {
        textarea.addEventListener('input', () => {
            textarea.style.height = 'auto';
            textarea.style.height = (textarea.scrollHeight) + 'px';
        });
    });
    
    console.log('Profile page loaded successfully!');
});

function loadPosts(userId, page = 1) {
    fetch(`api/posts.php?action=get_user_posts&user_id=${userId}&page=${page}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const postsContainer = document.getElementById('posts');
                data.posts.forEach(post => {
                    postsContainer.appendChild(createPostElement(post));
                });
            }
        })
        .catch(error => console.error('Error loading posts:', error));
}

function createPostElement(post) {
    const postElement = document.createElement('div');
    postElement.className = 'bg-[#1a1f2e] rounded-2xl p-6';
    
    const postHeader = `
        <div class="flex items-center gap-4 mb-4">
            <img src="${post.avatar || 'assets/images/default-avatar.png'}" alt="${post.username}" class="w-12 h-12 rounded-full">
            <div>
                <div class="font-semibold">${post.username}</div>
                <div class="text-gray-400 text-sm">${post.created_at}</div>
            </div>
        </div>
    `;
    
    const postContent = `
        <div class="mb-4">
            <p class="whitespace-pre-wrap">${linkify(post.content)}</p>
            ${post.media ? createMediaElement(post.media) : ''}
        </div>
    `;
    
    const postActions = `
        <div class="flex items-center gap-6 text-gray-400">
            <button class="flex items-center gap-2 hover:text-white transition-colors ${post.is_liked ? 'text-red-500' : ''}" onclick="toggleLike(${post.id}, this)">
                <i class="bi bi-heart${post.is_liked ? '-fill' : ''}"></i>
                <span>${post.likes_count}</span>
            </button>
            <button class="flex items-center gap-2 hover:text-white transition-colors" onclick="toggleComments(${post.id}, this)">
                <i class="bi bi-chat"></i>
                <span>${post.comments_count}</span>
            </button>
            <button class="flex items-center gap-2 hover:text-white transition-colors ${post.is_bookmarked ? 'text-yellow-500' : ''}" onclick="toggleBookmark(${post.id}, this)">
                <i class="bi bi-bookmark${post.is_bookmarked ? '-fill' : ''}"></i>
            </button>
        </div>
    `;
    
    postElement.innerHTML = postHeader + postContent + postActions;
    return postElement;
}

function createMediaElement(media) {
    const extension = media.split('.').pop().toLowerCase();
    const isVideo = ['mp4', 'webm', 'ogg'].includes(extension);
    
    if (isVideo) {
        return `
            <div class="relative pt-[56.25%] rounded-xl overflow-hidden mb-4">
                <video src="${media}" class="absolute inset-0 w-full h-full object-contain" controls></video>
            </div>
        `;
    } else {
        return `
            <div class="rounded-xl overflow-hidden mb-4">
                <img src="${media}" class="w-full" alt="Post media">
            </div>
        `;
    }
}

function linkify(text) {
    text = text.replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank" class="text-blue-500 hover:underline">$1</a>');
    
    text = text.replace(/#([^\s#]+)/g, '<a href="hashtag.php?tag=$1" class="text-blue-500 hover:underline">#$1</a>');
    
    text = text.replace(/@([^\s@]+)/g, '<a href="u.php?username=$1" class="text-blue-500 hover:underline">@$1</a>');
    
    return text;
}

function handlePostSubmit(event) {
    event.preventDefault();
    
    const form = event.target;
    const contentInput = form.querySelector('[name="content"]');
    const mediaInput = form.querySelector('[name="media"]');
    const submitBtn = form.querySelector('button[type="submit"]');
    
    if (!contentInput || (!contentInput.value.trim() && (!mediaInput || !mediaInput.files[0]))) {
        return;
    }
    
    if (submitBtn) {
        const originalBtnText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="bi bi-arrow-repeat animate-spin"></i> جار النشر...';
        
        try {
    const formData = new FormData(form);
    formData.append('action', 'create_post_on_profile');
    
    fetch('api/posts.php', {
        method: 'POST',
        body: formData
    })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(result => {
                if (result.success && result.post) {
                    if (contentInput) contentInput.value = '';
                    if (mediaInput) mediaInput.value = '';
                    
                    const mediaPreview = document.getElementById('mediaPreview');
                    if (mediaPreview) {
                        mediaPreview.style.display = 'none';
                        mediaPreview.innerHTML = '';
                    }
                    
                    const postsContainer = document.getElementById('userPostsContainer');
                    const noPostsMessage = document.getElementById('noPostsMessage');
                    
                    if (postsContainer) {
                        if (noPostsMessage) {
                            noPostsMessage.style.display = 'none';
                        }
                        
                        const postElement = createPostElement(result.post);
                        if (postElement) {
                            if (postsContainer.firstChild) {
                                postsContainer.insertBefore(postElement, postsContainer.firstChild);
                            } else {
                                postsContainer.appendChild(postElement);
                            }
                            
                            const statsElement = document.querySelector('.stat-number');
                            if (statsElement) {
                                const currentCount = parseInt(statsElement.textContent.replace(/,/g, '')) || 0;
                                statsElement.textContent = (currentCount + 1).toLocaleString();
                            }
                            
                            showUIMessage('تم النشر بنجاح', 'success');
                        }
                    }
                }
            })
            .catch(error => {
                console.log('Error creating post:', error);
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            });
            
        } catch (error) {
            console.log('Error creating post:', error);
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    }
}

function handleMediaFiles(files) {
    const mediaPreview = document.getElementById('mediaPreview');
    const mediaInput = document.getElementById('media');
    
    if (!mediaPreview || !mediaInput || !files || !files[0]) return;
    
    const file = files[0];
    const reader = new FileReader();
    
    reader.onload = function(e) {
        mediaPreview.style.display = 'block';
        mediaPreview.innerHTML = '';
        
        if (file.type.startsWith('image/')) {
                const img = document.createElement('img');
            img.src = e.target.result;
            img.className = 'max-w-full h-auto rounded-lg';
            mediaPreview.appendChild(img);
        } else if (file.type.startsWith('video/')) {
                const video = document.createElement('video');
            video.src = e.target.result;
                video.controls = true;
            video.className = 'max-w-full h-auto rounded-lg';
            mediaPreview.appendChild(video);
        }
        
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'absolute top-2 right-2 bg-red-500 text-white rounded-full p-2 hover:bg-red-600 transition-colors';
        removeBtn.innerHTML = '<i class="bi bi-x-lg"></i>';
        removeBtn.onclick = function() {
            mediaPreview.style.display = 'none';
            mediaPreview.innerHTML = '';
            mediaInput.value = '';
        };
        mediaPreview.appendChild(removeBtn);
    };
    
    reader.readAsDataURL(file);
}

function toggleLike(postId, button) {
    fetch('api/posts.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `action=toggle_like&post_id=${postId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const icon = button.querySelector('i');
            const count = button.querySelector('span');
            
            if (data.action === 'like') {
                button.classList.add('text-red-500');
                icon.classList.remove('bi-heart');
                icon.classList.add('bi-heart-fill');
            } else {
                button.classList.remove('text-red-500');
                icon.classList.remove('bi-heart-fill');
                icon.classList.add('bi-heart');
            }
            
            count.textContent = data.likes_count;
        }
    })
    .catch(error => console.error('Error toggling like:', error));
}

function toggleBookmark(postId, button) {
    fetch('api/posts.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `action=toggle_bookmark&post_id=${postId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const icon = button.querySelector('i');
            
            if (data.action === 'bookmark') {
                button.classList.add('text-yellow-500');
                icon.classList.remove('bi-bookmark');
                icon.classList.add('bi-bookmark-fill');
            } else {
                button.classList.remove('text-yellow-500');
                icon.classList.remove('bi-bookmark-fill');
                icon.classList.add('bi-bookmark');
            }
        }
    })
    .catch(error => console.error('Error toggling bookmark:', error));
}

function toggleFollow(userId, button) {
    fetch('api/users.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `action=toggle_follow&user_id=${userId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const icon = button.querySelector('i');
            const text = button.querySelector('span');
            
            if (data.action === 'follow') {
                button.classList.add('following');
                icon.classList.remove('bi-person-plus');
                icon.classList.add('bi-person-dash');
                text.textContent = 'إلغاء المتابعة';
            } else {
                button.classList.remove('following');
                icon.classList.remove('bi-person-dash');
                icon.classList.add('bi-person-plus');
                text.textContent = 'متابعة';
            }
            
            const followersCount = document.querySelector('.stat-item:nth-child(2) .stat-value');
            followersCount.textContent = data.followers_count;
        }
    })
    .catch(error => console.error('Error toggling follow:', error));
}

function openEditProfileModal() {
    const modal = document.getElementById('genericModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalBody = document.getElementById('modalBody');
    const modalCancelBtn = document.getElementById('modalCancelBtn');
    const modalConfirmBtn = document.getElementById('modalConfirmBtn');
    
    modalTitle.textContent = 'تعديل الملف الشخصي';
    modalBody.innerHTML = `
        <form id="editProfileForm" class="space-y-6">
            <div>
                <label class="block text-sm font-medium mb-2">الصورة الشخصية</label>
                <div class="flex items-center gap-4">
                    <img src="${currentUser.avatar || 'assets/images/default-avatar.png'}" alt="" class="w-20 h-20 rounded-full">
                    <input type="file" name="avatar" accept="image/*" class="hidden" id="avatarInput">
                    <button type="button" class="bg-[#0a0f1c] border border-gray-700 rounded-lg px-4 py-2" onclick="document.getElementById('avatarInput').click()">
                        تغيير الصورة
                    </button>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-2">نبذة شخصية</label>
                <textarea name="bio" class="w-full bg-[#0a0f1c] border border-gray-700 rounded-xl p-3 text-white resize-none h-24">${currentUser.bio || ''}</textarea>
            </div>
        </form>
    `;
    
    modalCancelBtn.textContent = 'إلغاء';
    modalConfirmBtn.textContent = 'حفظ';
    
    modalCancelBtn.onclick = () => {
        modal.classList.remove('active');
    };
    
    modalConfirmBtn.onclick = () => {
        const form = document.getElementById('editProfileForm');
        const formData = new FormData(form);
        formData.append('action', 'update_profile');
        
        fetch('api/users.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error updating profile:', error);
            alert('حدث خطأ أثناء تحديث الملف الشخصي');
        });
    };
    
    modal.classList.add('active');
}

function startChat(userId) {
    window.location.href = `messages.php?user=${userId}`;
}

function viewActivity() {
    window.location.href = 'activity.php';
}

document.addEventListener('DOMContentLoaded', () => {
    const postsContainer = document.getElementById('posts');
    if (postsContainer) {
        loadPosts(userId);
    }
});

async function deletePost(postId) {
    if (!confirm('هل أنت متأكد من حذف هذا المنشور؟')) {
        return;
    }
    
    try {
        const response = await fetch('api/posts.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete_post&post_id=${postId}`
        });
        
        const result = await response.json();
        
        if (result.success) {
            const postElement = document.querySelector(`.post-card[data-post-id="${postId}"]`);
            if (postElement) {
                postElement.remove();
                
                const statsElement = document.querySelector('.stat-number');
                if (statsElement) {
                    const currentCount = parseInt(statsElement.textContent.replace(/,/g, '')) || 0;
                    statsElement.textContent = Math.max(0, currentCount - 1).toLocaleString();
                }
                
                showUIMessage('تم حذف المنشور بنجاح', 'success');
                
                const postsContainer = document.getElementById('userPostsContainer');
                const noPostsMessage = document.getElementById('noPostsMessage');
                if (postsContainer && noPostsMessage && !postsContainer.children.length) {
                    noPostsMessage.style.display = 'block';
                }
            }
        } else {
            throw new Error(result.message || 'فشل في حذف المنشور');
        }
    } catch (error) {
        console.error('Error deleting post:', error);
        showUIMessage(error.message || 'حدث خطأ أثناء حذف المنشور', 'error');
    }
}

async function toggleComments(postId, button) {
    const commentsSection = document.querySelector(`#comments-section-${postId}`);
    const commentsList = document.querySelector(`#comments-list-${postId}`);
    
    if (!commentsSection || !commentsList) return;
    
    if (commentsSection.classList.contains('hidden')) {

        commentsSection.classList.remove('hidden');
        commentsSection.classList.add('active');
        
        
        try {
            
            commentsList.innerHTML = `
                <div class="flex justify-center items-center p-4">
                    <i class="bi bi-arrow-repeat animate-spin text-2xl text-blue-500"></i>
                </div>
            `;
            
            const response = await fetch(`api/comments.php?action=get_comments&post_id=${postId}`);
            const result = await response.json();
            
            if (result.success) {
                if (result.comments.length > 0) {
                    commentsList.innerHTML = result.comments.map(comment => createCommentElement(comment)).join('');
                } else {
                    commentsList.innerHTML = `
                        <div class="text-center p-4 text-gray-400">
                            <i class="bi bi-chat-dots block text-3xl mb-2"></i>
                            لا توجد تعليقات بعد. كن أول من يعلق!
                        </div>
                    `;
                }
            } else {
                throw new Error(result.message || 'فشل في تحميل التعليقات');
            }
        } catch (error) {
            console.error('Error loading comments:', error);
            commentsList.innerHTML = `
                <div class="text-center p-4 text-red-400">
                    <i class="bi bi-exclamation-circle block text-3xl mb-2"></i>
                    حدث خطأ أثناء تحميل التعليقات
                </div>
            `;
        }
    } else {
        commentsSection.classList.add('hidden');
        commentsSection.classList.remove('active');
    }
}

function createCommentElement(comment) {
    const avatar = comment.avatar || `https://placehold.co/40x40/1a1f2e/ffffff?text=${comment.username.charAt(0).toUpperCase()}`;
    
    return `
        <div class="comment-item" data-comment-id="${comment.id}">
            <img src="${avatar}" alt="${comment.username}" class="comment-avatar">
            <div class="comment-content">
                <div class="comment-header">
                    <span class="comment-author">@${comment.username}</span>
                    <span class="comment-time">${comment.created_at}</span>
                </div>
                <p class="comment-text">${comment.content}</p>
                ${comment.is_owner ? `
                    <form class="comment-edit-form" id="edit-form-${comment.id}">
                        <input type="text" class="form-control" name="content" value="${comment.content}">
                        <div class="btn-group">
                            <button type="submit" class="btn btn-save">حفظ</button>
                            <button type="button" class="btn btn-cancel" onclick="cancelEditComment(${comment.id})">إلغاء</button>
                        </div>
                    </form>
                ` : ''}
            </div>
            ${comment.is_owner ? `
                <div class="comment-actions">
                    <button class="comment-btn edit-btn" onclick="editComment(${comment.id}, ${comment.post_id})">
                        <i class="bi bi-pencil-square"></i>
                    </button>
                    <button class="comment-btn delete-btn" onclick="deleteComment(${comment.id}, ${comment.post_id})">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            ` : ''}
        </div>
    `;
}

function editComment(commentId, postId) {
    const commentElement = document.querySelector(`.comment-item[data-comment-id="${commentId}"]`);
    if (!commentElement) return;
    
    const commentText = commentElement.querySelector('.comment-text');
    const editForm = commentElement.querySelector('.comment-edit-form');
    
    if (!commentText || !editForm) return;
    
    commentText.style.display = 'none';
    editForm.classList.add('active');
    
    const input = editForm.querySelector('input[name="content"]');
    if (input) {
        input.value = commentText.textContent;
        input.focus();
    }
    
    editForm.onsubmit = async function(e) {
        e.preventDefault();
        
        const newContent = input.value.trim();
        if (!newContent) return;
        
        const submitBtn = editForm.querySelector('.btn-save');
        const originalBtnText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="bi bi-arrow-repeat animate-spin"></i>';
        
        try {
            const response = await fetch('api/comments.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=edit_comment&comment_id=${commentId}&content=${encodeURIComponent(newContent)}`
            });
            
            const result = await response.json();
            
            if (result.success) {
                commentText.textContent = newContent;
                
                editForm.classList.remove('active');
                commentText.style.display = 'block';
                
                showUIMessage('تم تحديث التعليق بنجاح', 'success');
            } else {
                throw new Error(result.message || 'فشل في تحديث التعليق');
            }
        } catch (error) {
            console.error('Error updating comment:', error);
            showUIMessage(error.message || 'حدث خطأ أثناء تحديث التعليق', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = originalBtnText;
        }
    };
}

function cancelEditComment(commentId) {
    const commentElement = document.querySelector(`.comment-item[data-comment-id="${commentId}"]`);
    if (!commentElement) return;
    
    const commentText = commentElement.querySelector('.comment-text');
    const editForm = commentElement.querySelector('.comment-edit-form');
    
    if (!commentText || !editForm) return;
    
    editForm.classList.remove('active');
    commentText.style.display = 'block';
}

let currentEditPostId = null;
let originalMediaUrl = null;
let mediaWasRemoved = false;

function editPost(postId) {
    const postElement = document.querySelector(`.post-card[data-post-id="${postId}"]`);
    if (!postElement) return;
    
    currentEditPostId = postId;
    const contentElement = postElement.querySelector('.post-content p');
    const mediaElement = postElement.querySelector('.post-media');
    
    const editContentInput = document.getElementById('editPostContent');
    editContentInput.value = contentElement ? contentElement.textContent.trim() : '';
    
    document.getElementById('editPostId').value = postId;
    
    const editMediaPreview = document.getElementById('editMediaPreview');
    const removeMediaBtn = document.getElementById('removeMediaBtn');
    
    if (mediaElement) {
        originalMediaUrl = mediaElement.src;
        editMediaPreview.style.display = 'block';
        editMediaPreview.innerHTML = mediaElement.outerHTML;
        removeMediaBtn.classList.remove('hidden');
    } else {
        originalMediaUrl = null;
        editMediaPreview.style.display = 'none';
        editMediaPreview.innerHTML = '';
        removeMediaBtn.classList.add('hidden');
    }
    
    mediaWasRemoved = false;
    
    document.getElementById('editPostModal').classList.remove('hidden');
}

function closeEditPostModal() {
    document.getElementById('editPostModal').classList.add('hidden');
    currentEditPostId = null;
    originalMediaUrl = null;
    mediaWasRemoved = false;
    
    document.getElementById('editPostForm').reset();
    document.getElementById('editMediaPreview').innerHTML = '';
    document.getElementById('editMediaPreview').style.display = 'none';
    document.getElementById('removeMediaBtn').classList.add('hidden');
}

function handleEditMediaFiles(files) {
    const mediaPreview = document.getElementById('editMediaPreview');
    const removeMediaBtn = document.getElementById('removeMediaBtn');
    
    if (!mediaPreview || !files || !files[0]) return;
    
    const file = files[0];
    const reader = new FileReader();
    
    reader.onload = function(e) {
        mediaPreview.style.display = 'block';
        mediaPreview.innerHTML = '';
        
        if (file.type.startsWith('image/')) {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.className = 'max-w-full h-auto rounded-lg post-media';
            mediaPreview.appendChild(img);
        } else if (file.type.startsWith('video/')) {
            const video = document.createElement('video');
            video.src = e.target.result;
            video.controls = true;
            video.className = 'max-w-full h-auto rounded-lg post-media';
            mediaPreview.appendChild(video);
        }
        
        removeMediaBtn.classList.remove('hidden');
        mediaWasRemoved = false;
    };
    
    reader.readAsDataURL(file);
}

function removeEditMedia() {
    const mediaPreview = document.getElementById('editMediaPreview');
    const mediaInput = document.getElementById('editPostMedia');
    const removeMediaBtn = document.getElementById('removeMediaBtn');
    
    mediaPreview.style.display = 'none';
    mediaPreview.innerHTML = '';
    mediaInput.value = '';
    removeMediaBtn.classList.add('hidden');
    mediaWasRemoved = true;
}

const editPostForm = document.getElementById('editPostForm');
if (editPostForm) {
    editPostForm.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'edit_post');
    
    if (mediaWasRemoved) {
        formData.append('remove_media', '1');
    }
    
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="bi bi-arrow-repeat animate-spin"></i> جار الحفظ...';
    
    try {
        const response = await fetch('api/posts.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            const postElement = document.querySelector(`.post-card[data-post-id="${currentEditPostId}"]`);
            if (postElement) {
                const contentElement = postElement.querySelector('.post-content');
                const mediaContainer = postElement.querySelector('.post-media');
                
                if (result.post.content) {
                    contentElement.innerHTML = `<p>${result.post.content}</p>`;
                } else {
                    contentElement.innerHTML = '';
                }
                
                if (result.post.media_url) {
                    if (!mediaContainer) {
                        const mediaElement = result.post.media_type === 'video' 
                            ? `<video src="${result.post.media_url}" controls class="post-media"></video>`
                            : `<img src="${result.post.media_url}" alt="صورة المنشور" class="post-media">`;
                        contentElement.insertAdjacentHTML('beforeend', mediaElement);
                    } else {
                        mediaContainer.src = result.post.media_url;
                    }
                } else if (mediaContainer) {
                    mediaContainer.remove();
                }
            }
            
            showUIMessage('تم تحديث المنشور بنجاح', 'success');
            closeEditPostModal();
        } else {
            throw new Error(result.message || 'فشل في تحديث المنشور');
        }
    } catch (error) {
        console.error('Error updating post:', error);
        showUIMessage(error.message || 'حدث خطأ أثناء تحديث المنشور', 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
    }
  });
}
