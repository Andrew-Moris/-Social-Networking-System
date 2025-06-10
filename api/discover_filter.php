<?php


require_once '../config.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

function sendSuccess($data = null, $message = null) {
    $response = ['success' => true];
    if ($message) $response['message'] = $message;
    if ($data !== null) $response['data'] = $data;
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit();
}

function sendError($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    sendError('يجب تسجيل الدخول أولاً', 401);
}

$user_id = $_SESSION['user_id'];
$filter = $_GET['filter'] ?? 'all';

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $baseQuery = "
        SELECT DISTINCT p.*, u.username, u.first_name, u.last_name, u.profile_picture,
            (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
            (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count,
            (SELECT COUNT(*) FROM shares WHERE post_id = p.id) as shares_count,
            (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?) as user_liked,
            (SELECT COUNT(*) FROM bookmarks WHERE post_id = p.id AND user_id = ?) as user_bookmarked
        FROM posts p
        JOIN users u ON p.user_id = u.id
        LEFT JOIN friendships f ON (
            (f.sender_id = ? AND f.receiver_id = p.user_id) OR
            (f.sender_id = p.user_id AND f.receiver_id = ?)
        )
        WHERE 
            p.is_private = 0 OR 
            p.user_id = ? OR
            (f.status = 'accepted')
    ";
    
    $params = [$user_id, $user_id, $user_id, $user_id, $user_id];
    $orderBy = "ORDER BY p.created_at DESC";
    $limit = "LIMIT 50";
    
    switch ($filter) {
        case 'all':
            break;
            
        case 'recent':
            $baseQuery .= " AND p.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
            $orderBy = "ORDER BY p.created_at DESC";
            break;
            
        case 'popular':
            $baseQuery .= " AND p.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            $orderBy = "ORDER BY (
                (SELECT COUNT(*) FROM likes WHERE post_id = p.id) + 
                (SELECT COUNT(*) FROM comments WHERE post_id = p.id) * 2
            ) DESC, p.created_at DESC";
            break;
            
        case 'media':
            $baseQuery .= " AND (p.image_url IS NOT NULL OR p.media_url IS NOT NULL)";
            $orderBy = "ORDER BY p.created_at DESC";
            break;
            
        default:
            sendError('فلتر غير صحيح');
    }
    
    $fullQuery = $baseQuery . " " . $orderBy . " " . $limit;
    
    $stmt = $pdo->prepare($fullQuery);
    $stmt->execute($params);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($posts as &$post) {
        $post['likes_count'] = (int)$post['likes_count'];
        $post['comments_count'] = (int)$post['comments_count'];
        $post['shares_count'] = (int)$post['shares_count'];
        $post['user_liked'] = (bool)$post['user_liked'];
        $post['user_bookmarked'] = (bool)$post['user_bookmarked'];
        
        $post['created_at_formatted'] = date('M d, Y', strtotime($post['created_at']));
        
        $post['content'] = htmlspecialchars($post['content']);
        
        if (empty($post['profile_picture'])) {
            $post['profile_picture'] = 'https://ui-avatars.com/api/?name=' . urlencode($post['username']) . '&background=667eea&color=fff&size=200';
        }
    }
    
    $stats = [
        'total_posts' => count($posts),
        'filter_applied' => $filter,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    sendSuccess([
        'posts' => $posts,
        'stats' => $stats
    ], "تم تحميل {$stats['total_posts']} منشور بنجاح");
    
} catch (PDOException $e) {
    error_log("Database error in discover_filter.php: " . $e->getMessage());
    sendError('حدث خطأ في قاعدة البيانات');
} catch (Exception $e) {
    error_log("General error in discover_filter.php: " . $e->getMessage());
    sendError('حدث خطأ غير متوقع');
}
?> 