<?php

require_once 'config.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'يجب تسجيل الدخول أولاً'
    ]);
    exit;
}

if (empty($_POST['content'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'يرجى كتابة محتوى للمنشور'
    ]);
    exit;
}

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
    
    $tables_stmt = $pdo->query("SHOW TABLES LIKE 'posts'");
    $posts_table_exists = $tables_stmt->rowCount() > 0;
    
    if (!$posts_table_exists) {
        $pdo->exec("
            CREATE TABLE posts (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                content TEXT NOT NULL,
                image_url VARCHAR(255) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                likes_count INT UNSIGNED DEFAULT 0,
                comments_count INT UNSIGNED DEFAULT 0,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    } else {
        $columns_stmt = $pdo->query("SHOW COLUMNS FROM posts LIKE 'image_url'");
        if ($columns_stmt->rowCount() === 0) {
            $pdo->exec("ALTER TABLE posts ADD COLUMN image_url VARCHAR(255) DEFAULT NULL AFTER content");
        }
    }
    
    $image_url = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/posts/';
        
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['image']['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            throw new Exception('نوع الملف غير مدعوم. يرجى رفع صورة بصيغة JPG أو PNG أو GIF');
        }
        
        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid('post_') . '.' . $file_extension;
        $file_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
            $image_url = $file_path;
        } else {
            throw new Exception('فشل في رفع الصورة');
        }
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO posts (user_id, content, image_url)
        VALUES (:user_id, :content, :image_url)
    ");
    
    $stmt->execute([
        'user_id' => $_SESSION['user_id'],
        'content' => $_POST['content'],
        'image_url' => $image_url
    ]);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'تم نشر المنشور بنجاح'
    ]);
    
} catch (Exception $e) {
    log_error("Error in post_create.php: " . $e->getMessage());
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ أثناء نشر المنشور: ' . $e->getMessage()
    ]);
}
