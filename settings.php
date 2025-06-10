<?php


session_start();

require_once 'config.php'; 
require_once 'functions.php'; 

if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

$session_username = $_SESSION['username'];
$session_user_id = $_SESSION['user_id'];

$user = null;
$success_message = '';
$error_message = '';

try {
    $host = 'localhost';
    $dbname = 'wep_db';
    $user_db = 'root';
    $password = '';
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $user_db, $password, $options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    error_log("Settings.php: Database connection successful");
    
    $user = findUserByUsername($pdo, $session_username);
    
    if (!$user) {
        log_error("User from session '{$session_username}' not found in database.");
        session_destroy();
        header('Location: login.php');
        exit;
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'update_avatar') {
            if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
                $error_message = 'حدث خطأ أثناء رفع الصورة. الرجاء المحاولة مرة أخرى.';
            } else {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $file_type = $_FILES['avatar']['type'];
                
                if (!in_array($file_type, $allowed_types)) {
                    $error_message = 'نوع الملف غير مدعوم. يرجى استخدام صورة بصيغة JPEG أو PNG أو GIF.';
                } else {
                    $upload_dir = 'uploads/avatars/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $file_extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                    $new_file_name = 'avatar_' . $session_user_id . '_' . time() . '.' . $file_extension;
                    $target_file = $upload_dir . $new_file_name;
                    
                    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target_file)) {
                        $avatar_path = '/' . $target_file; 
                        
                        $columns_stmt = $pdo->query("SHOW COLUMNS FROM users");
                        $columns = $columns_stmt->fetchAll(PDO::FETCH_COLUMN);
                        
                        $avatar_column = null;
                        foreach(['avatar', 'avatar_url', 'profile_picture', 'image', 'picture'] as $possible_column) {
                            if (in_array($possible_column, $columns)) {
                                $avatar_column = $possible_column;
                                break;
                            }
                        }
                        
                        if ($avatar_column) {
                            $avatar_update_stmt = $pdo->prepare("UPDATE users SET {$avatar_column} = ?, updated_at = NOW() WHERE id = ?");
                            if ($avatar_update_stmt->execute([$avatar_path, $session_user_id])) {
                                $success_message = 'تم تحديث الصورة الشخصية بنجاح';
                                $user = findUserByUsername($pdo, $session_username);
                            } else {
                                $error_message = 'فشل في تحديث الصورة الشخصية في قاعدة البيانات';
                            }
                        } else {
                            $error_message = 'لم يتم العثور على عمود الصورة الشخصية في قاعدة البيانات';
                        }
                    } else {
                        $error_message = 'فشل في تحميل الصورة. الرجاء المحاولة مرة أخرى.';
                    }
                }
            }
        }
        
        if ($action === 'update_profile') {
            $first_name = trim($_POST['first_name'] ?? '');
            $last_name = trim($_POST['last_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $bio = trim($_POST['bio'] ?? '');
            $location = trim($_POST['location'] ?? '');
            $website = trim($_POST['website'] ?? '');
            
            if (empty($first_name) || empty($last_name) || empty($email)) {
                $error_message = 'الاسم الأول والأخير والبريد الإلكتروني مطلوبة';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error_message = 'البريد الإلكتروني غير صحيح';
            } else {
                $email_check = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $email_check->execute([$email, $session_user_id]);
                
                if ($email_check->rowCount() > 0) {
                    $error_message = 'البريد الإلكتروني مستخدم بالفعل';
                } else {
                    $update_stmt = $pdo->prepare("
                        UPDATE users 
                        SET first_name = ?, last_name = ?, email = ?, bio = ?, location = ?, website = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    
                    if ($update_stmt->execute([$first_name, $last_name, $email, $bio, $location, $website, $session_user_id])) {
                        $success_message = 'تم تحديث البيانات بنجاح';
                        $user = findUserByUsername($pdo, $session_username);
                    } else {
                        $error_message = 'فشل في تحديث البيانات';
                    }
                }
            }
        }
        
        if ($action === 'change_password') {
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                $error_message = 'جميع حقول كلمة المرور مطلوبة';
            } elseif ($new_password !== $confirm_password) {
                $error_message = 'كلمة المرور الجديدة وتأكيدها غير متطابقين';
            } elseif (strlen($new_password) < 6) {
                $error_message = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
            } elseif (!password_verify($current_password, $user['password'])) {
                $error_message = 'كلمة المرور الحالية غير صحيحة';
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $password_stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
                
                if ($password_stmt->execute([$hashed_password, $session_user_id])) {
                    $success_message = 'تم تغيير كلمة المرور بنجاح';
                } else {
                    $error_message = 'فشل في تغيير كلمة المرور';
                }
            }
        }
        
        if ($action === 'update_privacy') {
            $privacy_level = $_POST['privacy_level'] ?? 'public';
            $show_email = isset($_POST['show_email']) ? 1 : 0;
            $show_location = isset($_POST['show_location']) ? 1 : 0;
            $allow_messages = isset($_POST['allow_messages']) ? 1 : 0;
            
            $privacy_stmt = $pdo->prepare("
                UPDATE users 
                SET privacy_level = ?, show_email = ?, show_location = ?, allow_messages = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            if ($privacy_stmt->execute([$privacy_level, $show_email, $show_location, $allow_messages, $session_user_id])) {
                $success_message = 'تم تحديث إعدادات الخصوصية بنجاح';
                $user = findUserByUsername($pdo, $session_username);
            } else {
                $error_message = 'فشل في تحديث إعدادات الخصوصية';
            }
        }
    }

} catch (PDOException $e) {
    log_error("Database Error on settings.php: " . $e->getMessage());
    $error_message = "حدث خطأ أثناء تحميل الصفحة. يرجى المحاولة مرة أخرى لاحقًا.";
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> | Settings</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
            transform: none !important;
            transition: none !important;
            animation: none !important;
        }
        
        .nav-link,
        .sidebar-nav a,
        .btn,
        .form-input,
        .tab-button {
            transition: color 0.2s ease, background-color 0.2s ease, border-color 0.2s ease, opacity 0.2s ease !important;
            transform: none !important;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-primary);
            background-image: 
                radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(120, 219, 255, 0.2) 0%, transparent 50%);
            color: var(--text-primary);
            min-height: 100vh;
            line-height: 1.6;
            overflow-x: hidden;
        }
        
        .glass-morphism {
            background: var(--bg-card);
            backdrop-filter: var(--blur-glass);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-card);
        }
        
        .nav-header {
            background: rgba(10, 15, 28, 0.9);
            backdrop-filter: var(--blur-glass);
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 100;
            padding: 1rem 0;
        }
        
        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem 1rem;
            position: relative;
            z-index: 1;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 3rem;
            padding: 3rem 0 2rem 0;
            background: var(--bg-card);
            backdrop-filter: var(--blur-glass);
            border: 1px solid var(--border-color);
            border-radius: 2rem;
            position: relative;
            overflow: visible;
            z-index: 1;
        }
        
        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(102, 126, 234, 0.1) 0%, transparent 50%, rgba(255, 119, 198, 0.1) 100%);
            z-index: 1;
        }
        
        .page-header > * {
            position: relative;
            z-index: 2;
        }
        
        .page-title {
            font-size: 3.5rem;
            font-weight: 800;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
        }
        
        .page-subtitle {
            color: var(--text-secondary);
            font-size: 1.2rem;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .settings-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .settings-sidebar {
            background: var(--bg-card);
            backdrop-filter: var(--blur-glass);
            border: 1px solid var(--border-color);
            border-radius: 1.5rem;
            padding: 2rem;
            height: fit-content;
            position: sticky;
            top: 120px;
            z-index: 2;
        }
        
        .sidebar-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .sidebar-nav {
            list-style: none;
        }
        
        .sidebar-nav li {
            margin-bottom: 0.5rem;
        }
        
        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 1.5rem;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 1rem;
            font-weight: 500;
            position: relative;
            z-index: 1;
        }
        
        .sidebar-nav a:hover {
            background: var(--bg-card-hover);
            color: var(--text-primary);
        }
        
        .sidebar-nav a.active {
            background: var(--primary-gradient);
            color: white;
        }
        
        .settings-content {
            background: var(--bg-card);
            backdrop-filter: var(--blur-glass);
            border: 1px solid var(--border-color);
            border-radius: 1.5rem;
            padding: 2.5rem;
            position: relative;
            z-index: 1;
        }
        
        .section {
            display: none;
        }
        
        .section.active {
            display: block;
        }
        
        .section-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.75rem;
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 0.95rem;
        }
        
        .form-input {
            width: 100%;
            padding: 1rem 1.25rem;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-color);
            border-radius: 0.75rem;
            color: var(--text-primary);
            font-family: inherit;
            font-size: 0.95rem;
            position: relative;
            z-index: 1;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #667eea;
            background: rgba(255, 255, 255, 0.05);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-input::placeholder {
            color: var(--text-muted);
        }
        
        .form-textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        .form-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23a1a8b3' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 1rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 3rem;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--border-color);
            border-radius: 0.75rem;
        }
        
        .checkbox {
            width: 1.25rem;
            height: 1.25rem;
            accent-color: #667eea;
        }
        
        .checkbox-label {
            color: var(--text-secondary);
            font-weight: 500;
            cursor: pointer;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.875rem 1.75rem;
            border: none;
            border-radius: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            font-size: 0.95rem;
            text-decoration: none;
            position: relative;
            z-index: 1;
        }
        
        .btn-primary {
            background: var(--primary-gradient);
            color: white;
            box-shadow: var(--shadow-card);
        }
        
        .btn-primary:hover {
            opacity: 0.9;
        }
        
        .btn-secondary {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
        }
        
        .btn-secondary:hover {
            background: var(--bg-card-hover);
        }
        
        .alert {
            padding: 1.25rem 1.5rem;
            border-radius: 1rem;
            margin-bottom: 2rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid #10b981;
            color: #a7f3d0;
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid #ef4444;
            color: #fecaca;
        }
        
        .info-card {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            padding: 2rem;
            margin-top: 2rem;
        }
        
        .info-card h3 {
            color: var(--text-primary);
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .info-card ul {
            color: var(--text-secondary);
            line-height: 1.8;
            list-style: none;
        }
        
        .info-card li {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        .form-help {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-top: 0.5rem;
        }
        
        .min-h-screen { min-height: 100vh; }
        .flex { display: flex; }
        .flex-col { flex-direction: column; }
        .items-center { align-items: center; }
        .justify-between { justify-content: space-between; }
        .justify-center { justify-content: center; }
        .gap-2 { gap: 0.5rem; }
        .gap-3 { gap: 0.75rem; }
        .space-x-6 > * + * { margin-left: 1.5rem; }
        .container { max-width: 1200px; margin: 0 auto; }
        .mx-auto { margin-left: auto; margin-right: auto; }
        .px-4 { padding-left: 1rem; padding-right: 1rem; }
        .px-6 { padding-left: 1.5rem; padding-right: 1.5rem; }
        .py-2 { padding-top: 0.5rem; padding-bottom: 0.5rem; }
        .py-4 { padding-top: 1rem; padding-bottom: 1rem; }
        .text-center { text-align: center; }
        .text-white { color: white; }
        .text-gray-100 { color: #f3f4f6; }
        .text-gray-300 { color: #d1d5db; }
        .text-blue-400 { color: #60a5fa; }
        .text-red-400 { color: #f87171; }
        .text-xl { font-size: 1.25rem; }
        .text-2xl { font-size: 1.5rem; }
        .font-bold { font-weight: 700; }
        .hover\:text-white:hover { color: white; }
        .hover\:text-blue-400:hover { color: #60a5fa; }
        .hover\:text-red-400:hover { color: #f87171; }
        .transition-colors { transition: color 0.2s ease, background-color 0.2s ease !important; }
        
        @media (max-width: 768px) {
            .page-title {
                font-size: 2.5rem;
            }
            
            .settings-container {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .settings-sidebar {
                position: relative;
                top: auto;
            }
            
            .sidebar-nav {
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            
            .sidebar-nav li {
                margin-bottom: 0;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .main-container {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="min-h-screen flex flex-col">
        <header class="nav-header">
            <div class="container mx-auto px-6 flex justify-between items-center">
                <div class="text-2xl font-bold">
                    <a href="home.php" class="text-white hover:text-blue-400 transition-colors">
                        <span style="background: var(--primary-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">SUT</span> 
                        <span class="text-gray-100">Premium</span>
                    </a>
                </div>
                
                <nav class="flex items-center space-x-6">
                    <a href="home.php" class="text-gray-300 hover:text-white transition-colors">
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
        
        <div class="main-container">
            <div class="page-header">
                <h1 class="page-title"><i class="bi bi-gear mr-3"></i>Settings</h1>
                <p class="page-subtitle">Manage your account settings, privacy preferences, and personal information</p>
            </div>

            <div class="settings-container">
                <aside class="settings-sidebar">
                    <h3 class="sidebar-title">
                        <i class="bi bi-sliders"></i>
                        Settings Menu
                    </h3>
                    <ul class="sidebar-nav">
                        <li>
                            <a href="#avatar" class="nav-item active" data-section="avatar">
                                <i class="bi bi-person-bounding-box"></i>
                                الصورة الشخصية
                            </a>
                        </li>
                        <li>
                            <a href="#profile" class="nav-item" data-section="profile">
                                <i class="bi bi-person-circle"></i>
                                Profile Settings
                            </a>
                        </li>
                        <li>
                            <a href="#password" class="nav-item" data-section="password">
                                <i class="bi bi-shield-lock"></i>
                                Password & Security
                            </a>
                        </li>
                        <li>
                            <a href="#privacy" class="nav-item" data-section="privacy">
                                <i class="bi bi-eye-slash"></i>
                                Privacy Settings
                            </a>
                        </li>
                        <li>
                            <a href="#notifications" class="nav-item" data-section="notifications">
                                <i class="bi bi-bell"></i>
                                Notifications
                            </a>
                        </li>
                    </ul>
                </aside>

                <main class="settings-content">
                    <?php if ($success_message): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle"></i>
                            <?php echo htmlspecialchars($success_message); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($error_message): ?>
                        <div class="alert alert-error">
                            <i class="bi bi-exclamation-triangle"></i>
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>

                    <section id="avatar" class="section active">
                        <h2 class="section-title">
                            <i class="bi bi-person-bounding-box"></i>
                            الصورة الشخصية
                        </h2>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="update_avatar">
                            
                            <div class="form-group text-center">
                                <div style="margin-bottom: 1.5rem;">
                                    <img src="<?php echo !empty($user['avatar']) ? htmlspecialchars($user['avatar']) : (!empty($user['avatar_url']) ? htmlspecialchars($user['avatar_url']) : 'https://placehold.co/200x200/1a1f2e/ffffff?text=' . strtoupper(substr($user['username'], 0, 1))); ?>" 
                                         alt="الصورة الشخصية" 
                                         style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; margin: 0 auto; border: 3px solid var(--border-color); box-shadow: var(--shadow-card);">
                                </div>
                                
                                <label for="avatar" class="form-label">اختر صورة جديدة</label>
                                <input type="file" id="avatar" name="avatar" class="form-input" accept="image/jpeg,image/png,image/gif" style="padding: 0.75rem;">
                                <div class="form-help">يمكنك تحميل صورة بصيغة JPG أو PNG أو GIF. الحد الأقصى لحجم الملف هو 5 ميجابايت.</div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-cloud-upload"></i>
                                تحميل الصورة
                            </button>
                        </form>
                    </section>

                    <section id="profile" class="section">
                        <h2 class="section-title">
                            <i class="bi bi-person-circle"></i>
                            Profile Information
                        </h2>
                        <form method="POST">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="first_name" class="form-label">First Name *</label>
                                    <input type="text" id="first_name" name="first_name" class="form-input" 
                                           value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="last_name" class="form-label">Last Name *</label>
                                    <input type="text" id="last_name" name="last_name" class="form-input" 
                                           value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="email" class="form-label">Email Address *</label>
                                <input type="email" id="email" name="email" class="form-input" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="bio" class="form-label">Bio</label>
                                <textarea id="bio" name="bio" class="form-input form-textarea" 
                                          placeholder="Tell us about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                                <div class="form-help">Write a short description about yourself (optional)</div>
                            </div>
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="location" class="form-label">Location</label>
                                    <input type="text" id="location" name="location" class="form-input" 
                                           value="<?php echo htmlspecialchars($user['location'] ?? ''); ?>" 
                                           placeholder="Your city or country">
                                </div>
                                
                                <div class="form-group">
                                    <label for="website" class="form-label">Website</label>
                                    <input type="url" id="website" name="website" class="form-input" 
                                           value="<?php echo htmlspecialchars($user['website'] ?? ''); ?>" 
                                           placeholder="https://example.com">
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i>
                                Save Changes
                            </button>
                        </form>
                    </section>

                    <section id="password" class="section">
                        <h2 class="section-title">
                            <i class="bi bi-shield-lock"></i>
                            Change Password
                        </h2>
                        <form method="POST">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="form-group">
                                <label for="current_password" class="form-label">Current Password *</label>
                                <input type="password" id="current_password" name="current_password" class="form-input" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="new_password" class="form-label">New Password *</label>
                                <input type="password" id="new_password" name="new_password" class="form-input" 
                                       minlength="6" required>
                                <div class="form-help">Password must be at least 6 characters long</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password" class="form-label">Confirm New Password *</label>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-input" 
                                       minlength="6" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-shield-check"></i>
                                Update Password
                            </button>
                        </form>
                    </section>

                    <section id="privacy" class="section">
                        <h2 class="section-title">
                            <i class="bi bi-eye-slash"></i>
                            Privacy Settings
                        </h2>
                        <form method="POST">
                            <input type="hidden" name="action" value="update_privacy">
                            
                            <div class="form-group">
                                <label for="privacy_level" class="form-label">Profile Visibility</label>
                                <select id="privacy_level" name="privacy_level" class="form-input form-select">
                                    <option value="public" <?php echo ($user['privacy_level'] ?? 'public') === 'public' ? 'selected' : ''; ?>>
                                        Public - Anyone can view your profile
                                    </option>
                                    <option value="friends" <?php echo ($user['privacy_level'] ?? 'public') === 'friends' ? 'selected' : ''; ?>>
                                        Friends Only - Only your friends can view
                                    </option>
                                    <option value="private" <?php echo ($user['privacy_level'] ?? 'public') === 'private' ? 'selected' : ''; ?>>
                                        Private - Only you can view your profile
                                    </option>
                                </select>
                            </div>
                            
                            <div class="checkbox-group">
                                <input type="checkbox" id="show_email" name="show_email" class="checkbox" 
                                       <?php echo ($user['show_email'] ?? 0) ? 'checked' : ''; ?>>
                                <label for="show_email" class="checkbox-label">Show email address on profile</label>
                            </div>
                            
                            <div class="checkbox-group">
                                <input type="checkbox" id="show_location" name="show_location" class="checkbox" 
                                       <?php echo ($user['show_location'] ?? 0) ? 'checked' : ''; ?>>
                                <label for="show_location" class="checkbox-label">Show location on profile</label>
                            </div>
                            
                            <div class="checkbox-group">
                                <input type="checkbox" id="allow_messages" name="allow_messages" class="checkbox" 
                                       <?php echo ($user['allow_messages'] ?? 1) ? 'checked' : ''; ?>>
                                <label for="allow_messages" class="checkbox-label">Allow messages from other users</label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-shield"></i>
                                Save Privacy Settings
                            </button>
                        </form>
                    </section>

                    <section id="notifications" class="section">
                        <h2 class="section-title">
                            <i class="bi bi-bell"></i>
                            Notification Preferences
                        </h2>
                        <p style="color: var(--text-secondary); margin-bottom: 2rem;">
                            Notification settings will be available soon. Currently, all notifications are enabled by default.
                        </p>
                        
                        <div class="info-card">
                            <h3>Currently Available Notifications:</h3>
                            <ul>
                                <li><i class="bi bi-check-circle" style="color: #10b981;"></i> Like notifications</li>
                                <li><i class="bi bi-check-circle" style="color: #10b981;"></i> Comment notifications</li>
                                <li><i class="bi bi-check-circle" style="color: #10b981;"></i> Follow notifications</li>
                                <li><i class="bi bi-check-circle" style="color: #10b981;"></i> Share notifications</li>
                                <li><i class="bi bi-check-circle" style="color: #10b981;"></i> Message notifications</li>
                            </ul>
                        </div>
                    </section>
                </main>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                
                document.querySelectorAll('.nav-item').forEach(nav => nav.classList.remove('active'));
                document.querySelectorAll('.section').forEach(section => section.classList.remove('active'));
                
                this.classList.add('active');
                
                const sectionId = this.getAttribute('data-section');
                document.getElementById(sectionId).classList.add('active');
            });
        });

        document.getElementById('confirm_password')?.addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
        
        console.log('🎨 Settings page loaded with modern design');
    </script>
</body>
</html> 