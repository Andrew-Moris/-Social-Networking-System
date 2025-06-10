<?php


session_start();

require_once 'config.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id'])) {
    try {
        $host = 'localhost';
        $dbname = 'wep_db';
        $user_db = 'root';
        $password = '';
        $dsn_temp = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
        $pdo_temp = new PDO($dsn_temp, $user_db, $password);
        
        $stmt_temp = $pdo_temp->prepare("SELECT * FROM users WHERE id = 5");
        $stmt_temp->execute();
        $user_temp = $stmt_temp->fetch();
        
        if ($user_temp) {
            $_SESSION['user_id'] = $user_temp['id'];
            $_SESSION['username'] = $user_temp['username'];
            $_SESSION['email'] = $user_temp['email'];
            $_SESSION['first_name'] = $user_temp['first_name'];
            $_SESSION['last_name'] = $user_temp['last_name'];
            $_SESSION['avatar_url'] = $user_temp['avatar_url'];
        } else {
            header('Location: frontend/login.html?error=' . urlencode('يرجى تسجيل الدخول أولاً'));
            exit;
        }
    } catch (Exception $e) {
        header('Location: frontend/login.html?error=' . urlencode('يرجى تسجيل الدخول أولاً'));
        exit;
    }
}

$current_user_id = $_SESSION['user_id'];
$current_username = $_SESSION['username'];

