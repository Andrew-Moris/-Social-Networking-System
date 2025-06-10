
async function fetchUserProfile(username) {
    try {
        const response = await fetch(`/WEP/api/public_users.php?username=${encodeURIComponent(username)}`);
        if (!response.ok) {
            throw new Error(`خطأ في الاستجابة: ${response.status}`);
        }
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('خطأ في جلب بيانات المستخدم:', error);
        return { success: false, error: error.message };
    }
}

function displayUserProfile(userData) {
    if (!userData || !userData.user) {
        console.error('بيانات المستخدم غير متوفرة');
        return;
    }
    
    const user = userData.user;
    
    const profileHeader = document.querySelector('.profile-header');
    if (profileHeader) {
        const profileAvatar = profileHeader.querySelector('.profile-avatar img');
        if (profileAvatar) {
            profileAvatar.src = user.avatar_url || '/WEP/assets/images/default-avatar.png';
            profileAvatar.alt = user.username;
        }
        
        const profileUsername = profileHeader.querySelector('.profile-username');
        if (profileUsername) {
            profileUsername.textContent = user.username;
        }
        
        const profileBio = profileHeader.querySelector('.profile-bio');
        if (profileBio) {
            profileBio.textContent = user.bio || 'لا توجد سيرة ذاتية';
        }
        
        const postsCount = profileHeader.querySelector('.profile-stat-posts .stat-value');
        if (postsCount) {
            postsCount.textContent = user.posts_count || 0;
        }
        
        const followersCount = profileHeader.querySelector('.profile-stat-followers .stat-value');
        if (followersCount) {
            followersCount.textContent = user.followers_count || 0;
        }
        
        const followingCount = profileHeader.querySelector('.profile-stat-following .stat-value');
        if (followingCount) {
            followingCount.textContent = user.following_count || 0;
        }
    }
    
    if (userData.posts && userData.posts.length > 0) {
        displayPosts(userData.posts);
    }
}

