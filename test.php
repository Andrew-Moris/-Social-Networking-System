<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
    
    $username = isset($_GET['username']) ? $_GET['username'] : $_SESSION['username'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if (!$user) {
        die('المستخدم غير موجود');
    }
    
    $stats = [
        'posts' => 0,
        'followers' => 0,
        'following' => 0
    ];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $stats['posts'] = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE followed_id = ?");
    $stmt->execute([$user['id']]);
    $stats['followers'] = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE follower_id = ?");
    $stmt->execute([$user['id']]);
    $stats['following'] = $stmt->fetchColumn();
    
    $isFollowing = false;
    if ($user['id'] !== $_SESSION['user_id']) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE follower_id = ? AND followed_id = ?");
        $stmt->execute([$_SESSION['user_id'], $user['id']]);
        $isFollowing = $stmt->fetchColumn() > 0;
    }
    
} catch (PDOException $e) {
    die('خطأ في الاتصال بقاعدة البيانات');
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user['username']); ?> | WEP Social</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Cairo', sans-serif; }
        body { background: #0a0f1c; color: #ffffff; }
    </style>
</head>
<body class="min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-[#1a1f2e] rounded-2xl p-8 mb-8">
            <div class="flex items-center gap-6">
                <img src="<?php echo $user['avatar'] ?: 'assets/images/default-avatar.png'; ?>" alt="<?php echo htmlspecialchars($user['username']); ?>" class="w-24 h-24 rounded-full">
                <div>
                    <h1 class="text-2xl font-bold mb-2"><?php echo htmlspecialchars($user['username']); ?></h1>
                    <p class="text-gray-400 mb-4"><?php echo nl2br(htmlspecialchars($user['bio'] ?: 'لا يوجد نبذة شخصية')); ?></p>
                    <div class="flex gap-8 mb-6">
                        <div class="text-center">
                            <div class="text-xl font-bold"><?php echo number_format($stats['posts']); ?></div>
                            <div class="text-gray-400">منشور</div>
                        </div>
                        <div class="text-center">
                            <div class="text-xl font-bold"><?php echo number_format($stats['followers']); ?></div>
                            <div class="text-gray-400">متابع</div>
                        </div>
                        <div class="text-center">
                            <div class="text-xl font-bold"><?php echo number_format($stats['following']); ?></div>
                            <div class="text-gray-400">يتابع</div>
                        </div>
                    </div>
                    <?php if ($user['id'] === $_SESSION['user_id']): ?>
                        <button class="bg-gradient-to-r from-blue-500 to-purple-600 text-white px-6 py-2 rounded-lg font-semibold">
                            <i class="bi bi-pencil"></i>
                            تعديل الملف الشخصي
                        </button>
                    <?php else: ?>
                        <button class="bg-gradient-to-r from-blue-500 to-purple-600 text-white px-6 py-2 rounded-lg font-semibold <?php echo $isFollowing ? 'following' : ''; ?>">
                            <i class="bi <?php echo $isFollowing ? 'bi-person-dash' : 'bi-person-plus'; ?>"></i>
                            <?php echo $isFollowing ? 'إلغاء المتابعة' : 'متابعة'; ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($user['id'] === $_SESSION['user_id']): ?>
        <div class="bg-[#1a1f2e] rounded-2xl p-6 mb-8">
            <form id="postForm" onsubmit="handlePostSubmit(event)">
                <textarea name="content" class="w-full bg-[#0a0f1c] border border-gray-700 rounded-xl p-4 text-white resize-none mb-4" placeholder="شارك أفكارك مع متابعيك..."></textarea>
                <div class="flex justify-between items-center">
                    <div class="flex gap-4">
                        <button type="button" class="bg-[#0a0f1c] border border-gray-700 rounded-lg p-2">
                            <i class="bi bi-image"></i>
                        </button>
                        <button type="button" class="bg-[#0a0f1c] border border-gray-700 rounded-lg p-2">
                            <i class="bi bi-emoji-smile"></i>
                        </button>
                    </div>
                    <button type="submit" class="bg-gradient-to-r from-blue-500 to-purple-600 text-white px-6 py-2 rounded-lg font-semibold">
                        <i class="bi bi-send"></i>
                        نشر
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <div id="posts" class="space-y-6">
        </div>
    </div>

    <script src="assets/js/profile.js"></script>
</body>
</html>
