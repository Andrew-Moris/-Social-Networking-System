

document.addEventListener('DOMContentLoaded', function() {
    const notificationsToggle = document.getElementById('notifications-toggle');
    const notificationsDropdown = document.getElementById('notifications-dropdown');
    
    const messagesToggle = document.getElementById('messages-toggle');
    const messagesDropdown = document.getElementById('messages-dropdown');
    
    const userMenuToggle = document.getElementById('user-menu-toggle');
    const userDropdown = document.getElementById('user-dropdown');
    
    if (notificationsToggle && notificationsDropdown) {
        notificationsToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            notificationsDropdown.style.display = notificationsDropdown.style.display === 'block' ? 'none' : 'block';
            
            if (messagesDropdown) messagesDropdown.style.display = 'none';
            if (userDropdown) userDropdown.style.display = 'none';
        });
    }
    
    if (messagesToggle && messagesDropdown) {
        messagesToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            messagesDropdown.style.display = messagesDropdown.style.display === 'block' ? 'none' : 'block';
            
            if (notificationsDropdown) notificationsDropdown.style.display = 'none';
            if (userDropdown) userDropdown.style.display = 'none';
        });
    }
    
    if (userMenuToggle && userDropdown) {
        userMenuToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdown.style.display = userDropdown.style.display === 'block' ? 'none' : 'block';
            
            if (notificationsDropdown) notificationsDropdown.style.display = 'none';
            if (messagesDropdown) messagesDropdown.style.display = 'none';
        });
    }
    
    document.addEventListener('click', function() {
        if (notificationsDropdown) notificationsDropdown.style.display = 'none';
        if (messagesDropdown) messagesDropdown.style.display = 'none';
        if (userDropdown) userDropdown.style.display = 'none';
    });
    
    
    const likeBtns = document.querySelectorAll('.like-btn');
    likeBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const postId = this.getAttribute('data-post-id');
            
            this.classList.toggle('liked');
            
            const dislikeBtn = document.querySelector(`.dislike-btn[data-post-id="${postId}"]`);
            if (dislikeBtn && dislikeBtn.classList.contains('disliked')) {
                dislikeBtn.classList.remove('disliked');
            }
            
            console.log('تم الإعجاب بالمنشور رقم ' + postId);
        });
    });
    
    const dislikeBtns = document.querySelectorAll('.dislike-btn');
    dislikeBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const postId = this.getAttribute('data-post-id');
            
            this.classList.toggle('disliked');
            
            const likeBtn = document.querySelector(`.like-btn[data-post-id="${postId}"]`);
            if (likeBtn && likeBtn.classList.contains('liked')) {
                likeBtn.classList.remove('liked');
            }
            
            console.log('تم عدم الإعجاب بالمنشور رقم ' + postId);
        });
    });
    
    const commentBtns = document.querySelectorAll('.comment-btn');
    commentBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const postId = this.getAttribute('data-post-id');
            const commentsSection = document.getElementById(`comments-${postId}`);
            
            if (commentsSection) {
                if (commentsSection.style.display === 'none' || !commentsSection.style.display) {
                    commentsSection.style.display = 'block';
                    
                    const commentInput = commentsSection.querySelector('.comment-input');
                    if (commentInput) {
                        commentInput.focus();
                    }
                } else {
                    commentsSection.style.display = 'none';
                }
            }
        });
    });
    
    const shareBtns = document.querySelectorAll('.share-btn');
    shareBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const postId = this.getAttribute('data-post-id');
            
            alert('تم فتح خيارات مشاركة المنشور رقم ' + postId);
        });
    });
    
    const followBtns = document.querySelectorAll('.follow-btn');
    followBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            const isFollowing = this.classList.contains('following');
            
            if (isFollowing) {
                this.classList.remove('following');
                this.textContent = 'متابعة';
            } else {
                this.classList.add('following');
                this.textContent = 'إلغاء المتابعة';
            }
            
            console.log(`تم ${isFollowing ? 'إلغاء متابعة' : 'متابعة'} المستخدم رقم ${userId}`);
        });
    });
    
    const createPostTrigger = document.getElementById('create-post-trigger');
    const createPostModal = document.getElementById('create-post-modal');
    const closeCreatePost = document.getElementById('close-create-post');
    const cancelPost = document.getElementById('cancel-post');
    
    if (createPostTrigger && createPostModal) {
        createPostTrigger.addEventListener('click', function() {
            createPostModal.style.display = 'flex';
            document.body.style.overflow = 'hidden'; 
        });
    }
    
    if (closeCreatePost && createPostModal) {
        closeCreatePost.addEventListener('click', function() {
            createPostModal.style.display = 'none';
            document.body.style.overflow = ''; 
        });
    }
    
    if (cancelPost && createPostModal) {
        cancelPost.addEventListener('click', function() {
            createPostModal.style.display = 'none';
            document.body.style.overflow = '';
        });
    }
    
    if (createPostModal) {
        createPostModal.addEventListener('click', function(e) {
            if (e.target === createPostModal) {
                createPostModal.style.display = 'none';
                document.body.style.overflow = '';
            }
        });
    }
});
