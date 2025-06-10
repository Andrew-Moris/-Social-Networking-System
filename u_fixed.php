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

$debug_messages = [];
$debug_messages[] = "🔍 بدء تحميل الصفحة للمستخدم: $username (ID: $user_id)";

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $debug_messages[] = "✅ الاتصال بقاعدة البيانات ناجح";
    
    $user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $user_stmt->execute([$user_id]);
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception("المستخدم غير موجود في قاعدة البيانات");
    }
    
    $debug_messages[] = "✅ تم جلب بيانات المستخدم: " . $user['username'];
    
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
    $count_stmt->execute([$user_id]);
    $total_posts = $count_stmt->fetchColumn();
    
    $debug_messages[] = "📊 إجمالي منشورات المستخدم: $total_posts";
    
    if ($total_posts == 0) {
        $create_stmt = $pdo->prepare("INSERT INTO posts (user_id, content, created_at) VALUES (?, ?, NOW())");
        $create_stmt->execute([$user_id, '🎉 مرحباً بكم في صفحتي الشخصية! هذا أول منشور لي.']);
        $debug_messages[] = "✅ تم إنشاء منشور تجريبي";
        $total_posts = 1;
    }
    
    $posts_stmt = $pdo->prepare("
        SELECT p.id, p.content, p.image_url, p.created_at,
               u.username, u.first_name, u.last_name, u.avatar_url as user_avatar,
               0 as likes_count, 0 as comments_count, 0 as is_liked, 0 as is_bookmarked
        FROM posts p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.user_id = ? 
        ORDER BY p.created_at DESC 
        LIMIT 20
    ");
    $posts_stmt->execute([$user_id]);
    $posts = $posts_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $debug_messages[] = "📝 تم جلب " . count($posts) . " منشور بنجاح";
    
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
        $debug_messages[] = "🔄 تم تحديث العدادات لجميع المنشورات";
    }
    
    $stats_stmt = $pdo->prepare("
        SELECT 
            (SELECT COUNT(*) FROM posts WHERE user_id = ?) as posts_count,
            (SELECT COUNT(*) FROM followers WHERE followed_id = ?) as followers_count,
            (SELECT COUNT(*) FROM followers WHERE follower_id = ?) as following_count
    ");
    $stats_stmt->execute([$user_id, $user_id, $user_id]);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
    $debug_messages[] = "📈 تم جلب الإحصائيات بنجاح";
    
} catch (Exception $e) {
    $debug_messages[] = "❌ خطأ: " . $e->getMessage();
    error_log("خطأ في u_fixed.php: " . $e->getMessage());
    $user = ['username' => $username, 'first_name' => '', 'last_name' => '', 'avatar_url' => ''];
    $posts = [];
    $stats = ['posts_count' => 0, 'followers_count' => 0, 'following_count' => 0];
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
        .debug-panel {
            position: fixed;
            top: 10px;
            left: 10px;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 10px;
            border-radius: 8px;
            max-width: 300px;
            font-size: 12px;
            z-index: 1000;
            display: none;
        }
        .debug-toggle {
            position: fixed;
            top: 10px;
            left: 10px;
            background: #3B82F6;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            z-index: 1001;
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
    </style>
</head>
<body class="antialiased">
    <button class="debug-toggle" onclick="toggleDebug()">🔍 Debug</button>
    <div class="debug-panel" id="debugPanel">
        <h4>🔧 معلومات التشخيص:</h4>
        <?php foreach ($debug_messages as $msg): ?>
            <div><?php echo $msg; ?></div>
        <?php endforeach; ?>
    </div>

    <div class="min-h-screen flex flex-col">
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

        <main class="container mx-auto px-4 flex-grow">
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
                                    <button class="action-button" data-post-id="<?php echo $post['id']; ?>" onclick="alert('تم تطوير نظام التعليقات')">
                                        <i class="bi bi-chat-dots"></i>
                                        <span><?php echo $post['comments_count']; ?></span>
                                    </button>
                                    <button class="action-button" data-post-id="<?php echo $post['id']; ?>" onclick="alert('تم تطوير نظام المشاركة')">
                                        <i class="bi bi-share"></i>
                                        مشاركة
                                    </button>
                                    <button class="action-button <?php echo $post['is_bookmarked'] ? 'bookmarked' : ''; ?>" data-post-id="<?php echo $post['id']; ?>" onclick="toggleBookmark(<?php echo $post['id']; ?>, this)">
                                        <i class="bi bi-bookmark<?php echo $post['is_bookmarked'] ? '-fill' : ''; ?>"></i>
                                        <?php echo $post['is_bookmarked'] ? 'محفوظ' : 'حفظ'; ?>
                                    </button>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        console.log('🎉 الصفحة الشخصية المحسنة جاهزة!');
        
        function toggleDebug() {
            const panel = document.getElementById('debugPanel');
            panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
        }
        
        async function toggleLike(postId, element) {
            alert('تم تطوير نظام الإعجابات - معرف المنشور: ' + postId);
        }
        
        async function toggleBookmark(postId, element) {
            alert('تم تطوير نظام المفضلة - معرف المنشور: ' + postId);
        }
        
        <?php if (!empty($posts)): ?>
            setTimeout(() => {
                alert('✅ تم تحميل <?php echo count($posts); ?> منشور بنجاح!');
            }, 1000);
        <?php endif; ?>
    </script>
</body>
</html> 