function displayPosts(posts) {
    const feedContainer = document.querySelector('.feed-container');
    if (!feedContainer) return;
    
    const emptyFeed = feedContainer.querySelector('.empty-feed');
    if (emptyFeed && posts.length > 0) {
        emptyFeed.style.display = 'none';
    }
    
    const existingPosts = feedContainer.querySelectorAll('.post');
    if (existingPosts.length > 0) {
        existingPosts.forEach(post => post.remove());
    }
    
    posts.forEach(post => {
        const userReaction = post.user_reaction || null;
        const isLiked = userReaction === 'like';
        const isDisliked = userReaction === 'dislike';
        
        const postElement = document.createElement('div');
        postElement.className = 'post';
        postElement.id = `post-${post.id}`;
        
        const postDate = new Date(post.created_at);
        const formattedDate = postDate.toLocaleDateString('ar-SA', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
        
        postElement.innerHTML = `
            <div class="post-header">
                <img src="${post.avatar_url || '/WEP/assets/images/default-avatar.png'}" 
                     class="user-avatar" alt="${post.username}">
                <div class="post-user-info">
                    <h6 class="post-username">
                        <a href="/WEP/index.php?username=${encodeURIComponent(post.username)}">
                            ${post.username}
                        </a>
                    </h6>
                    <div class="post-time">${formattedDate}</div>
                </div>
                <div class="dropdown">
                    <button class="btn btn-sm btn-light" type="button" id="post-options-${post.id}" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="post-options-${post.id}">
                        <li><a class="dropdown-item" href="#"><i class="fas fa-bookmark me-2"></i> حفظ المنشور</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-flag me-2"></i> الإبلاغ عن المنشور</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="post-content">
                ${post.content ? `<div class="post-text">${post.content.replace(/\n/g, '<br>')}</div>` : ''}
                ${post.image_url ? `<img src="${post.image_url}" class="post-image" alt="صورة المنشور">` : ''}
            </div>
            
            <div class="post-stats">
                <div class="post-reactions">
                    <div class="reaction-group likes">
                        <i class="fas fa-thumbs-up text-primary"></i>
                        <span>${post.likes_count || 0}</span>
                    </div>
                    <div class="reaction-group dislikes">
                        <i class="fas fa-thumbs-down text-danger"></i>
                        <span>${post.dislikes_count || 0}</span>
                    </div>
                </div>
                <div class="comments-count">
                    ${post.comments_count > 0 ? `<span>${post.comments_count} تعليقات</span>` : ''}
                </div>
            </div>
            
            <div class="post-actions">
                <button class="post-action-btn like-btn ${isLiked ? 'liked' : ''}" data-post-id="${post.id}">
                    <i class="fas fa-thumbs-up"></i>
                    <span>إعجاب</span>
                </button>
                <button class="post-action-btn dislike-btn ${isDisliked ? 'disliked' : ''}" data-post-id="${post.id}">
                    <i class="fas fa-thumbs-down"></i>
                    <span>عدم إعجاب</span>
                </button>
                <button class="post-action-btn comment-btn" data-post-id="${post.id}">
                    <i class="fas fa-comment"></i>
                    <span>تعليق</span>
                </button>
                <button class="post-action-btn share-btn" data-post-id="${post.id}">
                    <i class="fas fa-share"></i>
                    <span>مشاركة</span>
                </button>
            </div>
            
            <div class="post-comments" id="comments-${post.id}" style="display: none;">
                <!-- سيتم إضافة التعليقات ديناميكيًا -->
                <div class="comment-input-container">
                    <input type="text" class="comment-input" placeholder="اكتب تعليقاً..." data-post-id="${post.id}">
                    <button class="comment-submit-btn" data-post-id="${post.id}">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        `;
        
        const loadMoreBtn = feedContainer.querySelector('.load-more-btn');
        if (loadMoreBtn) {
            loadMoreBtn.parentElement.before(postElement);
        } else {
            feedContainer.appendChild(postElement);
        }
    });
    
    activatePostInteractions();
}

function activatePostInteractions() {
    document.querySelectorAll('.like-btn:not(.activated)').forEach(btn => {
        btn.classList.add('activated');
        btn.addEventListener('click', function() {
            const postId = this.getAttribute('data-post-id');
            
            this.classList.toggle('liked');
            
            const dislikeBtn = document.querySelector(`.dislike-btn[data-post-id="${postId}"]`);
            if (dislikeBtn && dislikeBtn.classList.contains('disliked')) {
                dislikeBtn.classList.remove('disliked');
            }
            
            const likesCountElement = document.querySelector(`#post-${postId} .likes span`);
            if (likesCountElement) {
                let likesCount = parseInt(likesCountElement.textContent);
                likesCount = this.classList.contains('liked') ? likesCount + 1 : likesCount - 1;
                likesCountElement.textContent = likesCount;
            }
            
            console.log(`تم ${this.classList.contains('liked') ? 'الإعجاب' : 'إلغاء الإعجاب'} بالمنشور رقم ${postId}`);
        });
    });

document.addEventListener('DOMContentLoaded', async function() {
    const urlParams = new URLSearchParams(window.location.search);
    const username = urlParams.get('username');
    
    if (username) {
        console.log('جاري جلب بيانات المستخدم:', username);
        const userData = await fetchUserProfile(username);
        
        if (userData.success && userData.user) {
            console.log('تم جلب بيانات المستخدم بنجاح:', userData.user.username);
            displayUserProfile(userData);
        } else {
            console.error('فشل في جلب بيانات المستخدم:', userData.error || 'خطأ غير معروف');
            const mainContent = document.querySelector('.main-content');
            if (mainContent) {
                mainContent.innerHTML = `
                    <div class="alert alert-danger text-center mt-4">
                        <i class="fas fa-exclamation-circle fa-3x mb-3"></i>
                        <h4>عذراً، لم يتم العثور على المستخدم</h4>
                        <p>المستخدم "${username}" غير موجود أو قد تمت إزالة الحساب.</p>
                        <a href="/WEP/index.php" class="btn btn-primary mt-3">العودة إلى الصفحة الرئيسية</a>
                    </div>
                `;
            }
        }
    } else {
        console.log('لم يتم تحديد اسم مستخدم في عنوان URL');
    }
});
