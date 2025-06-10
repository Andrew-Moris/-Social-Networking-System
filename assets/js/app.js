document.addEventListener('DOMContentLoaded', function() {
    initializeToasts();
    
    initializeLazyLoading();
    
    initializeClickOutside();

    const avatarUpload = document.getElementById('avatar-upload');
    if (avatarUpload) {
        avatarUpload.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;

            if (!file.type.startsWith('image/')) {
                showToast('يرجى اختيار ملف صورة صالح', 'error');
                return;
            }

            if (file.size > 2 * 1024 * 1024) {
                showToast('حجم الصورة كبير جداً. الحد الأقصى 2 ميجابايت', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('avatar', file);

            showToast('جاري رفع الصورة...', 'info');

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
                    showToast('تم تحديث الصورة الشخصية بنجاح', 'success');
                } else {
                    showToast(data.message || 'حدث خطأ أثناء رفع الصورة', 'error');
                }
            })
            .catch(error => {
                console.error('Error uploading avatar:', error);
                showToast('حدث خطأ أثناء رفع الصورة', 'error');
            });
        });
    }
});

function initializeToasts() {
    document.querySelectorAll('.toast').forEach(toast => {
        setTimeout(() => {
            toast.classList.add('fade-out');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    });
}

function initializeLazyLoading() {
    const images = document.querySelectorAll('img[data-src]');
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                observer.unobserve(img);
            }
        });
    });

    images.forEach(img => imageObserver.observe(img));
}

function initializeClickOutside() {
    document.addEventListener('click', function(event) {
        const dropdowns = document.querySelectorAll('.dropdown-content');
        dropdowns.forEach(dropdown => {
            if (!dropdown.contains(event.target) && !event.target.matches('.dropdown-trigger')) {
                dropdown.classList.remove('show');
            }
        });
    });
}

function toggleFollow(button) {
    const userId = button.dataset.userId;
    const isFollowing = button.classList.contains('following');
    
    fetch('api/follow.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ user_id: userId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            button.classList.toggle('following');
            button.textContent = button.classList.contains('following') ? 'إلغاء المتابعة' : 'متابعة';
            
            const followersCount = document.querySelector('.followers-count');
            if (followersCount) {
                followersCount.textContent = data.followers_count;
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('حدث خطأ أثناء تحديث حالة المتابعة');
    });
}

function showToast(message, type = 'info') {
    let toast = document.querySelector('.toast-notification');
    if (!toast) {
        toast = document.createElement('div');
        toast.className = 'toast-notification';
        document.body.appendChild(toast);
    }

    toast.classList.remove('success', 'error', 'info');
    
    if (type) {
        toast.classList.add(type);
    }

    toast.textContent = message;
    toast.classList.add('show');

    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

function loadTabContent(tabName) {
    const userId = document.querySelector('.profile-container').dataset.userId;
    const container = document.getElementById(tabName);
    
    if (!container) return;
    
    container.innerHTML = '<div class="loading-spinner"></div>';
    
    fetch(`api/profile_${tabName}.php?user_id=${userId}`)
        .then(response => response.json())
        .then(data => {
            container.innerHTML = '';
            
            if (data.error) {
                container.innerHTML = `<div class="error-state">${data.error}</div>`;
                return;
            }
            
            if (data.length === 0) {
                container.innerHTML = '<div class="empty-state">لا يوجد محتوى</div>';
                return;
            }
            
            switch(tabName) {
                case 'posts':
                    renderPosts(data, container);
                    break;
                case 'media':
                    renderMedia(data, container);
                    break;
                case 'likes':
                    renderLikes(data, container);
                    break;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            container.innerHTML = '<div class="error-state">حدث خطأ في تحميل المحتوى</div>';
        });
}

function renderPosts(posts, container) {
    posts.forEach(post => {
        const postElement = document.createElement('div');
        postElement.className = 'post-card';
        postElement.innerHTML = `
            <div class="post-content">${post.content}</div>
            <div class="post-meta">
                <span class="post-date">${formatDate(post.created_at)}</span>
                <span class="post-stats">
                    <span>${post.likes_count} إعجاب</span>
                    <span>${post.comments_count} تعليق</span>
                </span>
            </div>
        `;
        container.appendChild(postElement);
    });
}

function renderMedia(mediaItems, container) {
    const mediaGrid = document.createElement('div');
    mediaGrid.className = 'media-grid';
    
    mediaItems.forEach(media => {
        const mediaElement = document.createElement('div');
        mediaElement.className = 'media-item';
        mediaElement.innerHTML = `
            <img src="${media.url}" alt="صورة" loading="lazy">
        `;
        mediaGrid.appendChild(mediaElement);
    });
    
    container.appendChild(mediaGrid);
}

function renderLikes(likes, container) {
    likes.forEach(like => {
        const likeElement = document.createElement('div');
        likeElement.className = 'liked-post';
        likeElement.innerHTML = `
            <div class="post-preview">${like.post_content}</div>
            <div class="post-meta">
                <span>أعجبك في ${formatDate(like.liked_at)}</span>
            </div>
        `;
        container.appendChild(likeElement);
    });
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('ar-SA', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(date);
}

function switchTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    document.getElementById(tabName).classList.add('active');
    document.querySelector(`.tab-btn[onclick="switchTab('${tabName}')"]`).classList.add('active');
    
    loadTabContent(tabName);
} 