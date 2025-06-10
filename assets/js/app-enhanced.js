

class SocialApp {
    constructor() {
        this.init();
        this.setupEventListeners();
        this.loadInitialData();
    }

    init() {
        this.apiBaseUrl = 'api/';
        this.maxImageSize = 5 * 1024 * 1024; 
        this.allowedImageTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        this.postForm = document.getElementById('postForm');
        this.postsContainer = document.getElementById('postsContainer');
        this.imagePreview = document.getElementById('imagePreview');
        this.imageInput = document.getElementById('postImage');
    }

    setupEventListeners() {
        if (this.postForm) {
            this.postForm.addEventListener('submit', (e) => this.handlePostSubmit(e));
        }

        if (this.imageInput) {
            this.imageInput.addEventListener('change', (e) => this.handleImageSelect(e));
        }

        document.addEventListener('click', (e) => {
            if (e.target.matches('.delete-post, .delete-post *')) {
                e.preventDefault();
                const postId = this.getPostId(e.target);
                if (postId) this.deletePost(postId);
            }

            if (e.target.matches('.like-btn, .like-btn *')) {
                e.preventDefault();
                const postId = this.getPostId(e.target);
                if (postId) this.toggleLike(postId, e.target);
            }

            if (e.target.matches('.follow-btn, .follow-btn *')) {
                e.preventDefault();
                const userId = e.target.closest('.follow-btn').dataset.userId;
                if (userId) this.toggleFollow(userId, e.target.closest('.follow-btn'));
            }

            if (e.target.matches('.bookmark-btn, .bookmark-btn *')) {
                e.preventDefault();
                const postId = this.getPostId(e.target);
                if (postId) this.toggleBookmark(postId, e.target);
            }
        });

        document.addEventListener('click', (e) => {
            if (e.target.matches('.remove-image')) {
                this.removeImagePreview();
            }
        });
    }

    loadInitialData() {
        if (this.postsContainer) {
            this.loadPosts();
        }
    }


    async handlePostSubmit(e) {
        e.preventDefault();
        
        const formData = new FormData(this.postForm);
        formData.append('action', 'create_post');

        const content = formData.get('content')?.trim();
        const hasImage = this.imageInput && this.imageInput.files.length > 0;

        if (!content && !hasImage) {
            console.log('يرجى إدخال محتوى أو صورة');
            return;
        }

        if (content && content.length > 5000) {
            console.log('المحتوى طويل جداً');
            return;
        }

        const submitBtn = this.postForm.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'جاري النشر...';

        try {
            const response = await fetch('api/posts_fixed.php', {
                method: 'POST',
                body: formData
            });

            const result = await this.handleApiResponse(response);
            
            if (result.success) {
                console.log('تم نشر المنشور بنجاح!');
                this.postForm.reset();
                this.removeImagePreview();
                
                if (result.post && this.postsContainer) {
                    this.addNewPostToDOM(result.post);
                }
                
                this.updatePostsCount(1);
            } else {
                console.log(result.message || 'لم يتم نشر المنشور');
            }
        } catch (error) {
            console.error('خطأ في نشر المنشور:', error);
            console.log('لم يتم نشر المنشور');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    }

  
    handleImageSelect(e) {
        const file = e.target.files[0];
        
        if (!file) {
            this.removeImagePreview();
            return;
        }

        if (!this.allowedImageTypes.includes(file.type)) {
            console.log('نوع الملف غير مدعوم');
            e.target.value = '';
            return;
        }

        if (file.size > this.maxImageSize) {
            console.log('الصورة كبيرة جداً');
            e.target.value = '';
            return;
        }

        this.createImagePreview(file);
    }

  
    createImagePreview(file) {
        const reader = new FileReader();
        
        reader.onload = (e) => {
            if (this.imagePreview) {
                this.imagePreview.innerHTML = `
                    <div class="image-preview-container">
                        <img src="${e.target.result}" alt="معاينة الصورة" class="preview-image">
                        <button type="button" class="remove-image" title="إزالة الصورة">
                            <i class="bi bi-x-circle-fill"></i>
                        </button>
                        <div class="image-info">
                            <span class="image-name">${file.name}</span>
                            <span class="image-size">${this.formatFileSize(file.size)}</span>
                        </div>
                    </div>
                `;
                this.imagePreview.style.display = 'block';
            }
        };

        reader.readAsDataURL(file);
    }

    
    removeImagePreview() {
        if (this.imagePreview) {
            this.imagePreview.innerHTML = '';
            this.imagePreview.style.display = 'none';
        }
        if (this.imageInput) {
            this.imageInput.value = '';
        }
    }

    
    async deletePost(postId) {
        if (!confirm('هل أنت متأكد من حذف هذا المنشور؟')) {
            return;
        }

        try {
            const response = await fetch('api/posts_fixed.php', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'delete_post',
                    post_id: parseInt(postId)
                })
            });