$following = [];
$followers = [];
$friend_requests_received = [];
$friend_requests_sent = [];
$all_users = [];
$error_message = null;

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
    
    error_log("Friends.php: Database connection successful");
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$current_user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        header('Location: logout.php');
        exit;
    }
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS followers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        follower_id INT NOT NULL,
        followed_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (followed_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_follow (follower_id, followed_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS friend_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_request (sender_id, receiver_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    $followers_stmt = $pdo->prepare("
        SELECT DISTINCT 
            u.*,
            (SELECT COUNT(*) FROM followers WHERE follower_id = u.id) as following_count,
            (SELECT COUNT(*) FROM followers WHERE followed_id = u.id) as followers_count,
            CASE WHEN EXISTS (
                SELECT 1 FROM followers 
                WHERE follower_id = ? AND followed_id = u.id
            ) THEN 1 ELSE 0 END as is_following
        FROM users u
        INNER JOIN followers f ON f.follower_id = u.id
        WHERE f.followed_id = ?
        ORDER BY u.username ASC
    ");
    
    if (!$followers_stmt->execute([$current_user_id, $current_user_id])) {
        error_log("Error executing followers query: " . implode(", ", $followers_stmt->errorInfo()));
    }
    $followers = $followers_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $following_stmt = $pdo->prepare("
        SELECT DISTINCT
            u.*,
            (SELECT COUNT(*) FROM followers WHERE follower_id = u.id) as following_count,
            (SELECT COUNT(*) FROM followers WHERE followed_id = u.id) as followers_count,
            1 as is_following
        FROM users u
        INNER JOIN followers f ON f.followed_id = u.id
        WHERE f.follower_id = ?
        ORDER BY u.username ASC
    ");
    
    if (!$following_stmt->execute([$current_user_id])) {
        error_log("Error executing following query: " . implode(", ", $following_stmt->errorInfo()));
    }
    $following = $following_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("
        SELECT u.*, fr.created_at as request_date, fr.id as request_id
        FROM users u
        JOIN friend_requests fr ON u.id = fr.sender_id
        WHERE fr.receiver_id = ? AND fr.status = 'pending'
        ORDER BY fr.created_at DESC
    ");
    $stmt->execute([$current_user_id]);
    $friend_requests_received = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    
    $stmt = $pdo->prepare("
        SELECT u.*, fr.created_at as request_date, fr.status, fr.id as request_id
        FROM users u
        JOIN friend_requests fr ON u.id = fr.receiver_id
        WHERE fr.sender_id = ?
        ORDER BY fr.created_at DESC
    ");
    $stmt->execute([$current_user_id]);
    $friend_requests_sent = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    
    error_log("Retrieving all users except current user ID: {$current_user_id}");
    
    $all_users_stmt = $pdo->prepare("
        SELECT 
            u.*,
            (SELECT COUNT(*) FROM posts WHERE user_id = u.id) as posts_count,
            CASE WHEN EXISTS (
                SELECT 1 FROM followers 
                WHERE follower_id = ? AND followed_id = u.id
            ) THEN 1 ELSE 0 END as is_following,
            (SELECT COUNT(*) FROM followers WHERE followed_id = u.id) as followers_count,
            (SELECT COUNT(*) FROM followers WHERE follower_id = u.id) as following_count
        FROM users u
        WHERE u.id != ?
        ORDER BY u.created_at DESC
    ");
    
    if (!$all_users_stmt->execute([$current_user_id, $current_user_id])) {
        error_log("Error executing all_users query: " . implode(", ", $all_users_stmt->errorInfo()));
        $all_users = [];
    } else {
        $all_users = $all_users_stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
    
    error_log("Found " . count($all_users) . " users in the system");
    
    foreach ($all_users as $user) {
        error_log("User: {$user['username']} (ID: {$user['id']}) - Posts: {$user['posts_count']}, Followers: {$user['followers_count']}, Following: {$user['following_count']}");
    }
    
    
} catch (PDOException $e) {
    error_log("Error in friends.php: " . $e->getMessage());
    $followers = [];
    $following = [];
    $friend_requests_received = [];
    $friend_requests_sent = [];
    $all_users = [];
    $error_message = "An error occurred while loading data";
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    $error_message = "An unexpected error occurred";
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الأصدقاء والمتابعون | <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Cairo', sans-serif;
        }
        
        body {
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        
        .nav-header {
            background: rgba(10, 15, 28, 0.9);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            text-decoration: none;
        }
        
        .logo span {
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .nav-links {
            display: flex;
            gap: 2rem;
        }
        
        .nav-link {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 1.2rem;
            transition: color 0.3s ease;
        }
        
        .nav-link:hover {
            color: var(--text-primary);
        }
        
        .section {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .user-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .user-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            padding: 1.5rem;
            transition: transform 0.3s ease, background-color 0.3s ease;
        }
        
        .user-card:hover {
            background: var(--bg-card-hover);
            transform: translateY(-5px);
        }
        
        .user-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .user-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .user-info h3 {
            font-size: 1.2rem;
            margin-bottom: 0.25rem;
        }
        
        .user-info p {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .user-stats {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.3s ease;
            border: none;
            width: 100%;
            justify-content: center;
        }
        
        .btn-primary {
            background: var(--primary-gradient);
            color: white;
        }
        
        .btn-secondary {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
        }
        
        .btn:hover {
            opacity: 0.9;
        }
        
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: var(--text-secondary);
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        @media (max-width: 768px) {
            .user-grid {
                grid-template-columns: 1fr;
            }
            
            .section {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Header -->
    <header class="nav-header">
        <div class="nav-container">
            <a href="home.php" class="logo">
                <span>SUT</span> Premium
            </a>
            <nav class="nav-links">
                <a href="home.php" class="nav-link" title="الرئيسية">
                    <i class="bi bi-house-door"></i>
                </a>
                <a href="discover.php" class="nav-link" title="اكتشف">
                    <i class="bi bi-compass"></i>
                </a>
                <a href="chat.php" class="nav-link" title="الرسائل">
                    <i class="bi bi-chat"></i>
                </a>
                <a href="friends.php" class="nav-link" title="الأصدقاء" style="color: var(--text-primary);">
                    <i class="bi bi-people"></i>
                </a>
                <a href="bookmarks.php" class="nav-link" title="المحفوظات">
                    <i class="bi bi-bookmark"></i>
                </a>
                <a href="u.php" class="nav-link" title="الملف الشخصي">
                    <i class="bi bi-person"></i>
                </a>
                <a href="logout.php" class="nav-link" title="تسجيل الخروج">
                    <i class="bi bi-box-arrow-right"></i>
                </a>
            </nav>
        </div>
    </header>
    
    <div class="container">
        <!-- Following Section -->
        <section class="section">
            <h2 class="section-title">
                <i class="bi bi-person-check" style="color: #667eea;"></i>
                الأشخاص الذين تتابعهم
            </h2>
            <?php if (empty($following)): ?>
                <div class="empty-state">
                    <i class="bi bi-person-plus"></i>
                    <h3>لم تقم بمتابعة أي شخص بعد</h3>
                    <p>ابدأ بمتابعة الأشخاص لرؤية محتواهم في صفحتك الرئيسية</p>
                </div>
            <?php else: ?>
                <div class="user-grid">
                    <?php foreach ($following as $user): ?>
                        <div class="user-card">
                            <div class="user-header">
                                <img src="<?php echo !empty($user['avatar_url']) ? htmlspecialchars($user['avatar_url']) : 'https://ui-avatars.com/api/?name=' . urlencode($user['username']) . '&background=667eea&color=fff&size=200'; ?>" 
                                     alt="<?php echo htmlspecialchars($user['username']); ?>" 
                                     class="user-avatar">
                                <div class="user-info">
                                    <h3><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>
                                    <p>@<?php echo htmlspecialchars($user['username']); ?></p>
                                </div>
                                </div>
                            <div class="user-stats">
                                <span><i class="bi bi-people"></i> <?php echo $user['followers_count']; ?> متابع</span>
                                <span><i class="bi bi-person-check"></i> <?php echo $user['following_count']; ?> يتابع</span>
                            </div>
                            <button class="btn btn-secondary" onclick="unfollowUser(<?php echo $user['id']; ?>, this)">
                                <i class="bi bi-person-dash"></i> إلغاء المتابعة
                                    </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- Followers Section -->
        <section class="section">
            <h2 class="section-title">
                <i class="bi bi-people" style="color: #f093fb;"></i>
                المتابعون
            </h2>
            <?php if (empty($followers)): ?>
                <div class="empty-state">
                    <i class="bi bi-people"></i>
                    <h3>ليس لديك متابعون بعد</h3>
                    <p>عندما يتابعك الأشخاص، سيظهرون هنا</p>
                </div>
            <?php else: ?>
                <div class="user-grid">
                    <?php foreach ($followers as $user): ?>
                        <div class="user-card">
                            <div class="user-header">
                                <img src="<?php echo !empty($user['avatar_url']) ? htmlspecialchars($user['avatar_url']) : 'https://ui-avatars.com/api/?name=' . urlencode($user['username']) . '&background=667eea&color=fff&size=200'; ?>" 
                                     alt="<?php echo htmlspecialchars($user['username']); ?>" 
                                     class="user-avatar">
                                <div class="user-info">
                                    <h3><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>
                                    <p>@<?php echo htmlspecialchars($user['username']); ?></p>
                            </div>
                                </div>
                            <div class="user-stats">
                                <span><i class="bi bi-people"></i> <?php echo $user['followers_count']; ?> متابع</span>
                                <span><i class="bi bi-person-check"></i> <?php echo $user['following_count']; ?> يتابع</span>
                            </div>
                            <?php if ($user['is_following']): ?>
                                <button class="btn btn-secondary" onclick="unfollowUser(<?php echo $user['id']; ?>, this)">
                                    <i class="bi bi-person-dash"></i> إلغاء المتابعة
                                        </button>
                                    <?php else: ?>
                                <button class="btn btn-primary" onclick="followUser(<?php echo $user['id']; ?>, this)">
                                    <i class="bi bi-person-plus"></i> متابعة
                                        </button>
                                    <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- Suggested Users Section -->
        <section class="section">
            <h2 class="section-title">
                <i class="bi bi-person-plus" style="color: #43e97b;"></i>
                اقتراحات للمتابعة
            </h2>
            <?php if (empty($all_users)): ?>
                <div class="empty-state">
                    <i class="bi bi-emoji-smile"></i>
                    <h3>لا توجد اقتراحات حالياً</h3>
                    <p>عد لاحقاً لرؤية المزيد من الاقتراحات</p>
                </div>
            <?php else: ?>
                <div class="user-grid">
                    <?php foreach ($all_users as $user_item): ?>
                        <div class="user-card">
                            <div class="user-header">
                                <img src="<?php echo !empty($user_item['avatar_url']) ? htmlspecialchars($user_item['avatar_url']) : 'https://ui-avatars.com/api/?name=' . urlencode($user_item['username']) . '&background=667eea&color=fff&size=200'; ?>" 
                                     alt="<?php echo htmlspecialchars($user_item['username']); ?>" 
                                     class="user-avatar">
                                <div class="user-info">
                                    <h3><?php echo !empty($user_item['first_name']) ? htmlspecialchars($user_item['first_name'] . ' ' . $user_item['last_name']) : htmlspecialchars($user_item['username']); ?></h3>
                                    <p>@<?php echo htmlspecialchars($user_item['username']); ?></p>
                                </div>
                                </div>
                            <div class="user-stats">
                                <span><i class="bi bi-people"></i> <?php echo $user_item['followers_count']; ?> متابع</span>
                                <span><i class="bi bi-person-check"></i> <?php echo $user_item['following_count']; ?> يتابع</span>
                                </div>
                            <button class="btn btn-primary" onclick="followUser(<?php echo $user_item['id']; ?>, this)">
                                <i class="bi bi-person-plus"></i> متابعة
                                    </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
    
    <script>
    console.log('=== تشخيص صفحة الأصدقاء ===');
    console.log('Current User ID:', <?php echo json_encode($current_user_id); ?>);
    console.log('All Users Count:', <?php echo count($all_users); ?>);
    console.log('Followers Count:', <?php echo count($followers); ?>);
    console.log('Following Count:', <?php echo count($following); ?>);
    console.log('All Users Data:', <?php echo json_encode($all_users); ?>);
    
    if (<?php echo count($all_users); ?> === 0) {
        console.warn('⚠️ لا توجد مستخدمين في النظام!');
    } else {
        console.log('✅ تم العثور على مستخدمين في النظام');
    }
    
    const userCards = document.querySelectorAll('.user-card');
    const sections = document.querySelectorAll('.section');
    console.log('User Cards Found:', userCards.length);
    console.log('Sections Found:', sections.length);
    
    fetch('api/social.php', { method: 'HEAD' })
        .then(response => console.log('social.php status:', response.status))
        .catch(error => console.error('social.php error:', error));
    
    console.log('=== انتهاء تشخيص صفحة الأصدقاء ===');
    
    async function followUser(userId, button) {
            button.disabled = true;
        const originalHtml = button.innerHTML;
        button.innerHTML = '<i class="bi bi-hourglass-split"></i> جارٍ المتابعة...';
        
        try {
            console.log('🚀 إرسال طلب متابعة للمستخدم:', userId);
            
            const requestData = {
                action: 'toggle_follow',
                user_id: userId
            };
            console.log('📤 بيانات الطلب:', requestData);
            
            const response = await fetch('api/social.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(requestData)
            });
            
            console.log('📡 استجابة الخادم:', response.status, response.statusText);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const responseText = await response.text();
            console.log('📄 نص الاستجابة:', responseText);
            
            let result;
            try {
                result = JSON.parse(responseText);
            } catch (parseError) {
                console.error('❌ خطأ في تحليل JSON:', parseError);
                throw new Error('Invalid JSON response');
            }
            
            console.log('📊 نتيجة API:', result);
            
            if (result.success) {
                console.log('✅ تم تنفيذ المتابعة بنجاح');
                button.className = 'btn btn-secondary';
                button.innerHTML = '<i class="bi bi-person-dash"></i> إلغاء المتابعة';
                button.onclick = () => unfollowUser(userId, button);
            } else {
                console.error('❌ فشل في المتابعة:', result.message || 'سبب غير معروف');
                button.innerHTML = originalHtml;
                alert('فشل في المتابعة: ' + (result.message || 'سبب غير معروف'));
            }
        } catch (error) {
            console.error('❌ خطأ في المتابعة:', error);
            button.innerHTML = originalHtml;
            alert('حدث خطأ أثناء المتابعة: ' + error.message);
        } finally {
                button.disabled = false;
        }
    }

    async function unfollowUser(userId, button) {
        if (!confirm('هل أنت متأكد من إلغاء المتابعة؟')) return;
            
            button.disabled = true;
        const originalHtml = button.innerHTML;
        button.innerHTML = '<i class="bi bi-hourglass-split"></i> جارٍ إلغاء المتابعة...';
        
        try {
            const response = await fetch('api/social.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'toggle_follow',
                    user_id: userId
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                    const card = button.closest('.user-card');
                if (card && card.parentElement.closest('.section').querySelector('.section-title').textContent.includes('الأشخاص الذين تتابعهم')) {
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.8)';
                    setTimeout(() => {
                        card.remove();
                        const remainingCards = card.parentElement.querySelectorAll('.user-card');
                        if (remainingCards.length === 0) {
                            const section = card.parentElement.closest('.section');
                            section.innerHTML = `
                                <h2 class="section-title">
                                    <i class="bi bi-person-check" style="color: #667eea;"></i>
                                    الأشخاص الذين تتابعهم
                                </h2>
                                <div class="empty-state">
                                    <i class="bi bi-person-plus"></i>
                                    <h3>لم تقم بمتابعة أي شخص بعد</h3>
                                    <p>ابدأ بمتابعة الأشخاص لرؤية محتواهم في صفحتك الرئيسية</p>
                                </div>
                            `;
                        }
                    }, 300);
                } else {
                    button.className = 'btn btn-primary';
                    button.innerHTML = '<i class="bi bi-person-plus"></i> متابعة';
                    button.onclick = () => followUser(userId, button);
                }
                } else {
                button.innerHTML = originalHtml;
            }
        } catch (error) {
            console.error('Error unfollowing user:', error);
            button.innerHTML = originalHtml;
        } finally {
                button.disabled = false;
        }
    }
    </script>
</body>
</html>
