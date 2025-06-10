<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $user_stmt->execute([$user_id]);
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception("المستخدم غير موجود في قاعدة البيانات");
    }
    
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
    $count_stmt->execute([$user_id]);
    $total_posts = $count_stmt->fetchColumn();
    
    if ($total_posts == 0) {
        $create_stmt = $pdo->prepare("INSERT INTO posts (user_id, content, created_at) VALUES (?, ?, NOW())");
        $create_stmt->execute([$user_id, '🎉 مرحباً بكم في صفحتي الشخصية! هذا أول منشور لي.']);
    }
    
    $posts_stmt = $pdo->prepare("
        SELECT p.id, p.content, p.image_url, p.created_at,
               u.username, u.first_name, u.last_name, u.avatar_url as user_avatar
        FROM posts p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.user_id = ? 
        ORDER BY p.created_at DESC 
        LIMIT 20
    ");
    $posts_stmt->execute([$user_id]);
    $posts = $posts_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($posts)) {
        foreach ($posts as &$post) {
            $like_stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ?");
            $like_stmt->execute([$post['id']]);
            $post['likes_count'] = $like_stmt->fetchColumn();
            
            $comment_stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE post_id = ?");
            $comment_stmt->execute([$post['id']]);
            $post['comments_count'] = $comment_stmt->fetchColumn();
            
            $user_like_stmt = $pdo->prepare("SELECT id FROM likes WHERE post_id = ? AND user_id = ?");
            $user_like_stmt->execute([$post['id'], $user_id]);
            $post['is_liked'] = $user_like_stmt->fetchColumn() ? 1 : 0;
            
            $bookmark_stmt = $pdo->prepare("SELECT id FROM bookmarks WHERE post_id = ? AND user_id = ?");
            $bookmark_stmt->execute([$post['id'], $user_id]);
            $post['is_bookmarked'] = $bookmark_stmt->fetchColumn() ? 1 : 0;
        }
    }
    
    $stats_stmt = $pdo->prepare("
        SELECT 
            (SELECT COUNT(*) FROM posts WHERE user_id = ?) as posts_count,
            (SELECT COUNT(*) FROM followers WHERE followed_id = ?) as followers_count,
            (SELECT COUNT(*) FROM followers WHERE follower_id = ?) as following_count
    ");
    $stats_stmt->execute([$user_id, $user_id, $user_id]);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("خطأ في u.php: " . $e->getMessage());
    
    $user = [
        'username' => $username, 
        'first_name' => '', 
        'last_name' => '', 
        'avatar_url' => '',
        'bio' => ''
    ];
    $posts = [];
    $stats = ['posts_count' => 0, 'followers_count' => 0, 'following_count' => 0];
    
    $error_message = "خطأ في تحميل البيانات: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الصفحة الشخصية - <?php echo htmlspecialchars($user['username']); ?></title>
    <meta name="user-id" content="<?php echo $user_id; ?>">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap');
        :root {
            --bg-primary: #0A0F1E;
            --bg-glass: rgba(23, 31, 48, 0.7);
            --bg-glass-header: rgba(17, 24, 39, 0.85);
            --bg-glass-sidebar: rgba(28, 38, 59, 0.75);
            --bg-glass-card: rgba(20, 28, 46, 0.75);
            --border-glass: rgba(55, 65, 81, 0.4);
            --text-primary: #E5E7EB;
            --text-secondary: #9CA3AF;
            --text-muted: #6B7280;
            --accent-primary: #3B82F6;
            --accent-primary-hover: #2563EB;
            --accent-primary-active-bg: rgba(59, 130, 246, 0.2);
            --success-online: #10B981;
            --danger-action: #EF4444;
            --font-family-base: 'Cairo', sans-serif;
            --border-radius-card: 1rem;
            --border-radius-input: 0.625rem;
            --shadow-card: 0 10px 25px -5px rgba(0, 0, 0, 0.3), 0 8px 10px -6px rgba(0, 0, 0, 0.3);
            --shadow-glass-inset: inset 0 1px 2px 0 rgba(255, 255, 255, 0.02);
            --primary-gradient: linear-gradient(135deg, var(--accent-primary), #EC4899);
        }
        body {
            font-family: var(--font-family-base);
            background-color: var(--bg-primary);
            background-image: 
                radial-gradient(circle at 15% 50%, rgba(37, 99, 235, 0.1) 0%, transparent 25%),
                radial-gradient(circle at 85% 30%, rgba(236, 72, 153, 0.1) 0%, transparent 25%);
            color: var(--text-primary);
            overflow-x: hidden;
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        .glass-effect {
            background-color: var(--bg-glass);
            backdrop-filter: blur(18px) saturate(180%);
            -webkit-backdrop-filter: blur(18px) saturate(180%);
            border: 1px solid var(--border-glass);
            box-shadow: var(--shadow-card), var(--shadow-glass-inset);
            border-radius: var(--border-radius-card);
            transition: all 0.3s ease;
        }
        .glass-effect:hover {
            box-shadow: 0 15px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.1), var(--shadow-glass-inset);
        }
        .nav-header {
            background-color: var(--bg-glass-header);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border-bottom: 1px solid var(--border-glass);
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
        }
        .profile-header {
            background: var(--primary-gradient);
            color: white;
            padding: 4rem 0;
            margin-bottom: 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .profile-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, transparent 50%, rgba(255,255,255,0.05) 100%);
        }
        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.3), 0 10px 15px -3px rgba(0, 0, 0, 0.5);
            position: relative;
            z-index: 2;
        }
        .create-post-card {
            background-color: var(--bg-glass-card);
            backdrop-filter: blur(18px) saturate(180%);
            -webkit-backdrop-filter: blur(18px) saturate(180%);
            border: 1px solid var(--border-glass);
            border-radius: var(--border-radius-card);
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-card);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .post-container {
            background-color: var(--bg-glass-card);
            backdrop-filter: blur(18px) saturate(180%);
            -webkit-backdrop-filter: blur(18px) saturate(180%);
            border: 1px solid var(--border-glass);
            border-radius: var(--border-radius-card);
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-card);
            margin-bottom: 1.5rem;
        }
        .post-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.4);
        }
        .post-header {
            display: flex;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-glass);
        }
        .post-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            margin-left: 1rem;
            border: 2px solid var(--accent-primary);
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.3);
        }
        .post-content {
            padding: 1.5rem;
            line-height: 1.6;
            color: var(--text-primary);
            font-size: 1rem;
        }
        .post-image {
            width: 100%;
            max-height: 400px;
            object-fit: cover;
            margin-top: 1rem;
            border-radius: 10px;
        }
        .post-actions {
            display: flex;
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--border-glass);
            gap: 1.5rem;
        }
        .action-button {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.2rem;
            border: none;
            background: transparent;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.3s ease;
            border-radius: 1.5rem;
            font-weight: 500;
            border: 2px solid transparent;
        }
        .action-button:hover {
            background: var(--accent-primary-active-bg);
            color: var(--accent-primary);
            transform: translateY(-2px);
        }
        .action-button.liked {
            color: var(--danger-action);
            border-color: var(--danger-action);
        }
        .action-button.liked:hover {
            background: rgba(239, 68, 68, 0.1);
        }
        .action-button.bookmarked {
            color: #FBBF24;
            border-color: #FBBF24;
        }
        .action-button.bookmarked:hover {
            background: rgba(251, 191, 36, 0.1);
        }
        .stats-container {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin: 2rem 0;
            padding: 1rem 0;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }
        .stat {
            text-align: center;
            min-width: 90px;
        }
        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
        }
        .stat-label {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.875rem;
        }
        .image-preview {
            max-width: 300px;
            margin: 10px 0;
            position: relative;
            border-radius: 10px;
            overflow: hidden;
        }
        .image-preview img {
            width: 100%;
            height: auto;
        }
        .remove-image {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(220, 53, 69, 0.8);
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            cursor: pointer;
        }
        .btn-primary {
            background-color: var(--accent-primary);
            color: white;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius-input);
            border: none;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .btn-primary:hover {
            background-color: var(--accent-primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(59, 130, 246, 0.4);
        }
        .btn-outline {
            background-color: transparent;
            border: 2px solid var(--accent-primary);
            color: var(--accent-primary);
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius-input);
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .btn-outline:hover {
            background-color: var(--accent-primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.35);
        }
        .form-control {
            background-color: rgba(55, 65, 81, 0.5);
            border: 1px solid var(--border-glass);
            border-radius: var(--border-radius-input);
            padding: 0.75rem 1rem;
            color: var(--text-primary);
            width: 100%;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .form-control::placeholder {
            color: var(--text-muted);
        }
        
        /* أنماط التعليقات */
        .comments-section {
            background-color: rgba(0, 0, 0, 0.1);
            border-top: 1px solid var(--border-glass);
            padding: 1rem;
            margin-top: 1rem;
        }
        
        .comment-item {
            transition: all 0.3s ease;
        }
        
        .comment-item:hover {
            background-color: rgba(59, 130, 246, 0.05);
        }
        
        .add-comment-form {
            border-top: 1px solid var(--border-glass);
            padding-top: 1rem;
            margin-top: 1rem;
        }
        
        .comment-input {
            background-color: rgba(55, 65, 81, 0.3) !important;
        }
        
        .comment-input:focus {
            background-color: rgba(55, 65, 81, 0.5) !important;
        }
        
        @media (max-width: 768px) {
            .profile-header {
                padding: 3rem 0;
            }
            .stats-container {
                gap: 1rem;
            }
            .post-header {
                padding: 1rem;
            }
            .post-content {
                padding: 1rem;
            }
            .post-actions {
                padding: 0.75rem 1rem;
                gap: 1rem;
            }
        }
    </style>
</head>
<body class="antialiased">
    <div class="min-h-screen flex flex-col">
        <!-- Header -->
        <header class="nav-header sticky top-0 z-50">
            <div class="container mx-auto px-4 sm:px-6 py-3.5 flex justify-between items-center">
                <div class="text-2xl font-bold text-white">
                    <a href="home.php"><span class="text-accent-primary">SUT</span> <span class="text-gray-100">Premium</span></a>
                </div>
                <div class="flex items-center space-x-4 space-x-reverse">
                    <a href="home.php" class="text-white hover:text-accent-primary transition-colors">
                        <i class="bi bi-house-door text-xl"></i>
                    </a>
                    <a href="discover.php" class="text-white hover:text-accent-primary transition-colors">
                        <i class="bi bi-compass text-xl"></i>
                    </a>
                    <a href="chat.php" class="text-white hover:text-accent-primary transition-colors">
                        <i class="bi bi-chat text-xl"></i>
                    </a>
                    <a href="friends.php" class="text-white hover:text-accent-primary transition-colors">
                        <i class="bi bi-people text-xl"></i>
                    </a>
                    <a href="bookmarks.php" class="text-white hover:text-accent-primary transition-colors">
                        <i class="bi bi-bookmark text-xl"></i>
                    </a>
                    <a href="u.php" class="text-accent-primary transition-colors">
                        <i class="bi bi-person text-xl"></i>
                    </a>
                    <a href="logout.php" class="text-white hover:text-red-500 transition-colors">
                        <i class="bi bi-box-arrow-right text-xl"></i>
                    </a>
                </div>
            </div>
        </header>

        <!-- Profile Header -->
        <div class="profile-header">
            <div class="container mx-auto px-4 text-center">
                <div class="relative mx-auto mb-4" style="width: fit-content;">
                    <img src="<?php echo !empty($user['avatar_url']) ? htmlspecialchars($user['avatar_url']) : 'https://ui-avatars.com/api/?name=' . urlencode($user['username']) . '&background=3B82F6&color=fff&size=150'; ?>" 
                         alt="الصورة الشخصية" class="profile-avatar mx-auto">
                </div>
                
                <h1 class="text-3xl font-bold mb-2" style="position: relative; z-index: 2;">
                    <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                </h1>
                <p class="text-xl opacity-90 mb-2" style="position: relative; z-index: 2;">
                    @<?php echo htmlspecialchars($user['username']); ?>
                </p>
                
                <?php if (!empty($user['bio'])): ?>
                    <p class="text-lg opacity-80 max-w-md mx-auto" style="position: relative; z-index: 2;">
                        <?php echo htmlspecialchars($user['bio']); ?>
                    </p>
                <?php endif; ?>
                
                <div class="stats-container" style="position: relative; z-index: 2;">
                    <div class="stat">
                        <div class="stat-value"><?php echo $stats['posts_count']; ?></div>
                        <div class="stat-label">منشور</div>
                    </div>
                    <div class="stat">
                        <div class="stat-value"><?php echo $stats['followers_count']; ?></div>
                        <div class="stat-label">متابع</div>
                    </div>
                    <div class="stat">
                        <div class="stat-value"><?php echo $stats['following_count']; ?></div>
                        <div class="stat-label">يتابع</div>
                    </div>
                </div>
                
                <button class="btn-outline mt-4" onclick="window.location.href='settings.php'" style="position: relative; z-index: 2; border-color: white; color: white;">
                    <i class="bi bi-pencil ml-2"></i> تعديل الملف
                </button>
            </div>
        </div>

        <!-- Main Content -->
        <main class="container mx-auto px-4 flex-grow">
            <!-- Create Post Card -->
            <div class="create-post-card max-w-2xl mx-auto">
                <h3 class="text-lg font-bold mb-4 text-accent-primary">
                    <i class="bi bi-plus-circle ml-2"></i>إنشاء منشور جديد
                </h3>
                <form id="postForm">
                    <div class="mb-4">
                        <textarea class="form-control" id="postContent" name="content" rows="3" 
                                  placeholder="ما الذي تفكر فيه؟" style="resize: vertical; min-height: 100px;"></textarea>
                    </div>
                    
                    <!-- Image Preview Area -->
                    <div id="imagePreview" style="display: none;"></div>
                    
                    <div class="flex justify-between items-center">
                        <div class="flex gap-3">
                            <button type="button" class="action-button" onclick="document.getElementById('postImage').click()">
                                <i class="bi bi-image"></i> صورة
                            </button>
                            <input type="file" id="postImage" name="image" accept="image/*" style="display: none;">
                            
                            <button type="button" class="action-button" onclick="addEmoji()">
                                <i class="bi bi-emoji-smile"></i> رموز
                            </button>
                        </div>
                        <button type="submit" class="btn-primary">
                            <i class="bi bi-send ml-2"></i> نشر
                        </button>
                    </div>
                </form>
            </div>

            <!-- Messages Container -->
            <div id="messageContainer" class="max-w-2xl mx-auto">
                <?php if (isset($error_message)): ?>
                    <div class="glass-effect bg-red-500 bg-opacity-20 border-red-500 text-red-100 border-2 rounded-lg p-4 mb-4 flex items-center">
                        <i class="bi bi-exclamation-triangle-fill text-red-400 text-xl mr-3"></i>
                        <span class="flex-1"><?php echo htmlspecialchars($error_message); ?></span>
                        <small class="text-red-300 ml-3">راجع لوحة التحكم للمزيد من التفاصيل</small>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Posts Section -->
            <div class="max-w-2xl mx-auto">
                <h3 class="text-xl font-bold mb-6 text-center">
                    <i class="bi bi-collection text-accent-primary ml-2"></i>منشوراتي (<?php echo count($posts); ?>)
                </h3>
                
                <div id="postsContainer">
                    <?php if (empty($posts)): ?>
                        <div class="text-center py-16 glass-effect">
                            <i class="bi bi-journal-x text-6xl text-accent-primary opacity-70 mb-6"></i>
                            <h4 class="text-xl font-bold mb-2">لا توجد منشورات بعد</h4>
                            <p class="text-text-secondary">ابدأ بمشاركة أول منشور لك!</p>
                            <?php if (isset($error_message)): ?>
                                <div class="mt-4 p-3 bg-yellow-500 bg-opacity-20 border border-yellow-500 rounded-lg text-yellow-100">
                                    <p class="text-sm">💡 تلميح: قد يكون السبب في عدم ظهور المنشورات خطأ في قاعدة البيانات</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <?php foreach ($posts as $post): ?>
                            <article class="post-container" data-post-id="<?php echo $post['id']; ?>">
                                <header class="post-header">
                                    <img src="<?php echo !empty($post['user_avatar']) ? htmlspecialchars($post['user_avatar']) : 'https://ui-avatars.com/api/?name=' . urlencode($post['username']) . '&background=3B82F6&color=fff&size=50'; ?>" 
                                         alt="<?php echo htmlspecialchars($post['username']); ?>" class="post-avatar">
                                    <div class="flex-grow">
                                        <h4 class="font-semibold text-text-primary"><?php echo htmlspecialchars($post['first_name'] . ' ' . $post['last_name']); ?></h4>
                                        <small class="text-text-secondary">@<?php echo htmlspecialchars($post['username']); ?> • <?php echo date('d/m/Y H:i', strtotime($post['created_at'])); ?></small>
                                    </div>
                                    <div class="relative">
                                        <button class="action-button" onclick="toggleDropdown(<?php echo $post['id']; ?>)">
                                            <i class="bi bi-three-dots"></i>
                                        </button>
                                        <div id="dropdown-<?php echo $post['id']; ?>" class="absolute left-0 mt-2 w-48 glass-effect rounded-lg shadow-lg hidden z-10">
                                            <button class="w-full text-left px-4 py-2 text-danger-action hover:bg-red-500 hover:bg-opacity-10 rounded-lg" onclick="deletePost(<?php echo $post['id']; ?>)">
                                                <i class="bi bi-trash ml-2"></i> حذف
                                            </button>
                                        </div>
                                    </div>
                                </header>
                                
                                <div class="post-content">
                                    <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                                    <?php if (!empty($post['image_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($post['image_url']); ?>" alt="صورة المنشور" class="post-image">
                                    <?php endif; ?>
                                </div>
                                
                                <div class="post-actions">
                                    <button class="action-button <?php echo $post['is_liked'] ? 'liked' : ''; ?>" data-post-id="<?php echo $post['id']; ?>" onclick="toggleLike(<?php echo $post['id']; ?>, this)">
                                        <i class="bi bi-heart<?php echo $post['is_liked'] ? '-fill' : ''; ?>"></i>
                                        <span class="like-count"><?php echo $post['likes_count']; ?></span>
                                    </button>
                                    <button class="action-button" data-post-id="<?php echo $post['id']; ?>" onclick="toggleComments(<?php echo $post['id']; ?>)">
                                        <i class="bi bi-chat-dots"></i>
                                        <span><?php echo $post['comments_count']; ?></span>
                                    </button>
                                    <button class="action-button" data-post-id="<?php echo $post['id']; ?>" onclick="sharePost(<?php echo $post['id']; ?>)">
                                        <i class="bi bi-share"></i>
                                        مشاركة
                                    </button>
                                    <button class="action-button <?php echo $post['is_bookmarked'] ? 'bookmarked' : ''; ?>" data-post-id="<?php echo $post['id']; ?>" onclick="toggleBookmark(<?php echo $post['id']; ?>, this)">
                                        <i class="bi bi-bookmark<?php echo $post['is_bookmarked'] ? '-fill' : ''; ?>"></i>
                                        <?php echo $post['is_bookmarked'] ? 'محفوظ' : 'حفظ'; ?>
                                    </button>
                                </div>
                                
                                <!-- قسم التعليقات -->
                                <div class="comments-section" id="comments-<?php echo $post['id']; ?>" style="display: none;">
                                    <div class="comments-container" id="comments-container-<?php echo $post['id']; ?>">
                                        <!-- التعليقات ستُحمّل هنا -->
                                    </div>
                                    
                                    <!-- نموذج إضافة تعليق -->
                                    <div class="add-comment-form mt-3 pt-3 border-top">
                                        <form class="comment-form" data-post-id="<?php echo $post['id']; ?>">
                                            <div class="flex gap-3">
                                                <img src="<?php echo !empty($user['avatar_url']) ? htmlspecialchars($user['avatar_url']) : 'https://ui-avatars.com/api/?name=' . urlencode($user['username']) . '&background=3B82F6&color=fff&size=40'; ?>" 
                                                     alt="صورتك" class="w-10 h-10 rounded-full object-cover">
                                                <div class="flex-1">
                                                    <textarea class="form-control comment-input" rows="2" placeholder="اكتب تعليقاً..." 
                                                             style="resize: none; min-height: 60px;"></textarea>
                                                    <div class="flex justify-end mt-2">
                                                        <button type="button" class="action-button text-sm" onclick="addEmoji(this.parentElement.parentElement.querySelector('.comment-input'))">
                                                            <i class="bi bi-emoji-smile"></i>
                                                        </button>
                                                        <button type="submit" class="btn-primary text-sm py-2 px-4">
                                                            <i class="bi bi-send mr-1"></i> تعليق
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script src="assets/js/app-enhanced.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🎉 الصفحة الشخصية جاهزة!');
        
        const postForm = document.getElementById('postForm');
        if (postForm) {
            postForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                formData.append('action', 'create_post');
                
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="bi bi-hourglass-split animate-spin ml-2"></i> جاري النشر...';
                
                try {
                    const response = await fetch('api/posts_fixed.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        showMessage('تم نشر المنشور بنجاح!', 'success');
                        this.reset();
                        removeImagePreview();
                        
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        showMessage(result.message || 'حدث خطأ أثناء نشر المنشور', 'error');
                    }
                } catch (error) {
                    console.error('خطأ في نشر المنشور:', error);
                    showMessage('حدث خطأ غير متوقع', 'error');
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            });
        }
        
        const imageInput = document.getElementById('postImage');
        if (imageInput) {
            imageInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    if (!['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'].includes(file.type)) {
                        showMessage('نوع الملف غير مدعوم. يرجى اختيار صورة (JPEG, PNG, GIF, WebP)', 'error');
                        this.value = '';
                        return;
                    }
                    
                    if (file.size > 5 * 1024 * 1024) {
                        showMessage('الصورة كبيرة جداً. الحد الأقصى 5MB', 'error');
                        this.value = '';
                        return;
                    }
                    
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const preview = document.getElementById('imagePreview');
                        preview.innerHTML = `
                            <div class="image-preview glass-effect p-2">
                                <img src="${e.target.result}" alt="معاينة الصورة" class="rounded-lg">
                                <button type="button" class="remove-image" onclick="removeImagePreview()">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                            <small class="text-text-muted block mt-2">
                                <i class="bi bi-info-circle ml-1"></i> ${file.name} (${(file.size/1024/1024).toFixed(2)} MB)
                            </small>
                        `;
                        preview.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                }
            });
        }
    });
    
    async function toggleLike(postId, element) {
        const likeBtn = element;
        const likeCount = likeBtn.querySelector('.like-count');
        const likeIcon = likeBtn.querySelector('i');
        
        const originalIcon = likeIcon.className;
        likeIcon.className = 'bi bi-hourglass-split animate-spin';
        
        try {
            const response = await fetch('api/social.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'toggle_like',
                    post_id: parseInt(postId)
                })
            });

            const result = await response.json();
            
            if (result.success) {
                if (result.data.is_liked) {
                    likeBtn.classList.add('liked');
                    likeIcon.className = 'bi bi-heart-fill';
                } else {
                    likeBtn.classList.remove('liked');
                    likeIcon.className = 'bi bi-heart';
                }
                
                if (likeCount) {
                    likeCount.textContent = result.data.like_count || 0;
                }
                
                showMessage(result.message, 'success');
            } else {
                likeIcon.className = originalIcon;
                showMessage(result.message || 'حدث خطأ في الإعجاب', 'error');
            }
        } catch (error) {
            likeIcon.className = originalIcon;
            console.error('خطأ في الإعجاب:', error);
            showMessage('حدث خطأ غير متوقع', 'error');
        }
    }
    
    async function toggleBookmark(postId, element) {
        const bookmarkBtn = element;
        const bookmarkIcon = bookmarkBtn.querySelector('i');
        const bookmarkText = bookmarkBtn.childNodes[2];
        
        const originalIcon = bookmarkIcon.className;
        bookmarkIcon.className = 'bi bi-hourglass-split animate-spin';

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

            const result = await response.json();
            
            if (result.success) {
                if (result.data.is_bookmarked) {
                    bookmarkBtn.classList.add('bookmarked');
                    bookmarkIcon.className = 'bi bi-bookmark-fill';
                    if (bookmarkText) bookmarkText.textContent = ' محفوظ';
                } else {
                    bookmarkBtn.classList.remove('bookmarked');
                    bookmarkIcon.className = 'bi bi-bookmark';
                    if (bookmarkText) bookmarkText.textContent = ' حفظ';
                }
                
                showMessage(result.message, 'success');
            } else {
                bookmarkIcon.className = originalIcon;
                showMessage(result.message || 'حدث خطأ في المفضلة', 'error');
            }
        } catch (error) {
            bookmarkIcon.className = originalIcon;
            console.error('خطأ في المفضلة:', error);
            showMessage('حدث خطأ غير متوقع', 'error');
        }
    }
    
    async function toggleComments(postId) {
        const commentsSection = document.getElementById(`comments-${postId}`);
        const commentsContainer = document.getElementById(`comments-container-${postId}`);
        
        if (commentsSection.style.display === 'none') {
            commentsSection.style.display = 'block';
            await loadComments(postId);
        } else {
            commentsSection.style.display = 'none';
        }
    }
        async function loadComments(postId) {
        const container = document.getElementById(`comments-container-${postId}`);
        
        try {
            const response = await fetch(`api/social.php?action=get_comments&post_id=${postId}`);
            const result = await response.json();
            
            if (result.success && result.data.comments) {
                if (result.data.comments.length === 0) {
                    container.innerHTML = '<div class="text-center py-4 text-text-muted">لا توجد تعليقات بعد. كن أول من يعلق!</div>';
                } else {
                    container.innerHTML = result.data.comments.map(comment => `
                        <div class="comment-item p-3 mb-2 glass-effect rounded-lg">
                            <div class="flex gap-3">
                                <img src="${comment.avatar_url || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(comment.username) + '&background=3B82F6&color=fff&size=40'}" 
                                     alt="${comment.username}" class="w-10 h-10 rounded-full object-cover">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="font-semibold text-text-primary">${comment.first_name} ${comment.last_name}</span>
                                        <span class="text-text-muted text-sm">@${comment.username}</span>
                                        <span class="text-text-muted text-xs">${formatDate(comment.created_at)}</span>
                                    </div>
                                    <p class="text-text-primary mb-2">${comment.content}</p>
                                    <div class="flex gap-2">
                                        <button class="action-button text-xs ${comment.user_liked ? 'liked' : ''}" onclick="toggleCommentLike(${comment.id}, this)">
                                            <i class="bi bi-heart${comment.user_liked ? '-fill' : ''}"></i>
                                            <span>${comment.like_count}</span>
                                        </button>
                                        <button class="action-button text-xs" onclick="replyToComment(${postId}, '${comment.username}')">
                                            <i class="bi bi-reply"></i>
                                            رد
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `).join('');
                }
            } else {
                container.innerHTML = '<div class="text-center py-4 text-red-500">حدث خطأ في تحميل التعليقات</div>';
            }
        } catch (error) {
            console.error('خطأ في تحميل التعليقات:', error);
            container.innerHTML = '<div class="text-center py-4 text-red-500">حدث خطأ غير متوقع</div>';
        }
    }
    
    async function addComment(postId, content) {
        try {
            const response = await fetch('api/social.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'add_comment',
                    post_id: postId,
                    content: content
                })
            });

            const result = await response.json();
            
            if (result.success) {
                showMessage('تم إضافة التعليق بنجاح!', 'success');
                await loadComments(postId);
                updateCommentsCount(postId);
                return true;
            } else {
                showMessage(result.message || 'حدث خطأ في إضافة التعليق', 'error');
                return false;
            }
        } catch (error) {
            console.error('خطأ في إضافة التعليق:', error);
            showMessage('حدث خطأ غير متوقع', 'error');
            return false;
        }
    }
    
    async function updateCommentsCount(postId) {
        try {
            const response = await fetch(`api/social.php?action=get_post_stats&post_id=${postId}`);
            const result = await response.json();
            
            if (result.success) {
                const commentBtn = document.querySelector(`[data-post-id="${postId}"] .action-button:nth-child(2) span`);
                if (commentBtn) {
                    commentBtn.textContent = result.data.comment_count;
                }
            }
        } catch (error) {
            console.error('خطأ في تحديث عداد التعليقات:', error);
        }
    }
    
    async function toggleCommentLike(commentId, element) {
        const icon = element.querySelector('i');
        const count = element.querySelector('span');
        const originalIcon = icon.className;
        
        icon.className = 'bi bi-hourglass-split animate-spin';
        
        try {
            const response = await fetch('api/social.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'toggle_like',
                    post_id: commentId,
                    type: 'comment'
                })
            });

            const result = await response.json();
            
            if (result.success) {
                if (result.data.is_liked) {
                    element.classList.add('liked');
                    icon.className = 'bi bi-heart-fill';
                } else {
                    element.classList.remove('liked');
                    icon.className = 'bi bi-heart';
                }
                count.textContent = result.data.like_count;
            } else {
                icon.className = originalIcon;
                showMessage(result.message || 'حدث خطأ في الإعجاب', 'error');
            }
        } catch (error) {
            icon.className = originalIcon;
            console.error('خطأ في إعجاب التعليق:', error);
            showMessage('حدث خطأ غير متوقع', 'error');
        }
    }
    
    function replyToComment(postId, username) {
        const commentForm = document.querySelector(`[data-post-id="${postId}"] .comment-input`);
        if (commentForm) {
            commentForm.value = `@${username} `;
            commentForm.focus();
            commentForm.setSelectionRange(commentForm.value.length, commentForm.value.length);
        }
    }
    
    function formatDate(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffInSeconds = Math.floor((now - date) / 1000);
        
        if (diffInSeconds < 60) {
            return 'الآن';
        } else if (diffInSeconds < 3600) {
            const minutes = Math.floor(diffInSeconds / 60);
            return `منذ ${minutes} دقيقة`;
        } else if (diffInSeconds < 86400) {
            const hours = Math.floor(diffInSeconds / 3600);
            return `منذ ${hours} ساعة`;
        } else {
            const days = Math.floor(diffInSeconds / 86400);
            return `منذ ${days} يوم`;
        }
    }
    
    document.addEventListener('submit', function(e) {
        if (e.target.classList.contains('comment-form')) {
            e.preventDefault();
            
            const postId = e.target.getAttribute('data-post-id');
            const textarea = e.target.querySelector('.comment-input');
            const content = textarea.value.trim();
            
            if (!content) {
                showMessage('يرجى كتابة تعليق', 'warning');
                return;
            }
            
            const submitBtn = e.target.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split animate-spin mr-1"></i> جاري الإرسال...';
            
            addComment(postId, content).then(success => {
                if (success) {
                    textarea.value = '';
                }
            }).finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        }
    });
    
    function deletePost(postId) {
        if (!confirm('هل أنت متأكد من حذف هذا المنشور؟')) return;
        
        hideDropdown(postId);
        
        if (window.socialApp && window.socialApp.deletePost) {
            window.socialApp.deletePost(postId);
        } else {
            fetch('api/posts_fixed.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'delete_post',
                    post_id: parseInt(postId)
                })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showMessage('تم حذف المنشور بنجاح', 'success');
                    const postElement = document.querySelector(`[data-post-id="${postId}"]`);
                    if (postElement) {
                        postElement.style.transition = 'all 0.3s ease';
                        postElement.style.opacity = '0';
                        postElement.style.transform = 'translateX(-100%)';
                        setTimeout(() => {
                            postElement.remove();
                        }, 300);
                    }
                } else {
                    showMessage(result.message || 'حدث خطأ أثناء حذف المنشور', 'error');
                }
            })
            .catch(error => {
                console.error('خطأ في حذف المنشور:', error);
                showMessage('حدث خطأ غير متوقع', 'error');
            });
        }
    }
    
    function toggleDropdown(postId) {
        const dropdown = document.getElementById(`dropdown-${postId}`);
        const allDropdowns = document.querySelectorAll('[id^="dropdown-"]');
        
        allDropdowns.forEach(d => {
            if (d.id !== `dropdown-${postId}`) {
                d.classList.add('hidden');
            }
        });
        
        dropdown.classList.toggle('hidden');
    }
    
    function hideDropdown(postId) {
        const dropdown = document.getElementById(`dropdown-${postId}`);
        if (dropdown) {
            dropdown.classList.add('hidden');
        }
    }
    
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.relative')) {
            const allDropdowns = document.querySelectorAll('[id^="dropdown-"]');
            allDropdowns.forEach(d => d.classList.add('hidden'));
        }
    });
    
    function sharePost(postId) {
        if (navigator.share) {
            navigator.share({
                title: 'شارك هذا المنشور',
                text: 'شاهد هذا المنشور المثير!',
                url: window.location.href
            }).then(() => {
                showMessage('تم مشاركة المنشور!', 'success');
            }).catch(() => {
                copyToClipboard();
            });
        } else {
            copyToClipboard();
        }
        
        function copyToClipboard() {
            navigator.clipboard.writeText(window.location.href).then(() => {
                showMessage('تم نسخ رابط المنشور!', 'success');
            }).catch(() => {
                showMessage('تعذر نسخ الرابط', 'error');
            });
        }
    }
    
    function addEmoji(textarea) {
        if (!textarea) {
            textarea = document.getElementById('postContent');
        }
        
        const emojis = ['😊', '😍', '🔥', '💯', '👍', '❤️', '🎉', '💪', '🌟', '✨', '🚀', '💫', '🎯', '⭐'];
        const randomEmoji = emojis[Math.floor(Math.random() * emojis.length)];
        
        const cursorPos = textarea.selectionStart;
        const textBefore = textarea.value.substring(0, cursorPos);
        const textAfter = textarea.value.substring(cursorPos);
        
        textarea.value = textBefore + randomEmoji + textAfter;
        textarea.focus();
        textarea.setSelectionRange(cursorPos + randomEmoji.length, cursorPos + randomEmoji.length);
    }
    
    function removeImagePreview() {
        document.getElementById('imagePreview').style.display = 'none';
        document.getElementById('postImage').value = '';
    }
    
    function showMessage(message, type = 'info') {
        const container = document.getElementById('messageContainer');
        
        container.innerHTML = '';
        
        const alertStyles = {
            'success': 'bg-green-500 bg-opacity-20 border-green-500 text-green-100',
            'error': 'bg-red-500 bg-opacity-20 border-red-500 text-red-100',
            'info': 'bg-blue-500 bg-opacity-20 border-blue-500 text-blue-100',
            'warning': 'bg-yellow-500 bg-opacity-20 border-yellow-500 text-yellow-100'
        };
        
        const iconClasses = {
            'success': 'bi-check-circle-fill text-green-400',
            'error': 'bi-exclamation-triangle-fill text-red-400',
            'info': 'bi-info-circle-fill text-blue-400',
            'warning': 'bi-exclamation-triangle-fill text-yellow-400'
        };
        
        const alertDiv = document.createElement('div');
        alertDiv.className = `glass-effect ${alertStyles[type]} border-2 rounded-lg p-4 mb-4 flex items-center transition-all duration-300 transform`;
        alertDiv.innerHTML = `
            <i class="bi ${iconClasses[type]} text-xl mr-3"></i>
            <span class="flex-1">${message}</span>
            <button onclick="this.parentElement.remove()" class="text-current hover:opacity-70 ml-3">
                <i class="bi bi-x-lg"></i>
            </button>
        `;
        
        container.appendChild(alertDiv);
        
        setTimeout(() => {
            alertDiv.style.transform = 'translateX(0)';
            alertDiv.style.opacity = '1';
        }, 10);
        
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.style.transform = 'translateX(-100%)';
                alertDiv.style.opacity = '0';
                setTimeout(() => {
                    if (alertDiv.parentNode) {
                        alertDiv.remove();
                    }
                }, 300);
            }
        }, 5000);
    }
    
    document.querySelectorAll('.post-container').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-8px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    window.addEventListener('scroll', () => {
        const scrolled = window.pageYOffset;
        const parallax = document.querySelector('.profile-header');
        if (parallax) {
            const speed = scrolled * 0.5;
            parallax.style.transform = `translateY(${speed}px)`;
        }
    });
    </script>
</body>
</html> 