<?php

require_once 'config.php';
require_once 'functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

$user = null;
$bookmarked_posts = [];
$error_message = null;

try {
    if (!isset($pdo)) {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        session_destroy();
        header('Location: login.php');
        exit;
    }
    
    $tables_stmt = $pdo->query("SHOW TABLES LIKE 'bookmarks'");
    if ($tables_stmt->rowCount() == 0) {
        $pdo->exec("
            CREATE TABLE bookmarks (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                post_id INT UNSIGNED NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_post_id (post_id),
                UNIQUE KEY unique_bookmark (user_id, post_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
    
    $tables_stmt = $pdo->query("SHOW TABLES LIKE 'favorites'");
    if ($tables_stmt->rowCount() == 0) {
        $pdo->exec("
            CREATE TABLE favorites (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                post_id INT UNSIGNED NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_post_id (post_id),
                UNIQUE KEY unique_favorite (user_id, post_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
    
    $stmt = $pdo->prepare("
        SELECT p.*, u.username, u.first_name, u.last_name, u.profile_picture,
            b.created_at as bookmarked_at
        FROM posts p
        JOIN bookmarks b ON p.id = b.post_id
        JOIN users u ON p.user_id = u.id
        WHERE b.user_id = ?
        ORDER BY b.created_at DESC
    ");
    $stmt->execute([$user_id]);
    
    error_log("SQL Query executed for user ID: " . $user_id);
    $bookmarked_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Bookmarked posts count: " . count($bookmarked_posts));
    if (count($bookmarked_posts) > 0) {
        error_log("First post data: " . json_encode($bookmarked_posts[0]));
    }
    
} catch (PDOException $e) {
    error_log("Database error in bookmarks.php: " . $e->getMessage());
    $error_message = "حدث خطأ أثناء تحميل البيانات";
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> | Bookmarks</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --warning-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            --danger-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            
            --bg-primary: #0a0f1c;
            --bg-secondary: #1a1f2e;
            --bg-card: rgba(255, 255, 255, 0.05);
            --bg-card-hover: rgba(255, 255, 255, 0.1);
            --border-color: rgba(255, 255, 255, 0.1);
            --text-primary: #ffffff;
            --text-secondary: #a1a8b3;
            --text-muted: #6b7280;
            
            --shadow-primary: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
            --shadow-card: 0 10px 15px -3px rgba(0, 0, 0, 0.2), 0 4px 6px -2px rgba(0, 0, 0, 0.1);
            --blur-glass: blur(20px);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-primary);
            background-image: 
                radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(120, 219, 255, 0.2) 0%, transparent 50%);
            color: var(--text-primary);
            min-height: 100vh;
            line-height: 1.6;
            overflow-x: hidden;
        }
        
        .glass-morphism {
            background: var(--bg-card);
            backdrop-filter: var(--blur-glass);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-card);
        }
        
        .nav-header {
            background: rgba(10, 15, 28, 0.9);
            backdrop-filter: var(--blur-glass);
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 1000;
            padding: 1rem 0;
        }
        
        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .page-title {
            font-size: 3rem;
            font-weight: 800;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }
        
        .page-subtitle {
            color: var(--text-secondary);
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .posts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(500px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .post-card {
            background: var(--bg-card);
            backdrop-filter: var(--blur-glass);
            border: 1px solid var(--border-color);
            border-radius: 1.5rem;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }
        
        .post-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-primary);
            border-color: rgba(102, 126, 234, 0.3);
            background: var(--bg-card-hover);
        }
        
        .post-header {
            display: flex;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: 2px solid rgba(102, 126, 234, 0.3);
            cursor: pointer;
            transition: all 0.3s ease;
            margin-right: 1rem;
        }
        
        .user-avatar:hover {
            transform: scale(1.1);
            border-color: rgba(102, 126, 234, 0.6);
        }
        
        .user-info h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
            cursor: pointer;
            transition: color 0.3s ease;
        }
        
        .user-info h3:hover {
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .user-info .username {
            color: var(--text-secondary);
            font-size: 0.9rem;
            cursor: pointer;
            transition: color 0.3s ease;
        }
        
        .user-info .username:hover {
            color: #667eea;
        }
        
        .post-time {
            margin-left: auto;
            color: var(--text-muted);
            font-size: 0.85rem;
        }
        
        .post-content {
            padding: 1.5rem;
            line-height: 1.6;
            color: var(--text-primary);
        }
        
        .post-media {
            width: 100%;
            max-height: 300px;
            object-fit: cover;
            margin-top: 1rem;
            border-radius: 0.75rem;
        }
        
        .post-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--border-color);
            background: rgba(0, 0, 0, 0.1);
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
        }
        
        .action-button {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border: none;
            background: transparent;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.3s ease;
            border-radius: 0.75rem;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .action-button:hover {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
        }
        
        .action-button.liked {
            color: #ef4444;
        }
        
        .action-button.liked:hover {
            background: rgba(239, 68, 68, 0.1);
        }
        
        .action-button.favorited {
            color: #fbbf24;
        }
        
        .action-button.favorited:hover {
            background: rgba(251, 191, 36, 0.1);
        }
        
        .bookmark-badge {
            background: var(--warning-gradient);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .remove-bookmark-btn {
            background: var(--danger-gradient);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.75rem;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .remove-bookmark-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(250, 112, 154, 0.3);
        }
        
        .remove-bookmark-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .remove-bookmark-btn.removing {
            background: #666 !important;
            animation: pulse 1.5s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        
        .post-card.removing {
            transition: all 0.5s ease;
            transform: translateX(-100%);
            opacity: 0;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--bg-card);
            backdrop-filter: var(--blur-glass);
            border: 1px solid var(--border-color);
            border-radius: 1.5rem;
            margin: 2rem auto;
            max-width: 600px;
        }
        
        .empty-state-icon {
            font-size: 4rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
            display: block;
        }
        
        .empty-state-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        
        .empty-state-desc {
            color: var(--text-secondary);
            margin-bottom: 2rem;
        }
        
        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--primary-gradient);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        
        .error-message {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 1rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
        }
        
        @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
            }
            
            .posts-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .main-container {
                padding: 1rem;
            }
            
            .post-header {
                padding: 1rem;
            }
            
            .post-content {
                padding: 1rem;
            }
            
            .post-actions {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }
            
            .action-buttons {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Header -->
    <header class="nav-header">
        <div class="container mx-auto px-6 flex justify-between items-center">
            <div class="text-2xl font-bold">
                <a href="home.php" class="text-white hover:text-blue-400 transition-colors">
                    <span style="background: var(--primary-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">SUT</span> 
                    <span class="text-gray-100">Premium</span>
                </a>
            </div>
            
            <nav class="flex items-center space-x-6">
                <a href="home.php" class="text-gray-300 hover:text-white transition-colors">
                    <i class="bi bi-house-door text-xl"></i>
                </a>
                <a href="discover.php" class="text-gray-300 hover:text-white transition-colors">
                    <i class="bi bi-compass text-xl"></i>
                </a>
                <a href="chat.php" class="text-gray-300 hover:text-white transition-colors">
                    <i class="bi bi-chat text-xl"></i>
                </a>
                <a href="friends.php" class="text-gray-300 hover:text-white transition-colors">
                    <i class="bi bi-people text-xl"></i>
                </a>
                <a href="bookmarks.php" class="text-blue-400 transition-colors">
                    <i class="bi bi-bookmark-fill text-xl"></i>
                </a>
                <a href="u.php" class="text-gray-300 hover:text-white transition-colors">
                    <i class="bi bi-person text-xl"></i>
                </a>
                <a href="logout.php" class="text-gray-300 hover:text-red-400 transition-colors">
                    <i class="bi bi-box-arrow-right text-xl"></i>
                </a>
            </nav>
        </div>
    </header>

    <!-- Main Container -->
    <div class="main-container">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title"><i class="bi bi-bookmark-fill mr-3"></i>Saved Posts</h1>
            <p class="page-subtitle">Your collection of bookmarked posts for later reading</p>
        </div>
        
        <!-- Error Message -->
        <?php if ($error_message): ?>
            <div class="error-message">
                <i class="bi bi-exclamation-triangle text-xl"></i>
                <strong>Error:</strong> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Posts Grid -->
        <?php if (empty($bookmarked_posts)): ?>
            <div class="empty-state">
                <i class="bi bi-bookmark empty-state-icon"></i>
                <h3 class="empty-state-title">No Bookmarks Yet</h3>
                <p class="empty-state-desc">Start bookmarking posts to build your personal collection of interesting content</p>
                <a href="discover.php" class="btn-primary">
                    <i class="bi bi-compass"></i>
                    Discover Posts
                </a>
            </div>
        <?php else: ?>
            <div class="posts-grid">
                <?php foreach ($bookmarked_posts as $post): ?>
                    <article class="post-card" id="post-<?php echo $post['id']; ?>">
                        <div class="post-header">
                            <img src="<?php echo !empty($post['profile_picture']) ? htmlspecialchars($post['profile_picture']) : 'https://ui-avatars.com/api/?name=' . urlencode($post['username']) . '&background=667eea&color=fff&size=200'; ?>" 
                                 alt="User Avatar" 
                                 class="user-avatar"
                                 onclick="openProfile('<?php echo $post['username']; ?>')">
                            <div class="user-info">
                                <h3 onclick="openProfile('<?php echo $post['username']; ?>')">
                                    <?php echo !empty($post['first_name']) ? htmlspecialchars($post['first_name'] . ' ' . $post['last_name']) : htmlspecialchars($post['username']); ?>
                                </h3>
                                <p class="username" onclick="openProfile('<?php echo $post['username']; ?>')">@<?php echo htmlspecialchars($post['username']); ?></p>
                            </div>
                                <div class="post-time">
                                <i class="bi bi-clock mr-1"></i>
                                <?php echo date('M d, Y', strtotime($post['created_at'])); ?>
                            </div>
                        </div>
                        
                        <div class="post-content">
                            <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                            <?php
                            // Simple image display
                            $image_url = !empty($post['image_url']) ? $post['image_url'] : '';
                            $media_url = !empty($post['media_url']) ? $post['media_url'] : '';
                            
                            if (!empty($image_url)): ?>
                                <div class="media-container" style="position: relative;">
                                    <img src="<?php echo htmlspecialchars($image_url); ?>" 
                                         alt="Post Image" 
                                         class="post-media">
                                    <button class="favorite-btn action-button" 
                                            onclick="toggleFavorite(<?php echo $post['id']; ?>, this)" 
                                            style="position: absolute; top: 10px; right: 10px; z-index: 10; background: rgba(0,0,0,0.5); border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                        <i class="bi bi-star"></i>
                                    </button>
                                </div>
                            <?php elseif (!empty($media_url)): ?>
                                <div class="media-container" style="position: relative;">
                                    <img src="<?php echo htmlspecialchars($media_url); ?>" 
                                         alt="Post Media" 
                                         class="post-media">
                                    <button class="favorite-btn action-button" 
                                            onclick="toggleFavorite(<?php echo $post['id']; ?>, this)" 
                                            style="position: absolute; top: 10px; right: 10px; z-index: 10; background: rgba(0,0,0,0.5); border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                        <i class="bi bi-star"></i>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="post-actions">
                            <div class="action-buttons">
                                <button class="action-button <?php echo $post['user_liked'] ? 'liked' : ''; ?>" 
                                        onclick="toggleLike(<?php echo $post['id']; ?>, this)">
                                    <i class="bi bi-heart<?php echo $post['user_liked'] ? '-fill' : ''; ?>"></i>
                                    <span><?php echo $post['likes_count']; ?></span>
                                </button>

                                <button class="action-button" onclick="toggleComments(<?php echo $post['id']; ?>)">
                                    <i class="bi bi-chat-dots"></i>
                                    <span><?php echo $post['comments_count']; ?></span>
                                </button>
                                
                                <button class="action-button" onclick="sharePost(<?php echo $post['id']; ?>)">
                                    <i class="bi bi-share"></i>
                                    <span><?php echo $post['shares_count']; ?></span>
                                </button>
                            </div>
                            
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <div class="bookmark-badge">
                                    <i class="bi bi-bookmark-fill"></i>
                                    Saved <?php echo date('M d', strtotime($post['bookmarked_at'])); ?>
                                </div>
                                
                                <button type="button" 
                                        class="remove-bookmark-btn" 
                                        onclick="removeBookmark(<?php echo $post['id']; ?>, this)"
                                        data-post-id="<?php echo $post['id']; ?>">
                                    <i class="bi bi-trash"></i>
                                    Remove
                                </button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function openProfile(username) {
            window.open(`u.php?username=${username}`, '_blank');
        }
        
        function toggleLike(postId, element) {
            console.log('🔥 toggleLike called for bookmarked post:', postId);
            
            const icon = element.querySelector('i');
            const countSpan = element.querySelector('span');
            const isLiked = element.classList.contains('liked');
            
            element.classList.toggle('liked');
            const newCount = isLiked ? (parseInt(countSpan.textContent) - 1) : (parseInt(countSpan.textContent) + 1);
            countSpan.textContent = newCount;
            icon.className = isLiked ? 'bi bi-heart' : 'bi bi-heart-fill';
            
            fetch('api/social.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'toggle_like',
                    post_id: postId,
                    type: 'post'
                })
            })
            .then(response => response.json())
            .then(data => {
                console.log('✅ Like API Response:', data);
                
                if (data.success) {
                    const finalCount = data.data.like_count || data.data.likes_count || 0;
                    const finalIsLiked = data.data.is_liked;
                    
                    countSpan.textContent = finalCount;
                    element.classList.toggle('liked', finalIsLiked);
                    icon.className = finalIsLiked ? 'bi bi-heart-fill' : 'bi bi-heart';
                } else {
                    element.classList.toggle('liked');
                    countSpan.textContent = isLiked ? (parseInt(countSpan.textContent) + 1) : (parseInt(countSpan.textContent) - 1);
                    icon.className = isLiked ? 'bi bi-heart-fill' : 'bi bi-heart';
                    alert('Error: ' + (data.message || 'Failed to like post'));
                }
            })
            .catch(error => {
                console.error('💥 Like Error:', error);
                element.classList.toggle('liked');
                countSpan.textContent = isLiked ? (parseInt(countSpan.textContent) + 1) : (parseInt(countSpan.textContent) - 1);
                icon.className = isLiked ? 'bi bi-heart-fill' : 'bi bi-heart';
                alert('Network error: ' + error.message);
            });
        }
        
        function toggleComments(postId) {
            alert('Comments feature will be implemented soon!');
        }
        
        function sharePost(postId) {
            const shareUrl = `${window.location.origin}/WEP/post.php?id=${postId}`;
            
            if (navigator.share) {
                navigator.share({
                    title: 'Check out this post!',
                    url: shareUrl
                });
            } else if (navigator.clipboard) {
                navigator.clipboard.writeText(shareUrl).then(() => {
                    alert('Post link copied to clipboard!');
                });
                    } else {
                prompt('Copy this link:', shareUrl);
            }
        }
        
        function removeBookmark(postId, buttonElement) {
            console.log('🗑️ removeBookmark called for post:', postId);
            
            if (!confirm('Are you sure you want to remove this post from bookmarks?')) {
                return;
            }
            
            const postCard = buttonElement.closest('.post-card');
            const originalButtonText = buttonElement.innerHTML;
            
            buttonElement.disabled = true;
            buttonElement.classList.add('removing');
            buttonElement.innerHTML = '<i class="bi bi-three-dots"></i> Removing...';
            buttonElement.style.background = '#666';
            
            fetch('api/posts.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'action=toggle_bookmark&post_id=' + postId
            })
            .then(response => {
                console.log('Remove bookmark API response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('✅ Remove bookmark API Response:', data);
                
                if (data.success) {
                    postCard.classList.add('removing');
                    
                    setTimeout(() => {
                        postCard.remove();
                        
                        const remainingPosts = document.querySelectorAll('.post-card');
                        if (remainingPosts.length === 0) {
                            const postsGrid = document.querySelector('.posts-grid');
                            if (postsGrid) {
                                postsGrid.innerHTML = `
                                    <div class="empty-state" style="grid-column: 1 / -1;">
                                        <i class="bi bi-bookmark empty-state-icon"></i>
                                        <h3 class="empty-state-title">No Bookmarks Left</h3>
                                        <p class="empty-state-desc">All bookmarks have been removed. Start exploring to find new content to save!</p>
                                        <a href="discover.php" class="btn-primary">
                                            <i class="bi bi-compass"></i>
                                            Discover Posts
                                        </a>
                                    </div>
                                `;
                            }
                        }
                        
                        console.log('✅ Post removed from bookmarks successfully');
                    }, 500);
                    
                } else {
                    console.error('❌ Remove bookmark API returned error:', data.message);
                    buttonElement.disabled = false;
                    buttonElement.classList.remove('removing');
                    buttonElement.innerHTML = originalButtonText;
                    buttonElement.style.background = '';
                    alert('Error removing bookmark: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('💥 Remove bookmark network error:', error);
                buttonElement.disabled = false;
                buttonElement.classList.remove('removing');
                buttonElement.innerHTML = originalButtonText;
                buttonElement.style.background = '';
                alert('Network error: ' + error.message);
            });
        }
        
        console.log('📚 Bookmarks page loaded with', <?php echo count($bookmarked_posts); ?>, 'saved posts');
        
        function toggleFavorite(postId, element) {
            console.log('⭐ toggleFavorite called for post:', postId);
            
            const icon = element.querySelector('i');
            const isFavorited = element.classList.contains('favorited');
            
            element.classList.toggle('favorited');
            icon.className = isFavorited ? 'bi bi-star' : 'bi bi-star-fill';
            
            fetch('api/posts.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'action=toggle_favorite&post_id=' + postId
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('✅ Favorite API Response:', data);
                
                if (data.success) {
                    console.log('✅ Post favorite toggled successfully');
                } else {
                    console.error('❌ Favorite API returned error:', data.message);
                    element.classList.toggle('favorited');
                    icon.className = isFavorited ? 'bi bi-star-fill' : 'bi bi-star';
                    alert('Error toggling favorite: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('💥 Favorite network error:', error);
                element.classList.toggle('favorited');
                icon.className = isFavorited ? 'bi bi-star-fill' : 'bi bi-star';
                alert('Network error: ' + error.message);
            });
        }
    </script>
</body>
</html>
