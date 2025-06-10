

document.addEventListener('DOMContentLoaded', function() {
    fixPostImages();
    
    observeDOM();
});


function fixPostImages() {
    console.log('Fixing post images...');
    
    const postImages = document.querySelectorAll('.post-media, .post-card img, .post-content img, [data-post-id] img');
    
    if (postImages.length > 0) {
        console.log(`Found ${postImages.length} post images to fix`);
    } else {
        console.log('No post images found');
        
        setTimeout(() => {
            const delayedImages = document.querySelectorAll('.post-media, .post-card img, .post-content img, [data-post-id] img');
            if (delayedImages.length > 0) {
                console.log(`Found ${delayedImages.length} post images after delay`);
                fixDelayedImages(delayedImages);
            }
        }, 1000);
        
        return;
    }
    
    postImages.forEach(function(img, index) {
        if (img.tagName.toLowerCase() === 'img') {
            const originalSrc = img.getAttribute('src');
            console.log(`Image ${index + 1} original src: ${originalSrc}`);
            
            if (originalSrc) {
                if (originalSrc.startsWith('uploads/')) {
                    img.src = '/WEP/' + originalSrc;
                    console.log(`Image ${index + 1} fixed src: ${img.src}`);
                } else if (!originalSrc.startsWith('/') && !originalSrc.startsWith('http')) {
                    img.src = '/WEP/' + originalSrc;
                    console.log(`Image ${index + 1} fixed src: ${img.src}`);
                }
            }
        }
    });
}

/**
 * @param {NodeList} images 
 */
function fixDelayedImages(images) {
    images.forEach(function(img, index) {
        if (img.tagName.toLowerCase() === 'img') {
            const originalSrc = img.getAttribute('src');
            console.log(`Delayed image ${index + 1} original src: ${originalSrc}`);
            
            if (originalSrc) {
                if (originalSrc.startsWith('uploads/')) {
                    img.src = '/WEP/' + originalSrc;
                    console.log(`Delayed image ${index + 1} fixed src: ${img.src}`);
                } else if (!originalSrc.startsWith('/') && !originalSrc.startsWith('http')) {
                    img.src = '/WEP/' + originalSrc;
                    console.log(`Delayed image ${index + 1} fixed src: ${img.src}`);
                }
            }
        }
    });
}

function observeDOM() {
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes && mutation.addedNodes.length > 0) {
                let hasNewPosts = false;
                
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1 && (
                        node.classList && node.classList.contains('post-card') ||
                        node.querySelector && node.querySelector('.post-card, .post-media')
                    )) {
                        hasNewPosts = true;
                    }
                });
                
                if (hasNewPosts) {
                    console.log('New posts detected, fixing images...');
                    setTimeout(fixPostImages, 100);
                }
            }
        });
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
}


function fixPostDisplay() {
    const postContainers = document.querySelectorAll('#userPostsContainer, .feed-posts, .posts-container');
    
    postContainers.forEach(container => {
        const posts = container.querySelectorAll('.post-card');
        
        if (posts.length === 0) {
            console.log(`No posts found in container ${container.id || 'unknown'}`);
            
            const noPostsMessage = document.getElementById('noPostsMessage');
            if (noPostsMessage) {
                noPostsMessage.style.display = 'block';
            }
        } else {
            console.log(`Found ${posts.length} posts in container ${container.id || 'unknown'}`);
            
            const noPostsMessage = document.getElementById('noPostsMessage');
            if (noPostsMessage) {
                noPostsMessage.style.display = 'none';
            }
        }
    });
}


function reloadPosts() {
    console.log('Reloading posts...');
    
    if (typeof loadUserPosts === 'function') {
        loadUserPosts();
    } else {
        console.log('loadUserPosts function not found');
        
        const userId = document.body.getAttribute('data-user-id') || 
                      document.querySelector('meta[name="user-id"]')?.getAttribute('content');
        
        if (userId) {
            fetch(`api/posts.php?action=get_user_posts&user_id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    console.log('Posts loaded manually:', data);
                    
                    if (data.success && data.posts) {
                        updatePostsInPage(data.posts);
                    }
                })
                .catch(error => {
                    console.error('Error loading posts:', error);
                });
        }
    }
}


function updatePostsInPage(posts) {
    const postsContainer = document.getElementById('userPostsContainer');
    if (!postsContainer) return;
    
    postsContainer.innerHTML = '';
    
    if (posts.length === 0) {
        const noPostsMessage = document.getElementById('noPostsMessage');
        if (noPostsMessage) {
            noPostsMessage.style.display = 'block';
        }
        return;
    }
    
    const noPostsMessage = document.getElementById('noPostsMessage');
    if (noPostsMessage) {
        noPostsMessage.style.display = 'none';
    }
    
    posts.forEach(post => {
        const postElement = document.createElement('div');
        postElement.className = 'post-card';
        postElement.setAttribute('data-post-id', post.id);
        
        const postDate = new Date(post.created_at);
        const formattedDate = formatTimeAgo(post.created_at);
        
        const currentUserId = document.body.getAttribute('data-user-id');
        const isCurrentUserPost = post.user_id === parseInt(currentUserId);
        
        let postContent = '';
        
        postContent += `
            <div class="post-header">
                <div class="post-user-info">
                    <img src="${post.avatar || 'assets/images/default-avatar.png'}" alt="${post.username}" class="post-avatar">
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
            const mediaUrl = post.media_url || post.image_url;
            console.log('Media URL:', mediaUrl); 
            
            const isVideo = mediaUrl.match(/\.(mp4|webm|ogg)$/i);
            
            if (isVideo) {
                postContent += `
                    <div class="media-container">
                        <video src="${mediaUrl}" controls class="post-media"></video>
                    </div>`;
            } else {
                postContent += `
                    <div class="media-container">
                        <img src="${mediaUrl}" alt="صورة المنشور" class="post-media" onclick="showImageModal('${mediaUrl}')">
                    </div>`;
            }
        }
        
        postContent += `</div>`;
        
        postContent += `
            <div class="post-interactions">
                <button class="interaction-button ${post.is_liked ? 'active' : ''}" onclick="likePost(${post.id})">
                    <i class="bi bi-heart${post.is_liked ? '-fill' : ''}"></i>
                    <span>${post.likes_count || 0}</span>
                </button>
                <button class="interaction-button" onclick="showComments(${post.id})">
                    <i class="bi bi-chat"></i>
                    <span>${post.comments_count || 0}</span>
                </button>
                <button class="interaction-button" onclick="sharePost(${post.id})">
                    <i class="bi bi-share"></i>
                </button>
                <button class="interaction-button ${post.is_bookmarked ? 'active' : ''}" onclick="bookmarkPost(${post.id})">
                    <i class="bi bi-bookmark${post.is_bookmarked ? '-fill' : ''}"></i>
                </button>
            </div>`;
        
        postElement.innerHTML = postContent;
        postsContainer.appendChild(postElement);
    });
    
    setTimeout(fixPostImages, 100);
}


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

document.addEventListener('DOMContentLoaded', function() {
    fixPostImages();
    
    fixPostDisplay();
    
    setTimeout(reloadPosts, 1000);
});
