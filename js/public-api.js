
async function fetchPosts(page = 1, limit = 10) {
    try {
        const response = await fetch(`/WEP/api/public_posts.php?page=${page}&limit=${limit}`);
        if (!response.ok) {
            throw new Error(`خطأ في الاستجابة: ${response.status}`);
        }
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('خطأ في جلب المنشورات:', error);
        return { success: false, error: error.message, posts: [] };
    }
}

function displayPosts(posts) {
    const feedContainer = document.querySelector('.feed-container');
    if (!feedContainer) return;

    const emptyFeed = feedContainer.querySelector('.empty-feed');
    if (emptyFeed && posts.length > 0) {
        emptyFeed.style.display = 'none';
    }

    posts.forEach(post => {
        if (document.getElementById(`post-${post.id}`)) {
            return;
        }

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
    
    document.querySelectorAll('.dislike-btn:not(.activated)').forEach(btn => {
        btn.classList.add('activated');
        btn.addEventListener('click', function() {
            const postId = this.getAttribute('data-post-id');
            
            this.classList.toggle('disliked');
            
            const likeBtn = document.querySelector(`.like-btn[data-post-id="${postId}"]`);
            if (likeBtn && likeBtn.classList.contains('liked')) {
                likeBtn.classList.remove('liked');
            }
            
            const dislikesCountElement = document.querySelector(`#post-${postId} .dislikes span`);
            if (dislikesCountElement) {
                let dislikesCount = parseInt(dislikesCountElement.textContent);
                dislikesCount = this.classList.contains('disliked') ? dislikesCount + 1 : dislikesCount - 1;
                dislikesCountElement.textContent = dislikesCount;
            }
            
            console.log(`تم ${this.classList.contains('disliked') ? 'عدم الإعجاب' : 'إلغاء عدم الإعجاب'} بالمنشور رقم ${postId}`);
        });
    });
    
    document.querySelectorAll('.comment-btn:not(.activated)').forEach(btn => {
        btn.classList.add('activated');
        btn.addEventListener('click', function() {
            const postId = this.getAttribute('data-post-id');
            const commentsSection = document.getElementById(`comments-${postId}`);
            
            if (commentsSection) {
                commentsSection.style.display = commentsSection.style.display === 'none' || !commentsSection.style.display ? 'block' : 'none';
                
                if (commentsSection.style.display === 'block') {
                    const commentInput = commentsSection.querySelector('.comment-input');
                    if (commentInput) {
                        commentInput.focus();
                    }
                }
            }
        });
    });
    
    document.querySelectorAll('.share-btn:not(.activated)').forEach(btn => {
        btn.classList.add('activated');
        btn.addEventListener('click', function() {
            const postId = this.getAttribute('data-post-id');
            alert('تم فتح خيارات مشاركة المنشور رقم ' + postId);
        });
    });
}

document.addEventListener('DOMContentLoaded', async function() {
    console.log('تحميل المنشورات من واجهة API العامة...');
    const response = await fetchPosts();
    
    if (response.success && response.posts && response.posts.length > 0) {
        console.log('تم جلب المنشورات بنجاح:', response.posts.length);
        displayPosts(response.posts);
        
        const loadMoreBtn = document.getElementById('load-more-posts');
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', async function() {
                const page = parseInt(this.getAttribute('data-page') || 2);
                console.log('تحميل المزيد من المنشورات، الصفحة:', page);
                
                const morePostsResponse = await fetchPosts(page);
                if (morePostsResponse.success && morePostsResponse.posts && morePostsResponse.posts.length > 0) {
                    displayPosts(morePostsResponse.posts);
                    this.setAttribute('data-page', page + 1);
                    
                    if (!morePostsResponse.has_more) {
                        this.style.display = 'none';
                    }
                } else {
                    this.style.display = 'none';
                }
            });
        }
    } else {
        console.error('فشل في جلب المنشورات:', response.error || 'خطأ غير معروف');
    }
    
    const submitPostBtn = document.getElementById('submit-post');
    if (submitPostBtn) {
        submitPostBtn.addEventListener('click', async function() {
            const content = document.getElementById('post-content-textarea').value.trim();
            if (!content) {
                alert('يرجى كتابة محتوى للمنشور');
                return;
            }
            
            try {
                const response = await fetch('/WEP/api/public_posts.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ content })
                });
                
                const data = await response.json();
                if (data.success) {
                    console.log('تم إنشاء المنشور بنجاح:', data);
                    
                    const createPostModal = document.getElementById('create-post-modal');
                    if (createPostModal) {
                        createPostModal.style.display = 'none';
                        document.body.style.overflow = '';
                    }
                    
                    document.getElementById('post-content-textarea').value = '';
                    
                    if (data.post) {
                        displayPosts([data.post]);
                    }
                    
                } else {
                    alert('فشل في إنشاء المنشور: ' + (data.error || 'خطأ غير معروف'));
                }
            } catch (error) {
                console.error('خطأ في إرسال المنشور:', error);
                alert('حدث خطأ أثناء إنشاء المنشور');
            }
        });
    }
});
