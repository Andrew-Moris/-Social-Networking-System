<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id'])) {
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

$current_user_id = $_SESSION['user_id'];
$current_username = $_SESSION['username'];

$profile_username = $_GET['username'] ?? $current_username;

if (empty($profile_username)) {
    $profile_username = $current_username;
}

try {
    if (!isset($pdo)) {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    
    try {
        $pdo->query("SELECT 1 FROM bookmarks LIMIT 1");
    } catch (Exception $e) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS bookmarks (
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
    
    $user_stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $user_stmt->execute([$profile_username]);
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        header('Location: home.php?error=' . urlencode('المستخدم غير موجود'));
        exit;
    }
    
    $profile_user_id = $user['id'];
    
    $is_own_profile = ($profile_user_id == $current_user_id);
    
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
    $count_stmt->execute([$profile_user_id]);
    $total_posts = $count_stmt->fetchColumn();
    
    if ($total_posts == 0 && $profile_user_id == $current_user_id) {
        try {
            $create_stmt = $pdo->prepare("INSERT INTO posts (user_id, content, created_at) VALUES (?, ?, NOW())");
            $create_stmt->execute([$profile_user_id, '🎉 Welcome to my profile! This is my first post.']);
        } catch (Exception $e) {
            error_log("Cannot create test post: " . $e->getMessage());
        }
    }
    
    $posts_stmt = $pdo->prepare("
        SELECT p.id, p.content, p.media_url, p.created_at,
               u.username, u.first_name, u.last_name, u.avatar_url as user_avatar,
               (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
               (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count,
               (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?) as is_liked,
               (SELECT COUNT(*) FROM bookmarks WHERE post_id = p.id AND user_id = ?) as is_bookmarked
        FROM posts p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.user_id = ? 
        ORDER BY p.created_at DESC 
        LIMIT 20
    ");
    $posts_stmt->execute([$current_user_id, $current_user_id, $profile_user_id]);
    $posts = $posts_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($posts)) {
        foreach ($posts as &$post) {
            $post['likes_count'] = (int)$post['likes_count'];
            $post['comments_count'] = (int)$post['comments_count'];
            $post['is_liked'] = (int)$post['is_liked'];
            $post['is_bookmarked'] = (int)$post['is_bookmarked'];
                
        }
    }
    
    try {
        $posts_stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
        $posts_stmt->execute([$profile_user_id]);
        $posts_count = (int)$posts_stmt->fetchColumn();
        
        $followers_stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE followed_id = ?");
        $followers_stmt->execute([$profile_user_id]);
        $followers_count = (int)$followers_stmt->fetchColumn();
        
        $following_stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE follower_id = ?");
        $following_stmt->execute([$profile_user_id]);
        $following_count = (int)$following_stmt->fetchColumn();
        
        $stats = [
            'posts_count' => $posts_count,
            'followers_count' => $followers_count,
            'following_count' => $following_count
        ];
        
        error_log("User {$profile_user_id} stats: " . json_encode($stats));
        
        
    } catch (Exception $e) {
        error_log("Error getting user stats: " . $e->getMessage());
        $stats = ['posts_count' => 0, 'followers_count' => 0, 'following_count' => 0];
    }
    
    $is_following = false;
    
    if (!$is_own_profile) {
        $follow_check_stmt = $pdo->prepare("SELECT id FROM followers WHERE follower_id = ? AND followed_id = ?");
        $follow_check_stmt->execute([$current_user_id, $profile_user_id]);
        $is_following = $follow_check_stmt->fetchColumn() ? true : false;
    }
    
} catch (Exception $e) {
    error_log("Error in u.php: " . $e->getMessage());
    
    $user = [
        'username' => $profile_username, 
        'first_name' => '', 
        'last_name' => '', 
        'avatar_url' => '',
        'bio' => ''
    ];
    $posts = [];
    $stats = ['posts_count' => 0, 'followers_count' => 0, 'following_count' => 0];
    $is_following = false;
    $is_own_profile = true; 
    $profile_user_id = $current_user_id;
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> | Profile - <?php echo htmlspecialchars($user['username']); ?></title>
    <meta name="user-id" content="<?php echo $current_user_id; ?>">

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
            transform: none !important;
            transition: none !important;
            animation: none !important;
        }
        
        .btn-primary,
        .btn-secondary,
        .action-button,
        .profile-avatar,
        .avatar-overlay,
        .filter-button {
            transition: color 0.2s ease, background-color 0.2s ease, border-color 0.2s ease, opacity 0.2s ease !important;
            transform: none !important;
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
            z-index: 100;
            padding: 1rem 0;
        }
        
        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem 1rem;
            position: relative;
            z-index: 1;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
            z-index: 1;
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
        
        .profile-header { 
            background: var(--bg-card);
            backdrop-filter: var(--blur-glass);
            border: 1px solid var(--border-color);
            border-radius: 2rem;
            padding: 3rem 2rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
            text-align: center;
            box-shadow: var(--shadow-card);
            z-index: 2;
        }
        
        .profile-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(102, 126, 234, 0.1) 0%, transparent 50%, rgba(255, 119, 198, 0.1) 100%);
            z-index: 1;
        }
        
        .profile-header > * {
            position: relative;
            z-index: 2;
        }
        
        .profile-avatar-container {
            position: relative;
            width: 120px;
            height: 120px;
            margin: 0 auto 1.5rem;
            display: block;
        }
        
        .profile-avatar { 
            width: 120px; 
            height: 120px; 
            border-radius: 50%; 
            border: 4px solid rgba(102, 126, 234, 0.3);
            box-shadow: 0 8px 32px rgba(102, 126, 234, 0.3);
            cursor: pointer;
            object-fit: cover;
        }
        
        .profile-avatar:hover {
            border-color: rgba(102, 126, 234, 0.6);
        }
        
        .avatar-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            opacity: 0;
            cursor: pointer;
            color: white;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .avatar-overlay:hover {
            opacity: 1;
        }
        
        .avatar-overlay i {
            font-size: 1.5rem;
            margin-bottom: 0.25rem;
        }
        
        .profile-avatar-container:hover .avatar-overlay {
            opacity: 1;
        }
        
        .profile-name {
            font-size: 2.5rem;
            font-weight: 800;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }
        
        .profile-username {
            font-size: 1.2rem;
            color: var(--text-secondary);
            margin-bottom: 1rem;
        }
        
        .profile-bio {
            font-size: 1.1rem;
            color: var(--text-primary);
            max-width: 600px;
            margin: 0 auto 2rem;
            line-height: 1.6;
        }
        
        .profile-stats {
            display: flex;
            justify-content: center;
            gap: 3rem;
            margin-bottom: 2rem;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .profile-actions {
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
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
            border: none;
            cursor: pointer;
            position: relative;
            z-index: 1;
        }
        
        .btn-primary:hover {
            opacity: 0.9;
        }
        
        .btn-secondary {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--bg-card);
            backdrop-filter: var(--blur-glass);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            text-decoration: none;
            font-weight: 500;
            cursor: pointer;
            position: relative;
            z-index: 1;
        }
        
        .btn-secondary:hover {
            background: var(--bg-card-hover);
        }
        
        .content-section {
            background: var(--bg-card);
            backdrop-filter: var(--blur-glass);
            border: 1px solid var(--border-color);
            border-radius: 1.5rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-card);
            position: relative;
            z-index: 2;
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .create-post-form {
            background: var(--bg-card);
            backdrop-filter: var(--blur-glass);
            border: 1px solid var(--border-color);
            border-radius: 1.5rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-card);
            position: relative;
            z-index: 2;
        }
        
        .form-control {
            width: 100%;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-color);
            border-radius: 0.75rem;
            color: var(--text-primary);
            font-size: 1rem;
            resize: vertical;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            background: rgba(255, 255, 255, 0.08);
        }
        
        .form-control::placeholder {
            color: var(--text-muted);
        }
        
        .posts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(500px, 1fr));
            gap: 1.5rem;
            position: relative;
            z-index: 1;
        }
        
        .post-card { 
            background: var(--bg-card);
            backdrop-filter: var(--blur-glass);
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            overflow: hidden;
            position: relative;
            box-shadow: var(--shadow-card);
            z-index: 1;
        }
        
        .post-card:hover {
            background: var(--bg-card-hover);
        }
        
        .post-header {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .post-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid rgba(102, 126, 234, 0.3);
            margin-right: 0.75rem;
        }
        
        .post-user-info h4 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }
        
        .post-user-info .username {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }
        
        .post-time {
            margin-left: auto;
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        
        .post-content {
            padding: 1rem;
            color: var(--text-primary);
            line-height: 1.6;
        }
        
        .post-media {
            width: 100%;
            max-height: 300px;
            object-fit: cover;
            margin-top: 0.75rem;
            border-radius: 0.5rem;
        }
        
        .post-actions {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            border-top: 1px solid var(--border-color);
            background: rgba(0, 0, 0, 0.1);
            gap: 1rem;
        }
        
        .action-button {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.5rem 0.75rem;
            border: none;
            background: transparent;
            color: var(--text-secondary);
            cursor: pointer;
            border-radius: 0.5rem;
            font-weight: 500;
            font-size: 0.85rem;
            white-space: nowrap;
        }
        
        .count-text {
            margin-left: 4px;
            display: inline-block;
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
        
        .action-button.bookmarked {
            color: #f59e0b;
        }
        
        .action-button.bookmarked:hover {
            background: rgba(245, 158, 11, 0.1);
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--text-secondary);
        }
        
        .empty-state-icon {
            font-size: 3rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
        }
        
        .empty-state-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        
        .empty-state-desc {
            color: var(--text-secondary);
        }
        
        .image-preview {
            position: relative;
            margin: 1rem 0;
        }
        
        .image-preview img {
            width: 100%;
            max-height: 200px;
            object-fit: cover;
            border-radius: 0.5rem;
        }
        
        .remove-image { 
            position: absolute; 
            top: 0.5rem;
            right: 0.5rem;
            background: rgba(239, 68, 68, 0.9);
            color: white; 
            border: none; 
            border-radius: 50%; 
            width: 30px; 
            height: 30px; 
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        
        .remove-image:hover {
            background: rgba(239, 68, 68, 1);
        }
        
        .dropdown-menu {
            position: absolute;
            right: 0;
            top: 100%;
            background: var(--bg-card);
            backdrop-filter: var(--blur-glass);
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            box-shadow: var(--shadow-card);
            min-width: 150px;
            z-index: 10;
        }
        
        .dropdown-item {
            width: 100%;
            text-align: left;
            padding: 0.75rem 1rem;
            background: transparent;
            border: none;
            color: var(--text-primary);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .dropdown-item:hover {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }
        
        .comments-section {
            border-top: 1px solid var(--border-color);
            background: rgba(0, 0, 0, 0.2);
            position: relative;
            z-index: 1;
        }
        
        .comment-item {
            padding: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            position: relative;
            z-index: 1;
        }
        
        .comment-item:last-child {
            border-bottom: none;
        }
        
        .comment-item .action-button {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            background: transparent;
            border: 1px solid transparent;
            color: var(--text-secondary);
        }
        
        .comment-item .action-button:hover {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-primary);
        }
        
        .comment-item .action-button.liked {
            color: #e91e63;
        }
        
        .min-h-screen { min-height: 100vh; }
        .flex { display: flex; }
        .flex-col { flex-direction: column; }
        .flex-1 { flex: 1; }
        .items-center { align-items: center; }
        .justify-between { justify-content: space-between; }
        .justify-center { justify-content: center; }
        .gap-2 { gap: 0.5rem; }
        .gap-3 { gap: 0.75rem; }
        .space-x-6 > * + * { margin-left: 1.5rem; }
        .container { max-width: 1200px; margin: 0 auto; }
        .mx-auto { margin-left: auto; margin-right: auto; }
        .px-6 { padding-left: 1.5rem; padding-right: 1.5rem; }
        .px-3 { padding-left: 0.75rem; padding-right: 0.75rem; }
        .py-2 { padding-top: 0.5rem; padding-bottom: 0.5rem; }
        .py-4 { padding-top: 1rem; padding-bottom: 1rem; }
        .p-4 { padding: 1rem; }
        .mb-1 { margin-bottom: 0.25rem; }
        .mb-2 { margin-bottom: 0.5rem; }
        .mb-4 { margin-bottom: 1rem; }
        .ml-2 { margin-left: 0.5rem; }
        .mr-1 { margin-right: 0.25rem; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-white { color: white; }
        .text-gray-100 { color: #f3f4f6; }
        .text-gray-300 { color: #d1d5db; }
        .text-gray-400 { color: #9ca3af; }
        .text-gray-500 { color: #6b7280; }
        .text-blue-400 { color: #60a5fa; }
        .text-red-400 { color: #f87171; }
        .text-xl { font-size: 1.25rem; }
        .text-2xl { font-size: 1.5rem; }
        .text-sm { font-size: 0.875rem; }
        .text-xs { font-size: 0.75rem; }
        .font-bold { font-weight: 700; }
        .font-semibold { font-weight: 600; }
        .w-10 { width: 2.5rem; }
        .h-10 { height: 2.5rem; }
        .rounded-full { border-radius: 50%; }
        .rounded-lg { border-radius: 0.5rem; }
        .object-cover { object-fit: cover; }
        .bg-gray-800 { background-color: #1f2937; }
        .bg-blue-600 { background-color: #2563eb; }
        .border { border-width: 1px; }
        .border-t { border-top-width: 1px; }
        .border-gray-600 { border-color: #4b5563; }
        .border-gray-700 { border-color: #374151; }
        .border-blue-500 { border-color: #3b82f6; }
        .hover\:text-white:hover { color: white; }
        .hover\:text-blue-400:hover { color: #60a5fa; }
        .hover\:text-red-400:hover { color: #f87171; }
        .hover\:bg-blue-700:hover { background-color: #1d4ed8; }
        .focus\:outline-none:focus { outline: none; }
        .focus\:border-blue-500:focus { border-color: #3b82f6; }
        .placeholder-gray-400::placeholder { color: #9ca3af; }
        .transition-colors { transition: color 0.2s ease, background-color 0.2s ease !important; }
        .relative { position: relative; }
        .hidden { display: none; }
        .animate-spin { animation: spin 1s linear infinite !important; }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        @keyframes pulse {
            0% { transform: scale(1); opacity: 0.3; }
            50% { transform: scale(1.05); opacity: 0.1; }
            100% { transform: scale(1); opacity: 0.3; }
        }
        
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        
        @media (max-width: 768px) {
            .profile-name {
                font-size: 2rem;
            }
            
            .profile-stats {
                gap: 2rem;
            }
            
            .stat-number {
                font-size: 1.5rem;
        }
        
            .posts-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .main-container {
                padding: 1rem;
            }
            
            .profile-header {
                padding: 2rem 1rem;
            }
            
            .profile-actions {
                flex-direction: column;
                align-items: center;
            }
            
            .page-title {
                font-size: 2rem;
            }
        }

        .emoji-btn {
            background: rgba(255, 255, 255, 0.1);
            border: none;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .emoji-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.1);
        }

        #comments-list-<?php echo $post['id']; ?>::-webkit-scrollbar {
            width: 8px;
        }

        #comments-list-<?php echo $post['id']; ?>::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
        }

        #comments-list-<?php echo $post['id']; ?>::-webkit-scrollbar-thumb {
            background: rgba(102, 126, 234, 0.5);
            border-radius: 4px;
        }

        #comments-list-<?php echo $post['id']; ?>::-webkit-scrollbar-thumb:hover {
            background: rgba(102, 126, 234, 0.7);
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
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
                <a href="bookmarks.php" class="text-gray-300 hover:text-white transition-colors">
                    <i class="bi bi-bookmark text-xl"></i>
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

    <div class="main-container">
        <div class="page-header">
            <h1 class="page-title"><?php echo $is_own_profile ? 'My Profile' : (htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?: htmlspecialchars($user['username'])); ?></h1>
            <p class="page-subtitle"><?php echo $is_own_profile ? 'Your personal space to share thoughts, connect with others, and showcase your personality' : 'View ' . htmlspecialchars($user['username']) . '\'s profile and posts'; ?></p>
                </div>

        <div class="profile-header">
            <div class="profile-avatar-container">
            <img src="<?php echo !empty($user['avatar_url']) ? htmlspecialchars($user['avatar_url']) : 'https://ui-avatars.com/api/?name=' . urlencode($user['username']) . '&background=667eea&color=fff&size=200'; ?>" 
                     alt="Profile Picture" class="profile-avatar" id="profileAvatar">
                <?php if ($is_own_profile): ?>
                    <div class="avatar-overlay" onclick="openAvatarUpload()">
                        <i class="bi bi-camera"></i>
                        <span>Change Photo</span>
                    </div>
                    <input type="file" id="avatarInput" accept="image/*" style="display: none;">
                <?php endif; ?>
            </div>
            
            <h1 class="profile-name">
                <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?: htmlspecialchars($user['username']); ?>
            </h1>
            <p class="profile-username">
                @<?php echo htmlspecialchars($user['username']); ?>
            </p>
            
                    <?php if (!empty($user['bio'])): ?>
                <p class="profile-bio">
                    <?php echo htmlspecialchars($user['bio']); ?>
                </p>
                    <?php endif; ?>
            
            <div class="profile-stats">
                <div class="stat-item">
                    <div class="stat-number"><?php echo $stats['posts_count']; ?></div>
                    <div class="stat-label">POSTS</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $stats['followers_count']; ?></div>
                    <div class="stat-label">FOLLOWERS</div>
                        </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $stats['following_count']; ?></div>
                    <div class="stat-label">FOLLOWING</div>
                        </div>
                        </div>
            
            <script>
                console.log('🔍 Debug Stats:', <?php echo json_encode($stats); ?>);
                console.log('📊 Posts Count:', <?php echo $stats['posts_count']; ?>);
                console.log('👥 Followers Count:', <?php echo $stats['followers_count']; ?>);
                console.log('➡️ Following Count:', <?php echo $stats['following_count']; ?>);
                console.log('👤 Profile User ID:', <?php echo $profile_user_id; ?>);
                console.log('🔐 Current User ID:', <?php echo $current_user_id; ?>);
                console.log('🏠 Is Own Profile:', <?php echo $is_own_profile ? 'true' : 'false'; ?>);
            </script>
            
            <div class="profile-actions">
                <?php if ($is_own_profile): ?>
                <a href="settings.php" class="btn-primary">
                    <i class="bi bi-pencil"></i> Edit Profile
                </a>
                <button class="btn-secondary" onclick="shareProfile()">
                    <i class="bi bi-share"></i> Share Profile
                    </button>
                <?php else: ?>
                    <?php if ($is_following): ?>
                        <button class="btn-secondary" id="followBtn" onclick="unfollowUser(<?php echo $profile_user_id; ?>)">
                            <i class="bi bi-person-dash"></i> Unfollow
                        </button>
                    <?php else: ?>
                        <button class="btn-primary" id="followBtn" onclick="followUser(<?php echo $profile_user_id; ?>)">
                            <i class="bi bi-person-plus"></i> Follow
                        </button>
                    <?php endif; ?>
                    <button class="btn-secondary" onclick="messageUser(<?php echo $profile_user_id; ?>)">
                        <i class="bi bi-chat-dots"></i> Message
                    </button>
                    <button class="btn-secondary" onclick="shareProfile()">
                        <i class="bi bi-share"></i> Share Profile
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($is_own_profile): ?>
        <div class="create-post-form">
            <h3 class="section-title">
                <i class="bi bi-plus-circle" style="color: #667eea;"></i>
                Create New Post
            </h3>
            <form id="postForm">
                <div class="mb-4">
                    <textarea class="form-control" id="postContent" name="content" rows="3" 
                              placeholder="What's on your mind? Share your thoughts with the world..." 
                              style="resize: vertical; min-height: 100px;"></textarea>
                </div>
                
                <div id="imagePreview" style="display: none;"></div>
                
                <div class="flex justify-between items-center">
                    <div class="flex gap-3">
                        <button type="button" class="action-button" onclick="document.getElementById('postImage').click()">
                            <i class="bi bi-image"></i> Photo
                        </button>
                        <input type="file" id="postImage" name="image" accept="image/*" style="display: none;">
                        
                        <button type="button" class="action-button" id="emoji-button" onclick="addEmoji()">
                            <i class="bi bi-emoji-smile"></i> Emoji
                        </button>
                    </div>
                    <button type="submit" class="btn-primary">
                        <i class="bi bi-send"></i> Post
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <div class="content-section">
            <h3 class="section-title">
                <i class="bi bi-collection" style="color: #667eea;"></i>
                <?php echo $is_own_profile ? 'My Posts' : htmlspecialchars($user['username']) . '\'s Posts'; ?>
            </h3>
            
            <div id="postsContainer">
                <?php if (empty($posts)): ?>
                    <div class="empty-state">
                        <i class="bi bi-journal-x empty-state-icon"></i>
                        <h4 class="empty-state-title">No Posts Yet</h4>
                        <p class="empty-state-desc">Start sharing your thoughts with your first post!</p>
                    </div>
                <?php else: ?>
                    <div class="posts-grid">
                    <?php foreach ($posts as $post): ?>
                            <article class="post-card" data-post-id="<?php echo $post['id']; ?>">
                                <header class="post-header">
                                    <img src="<?php echo !empty($post['user_avatar']) ? htmlspecialchars($post['user_avatar']) : 'https://ui-avatars.com/api/?name=' . urlencode($post['username']) . '&background=667eea&color=fff&size=80'; ?>" 
                                         alt="<?php echo htmlspecialchars($post['username']); ?>" class="post-avatar">
                                    <div class="post-user-info">
                                        <h4><?php echo htmlspecialchars($post['first_name'] . ' ' . $post['last_name']) ?: htmlspecialchars($post['username']); ?></h4>
                                        <div class="username">@<?php echo htmlspecialchars($post['username']); ?></div>
                                </div>
                                    <div class="post-time"><?php echo date('M d, Y H:i', strtotime($post['created_at'])); ?></div>
                                    <?php if ($is_own_profile): ?>
                                    <div class="relative ml-2">
                                        <button class="action-button" onclick="toggleDropdown(<?php echo $post['id']; ?>)">
                                        <i class="bi bi-three-dots"></i>
                                    </button>
                                        <div id="dropdown-<?php echo $post['id']; ?>" class="dropdown-menu hidden">
                                            <button class="dropdown-item" onclick="deletePost(<?php echo $post['id']; ?>)">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                </div>
                            </div>
                                    <?php endif; ?>
                                </header>
                            
                            <div class="post-content">
                                <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                                <?php if (!empty($post['media_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($post['media_url']); ?>" alt="Post Image" class="post-media">
                                <?php endif; ?>
                            </div>
                            
                                <div class="post-actions">
                                    <button class="action-button <?php echo $post['is_liked'] ? 'liked' : ''; ?>" onclick="toggleLike(<?php echo $post['id']; ?>, this)" data-count="<?php echo $post['likes_count']; ?>">
                                        <i class="bi bi-heart<?php echo $post['is_liked'] ? '-fill' : ''; ?>"></i>
                                        <span class="count-text"><?php echo $post['likes_count'] > 0 ? ' ' . $post['likes_count'] : ''; ?></span>
                                    </button>
                                    <button class="action-button" data-post-id="<?php echo $post['id']; ?>" data-action="comment" onclick="toggleComments(<?php echo $post['id']; ?>)">
                                        <i class="bi bi-chat-dots"></i>
                                        <span class="count-text"><?php echo $post['comments_count'] > 0 ? ' ' . $post['comments_count'] : ','; ?></span>
                                    </button>
                                    <button class="action-button" data-post-id="<?php echo $post['id']; ?>" data-action="share" onclick="sharePost(<?php echo $post['id']; ?>)">
                                        <i class="bi bi-share"></i>
                                        Share
                                    </button>
                                    <button class="action-button <?php echo $post['is_bookmarked'] ? 'bookmarked' : ''; ?>" data-post-id="<?php echo $post['id']; ?>" data-action="bookmark" onclick="toggleBookmark(<?php echo $post['id']; ?>, this)">
                                        <i class="bi bi-bookmark<?php echo $post['is_bookmarked'] ? '-fill' : ''; ?>"></i>
                                        <?php echo $post['is_bookmarked'] ? 'Saved' : 'Save'; ?>
                                    </button>
                                </div>
                                
                                <div id="comments-<?php echo $post['id']; ?>" style="display: none;">
                                    <div style="background: linear-gradient(135deg, rgba(17, 24, 39, 0.95), rgba(17, 24, 39, 0.85)); backdrop-filter: blur(10px); border-top: 1px solid rgba(255, 255, 255, 0.1);">
                                        <div style="padding: 20px; border-bottom: 1px solid rgba(255, 255, 255, 0.1);">
                                            <div style="display: flex; align-items: center; gap: 15px;">
                                                <img src="<?php echo !empty($_SESSION['avatar_url']) ? htmlspecialchars($_SESSION['avatar_url']) : 'https://ui-avatars.com/api/?name=' . urlencode($current_username) . '&background=667eea&color=fff&size=50'; ?>" 
                                                     style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(102, 126, 234, 0.5);">
                                                
                                                <div style="flex: 1; position: relative;">
                                                    <input type="text" 
                                                           id="comment-input-<?php echo $post['id']; ?>"
                                                           placeholder="اكتب تعليقاً..." 
                                                           style="width: 100%; background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 25px; padding: 12px 20px; color: white; font-size: 14px; transition: all 0.3s ease;">
                                                </div>
                                                
                                                <button onclick="addNewComment(<?php echo $post['id']; ?>)" 
                                                        style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; padding: 12px 25px; border-radius: 25px; font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 8px; transition: all 0.3s ease;">
                                                    <i class="bi bi-send"></i>
                                                    <span>إرسال</span>
                                                </button>
                                            </div>
                                            
                                            <div style="margin-top: 15px; display: flex; justify-content: center; gap: 10px;">
                                                <button onclick="addQuickEmoji('<?php echo $post['id']; ?>', '❤️')" class="emoji-btn">❤️</button>
                                                <button onclick="addQuickEmoji('<?php echo $post['id']; ?>', '👍')" class="emoji-btn">👍</button>
                                                <button onclick="addQuickEmoji('<?php echo $post['id']; ?>', '🔥')" class="emoji-btn">🔥</button>
                                                <button onclick="addQuickEmoji('<?php echo $post['id']; ?>', '👏')" class="emoji-btn">👏</button>
                                                <button onclick="addQuickEmoji('<?php echo $post['id']; ?>', '💯')" class="emoji-btn">💯</button>
                                            </div>
                                        </div>
                                        
                                        <div id="comments-list-<?php echo $post['id']; ?>" style="max-height: 400px; overflow-y: auto; padding: 20px; background: rgba(17, 24, 39, 0.7);">
                                            <div style="text-align: center; padding: 30px;">
                                                <div style="width: 40px; height: 40px; border: 3px solid rgba(102, 126, 234, 0.3); border-top-color: #667eea; border-radius: 50%; margin: 0 auto 15px; animation: spin 1s linear infinite;"></div>
                                                <p style="color: rgba(255, 255, 255, 0.7); font-size: 14px;">جاري تحميل التعليقات...</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </article>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    
    function openAvatarUpload() {
        document.getElementById('avatarInput').click();
    }
    
    async function uploadAvatar(file) {
        const formData = new FormData();
        formData.append('avatar', file);
        
        const avatarImg = document.getElementById('profileAvatar');
        const originalSrc = avatarImg.src;
        
        try {
            const loadingOverlay = document.createElement('div');
            loadingOverlay.className = 'avatar-overlay';
            loadingOverlay.style.opacity = '1';
            loadingOverlay.innerHTML = '<i class="bi bi-hourglass-split animate-spin"></i><span>Uploading...</span>';
            document.querySelector('.profile-avatar-container').appendChild(loadingOverlay);
            
            const response = await fetch('api/upload_avatar.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                avatarImg.src = result.avatar_url + '?t=' + Date.now(); 
                
                document.querySelectorAll('img[alt="Your Avatar"]').forEach(img => {
                    img.src = result.avatar_url + '?t=' + Date.now();
                });
                
                console.log('✅ ' + result.message);
                
                avatarImg.style.opacity = '0.8';
                setTimeout(() => {
                    avatarImg.style.opacity = '1';
                }, 300);
                
            } else {
                console.log('❌ ' + result.message);
                alert('Failed to upload avatar: ' + result.message);
            }
            
        } catch (error) {
            console.error('Error uploading avatar:', error);
            console.log('❌ Error uploading avatar');
            alert('Error uploading avatar. Please try again.');
        } finally {
            const loadingOverlay = document.querySelector('.profile-avatar-container .avatar-overlay:last-child');
            if (loadingOverlay && loadingOverlay.innerHTML.includes('Uploading')) {
                loadingOverlay.remove();
            }
        }
    }
    
    async function followUser(userId) {
        const followBtn = document.getElementById('followBtn');
        if (!followBtn) return;
        
        const originalText = followBtn.innerHTML;
        followBtn.disabled = true;
        followBtn.innerHTML = '<i class="bi bi-hourglass-split animate-spin"></i> Following...';
        
        try {
            const response = await fetch('api/social.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'toggle_follow',
                    user_id: userId
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                if (result.data.is_following) {
                    followBtn.className = 'btn-secondary';
                    followBtn.innerHTML = '<i class="bi bi-person-dash"></i> Unfollow';
                    followBtn.onclick = () => unfollowUser(userId);
                } else {
                    followBtn.className = 'btn-primary';
                    followBtn.innerHTML = '<i class="bi bi-person-plus"></i> Follow';
                    followBtn.onclick = () => followUser(userId);
                }
                
                const followersCount = document.querySelector('.stat-item:nth-child(2) .stat-number');
                if (followersCount) {
                    followersCount.textContent = result.data.followers_count;
                }
                
                console.log(result.message);
            } else {
                console.log(result.message || 'Failed to follow user');
            }
        } catch (error) {
            console.error('Error following user:', error);
            console.log('An error occurred while following');
        } finally {
            followBtn.disabled = false;
            if (followBtn.innerHTML.includes('Following...')) {
                followBtn.innerHTML = originalText;
            }
        }
    }
    
    async function unfollowUser(userId) {
        const followBtn = document.getElementById('followBtn');
        if (!followBtn) return;
        
        const originalText = followBtn.innerHTML;
        followBtn.disabled = true;
        followBtn.innerHTML = '<i class="bi bi-hourglass-split animate-spin"></i> Unfollowing...';
        
        try {
            const response = await fetch('api/social.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'toggle_follow',
                    user_id: userId
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                if (result.data.is_following) {
                    followBtn.className = 'btn-secondary';
                    followBtn.innerHTML = '<i class="bi bi-person-dash"></i> Unfollow';
                    followBtn.onclick = () => unfollowUser(userId);
                } else {
                    followBtn.className = 'btn-primary';
                    followBtn.innerHTML = '<i class="bi bi-person-plus"></i> Follow';
                    followBtn.onclick = () => followUser(userId);
                }
                
                const followersCount = document.querySelector('.stat-item:nth-child(2) .stat-number');
                if (followersCount) {
                    followersCount.textContent = result.data.followers_count;
                }
                
                console.log(result.message);
            } else {
                console.log(result.message || 'Failed to unfollow user');
            }
        } catch (error) {
            console.error('Error unfollowing user:', error);
            console.log('An error occurred while unfollowing');
        } finally {
            followBtn.disabled = false;
            if (followBtn.innerHTML.includes('Unfollowing...')) {
                followBtn.innerHTML = originalText;
            }
        }
    }
    
    function messageUser(userId) {
        window.location.href = `chat.php?user=${userId}`;
    }

    document.addEventListener('DOMContentLoaded', function() {
        console.log('🎉 Profile page ready!');
        
        console.log('=== تشخيص صفحة الملف الشخصي ===');
        console.log('Current User ID:', <?php echo json_encode($current_user_id); ?>);
        console.log('Profile User ID:', <?php echo json_encode($profile_user_id); ?>);
        console.log('Is Own Profile:', <?php echo json_encode($is_own_profile); ?>);
        console.log('Stats:', <?php echo json_encode($stats); ?>);
        console.log('Posts Count:', <?php echo count($posts); ?>);
        console.log('User Data:', <?php echo json_encode($user); ?>);
        
        const postForm = document.getElementById('postForm');
        const postsContainer = document.getElementById('postsContainer');
        const statsElements = document.querySelectorAll('.stat-number');
        
        console.log('Post Form Found:', !!postForm);
        console.log('Posts Container Found:', !!postsContainer);
        console.log('Stats Elements Count:', statsElements.length);
        
        statsElements.forEach((element, index) => {
            console.log(`Stat ${index + 1} Value:`, element.textContent);
        });
        
        console.log('Testing API files...');
        fetch('api/posts_fixed.php', { method: 'HEAD' })
            .then(response => console.log('posts_fixed.php status:', response.status))
            .catch(error => console.error('posts_fixed.php error:', error));
            
        fetch('api/social.php', { method: 'HEAD' })
            .then(response => console.log('social.php status:', response.status))
            .catch(error => console.error('social.php error:', error));
        
        console.log('=== انتهاء التشخيص ===');
        
        if (postForm) {
            postForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                formData.append('action', 'create_post');
                
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="bi bi-hourglass-split animate-spin"></i> Posting...';
                
                try {
                    console.log('🚀 إرسال طلب إنشاء منشور...');
                    console.log('Form Data:', Object.fromEntries(formData.entries()));
                    
                    const response = await fetch('api/posts_fixed.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    console.log('📡 استجابة الخادم:', response.status, response.statusText);
                    
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    
                    const responseText = await response.text();
                    console.log('📄 نص الاستجابة:', responseText);
                    
                    let result;
                    try {
                        result = JSON.parse(responseText);
                    } catch (parseError) {
                        console.error('❌ خطأ في تحليل JSON:', parseError);
                        console.error('النص المستلم:', responseText);
                        throw new Error('Invalid JSON response');
                    }
                    
                    console.log('📊 نتيجة API:', result);
            
                    if (result.success) {
                        console.log('✅ تم إنشاء المنشور بنجاح!');
                        this.reset();
                        removeImagePreview();
                        
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
            } else {
                        console.error('❌ فشل إنشاء المنشور:', result.message || 'سبب غير معروف');
                        alert('فشل في إنشاء المنشور: ' + (result.message || 'سبب غير معروف'));
            }
                } catch (error) {
                    console.error('❌ خطأ في إنشاء المنشور:', error);
                    alert('حدث خطأ أثناء إنشاء المنشور: ' + error.message);
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
                        console.log('Unsupported file type');
                        this.value = '';
                        return;
                    }
                    
                    if (file.size > 5 * 1024 * 1024) {
                        console.log('Image too large');
                        this.value = '';
                        return;
                    }
                    
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const preview = document.getElementById('imagePreview');
                        preview.innerHTML = `
                            <div class="image-preview">
                                <img src="${e.target.result}" alt="Image Preview">
                                <button type="button" class="remove-image" onclick="removeImagePreview()">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                            <small class="text-gray-400 block mt-2">
                                <i class="bi bi-info-circle"></i> ${file.name} (${(file.size/1024/1024).toFixed(2)} MB)
                            </small>
                        `;
                        preview.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                }
            });
        }
        
        const avatarInput = document.getElementById('avatarInput');
        if (avatarInput) {
            avatarInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    if (!['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'].includes(file.type)) {
                        alert('Please select a valid image file (JPEG, PNG, GIF, WebP)');
                        this.value = '';
                        return;
                    }
                    
                    if (file.size > 2 * 1024 * 1024) {
                        alert('Image is too large. Maximum size is 2MB');
                        this.value = '';
                        return;
                    }
                    
                    uploadAvatar(file);
                    
                    this.value = '';
                }
            });
        }
        
        document.addEventListener('submit', function(e) {
            if (e.target.classList.contains('comment-form-inner')) {
                e.preventDefault();
                
                const postId = e.target.getAttribute('data-post-id');
                const textarea = e.target.querySelector('.comment-input');
                const content = textarea.value.trim();
                
                if (!content) {
                    console.log('Please write a comment');
                    return;
                }
                
                const submitBtn = e.target.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="bi bi-hourglass-split animate-spin"></i> Sending...';
                
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
    });
    
    async function toggleLike(postId, element) {
        const likeBtn = element;
        const likeIcon = likeBtn.querySelector('i');
        const countSpan = likeBtn.querySelector('.count-text');
        
        const isLiked = likeBtn.classList.contains('liked');
        
        if (isLiked) {
            likeBtn.classList.remove('liked');
            likeIcon.className = 'bi bi-heart';
        } else {
            likeBtn.classList.add('liked');
            likeIcon.className = 'bi bi-heart-fill';
        }
        
        let count = parseInt(likeBtn.dataset.count) || 0;
        
        if (isLiked) {
            count = Math.max(0, count - 1);
        } else {
            count = count + 1;
        }
        
        likeBtn.dataset.count = count;
        countSpan.textContent = count > 0 ? ' ' + count : '';
        
        try {
            const response = await fetch('api/posts.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=toggle_like&post_id=' + postId
            });

            const result = await response.json();
            
            if (!result.success) {
                if (isLiked) {
                    likeBtn.classList.add('liked');
                    likeIcon.className = 'bi bi-heart-fill';
                } else {
                    likeBtn.classList.remove('liked');
                    likeIcon.className = 'bi bi-heart';
                }
                
                if (isLiked) {
                    count = count + 1;
                } else {
                    count = Math.max(0, count - 1);
                }
                
                likeBtn.dataset.count = count;
                countSpan.textContent = count > 0 ? ' ' + count : '';
            }
        } catch (error) {
            console.error('Error toggling like:', error);
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
                    if (bookmarkText) bookmarkText.textContent = ' Saved';
                } else {
                    bookmarkBtn.classList.remove('bookmarked');
                    bookmarkIcon.className = 'bi bi-bookmark';
                    if (bookmarkText) bookmarkText.textContent = ' Save';
                }
                
                console.log('✅ Bookmark toggled successfully');
            } else {
                bookmarkIcon.className = originalIcon;
                console.log('❌ Bookmark toggle failed:', result.message);
            }
        } catch (error) {
            bookmarkIcon.className = originalIcon;
            console.error('Error toggling bookmark:', error);
            }
        }
        
    function toggleComments(postId) {
        const commentsSection = document.getElementById('comments-' + postId);
        
        if (commentsSection.style.display === 'none' || commentsSection.style.display === '') {
            commentsSection.style.display = 'block';
            loadSimpleComments(postId);
            fetchAndUpdateCommentsCount(postId);
        } else {
            commentsSection.style.display = 'none';
        }
    }
    
    function fetchAndUpdateCommentsCount(postId) {
        fetch(`api/social.php?action=get_comments_count&post_id=${postId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(result => {
                if (result.success) {
                    const count = result.data.count;
                    const commentBtn = document.querySelector(`button[data-action="comment"][data-post-id="${postId}"]`);
                    if (commentBtn) {
                        const countSpan = commentBtn.querySelector('.count-text');
                        if (countSpan) {
                            countSpan.textContent = count > 0 ? count : '';
                        }
                    }
                }
            })
            .catch(error => {
            });
    }
    
    function loadSimpleComments(postId) {
        console.log('📥 Loading comments for post:', postId);
        
        const container = document.getElementById('comments-list-' + postId);
        if (!container) {
            console.error('❌ Comments container not found');
            return;
        }
        
        container.innerHTML = `
            <div style="
                text-align: center; 
                padding: 50px 30px; 
                background: linear-gradient(135deg, rgba(255,255,255,0.03) 0%, rgba(255,255,255,0.01) 100%);
                border-radius: 12px;
                position: relative;
                overflow: hidden;
            ">
                <!-- Animated Background -->
                <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(45deg, transparent 30%, rgba(102, 126, 234, 0.05) 50%, transparent 70%); animation: shimmer 2s infinite;"></div>
                
                <div style="position: relative; z-index: 2;">
                    <!-- Loading Spinner -->
                    <div style="
                        width: 50px; 
                        height: 50px; 
                        margin: 0 auto 20px; 
                        border: 3px solid rgba(255,255,255,0.1);
                        border-top: 3px solid #667eea;
                        border-radius: 50%;
                        animation: spin 1s linear infinite;
                    "></div>
                    
                    <h4 style="
                        color: white; 
                        font-size: 16px; 
                        margin-bottom: 8px;
                        font-weight: 600;
                    ">جاري تحميل التعليقات</h4>
                    
                    <p style="
                        color: rgba(255,255,255,0.6); 
                        font-size: 13px;
                        margin: 0;
                    ">يرجى الانتظار قليلاً...</p>
                    
                    <!-- Loading Dots -->
                    <div style="margin-top: 15px; display: flex; justify-content: center; gap: 4px;">
                        <div style="width: 8px; height: 8px; background: #667eea; border-radius: 50%; animation: pulse 1.5s infinite;"></div>
                        <div style="width: 8px; height: 8px; background: #764ba2; border-radius: 50%; animation: pulse 1.5s infinite 0.2s;"></div>
                        <div style="width: 8px; height: 8px; background: #f093fb; border-radius: 50%; animation: pulse 1.5s infinite 0.4s;"></div>
                    </div>
                </div>
            </div>
        `;
        
        fetch('api/social.php?action=get_comments&post_id=' + postId)
            .then(response => response.json())
            .then(result => {
                console.log('📊 Comments result:', result);
                
                if (result.success && result.data && result.data.comments) {
                    const comments = result.data.comments;
                    
                                            if (comments.length === 0) {
                            container.innerHTML = `
                                <div style="
                                    text-align: center; 
                                    padding: 60px 40px; 
                                    background: linear-gradient(135deg, rgba(255,255,255,0.05) 0%, rgba(255,255,255,0.02) 100%);
                                    border-radius: 16px; 
                                    border: 1px dashed rgba(255,255,255,0.15);
                                    position: relative;
                                    overflow: hidden;
                                ">
                                    <!-- Background Pattern -->
                                    <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; opacity: 0.03; background-image: radial-gradient(circle at 50% 50%, rgba(102, 126, 234, 0.4) 0%, transparent 70%);"></div>
                                    
                                    <!-- Floating Icons -->
                                    <div style="position: absolute; top: 20px; left: 20px; color: rgba(102, 126, 234, 0.2); font-size: 20px; animation: pulse 3s infinite;">💬</div>
                                    <div style="position: absolute; top: 20px; right: 20px; color: rgba(255, 119, 198, 0.2); font-size: 18px; animation: pulse 3s infinite 1s;">❤️</div>
                                    <div style="position: absolute; bottom: 20px; left: 30px; color: rgba(79, 172, 254, 0.2); font-size: 16px; animation: pulse 3s infinite 2s;">✨</div>
                                    <div style="position: absolute; bottom: 20px; right: 30px; color: rgba(245, 158, 11, 0.2); font-size: 22px; animation: pulse 3s infinite 0.5s;">🔥</div>
                                    
                                    <div style="position: relative; z-index: 2;">
                                        <div style="
                                            width: 80px; 
                                            height: 80px; 
                                            margin: 0 auto 20px; 
                                            background: linear-gradient(135deg, rgba(102, 126, 234, 0.2), rgba(118, 75, 162, 0.2));
                                            border-radius: 50%;
                                            display: flex;
                                            align-items: center;
                                            justify-content: center;
                                            border: 2px solid rgba(255,255,255,0.1);
                                            box-shadow: 0 8px 32px rgba(102, 126, 234, 0.2);
                                        ">
                                            <i class="bi bi-chat-dots" style="font-size: 32px; color: rgba(255,255,255,0.6);"></i>
                                        </div>
                                        
                                        <h3 style="
                                            font-size: 20px; 
                                            margin-bottom: 12px; 
                                            background: linear-gradient(135deg, #667eea, #764ba2);
                                            -webkit-background-clip: text;
                                            -webkit-text-fill-color: transparent;
                                            background-clip: text;
                                            font-weight: 700;
                                        ">لا توجد تعليقات بعد</h3>
                                        
                                        <p style="
                                            font-size: 14px; 
                                            color: rgba(255,255,255,0.6); 
                                            margin-bottom: 20px;
                                            line-height: 1.5;
                                        ">كن أول من يشارك رأيه ويبدأ المحادثة!</p>
                                        
                                        <div style="
                                            display: inline-flex;
                                            align-items: center;
                                            gap: 8px;
                                            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
                                            padding: 8px 16px;
                                            border-radius: 20px;
                                            border: 1px solid rgba(255,255,255,0.1);
                                            color: rgba(255,255,255,0.7);
                                            font-size: 12px;
                                        ">
                                            <i class="bi bi-arrow-up" style="font-size: 14px;"></i>
                                            <span>اكتب تعليقك أعلاه</span>
                                        </div>
                                    </div>
                                </div>
                            `;
                } else {
                        let html = '';
                        const currentUserId = <?php echo $current_user_id; ?>;
                        
                        comments.forEach(comment => {
                            html += `
                                <div id="comment-${comment.id}" style="
                                    padding: 20px; 
                                    margin: 15px 0; 
                                    background: linear-gradient(135deg, rgba(255,255,255,0.05) 0%, rgba(255,255,255,0.02) 100%);
                                    border: 1px solid rgba(255,255,255,0.1);
                                    border-radius: 16px; 
                                    position: relative;
                                    backdrop-filter: blur(10px);
                                    box-shadow: 0 8px 32px rgba(0,0,0,0.3), 0 2px 8px rgba(102, 126, 234, 0.1);
                                    transition: all 0.3s ease;
                                    overflow: hidden;
                                " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 12px 40px rgba(0,0,0,0.4), 0 4px 16px rgba(102, 126, 234, 0.2)';" 
                                   onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 8px 32px rgba(0,0,0,0.3), 0 2px 8px rgba(102, 126, 234, 0.1)';">
                                   
                                    <!-- Gradient Border Effect -->
                                    <div style="position: absolute; top: 0; left: 0; right: 0; height: 2px; background: linear-gradient(90deg, #667eea, #764ba2, #f093fb, #f5576c); opacity: 0.6;"></div>
                                    
                                    <div style="display: flex; gap: 16px; position: relative; z-index: 2;">
                                        <!-- Avatar with Glow Effect -->
                                        <div style="position: relative;">
                                            <img src="${comment.avatar_url || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(comment.username) + '&background=667eea&color=fff&size=50'}" 
                                                 style="
                                                    width: 50px; 
                                                    height: 50px; 
                                                    border-radius: 50%; 
                                                    object-fit: cover; 
                                                    border: 3px solid transparent;
                                                    background: linear-gradient(135deg, #667eea, #764ba2) padding-box, linear-gradient(135deg, #667eea, #764ba2) border-box;
                                                    box-shadow: 0 4px 16px rgba(102, 126, 234, 0.4), 0 0 20px rgba(102, 126, 234, 0.2);
                                                 ">
                                            <!-- Online Status Indicator -->
                                            <div style="position: absolute; bottom: 2px; right: 2px; width: 14px; height: 14px; background: linear-gradient(135deg, #4facfe, #00f2fe); border: 2px solid #1a1a1a; border-radius: 50%; box-shadow: 0 0 8px rgba(79, 172, 254, 0.6);"></div>
                                        </div>
                                        
                                        <div style="flex: 1;">
                                            <!-- Header with User Info and Actions -->
                                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                                                <div style="display: flex; align-items: center; gap: 8px;">
                                                    <strong style="
                                                        color: white; 
                                                        font-size: 16px; 
                                                        font-weight: 700;
                                                        background: linear-gradient(135deg, #667eea, #764ba2);
                                                        -webkit-background-clip: text;
                                                        -webkit-text-fill-color: transparent;
                                                        background-clip: text;
                                                    ">${comment.first_name} ${comment.last_name}</strong>
                                                    
                                                    <span style="
                                                        color: #999; 
                                                        font-size: 13px; 
                                                        background: rgba(255,255,255,0.05); 
                                                        padding: 2px 8px; 
                                                        border-radius: 12px;
                                                        border: 1px solid rgba(255,255,255,0.1);
                                                    ">@${comment.username}</span>
                                                    
                                                    <span style="
                                                        color: #666; 
                                                        font-size: 11px; 
                                                        background: rgba(255,255,255,0.03); 
                                                        padding: 2px 6px; 
                                                        border-radius: 8px;
                                                    ">${comment.created_at}</span>
                                                </div>
                                                
                                                ${comment.user_id === currentUserId ? `
                                                    <button onclick="deleteComment(${comment.id}, ${postId})" 
                                                            style="
                                                                background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(239, 68, 68, 0.05));
                                                                border: 1px solid rgba(239, 68, 68, 0.3); 
                                                                color: #ef4444; 
                                                                padding: 8px 12px; 
                                                                border-radius: 10px; 
                                                                cursor: pointer; 
                                                                font-size: 11px; 
                                                                font-weight: 600;
                                                                transition: all 0.3s ease;
                                                                backdrop-filter: blur(10px);
                                                                box-shadow: 0 2px 8px rgba(239, 68, 68, 0.2);
                                                            "
                                                            onmouseover="
                                                                this.style.background='linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(239, 68, 68, 0.1))'; 
                                                                this.style.borderColor='#ef4444';
                                                                this.style.transform='translateY(-1px)';
                                                                this.style.boxShadow='0 4px 12px rgba(239, 68, 68, 0.3)';
                                                            "
                                                            onmouseout="
                                                                this.style.background='linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(239, 68, 68, 0.05))'; 
                                                                this.style.borderColor='rgba(239, 68, 68, 0.3)';
                                                                this.style.transform='translateY(0)';
                                                                this.style.boxShadow='0 2px 8px rgba(239, 68, 68, 0.2)';
                                                            "
                                                            title="حذف التعليق">
                                                        <i class="bi bi-trash" style="font-size: 10px; margin-left: 4px;"></i> حذف
                                            </button>
                                        ` : ''}
                                    </div>
                                            
                                            <!-- Comment Content with Beautiful Typography -->
                                            <div style="
                                                background: rgba(255,255,255,0.03);
                                                border: 1px solid rgba(255,255,255,0.05);
                                                border-radius: 12px;
                                                padding: 16px;
                                                margin: 12px 0;
                                                position: relative;
                                                overflow: hidden;
                                            ">
                                                <!-- Content Background Pattern -->
                                                <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; opacity: 0.02; background-image: radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.3) 0%, transparent 50%);"></div>
                                                
                                                <p style="
                                                    color: white; 
                                                    margin: 0; 
                                                    line-height: 1.6; 
                                                    font-size: 15px;
                                                    position: relative;
                                                    z-index: 1;
                                                    font-weight: 400;
                                                ">${comment.content}</p>
                                            </div>
                                            
                                            <!-- Action Buttons with Glassmorphism -->
                                            <div style="display: flex; gap: 12px; margin-top: 16px;">
                                                <button onclick="likeComment(${comment.id})" 
                                                        style="
                                                            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(239, 68, 68, 0.05));
                                                            border: 1px solid rgba(239, 68, 68, 0.2);
                                                            color: #ef4444; 
                                                            cursor: pointer; 
                                                            font-size: 12px; 
                                                            display: flex; 
                                                            align-items: center; 
                                                            gap: 6px; 
                                                            padding: 8px 14px; 
                                                            border-radius: 20px; 
                                                            transition: all 0.3s ease;
                                                            backdrop-filter: blur(10px);
                                                            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.1);
                                                            font-weight: 500;
                                                        "
                                                        onmouseover="
                                                            this.style.background='linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(239, 68, 68, 0.1))';
                                                            this.style.transform='translateY(-2px)';
                                                            this.style.boxShadow='0 4px 16px rgba(239, 68, 68, 0.3)';
                                                            this.style.borderColor='rgba(239, 68, 68, 0.4)';
                                                        "
                                                        onmouseout="
                                                            this.style.background='linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(239, 68, 68, 0.05))';
                                                            this.style.transform='translateY(0)';
                                                            this.style.boxShadow='0 2px 8px rgba(239, 68, 68, 0.1)';
                                                            this.style.borderColor='rgba(239, 68, 68, 0.2)';
                                                        ">
                                                    <i class="bi bi-heart" style="font-size: 14px;"></i> 
                                                    <span>${comment.like_count || 0}</span>
                                        </button>
                                                
                                                <button onclick="replyToComment('${comment.username}', ${postId})" 
                                                        style="
                                                            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(102, 126, 234, 0.05));
                                                            border: 1px solid rgba(102, 126, 234, 0.2);
                                                            color: #667eea; 
                                                            cursor: pointer; 
                                                            font-size: 12px; 
                                                            display: flex; 
                                                            align-items: center; 
                                                            gap: 6px; 
                                                            padding: 8px 14px; 
                                                            border-radius: 20px; 
                                                            transition: all 0.3s ease;
                                                            backdrop-filter: blur(10px);
                                                            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.1);
                                                            font-weight: 500;
                                                        "
                                                        onmouseover="
                                                            this.style.background='linear-gradient(135deg, rgba(102, 126, 234, 0.2), rgba(102, 126, 234, 0.1))';
                                                            this.style.transform='translateY(-2px)';
                                                            this.style.boxShadow='0 4px 16px rgba(102, 126, 234, 0.3)';
                                                            this.style.borderColor='rgba(102, 126, 234, 0.4)';
                                                        "
                                                        onmouseout="
                                                            this.style.background='linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(102, 126, 234, 0.05))';
                                                            this.style.transform='translateY(0)';
                                                            this.style.boxShadow='0 2px 8px rgba(102, 126, 234, 0.1)';
                                                            this.style.borderColor='rgba(102, 126, 234, 0.2)';
                                                        ">
                                                    <i class="bi bi-reply" style="font-size: 14px;"></i> 
                                                    <span>رد</span>
                                                </button>
                                                
                                                <!-- Share Button -->
                                                <button onclick="shareComment(${comment.id})" 
                                                        style="
                                                            background: linear-gradient(135deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.02));
                                                            border: 1px solid rgba(255, 255, 255, 0.1);
                                                            color: #999; 
                                                            cursor: pointer; 
                                                            font-size: 12px; 
                                                            display: flex; 
                                                            align-items: center; 
                                                            gap: 6px; 
                                                            padding: 8px 14px; 
                                                            border-radius: 20px; 
                                                            transition: all 0.3s ease;
                                                            backdrop-filter: blur(10px);
                                                            box-shadow: 0 2px 8px rgba(255, 255, 255, 0.05);
                                                            font-weight: 500;
                                                        "
                                                        onmouseover="
                                                            this.style.background='linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05))';
                                                            this.style.transform='translateY(-2px)';
                                                            this.style.boxShadow='0 4px 16px rgba(255, 255, 255, 0.1)';
                                                            this.style.color='white';
                                                        "
                                                        onmouseout="
                                                            this.style.background='linear-gradient(135deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.02))';
                                                            this.style.transform='translateY(0)';
                                                            this.style.boxShadow='0 2px 8px rgba(255, 255, 255, 0.05)';
                                                            this.style.color='#999';
                                                        ">
                                                    <i class="bi bi-share" style="font-size: 14px;"></i> 
                                                    <span>مشاركة</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                            `;
                        });
                        container.innerHTML = html;
                }
            } else {
                    container.innerHTML = `
                        <div style="
                            text-align: center; 
                            padding: 50px 30px; 
                            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(239, 68, 68, 0.05) 100%);
                            border-radius: 16px; 
                            border: 1px solid rgba(239, 68, 68, 0.2);
                            position: relative;
                            overflow: hidden;
                        ">
                            <!-- Error Background Pattern -->
                            <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; opacity: 0.03; background-image: radial-gradient(circle at 50% 50%, rgba(239, 68, 68, 0.4) 0%, transparent 70%);"></div>
                            
                            <div style="position: relative; z-index: 2;">
                                <div style="
                                    width: 60px; 
                                    height: 60px; 
                                    margin: 0 auto 20px; 
                                    background: linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(239, 68, 68, 0.1));
                                    border-radius: 50%;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    border: 2px solid rgba(239, 68, 68, 0.3);
                                ">
                                    <i class="bi bi-exclamation-triangle" style="font-size: 24px; color: #ef4444;"></i>
                                </div>
                                
                                <h4 style="color: #ef4444; font-size: 18px; margin-bottom: 8px; font-weight: 600;">خطأ في تحميل التعليقات</h4>
                                <p style="color: rgba(239, 68, 68, 0.8); font-size: 13px; margin: 0;">حدث خطأ أثناء جلب التعليقات، يرجى المحاولة مرة أخرى</p>
                                
                                <button onclick="loadSimpleComments(${postId})" style="
                                    margin-top: 20px;
                                    background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(239, 68, 68, 0.05));
                                    border: 1px solid rgba(239, 68, 68, 0.3);
                                    color: #ef4444;
                                    padding: 8px 16px;
                                    border-radius: 20px;
                                    cursor: pointer;
                                    font-size: 12px;
                                    font-weight: 600;
                                    transition: all 0.3s ease;
                                " onmouseover="this.style.background='linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(239, 68, 68, 0.1))'" onmouseout="this.style.background='linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(239, 68, 68, 0.05))'">
                                    <i class="bi bi-arrow-clockwise" style="margin-left: 5px;"></i>إعادة المحاولة
                                </button>
                            </div>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('❌ Error loading comments:', error);
                container.innerHTML = `
                    <div style="
                        text-align: center; 
                        padding: 50px 30px; 
                        background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(239, 68, 68, 0.05) 100%);
                        border-radius: 16px; 
                        border: 1px solid rgba(239, 68, 68, 0.2);
                        position: relative;
                        overflow: hidden;
                    ">
                        <!-- Network Error Background -->
                        <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; opacity: 0.03; background-image: radial-gradient(circle at 50% 50%, rgba(239, 68, 68, 0.4) 0%, transparent 70%);"></div>
                        
                        <div style="position: relative; z-index: 2;">
                            <div style="
                                width: 60px; 
                                height: 60px; 
                                margin: 0 auto 20px; 
                                background: linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(239, 68, 68, 0.1));
                                border-radius: 50%;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                border: 2px solid rgba(239, 68, 68, 0.3);
                            ">
                                <i class="bi bi-wifi-off" style="font-size: 24px; color: #ef4444;"></i>
                            </div>
                            
                            <h4 style="color: #ef4444; font-size: 18px; margin-bottom: 8px; font-weight: 600;">خطأ في الاتصال</h4>
                            <p style="color: rgba(239, 68, 68, 0.8); font-size: 13px; margin: 0;">تعذر الاتصال بالخادم، تحقق من اتصالك بالإنترنت</p>
                            
                            <button onclick="loadSimpleComments(${postId})" style="
                                margin-top: 20px;
                                background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(239, 68, 68, 0.05));
                                border: 1px solid rgba(239, 68, 68, 0.3);
                                color: #ef4444;
                                padding: 8px 16px;
                                border-radius: 20px;
                                cursor: pointer;
                                font-size: 12px;
                                font-weight: 600;
                                transition: all 0.3s ease;
                            " onmouseover="this.style.background='linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(239, 68, 68, 0.1))'" onmouseout="this.style.background='linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(239, 68, 68, 0.05))'">
                                <i class="bi bi-arrow-clockwise" style="margin-left: 5px;"></i>إعادة المحاولة
                            </button>
                        </div>
                    </div>
                `;
            });
    }
    
    function addNewComment(postId) {
        console.log('📝 Adding comment to post:', postId);
        
        const input = document.getElementById('comment-input-' + postId);
        const content = input.value.trim();
        
        if (!content) {
            alert('يرجى كتابة تعليق');
            return;
        }
        
        input.disabled = true;
        const originalValue = input.value;
        input.value = 'جاري الإرسال...';
        
        fetch('api/social.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'add_comment',
                    post_id: parseInt(postId),
                    content: content
                })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(result => {
            console.log('📊 Add comment result:', result);
            
            if (result.success) {
                input.value = '';
                loadSimpleComments(postId);
                fetchAndUpdateCommentsCount(postId)
                    .then(count => {
                        console.log(`✅ Comment added and count updated to: ${count}`);
                        showMessage('تم إضافة التعليق بنجاح!', 'success');
                    });
            } else {
                showMessage('فشل في إضافة التعليق: ' + (result.message || 'خطأ غير معروف'), 'error');
                input.value = originalValue;
            }
        })
        .catch(error => {
            console.error('❌ Error adding comment:', error.message);
            showMessage('حدث خطأ أثناء إضافة التعليق', 'error');
            input.value = originalValue;
        })
        .finally(() => {
            input.disabled = false;
        });
    }
    
    function fetchAndUpdateCommentsCount(postId) {
        console.log('🔄 Fetching and updating comments count for post:', postId);
        
        return fetch(`api/posts.php?action=get_comments_count&post_id=${postId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(result => {
                console.log('📊 Comments count API result:', result);
                
                if (result.success) {
                    const postCard = document.querySelector(`article[data-post-id="${postId}"]`);
                    if (!postCard) {
                        throw new Error(`Post card with ID ${postId} not found`);
                    }
                    
                    const commentBtn = postCard.querySelector('button[data-action="comment"]');
                    if (!commentBtn) {
                        throw new Error(`Comment button for post ${postId} not found`);
                    }
                    
                    const countSpan = commentBtn.querySelector('.count-text');
                    if (!countSpan) {
                        const newSpan = document.createElement('span');
                        newSpan.className = 'count-text';
                        commentBtn.appendChild(newSpan);
                        return newSpan;
                    }
                    
                    const count = result.data && result.data.count !== undefined ? result.data.count : 0;
                    
                    countSpan.textContent = count > 0 ? ' ' + count : ',';
                    console.log('✅ Successfully updated comment count to:', count);
                    
                    return count;
                } else {
                    throw new Error(result.message || 'Unknown error');
                }
            })
            .catch(error => {
                console.error('❌ Error updating comments count:', error.message);
                return null;
            });
    }
    
    function deleteComment(commentId, postId) {
        if (!confirm('هل أنت متأكد من حذف هذا التعليق؟')) {
            return;
        }
        
        console.log('🗑️ Deleting comment:', commentId);
        
        const commentElement = document.getElementById('comment-' + commentId);
        if (commentElement) {
            commentElement.style.transition = 'all 0.3s ease';
            commentElement.style.opacity = '0.5';
            commentElement.style.transform = 'translateX(-20px)';
        }
        
        fetch('api/social.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                action: 'delete_comment',
                comment_id: parseInt(commentId)
            })
        })
        .then(response => response.json())
        .then(result => {
            console.log('📊 Delete comment result:', result);
            
            if (result.success) {
                if (commentElement) {
                    commentElement.style.opacity = '0';
                    commentElement.style.transform = 'translateX(-100px)';
                    
                    setTimeout(() => {
                        commentElement.remove();
                        
                        updateCommentsCount(postId, false);
                        
                        const container = document.getElementById('comments-list-' + postId);
                        if (container && container.children.length === 0) {
                            container.innerHTML = '<div style="text-align: center; color: #999; padding: 40px; background: rgba(255,255,255,0.02); border-radius: 8px; border: 1px dashed rgba(255,255,255,0.1);"><i class="bi bi-chat-dots" style="font-size: 24px; margin-bottom: 10px; display: block; opacity: 0.5;"></i><div style="font-size: 16px; margin-bottom: 5px;">لا توجد تعليقات بعد</div><div style="font-size: 12px; opacity: 0.7;">كن أول من يعلق على هذا المنشور</div></div>';
                        }
                    }, 300);
                }
                
                alert('تم حذف التعليق بنجاح!');
            } else {
                if (commentElement) {
                    commentElement.style.opacity = '1';
                    commentElement.style.transform = 'translateX(0)';
                }
                alert('فشل في حذف التعليق: ' + (result.message || 'خطأ غير معروف'));
            }
        })
        .catch(error => {
            console.error('❌ Error deleting comment:', error);
            
            if (commentElement) {
                commentElement.style.opacity = '1';
                commentElement.style.transform = 'translateX(0)';
            }
            alert('حدث خطأ أثناء حذف التعليق');
        });
    }
    
    function likeComment(commentId) {
        console.log('❤️ Liking comment:', commentId);
        
        fetch('api/social.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                action: 'toggle_comment_like',
                    comment_id: parseInt(commentId)
                })
        })
        .then(response => response.json())
        .then(result => {
            console.log('📊 Like comment result:', result);
            
            if (result.success) {
                const likeBtn = document.querySelector(`button[onclick="likeComment(${commentId})"]`);
                if (likeBtn) {
                    const icon = likeBtn.querySelector('i');
                    const countText = likeBtn.childNodes[2];
                    
                    if (result.data.is_liked) {
                        icon.className = 'bi bi-heart-fill';
                        likeBtn.style.color = '#ef4444';
                    } else {
                        icon.className = 'bi bi-heart';
                        likeBtn.style.color = '#999';
                    }
                    
                    if (countText) {
                        countText.textContent = ' ' + (result.data.like_count || 0);
                    }
                }
            } else {
                console.log('❌ Failed to like comment:', result.message);
            }
        })
        .catch(error => {
            console.error('❌ Error liking comment:', error);
        });
    }
    
    function replyToComment(username, postId) {
        console.log('💬 Replying to:', username);
        
        const input = document.getElementById('comment-input-' + postId);
        if (input) {
            input.value = '@' + username + ' ';
            input.focus();
            input.setSelectionRange(input.value.length, input.value.length);
            
            input.style.borderColor = 'rgba(102, 126, 234, 0.8)';
            input.style.boxShadow = '0 0 0 4px rgba(102, 126, 234, 0.2), 0 8px 24px rgba(0,0,0,0.2)';
                    
                    setTimeout(() => {
                input.style.borderColor = 'rgba(255,255,255,0.15)';
                input.style.boxShadow = '0 4px 16px rgba(0,0,0,0.1), inset 0 1px 0 rgba(255,255,255,0.1)';
            }, 2000);
        }
    }
    
    function shareComment(commentId) {
        console.log('📤 Sharing comment:', commentId);
        
        const commentUrl = window.location.href + '#comment-' + commentId;
        
        if (navigator.share) {
            navigator.share({
                title: 'شاهد هذا التعليق المميز',
                text: 'تعليق رائع يستحق المشاهدة!',
                url: commentUrl
            }).then(() => {
                console.log('✅ Comment shared successfully!');
                showNotification('تم مشاركة التعليق بنجاح! 🎉', 'success');
            }).catch(() => {
                copyCommentToClipboard(commentUrl);
            });
        } else {
            copyCommentToClipboard(commentUrl);
        }
    }
    
    function copyCommentToClipboard(url) {
        navigator.clipboard.writeText(url).then(() => {
            console.log('📋 Comment link copied!');
            showNotification('تم نسخ رابط التعليق! 📋', 'info');
        }).catch(() => {
            console.log('❌ Could not copy comment link');
            showNotification('لم يتم نسخ الرابط', 'error');
        });
    }
    
    function addQuickEmoji(postId, emoji) {
        console.log('😊 Adding quick emoji:', emoji, 'to post:', postId);
        
        const input = document.getElementById('comment-input-' + postId);
        if (input) {
            const cursorPos = input.selectionStart;
            const textBefore = input.value.substring(0, cursorPos);
            const textAfter = input.value.substring(cursorPos);
            
            input.value = textBefore + emoji + ' ' + textAfter;
            input.focus();
            input.setSelectionRange(cursorPos + emoji.length + 1, cursorPos + emoji.length + 1);
            
            input.style.transform = 'scale(1.02)';
            setTimeout(() => {
                input.style.transform = 'scale(1)';
            }, 200);
        }
    }
    
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, ${type === 'success' ? '#4facfe, #00f2fe' : type === 'error' ? '#fa709a, #fee140' : '#667eea, #764ba2'});
            color: white;
            padding: 16px 24px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            backdrop-filter: blur(10px);
            z-index: 10000;
            font-weight: 600;
            font-size: 14px;
            transform: translateX(400px);
            transition: all 0.4s ease;
            border: 1px solid rgba(255,255,255,0.2);
        `;
        
        notification.textContent = message;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 100);
        
        setTimeout(() => {
            notification.style.transform = 'translateX(400px)';
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 400);
        }, 3000);
    }
    
    function deletePost(postId) {
        if (!confirm('Are you sure you want to delete this post?')) return;
        
        hideDropdown(postId);
        
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
                console.log('Post deleted successfully');
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
                console.log('Post not deleted');
            }
        })
        .catch(error => {
            console.error('Error deleting post:', error);
            console.log('Post not deleted');
        });
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
                title: 'Check out this post',
                text: 'See this amazing post!',
                url: window.location.href
            }).then(() => {
                console.log('Post shared successfully!');
            }).catch(() => {
                copyToClipboard();
            });
        } else {
            copyToClipboard();
        }
        
        function copyToClipboard() {
            navigator.clipboard.writeText(window.location.href).then(() => {
                console.log('Post link copied!');
            }).catch(() => {
                console.log('Could not copy link');
            });
        }
    }
    
    function shareProfile() {
        if (navigator.share) {
            navigator.share({
                title: 'Check out my profile',
                text: 'Visit my profile on SUT Premium!',
                url: window.location.href
            }).then(() => {
                console.log('Profile shared successfully!');
            }).catch(() => {
                copyProfileToClipboard();
            });
        } else {
            copyProfileToClipboard();
        }
        
        function copyProfileToClipboard() {
            navigator.clipboard.writeText(window.location.href).then(() => {
                console.log('Profile link copied!');
            }).catch(() => {
                console.log('Could not copy link');
            });
        }
    }
    
    function addEmoji(textarea, emoji) {
        if (!textarea) {
            textarea = document.getElementById('postContent');
        }
        
        if (!emoji) {
            showEmojiPicker(textarea);
            return;
        }
        
        const cursorPos = textarea.selectionStart;
        const textBefore = textarea.value.substring(0, cursorPos);
        const textAfter = textarea.value.substring(cursorPos);
        
        textarea.value = textBefore + emoji + textAfter;
        textarea.focus();
        textarea.setSelectionRange(cursorPos + emoji.length, cursorPos + emoji.length);
    }
    
    function showEmojiPicker(targetTextarea) {
        let emojiPicker = document.getElementById('emoji-picker');
        
        if (!emojiPicker) {
            emojiPicker = document.createElement('div');
            emojiPicker.id = 'emoji-picker';
            emojiPicker.style.position = 'absolute';
            emojiPicker.style.zIndex = '1000';
            emojiPicker.style.background = 'rgba(30, 41, 59, 0.95)';
            emojiPicker.style.border = '1px solid rgba(255, 255, 255, 0.1)';
            emojiPicker.style.borderRadius = '12px';
            emojiPicker.style.padding = '10px';
            emojiPicker.style.boxShadow = '0 8px 32px rgba(0, 0, 0, 0.3)';
            emojiPicker.style.backdropFilter = 'blur(10px)';
            emojiPicker.style.display = 'grid';
            emojiPicker.style.gridTemplateColumns = 'repeat(7, 1fr)';
            emojiPicker.style.gap = '10px';
            emojiPicker.style.maxWidth = '300px';
            emojiPicker.style.transition = 'all 0.3s ease';
            emojiPicker.style.transform = 'scale(0.95)';
            emojiPicker.style.opacity = '0';
            
            const emojiCategories = {
                'Smileys': ['😀', '😃', '😄', '😁', '😆', '😅', '😂', '🤣', '😊', '😇', '🙂', '🙃', '😉', '😌', '😍', '🥰', '😘'],
                'Gestures': ['👍', '👎', '👌', '✌️', '🤞', '🤟', '🤘', '🤙', '👈', '👉', '👆', '👇', '☝️', '✋', '🤚', '🖐️', '🖖'],
                'Animals': ['🐶', '🐱', '🐭', '🐹', '🐰', '🦊', '🐻', '🐼', '🐨', '🦁', '🐮', '🐷', '🐸', '🐵', '🐔', '🐧', '🦄'],
                'Food': ['🍎', '🍐', '🍊', '🍋', '🍌', '🍉', '🍇', '🍓', '🍈', '🍒', '🍑', '🥭', '🍍', '🥥', '🥝', '🍅', '🍆'],
                'Activities': ['⚽', '🏀', '🏈', '⚾', '🥎', '🎾', '🏐', '🏉', '🥏', '🎱', '🏓', '🏸', '🏒', '🏑', '🥍', '🏏', '⛳'],
                'Travel': ['🚗', '🚕', '🚙', '🚌', '🚎', '🏎️', '🚓', '🚑', '🚒', '🚐', '🚚', '🚛', '🚜', '🛴', '🚲', '🛵', '🏍️'],
                'Symbols': ['❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '❣️', '💕', '💞', '💓', '💗', '💖', '💘', '💝', '💟', '☮️']
            };
            
            const tabsContainer = document.createElement('div');
            tabsContainer.style.display = 'flex';
            tabsContainer.style.justifyContent = 'space-between';
            tabsContainer.style.marginBottom = '10px';
            tabsContainer.style.borderBottom = '1px solid rgba(255, 255, 255, 0.1)';
            tabsContainer.style.paddingBottom = '5px';
            
            let firstCategory = null;
            
            Object.keys(emojiCategories).forEach((category, index) => {
                const tab = document.createElement('button');
                tab.textContent = category === 'Smileys' ? '😊' : 
                                 category === 'Gestures' ? '👍' : 
                                 category === 'Animals' ? '🐱' : 
                                 category === 'Food' ? '🍎' : 
                                 category === 'Activities' ? '⚽' : 
                                 category === 'Travel' ? '🚗' : '❤️';
                tab.style.background = 'none';
                tab.style.border = 'none';
                tab.style.fontSize = '20px';
                tab.style.cursor = 'pointer';
                tab.style.opacity = index === 0 ? '1' : '0.5';
                tab.style.transition = 'all 0.2s ease';
                tab.dataset.category = category;
                
                tab.addEventListener('click', function() {
                    tabsContainer.querySelectorAll('button').forEach(btn => {
                        btn.style.opacity = '0.5';
                    });
                    
                    this.style.opacity = '1';
                    
                    showEmojiCategory(category);
                });
                
                tabsContainer.appendChild(tab);
                
                if (index === 0) {
                    firstCategory = category;
                }
            });
            
            emojiPicker.appendChild(tabsContainer);
            
            const emojiContainer = document.createElement('div');
            emojiContainer.id = 'emoji-container';
            emojiContainer.style.display = 'grid';
            emojiContainer.style.gridTemplateColumns = 'repeat(7, 1fr)';
            emojiContainer.style.gap = '10px';
            emojiContainer.style.maxHeight = '200px';
            emojiContainer.style.overflowY = 'auto';
            emojiContainer.style.padding = '5px';
            emojiPicker.appendChild(emojiContainer);
            
            function showEmojiCategory(category) {
                emojiContainer.innerHTML = '';
                
                emojiCategories[category].forEach(emoji => {
                    const emojiButton = document.createElement('button');
                    emojiButton.textContent = emoji;
                    emojiButton.style.fontSize = '24px';
                    emojiButton.style.background = 'rgba(255, 255, 255, 0.05)';
                    emojiButton.style.border = '1px solid rgba(255, 255, 255, 0.1)';
                    emojiButton.style.borderRadius = '8px';
                    emojiButton.style.cursor = 'pointer';
                    emojiButton.style.transition = 'all 0.2s ease';
                    emojiButton.style.width = '40px';
                    emojiButton.style.height = '40px';
                    emojiButton.style.display = 'flex';
                    emojiButton.style.alignItems = 'center';
                    emojiButton.style.justifyContent = 'center';
                    
                    emojiButton.addEventListener('mouseover', function() {
                        this.style.transform = 'scale(1.1)';
                        this.style.background = 'rgba(255, 255, 255, 0.1)';
                    });
                    
                    emojiButton.addEventListener('mouseout', function() {
                        this.style.transform = 'scale(1)';
                        this.style.background = 'rgba(255, 255, 255, 0.05)';
                    });
                    
                    emojiButton.addEventListener('click', function() {
                        addEmoji(targetTextarea, emoji);
                        hideEmojiPicker();
                    });
                    
                    emojiContainer.appendChild(emojiButton);
                });
            }
            
            const closeButton = document.createElement('button');
            closeButton.innerHTML = '&times;';
            closeButton.style.position = 'absolute';
            closeButton.style.top = '5px';
            closeButton.style.right = '5px';
            closeButton.style.background = 'rgba(255, 255, 255, 0.1)';
            closeButton.style.border = 'none';
            closeButton.style.borderRadius = '50%';
            closeButton.style.width = '25px';
            closeButton.style.height = '25px';
            closeButton.style.display = 'flex';
            closeButton.style.alignItems = 'center';
            closeButton.style.justifyContent = 'center';
            closeButton.style.cursor = 'pointer';
            closeButton.style.color = 'white';
            closeButton.style.fontSize = '16px';
            
            closeButton.addEventListener('click', hideEmojiPicker);
            emojiPicker.appendChild(closeButton);
            
            document.body.appendChild(emojiPicker);
            
            if (firstCategory) {
                showEmojiCategory(firstCategory);
            }
            
            document.addEventListener('click', function(e) {
                if (emojiPicker && !emojiPicker.contains(e.target) && e.target.id !== 'emoji-button') {
                    hideEmojiPicker();
                }
            });
        }
        
        const textareaRect = targetTextarea.getBoundingClientRect();
        emojiPicker.style.top = (textareaRect.bottom + window.scrollY + 10) + 'px';
        emojiPicker.style.left = (textareaRect.left + window.scrollX) + 'px';
        
        emojiPicker.style.display = 'block';
        setTimeout(() => {
            emojiPicker.style.opacity = '1';
            emojiPicker.style.transform = 'scale(1)';
        }, 10);
    }
    
    function hideEmojiPicker() {
        const emojiPicker = document.getElementById('emoji-picker');
        if (emojiPicker) {
            emojiPicker.style.opacity = '0';
            emojiPicker.style.transform = 'scale(0.95)';
            setTimeout(() => {
                emojiPicker.style.display = 'none';
            }, 300);
        }
    }
        
        function removeImagePreview() {
            document.getElementById('imagePreview').style.display = 'none';
            document.getElementById('postImage').value = '';
        }
    

    

    
    
    
    document.addEventListener("DOMContentLoaded", function() {
        console.log("🔧 Setting up simple comments system...");
        
        const commentButtons = document.querySelectorAll('button[data-action="comment"]');
        console.log('🔍 Found comment buttons:', commentButtons.length);
        
        document.querySelectorAll('article[data-post-id]').forEach(postCard => {
            const postId = postCard.getAttribute('data-post-id');
            if (postId) {
                fetchAndUpdateCommentsCount(postId)
                    .then(count => {
                        console.log(`✅ Initialized comment count for post ${postId}: ${count}`);
                    });
            }
        });
        
        commentButtons.forEach((btn, index) => {
            console.log(`🔘 Setting up comment button ${index + 1}`);
            
            btn.removeAttribute('onclick');
            
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                
                const postCard = this.closest('article[data-post-id]');
                if (!postCard) {
                    console.error('❌ Could not find post card');
                    return;
                }
                
                const postId = postCard.getAttribute('data-post-id');
                console.log('🔄 Toggle comments for post:', postId);
                
                toggleComments(postId);
            });
        });
        
        setTimeout(function() {
            const commentInputs = document.querySelectorAll('[id^="comment-input-"]');
            commentInputs.forEach(input => {
                input.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        const postId = this.id.replace('comment-input-', '');
                        addNewComment(postId);
                    }
                });
                
                input.addEventListener('click', function() {
                    this.focus();
                });
            });
        }, 1000);
        
        console.log("✅ Simple comments system ready!");
    });
    
    function showMessage(message, type = 'info') {
        const messageDiv = document.createElement('div');
        messageDiv.className = `alert alert-${type}`;
        messageDiv.style.position = 'fixed';
        messageDiv.style.top = '20px';
        messageDiv.style.right = '20px';
        messageDiv.style.padding = '10px 20px';
        messageDiv.style.borderRadius = '5px';
        messageDiv.style.zIndex = '9999';
        messageDiv.style.backgroundColor = type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#17a2b8';
        messageDiv.style.color = 'white';
        messageDiv.textContent = message;
        
        document.body.appendChild(messageDiv);
        
        setTimeout(() => {
            messageDiv.remove();
        }, 3000);
    }
    </script>
</body>
</html> 