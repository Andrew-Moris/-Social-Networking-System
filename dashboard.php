<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: frontend/login.html');
    exit;
}

$host = 'localhost';
$port = 5432;
$user = 'postgres';
$password = '20043110';
$dbname = 'socialmedia';

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $conn = new PDO($dsn, $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user_data) {
        session_destroy();
        header('Location: frontend/login.html');
        exit;
    }
    
    $post_count = 0;
    $follower_count = 0;
    $following_count = 0;
    
    $post_stmt = $conn->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
    if ($post_stmt) {
        $post_stmt->execute([$_SESSION['user_id']]);
        $post_count = $post_stmt->fetchColumn();
    }
    
    $follower_stmt = $conn->prepare("SELECT COUNT(*) FROM followers WHERE followed_id = ?");
    if ($follower_stmt) {
        $follower_stmt->execute([$_SESSION['user_id']]);
        $follower_count = $follower_stmt->fetchColumn();
    }
    
    $following_stmt = $conn->prepare("SELECT COUNT(*) FROM followers WHERE follower_id = ?");
    if ($following_stmt) {
        $following_stmt->execute([$_SESSION['user_id']]);
        $following_count = $following_stmt->fetchColumn();
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    exit;
}

$displayName = !empty($user_data['first_name']) ? $user_data['first_name'] . ' ' . $user_data['last_name'] : $user_data['username'];
$username = '@' . $user_data['username'];
$bio = !empty($user_data['bio']) ? $user_data['bio'] : 'مطور واجهات ومحب للتصاميم العصرية. أؤمن بأن التفاصيل الصغيرة تصنع فارقًا كبيرًا في تجربة المستخدم.';
$avatar = !empty($user_data['profile_picture']) ? $user_data['profile_picture'] : 'https://placehold.co/170x170/0A0F1E/E5E7EB?text=أنا';
?><!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>واجهة اجتماعية بوظائف محسنة (تفاعلية)</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap');
        :root {
            --bg-primary: #0A0F1E;
            --bg-glass: rgba(23, 31, 48, 0.7); 
            --bg-glass-header: rgba(17, 24, 39, 0.85);
            --bg-glass-chat-sidebar: rgba(28, 38, 59, 0.75);
            --bg-glass-chat-window: rgba(20, 28, 46, 0.75);
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
        }
        body {
            font-family: var(--font-family-base);
            background-color: var(--bg-primary);
            color: var(--text-primary);
            overflow-x: hidden;
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
        }
        .nav-header {
            background-color: var(--bg-glass-header);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border-bottom: 1px solid var(--border-glass);
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
        }
        .nav-link {
            transition: background-color 0.25s ease, color 0.25s ease, transform 0.15s ease;
            padding: 0.65rem 1.2rem;
            border-radius: var(--border-radius-input);
            font-weight: 600;
            color: var(--text-secondary);
        }
        .nav-link.active {
            background-color: var(--accent-primary);
            color: white;
            box-shadow: 0 4px 14px rgba(59, 130, 246, 0.35);
        }
        .nav-link:not(.active):hover {
            background-color: rgba(55, 65, 81, 0.7);
            color: var(--text-primary);
            transform: translateY(-2px);
        }
        .content-section { display: none; }
        .content-section.active {
            display: block;
            animation: fadeInStagger 0.7s ease-out;
        }
        @keyframes fadeInStagger {
            from { opacity: 0; transform: translateY(25px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .card-base {
            border-radius: var(--border-radius-card);
            padding:clamp(1.25rem, 5vw, 2rem);
            margin-bottom: 2rem;
        }
        .action-button {
            transition: background-color 0.2s ease, color 0.2s ease, transform 0.15s ease;
            padding: 0.7rem;
            border-radius: 50%;
            color: var(--text-secondary);
        }
        .action-button:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--text-primary);
            transform: scale(1.1);
        }
        .like-button.liked, .dislike-button.disliked {
            transform: scale(1.15); 
        }
        .like-button.liked i, .like-button.liked span { color: var(--accent-primary); }
        .dislike-button.disliked i, .dislike-button.disliked span { color: var(--danger-action); }

        .profile-cover {
            height: 350px;
            background-size: cover;
            background-position: center;
            border-radius: var(--border-radius-card) var(--border-radius-card) 0 0;
        }
        .profile-avatar {
            width: 170px; height: 170px;
            border-radius: 50%;
            border: 8px solid var(--bg-primary);
            margin-top: -85px;
            box-shadow: 0 12px 35px rgba(0,0,0,0.5);
        }
        textarea, input[type="text"], input[type="search"] {
            background-color: rgba(17, 24, 39, 0.75);
            border: 1px solid var(--border-glass);
            color: var(--text-primary);
            border-radius: var(--border-radius-input);
            padding: 0.9rem 1.2rem;
            transition: border-color 0.25s ease, background-color 0.25s ease, box-shadow 0.25s ease;
            box-shadow: var(--shadow-glass-inset);
        }
        textarea:focus, input[type="text"]:focus, input[type="search"]:focus {
            background-color: rgba(17, 24, 39, 0.95);
            border-color: var(--accent-primary);
            outline: none;
            box-shadow: 0 0 0 3.5px rgba(59, 130, 246, 0.3), var(--shadow-glass-inset);
        }
        .section-title {
            font-size: clamp(1.75rem, 4vw, 2.25rem);
            font-weight: 800;
            margin-bottom: 2.25rem;
            color: white;
            padding-bottom: 1.1rem;
            border-bottom: 2px solid var(--border-glass);
        }
        .btn {
            font-weight: 700;
            padding: 0.85rem 2rem;
            border-radius: var(--border-radius-input);
            transition: background-color 0.2s ease, transform 0.15s ease, box-shadow 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            cursor: pointer;
        }
        .btn-primary {
            background-color: var(--accent-primary);
            color: white;
            box-shadow: 0 4px 15px -5px rgba(59, 130, 246, 0.45);
        }
        .btn-primary:hover {
            background-color: var(--accent-primary-hover);
            transform: translateY(-2.5px) scale(1.02);
            box-shadow: 0 6px 20px -5px rgba(59, 130, 246, 0.55);
        }
        .btn-secondary {
            background-color: rgba(55, 65, 81, 0.75);
            color: var(--text-primary);
        }
        .btn-secondary:hover {
            background-color: rgba(75, 85, 99, 0.85);
            transform: translateY(-2px);
        }
        .btn-danger {
            background-color: rgba(220, 38, 38, 0.75); 
            color: white;
        }
        .btn-danger:hover {
            background-color: rgba(185, 28, 28, 0.85); 
             transform: translateY(-2px);
        }
        .text-muted-light { color: var(--text-secondary); }
    </style>
    <style>
        .chat-section-wrapper.card-base { padding: 0; }
        .chat-layout {
            display: flex;
            height: calc(100vh - 200px);
            min-height: 550px;
            max-height: 750px;
            border-radius: var(--border-radius-card);
            overflow: hidden;
        }
        .chat-list-panel {
            width: 35%;
            min-width: 300px;
            max-width: 400px;
            background-color: var(--bg-glass-sidebar);
            border-left: 1px solid var(--border-glass);
            display: flex;
            flex-direction: column;
            transition: width 0.3s ease;
        }
        .chat-list-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border-glass);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .chat-list-header h3 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
        }
        .new-chat-btn {
            background: none; border: none; color: var(--accent-primary); font-size: 1.5rem;
            padding: 0.5rem; border-radius: 50%; transition: background-color 0.2s ease;
        }
        .new-chat-btn:hover { background-color: var(--accent-primary-active-bg); }
        .chat-search-bar { padding: 1rem 1.5rem; }
        .chat-search-bar input { width: 100%; font-size: 0.9rem; }
        .chat-list-items {
            overflow-y: auto;
            flex-grow: 1;
            padding: 0.5rem 0;
        }
        .chat-item {
            display: flex;
            align-items: center;
            padding: 1rem 1.5rem;
            cursor: pointer;
            transition: background-color 0.2s ease;
            border-right: 4px solid transparent;
        }
        .chat-item:hover { background-color: rgba(55, 65, 81, 0.3); }
        .chat-item.active {
            background-color: var(--accent-primary-active-bg);
            border-right-color: var(--accent-primary);
        }
        .chat-item-avatar img {
            width: 52px; height: 52px;
            border-radius: 50%;
            margin-left: 1rem;
            object-fit: cover;
        }
        .chat-item-info { flex-grow: 1; overflow: hidden; }
        .chat-item-name {
            font-weight: 600; color: var(--text-primary); font-size: 1.05rem;
            display: block;
        }
        .chat-item-preview {
            font-size: 0.85rem; color: var(--text-secondary);
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
            margin-top: 0.1rem;
        }
        .chat-item-meta {
            margin-right: auto;
            text-align: left;
            font-size: 0.75rem;
            color: var(--text-muted);
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }
        .unread-badge {
            background-color: var(--accent-primary);
            color: white;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 0.1rem 0.4rem;
            border-radius: 0.5rem;
            margin-top: 0.25rem;
        }
        .chat-conversation-panel {
            flex-grow: 1;
            background-color: var(--bg-glass-chat-window);
            display: flex;
            flex-direction: column;
        }
        .chat-conversation-header {
            padding: 1.25rem 1.75rem;
            border-bottom: 1px solid var(--border-glass);
            display: flex;
            align-items: center;
        }
        .chat-conversation-header img {
            width: 48px; height: 48px; border-radius: 50%; margin-left: 1rem;
        }
        .chat-user-details h4 { font-size: 1.15rem; font-weight: 700; color: var(--text-primary); }
        .chat-user-details p { font-size: 0.8rem; color: var(--success-online); }
        .chat-header-actions { margin-right: auto; display: flex; gap: 0.5rem; }
        .chat-header-actions button { color: var(--text-secondary); font-size: 1.25rem; padding: 0.5rem; }
        .chat-header-actions button:hover { color: var(--text-primary); background-color: rgba(255,255,255,0.05); border-radius: 50%;}
        .chat-messages-area {
            flex-grow: 1;
            overflow-y: auto;
            padding: 1.5rem 1.75rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .message-group { display: flex; flex-direction: column; }
        .message-group.sent { align-items: flex-start; }
        .message-group.received { align-items: flex-end; }
        .message-content {
            padding: 0.8rem 1.2rem;
            border-radius: 1.1rem;
            max-width: 80%;
            line-height: 1.6;
            font-size: 0.95rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            position: relative;
        }
        .message-content.sent {
            background-image: linear-gradient(135deg, var(--accent-primary), #1E40AF);
            color: white;
            border-bottom-right-radius: 0.375rem;
            margin-right: 0;
            align-self: flex-start;
        }
        .message-content.received {
            background-color: rgba(55, 65, 81, 0.95);
            color: var(--text-primary);
            border-bottom-left-radius: 0.375rem;
            align-self: flex-end;
        }
        .message-timestamp {
            font-size: 0.7rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
            display: block;
        }
        .message-group.sent .message-timestamp { text-align: right; }
        .message-group.received .message-timestamp { text-align: left; }
        .chat-composer-area {
            padding: 1rem 1.75rem;
            border-top: 1px solid var(--border-glass);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .chat-composer-area textarea {
            flex-grow: 1;
            resize: none;
            padding: 0.8rem 1rem;
            min-height: 48px;
            max-height: 120px;
            overflow-y: auto;
        }
        .chat-composer-actions button {
            color: var(--text-secondary); font-size: 1.35rem;
            padding: 0.6rem;
        }
        .chat-composer-actions button:hover { color: var(--accent-primary); }
        .send-btn {
            background-color: var(--accent-primary);
            color: white;
            padding: 0.75rem;
            border-radius: 50%;
            font-size: 1.25rem;
            box-shadow: 0 2px 8px rgba(59,130,246,0.3);
        }
        .send-btn:hover { background-color: var(--accent-primary-hover); transform: scale(1.05); }

        .toast-notification {
            position: fixed;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            background-color: rgba(17, 24, 39, 0.9);
            backdrop-filter: blur(10px);
            color: var(--text-primary);
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius-input);
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s ease, transform 0.3s ease;
            pointer-events: none;
        }
        .toast-notification.show {
            opacity: 1;
            transform: translateX(-50%) translateY(-1rem);
            pointer-events: auto;
        }
        #edit-profile-form-container {
            display: none; 
        }
    </style>
</head>
<body class="antialiased">
    <div class="min-h-screen flex flex-col">
        <header class="nav-header sticky top-0 z-50">
            <div class="container mx-auto px-4 sm:px-6 py-3.5 flex justify-between items-center">
                <div class="text-2xl font-bold text-white">
                    <span class="text-accent">SUT</span> <span class="text-gray-100">Premium</span>
                </div>
                <nav class="flex space-x-reverse space-x-1.5 sm:space-x-2.5">
                    <a href="#" data-target="profile-content" class="nav-link active">
                        <i class="bi bi-person-workspace ml-1 sm:ml-2 text-lg"></i><span class="hidden md:inline">الملف الشخصي</span>
                    </a>
                    <a href="#" data-target="feed-content" class="nav-link">
                        <i class="bi bi-journals ml-1 sm:ml-2 text-lg"></i><span class="hidden md:inline">المنشورات</span>
                    </a>
                    <a href="#" data-target="following-content" class="nav-link">
                        <i class="bi bi-person-check ml-1 sm:ml-2 text-lg"></i><span class="hidden md:inline">متابَعون</span>
                    </a>
                    <a href="#" data-target="chat-content" class="nav-link">
                        <i class="bi bi-chat-square-heart-fill ml-1 sm:ml-2 text-lg"></i><span class="hidden md:inline">شات</span>
                    </a>
                    <a href="#" data-target="friends-content" class="nav-link">
                        <i class="bi bi-people ml-1 sm:ml-2 text-lg"></i><span class="hidden md:inline">الأصدقاء</span>
                    </a>
                    <a href="logout.php" class="nav-link">
                        <i class="bi bi-box-arrow-right ml-1 sm:ml-2 text-lg"></i><span class="hidden md:inline">تسجيل الخروج</span>
                    </a>
                </nav>
            </div>
        </header>

        <main class="container mx-auto px-3 sm:px-4 py-10 flex-grow">
            <section id="profile-content" class="content-section active">
                <div id="profile-display-container">
                    <div class="profile-card-bg glass-effect rounded-xl shadow-2xl overflow-hidden">
                        <div class="profile-cover" style="background-image: url('https://source.unsplash.com/random/1600x600/?abstract,neon,glow');">
                        </div>
                        <div class="p-6 md:p-10 text-center">
                            <img src="<?php echo htmlspecialchars($avatar); ?>" alt="الصورة الرمزية" class="profile-avatar mx-auto" id="profile-avatar-img">
                            <h1 class="text-3xl lg:text-5xl font-extrabold text-white mt-5" id="profile-display-name"><?php echo htmlspecialchars($displayName); ?></h1>
                            <p class="text-muted-light text-lg mt-1" id="profile-username"><?php echo htmlspecialchars($username); ?></p>
                            <p class="text-slate-200 mt-5 max-w-xl mx-auto text-base sm:text-lg leading-relaxed" id="profile-bio">
                                <?php echo htmlspecialchars($bio); ?>
                            </p>
                            <div class="mt-10 flex flex-wrap justify-center gap-x-8 gap-y-5 sm:gap-x-12">
                                <div class="text-center">
                                    <p class="text-3xl sm:text-4xl font-bold text-white" id="profile-post-count"><?php echo $post_count; ?></p>
                                    <p class="text-muted-light text-sm sm:text-base">منشور</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-3xl sm:text-4xl font-bold text-white"><?php echo $follower_count; ?></p>
                                    <p class="text-muted-light text-sm sm:text-base">متابع</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-3xl sm:text-4xl font-bold text-white"><?php echo $following_count; ?></p>
                                    <p class="text-muted-light text-sm sm:text-base">يتابع</p>
                                </div>
                            </div>
                            <button class="mt-10 btn btn-primary" id="edit-profile-button">
                                <i class="bi bi-pencil-alt"></i> تعديل الملف الشخصي
                            </button>
                        </div>
                    </div>
                </div>
                <div id="edit-profile-form-container" class="card-base glass-effect mt-8" style="display: none;">
                    <h2 class="section-title">تعديل الملف الشخصي</h2>
                    <form id="profile-edit-form">
                        <div class="mb-6">
                            <label for="edit-display-name" class="block text-sm font-medium text-slate-300 mb-2">الاسم المعروض</label>
                            <input type="text" id="edit-display-name" name="displayName" class="w-full" value="<?php echo htmlspecialchars($displayName); ?>">
                        </div>
                        <div class="mb-6">
                            <label for="edit-username" class="block text-sm font-medium text-slate-300 mb-2">اسم المستخدم</label>
                            <input type="text" id="edit-username" name="username" class="w-full" value="<?php echo htmlspecialchars($username); ?>">
                        </div>
                        <div class="mb-6">
                            <label for="edit-bio" class="block text-sm font-medium text-slate-300 mb-2">النبذة التعريفية</label>
                            <textarea id="edit-bio" name="bio" rows="4" class="w-full"><?php echo htmlspecialchars($bio); ?></textarea>
                        </div>
                        <div class="mb-6">
                            <label for="edit-avatar-url" class="block text-sm font-medium text-slate-300 mb-2">رابط الصورة الرمزية (URL)</label>
                            <input type="text" id="edit-avatar-url" name="avatarUrl" class="w-full" value="<?php echo htmlspecialchars($avatar); ?>">
                        </div>
                        <div class="flex gap-4">
                            <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                            <button type="button" id="cancel-edit-profile-button" class="btn btn-secondary">إلغاء</button>
                        </div>
                    </form>
                </div>

                <h2 class="section-title mt-12">منشوراتي</h2>
                <div class="card-base glass-effect" id="my-posts-container">
                    <p class="text-muted-light text-center py-6 text-lg">لا توجد منشورات لعرضها في الملف الشخصي حاليًا.</p>
                </div>
            </section>

            <section id="feed-content" class="content-section">
                <h2 class="section-title">آخر المنشورات</h2>
                <div class="mb-6">
                    <div class="card-base glass-effect mb-6">
                        <form id="new-post-form">
                            <div class="mb-4">
                                <textarea name="post_content" id="new-post-content" rows="3" class="w-full" placeholder="ماذا يدور في ذهنك..."></textarea>
                            </div>
                            <div class="flex justify-between items-center">
                                <div class="post-attachments">
                                    <button type="button" class="btn btn-icon">
                                        <i class="bi bi-image"></i>
                                    </button>
                                    <button type="button" class="btn btn-icon">
                                        <i class="bi bi-paperclip"></i>
                                    </button>
                                    <button type="button" class="btn btn-icon">
                                        <i class="bi bi-emoji-smile"></i>
                                    </button>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-send"></i> نشر
                                </button>
                            </div>
                        </form>
                    </div>
                    <div id="feed-posts-container">
                        <div class="card-base glass-effect mb-6 post-card">
                            <div class="post-header flex items-center mb-4">
                                <img src="https://i.pravatar.cc/300?img=68" alt="صورة المستخدم" class="w-12 h-12 rounded-full ml-3 object-cover">
                                <div>
                                    <h3 class="font-semibold text-white">مريم الخالدي</h3>
                                    <p class="text-xs text-slate-400">منذ 3 ساعات</p>
                                </div>
                            </div>
                            <div class="post-content mb-4">
                                <p class="text-slate-200">سعيدة جداً بانضمامي إلى هذه المنصة! آمل أن أتمكن من مشاركة تجاربي في مجال التصميم الرقمي مع الجميع.</p>
                            </div>
                            <div class="post-image mb-4 rounded-lg overflow-hidden">
                                <img src="https://source.unsplash.com/random/800x600/?digital,art" alt="صورة المنشور" class="w-full h-auto">
                            </div>
                            <div class="post-actions flex border-t border-slate-700 pt-3">
                                <button class="post-action-btn">
                                    <i class="bi bi-heart"></i> <span>32</span>
                                </button>
                                <button class="post-action-btn">
                                    <i class="bi bi-chat-square"></i> <span>8</span>
                                </button>
                                <button class="post-action-btn">
                                    <i class="bi bi-share"></i> <span>2</span>
                                </button>
                            </div>
                        </div>
                        <div class="card-base glass-effect mb-6 post-card">
                            <div class="post-header flex items-center mb-4">
                                <img src="https://i.pravatar.cc/300?img=59" alt="صورة المستخدم" class="w-12 h-12 rounded-full ml-3 object-cover">
                                <div>
                                    <h3 class="font-semibold text-white">أحمد العلي</h3>
                                    <p class="text-xs text-slate-400">منذ 6 ساعات</p>
                                </div>
                            </div>
                            <div class="post-content mb-4">
                                <p class="text-slate-200">أفكار جديدة للمشروع القادم. مشاركة تجربتي في تطوير واجهات المستخدم باستخدام أحدث التقنيات.</p>
                            </div>
                            <div class="post-actions flex border-t border-slate-700 pt-3">
                                <button class="post-action-btn">
                                    <i class="bi bi-heart"></i> <span>21</span>
                                </button>
                                <button class="post-action-btn">
                                    <i class="bi bi-chat-square"></i> <span>5</span>
                                </button>
                                <button class="post-action-btn">
                                    <i class="bi bi-share"></i> <span>1</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="following-content" class="content-section">
                <h2 class="section-title">الأشخاص الذين أتابعهم</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="following-list-container">
                    <div class="card-base glass-effect">
                        <div class="flex items-center">
                            <img src="https://i.pravatar.cc/300?img=60" alt="صورة المستخدم" class="w-16 h-16 rounded-full ml-4 object-cover">
                            <div class="flex-grow">
                                <h3 class="font-semibold text-white text-lg">سارة المحمد</h3>
                                <p class="text-sm text-slate-400">@sarah_m</p>
                            </div>
                            <button class="btn btn-sm btn-outline-accent">
                                <i class="bi bi-person-dash"></i> إلغاء المتابعة
                            </button>
                        </div>
                    </div>
                    <div class="card-base glass-effect">
                        <div class="flex items-center">
                            <img src="https://i.pravatar.cc/300?img=33" alt="صورة المستخدم" class="w-16 h-16 rounded-full ml-4 object-cover">
                            <div class="flex-grow">
                                <h3 class="font-semibold text-white text-lg">عمر الناصر</h3>
                                <p class="text-sm text-slate-400">@omar_design</p>
                            </div>
                            <button class="btn btn-sm btn-outline-accent">
                                <i class="bi bi-person-dash"></i> إلغاء المتابعة
                            </button>
                        </div>
                    </div>
                    <div class="card-base glass-effect">
                        <div class="flex items-center">
                            <img src="https://i.pravatar.cc/300?img=32" alt="صورة المستخدم" class="w-16 h-16 rounded-full ml-4 object-cover">
                            <div class="flex-grow">
                                <h3 class="font-semibold text-white text-lg">لينا القاسم</h3>
                                <p class="text-sm text-slate-400">@lina_creative</p>
                            </div>
                            <button class="btn btn-sm btn-outline-accent">
                                <i class="bi bi-person-dash"></i> إلغاء المتابعة
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <section id="chat-content" class="content-section">
                <div class="chat-section-wrapper card-base">
                    <div class="chat-layout">
                        <div class="chat-list-panel">
                            <div class="chat-list-header">
                                <h3>المحادثات</h3>
                                <button class="new-chat-btn"><i class="bi bi-plus"></i></button>
                            </div>
                            <div class="chat-search-bar">
                                <input type="text" placeholder="بحث عن محادثة..." class="w-full">
                            </div>
                            <div class="chat-list-items">
                                <div class="chat-item active">
                                    <div class="chat-item-avatar">
                                        <img src="https://i.pravatar.cc/300?img=32" alt="صورة المستخدم">
                                    </div>
                                    <div class="chat-item-info">
                                        <span class="chat-item-name">لينا القاسم</span>
                                        <span class="chat-item-preview">هل يمكننا مناقشة المشروع غداً؟</span>
                                    </div>
                                    <div class="chat-item-meta">
                                        <span>12:45</span>
                                        <span class="unread-badge">2</span>
                                    </div>
                                </div>
                                <div class="chat-item">
                                    <div class="chat-item-avatar">
                                        <img src="https://i.pravatar.cc/300?img=59" alt="صورة المستخدم">
                                    </div>
                                    <div class="chat-item-info">
                                        <span class="chat-item-name">أحمد العلي</span>
                                        <span class="chat-item-preview">شكراً على المساعدة اليوم!</span>
                                    </div>
                                    <div class="chat-item-meta">
                                        <span>الأمس</span>
                                    </div>
                                </div>
                                <div class="chat-item">
                                    <div class="chat-item-avatar">
                                        <img src="https://i.pravatar.cc/300?img=60" alt="صورة المستخدم">
                                    </div>
                                    <div class="chat-item-info">
                                        <span class="chat-item-name">سارة المحمد</span>
                                        <span class="chat-item-preview">رأيت منشورك الجديد. رائع!</span>
                                    </div>
                                    <div class="chat-item-meta">
                                        <span>19/05</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="chat-conversation-panel">
                            <div class="chat-conversation-header">
                                <img src="https://i.pravatar.cc/300?img=32" alt="صورة المستخدم">
                                <div class="chat-user-details">
                                    <h4>لينا القاسم</h4>
                                    <p>متصل الآن</p>
                                </div>
                                <div class="chat-header-actions">
                                    <button><i class="bi bi-telephone"></i></button>
                                    <button><i class="bi bi-camera-video"></i></button>
                                    <button><i class="bi bi-info-circle"></i></button>
                                </div>
                            </div>
                            <div class="chat-messages-area">
                                <div class="message-group received">
                                    <div class="message-content received">
                                        مرحباً! كيف حالك اليوم؟
                                    </div>
                                    <span class="message-timestamp">12:30</span>
                                </div>
                                <div class="message-group sent">
                                    <div class="message-content sent">
                                        أنا بخير، شكراً للسؤال! ماذا عنك؟
                                    </div>
                                    <span class="message-timestamp">12:32</span>
                                </div>
                                <div class="message-group received">
                                    <div class="message-content received">
                                        أنا ممتاز، أعمل على مشروع جديد للتصميم
                                    </div>
                                    <span class="message-timestamp">12:33</span>
                                </div>
                                <div class="message-group received">
                                    <div class="message-content received">
                                        هل يمكننا مناقشة المشروع غداً؟
                                    </div>
                                    <span class="message-timestamp">12:45</span>
                                </div>
                            </div>
                            <div class="chat-composer-area">
                                <div class="chat-composer-actions">
                                    <button><i class="bi bi-emoji-smile"></i></button>
                                    <button><i class="bi bi-paperclip"></i></button>
                                </div>
                                <textarea placeholder="اكتب رسالتك..."></textarea>
                                <button class="send-btn"><i class="bi bi-send"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="friends-content" class="content-section">
                <h2 class="section-title">الأصدقاء</h2>
                <div class="card-base glass-effect mb-6">
                    <div class="mb-4">
                        <h3 class="text-xl font-semibold mb-3">البحث عن أصدقاء</h3>
                        <div class="flex gap-4">
                            <input type="text" placeholder="ابحث عن مستخدمين..." class="flex-grow">
                            <button class="btn btn-primary">بحث</button>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="friends-list-container">
                    <div class="card-base glass-effect">
                        <div class="flex items-center">
                            <img src="https://i.pravatar.cc/300?img=60" alt="صورة المستخدم" class="w-16 h-16 rounded-full ml-4 object-cover">
                            <div class="flex-grow">
                                <h3 class="font-semibold text-white text-lg">سارة المحمد</h3>
                                <p class="text-sm text-slate-400">@sarah_m</p>
                            </div>
                            <button class="btn btn-sm btn-primary">
                                <i class="bi bi-chat-dots"></i> محادثة
                            </button>
                        </div>
                    </div>
                    <div class="card-base glass-effect">
                        <div class="flex items-center">
                            <img src="https://i.pravatar.cc/300?img=33" alt="صورة المستخدم" class="w-16 h-16 rounded-full ml-4 object-cover">
                            <div class="flex-grow">
                                <h3 class="font-semibold text-white text-lg">عمر الناصر</h3>
                                <p class="text-sm text-slate-400">@omar_design</p>
                            </div>
                            <button class="btn btn-sm btn-primary">
                                <i class="bi bi-chat-dots"></i> محادثة
                            </button>
                        </div>
                    </div>
                    <div class="card-base glass-effect">
                        <div class="flex items-center">
                            <img src="https://i.pravatar.cc/300?img=32" alt="صورة المستخدم" class="w-16 h-16 rounded-full ml-4 object-cover">
                            <div class="flex-grow">
                                <h3 class="font-semibold text-white text-lg">لينا القاسم</h3>
                                <p class="text-sm text-slate-400">@lina_creative</p>
                            </div>
                            <button class="btn btn-sm btn-primary">
                                <i class="bi bi-chat-dots"></i> محادثة
                            </button>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <footer class="py-6 border-t border-slate-800">
            <div class="container mx-auto px-4 text-center text-sm text-slate-500">
                <p>© 2023 SUT Premium Social Network. جميع الحقوق محفوظة.</p>
            </div>
        </footer>
    </div>

    <div class="toast-notification" id="toast-notification"></div>

    <script>
        const userData = {
            id: <?php echo $_SESSION['user_id']; ?>,
            username: "<?php echo htmlspecialchars($username); ?>",
            displayName: "<?php echo htmlspecialchars($displayName); ?>",
            avatar: "<?php echo htmlspecialchars($avatar); ?>",
            bio: "<?php echo htmlspecialchars(str_replace('\n', ' ', $bio)); ?>"
        };

        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const target = this.getAttribute('data-target');
                
                document.querySelectorAll('.nav-link').forEach(el => el.classList.remove('active'));
                document.querySelectorAll('.content-section').forEach(el => el.classList.remove('active'));
                
                this.classList.add('active');
                document.getElementById(target).classList.add('active');
            });
        });

        const profileDisplayContainer = document.getElementById('profile-display-container');
        const editProfileFormContainer = document.getElementById('edit-profile-form-container');
        const editProfileButton = document.getElementById('edit-profile-button');
        const cancelEditProfileButton = document.getElementById('cancel-edit-profile-button');
        const profileEditForm = document.getElementById('profile-edit-form');

        editProfileButton.addEventListener('click', function() {
            profileDisplayContainer.style.display = 'none';
            editProfileFormContainer.style.display = 'block';
        });

        cancelEditProfileButton.addEventListener('click', function() {
            profileDisplayContainer.style.display = 'block';
            editProfileFormContainer.style.display = 'none';
        });

        profileEditForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const newDisplayName = formData.get('displayName');
            const newUsername = formData.get('username');
            const newBio = formData.get('bio');
            const newAvatarUrl = formData.get('avatarUrl');
            
            userData.displayName = newDisplayName;
            userData.username = newUsername;
            userData.bio = newBio;
            userData.avatar = newAvatarUrl;
            
            document.getElementById('profile-display-name').textContent = newDisplayName;
            document.getElementById('profile-username').textContent = newUsername;
            document.getElementById('profile-bio').textContent = newBio;
            document.getElementById('profile-avatar-img').src = newAvatarUrl;
            
            
            showToast('تم تحديث الملف الشخصي بنجاح!');
            
            profileDisplayContainer.style.display = 'block';
            editProfileFormContainer.style.display = 'none';
        });

        function showToast(message) {
            const toast = document.getElementById('toast-notification');
            toast.textContent = message;
            toast.classList.add('show');
            
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }

        document.addEventListener('DOMContentLoaded', function() {
            console.log('Dashboard cargado para el usuario:', userData.displayName);
        });
    </script>
</body>
</html>
