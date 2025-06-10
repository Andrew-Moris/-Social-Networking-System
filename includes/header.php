<?php
if (!isset($page_title)) {
    $page_title = APP_NAME;
}

$is_logged_in = isset($_SESSION['user_id']);
$current_username = $is_logged_in ? $_SESSION['username'] : '';

function getUserAvatar($username) {
    global $pdo, $dsn, $db_user, $db_pass, $pdo_options;
    
    if (!isset($pdo)) {
        try {
            $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
        } catch (PDOException $e) {
            return DEFAULT_AVATAR;
        }
    }
    
    try {
        $stmt = $pdo->prepare('SELECT avatar_url FROM users WHERE username = :username LIMIT 1');
        $stmt->execute(['username' => $username]);
        $result = $stmt->fetch();
        
        if ($result && !empty($result['avatar_url'])) {
            return $result['avatar_url'];
        }
    } catch (PDOException $e) {
    }
    
    return DEFAULT_AVATAR;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">
    
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="/WEP/css/dashboard-theme.css">
    
    <style>
        :root {
            --primary-color: #2ea043;
            --secondary-color: #58a6ff;
            --error-color: #f85149;
            --success-color: #2ea043;
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        .nav-link {
            font-weight: 600;
        }
        
        .dropdown-menu {
            right: auto;
            left: 0;
        }
        
        .btn-primary {
            background: var(--primary-gradient);
            border: none;
        }
        
        .btn-primary:hover, 
        .btn-primary:focus {
            background: linear-gradient(135deg, #e8e75d 0%, #00a6de 100%);
            box-shadow: 0 5px 15px rgba(0, 191, 255, 0.3);
        }
        
        .profile-card {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid #fff;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .profile-stats {
            border-top: 1px solid rgba(0, 0, 0, 0.1);
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            padding: 15px 0;
        }
        
        .stat-value {
            font-weight: 700;
            font-size: 1.2rem;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: #666;
        }
        
        .post-card {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
        }
        
        .post-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            margin-left: 15px;
        }
        
        .post-image {
            border-radius: 10px;
            max-height: 400px;
            object-fit: contain;
        }
        
        .post-actions {
            padding-top: 15px;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
            margin-top: 15px;
        }
        
        .post-actions button {
            margin-left: 10px;
        }
        
        .container {
            max-width: 1200px;
        }
        
        .user-avatar-small {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <header class="top-header">
        <div class="user-dropdown">
            <a href="<?php echo APP_URL; ?>/u.php?username=<?php echo $_SESSION['username']; ?>" class="user-info-link" title="الملف الشخصي">
                <span class="user-name"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <div class="user-avatar-header">
                    <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                </div>
            </a>
        </div>

        <nav class="top-nav-menu">
            <a href="<?php echo APP_URL; ?>/home.php" class="nav-link" title="الرئيسية">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
            </a>
            <a href="<?php echo APP_URL; ?>/discover.php" class="nav-link" title="اكتشف">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"></polygon></svg>
            </a>
            <a href="<?php echo APP_URL; ?>/chat.php" class="nav-link" title="الدردشات">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
            </a>
            <a href="<?php echo APP_URL; ?>/friends.php" class="nav-link" title="الأصدقاء">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            </a>
            <a href="<?php echo APP_URL; ?>/bookmarks.php" class="nav-link" title="المحفوظات">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path></svg>
            </a>
            <a href="<?php echo APP_URL; ?>/u.php?username=<?php echo $_SESSION['username']; ?>" class="nav-link" title="الملف الشخصي">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            </a>
            <a href="<?php echo APP_URL; ?>/logout.php" class="nav-link logout-link" title="تسجيل الخروج">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            </a>
        </nav>

        <a href="<?php echo APP_URL; ?>/home.php" class="navbar-brand">
            <svg class="brand-logo-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
            </svg>
            <span class="brand-main-text">SUT Premium</span>
        </a>
    </header>
    
