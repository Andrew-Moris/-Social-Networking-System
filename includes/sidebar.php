<?php
if (!isset($is_logged_in)) {
    $is_logged_in = isset($_SESSION['user_id']);
}

$current_username = isset($_SESSION['username']) ? $_SESSION['username'] : '';
$current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '';

$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar">
    <div class="logo">
        <a href="/WEP/index.php">
            <img src="/WEP/assets/images/logo.png" alt="<?php echo APP_NAME; ?>" onerror="this.src='/WEP/assets/images/logo-placeholder.png'; this.onerror='';">
        </a>
    </div>
    
    <ul class="sidebar-menu">
        <?php if ($is_logged_in): ?>
            <li class="sidebar-menu-section">الحساب</li>
            <li>
                <a href="/WEP/index.php?username=<?php echo urlencode($current_username); ?>" class="<?php echo (isset($_GET['username']) && $_GET['username'] == $current_username) ? 'active' : ''; ?>">
                    <i class="fas fa-user-circle"></i> <span>الملف الشخصي</span>
                </a>
            </li>
            <li>
                <a href="/WEP/notifications.php" class="<?php echo ($current_page == 'notifications.php') ? 'active' : ''; ?>">
                    <i class="fas fa-bell"></i> <span>الإشعارات</span>
                </a>
            </li>
            <li>
                <a href="/WEP/settings.php" class="<?php echo ($current_page == 'settings.php') ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i> <span>الإعدادات</span>
                </a>
            </li>
            
            <li class="sidebar-menu-section">المحتوى</li>
            <li>
                <a href="/WEP/index.php" class="<?php echo ($current_page == 'index.php' && !isset($_GET['username'])) ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i> <span>الرئيسية</span>
                </a>
            </li>
            <li>
                <a href="/WEP/explore.php" class="<?php echo ($current_page == 'explore.php') ? 'active' : ''; ?>">
                    <i class="fas fa-compass"></i> <span>استكشاف</span>
                </a>
            </li>
            <li>
                <a href="/WEP/messages.php" class="<?php echo ($current_page == 'messages.php') ? 'active' : ''; ?>">
                    <i class="fas fa-envelope"></i> <span>الرسائل</span>
                </a>
            </li>
            <li>
                <a href="/WEP/bookmarks.php" class="<?php echo ($current_page == 'bookmarks.php') ? 'active' : ''; ?>">
                    <i class="fas fa-bookmark"></i> <span>المحفوظات</span>
                </a>
            </li>
            
            <li class="sidebar-menu-section">أخرى</li>
            <li>
                <a href="/WEP/logout.php">
                    <i class="fas fa-sign-out-alt"></i> <span>تسجيل الخروج</span>
                </a>
            </li>
        <?php else: ?>
            <li>
                <a href="/WEP/frontend/login.html">
                    <i class="fas fa-sign-in-alt"></i> <span>تسجيل الدخول</span>
                </a>
            </li>
            <li>
                <a href="/WEP/frontend/register.html">
                    <i class="fas fa-user-plus"></i> <span>إنشاء حساب</span>
                </a>
            </li>
        <?php endif; ?>
    </ul>
</div>
<div class="mobile-menu-toggle d-lg-none">
    <button class="btn btn-outline-light" id="sidebar-toggle">
        <i class="fas fa-bars"></i>
    </button>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const sidebar = document.querySelector('.sidebar');
        
        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('show');
            });
        }
    });
</script>
