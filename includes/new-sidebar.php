<?php

if (!isset($is_logged_in)) {
    $is_logged_in = isset($_SESSION['user_id']);
}

$current_username = isset($_SESSION['username']) ? $_SESSION['username'] : '';
$current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '';

$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="left-sidebar">
    <?php if ($is_logged_in): ?>
        <a href="/WEP/index.php?username=<?php echo urlencode($current_username); ?>" class="menu-item <?php echo (isset($_GET['username']) && $_GET['username'] == $current_username) ? 'active' : ''; ?>">
            <div class="menu-icon">
                <img src="<?php echo isset($_SESSION['avatar_url']) && !empty($_SESSION['avatar_url']) ? $_SESSION['avatar_url'] : '/WEP/assets/images/default-avatar.png'; ?>" 
                     class="user-avatar" alt="<?php echo htmlspecialchars($current_username); ?>">
            </div>
            <div class="menu-label"><?php echo htmlspecialchars($current_username); ?></div>
        </a>
        
        <a href="/WEP/index.php" class="menu-item <?php echo ($current_page == 'index.php' && !isset($_GET['username'])) ? 'active' : ''; ?>">
            <div class="menu-icon"><i class="fas fa-home"></i></div>
            <div class="menu-label">الصفحة الرئيسية</div>
        </a>
        
        <a href="/WEP/friends.php" class="menu-item <?php echo ($current_page == 'friends.php') ? 'active' : ''; ?>">
            <div class="menu-icon"><i class="fas fa-user-friends"></i></div>
            <div class="menu-label">الأصدقاء</div>
        </a>
        
        <a href="/WEP/saved.php" class="menu-item <?php echo ($current_page == 'saved.php') ? 'active' : ''; ?>">
            <div class="menu-icon"><i class="fas fa-bookmark"></i></div>
            <div class="menu-label">المحفوظات</div>
        </a>
        
        <a href="/WEP/groups.php" class="menu-item <?php echo ($current_page == 'groups.php') ? 'active' : ''; ?>">
            <div class="menu-icon"><i class="fas fa-users"></i></div>
            <div class="menu-label">المجموعات</div>
        </a>
        
        <a href="/WEP/marketplace.php" class="menu-item <?php echo ($current_page == 'marketplace.php') ? 'active' : ''; ?>">
            <div class="menu-icon"><i class="fas fa-store"></i></div>
            <div class="menu-label">المتجر</div>
        </a>
        
        <a href="/WEP/videos.php" class="menu-item <?php echo ($current_page == 'videos.php') ? 'active' : ''; ?>">
            <div class="menu-icon"><i class="fas fa-video"></i></div>
            <div class="menu-label">الفيديوهات</div>
        </a>
        
        <hr class="sidebar-divider">
        
        <a href="/WEP/memories.php" class="menu-item <?php echo ($current_page == 'memories.php') ? 'active' : ''; ?>">
            <div class="menu-icon"><i class="fas fa-history"></i></div>
            <div class="menu-label">الذكريات</div>
        </a>
        
        <a href="/WEP/events.php" class="menu-item <?php echo ($current_page == 'events.php') ? 'active' : ''; ?>">
            <div class="menu-icon"><i class="fas fa-calendar-alt"></i></div>
            <div class="menu-label">الأحداث</div>
        </a>
        
        <a href="#" class="menu-item more-toggle" id="more-toggle">
            <div class="menu-icon"><i class="fas fa-chevron-down"></i></div>
            <div class="menu-label">المزيد</div>
        </a>
        
        <div class="more-menu" id="more-menu" style="display: none;">
            <a href="/WEP/pages.php" class="menu-item <?php echo ($current_page == 'pages.php') ? 'active' : ''; ?>">
                <div class="menu-icon"><i class="fas fa-flag"></i></div>
                <div class="menu-label">الصفحات</div>
            </a>
            
            <a href="/WEP/games.php" class="menu-item <?php echo ($current_page == 'games.php') ? 'active' : ''; ?>">
                <div class="menu-icon"><i class="fas fa-gamepad"></i></div>
                <div class="menu-label">الألعاب</div>
            </a>
            
            <a href="/WEP/jobs.php" class="menu-item <?php echo ($current_page == 'jobs.php') ? 'active' : ''; ?>">
                <div class="menu-icon"><i class="fas fa-briefcase"></i></div>
                <div class="menu-label">الوظائف</div>
            </a>
            
            <a href="/WEP/settings.php" class="menu-item <?php echo ($current_page == 'settings.php') ? 'active' : ''; ?>">
                <div class="menu-icon"><i class="fas fa-cog"></i></div>
                <div class="menu-label">الإعدادات</div>
            </a>
            
            <a href="/WEP/help.php" class="menu-item <?php echo ($current_page == 'help.php') ? 'active' : ''; ?>">
                <div class="menu-icon"><i class="fas fa-question-circle"></i></div>
                <div class="menu-label">المساعدة</div>
            </a>
        </div>
    <?php else: ?>
        <div class="visitor-sidebar">
            <h5 class="sidebar-title">مرحباً بك في <?php echo APP_NAME; ?></h5>
            <p>قم بتسجيل الدخول أو إنشاء حساب جديد للتفاعل مع المستخدمين ومشاركة المحتوى.</p>
            <a href="/WEP/frontend/login.html" class="btn btn-primary w-100 mb-2">تسجيل الدخول</a>
            <a href="/WEP/frontend/register.html" class="btn btn-outline w-100">إنشاء حساب</a>
        </div>
    <?php endif; ?>
</div>
