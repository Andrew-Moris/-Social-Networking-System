
console.log('🔧 تطبيق إصلاح التعليقات...');

window.toggleComments = async function(postId) {
    console.log('🔄 Toggling comments for post:', postId);
    
    const commentsSection = document.getElementById(`comments-${postId}`);
    console.log('📦 Comments section found:', !!commentsSection);
    
    if (commentsSection) {
        const isHidden = commentsSection.style.display === 'none' || commentsSection.style.display === '';
        console.log('👁️ Is hidden:', isHidden);
        
        if (isHidden) {
            console.log('👁️ Showing comments section');
            commentsSection.style.display = 'block';
            await loadComments(postId);
        } else {
            console.log('🙈 Hiding comments section');
            commentsSection.style.display = 'none';
        }
    } else {
        console.error('❌ Comments section not found for post:', postId);
    }
};

window.loadComments = async function(postId) {
    console.log('📥 Loading comments for post:', postId);
    
    const container = document.getElementById(`comments-container-${postId}`);
    console.log('📦 Container found:', !!container);
    
    if (!container) {
        console.error('❌ Comments container not found for post:', postId);
        return;
    }
    
    container.innerHTML = '<div style="text-align:center;padding:20px;color:#ccc;"><i class="bi bi-hourglass-split"></i> Loading comments...</div>';
    
    try {
        const response = await fetch(`api/social.php?action=get_comments&post_id=${postId}`);
        console.log('📡 API Response status:', response.status);
        
        const result = await response.json();
        console.log('📊 API Result:', result);
        
        if (result.success && result.data && result.data.comments) {
            console.log('💬 Found comments:', result.data.comments.length);
            
            if (result.data.comments.length === 0) {
                container.innerHTML = '<div style="text-align:center;padding:20px;color:#ccc;">No comments yet</div>';
            } else {
                const currentUserId = parseInt(document.querySelector('meta[name="user-id"]').content) || 0;
                console.log('👤 Current user ID:', currentUserId);
                
                container.innerHTML = result.data.comments.map(comment => `
                    <div style='padding:15px;border-bottom:1px solid rgba(255,255,255,0.1);'>
                        <div style='display:flex;gap:10px;'>
                            <img src='${comment.avatar_url || "https://ui-avatars.com/api/?name=" + encodeURIComponent(comment.username) + "&background=667eea&color=fff&size=40"}' 
                                 alt='${comment.username}' style='width:40px;height:40px;border-radius:50%;object-fit:cover;'>
                            <div style='flex:1;'>
                                <div style='display:flex;align-items:center;gap:10px;margin-bottom:5px;'>
                                    <span style='font-weight:600;color:white;'>${comment.first_name} ${comment.last_name}</span>
                                    <span style='color:#999;font-size:0.9rem;'>@${comment.username}</span>
                                    <span style='color:#666;font-size:0.8rem;'>${comment.created_at}</span>
                                </div>
                                <p style='color:white;margin:5px 0;'>${comment.content}</p>
                                <div style='display:flex;gap:10px;margin-top:8px;'>
                                    <button onclick='toggleCommentLike(${comment.id}, this)' style='background:none;border:none;color:#999;cursor:pointer;font-size:0.8rem;'>
                                        <i class='bi bi-heart${comment.user_liked ? "-fill" : ""}'></i>
                                        <span>${comment.like_count}</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `).join('');
            }
        } else {
            console.log('❌ API returned error or no data:', result.message || 'Unknown error');
            container.innerHTML = '<div style="text-align:center;padding:20px;color:#f44;">❌ Failed to load comments</div>';
        }
    } catch (error) {
        console.error('❌ Error loading comments:', error);
        container.innerHTML = '<div style="text-align:center;padding:20px;color:#f44;">❌ Error loading comments</div>';
    }
};

document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 Setting up comment button listeners...');
    
    const commentButtons = document.querySelectorAll('[data-action="comment"]');
    console.log('🔍 Found comment buttons:', commentButtons.length);
    
    commentButtons.forEach((btn, index) => {
        console.log(`🔘 Setting up comment button ${index + 1}`);
        
        btn.removeAttribute('onclick');
        
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const postCard = this.closest('[data-post-id]');
            if (!postCard) {
                console.error('❌ Could not find post card for comment button');
                return;
            }
            
            const postId = postCard.getAttribute('data-post-id');
            console.log('🔄 Opening comments for post:', postId);
            
            toggleComments(postId);
        });
    });
    
    console.log('✅ Comment buttons setup complete!');
});

console.log('✅ إصلاح التعليقات تم تحميله بنجاح!');
