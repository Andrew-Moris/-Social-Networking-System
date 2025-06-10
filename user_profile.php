<?php

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/functions.php';

if (!isset($_GET['username'])) {
    header('Location: index.php');
    exit;
}

$username = $_GET['username'];
$page_title = $username . " | " . APP_NAME;

$is_logged_in = isset($_SESSION['user_id']);
$current_username = $is_logged_in ? $_SESSION['username'] : '';

$user = null;
$posts = [];
$suggested_users = [];
$followers_count = 0;
$following_count = 0;
$posts_count = 0;

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
    
    $stmt = $pdo->prepare("SELECT id, username, avatar_url, bio, created_at FROM users WHERE username = :username");
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $user_id = $user['id'];
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE followed_id = :user_id");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $followers_count = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE follower_id = :user_id");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $following_count = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $posts_count = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("
            SELECT p.*, u.username, u.avatar_url 
            FROM posts p 
            JOIN users u ON p.user_id = u.id 
            WHERE p.user_id = :user_id 
            ORDER BY p.created_at DESC 
            LIMIT 10
        ");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("
            SELECT id, username, avatar_url, bio 
            FROM users 
            WHERE id != :user_id 
            ORDER BY RAND() 
            LIMIT 5
        ");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $suggested_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
}

$user_data = [
    'user' => $user,
    'followers_count' => $followers_count,
    'following_count' => $following_count,
    'posts_count' => $posts_count
];

include 'includes/new-header.php';
?>

<?php include 'includes/new-sidebar.php'; ?>

<div class="main-content">
    <?php if ($user): ?>
        <div class="profile-container">
            <div class="profile-header">
                <div class="profile-avatar">
                    <img src="<?php echo !empty($user['avatar_url']) ? $user['avatar_url'] : '/WEP/assets/images/default-avatar.png'; ?>" 
                         alt="<?php echo htmlspecialchars($user['username']); ?>">
                </div>
                <div class="profile-info">
                    <h1 class="profile-username"><?php echo htmlspecialchars($user['username']); ?></h1>
                    <p class="profile-bio"><?php echo !empty($user['bio']) ? htmlspecialchars($user['bio']) : 'لا توجد سيرة ذاتية'; ?></p>
                    
                    <div class="profile-stats">
                        <div class="profile-stat profile-stat-posts">
                            <span class="stat-value"><?php echo $posts_count; ?></span>
                            <span class="stat-label">منشور</span>
                        </div>
                        <div class="profile-stat profile-stat-followers">
                            <span class="stat-value"><?php echo $followers_count; ?></span>
                            <span class="stat-label">متابعين</span>
                        </div>
                        <div class="profile-stat profile-stat-following">
                            <span class="stat-value"><?php echo $following_count; ?></span>
                            <span class="stat-label">يتابع</span>
                        </div>
                    </div>
                    
                    <?php if ($is_logged_in && $current_username !== $username): ?>
                        <button class="btn btn-primary follow-btn" data-user-id="<?php echo $user['id']; ?>">متابعة</button>
                    <?php elseif ($is_logged_in && $current_username === $username): ?>
                        <a href="/WEP/settings.php" class="btn btn-outline-primary">تعديل الملف الشخصي</a>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="profile-posts">
                <h3>المنشورات</h3>
                
                <div class="feed-container">
                    <?php if (empty($posts)): ?>
                        <div class="empty-feed">
                            <div class="text-center py-5">
                                <i class="fas fa-newspaper fa-4x mb-3 text-muted"></i>
                                <h4>لا توجد منشورات لعرضها</h4>
                                <p class="text-muted">لم يقم <?php echo htmlspecialchars($username); ?> بنشر أي محتوى حتى الآن.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($posts as $post): ?>
                            <div class="post" id="post-<?php echo $post['id']; ?>">
                                <div class="post-header">
                                    <img src="<?php echo !empty($post['avatar_url']) ? $post['avatar_url'] : '/WEP/assets/images/default-avatar.png'; ?>" 
                                         class="user-avatar" alt="<?php echo htmlspecialchars($post['username']); ?>">
                                    <div class="post-user-info">
                                        <h6 class="post-username">
                                            <a href="/WEP/user_profile.php?username=<?php echo urlencode($post['username']); ?>">
                                                <?php echo htmlspecialchars($post['username']); ?>
                                            </a>
                                        </h6>
                                        <div class="post-time"><?php echo date('d M Y H:i', strtotime($post['created_at'])); ?></div>
                                    </div>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light" type="button" id="post-options-<?php echo $post['id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-ellipsis-h"></i>
                                        </button>
                                        <ul class="dropdown-menu" aria-labelledby="post-options-<?php echo $post['id']; ?>">
                                            <li><a class="dropdown-item" href="#"><i class="fas fa-bookmark me-2"></i> حفظ المنشور</a></li>
                                            <?php if ($is_logged_in && $current_username === $post['username']): ?>
                                                <li><a class="dropdown-item text-danger" href="#"><i class="fas fa-trash-alt me-2"></i> حذف المنشور</a></li>
                                            <?php endif; ?>
                                            <li><a class="dropdown-item" href="#"><i class="fas fa-flag me-2"></i> الإبلاغ عن المنشور</a></li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div class="post-content">
                                    <?php if (!empty($post['content'])): ?>
                                        <div class="post-text"><?php echo nl2br(htmlspecialchars($post['content'])); ?></div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($post['image_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($post['image_url']); ?>" class="post-image" alt="صورة المنشور">
                                    <?php endif; ?>
                                </div>
                                
                                <div class="post-actions">
                                    <button class="post-action-btn like-btn" data-post-id="<?php echo $post['id']; ?>">
                                        <i class="fas fa-thumbs-up"></i>
                                        <span>إعجاب</span>
                                    </button>
                                    <button class="post-action-btn dislike-btn" data-post-id="<?php echo $post['id']; ?>">
                                        <i class="fas fa-thumbs-down"></i>
                                        <span>عدم إعجاب</span>
                                    </button>
                                    <button class="post-action-btn comment-btn" data-post-id="<?php echo $post['id']; ?>">
                                        <i class="fas fa-comment"></i>
                                        <span>تعليق</span>
                                    </button>
                                    <button class="post-action-btn share-btn" data-post-id="<?php echo $post['id']; ?>">
                                        <i class="fas fa-share"></i>
                                        <span>مشاركة</span>
                                    </button>
                                </div>
                                
                                <div class="post-comments" id="comments-<?php echo $post['id']; ?>" style="display: none;">
                                    <div class="comment-input-container">
                                        <input type="text" class="comment-input" placeholder="اكتب تعليقاً..." data-post-id="<?php echo $post['id']; ?>">
                                        <button class="comment-submit-btn" data-post-id="<?php echo $post['id']; ?>">
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-danger text-center mt-4">
            <i class="fas fa-exclamation-circle fa-3x mb-3"></i>
            <h4>عذراً، لم يتم العثور على المستخدم</h4>
            <p>المستخدم "<?php echo htmlspecialchars($username); ?>" غير موجود أو قد تمت إزالة الحساب.</p>
            <a href="/WEP/index.php" class="btn btn-primary mt-3">العودة إلى الصفحة الرئيسية</a>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/new-right-sidebar.php'; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/WEP/js/social-app.js"></script>
</body>
</html>
