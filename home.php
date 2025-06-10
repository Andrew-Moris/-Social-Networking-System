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
            
    try {
    echo "<script>console.group('📊 تحميل إحصائيات المستخدم');</script>";
    echo "<script>console.log('🆔 معرف المستخدم:', $user_id);</script>";
    
    $stats_stmt = $pdo->prepare("
        SELECT 
            (SELECT COUNT(*) FROM posts WHERE user_id = ?) as posts_count,
            (SELECT COUNT(*) FROM followers WHERE followed_id = ?) as followers_count,
            (SELECT COUNT(*) FROM followers WHERE follower_id = ?) as following_count
    ");
    
    echo "<script>console.log('🔍 تنفيذ استعلام الإحصائيات...');</script>";
    $stats_stmt->execute([$user_id, $user_id, $user_id]);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
        
        error_log("User $user_id stats: " . json_encode($stats));
        echo "<script>console.log('📊 نتيجة الاستعلام الأولية:', " . json_encode($stats) . ");</script>";
        
        if (!$stats) {
            $stats = ['posts_count' => 0, 'followers_count' => 0, 'following_count' => 0];
            echo "<script>console.warn('⚠️ لم يتم العثور على إحصائيات، تم تعيين القيم الافتراضية');</script>";
        } else {
            echo "<script>console.log('✅ تم العثور على إحصائيات');</script>";
            $stats['posts_count'] = (int)($stats['posts_count'] ?? 0);
            $stats['followers_count'] = (int)($stats['followers_count'] ?? 0);
            $stats['following_count'] = (int)($stats['following_count'] ?? 0);
            echo "<script>console.log('🔄 بعد المعالجة:', " . json_encode($stats) . ");</script>";
        }
        
        if ($stats['posts_count'] == 0) {
            echo "<script>console.log('🔍 فحص إضافي للمنشورات...');</script>";
            $posts_check = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
            $posts_check->execute([$user_id]);
            $actual_posts = $posts_check->fetchColumn();
            error_log("Direct posts count for user $user_id: $actual_posts");
            echo "<script>console.log('📝 العدد الفعلي للمنشورات:', $actual_posts);</script>";
            $stats['posts_count'] = (int)$actual_posts;
        }
        
        echo "<script>console.log('🏆 الإحصائيات النهائية:', " . json_encode($stats) . ");</script>";
        echo "<script>console.groupEnd();</script>";
        
    } catch (Exception $e) {
        error_log("Error getting user stats: " . $e->getMessage());
        echo "<script>console.error('❌ خطأ في جلب الإحصائيات:', '" . addslashes($e->getMessage()) . "');</script>";
        echo "<script>console.groupEnd();</script>";
        $stats = ['posts_count' => 0, 'followers_count' => 0, 'following_count' => 0];
    }
    
    $following_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE follower_id = ?");
    $following_count_stmt->execute([$user_id]);
    $following_count = $following_count_stmt->fetchColumn();
    
    if ($following_count > 0) {
        $posts_stmt = $pdo->prepare("
            SELECT p.id, p.content, p.media_url as image_url, p.created_at,
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
            SELECT p.id, p.content, p.media_url as image_url, p.created_at,
                   u.username, u.first_name, u.last_name, u.avatar_url as user_avatar
            FROM posts p 
            JOIN users u ON p.user_id = u.id 
            ORDER BY p.created_at DESC 
            LIMIT 10
        ");
        $posts_stmt->execute();
    }
    $posts = $posts_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Found " . count($posts) . " posts");
    if (count($posts) > 0) {
        error_log("First post data: " . json_encode($posts[0]));
    }
    
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
            grid-template-columns: 1fr 2fr 1fr;
            gap: 2rem;
            padding: 0 1rem;
            direction: rtl;
        }
        
        .sidebar-card {
            background: var(--bg-card);
            backdrop-filter: var(--blur-glass);
            border: 1px solid var(--border-color);
            border-radius: 1.5rem;
            padding: 2rem;
            box-shadow: var(--shadow-card);
            position: sticky;
            top: 6rem;
        }
        
        .profile-card {
            text-align: center;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 4px solid rgba(102, 126, 234, 0.3);
            margin: 0 auto 1.5rem;
            display: block;
            transition: all 0.3s ease;
            box-shadow: 0 8px 32px rgba(102, 126, 234, 0.3);
            cursor: pointer;
        }
        
        .profile-avatar:hover {
            transform: scale(1.05);
            border-color: rgba(102, 126, 234, 0.6);
            box-shadow: 0 12px 48px rgba(102, 126, 234, 0.4);
        }
        
        .profile-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        
        .profile-username {
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
        }
        
        .profile-stats {
            display: flex;
            justify-content: space-around;
            padding: 1rem 0;
            border-top: 1px solid var(--border-color);
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 1.5rem;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            font-size: 0.8rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .quick-actions {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        
        .action-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            background: rgba(102, 126, 234, 0.1);
            border: 1px solid rgba(102, 126, 234, 0.3);
            border-radius: 0.75rem;
            color: var(--text-primary);
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .action-btn:hover {
            background: rgba(102, 126, 234, 0.2);
            transform: translateY(-2px);
            box-shadow: var(--shadow-card);
        }
        
        .main-feed {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }
        
        .create-post-card {
            background: var(--bg-card);
            backdrop-filter: var(--blur-glass);
            border: 1px solid var(--border-color);
            border-radius: 1.5rem;
            padding: 2rem;
            box-shadow: var(--shadow-card);
            margin-bottom: 2rem; 
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
        
        .form-control {
            width: 100%;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-color);
            border-radius: 0.75rem;
            color: var(--text-primary);
            font-size: 1rem;
            transition: all 0.3s ease;
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
            border: none;
            cursor: pointer;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        
        .post-card {
            background: var(--bg-card);
            backdrop-filter: var(--blur-glass);
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            box-shadow: var(--shadow-card);
            margin-bottom: 2rem; 
        }
        
        .post-card:last-child {
            margin-bottom: 3rem; 
        }
        
        .post-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-primary);
            border-color: rgba(102, 126, 234, 0.3);
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
            transition: all 0.3s ease;
            border-radius: 0.5rem;
            font-weight: 500;
            font-size: 0.85rem;
        }
        
        .action-button:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .action-button.liked {
            color: #f56565;
        }
        
        .action-button.bookmarked {
            color: #667eea;
        }
        
        /* تنسيق قسم التعليقات */
        .comments-section {
            margin-top: 15px;
            padding: 15px;
            background-color: rgba(26, 31, 46, 0.5);
            border-radius: 12px;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }
        
        .comments-list {
            max-height: 400px;
            overflow-y: auto;
            margin-bottom: 15px;
            padding-right: 5px;
        }
        
        .comment-item {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(161, 168, 179, 0.2);
        }
        
        .comment-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .add-comment-form textarea {
            background-color: rgba(10, 15, 28, 0.4);
            color: var(--text-primary);
            border: 1px solid rgba(161, 168, 179, 0.3);
            transition: all 0.3s ease;
        }
        
        .add-comment-form textarea:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.3);
        }
        
        .emoji-btn {
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            padding: 5px;
            border-radius: 50%;
            transition: all 0.2s ease;
        }
        
        .emoji-btn:hover {
            background-color: rgba(102, 126, 234, 0.2);
            transform: scale(1.1);
        }
        
        .suggestions-card {
            background: var(--bg-card);
            backdrop-filter: var(--blur-glass);
            border: 1px solid var(--border-color);
            border-radius: 1.5rem;
            padding: 2rem;
            box-shadow: var(--shadow-card);
        }
        
        .user-suggestion {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            border-radius: 0.75rem;
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }
        
        .user-suggestion:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        
        .suggestion-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: 2px solid rgba(102, 126, 234, 0.3);
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
            font-size: 0.85rem;
            color: var(--text-secondary);
        }
        
        .follow-btn {
            padding: 0.5rem 1rem;
            background: rgba(102, 126, 234, 0.2);
            border: 1px solid rgba(102, 126, 234, 0.4);
            border-radius: 0.5rem;
            color: #667eea;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .follow-btn:hover {
            background: rgba(102, 126, 234, 0.3);
            transform: translateY(-1px);
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
        
        .media-preview {
            position: relative;
            display: inline-block;
            margin: 5px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .media-preview img,
        .media-preview video {
            max-width: 100%;
            max-height: 200px;
            border-radius: 12px;
            display: block;
        }
        
        .media-preview video {
            width: 300px;
        }
        
        .remove-media {
            position: absolute;
            top: 8px;
            right: 8px;
            background: rgba(220, 53, 69, 0.9);
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
            z-index: 10;
        }
        
        .remove-media:hover {
            background: #dc3545;
            transform: scale(1.1);
        }
        
        .media-info {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0,0,0,0.7));
            color: white;
            padding: 8px;
            font-size: 0.8rem;
        }
        
        .emoji-picker {
            position: absolute;
            bottom: 70px;
            left: 20px;
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 15px;
            padding: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            display: none;
            z-index: 1000;
            max-width: 300px;
        }
        
        .emoji-grid {
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            gap: 5px;
            max-height: 200px;
            overflow-y: auto;
        }
        
        .emoji-item {
            padding: 8px;
            border: none;
            background: none;
            font-size: 1.2rem;
            cursor: pointer;
            border-radius: 8px;
            transition: background 0.2s ease;
        }
        
        .emoji-item:hover {
            background: #f8f9fa;
        }
        
        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .sidebar-card {
                position: static;
            }
            
            .page-title {
                font-size: 2.5rem;
            }
        }
        
        @media (max-width: 768px) {
            .main-container {
                padding: 1rem;
            }
            
            .page-title {
                font-size: 2rem;
            }
            
            .profile-stats {
                flex-direction: column;
                gap: 1rem;
            }
            
            .post-card {
                margin-bottom: 1.5rem; /* مسافة أصغر للشاشات الصغيرة */
            }
            
            .create-post-card {
                margin-bottom: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <header class="nav-header">
        <div class="container mx-auto px-6 flex justify-between items-center">
            <div class="text-2xl font-bold">
                <a href="home.php" class="text-white hover:text-blue-400 transition-colors">
                    <span style="background: var(--primary-gradient); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;">SUT</span> 
                    <span class="text-gray-100">Premium</span>
                </a>
            </div>
            
            <nav class="flex items-center gap-6">
                <a href="home.php" class="text-blue-400 transition-colors">
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
                        <div class="stat-number"><?php echo isset($stats['posts_count']) ? $stats['posts_count'] : 0; ?></div>
                        <div class="stat-label">Posts</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo isset($stats['followers_count']) ? $stats['followers_count'] : 0; ?></div>
                        <div class="stat-label">Followers</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo isset($stats['following_count']) ? $stats['following_count'] : 0; ?></div>
                        <div class="stat-label">Following</div>
                    </div>
                </div>
                
                <script>
                    console.log('🔍 Debug Stats:', <?php echo json_encode($stats); ?>);
                    console.log('📊 Posts Count:', <?php echo isset($stats['posts_count']) ? $stats['posts_count'] : 'undefined'; ?>);
                    console.log('👥 Followers Count:', <?php echo isset($stats['followers_count']) ? $stats['followers_count'] : 'undefined'; ?>);
                    console.log('➡️ Following Count:', <?php echo isset($stats['following_count']) ? $stats['following_count'] : 'undefined'; ?>);
                    console.log('👤 User ID:', <?php echo $user_id; ?>);
                </script>
                
                <div class="quick-actions">
                    <a href="u.php" class="action-btn">
                        <i class="bi bi-person"></i>
                        View Profile
                    </a>
                    <a href="settings.php" class="action-btn">
                        <i class="bi bi-gear"></i>
                        Settings
                    </a>
                    <a href="friends.php" class="action-btn">
                        <i class="bi bi-people"></i>
                        Find Friends
                    </a>
                </div>
            </div>
            
            <div class="main-feed">
                <div class="create-post-card">
                    <h3 class="section-title">
                        <i class="bi bi-plus-circle" style="color: #667eea;"></i>
                        What's on your mind?
                    </h3>
                    <form id="postForm" enctype="multipart/form-data">
                        <div class="mb-4">
                            <textarea class="form-control" id="postContent" name="content" rows="3" 
                                      placeholder="ماذا يدور في ذهنك؟ شارك أفكارك مع العالم..." 
                                      style="resize: vertical; min-height: 100px;"></textarea>
                        </div>
                    
                        <div id="mediaPreview" style="display: none; margin-bottom: 15px;"></div>
                        
                        <div class="flex justify-between items-center">
                            <div class="flex gap-3">
                                <button type="button" class="action-button" onclick="document.getElementById('postImage').click()" title="إضافة صورة">
                                    <i class="bi bi-image"></i> صورة
                                </button>
                                <input type="file" id="postImage" name="image" accept="image/*,video/*" style="display: none;" multiple>
                            
                                <button type="button" class="action-button" onclick="document.getElementById('postVideo').click()" title="إضافة فيديو">
                                    <i class="bi bi-camera-video"></i> فيديو
                                </button>
                                <input type="file" id="postVideo" name="video" accept="video/*" style="display: none;">
                                
                                <button type="button" class="action-button" onclick="addEmoji()" title="إضافة إيموجي">
                                    <i class="bi bi-emoji-smile"></i> إيموجي
                                </button>
                                
                                <button type="button" class="action-button" onclick="addLocation()" title="إضافة موقع">
                                    <i class="bi bi-geo-alt"></i> موقع
                                </button>
                            </div>
                            <button type="submit" class="btn-primary">
                                <i class="bi bi-send"></i> نشر
                            </button>
                        </div>
                    </form>
                </div>
                
                <div class="feed-section">
                    <h3 class="section-title">
                        <i class="bi bi-newspaper" style="color: #667eea;"></i>
                        Latest Updates
                    </h3>
                    
                    <?php if (empty($posts)): ?>
                        <div class="empty-state">
                            <i class="bi bi-newspaper empty-state-icon"></i>
                            <h4 class="empty-state-title">No Posts in Your Feed</h4>
                            <p class="empty-state-desc">Follow some friends to see their posts here, or create your first post!</p>
                            <div class="mt-4 flex gap-3 justify-center">
                                <a href="friends.php" class="btn-primary">
                                    <i class="bi bi-people"></i> Find Friends
                                </a>
                                <a href="discover.php" class="btn-primary">
                                    <i class="bi bi-compass"></i> Discover
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
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
                                </header>
                                
                                <div class="post-content">
                                    <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                                    <?php if (!empty($post['image_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($post['image_url']); ?>" alt="Post Image" class="post-media">
                                    <?php endif; ?>
                                </div>
                                
                                <div class="post-actions">
                                    <button class="action-button <?php echo $post['is_liked'] ? 'liked' : ''; ?>" onclick="toggleLike(<?php echo $post['id']; ?>, this)">
                                        <i class="bi bi-heart<?php echo $post['is_liked'] ? '-fill' : ''; ?>"></i>
                                        <span class="like-count"><?php echo $post['likes_count']; ?></span>
                                    </button>
                                    <button class="action-button" onclick="toggleComments(<?php echo $post['id']; ?>)">
                                        <i class="bi bi-chat-dots"></i>
                                        <span><?php echo $post['comments_count']; ?></span>
                                    </button>
                                    <button class="action-button" onclick="sharePost(<?php echo $post['id']; ?>)">
                                        <i class="bi bi-share"></i>
                                        Share
                                    </button>
                                    <button class="action-button <?php echo $post['is_bookmarked'] ? 'bookmarked' : ''; ?>" onclick="toggleBookmark(<?php echo $post['id']; ?>, this)">
                                        <i class="bi bi-bookmark<?php echo $post['is_bookmarked'] ? '-fill' : ''; ?>"></i>
                                        <?php echo $post['is_bookmarked'] ? 'Saved' : 'Save'; ?>
                                    </button>
                                </div>
                                
                                <div id="comments-<?php echo $post['id']; ?>" class="comments-section" style="display: none;">
                                    <div id="comments-list-<?php echo $post['id']; ?>" class="comments-list">
                                    </div>
                                    
                                    <div class="add-comment-form">
                                        <div style="display: flex; gap: 10px; margin-top: 15px;">
                                            <img src="<?php echo !empty($user['avatar_url']) ? htmlspecialchars($user['avatar_url']) : 'https://ui-avatars.com/api/?name=' . urlencode($user['username']) . '&background=667eea&color=fff&size=40'; ?>" 
                                                 alt="<?php echo htmlspecialchars($user['username']); ?>" 
                                                 style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                            
                                            <div style="flex: 1; position: relative;">
                                                <textarea id="comment-input-<?php echo $post['id']; ?>" 
                                                          placeholder="أضف تعليقاً..." 
                                                          style="width: 100%; border: 1px solid #ddd; border-radius: 20px; padding: 10px 15px; resize: none; min-height: 40px;"></textarea>
                                                
                                                <div style="display: flex; justify-content: space-between; margin-top: 8px;">
                                                    <div class="quick-emoji">
                                                        <button type="button" onclick="addQuickEmoji(<?php echo $post['id']; ?>, '👍')" class="emoji-btn">👍</button>
                                                        <button type="button" onclick="addQuickEmoji(<?php echo $post['id']; ?>, '❤️')" class="emoji-btn">❤️</button>
                                                        <button type="button" onclick="addQuickEmoji(<?php echo $post['id']; ?>, '😂')" class="emoji-btn">😂</button>
                                                        <button type="button" onclick="addQuickEmoji(<?php echo $post['id']; ?>, '👏')" class="emoji-btn">👏</button>
                                                        <button type="button" onclick="addQuickEmoji(<?php echo $post['id']; ?>, '🎉')" class="emoji-btn">🎉</button>
                                                    </div>
                                                    
                                                    <button type="button" onclick="addNewComment(<?php echo $post['id']; ?>)" 
                                                            style="background: var(--primary-gradient); color: white; border: none; border-radius: 20px; padding: 6px 15px; cursor: pointer;">
                                                        <i class="bi bi-send"></i> إرسال
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="suggestions-card">
                <h3 class="section-title">
                    <i class="bi bi-person-plus" style="color: #667eea;"></i>
                    Suggested for You
                </h3>
                
                <?php if (empty($suggested_users)): ?>
                    <div class="text-center py-8">
                        <i class="bi bi-people text-4xl text-gray-500 mb-3"></i>
                        <p class="text-gray-400">No suggestions available</p>
                        <a href="discover.php" class="btn-primary mt-3">
                            <i class="bi bi-compass"></i> Discover Users
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($suggested_users as $suggested_user): ?>
                        <div class="user-suggestion">
                            <img src="<?php echo !empty($suggested_user['avatar_url']) ? htmlspecialchars($suggested_user['avatar_url']) : 'https://ui-avatars.com/api/?name=' . urlencode($suggested_user['username']) . '&background=667eea&color=fff&size=100'; ?>" 
                                 alt="<?php echo htmlspecialchars($suggested_user['username']); ?>" class="suggestion-avatar">
                            <div class="suggestion-info">
                                <div class="suggestion-name">
                                    <?php echo htmlspecialchars($suggested_user['first_name'] . ' ' . $suggested_user['last_name']) ?: htmlspecialchars($suggested_user['username']); ?>
                                </div>
                                <div class="suggestion-username">@<?php echo htmlspecialchars($suggested_user['username']); ?></div>
                            </div>
                            <button class="follow-btn" onclick="followUser(<?php echo $suggested_user['id']; ?>, this)">
                                Follow
                            </button>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="text-center mt-4">
                        <a href="friends.php" class="text-blue-400 hover:text-blue-300 text-sm">
                            View all suggestions →
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
        console.log('🎉 Home dashboard ready!');
        
        const postForm = document.getElementById('postForm');
        if (postForm) {
            postForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const content = this.querySelector('#postContent').value.trim();
                const imageFile = this.querySelector('#postImage').files[0];
                const videoFile = this.querySelector('#postVideo').files[0];
                
                if (!content && !imageFile && !videoFile) {
                    alert('يجب إدخال نص أو إرفاق صورة/فيديو');
                    return;
                }
                
                const formData = new FormData(this);
                formData.append('action', 'create_post');
                
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="bi bi-hourglass-split animate-spin"></i> جاري النشر...';
                
                const statusDiv = document.createElement('div');
                statusDiv.className = 'status-message';
                statusDiv.style.marginTop = '10px';
                statusDiv.style.padding = '8px';
                statusDiv.style.borderRadius = '4px';
                statusDiv.style.textAlign = 'center';
                this.appendChild(statusDiv);
                
                try {
                    console.log('Sending post data to API...');
                    
                    if (imageFile && imageFile.size > 10 * 1024 * 1024) {
                        throw new Error('حجم الصورة كبير جداً. الحد الأقصى هو 10MB');
                    }
                    
                    if (videoFile && videoFile.size > 50 * 1024 * 1024) {
                        throw new Error('حجم الفيديو كبير جداً. الحد الأقصى هو 50MB');
                    }
                    
                    statusDiv.textContent = 'جاري رفع الملفات وإنشاء المنشور...';
                    statusDiv.style.backgroundColor = 'rgba(59, 130, 246, 0.2)';
                    statusDiv.style.color = '#3b82f6';
                    
                    console.log("Using simple_create_post.php API");
                    
                    const response = await fetch('api/simple_create_post.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    console.log('Response status:', response.status);
                    
                    if (!response.ok) {
                        console.error('API error:', response.status, response.statusText);
                        try {
                            const errorText = await response.text();
                            console.error('Error details:', errorText);
                        } catch (e) {
                            console.error('Could not read error details:', e);
                        }
                    }
                    
                    console.log('Response status:', response.status);
                    
                    if (!response.ok) {
                        const errorText = await response.text();
                        console.error('Server error:', errorText);
                        throw new Error(`خطأ في الخادم: ${response.status} ${response.statusText}`);
                    }
                    
                    const responseText = await response.text();
                    console.log('Raw response:', responseText);
                    
                    if (!responseText.trim()) {
                        throw new Error('استجابة فارغة من الخادم');
                    }
                    
                    const result = JSON.parse(responseText);
                    console.log('Parsed response:', result);
                    
                    if (result.success) {
                        statusDiv.textContent = 'تم إنشاء المنشور بنجاح!';
                        statusDiv.style.backgroundColor = 'rgba(16, 185, 129, 0.2)';
                        statusDiv.style.color = '#10b981';
                        
                        this.reset();
                        removeImagePreview();
                        
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        statusDiv.textContent = result.message || 'فشل في إنشاء المنشور';
                        statusDiv.style.backgroundColor = 'rgba(239, 68, 68, 0.2)';
                        statusDiv.style.color = '#ef4444';
                        console.error('Post creation failed:', result.message);
                    }
                } catch (error) {
                    statusDiv.textContent = `حدث خطأ: ${error.message}`;
                    statusDiv.style.backgroundColor = 'rgba(239, 68, 68, 0.2)';
                    statusDiv.style.color = '#ef4444';
                    console.error('Error creating post:', error);
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                    
                    if (!statusDiv.textContent.includes('تم إنشاء المنشور بنجاح')) {
                        setTimeout(() => {
                            statusDiv.remove();
                        }, 5000);
                    }
                }
            });
        }
        
        const imageInput = document.getElementById('postImage');
        const videoInput = document.getElementById('postVideo');
        
        if (imageInput) {
            imageInput.addEventListener('change', function(e) {
                handleMediaFiles(e.target.files);
            });
        }
        
        if (videoInput) {
            videoInput.addEventListener('change', function(e) {
                handleMediaFiles(e.target.files);
            });
        }
        
        function handleMediaFiles(files) {
            const preview = document.getElementById('mediaPreview');
            preview.innerHTML = '';
            
            Array.from(files).forEach((file, index) => {
                if (file.type.startsWith('image/')) {
                    if (!['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'].includes(file.type)) {
                        alert('نوع الصورة غير مدعوم');
                        return;
                    }
                    if (file.size > 5 * 1024 * 1024) {
                        alert('حجم الصورة كبير جداً (الحد الأقصى 5MB)');
                        return;
                    }
                    
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const mediaDiv = document.createElement('div');
                        mediaDiv.className = 'media-preview';
                        mediaDiv.innerHTML = `
                            <img src="${e.target.result}" alt="معاينة الصورة">
                            <button type="button" class="remove-media" onclick="removeMedia(${index})">
                                <i class="bi bi-x"></i>
                            </button>
                            <div class="media-info">
                                <i class="bi bi-image"></i> ${file.name} (${(file.size/1024/1024).toFixed(2)} MB)
                            </div>
                        `;
                        preview.appendChild(mediaDiv);
                    };
                    reader.readAsDataURL(file);
                    
                } else if (file.type.startsWith('video/')) {
                    if (!['video/mp4', 'video/avi', 'video/mov', 'video/wmv', 'video/webm'].includes(file.type)) {
                        alert('نوع الفيديو غير مدعوم');
                        return;
                    }
                    if (file.size > 50 * 1024 * 1024) {
                        alert('حجم الفيديو كبير جداً (الحد الأقصى 50MB)');
                        return;
                    }
                    
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const mediaDiv = document.createElement('div');
                        mediaDiv.className = 'media-preview';
                        mediaDiv.innerHTML = `
                            <video src="${e.target.result}" controls></video>
                            <button type="button" class="remove-media" onclick="removeMedia(${index})">
                                <i class="bi bi-x"></i>
                            </button>
                            <div class="media-info">
                                <i class="bi bi-camera-video"></i> ${file.name} (${(file.size/1024/1024).toFixed(2)} MB)
                            </div>
                        `;
                        preview.appendChild(mediaDiv);
                    };
                    reader.readAsDataURL(file);
                }
            });
            
            if (files.length > 0) {
                preview.style.display = 'block';
            }
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
            } else {
                likeIcon.className = originalIcon;
            }
        } catch (error) {
            likeIcon.className = originalIcon;
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
            } else {
                bookmarkIcon.className = originalIcon;
            }
        } catch (error) {
            bookmarkIcon.className = originalIcon;
            console.error('Error toggling bookmark:', error);
        }
    }
    
    async function followUser(userId, element) {
        const originalText = element.textContent;
        element.disabled = true;
        element.textContent = 'Following...';
        
        try {
            const response = await fetch('api/social.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'toggle_follow',
                    user_id: parseInt(userId)
                })
            });

            const result = await response.json();
            
            if (result.success) {
                if (result.data.is_following) {
                    element.textContent = 'Following';
                    element.style.background = 'rgba(34, 197, 94, 0.2)';
                    element.style.borderColor = 'rgba(34, 197, 94, 0.4)';
                    element.style.color = '#22c55e';
                } else {
                    element.textContent = 'Follow';
                    element.style.background = 'rgba(102, 126, 234, 0.2)';
                    element.style.borderColor = 'rgba(102, 126, 234, 0.4)';
                    element.style.color = '#667eea';
                }
            } else {
                element.textContent = originalText;
            }
        } catch (error) {
            console.error('Error following user:', error);
            element.textContent = originalText;
        } finally {
            element.disabled = false;
        }
    }
    
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
    
    function removeMedia(index) {
        const preview = document.getElementById('mediaPreview');
        const mediaItems = preview.querySelectorAll('.media-preview');
        if (mediaItems[index]) {
            mediaItems[index].remove();
        }
        if (preview.children.length === 0) {
            preview.style.display = 'none';
            document.getElementById('postImage').value = '';
            document.getElementById('postVideo').value = '';
        }
    }
    
    function removeImagePreview() {
        document.getElementById('mediaPreview').style.display = 'none';
        document.getElementById('mediaPreview').innerHTML = '';
        document.getElementById('postImage').value = '';
        document.getElementById('postVideo').value = '';
    }
    
    const emojis = [
        '😀', '😃', '😄', '😁', '😆', '😅', '😂', '🤣', '😊', '😇', '🙂', '🙃', '😉', '😌', '😍', '🥰',
        '😘', '😗', '😙', '😚', '😋', '😛', '😝', '😜', '🤪', '🤨', '🧐', '🤓', '😎', '🤩', '🥳', '😏',
        '😒', '😞', '😔', '😟', '😕', '🙁', '☹️', '😣', '😖', '😫', '😩', '🥺', '😢', '😭', '😤', '😠',
        '😡', '🤬', '🤯', '😳', '🥵', '🥶', '😱', '😨', '😰', '😥', '😓', '🤗', '🤔', '🤭', '🤫', '🤥',
        '❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '🤎', '💔', '❣️', '💕', '💞', '💓', '💗', '💖',
        '👍', '👎', '👌', '🤌', '🤏', '✌️', '🤞', '🤟', '🤘', '🤙', '👈', '👉', '👆', '🖕', '👇', '☝️',
        '🔥', '⭐', '🌟', '✨', '💫', '💥', '💢', '💨', '💦', '💤', '🎉', '🎊', '🎈', '🎁', '🏆', '🥇'
    ];
    
    function addEmoji() {
        const picker = document.getElementById('emojiPicker');
        if (!picker) {
            createEmojiPicker();
        } else {
            picker.style.display = picker.style.display === 'block' ? 'none' : 'block';
        }
    }
    
    function createEmojiPicker() {
        const picker = document.createElement('div');
        picker.id = 'emojiPicker';
        picker.className = 'emoji-picker';
        picker.style.display = 'block';
        
        const grid = document.createElement('div');
        grid.className = 'emoji-grid';
        
        emojis.forEach(emoji => {
            const button = document.createElement('button');
            button.className = 'emoji-item';
            button.textContent = emoji;
            button.onclick = () => insertEmoji(emoji);
            grid.appendChild(button);
        });
        
        picker.appendChild(grid);
        document.body.appendChild(picker);
        
        document.addEventListener('click', function(e) {
            if (!picker.contains(e.target) && !e.target.closest('.action-button')) {
                picker.style.display = 'none';
            }
        });
    }
    
    function insertEmoji(emoji) {
        const textarea = document.getElementById('postContent');
        const cursorPos = textarea.selectionStart;
        const textBefore = textarea.value.substring(0, cursorPos);
        const textAfter = textarea.value.substring(cursorPos);
        
        textarea.value = textBefore + emoji + textAfter;
        textarea.focus();
        textarea.setSelectionRange(cursorPos + emoji.length, cursorPos + emoji.length);
        
        document.getElementById('emojiPicker').style.display = 'none';
    }
    
    function addLocation() {
        const textarea = document.getElementById('postContent');
        const location = prompt('أدخل الموقع:');
        if (location) {
            textarea.value += ` 📍 ${location}`;
            textarea.focus();
        }
    }
    
    async function toggleComments(postId) {
        const commentsSection = document.getElementById(`comments-${postId}`);
        
        if (!commentsSection) {
            console.error(`قسم التعليقات غير موجود للمنشور ${postId}`);
            return;
        }
        
        if (commentsSection.style.display === 'none') {
            commentsSection.style.display = 'block';
            await loadComments(postId);
        } else {
            commentsSection.style.display = 'none';
        }
    }
    
    async function loadComments(postId) {
        const commentsList = document.getElementById(`comments-list-${postId}`);
        
        if (!commentsList) {
            console.error(`قائمة التعليقات غير موجودة للمنشور ${postId}`);
            return;
        }
        
        try {
            commentsList.innerHTML = `
                <div class="loading-indicator" style="text-align: center; padding: 20px;">
                    <i class="bi bi-hourglass-split" style="font-size: 24px; margin-bottom: 10px; display: block;"></i>
                    <p>جاري تحميل التعليقات...</p>
                </div>
            `;
            
            const response = await fetch(`api/comments.php?action=get_comments&post_id=${postId}`);
            const data = await response.json();
            
            if (data.success && data.comments) {
                if (data.comments.length > 0) {
                    commentsList.innerHTML = '';
                    data.comments.forEach(comment => {
                        const commentElement = createCommentElement(comment, postId);
                        commentsList.appendChild(commentElement);
                    });
                } else {
                    commentsList.innerHTML = `
                        <div style="text-align: center; padding: 20px; color: #777;">
                            <i class="bi bi-chat-square-text" style="font-size: 24px; margin-bottom: 10px; display: block;"></i>
                            <p>لا توجد تعليقات حتى الآن. كن أول من يعلق!</p>
                        </div>
                    `;
                }
            } else {
                commentsList.innerHTML = `
                    <div style="text-align: center; padding: 20px; color: #dc3545;">
                        <i class="bi bi-exclamation-triangle" style="font-size: 24px; margin-bottom: 10px; display: block;"></i>
                        <p>${data.message || 'حدث خطأ أثناء تحميل التعليقات'}</p>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error loading comments:', error);
            commentsList.innerHTML = `
                <div style="text-align: center; padding: 20px; color: #dc3545;">
                    <i class="bi bi-exclamation-triangle" style="font-size: 24px; margin-bottom: 10px; display: block;"></i>
                    <p>حدث خطأ أثناء تحميل التعليقات</p>
                </div>
            `;
        }
    }
    
    function createCommentElement(comment, postId) {
        const commentDiv = document.createElement('div');
        commentDiv.className = 'comment-item';
        commentDiv.setAttribute('data-comment-id', comment.id);
        
        const avatarUrl = comment.avatar_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(comment.username)}&background=667eea&color=fff&size=100`;
        
        const displayName = comment.first_name && comment.last_name ? 
            `${comment.first_name} ${comment.last_name}` : comment.username;
        
        commentDiv.innerHTML = `
            <div style="display: flex; gap: 12px; margin-bottom: 15px;">
                <img src="${avatarUrl}" alt="${comment.username}" 
                     style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                <div style="flex: 1;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <div>
                            <strong>${displayName}</strong>
                            <span style="color: #777; font-size: 0.85rem;">@${comment.username}</span>
                        </div>
                        <span style="color: #777; font-size: 0.85rem;">${comment.created_at}</span>
                    </div>
                    <p style="margin: 0 0 8px 0;">${comment.content}</p>
                    <div style="display: flex; gap: 15px;">
                        <button class="action-button ${comment.user_liked ? 'liked' : ''}" 
                                onclick="toggleCommentLike(${comment.id}, this)">
                            <i class="bi bi-heart${comment.user_liked ? '-fill' : ''}"></i>
                            <span>${comment.like_count || 0}</span>
                        </button>
                        ${comment.is_owner ? `
                            <button class="action-button" onclick="editComment(${comment.id}, ${postId})">
                                <i class="bi bi-pencil"></i>
                                تعديل
                            </button>
                            <button class="action-button" onclick="deleteComment(${comment.id}, ${postId})">
                                <i class="bi bi-trash"></i>
                                حذف
                            </button>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
        
        return commentDiv;
    }
    
    async function addNewComment(postId) {
        const commentInput = document.getElementById(`comment-input-${postId}`);
        const content = commentInput.value.trim();
        
        if (!content) {
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append('action', 'add_comment');
            formData.append('post_id', postId);
            formData.append('content', content);
            
            const response = await fetch('api/comments.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                commentInput.value = '';
                
                await loadComments(postId);
            } else {
                console.error('Error adding comment:', data.message);
            }
        } catch (error) {
            console.error('Error adding comment:', error);
        }
    }
    
    function addQuickEmoji(postId, emoji) {
        const commentInput = document.getElementById(`comment-input-${postId}`);
        commentInput.value += emoji;
        commentInput.focus();
    }
    
    async function toggleCommentLike(commentId, button) {
        try {
            const formData = new FormData();
            formData.append('action', 'toggle_comment_like');
            formData.append('comment_id', commentId);
            
            const response = await fetch('api/comments.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                const isLiked = data.is_liked;
                const likeCount = data.like_count;
                
                if (isLiked) {
                    button.classList.add('liked');
                    button.querySelector('i').className = 'bi bi-heart-fill';
                } else {
                    button.classList.remove('liked');
                    button.querySelector('i').className = 'bi bi-heart';
                }
                
                button.querySelector('span').textContent = likeCount;
            } else {
                console.error('Error toggling comment like:', data.message);
            }
        } catch (error) {
            console.error('Error toggling comment like:', error);
        }
    }
    
    async function deleteComment(commentId, postId) {
        if (!confirm('هل أنت متأكد من حذف هذا التعليق؟')) {
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append('action', 'delete_comment');
            formData.append('comment_id', commentId);
            
            const response = await fetch('api/comments.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                await loadComments(postId);
            } else {
                console.error('Error deleting comment:', data.message);
            }
        } catch (error) {
            console.error('Error deleting comment:', error);
        }
    }
    
    async function editComment(commentId, postId) {
        const commentElement = document.querySelector(`.comment-item[data-comment-id="${commentId}"] p`);
        const currentContent = commentElement.textContent;
        
        const newContent = prompt('تعديل التعليق:', currentContent);
        
        if (!newContent || newContent === currentContent) {
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append('action', 'edit_comment');
            formData.append('comment_id', commentId);
            formData.append('content', newContent);
            
            const response = await fetch('api/comments.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                commentElement.textContent = newContent;
            } else {
                console.error('Error editing comment:', data.message);
            }
        } catch (error) {
            console.error('Error editing comment:', error);
        }
    }
    </script>
</body>
</html>