            const result = await this.handleApiResponse(response);
            
            if (result.success) {
                console.log('تم حذف المنشور بنجاح!');
                this.removePostFromDOM(postId);
                this.updatePostsCount(-1);
            } else {
                console.log(result.message || 'لم يتم حذف المنشور');
            }
        } catch (error) {
            console.error('خطأ في حذف المنشور:', error);
            console.log('لم يتم حذف المنشور');
        }
    }

    async toggleLike(postId, element) {
        const likeBtn = element.closest('.like-btn');
        const likeCount = likeBtn.querySelector('.like-count');
        const likeIcon = likeBtn.querySelector('i');
        
        const isLiked = likeBtn.classList.contains('liked');
        const currentCount = parseInt(likeCount.textContent) || 0;
        
        likeBtn.classList.toggle('liked');
        likeCount.textContent = isLiked ? currentCount - 1 : currentCount + 1;
        likeIcon.className = isLiked ? 'bi bi-heart' : 'bi bi-heart-fill';

        try {
            const response = await fetch('api/social.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'toggle_like',
                    post_id: parseInt(postId),
                    type: 'post'
                })
            });

            const result = await this.handleApiResponse(response);
            
            if (result.success) {
                if (result.data && typeof result.data.likes_count !== 'undefined') {
                    likeCount.textContent = result.data.likes_count;
                    const finalIsLiked = result.data.is_liked;
                    likeBtn.classList.toggle('liked', finalIsLiked);
                    likeIcon.className = finalIsLiked ? 'bi bi-heart-fill' : 'bi bi-heart';
                }
            } else {
                console.log(result.message || 'لم يتم تسجيل الإعجاب');
                element.classList.toggle('liked');
            }
        } catch (error) {
            console.error('خطأ في الإعجاب:', error);
            console.log('لم يتم تسجيل الإعجاب');
            element.classList.toggle('liked');
        }
    }

    async toggleFollow(userId, button) {
        const isFollowing = button.classList.contains('following');
        const buttonText = button.querySelector('.follow-text');
        const buttonIcon = button.querySelector('i');
        
        button.disabled = true;
        buttonText.textContent = isFollowing ? 'جاري إلغاء المتابعة...' : 'جاري المتابعة...';

        try {
            const action = isFollowing ? 'unfollow' : 'follow';
            const method = isFollowing ? 'DELETE' : 'POST';
            
            const response = await fetch('api/follow_fixed.php', {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: action,
                    followed_id: parseInt(userId)
                })
            });

            const result = await this.handleApiResponse(response);
            
            if (result.success) {
                button.classList.toggle('following', result.is_following);
                
                if (result.is_following) {
                    buttonText.textContent = 'يتم المتابعة';
                    buttonIcon.className = 'bi bi-person-check-fill';
                    button.classList.remove('btn-primary');
                    button.classList.add('btn-secondary');
                } else {
                    buttonText.textContent = 'متابعة';
                    buttonIcon.className = 'bi bi-person-plus';
                    button.classList.remove('btn-secondary');
                    button.classList.add('btn-primary');
                }
                
                if (result.followers_count !== undefined) {
                    this.updateFollowersCount(result.followers_count);
                }
                
                console.log(result.message || 'تم تسجيل المتابعة');
            } else {
                console.log(result.message || 'لم يتم تسجيل المتابعة');
                button.textContent = originalText;
            }
        } catch (error) {
            console.error('خطأ في المتابعة:', error);
            console.log('لم يتم تسجيل المتابعة');
            button.textContent = originalText;
        }
    }

   
    async toggleBookmark(postId, element) {
        const bookmarkBtn = element.closest('.bookmark-btn');
        const bookmarkIcon = bookmarkBtn.querySelector('i');
        const isBookmarked = bookmarkBtn.classList.contains('bookmarked');

        try {
            const response = await fetch('api/social.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'toggle_bookmark',
                    post_id: parseInt(postId)
                })
            });

            const result = await this.handleApiResponse(response);
            
            if (result.success) {
                bookmarkBtn.classList.toggle('bookmarked', result.data.is_bookmarked);
                bookmarkIcon.className = result.data.is_bookmarked ? 'bi bi-bookmark-fill' : 'bi bi-bookmark';
                console.log(result.message || 'تم حفظ المنشور');
            } else {
                console.log(result.message || 'لم يتم حفظ المنشور');
                element.classList.toggle('bookmarked');
            }
        } catch (error) {
            console.error('خطأ في المفضلة:', error);
            console.log('لم يتم حفظ المنشور');
            element.classList.toggle('bookmarked');
        }
    }

    
    async loadPosts(filter = 'recent', page = 1) {
        try {
            const response = await fetch(`api/posts_fixed.php?filter=${filter}&page=${page}&limit=10`);
            const result = await this.handleApiResponse(response);
            
            if (result.success && result.posts) {
                if (page === 1) {
                    this.postsContainer.innerHTML = '';
                }
                
                result.posts.forEach(post => {
                    this.addPostToDOM(post);
                });
                
                if (result.posts.length === 0 && page === 1) {
                    this.postsContainer.innerHTML = '<div class="no-posts">لا توجد منشورات لعرضها</div>';
                }
            }
        } catch (error) {
            console.error('خطأ في تحميل المنشورات:', error);
        }
    }

    
    addNewPostToDOM(post) {
        const postElement = this.createPostElement(post);
        if (this.postsContainer) {
            this.postsContainer.insertAdjacentHTML('afterbegin', postElement);
            
            const newPost = this.postsContainer.firstElementChild;
            newPost.style.opacity = '0';
            newPost.style.transform = 'translateY(-20px)';
            
            requestAnimationFrame(() => {
                newPost.style.transition = 'all 0.3s ease';
                newPost.style.opacity = '1';
                newPost.style.transform = 'translateY(0)';
            });
        }
    }

    
    addPostToDOM(post) {
        if (this.postsContainer) {
            const postElement = this.createPostElement(post);
            this.postsContainer.insertAdjacentHTML('beforeend', postElement);
        }
    }

    removePostFromDOM(postId) {
        const postElement = document.getElementById(`post-${postId}`);
        if (postElement) {
            postElement.style.transition = 'all 0.3s ease';
            postElement.style.opacity = '0';
            postElement.style.transform = 'translateX(-100%)';
            
            setTimeout(() => {
                postElement.remove();
            }, 300);
        }
    }

   
    createPostElement(post) {
        const displayName = post.first_name && post.last_name 
            ? `${post.first_name} ${post.last_name}` 
            : post.username;
            
        const avatarUrl = post.avatar_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(post.username)}&background=random&color=fff`;
        
        const timeAgo = post.time_ago || this.formatTimeAgo(post.created_at);
        
        const imageHtml = post.image_url 
            ? `<img src="${post.image_url}" alt="صورة المنشور" class="post-image" loading="lazy">`
            : '';

        return `
            <div class="post" id="post-${post.id}" data-post-id="${post.id}">
                <div class="post-header">
                    <div class="post-user-info">
                        <img src="${avatarUrl}" alt="${post.username}" class="post-avatar">
                        <div class="post-user-details">
                            <h4 class="post-username">${displayName}</h4>
                            <span class="post-time">${timeAgo}</span>
                        </div>
                    </div>
                    ${this.isCurrentUserPost(post.user_id) ? 
                        `<div class="post-actions-menu">
                            <button class="delete-post" data-post-id="${post.id}" title="حذف المنشور">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>` : ''
                    }
                </div>
                
                <div class="post-content">
                    <p>${this.formatContent(post.content)}</p>
                    ${imageHtml}
                </div>
                
                <div class="post-footer">
                    <div class="post-stats">
                        <span class="stat-item">
                            <i class="bi bi-heart"></i>
                            ${post.likes_count || 0} إعجاب
                        </span>
                        <span class="stat-item">
                            <i class="bi bi-chat"></i>
                            ${post.comments_count || 0} تعليق
                        </span>
                    </div>
                    
                    <div class="post-actions">
                        <button class="post-action like-btn ${post.user_liked ? 'liked' : ''}" data-post-id="${post.id}">
                            <i class="bi bi-heart${post.user_liked ? '-fill' : ''}"></i>
                            <span class="like-count">${post.likes_count || 0}</span>
                        </button>
                        
                        <button class="post-action comment-btn" data-post-id="${post.id}">
                            <i class="bi bi-chat"></i>
                            تعليق
                        </button>
                        
                        <button class="post-action bookmark-btn ${post.user_bookmarked ? 'bookmarked' : ''}" data-post-id="${post.id}">
                            <i class="bi bi-bookmark${post.user_bookmarked ? '-fill' : ''}"></i>
                            حفظ
                        </button>
                        
                        <button class="post-action share-btn" data-post-id="${post.id}">
                            <i class="bi bi-share"></i>
                            مشاركة
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

   
    async handleApiResponse(response) {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const text = await response.text();
        
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error('استجابة غير صحيحة من الخادم:', text);
            throw new Error('استجابة غير صحيحة من الخادم');
        }
    }

    
    getPostId(element) {
        return element.closest('[data-post-id]')?.dataset.postId || 
               element.closest('.post')?.dataset.postId ||
               element.dataset.postId;
    }

    isCurrentUserPost(userId) {
        return window.currentUserId && parseInt(userId) === parseInt(window.currentUserId);
    }

    formatContent(content) {
        if (!content) return '';
        
        return content
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;')
            .replace(/\n/g, '<br>')
            .replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank" rel="noopener">$1</a>')
            .replace(/#([^\s]+)/g, '<span class="hashtag">#$1</span>')
            .replace(/@([^\s]+)/g, '<span class="mention">@$1</span>');
    }

    formatTimeAgo(datetime) {
        const now = new Date();
        const postTime = new Date(datetime);
        const diffInSeconds = Math.floor((now - postTime) / 1000);
        
        if (diffInSeconds < 60) return 'منذ لحظات';
        if (diffInSeconds < 3600) return `منذ ${Math.floor(diffInSeconds / 60)} دقيقة`;
        if (diffInSeconds < 86400) return `منذ ${Math.floor(diffInSeconds / 3600)} ساعة`;
        if (diffInSeconds < 2592000) return `منذ ${Math.floor(diffInSeconds / 86400)} يوم`;
        if (diffInSeconds < 31536000) return `منذ ${Math.floor(diffInSeconds / 2592000)} شهر`;
        return `منذ ${Math.floor(diffInSeconds / 31536000)} سنة`;
    }

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    updatePostsCount(change) {
        const postsCountElement = document.querySelector('.posts-count');
        if (postsCountElement) {
            const currentCount = parseInt(postsCountElement.textContent) || 0;
            postsCountElement.textContent = Math.max(0, currentCount + change);
        }
    }

    updateFollowersCount(count) {
        const followersCountElement = document.querySelector('.followers-count');
        if (followersCountElement) {
            followersCountElement.textContent = count;
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.socialApp = new SocialApp();
    
    const userIdMeta = document.querySelector('meta[name="user-id"]');
    if (userIdMeta) {
        window.currentUserId = userIdMeta.content;
    }
});

function toggleLike(element) {
    if (window.socialApp) {
        const postId = window.socialApp.getPostId(element);
        if (postId) window.socialApp.toggleLike(postId, element);
    }
}

function deletePost(postId) {
    if (window.socialApp) {
        window.socialApp.deletePost(postId);
    }
}

function followUser(userId, element) {
    if (window.socialApp) {
        window.socialApp.toggleFollow(userId, element);
    }
} 