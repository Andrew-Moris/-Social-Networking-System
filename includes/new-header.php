<?php

if (!isset($is_logged_in)) {
    $is_logged_in = isset($_SESSION['user_id']);
}

$current_username = isset($_SESSION['username']) ? $_SESSION['username'] : '';
$current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '';

$current_page = basename($_SERVER['PHP_SELF']);

$unread_notifications = 3;
$unread_messages = 2;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : APP_NAME; ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="/WEP/css/social-theme.css">
    
    <style>
    </style>
</head>
<body class="rtl">
    <nav class="top-nav">
        <div class="top-nav-container">
            <div class="d-flex align-items-center">
                <a href="/WEP/" class="logo me-3">
                    <span class="d-none d-sm-inline"><?php echo APP_NAME; ?></span>
                    <i class="fas fa-globe d-inline d-sm-none"></i>
                </a>
                
                <div class="search-container d-none d-md-block">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="search-input" placeholder="ابحث في <?php echo APP_NAME; ?>...">
                </div>
            </div>
            
            <div class="nav-menu d-none d-md-flex">
                <a href="/WEP/" class="nav-icon <?php echo ($current_page == 'index.php' && !isset($_GET['username'])) ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i>
                </a>
                <a href="/WEP/friends.php" class="nav-icon <?php echo ($current_page == 'friends.php') ? 'active' : ''; ?>">
                    <i class="fas fa-user-friends"></i>
                </a>
                <a href="/WEP/videos.php" class="nav-icon <?php echo ($current_page == 'videos.php') ? 'active' : ''; ?>">
                    <i class="fas fa-video"></i>
                </a>
                <a href="/WEP/marketplace.php" class="nav-icon <?php echo ($current_page == 'marketplace.php') ? 'active' : ''; ?>">
                    <i class="fas fa-store"></i>
                </a>
                <a href="/WEP/groups.php" class="nav-icon <?php echo ($current_page == 'groups.php') ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                </a>
            </div>
            
            <div class="nav-actions">
                <?php if ($is_logged_in): ?>
                    <div class="nav-icon" id="notifications-toggle">
                        <i class="fas fa-bell"></i>
                        <?php if ($unread_notifications > 0): ?>
                            <span class="notification-badge"><?php echo $unread_notifications; ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="nav-icon" id="messages-toggle">
                        <i class="fas fa-comment-alt"></i>
                        <?php if ($unread_messages > 0): ?>
                            <span class="notification-badge"><?php echo $unread_messages; ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="nav-icon" id="user-menu-toggle">
                        <img src="<?php echo isset($_SESSION['avatar_url']) && !empty($_SESSION['avatar_url']) ? $_SESSION['avatar_url'] : '/WEP/assets/images/default-avatar.png'; ?>" 
                             class="user-avatar" alt="<?php echo htmlspecialchars($current_username); ?>">
                    </div>
                <?php else: ?>
                    <a href="/WEP/frontend/login.html" class="btn btn-primary btn-sm me-2">تسجيل الدخول</a>
                    <a href="/WEP/frontend/register.html" class="btn btn-outline btn-sm">إنشاء حساب</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    
    <div class="dropdown-menu notifications-dropdown" id="notifications-dropdown">
        <h6 class="dropdown-header">الإشعارات</h6>
        <div class="dropdown-divider"></div>
        <a class="dropdown-item" href="#">
            <div class="d-flex">
                <img src="/WEP/assets/images/default-avatar.png" class="rounded-circle me-2" width="40" height="40">
                <div>
                    <p class="mb-0"><strong>أحمد محمد</strong> علق على منشورك.</p>
                    <small class="text-muted">منذ 5 دقائق</small>
                </div>
            </div>
        </a>
        <a class="dropdown-item" href="#">
            <div class="d-flex">
                <img src="/WEP/assets/images/default-avatar.png" class="rounded-circle me-2" width="40" height="40">
                <div>
                    <p class="mb-0"><strong>سارة أحمد</strong> بدأت بمتابعتك.</p>
                    <small class="text-muted">منذ ساعة</small>
                </div>
            </div>
        </a>
        <a class="dropdown-item" href="#">
            <div class="d-flex">
                <img src="/WEP/assets/images/default-avatar.png" class="rounded-circle me-2" width="40" height="40">
                <div>
                    <p class="mb-0"><strong>محمد علي</strong> أعجب بمنشورك.</p>
                    <small class="text-muted">منذ 3 ساعات</small>
                </div>
            </div>
        </a>
        <div class="dropdown-divider"></div>
        <a class="dropdown-item text-center" href="/WEP/notifications.php">عرض كل الإشعارات</a>
    </div>
    
    <div class="dropdown-menu messages-dropdown" id="messages-dropdown">
        <h6 class="dropdown-header">الرسائل</h6>
        <div class="dropdown-divider"></div>
        <a class="dropdown-item" href="#">
            <div class="d-flex">
                <img src="/WEP/assets/images/default-avatar.png" class="rounded-circle me-2" width="40" height="40">
                <div>
                    <p class="mb-0"><strong>خالد محمود</strong></p>
                    <small class="text-muted">مرحباً! كيف حالك اليوم؟</small>
                </div>
            </div>
        </a>
        <a class="dropdown-item" href="#">
            <div class="d-flex">
                <img src="/WEP/assets/images/default-avatar.png" class="rounded-circle me-2" width="40" height="40">
                <div>
                    <p class="mb-0"><strong>منى السيد</strong></p>
                    <small class="text-muted">هل يمكننا الاجتماع غداً؟</small>
                </div>
            </div>
        </a>
        <div class="dropdown-divider"></div>
        <a class="dropdown-item text-center" href="/WEP/messages.php">عرض كل الرسائل</a>
    </div>
    
    <div class="dropdown-menu user-dropdown" id="user-dropdown">
        <?php if ($is_logged_in): ?>
            <a class="dropdown-item d-flex align-items-center" href="/WEP/index.php?username=<?php echo urlencode($current_username); ?>">
                <i class="fas fa-user me-2"></i> الملف الشخصي
            </a>
            <a class="dropdown-item d-flex align-items-center" href="/WEP/settings.php">
                <i class="fas fa-cog me-2"></i> الإعدادات
            </a>
            <a class="dropdown-item d-flex align-items-center" href="/WEP/help.php">
                <i class="fas fa-question-circle me-2"></i> المساعدة
            </a>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item d-flex align-items-center" href="/WEP/logout.php">
                <i class="fas fa-sign-out-alt me-2"></i> تسجيل الخروج
            </a>
        <?php endif; ?>
    </div>
    
    <div class="main-container">
