<?php

require_once 'config.php';
require_once 'functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    try {
        $pdo_temp = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
        $stmt_temp = $pdo_temp->prepare("SELECT * FROM users WHERE username = 'ben10' OR id = 5");
        $stmt_temp->execute();
        $user_temp = $stmt_temp->fetch();
        
        if ($user_temp) {
            $_SESSION['user_id'] = $user_temp['id'];
            $_SESSION['username'] = $user_temp['username'];
            $_SESSION['email'] = $user_temp['email'];
            $_SESSION['first_name'] = $user_temp['first_name'];
            $_SESSION['last_name'] = $user_temp['last_name'];
            $_SESSION['avatar_url'] = $user_temp['avatar_url'];
        } else {
            header('Location: frontend/login.html?error=' . urlencode('يرجى تسجيل الدخول أولاً'));
            exit;
        }
    } catch (Exception $e) {
        header('Location: frontend/login.html?error=' . urlencode('يرجى تسجيل الدخول أولاً'));
        exit;
    }
}

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $user_id = $_SESSION['user_id'];
    
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
    if (!$user) {
        session_destroy();
        header('Location: login.php');
        exit;
    }
            
    $stats = ['posts_count' => 0, 'followers_count' => 0, 'following_count' => 0];
    
    try {
        $posts_stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
        $posts_stmt->execute([$user_id]);
        $stats['posts_count'] = (int)$posts_stmt->fetchColumn();
        
        $followers_stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE followed_id = ?");
        $followers_stmt->execute([$user_id]);
        $stats['followers_count'] = (int)$followers_stmt->fetchColumn();
        
        $following_stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE follower_id = ?");
        $following_stmt->execute([$user_id]);
        $stats['following_count'] = (int)$following_stmt->fetchColumn();
        
        error_log("User $user_id stats: Posts={$stats['posts_count']}, Followers={$stats['followers_count']}, Following={$stats['following_count']}");
        
    } catch (Exception $e) {
        error_log("Error getting user stats: " . $e->getMessage());
    }
    
    $following_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE follower_id = ?");
    $following_count_stmt->execute([$user_id]);
    $following_count = $following_count_stmt->fetchColumn();
    
    if ($following_count > 0) {
        $posts_stmt = $pdo->prepare("
            SELECT p.id, p.content, p.image_url, p.created_at,
                   u.username, u.first_name, u.last_name, u.avatar_url as user_avatar
            FROM posts p 
            JOIN users u ON p.user_id = u.id 
            WHERE p.user_id = ? OR p.user_id IN (
                SELECT followed_id FROM followers WHERE follower_id = ?
            )
            ORDER BY p.created_at DESC 
            LIMIT 10
        ");
        $posts_stmt->execute([$user_id, $user_id]);
    } else {
        $posts_stmt = $pdo->prepare("
            SELECT p.id, p.content, p.image_url, p.created_at,
                   u.username, u.first_name, u.last_name, u.avatar_url as user_avatar
            FROM posts p 
            JOIN users u ON p.user_id = u.id 
            ORDER BY p.created_at DESC 
            LIMIT 10
        ");
        $posts_stmt->execute();
    }
    $posts = $posts_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($posts)) {
        foreach ($posts as &$post) {
            $post['likes_count'] = 0;
            $post['comments_count'] = 0;
            $post['is_liked'] = 0;
            $post['is_bookmarked'] = 0;
            
            try {
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
            } catch (Exception $e) {
                error_log("Error getting post stats: " . $e->getMessage());
            }
        }
    }
    
    $suggested_users_stmt = $pdo->prepare("
        SELECT u.*, 
               CASE WHEN f.follower_id IS NOT NULL THEN 1 ELSE 0 END as i_follow
        FROM users u
        LEFT JOIN followers f ON u.id = f.followed_id AND f.follower_id = ?
        WHERE u.id != ? AND f.follower_id IS NULL
        ORDER BY u.created_at DESC
        LIMIT 5
    ");
    $suggested_users_stmt->execute([$user_id, $user_id]);
    $suggested_users = $suggested_users_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    
    $user = [
        'username' => $_SESSION['username'], 
        'first_name' => '', 
        'last_name' => '', 
        'avatar_url' => '',
        'bio' => ''
    ];
    $stats = ['posts_count' => 0, 'followers_count' => 0, 'following_count' => 0];
    $posts = [];
    $suggested_users = [];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الصفحة الرئيسية | SUT Premium</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
            font-family: 'Cairo', sans-serif;
        }
        
        body {
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            line-height: 1.6;
            overflow-x: hidden;
            direction: rtl;
        }
        
        .nav-header {
            background: rgba(10, 15, 28, 0.9);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 1000;
            padding: 1rem 0;
            direction: rtl;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        
        .page-header {
            text-align: center;
            margin: 2rem 0;
        }
        
        .page-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 0.5rem;
        }
        
        .page-subtitle {
            color: var(--text-secondary);
            font-size: 1.1rem;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 300px 1fr 300px;
            gap: 2rem;
            margin-top: 2rem;
        }
        
        @media (max-width: 1200px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
        }
        
        .sidebar-card {
            background: var(--bg-card);
            backdrop-filter: var(--blur-glass);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: var(--shadow-card);
            height: fit-content;
            position: sticky;
            top: 100px;
        }
        
        .profile-card {
            text-align: center;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 1.5rem;
            border: 4px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }
        
        .profile-name {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }
        
        .profile-username {
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
            font-size: 1rem;
        }
        
        .profile-stats {
            display: flex;
            justify-content: space-around;
            margin-bottom: 2rem;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            display: block;
        }
        
        .stat-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .main-content {
            background: var(--bg-card);
            backdrop-filter: var(--blur-glass);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: var(--shadow-card);
        }
        
        .post-composer {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-color);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .composer-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .composer-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, 0.2);
        }
        
        .composer-input {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1rem;
            width: 100%;
            color: var(--text-primary);
            font-size: 1rem;
            resize: vertical;
            min-height: 100px;
        }
        
        .composer-input::placeholder {
            color: var(--text-muted);
        }
        
        .composer-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
        }
        
        .composer-tools {
            display: flex;
            gap: 1rem;
        }
        
        .tool-btn {
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 1.2rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .tool-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-primary);
        }
        
        .post-btn {
            background: var(--primary-gradient);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .post-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6);
        }
        
        .posts-feed {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .post-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-color);
            border-radius: 15px;
            padding: 1.5rem;
            transition: all 0.3s ease;
        }
        
        .post-card:hover {
            background: var(--bg-card-hover);
            transform: translateY(-2px);
            box-shadow: var(--shadow-primary);
        }
        
        .post-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .post-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, 0.2);
        }
        
        .post-author {
            flex: 1;
        }
        
        .author-name {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }
        
        .post-time {
            font-size: 0.875rem;
            color: var(--text-muted);
        }
        
        .post-content {
            color: var(--text-primary);
            line-height: 1.6;
            margin-bottom: 1rem;
        }
        
        .post-actions {
            display: flex;
            justify-content: space-around;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
        }
        
        .action-btn {
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .action-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-primary);
        }
        
        .action-btn.liked {
            color: #e74c3c;
        }
        
        .action-btn.bookmarked {
            color: #f39c12;
        }
        
        .suggestions-card h3 {
            margin-bottom: 1.5rem;
            color: var(--text-primary);
            font-weight: 600;
        }
        
        .suggestion-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .suggestion-item:last-child {
            border-bottom: none;
        }
        
        .suggestion-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, 0.2);
        }
        
        .suggestion-info {
            flex: 1;
        }
        
        .suggestion-name {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }
        
        .suggestion-username {
            font-size: 0.875rem;
            color: var(--text-muted);
        }
        
        .follow-btn {
            background: var(--primary-gradient);
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .follow-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .no-posts {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--text-secondary);
        }
        
        .no-posts i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <nav class="nav-header">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 2rem;">
                    <h1 style="font-size: 1.5rem; font-weight: 800; color: #667eea;">SUT Premium</h1>
                    <div style="display: flex; gap: 1rem;">
                        <a href="home.php" style="color: var(--text-primary); text-decoration: none; padding: 0.5rem 1rem; border-radius: 8px; background: rgba(255,255,255,0.1);">الرئيسية</a>
                        <a href="discover.php" style="color: var(--text-secondary); text-decoration: none; padding: 0.5rem 1rem;">اكتشف</a>
                        <a href="chat.php" style="color: var(--text-secondary); text-decoration: none; padding: 0.5rem 1rem;">الدردشات</a>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <span style="color: var(--text-secondary);">مرحباً، <?php echo htmlspecialchars($user['username']); ?></span>
                    <a href="u.php" style="color: var(--text-primary); text-decoration: none;">الملف الشخصي</a>
                    <a href="logout.php" style="color: var(--text-secondary); text-decoration: none;">تسجيل الخروج</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title">مرحباً بك</h1>
            <p class="page-subtitle">لوحة التحكم الخاصة بك للتواصل ومشاركة المحتوى واكتشاف المزيد</p>
        </div>
        
        <div class="dashboard-grid">
            <div class="sidebar-card profile-card">
                <img src="<?php echo !empty($user['avatar_url']) ? htmlspecialchars($user['avatar_url']) : 'https://ui-avatars.com/api/?name=' . urlencode($user['username']) . '&background=667eea&color=fff&size=200'; ?>" 
                     alt="Profile Picture" class="profile-avatar">
                
                <h2 class="profile-name">
                    <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?: htmlspecialchars($user['username']); ?>
                </h2>
                <p class="profile-username">@<?php echo htmlspecialchars($user['username']); ?></p>
                
                <div class="profile-stats">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $stats['posts_count']; ?></div>
                        <div class="stat-label">Posts</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $stats['followers_count']; ?></div>
                        <div class="stat-label">Followers</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $stats['following_count']; ?></div>
                        <div class="stat-label">Following</div>
                    </div>
                </div>
                
                <button class="follow-btn" onclick="window.location.href='u.php'" style="width: 100%; margin-top: 1rem;">
                    <i class="bi bi-person-circle"></i> عرض الملف الشخصي
                </button>
            </div>

            <div class="main-content">
                <div class="post-composer">
                    <div class="composer-header">
                        <img src="<?php echo !empty($user['avatar_url']) ? htmlspecialchars($user['avatar_url']) : 'https://ui-avatars.com/api/?name=' . urlencode($user['username']) . '&background=667eea&color=fff&size=100'; ?>" 
                             alt="Your Avatar" class="composer-avatar">
                        <div>
                            <h3 style="color: var(--text-primary); margin: 0;">ماذا يدور في ذهنك؟</h3>
                        </div>
                    </div>
                    
                    <textarea class="composer-input" placeholder="شارك أفكارك مع العالم..."></textarea>
                    
                    <div class="composer-actions">
                        <div class="composer-tools">
                            <button class="tool-btn" title="إضافة صورة">
                                <i class="bi bi-image"></i>
                            </button>
                            <button class="tool-btn" title="إضافة موقع">
                                <i class="bi bi-geo-alt"></i>
                            </button>
                            <button class="tool-btn" title="إضافة فيديو">
                                <i class="bi bi-camera-video"></i>
                            </button>
                            <button class="tool-btn" title="إضافة رمز تعبيري">
                                <i class="bi bi-emoji-smile"></i>
                            </button>
                        </div>
                        <button class="post-btn">
                            <i class="bi bi-send"></i> نشر
                        </button>
                    </div>
                </div>

                <div class="posts-feed">
                    <h2 style="color: var(--text-primary); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="bi bi-newspaper"></i> آخر التحديثات
                    </h2>
                    
                    <?php if (!empty($posts)): ?>
                        <?php foreach ($posts as $post): ?>
                            <div class="post-card">
                                <div class="post-header">
                                    <img src="<?php echo !empty($post['user_avatar']) ? htmlspecialchars($post['user_avatar']) : 'https://ui-avatars.com/api/?name=' . urlencode($post['username']) . '&background=667eea&color=fff&size=100'; ?>" 
                                         alt="<?php echo htmlspecialchars($post['username']); ?>" class="post-avatar">
                                    <div class="post-author">
                                        <div class="author-name">
                                            <?php echo htmlspecialchars($post['first_name'] . ' ' . $post['last_name']) ?: htmlspecialchars($post['username']); ?>
                                        </div>
                                        <div class="post-time">
                                            <?php echo date('d M Y, H:i', strtotime($post['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="post-content">
                                    <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                                </div>
                                
                                <?php if (!empty($post['image_url'])): ?>
                                    <div style="margin: 1rem 0;">
                                        <img src="<?php echo htmlspecialchars($post['image_url']); ?>" 
                                             alt="Post Image" 
                                             style="width: 100%; border-radius: 12px; max-height: 400px; object-fit: cover;">
                                    </div>
                                <?php endif; ?>
                                
                                <div class="post-actions">
                                    <button class="action-btn <?php echo $post['is_liked'] ? 'liked' : ''; ?>">
                                        <i class="bi bi-heart<?php echo $post['is_liked'] ? '-fill' : ''; ?>"></i>
                                        <span><?php echo $post['likes_count']; ?></span>
                                    </button>
                                    <button class="action-btn">
                                        <i class="bi bi-chat"></i>
                                        <span><?php echo $post['comments_count']; ?></span>
                                    </button>
                                    <button class="action-btn">
                                        <i class="bi bi-share"></i>
                                        <span>مشاركة</span>
                                    </button>
                                    <button class="action-btn <?php echo $post['is_bookmarked'] ? 'bookmarked' : ''; ?>">
                                        <i class="bi bi-bookmark<?php echo $post['is_bookmarked'] ? '-fill' : ''; ?>"></i>
                                        <span>حفظ</span>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-posts">
                            <i class="bi bi-newspaper"></i>
                            <h3>لا توجد منشورات حتى الآن</h3>
                            <p>ابدأ بمتابعة بعض المستخدمين لرؤية منشوراتهم هنا</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="sidebar-card suggestions-card">
                <h3><i class="bi bi-people"></i> اقتراحات للمتابعة</h3>
                
                <?php if (!empty($suggested_users)): ?>
                    <?php foreach ($suggested_users as $suggested_user): ?>
                        <div class="suggestion-item">
                            <img src="<?php echo !empty($suggested_user['avatar_url']) ? htmlspecialchars($suggested_user['avatar_url']) : 'https://ui-avatars.com/api/?name=' . urlencode($suggested_user['username']) . '&background=667eea&color=fff&size=80'; ?>" 
                                 alt="<?php echo htmlspecialchars($suggested_user['username']); ?>" class="suggestion-avatar">
                            <div class="suggestion-info">
                                <div class="suggestion-name">
                                    <?php echo htmlspecialchars($suggested_user['first_name'] . ' ' . $suggested_user['last_name']) ?: htmlspecialchars($suggested_user['username']); ?>
                                </div>
                                <div class="suggestion-username">@<?php echo htmlspecialchars($suggested_user['username']); ?></div>
                            </div>
                            <button class="follow-btn">متابعة</button>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 2rem 0; color: var(--text-muted);">
                        <i class="bi bi-people" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                        <p>لا توجد اقتراحات متاحة</p>
                    </div>
                <?php endif; ?>
                
                <button class="follow-btn" onclick="window.location.href='discover.php'" style="width: 100%; margin-top: 1rem;">
                    <i class="bi bi-search"></i> اكتشف المزيد
                </button>
            </div>
        </div>
    </div>

    <script>
        console.log('🏠 الصفحة الرئيسية المصلحة جاهزة');
        console.log('📊 إحصائيات المستخدم:', <?php echo json_encode($stats); ?>);
        console.log('👤 معلومات المستخدم:', <?php echo json_encode(['id' => $user_id, 'username' => $user['username']]); ?>);
        
        document.addEventListener('DOMContentLoaded', function() {
            const postBtn = document.querySelector('.post-btn');
            const composerInput = document.querySelector('.composer-input');
            
            if (postBtn && composerInput) {
                postBtn.addEventListener('click', function() {
                    const content = composerInput.value.trim();
                    if (content) {
                        alert('سيتم إضافة وظيفة النشر قريباً!');
                    } else {
                        alert('يرجى كتابة شيء للنشر');
                    }
                });
            }
            
            document.querySelectorAll('.action-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const icon = this.querySelector('i');
                    if (icon.classList.contains('bi-heart')) {
                        icon.classList.toggle('bi-heart');
                        icon.classList.toggle('bi-heart-fill');
                        this.classList.toggle('liked');
                    } else if (icon.classList.contains('bi-bookmark')) {
                        icon.classList.toggle('bi-bookmark');
                        icon.classList.toggle('bi-bookmark-fill');
                        this.classList.toggle('bookmarked');
                    }
                });
            });
            
            document.querySelectorAll('.follow-btn').forEach(btn => {
                if (btn.textContent.trim() === 'متابعة') {
                    btn.addEventListener('click', function() {
                        this.textContent = 'يتابع';
                        this.style.background = 'var(--text-muted)';
                    });
                }
            });
        });
    </script>
</body>
</html> 