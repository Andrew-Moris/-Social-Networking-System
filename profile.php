<?php

define('BASE_PATH', __DIR__);

session_start();
require_once BASE_PATH . '/config.php';
require_once BASE_PATH . '/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . APP_URL . '/login.php');
    exit;
}

$profile_id = isset($_GET['id']) ? (int)$_GET['id'] : $_SESSION['user_id'];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
    
    $stmt = $pdo->prepare("
        SELECT u.*, 
               (SELECT COUNT(*) FROM followers WHERE following_id = u.id) as followers_count,
               (SELECT COUNT(*) FROM followers WHERE follower_id = u.id) as following_count,
               (SELECT COUNT(*) FROM posts WHERE user_id = u.id) as posts_count,
               EXISTS(SELECT 1 FROM followers WHERE follower_id = ? AND following_id = u.id) as is_following
        FROM users u 
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $profile_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        include BASE_PATH . '/includes/header.php';
        echo '<div class="container mt-5">
                <div class="alert alert-danger">
                    <h4>خطأ 404</h4>
                    <p>لم يتم العثور على المستخدم المطلوب.</p>
                    <a href="' . APP_URL . '/home.php" class="btn btn-primary">العودة للصفحة الرئيسية</a>
                </div>
              </div>';
        include BASE_PATH . '/includes/footer.php';
        exit;
    }
    
    $page_title = $user['username'] . ' - ' . APP_NAME;
    
} catch (PDOException $e) {
    error_log("Error in profile.php: " . $e->getMessage());
    include BASE_PATH . '/includes/header.php';
    echo '<div class="container mt-5">
            <div class="alert alert-danger">
                <h4>خطأ في قاعدة البيانات</h4>
                <p>حدث خطأ أثناء محاولة الوصول إلى قاعدة البيانات. الرجاء المحاولة مرة أخرى لاحقاً.</p>
                <a href="' . APP_URL . '/home.php" class="btn btn-primary">العودة للصفحة الرئيسية</a>
            </div>
          </div>';
    include BASE_PATH . '/includes/footer.php';
    exit;
}

include BASE_PATH . '/includes/header.php';
?>

<div class="container profile-container" data-user-id="<?php echo $profile_id; ?>">
    <div class="profile-header">
        <div class="profile-avatar">
            <?php if ($user['avatar_url']): ?>
                <img src="<?php echo htmlspecialchars($user['avatar_url']); ?>" alt="<?php echo htmlspecialchars($user['username']); ?>">
            <?php else: ?>
                <div class="default-avatar">
                    <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                </div>
            <?php endif; ?>
        </div>
        
        <h1 class="profile-username">
            <?php echo htmlspecialchars($user['username']); ?>
            <?php if ($user['is_verified']): ?>
                <i class="fas fa-check-circle verified-badge"></i>
            <?php endif; ?>
        </h1>
        
        <?php if ($user['name']): ?>
            <div class="profile-name"><?php echo htmlspecialchars($user['name']); ?></div>
        <?php endif; ?>
        
        <?php if ($user['bio']): ?>
            <div class="profile-bio"><?php echo nl2br(htmlspecialchars($user['bio'])); ?></div>
        <?php endif; ?>
        
        <div class="profile-stats">
            <div class="stat-item">
                <span class="stat-value"><?php echo number_format($user['posts_count']); ?></span>
                <span class="stat-label">منشور</span>
            </div>
            <div class="stat-item">
                <span class="stat-value followers-count"><?php echo number_format($user['followers_count']); ?></span>
                <span class="stat-label">متابع</span>
            </div>
            <div class="stat-item">
                <span class="stat-value"><?php echo number_format($user['following_count']); ?></span>
                <span class="stat-label">يتابع</span>
            </div>
        </div>
        
        <div class="profile-actions">
            <?php if ($profile_id !== $_SESSION['user_id']): ?>
                <button class="btn follow-btn <?php echo $user['is_following'] ? 'following' : ''; ?>" 
                        onclick="toggleFollow(this)" 
                        data-user-id="<?php echo $profile_id; ?>">
                    <?php echo $user['is_following'] ? 'إلغاء المتابعة' : 'متابعة'; ?>
                </button>
                <a href="<?php echo APP_URL; ?>/chat.php?user=<?php echo $profile_id; ?>" class="btn message-btn">
                    <i class="fas fa-envelope"></i> رسالة
                </a>
            <?php else: ?>
                <a href="<?php echo APP_URL; ?>/settings.php" class="btn edit-profile-btn">
                    <i class="fas fa-edit"></i> تعديل الملف الشخصي
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="profile-tabs">
        <button class="tab-btn active" onclick="switchTab('posts')">المنشورات</button>
        <button class="tab-btn" onclick="switchTab('media')">الوسائط</button>
        <button class="tab-btn" onclick="switchTab('likes')">الإعجابات</button>
    </div>
    
    <div id="posts" class="tab-content active"></div>
    <div id="media" class="tab-content"></div>
    <div id="likes" class="tab-content"></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadTabContent('posts');
});
</script>

<?php include BASE_PATH . '/includes/footer.php'; ?>
