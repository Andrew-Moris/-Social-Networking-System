<?php
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: frontend/login.html');
    exit;
}

$user_id = $_SESSION['user_id'];
$error = null;
$posts = [];

try {
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ];
    
    $host = 'localhost';
    $dbname = 'wep_db';
    $user = 'root';
    $password = '';
    
    $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $password, $options);
    
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        first_name VARCHAR(50),
        last_name VARCHAR(50),
        avatar VARCHAR(255),
        bio TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS posts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        content TEXT NOT NULL,
        media_url VARCHAR(255),
        is_private TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS likes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        post_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(user_id, post_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        post_id INT NOT NULL,
        content TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS shares (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        post_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS bookmarks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        post_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(user_id, post_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
    )");
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $current_user = $stmt->fetch();

    $existingTables = [];
    $tables_stmt = $pdo->query("SHOW TABLES");
    while($table = $tables_stmt->fetch(PDO::FETCH_NUM)) {
        $existingTables[] = $table[0];
    }
    
    $columns_stmt = $pdo->query("SHOW COLUMNS FROM users");
    $columns = [];
    while ($column = $columns_stmt->fetch(PDO::FETCH_ASSOC)) {
        $columns[] = $column['Field'];
    }
    
    $avatar_column = null;
    foreach(['avatar', 'avatar_url', 'profile_picture', 'image', 'picture'] as $possible_column) {
        if (in_array($possible_column, $columns)) {
            $avatar_column = $possible_column;
            break;
        }
    }
    
    $query = "SELECT DISTINCT p.*, u.username";
    
    if ($avatar_column) {
        $query .= ", u.{$avatar_column} as avatar";
    } else {
        $query .= ", NULL as avatar";
    }
    
    if (in_array('first_name', $columns)) {
        $query .= ", u.first_name";
    } else {
        $query .= ", NULL as first_name";
    }
    
    if (in_array('last_name', $columns)) {
        $query .= ", u.last_name";
    } else {
        $query .= ", NULL as last_name";
    }
    
    if (in_array('likes', $existingTables)) {
        $query .= ", (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count";
        $query .= ", (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?) as user_liked";
    } else {
        $query .= ", 0 as likes_count, 0 as user_liked";
    }
    
    if (in_array('comments', $existingTables)) {
        $query .= ", (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count";
    } else {
        $query .= ", 0 as comments_count";
    }
    
    if (in_array('shares', $existingTables)) {
        $query .= ", COALESCE((SELECT COUNT(*) FROM shares WHERE post_id = p.id), 0) as shares_count";
    } else {
        $query .= ", 0 as shares_count";
    }
    
    if (in_array('bookmarks', $existingTables)) {
        $query .= ", (SELECT COUNT(*) FROM bookmarks WHERE post_id = p.id AND user_id = ?) as user_bookmarked";
    } else {
        $query .= ", 0 as user_bookmarked";
    }
    
    $query .= " FROM posts p JOIN users u ON p.user_id = u.id";
    
    $post_columns_stmt = $pdo->query("SHOW COLUMNS FROM posts");
    $post_columns = [];
    while ($column = $post_columns_stmt->fetch(PDO::FETCH_ASSOC)) {
        $post_columns[] = $column['Field'];
    }
    
    if (in_array('is_private', $post_columns)) {
        $query .= " WHERE p.is_private = 0 OR p.user_id = ?";
    } else {
        $query .= " WHERE 1=1"; 
    }
    
    $query .= " ORDER BY p.created_at DESC LIMIT 50";
    
    $params = [];
    
    if (in_array('likes', $existingTables)) {
        $params[] = $user_id; 
    }
    
    if (in_array('bookmarks', $existingTables)) {
        $params[] = $user_id; 
    }
    
    if (in_array('is_private', $post_columns)) {
        $params[] = $user_id;
    }
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $posts = $stmt->fetchAll();

    foreach ($posts as &$post) {
        $post['shares_count'] = isset($post['shares_count']) ? (int)$post['shares_count'] : 0;
        $post['comments_count'] = isset($post['comments_count']) ? (int)$post['comments_count'] : 0;
        $post['likes_count'] = isset($post['likes_count']) ? (int)$post['likes_count'] : 0;
        $post['user_liked'] = isset($post['user_liked']) ? (bool)$post['user_liked'] : false;
        $post['user_bookmarked'] = isset($post['user_bookmarked']) ? (bool)$post['user_bookmarked'] : false;
        
        $post['first_name'] = isset($post['first_name']) ? $post['first_name'] : '';
        $post['last_name'] = isset($post['last_name']) ? $post['last_name'] : '';
        $post['avatar'] = isset($post['avatar']) ? $post['avatar'] : null;
        
        $post['content'] = isset($post['content']) ? $post['content'] : '';
        $post['media_url'] = isset($post['media_url']) ? $post['media_url'] : null;
        $post['created_at'] = isset($post['created_at']) ? $post['created_at'] : date('Y-m-d H:i:s');
    }
    unset($post); 

} catch (PDOException $e) {
    $error = 'حدث خطأ في قاعدة البيانات';
    error_log("Database error in discover.php: " . $e->getMessage());
    if (isset($query)) {
        error_log("Query: " . $query);
        error_log("Params: " . print_r($params, true));
    }
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="user-id" content="<?php echo $user_id; ?>">
    <title><?php echo APP_NAME; ?> | Discover</title>

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
        
        .filter-button,
        .action-button,
        .btn-primary,
        .user-avatar,
        .user-info h3,
        .user-info .username {
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
            padding: 3rem 0 1rem 0;
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
            margin: 0 auto 2rem;
        }
        
        .filters-container {
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 3rem;
            position: relative;
            z-index: 1;
            padding: 1rem 0;
        }
        
        .filter-button {
            background: var(--bg-card);
            backdrop-filter: var(--blur-glass);
            border: 1px solid var(--border-color);
            padding: 0.75rem 1.5rem;
            border-radius: 2rem;
            color: var(--text-secondary);
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            position: relative;
            z-index: 10;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            user-select: none;
            outline: none;
        }
        
        .filter-button:hover {
            background: var(--bg-card-hover);
            color: var(--text-primary);
        }
        
        .filter-button.active {
            background: var(--primary-gradient);
            color: white;
            border-color: rgba(102, 126, 234, 0.5);
        }
        
        .filter-button.active:hover {
            background: var(--primary-gradient);
            color: white;
        }
        
        .posts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(600px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
            position: relative;
            z-index: 1;
        }
        
        .post-card {
            background: var(--bg-card);
            backdrop-filter: var(--blur-glass);
            border: 1px solid var(--border-color);
            border-radius: 1.5rem;
            overflow: hidden;
            position: relative;
            z-index: 1;
            box-shadow: var(--shadow-card);
        }
        
        .post-card:hover {
            background: var(--bg-card-hover);
        }
        
        .post-header {
            display: flex;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: 2px solid rgba(102, 126, 234, 0.3);
            cursor: pointer;
            margin-right: 1rem;
        }
        
        .user-avatar:hover {
            border-color: rgba(102, 126, 234, 0.6);
        }
        
        .user-info h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
            cursor: pointer;
        }
        
        .user-info h3:hover {
            color: #667eea;
        }
        
        .user-info .username {
            color: var(--text-secondary);
            font-size: 0.9rem;
            cursor: pointer;
        }
        
        .user-info .username:hover {
            color: #667eea;
        }
        
        .post-time {
            margin-left: auto;
            color: var(--text-muted);
            font-size: 0.85rem;
        }
        
        .post-content {
            padding: 1.5rem;
            line-height: 1.7;
            color: var(--text-primary);
        }
        
        .post-media {
            width: 100%;
            max-height: 400px;
            object-fit: cover;
            margin-top: 1rem;
            border-radius: 0.75rem;
        }
        
        .post-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--border-color);
            background: rgba(0, 0, 0, 0.1);
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
        }
        
        .action-button {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border: none;
            background: transparent;
            color: var(--text-secondary);
            cursor: pointer;
            border-radius: 0.75rem;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .action-button:hover {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
        }
        
        .action-button.liked {
            color: #ef4444;
        }
        
        .action-button.liked:hover {
            background: rgba(239, 68, 68, 0.1);
        }
        
        .action-button.bookmarked {
            color: #f59e0b;
        }
        
        .action-button.bookmarked:hover {
            background: rgba(245, 158, 11, 0.1);
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--bg-card);
            backdrop-filter: var(--blur-glass);
            border: 1px solid var(--border-color);
            border-radius: 1.5rem;
            margin: 2rem auto;
            max-width: 600px;
            position: relative;
            z-index: 1;
        }
        
        .empty-state-icon {
            font-size: 4rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
            display: block;
        }
        
        .empty-state-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        
        .empty-state-desc {
            color: var(--text-secondary);
            margin-bottom: 2rem;
        }
        
        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--primary-gradient);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            text-decoration: none;
            font-weight: 600;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary:hover {
            opacity: 0.9;
        }
        
        .error-message {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 1rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
        }
        
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite !important;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .comments-section {
            border-top: 1px solid var(--border-color);
            background: rgba(0, 0, 0, 0.2);
            position: relative;
            z-index: 1;
        }
        
        .comment-item {
            padding: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            position: relative;
            z-index: 1;
        }
        
        .comment-item:last-child {
            border-bottom: none;
        }
        
        .comment-item .action-button {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            background: transparent;
            border: 1px solid transparent;
            color: var(--text-secondary);
        }
        
        .comment-item .action-button:hover {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-primary);
        }
        
        .comment-item .action-button.liked {
            color: #e91e63;
        }
        
        .loading-state {
            background: var(--bg-card);
            backdrop-filter: var(--blur-glass);
            border: 1px solid var(--border-color);
            border-radius: 1.5rem;
            padding: 3rem 2rem;
            margin: 2rem auto;
            max-width: 600px;
            position: relative;
            z-index: 1;
        }
        
        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 3px solid rgba(102, 126, 234, 0.3);
            border-radius: 50%;
            border-top-color: #667eea;
            animation: spin 1s ease-in-out infinite !important;
        }
        
        .min-h-screen { min-height: 100vh; }
        .flex { display: flex; }
        .flex-col { flex-direction: column; }
        .flex-1 { flex: 1; }
        .flex-grow { flex-grow: 1; }
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
        .px-3 { padding-left: 0.75rem; padding-right: 0.75rem; }
        .py-2 { padding-top: 0.5rem; padding-bottom: 0.5rem; }
        .py-4 { padding-top: 1rem; padding-bottom: 1rem; }
        .py-8 { padding-top: 2rem; padding-bottom: 2rem; }
        .p-4 { padding: 1rem; }
        .mb-1 { margin-bottom: 0.25rem; }
        .mb-2 { margin-bottom: 0.5rem; }
        .mb-4 { margin-bottom: 1rem; }
        .text-center { text-align: center; }
        .text-white { color: white; }
        .text-gray-100 { color: #f3f4f6; }
        .text-gray-300 { color: #d1d5db; }
        .text-gray-400 { color: #9ca3af; }
        .text-gray-500 { color: #6b7280; }
        .text-blue-400 { color: #60a5fa; }
        .text-red-400 { color: #f87171; }
        .text-red-300 { color: #fca5a5; }
        .text-xl { font-size: 1.25rem; }
        .text-2xl { font-size: 1.5rem; }
        .text-sm { font-size: 0.875rem; }
        .text-xs { font-size: 0.75rem; }
        .font-bold { font-weight: 700; }
        .font-semibold { font-weight: 600; }
        .w-10 { width: 2.5rem; }
        .h-10 { height: 2.5rem; }
        .rounded-full { border-radius: 50%; }
        .rounded-lg { border-radius: 0.5rem; }
        .object-cover { object-fit: cover; }
        .bg-gray-800 { background-color: #1f2937; }
        .bg-gray-700 { background-color: #374151; }
        .bg-blue-600 { background-color: #2563eb; }
        .border { border-width: 1px; }
        .border-t { border-top-width: 1px; }
        .border-gray-600 { border-color: #4b5563; }
        .border-gray-700 { border-color: #374151; }
        .border-blue-500 { border-color: #3b82f6; }
        .hover\:text-white:hover { color: white; }
        .hover\:text-blue-400:hover { color: #60a5fa; }
        .hover\:text-red-400:hover { color: #f87171; }
        .hover\:text-red-300:hover { color: #fca5a5; }
        .hover\:bg-blue-700:hover { background-color: #1d4ed8; }
        .focus\:outline-none:focus { outline: none; }
        .focus\:border-blue-500:focus { border-color: #3b82f6; }
        .placeholder-gray-400::placeholder { color: #9ca3af; }
        .transition-colors { transition: color 0.2s ease, background-color 0.2s ease !important; }
        
        .loading-state .loading-spinner {
            margin: 0 auto 1rem;
        }
        
        @media (max-width: 768px) {
            .page-title {
                font-size: 2.5rem;
            }
            
            .posts-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .main-container {
                padding: 1rem;
            }
            
            .post-header {
                padding: 1rem;
            }
            
            .post-content {
                padding: 1rem;
            }
            
            .filters-container {
                gap: 0.5rem;
            }
            
            .filter-button {
                padding: 0.5rem 1rem;
                font-size: 0.85rem;
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
                <h1 class="page-title"><i class="bi bi-compass mr-3"></i>Discover</h1>
                <p class="page-subtitle">Explore trending posts and discover new content from the community</p>
                
                <div class="filters-container">
                    <button class="filter-button active" data-filter="all">
                        <i class="bi bi-grid"></i>
                        All Posts
                    </button>
                    <button class="filter-button" data-filter="recent">
                        <i class="bi bi-clock"></i>
                        Recent
                    </button>
                    <button class="filter-button" data-filter="popular">
                        <i class="bi bi-fire"></i>
                        Popular
                    </button>
                    <button class="filter-button" data-filter="media">
                        <i class="bi bi-image"></i>
                        Media
                    </button>
                </div>
            </div>

            <main class="container mx-auto px-4 flex-grow">
                <?php if ($error): ?>
                    <div class="error-message">
                        <i class="bi bi-exclamation-triangle mr-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if (empty($posts)): ?>
                    <div class="empty-state">
                        <i class="bi bi-compass empty-state-icon"></i>
                        <h3 class="empty-state-title">No Posts Available</h3>
                        <p class="empty-state-desc">There are no posts to discover at the moment. Check back later for new content!</p>
                        <a href="home.php" class="btn-primary">
                            <i class="bi bi-house-door"></i>
                            Go to Home
                        </a>
                    </div>
                <?php else: ?>
                    <div class="posts-grid">
                        <?php foreach ($posts as $post): ?>
                            <article class="post-card" id="post-<?php echo $post['id']; ?>">
                                <div class="post-header">
                                    <img src="<?php echo !empty($post['avatar']) ? htmlspecialchars($post['avatar']) : 'https://ui-avatars.com/api/?name=' . urlencode($post['username']) . '&background=667eea&color=fff&size=200'; ?>" 
                                         alt="User Avatar" 
                                         class="user-avatar"
                                         onclick="openProfile('<?php echo $post['username']; ?>')">
                                    <div class="user-info">
                                        <h3 onclick="openProfile('<?php echo $post['username']; ?>')">
                                            <?php echo !empty($post['first_name']) ? htmlspecialchars($post['first_name'] . ' ' . $post['last_name']) : htmlspecialchars($post['username']); ?>
                                        </h3>
                                        <p class="username" onclick="openProfile('<?php echo $post['username']; ?>')">@<?php echo htmlspecialchars($post['username']); ?></p>
                                    </div>
                                    <div class="post-time">
                                        <i class="bi bi-clock mr-1"></i>
                                        <?php echo date('M d, Y', strtotime($post['created_at'])); ?>
                                    </div>
                                </div>

                                <div class="post-content">
                                    <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                                    
                                    <?php if (!empty($post['image_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($post['image_url']); ?>" 
                                             alt="Post Image" 
                                             class="post-media">
                                    <?php elseif (!empty($post['media_url'])): ?>
                                        <?php if ($post['media_type'] === 'image'): ?>
                                            <img src="<?php echo htmlspecialchars($post['media_url']); ?>" 
                                                 alt="Post Image" 
                                                 class="post-media">
                                        <?php elseif ($post['media_type'] === 'video'): ?>
                                            <video src="<?php echo htmlspecialchars($post['media_url']); ?>" 
                                                   controls 
                                                   class="post-media">
                                                Your browser does not support video playback
                                            </video>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>

                                <div class="post-actions">
                                    <div class="action-buttons">
                                        <button class="action-button <?php echo $post['user_liked'] ? 'liked' : ''; ?>" 
                                                onclick="toggleLike(<?php echo $post['id']; ?>, this)">
                                            <i class="bi bi-heart<?php echo $post['user_liked'] ? '-fill' : ''; ?>"></i>
                                            <span class="likes-count"><?php echo $post['likes_count']; ?></span>
                                        </button>

                                        <button class="action-button" data-post-id="<?php echo $post['id']; ?>" onclick="toggleComments(<?php echo $post['id']; ?>)">
                                            <i class="bi bi-chat-dots"></i>
                                            <span><?php echo $post['comments_count']; ?></span>
                                        </button>

                                        <button class="action-button" onclick="sharePost(<?php echo $post['id']; ?>)">
                                            <i class="bi bi-share"></i>
                                            <span><?php echo $post['shares_count']; ?></span>
                                        </button>

                                        <button class="action-button <?php echo $post['user_bookmarked'] ? 'bookmarked' : ''; ?>" 
                                                onclick="toggleBookmark(<?php echo $post['id']; ?>, this)">
                                            <i class="bi bi-bookmark<?php echo $post['user_bookmarked'] ? '-fill' : ''; ?>"></i>
                                            <span><?php echo $post['user_bookmarked'] ? 'Saved' : 'Save'; ?></span>
                                        </button>
                                    </div>
                                </div>

                                <div id="comments-<?php echo $post['id']; ?>" class="comments-section" style="display: none;">
                                    <div class="comment-form">
                                        <div class="flex gap-3 p-4 border-t border-gray-700">
                                            <img src="<?php echo !empty($current_user['avatar_url']) ? htmlspecialchars($current_user['avatar_url']) : 'https://ui-avatars.com/api/?name=' . urlencode($current_user['username']) . '&background=667eea&color=fff&size=80'; ?>" 
                                                 alt="Your Avatar" class="w-10 h-10 rounded-full object-cover">
                                            <div class="flex-1">
                                                <form onsubmit="submitComment(event, <?php echo $post['id']; ?>)" class="flex gap-2">
                                                    <input type="text" 
                                                           placeholder="Write a comment..." 
                                                           class="flex-1 bg-gray-800 border border-gray-600 rounded-lg px-3 py-2 text-white placeholder-gray-400 focus:outline-none focus:border-blue-500"
                                                           required>
                                                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                                                        <i class="bi bi-send"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="comments-container-<?php echo $post['id']; ?>" class="comments-container p-4">
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script>
        function openProfile(username) {
            window.open(`u.php?username=${username}`, '_blank');
        }
        
        function toggleLike(postId, element) {
            console.log('🔥 toggleLike called:', { postId, element });
            
            const icon = element.querySelector('i');
            const countSpan = element.querySelector('.likes-count');
            const isLiked = element.classList.contains('liked');
            
            console.log('Current state:', { isLiked, currentCount: countSpan.textContent });
            
            element.classList.toggle('liked');
            const newCount = isLiked ? (parseInt(countSpan.textContent) - 1) : (parseInt(countSpan.textContent) + 1);
            countSpan.textContent = newCount;
            icon.className = isLiked ? 'bi bi-heart' : 'bi bi-heart-fill';
            
            console.log('UI updated temporarily:', { newIsLiked: !isLiked, newCount });
            
            fetch('api/posts.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'action=toggle_like&post_id=' + postId + '&type=post'
            })
            .then(response => {
                console.log('Like API response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('✅ Like API Response:', data);
                
                if (data.success) {
                    const finalCount = data.likes_count || 0;
                    const finalIsLiked = data.action === 'like';
                    
                    console.log('Final server state:', { finalCount, finalIsLiked });
                    
                    countSpan.textContent = finalCount;
                    element.classList.toggle('liked', finalIsLiked);
                    icon.className = finalIsLiked ? 'bi bi-heart-fill' : 'bi bi-heart';
                } else {
                    console.error('❌ Like API returned error:', data.message);
                    element.classList.toggle('liked');
                    countSpan.textContent = isLiked ? (parseInt(countSpan.textContent) + 1) : (parseInt(countSpan.textContent) - 1);
                    icon.className = isLiked ? 'bi bi-heart-fill' : 'bi bi-heart';
                    alert('Error: ' + (data.message || 'Failed to like post'));
                }
            })
            .catch(error => {
                console.error('💥 Like network error:', error);
                element.classList.toggle('liked');
                countSpan.textContent = isLiked ? (parseInt(countSpan.textContent) + 1) : (parseInt(countSpan.textContent) - 1);
                icon.className = isLiked ? 'bi bi-heart-fill' : 'bi bi-heart';
                alert('Network error: ' + error.message);
            });
        }
        
        function toggleBookmark(postId, element) {
            console.log('🔖 toggleBookmark called:', { postId, element });
            
            const icon = element.querySelector('i');
            const textSpan = element.querySelector('span');
            const isBookmarked = element.classList.contains('bookmarked');
            
            console.log('Current bookmark state:', { isBookmarked });
            
            element.classList.toggle('bookmarked');
            icon.className = isBookmarked ? 'bi bi-bookmark' : 'bi bi-bookmark-fill';
            textSpan.textContent = isBookmarked ? 'Save' : 'Saved';
            
            console.log('UI updated temporarily:', { newIsBookmarked: !isBookmarked });
            
            fetch('api/posts.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'action=toggle_bookmark&post_id=' + postId
            })
            .then(response => {
                console.log('Bookmark API response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('✅ Bookmark API Response:', data);
                
                if (data.success) {
                    const finalIsBookmarked = data.action === 'bookmark';
                    
                    console.log('Final bookmark state:', { finalIsBookmarked });
                    
                    element.classList.toggle('bookmarked', finalIsBookmarked);
                    icon.className = finalIsBookmarked ? 'bi bi-bookmark-fill' : 'bi bi-bookmark';
                    textSpan.textContent = finalIsBookmarked ? 'Saved' : 'Save';
                } else {
                    console.error('❌ Bookmark API returned error:', data.message);
                    element.classList.toggle('bookmarked');
                    icon.className = isBookmarked ? 'bi bi-bookmark-fill' : 'bi bi-bookmark';
                    textSpan.textContent = isBookmarked ? 'Saved' : 'Save';
                    alert('Error: ' + (data.message || 'Failed to bookmark post'));
                }
            })
            .catch(error => {
                console.error('💥 Bookmark network error:', error);
                element.classList.toggle('bookmarked');
                icon.className = isBookmarked ? 'bi bi-bookmark-fill' : 'bi bi-bookmark';
                textSpan.textContent = isBookmarked ? 'Saved' : 'Save';
                alert('Network error: ' + error.message);
            });
        }
        
   
        async function toggleComments(postId) {
            const commentsSection = document.getElementById(`comments-${postId}`);
            if (!commentsSection) return;
            
            if (commentsSection.style.display === 'none') {
                commentsSection.style.display = 'block';
                await loadComments(postId);
            } else {
                commentsSection.style.display = 'none';
            }
        }
        
       
        async function loadComments(postId) {
            const container = document.getElementById(`comments-container-${postId}`);
            if (!container) return;
            
            container.innerHTML = `
                <div class="text-center py-4">
                    <div class="loading-spinner mx-auto mb-2"></div>
                    <p class="text-gray-400">جاري تحميل التعليقات...</p>
                </div>
            `;
            
            try {
                const response = await fetch(`api/comments.php?action=get_comments&post_id=${postId}`);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                console.log('💬 Comments data:', result);
                
                
                if (result.success && result.comments && result.comments.length > 0) {
                    renderComments(container, result.comments, postId);
                } else {
                    container.innerHTML = '<div class="text-center py-4 text-gray-400">لا توجد تعليقات حتى الآن</div>';
                }
            } catch (error) {
                console.error('❌ Error loading comments:', error);
                container.innerHTML = `
                    <div class="text-center py-4 text-red-400">
                        <i class="bi bi-exclamation-triangle mb-2 text-xl"></i>
                        <p>حدث خطأ أثناء تحميل التعليقات</p>
                    </div>
                `;
            }
        }
        
    
        function renderComments(container, comments, postId) {
            const currentUserId = parseInt(document.querySelector('meta[name="user-id"]')?.content || '0');
            
            container.innerHTML = comments.map(comment => {
                const avatar = comment.avatar || comment.avatar_url || comment.profile_picture || 
                               `https://ui-avatars.com/api/?name=${encodeURIComponent(comment.username)}&background=667eea&color=fff&size=80`;
                
                const isOwner = comment.user_id === currentUserId;
                
                return `
                    <div class="comment-item" id="comment-${comment.id}">
                        <div class="flex gap-3">
                            <img src="${avatar}" alt="${comment.username}" class="w-10 h-10 rounded-full object-cover">
                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-semibold text-white">${comment.first_name || ''} ${comment.last_name || ''}</span>
                                        <span class="text-gray-400 text-sm">@${comment.username}</span>
                                        <span class="text-gray-500 text-xs">${formatDate(comment.created_at)}</span>
                                    </div>
                                    ${isOwner ? `
                                        <button class="action-button text-xs text-red-400 hover:text-red-300" 
                                                onclick="deleteComment(${comment.id}, ${postId})" 
                                                title="حذف التعليق">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    ` : ''}
                                </div>
                                <p class="text-white mb-2">${comment.content}</p>
                                <div class="flex gap-2">
                                    <button class="action-button text-xs ${comment.user_liked ? 'liked' : ''}" 
                                            onclick="toggleCommentLike(${comment.id}, this)">
                                        <i class="bi bi-heart${comment.user_liked ? '-fill' : ''}"></i>
                                        <span>${comment.like_count || 0}</span>
                                    </button>
                                    <button class="action-button text-xs" 
                                            onclick="replyToComment(${postId}, '${comment.username}')">
                                        <i class="bi bi-reply"></i>
                                        رد
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }
    
        async function submitComment(event, postId) {
            event.preventDefault();
            
            const form = event.target;
            const input = form.querySelector('input[type="text"]');
            const submitBtn = form.querySelector('button[type="submit"]');
            const content = input.value.trim();
            
            if (!content) return;
            
            input.disabled = true;
            submitBtn.disabled = true;
            
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<div class="loading-spinner"></div>';
            
            try {
                const success = await addComment(postId, content);
                
                if (success) {
                    input.value = '';
                    console.log('✅ Comment added successfully!');
                } else {
                    console.error('❌ Failed to add comment');
                    alert('فشل في إضافة التعليق. يرجى المحاولة مرة أخرى.');
                }
            } catch (error) {
                console.error('❌ Error submitting comment:', error);
                alert('حدث خطأ أثناء إرسال التعليق. يرجى المحاولة مرة أخرى.');
            } finally {
                input.disabled = false;
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
                input.focus();
            }
        }
        
     
        async function addComment(postId, content) {
            try {
                const response = await fetch('api/comments.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=add_comment&post_id=${postId}&content=${encodeURIComponent(content)}`
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                console.log('💬 Add comment response:', result);
                
                if (result.success) {
                    await loadComments(postId);
                    
                    updateCommentsCount(postId);
                    return true;
                } else {
                    console.error('❌ Comment not added:', result.message);
                    return false;
                }
            } catch (error) {
                console.error('❌ Error adding comment:', error);
                return false;
            }
        }
        
     
        async function updateCommentsCount(postId) {
            try {
                const response = await fetch(`api/comments.php?action=get_post_stats&post_id=${postId}`);
                if (!response.ok) return;
                
                const result = await response.json();
                console.log('📊 Post stats:', result);
                
                if (result.success) {
                    const commentBtn = document.querySelector(`[data-post-id="${postId}"] .comment-count`);
                    if (commentBtn) {
                        commentBtn.textContent = result.comment_count || '0';
                    }
                }
            } catch (error) {
                console.error('❌ Error updating comments count:', error);
            }
        }
        
       
        async function deleteComment(commentId, postId) {
            if (!confirm('هل أنت متأكد من رغبتك في حذف هذا التعليق؟')) return;
            
            try {
                const response = await fetch('api/comments.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=delete_comment&comment_id=${commentId}`
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                console.log('🗑️ Delete comment response:', result);
                
                if (result.success) {
                    const commentElement = document.getElementById(`comment-${commentId}`);
                    if (commentElement) {
                        commentElement.style.transition = 'all 0.3s ease';
                        commentElement.style.opacity = '0';
                        commentElement.style.transform = 'translateX(-100%)';
                        
                        setTimeout(() => {
                            commentElement.remove();
                            
                            updateCommentsCount(postId);
                            
                            const commentsContainer = document.getElementById(`comments-container-${postId}`);
                            if (commentsContainer && (!commentsContainer.children.length || 
                                (commentsContainer.children.length === 1 && 
                                 commentsContainer.children[0].classList.contains('text-center')))) {
                                commentsContainer.innerHTML = '<div class="text-center py-4 text-gray-400">لا توجد تعليقات حتى الآن</div>';
                            }
                        }, 300);
                    }
                } else {
                    console.error('❌ Comment not deleted:', result.message);
                    alert('فشل في حذف التعليق: ' + (result.message || 'خطأ غير معروف'));
                }
            } catch (error) {
                console.error('❌ Error deleting comment:', error);
                alert('حدث خطأ أثناء حذف التعليق');
            }
        }
        
        
        async function toggleCommentLike(commentId, element) {
            const icon = element.querySelector('i');
            const countSpan = element.querySelector('span');
            const isLiked = element.classList.contains('liked');
            
            element.classList.toggle('liked');
            const newCount = isLiked ? Math.max(0, parseInt(countSpan.textContent || '0') - 1) : (parseInt(countSpan.textContent || '0') + 1);
            countSpan.textContent = newCount;
            icon.className = isLiked ? 'bi bi-heart' : 'bi bi-heart-fill';
            
            try {
                const response = await fetch('api/comments.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=toggle_comment_like&comment_id=${commentId}`
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                console.log('❤️ Comment like response:', result);
                
                if (result.success) {
                    const finalCount = result.like_count || 0;
                    const finalIsLiked = result.action === 'like';
                    
                    countSpan.textContent = finalCount;
                    element.classList.toggle('liked', finalIsLiked);
                    icon.className = finalIsLiked ? 'bi bi-heart-fill' : 'bi bi-heart';
                } else {
                    element.classList.toggle('liked');
                    countSpan.textContent = isLiked ? (parseInt(countSpan.textContent || '0') + 1) : Math.max(0, parseInt(countSpan.textContent || '0') - 1);
                    icon.className = isLiked ? 'bi bi-heart-fill' : 'bi bi-heart';
                }
            } catch (error) {
                console.error('❌ Error toggling comment like:', error);
                element.classList.toggle('liked');
                countSpan.textContent = isLiked ? (parseInt(countSpan.textContent || '0') + 1) : Math.max(0, parseInt(countSpan.textContent || '0') - 1);
                icon.className = isLiked ? 'bi bi-heart-fill' : 'bi bi-heart';
            }
        }
        
     
        function replyToComment(postId, username) {
            const commentsSection = document.getElementById(`comments-${postId}`);
            if (!commentsSection) return;
            
            const input = commentsSection.querySelector('input[type="text"]');
            if (input) {
                input.value = `@${username} `;
                input.focus();
                
                input.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
        
        function formatDate(dateString) {
            if (!dateString) return 'Unknown';
            
            try {
                const date = new Date(dateString);
                if (isNaN(date.getTime())) return 'Unknown';
                
                const now = new Date();
                const diffInSeconds = Math.floor((now - date) / 1000);
                
                if (diffInSeconds < 60) {
                    return 'now';
                } else if (diffInSeconds < 3600) {
                    const minutes = Math.floor(diffInSeconds / 60);
                    return `${minutes}m`;
                } else if (diffInSeconds < 86400) {
                    const hours = Math.floor(diffInSeconds / 3600);
                    return `${hours}h`;
                } else {
                    const days = Math.floor(diffInSeconds / 86400);
                    return `${days}d`;
                }
            } catch (error) {
                console.error('Error formatting date:', error);
                return 'Unknown';
            }
        }
        
        function sharePost(postId) {
            const shareUrl = `${window.location.origin}/WEP/post.php?id=${postId}`;
            
            if (navigator.share) {
                navigator.share({
                    title: 'Check out this post!',
                    url: shareUrl
                });
            } else if (navigator.clipboard) {
                navigator.clipboard.writeText(shareUrl).then(() => {
                    alert('Post link copied to clipboard!');
                });
            } else {
                prompt('Copy this link:', shareUrl);
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 Discover page loaded, initializing simple filters...');
            
            const filterButtons = document.querySelectorAll('.filter-button');
            const allPosts = document.querySelectorAll('.post-card');
            
            console.log(`📊 Found ${filterButtons.length} filter buttons and ${allPosts.length} posts`);
            
            filterButtons.forEach((button, index) => {
                button.addEventListener('click', function() {
                    console.log('🖱️ Filter button clicked:', this.getAttribute('data-filter'));
                    
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    
                    const filter = this.getAttribute('data-filter');
                    console.log('🔍 Filter applied:', filter);
                    
                    allPosts.forEach(post => {
                        const postContent = post.textContent.toLowerCase();
                        const postDate = new Date(post.querySelector('.post-time').textContent);
                        const hasMedia = post.querySelector('.post-media') !== null;
                        const now = new Date();
                        const daysDiff = (now - postDate) / (1000 * 60 * 60 * 24);
                        
                        let shouldShow = true;
                        
                        switch(filter) {
                            case 'all':
                                shouldShow = true;
                                break;
                            case 'recent':
                                shouldShow = daysDiff <= 1; 
                                break;
                            case 'popular':
                                const likesCount = parseInt(post.querySelector('.likes-count')?.textContent || '0');
                                shouldShow = likesCount >= 1; 
                                break;
                            case 'media':
                                shouldShow = hasMedia;
                                break;
                        }
                        
                        post.style.display = shouldShow ? 'block' : 'none';
                    });
                    
                    const visiblePosts = Array.from(allPosts).filter(post => post.style.display !== 'none');
                    const postsGrid = document.querySelector('.posts-grid');
                    
                    if (visiblePosts.length === 0) {
                        const emptyState = document.createElement('div');
                        emptyState.className = 'empty-state';
                        emptyState.id = 'filter-empty-state';
                        emptyState.innerHTML = `
                            <i class="bi bi-search empty-state-icon"></i>
                            <h3 class="empty-state-title">No Posts Found</h3>
                            <p class="empty-state-desc">No posts match the "${filter}" filter. Try a different filter!</p>
                            <button onclick="resetFilter()" class="btn-primary">
                                <i class="bi bi-arrow-clockwise"></i>
                                Show All Posts
                            </button>
                        `;
                        
                        const existingEmpty = document.getElementById('filter-empty-state');
                        if (existingEmpty) existingEmpty.remove();
                        
                        postsGrid.parentElement.appendChild(emptyState);
                        postsGrid.style.display = 'none';
                        } else {
                        const existingEmpty = document.getElementById('filter-empty-state');
                        if (existingEmpty) existingEmpty.remove();
                        postsGrid.style.display = 'grid';
                    }
                    
                    console.log(`✅ Filter "${filter}" applied. Showing ${visiblePosts.length} posts`);
                    });
                });
            
            console.log('✅ Simple filter buttons initialized successfully');
        });
        
        function formatPostDate(dateString) {
            if (!dateString) return 'Unknown';
            
            try {
                const date = new Date(dateString);
                if (isNaN(date.getTime())) return 'Unknown';
                
                return date.toLocaleDateString('en-US', { 
                    month: 'short', 
                    day: 'numeric', 
                    year: 'numeric' 
                });
            } catch (error) {
                console.error('Error formatting post date:', error);
                return 'Unknown';
            }
        }
        
        function resetFilter() {
            const allPostsBtn = document.querySelector('[data-filter="all"]');
            if (allPostsBtn) {
                allPostsBtn.click();
            }
        }
        
        window.currentUserAvatar = '<?php echo !empty($current_user['avatar_url']) ? htmlspecialchars($current_user['avatar_url']) : 'https://ui-avatars.com/api/?name=' . urlencode($current_user['username']) . '&background=667eea&color=fff&size=80'; ?>';
        
        console.log('🌟 Discover page loaded with', <?php echo count($posts); ?>, 'posts');
    </script>
</body>
</html> 