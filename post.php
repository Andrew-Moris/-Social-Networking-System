<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$current_user_id = $_SESSION['user_id'];

$post_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$current_user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        header('Location: logout.php');
        exit;
    }
    
    $post = null;
    $comments = [];
    $post_owner = null;
    
    if ($post_id) {
        $stmt = $pdo->prepare("
            SELECT p.*, u.username, u.first_name, u.last_name, u.avatar_url 
            FROM posts p
            JOIN users u ON p.user_id = u.id
            WHERE p.id = ?
        ");
        $stmt->execute([$post_id]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$post) {
            header('Location: index.php');
            exit;
        }
        
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS comments (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                post_id INT UNSIGNED NOT NULL,
                user_id INT UNSIGNED NOT NULL,
                content TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX (post_id),
                INDEX (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        $stmt = $pdo->prepare("
            SELECT c.*, u.username, u.first_name, u.last_name, u.avatar_url 
            FROM comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.post_id = ?
            ORDER BY c.created_at ASC
        ");
        $stmt->execute([$post_id]);
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch (PDOException $e) {
    error_log("خطأ في قاعدة البيانات: " . $e->getMessage());
    $error_message = "حدث خطأ أثناء تحميل البيانات";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_content']) && !$post_id) {
    $post_content = trim($_POST['post_content']);
    $image_url = null;
    
    if (empty($post_content)) {
        $post_error = "يرجى كتابة محتوى المنشور";
    } else {
        try {
            if (isset($_FILES['post_image']) && $_FILES['post_image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'uploads/posts/';
                
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_name = time() . '_' . basename($_FILES['post_image']['name']);
                $target_file = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['post_image']['tmp_name'], $target_file)) {
                    $image_url = $target_file;
                }
            }
            
            $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, image_url, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$current_user_id, $post_content, $image_url]);
            
            $new_post_id = $pdo->lastInsertId();
            
            header("Location: post.php?id=$new_post_id");
            exit;
        } catch (PDOException $e) {
            error_log("خطأ في إنشاء منشور: " . $e->getMessage());
            $post_error = "حدث خطأ أثناء إنشاء المنشور";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_content']) && $post_id) {
    $comment_content = trim($_POST['comment_content']);
    
    if (empty($comment_content)) {
        $comment_error = "يرجى كتابة محتوى التعليق";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$post_id, $current_user_id, $comment_content]);
            
            header("Location: post.php?id=$post_id");
            exit;
        } catch (PDOException $e) {
            error_log("خطأ في إضافة تعليق: " . $e->getMessage());
            $comment_error = "حدث خطأ أثناء إضافة التعليق";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SUT Premium | <?php echo $post_id ? 'عرض منشور' : 'منشور جديد'; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Cairo', sans-serif;
        }
        
        body {
            background-color: #f0f2f5;
        }
        
        .navbar {
            background: linear-gradient(135deg, #00bfff 0%, #59e6ff 100%);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .navbar .logo {
            font-weight: bold;
            font-size: 24px;
            color: white;
            text-decoration: none;
        }
        
        .navbar .nav-links {
            display: flex;
            align-items: center;
        }
        
        .navbar .nav-links a {
            color: white;
            margin-right: 20px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .navbar .nav-links a:hover {
            color: #f0f2f5;
        }
        
        .navbar .user-menu {
            display: flex;
            align-items: center;
        }
        
        .navbar .user-menu img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-left: 10px;
            object-fit: cover;
            border: 2px solid white;
        }
        
        .main-content {
            max-width: 800px;
            margin: 20px auto;
            padding: 0 20px;
        }
        
        .page-title {
            font-size: 24px;
            margin-bottom: 20px;
            color: #333;
        }
        
        .new-post {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .new-post textarea {
            width: 100%;
            border: 1px solid #ddd;
            resize: none;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
            min-height: 150px;
            font-size: 16px;
        }
        
        .new-post textarea:focus {
            outline: none;
            border-color: #00bfff;
        }
        
        .post-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .post-attachments {
            display: flex;
            gap: 15px;
        }
        
        .attachment-btn {
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        
        .attachment-btn:hover {
            background-color: #f0f2f5;
        }
        
        .post-btn {
            background: linear-gradient(135deg, #00bfff 0%, #59e6ff 100%);
            color: white;
            border: none;
            border-radius: 5px;
            padding: 10px 25px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .post-btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        
        .post {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .post-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .post-header img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-left: 15px;
            object-fit: cover;
        }
        
        .post-header-info h3 {
            margin: 0;
            font-size: 16px;
        }
        
        .post-header-info h3 a {
            color: #333;
            text-decoration: none;
        }
        
        .post-header-info h3 a:hover {
            color: #00bfff;
        }
        
        .post-date {
            color: #666;
            font-size: 12px;
        }
        
        .post-content {
            margin-bottom: 15px;
            font-size: 16px;
            line-height: 1.5;
        }
        
        .post-image {
            width: 100%;
            border-radius: 10px;
            margin-bottom: 15px;
        }
        
        .post-footer {
            display: flex;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }
        
        .post-action {
            flex: 1;
            text-align: center;
            color: #666;
            padding: 5px 0;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        
        .post-action:hover {
            background-color: #f0f2f5;
        }
        
        .comments-section {
            margin-top: 20px;
        }
        
        .comments-title {
            font-size: 18px;
            margin-bottom: 15px;
            color: #333;
        }
        
        .comment {
            background-color: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .comment-header {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .comment-header img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-left: 10px;
            object-fit: cover;
        }
        
        .comment-header-info h4 {
            margin: 0;
            font-size: 14px;
        }
        
        .comment-header-info h4 a {
            color: #333;
            text-decoration: none;
        }
        
        .comment-header-info h4 a:hover {
            color: #00bfff;
        }
        
        .comment-date {
            color: #666;
            font-size: 12px;
        }
        
        .comment-content {
            font-size: 14px;
            line-height: 1.5;
        }
        
        .add-comment {
            display: flex;
            margin-top: 20px;
        }
        
        .add-comment img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-left: 10px;
            object-fit: cover;
        }
        
        .add-comment form {
            flex: 1;
            display: flex;
        }
        
        .add-comment input {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 20px;
            font-size: 14px;
            margin-left: 10px;
        }
        
        .add-comment input:focus {
            outline: none;
            border-color: #00bfff;
        }
        
        .add-comment button {
            background-color: #00bfff;
            color: white;
            border: none;
            border-radius: 20px;
            padding: 10px 20px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .add-comment button:hover {
            background-color: #009fd9;
        }
        
        /* تصميم متجاوب */
        @media (max-width: 768px) {
            .navbar .nav-links {
                display: none;
            }
            
            .post-actions {
                flex-direction: column;
                gap: 10px;
            }
            
            .post-attachments {
                width: 100%;
                justify-content: space-around;
            }
            
            .post-btn {
                width: 100%;
            }
            
            .add-comment {
                flex-direction: column;
            }
            
            .add-comment img {
                margin-bottom: 10px;
                align-self: center;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="index.php" class="logo">SUT Premium</a>
        <div class="nav-links">
            <a href="index.php"><i class="bi bi-house"></i> الرئيسية</a>
            <a href="friends.php"><i class="bi bi-people"></i> الأصدقاء</a>
            <a href="chat.php"><i class="bi bi-chat"></i> الرسائل</a>
            <a href="notifications.php"><i class="bi bi-bell"></i> الإشعارات</a>
        </div>
        <div class="user-menu">
            <a href="profile.php" style="color: white;"><i class="bi bi-person"></i> الملف الشخصي</a>
            <a href="logout.php" style="color: white;"><i class="bi bi-box-arrow-right"></i> تسجيل الخروج</a>
            <img src="<?php echo !empty($user['avatar_url']) ? htmlspecialchars($user['avatar_url']) : 'https://i.pravatar.cc/150?img=' . ($current_user_id % 70); ?>" alt="صورة المستخدم">
        </div>
    </nav>
    
    <div class="main-content">
        <?php if ($post_id): ?>
            <h1 class="page-title">عرض منشور</h1>
            
            <div class="post">
                <div class="post-header">
                    <img src="<?php echo !empty($post['avatar_url']) ? htmlspecialchars($post['avatar_url']) : 'https://i.pravatar.cc/150?img=' . ($post['user_id'] % 70); ?>" alt="صورة المستخدم">
                    <div class="post-header-info">
                        <h3>
                            <a href="u.php?username=<?php echo htmlspecialchars($post['username']); ?>">
                                <?php echo !empty($post['first_name']) ? htmlspecialchars($post['first_name'] . ' ' . $post['last_name']) : htmlspecialchars($post['username']); ?>
                            </a>
                        </h3>
                        <span class="post-date">
                            <?php
                            $timestamp = strtotime($post['created_at']);
                            $now = time();
                            $diff = $now - $timestamp;
                            
                            if ($diff < 60) {
                                echo 'منذ ' . $diff . ' ثانية';
                            } elseif ($diff < 3600) {
                                echo 'منذ ' . floor($diff / 60) . ' دقيقة';
                            } elseif ($diff < 86400) {
                                echo 'منذ ' . floor($diff / 3600) . ' ساعة';
                            } elseif ($diff < 604800) {
                                echo 'منذ ' . floor($diff / 86400) . ' يوم';
                            } else {
                                echo date('Y-m-d', $timestamp);
                            }
                            ?>
                        </span>
                    </div>
                </div>
                <div class="post-content">
                    <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                </div>
                <?php if (!empty($post['image_url'])): ?>
                    <img src="<?php echo htmlspecialchars($post['image_url']); ?>" alt="صورة المنشور" class="post-image">
                <?php endif; ?>
                <div class="post-footer">
                    <div class="post-action">
                        <i class="bi bi-heart"></i> إعجاب
                    </div>
                    <div class="post-action">
                        <i class="bi bi-chat"></i> تعليق
                    </div>
                    <div class="post-action">
                        <i class="bi bi-share"></i> مشاركة
                    </div>
                </div>
            </div>
            
            <div class="comments-section">
                <h2 class="comments-title">التعليقات (<?php echo count($comments); ?>)</h2>
                
                <?php if (empty($comments)): ?>
                    <div style="text-align: center; padding: 20px; color: #666; background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);">
                        لا توجد تعليقات حتى الآن. كن أول من يعلق!
                    </div>
                <?php else: ?>
                    <?php foreach ($comments as $comment): ?>
                        <div class="comment">
                            <div class="comment-header">
                                <img src="<?php echo !empty($comment['avatar_url']) ? htmlspecialchars($comment['avatar_url']) : 'https://i.pravatar.cc/150?img=' . ($comment['user_id'] % 70); ?>" alt="صورة المستخدم">
                                <div class="comment-header-info">
                                    <h4>
                                        <a href="u.php?username=<?php echo htmlspecialchars($comment['username']); ?>">
                                            <?php echo !empty($comment['first_name']) ? htmlspecialchars($comment['first_name'] . ' ' . $comment['last_name']) : htmlspecialchars($comment['username']); ?>
                                        </a>
                                    </h4>
                                    <span class="comment-date">
                                        <?php
                                        $timestamp = strtotime($comment['created_at']);
                                        $now = time();
                                        $diff = $now - $timestamp;
                                        
                                        if ($diff < 60) {
                                            echo 'منذ ' . $diff . ' ثانية';
                                        } elseif ($diff < 3600) {
                                            echo 'منذ ' . floor($diff / 60) . ' دقيقة';
                                        } elseif ($diff < 86400) {
                                            echo 'منذ ' . floor($diff / 3600) . ' ساعة';
                                        } elseif ($diff < 604800) {
                                            echo 'منذ ' . floor($diff / 86400) . ' يوم';
                                        } else {
                                            echo date('Y-m-d', $timestamp);
                                        }
                                        ?>
                                    </span>
                                </div>
                            </div>
                            <div class="comment-content">
                                <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <div class="add-comment">
                    <img src="<?php echo !empty($user['avatar_url']) ? htmlspecialchars($user['avatar_url']) : 'https://i.pravatar.cc/150?img=' . ($current_user_id % 70); ?>" alt="صورة المستخدم">
                    <form action="" method="POST">
                        <input type="text" name="comment_content" placeholder="اكتب تعليقاً..." required>
                        <button type="submit">تعليق</button>
                    </form>
                </div>
                <?php if (isset($comment_error)): ?>
                    <p style="color: red; margin-top: 10px;"><?php echo $comment_error; ?></p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <h1 class="page-title">منشور جديد</h1>
            
            <div class="new-post">
                <form action="" method="POST" enctype="multipart/form-data">
                    <textarea name="post_content" placeholder="ماذا يدور في ذهنك؟"></textarea>
                    <div class="post-actions">
                        <div class="post-attachments">
                            <label for="post-image" class="attachment-btn">
                                <i class="bi bi-image"></i>
                                <span>صورة</span>
                                <input type="file" id="post-image" name="post_image" style="display: none;" accept="image/*">
                            </label>
                            <button type="button" class="attachment-btn">
                                <i class="bi bi-camera-video"></i>
                                <span>فيديو</span>
                            </button>
                            <button type="button" class="attachment-btn">
                                <i class="bi bi-emoji-smile"></i>
                                <span>مشاعر</span>
                            </button>
                        </div>
                        <button type="submit" class="post-btn">نشر</button>
                    </div>
                    <?php if (isset($post_error)): ?>
                        <p style="color: red; margin-top: 10px;"><?php echo $post_error; ?></p>
                    <?php endif; ?>
                </form>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        document.getElementById('post-image')?.addEventListener('change', function() {
            const fileName = this.files[0]?.name;
            if (fileName) {
                const fileLabel = this.previousElementSibling;
                fileLabel.textContent = fileName.length > 15 ? fileName.substring(0, 15) + '...' : fileName;
            }
        });
    </script>
</body>
</html>
