<?php

if (!isset($_SESSION)) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'SUT Premium'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="/WEP/assets/css/main.css" rel="stylesheet">
    <?php if (isset($extra_css) && !empty($extra_css)): ?>
    <link href="<?php echo $extra_css; ?>" rel="stylesheet">
    <?php endif; ?>
</head>
<body class="antialiased">
    <div class="min-h-screen flex flex-col">
        <?php if (isset($user) && $user):  ?>
        <header class="main-header">
            <nav class="main-nav">
                <div class="container">
                    <a href="index.php" class="logo">WEP</a>
                    <ul class="nav-links">
                        <li><a href="home.php">الرئيسية</a></li>
                        <li><a href="discover.php" class="active">استكشف</a></li>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <li><a href="profile.php">الملف الشخصي</a></li>
                            <li><a href="notifications.php">الإشعارات</a></li>
                            <li><a href="messages.php">الرسائل</a></li>
                            <li><a href="logout.php">تسجيل الخروج</a></li>
                        <?php else: ?>
                            <li><a href="login.php">تسجيل الدخول</a></li>
                            <li><a href="register.php">إنشاء حساب</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </nav>
        </header>
        <?php endif; ?>

<style>
.main-header {
    background: #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    position: sticky;
    top: 0;
    z-index: 1000;
}

.main-nav {
    padding: 1rem 0;
}

.main-nav .container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
}

.logo {
    font-size: 1.5rem;
    font-weight: bold;
    color: #007bff;
    text-decoration: none;
}

.nav-links {
    display: flex;
    gap: 1.5rem;
    list-style: none;
    margin: 0;
    padding: 0;
}

.nav-links a {
    color: #333;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s;
}

.nav-links a:hover,
.nav-links a.active {
    color: #007bff;
}

@media (max-width: 768px) {
    .nav-links {
        gap: 1rem;
    }
}
</style>
