<?php
/**
 * @param string
 * @return void
 */
if (!function_exists('log_error')) {
    function log_error($message) {
        $log_file = __DIR__ . '/logs/error.log';
        $log_dir = dirname($log_file);
        
        if (!file_exists($log_dir)) {
            mkdir($log_dir, 0777, true);
        }
        
        $log_message = date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL;
        
        file_put_contents($log_file, $log_message, FILE_APPEND);
    }
}

/**
 * @param string 
 * @return string 
 */
function sanitize_text($text) {
    return htmlspecialchars(trim($text), ENT_QUOTES, 'UTF-8');
}

/**

 * @param string 
 * @return bool
 */
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 
 * @param string 
 * @return bool
 */
function is_valid_username($username) {
 
    return preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username) === 1;
}

/**
 
 * @param string 
 * @return string 
 */
function format_date($date) {
    $timestamp = strtotime($date);
    $now = time();
    $diff = $now - $timestamp;
    
    if ($diff < 60) {
        return 'منذ لحظات';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return 'منذ ' . $minutes . ' دقيقة';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return 'منذ ' . $hours . ' ساعة';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return 'منذ ' . $days . ' يوم';
    } else {
        return date('Y/m/d', $timestamp);
    }
}

/**

 * @param string 
 * @return string 
 */
function get_user_avatar($username) {
    return 'https://ui-avatars.com/api/?name=' . urlencode($username) . '&background=1f6feb&color=fff';
}

/**
 * @param int 
 * @param string 
 * @return bool
 */
function has_permission($user_id, $permission) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_permissions WHERE user_id = ? AND permission = ?");
        $stmt->execute([$user_id, $permission]);
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        log_error("خطأ في التحقق من الصلاحيات: " . $e->getMessage());
        return false;
    }
}

/**
 * @return string 
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * @param string 
 * @return bool
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * @param int 
 * @return array 
 */
function get_user_settings($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM user_settings WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        log_error("خطأ في تحميل إعدادات المستخدم: " . $e->getMessage());
        return [];
    }
}

/**
 * @param int 
 * @param array 
 * @return bool
 */
function update_user_settings($user_id, $settings) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE user_settings SET settings = ? WHERE user_id = ?");
        return $stmt->execute([json_encode($settings), $user_id]);
    } catch (PDOException $e) {
        log_error("خطأ في تحديث إعدادات المستخدم: " . $e->getMessage());
        return false;
    }
}

/**
 * @param PDO
 * @param int 
 * @param int
 * @param int 
 * @return array 
 */
if (!function_exists('getUserPostsWithCount')) {
    function getUserPostsWithCount($pdo, $user_id, $page = 1, $per_page = 10) {
        try {
            $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
            $count_stmt->execute([$user_id]);
            $total_count = $count_stmt->fetchColumn();
            
            $offset = ($page - 1) * $per_page;
            
            $stmt = $pdo->prepare(
                "SELECT p.*, 
                    (SELECT COUNT(*) FROM likes WHERE post_id = p.id) AS likes_count,
                    (SELECT COUNT(*) FROM dislikes WHERE post_id = p.id) AS dislikes_count,
                    (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS comments_count,
                    (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?) AS is_liked_by_current_user
                FROM posts p 
                WHERE p.user_id = ? 
                ORDER BY p.created_at DESC 
                LIMIT ? OFFSET ?"
            );
            $stmt->execute([$user_id, $user_id, $per_page, $offset]);
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'posts' => $posts,
                'total_count' => $total_count,
                'has_more' => ($offset + $per_page) < $total_count
            ];
        } catch (PDOException $e) {
            log_error("خطأ في جلب منشورات المستخدم: " . $e->getMessage());
            return ['posts' => [], 'total_count' => 0, 'has_more' => false];
        }
    }
}

/**
 * @param string
 * @param bool 
 * @return string 
 */
if (!function_exists('time_elapsed_string')) {
    function time_elapsed_string($datetime, $full = false) {
        $now = new DateTime;
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);
        
        $weeks = floor($diff->days / 7);
        $days_remaining = $diff->days - ($weeks * 7);
        
        $string = array(
            'y' => ['value' => $diff->y, 'text' => 'سنة'],
            'm' => ['value' => $diff->m, 'text' => 'شهر'],
            'w' => ['value' => $weeks, 'text' => 'أسبوع'],
            'd' => ['value' => $days_remaining, 'text' => 'يوم'],
            'h' => ['value' => $diff->h, 'text' => 'ساعة'],
            'i' => ['value' => $diff->i, 'text' => 'دقيقة'],
            's' => ['value' => $diff->s, 'text' => 'ثانية'],
        );
        
        $result = [];
        foreach ($string as $k => $data) {
            if ($data['value']) {
                $result[$k] = $data['value'] . ' ' . $data['text'] . ($data['value'] > 1 ? '' : ''); // Sin plural en árabe
            }
        }
        
        if (!$full) $result = array_slice($result, 0, 1);
        return $result ? 'منذ ' . implode(', ', $result) : 'الآن';
    }
}

/**
 * @param PDO 
 * @param string 
 * @return array|bool 
 */
if (!function_exists('findUserByUsername')) {
    function findUserByUsername($pdo, $username) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            log_error("خطأ في البحث عن المستخدم: " . $e->getMessage());
            return false;
        }
    }
}

/**

 * @param PDO 
 * @param int 
 * @return int 
 */
if (!function_exists('getFollowersCount')) {
    function getFollowersCount($pdo, $user_id) {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE followed_id = ?");
            $stmt->execute([$user_id]);
            return $stmt->fetchColumn() ?: 0;
        } catch (PDOException $e) {
            log_error("خطأ في حساب عدد المتابعين: " . $e->getMessage());
            return 0;
        }
    }
}

/**
 * @param PDO 
 * @param int 
 * @return int 
 */
if (!function_exists('getFollowingCount')) {
    function getFollowingCount($pdo, $user_id) {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE follower_id = ?");
            $stmt->execute([$user_id]);
            return $stmt->fetchColumn() ?: 0;
        } catch (PDOException $e) {
            log_error("خطأ في حساب عدد المتابَعين: " . $e->getMessage());
            return 0;
        }
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getCurrentUsername() {
    return $_SESSION['username'] ?? null;
}

function validateSession() {
    if (!isLoggedIn()) {
        header('Location: frontend/login.html?error=' . urlencode('يرجى تسجيل الدخول أولاً'));
        exit;
    }
}

function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

if (!function_exists('getUserProfileUrl')) {
    function getUserProfileUrl($username) {
        return 'u.php?username=' . urlencode($username);
    }
}

function formatDate($date) {
    $timestamp = strtotime($date);
    $now = time();
    $diff = $now - $timestamp;
    
    if ($diff < 60) {
        return 'الآن';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return "منذ $minutes دقيقة";
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return "منذ $hours ساعة";
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return "منذ $days يوم";
    } else {
        return date('Y-m-d', $timestamp);
    }
}

if (!function_exists('checkConfig')) {
    function checkConfig() {
        if (!file_exists('config.php')) {
            die('ملف التكوين (config.php) غير موجود');
        }
    }
}

if (!function_exists('checkDatabaseConnection')) {
    function checkDatabaseConnection($dsn, $db_user, $db_pass, $pdo_options) {
        try {
            $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return true;
        } catch (PDOException $e) {
            error_log("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('logout')) {
    function logout() {
        session_start();
        session_destroy();
        header('Location: frontend/login.html?success=' . urlencode('تم تسجيل الخروج بنجاح'));
        exit;
    }
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validateEgyptianPhone($phone) {
    return preg_match('/^(01)[0-2|5]{1}[0-9]{8}$/', $phone);
}

function sanitize($input) {
    if (is_array($input)) {
        foreach ($input as $key => $value) {
            $input[$key] = sanitize($value);
        }
        return $input;
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function validateDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

function createError($message) {
    return ['error' => true, 'message' => $message];
}

function createSuccess($data = [], $message = '') {
    return ['error' => false, 'data' => $data, 'message' => $message];
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function validatePassword($password) {
    return strlen($password) >= PASSWORD_MIN_LENGTH;
}

function validateUsername($username) {
    $length = strlen($username);
    return $length >= USERNAME_MIN_LENGTH && $length <= USERNAME_MAX_LENGTH;
}

function uploadImage($file, $directory = UPLOAD_DIR) {
    if (!isset($file['error']) || is_array($file['error'])) {
        throw new Exception('Invalid file parameters');
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload failed');
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        throw new Exception('File is too large');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($file['tmp_name']);

    if (!in_array($mime_type, ALLOWED_IMAGE_TYPES)) {
        throw new Exception('Invalid file type');
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = generateToken() . '.' . $extension;
    $filepath = $directory . $filename;

    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Failed to move uploaded file');
    }

    return $filename;
}

function uploadVideo($file, $directory = UPLOAD_DIR) {
    if (!isset($file['error']) || is_array($file['error'])) {
        throw new Exception('Invalid file parameters');
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload failed');
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        throw new Exception('File is too large');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($file['tmp_name']);

    if (!in_array($mime_type, ALLOWED_VIDEO_TYPES)) {
        throw new Exception('Invalid file type');
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = generateToken() . '.' . $extension;
    $filepath = $directory . $filename;

    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Failed to move uploaded file');
    }

    return $filename;
}

function truncateText($text, $length = 100) {
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length) . '...';
}

function getPDO() {
    global $pdo, $dsn, $DB_USER, $DB_PASS, $pdo_options;
    
    if (!isset($pdo)) {
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            error_log("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
            return null;
        }
    }
    return $pdo;
}

function isUserOnline($user_id) {
    $pdo = getPDO();
    if (!$pdo) return false;
    
    try {
        $stmt = $pdo->prepare("
            SELECT last_activity 
            FROM users 
            WHERE id = ? 
            AND last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ");
        $stmt->execute([$user_id]);
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        error_log("Error in isUserOnline: " . $e->getMessage());
        return false;
    }
}


function getLastMessage($user1_id, $user2_id) {
    $pdo = getPDO();
    if (!$pdo) return null;
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM messages 
            WHERE (sender_id = ? AND receiver_id = ?)
            OR (sender_id = ? AND receiver_id = ?)
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$user1_id, $user2_id, $user2_id, $user1_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error in getLastMessage: " . $e->getMessage());
        return null;
    }
}


function getUnreadCount($sender_id, $receiver_id) {
    $pdo = getPDO();
    if (!$pdo) return 0;
    
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM messages 
            WHERE sender_id = ? 
            AND receiver_id = ? 
            AND is_read = 0
        ");
        $stmt->execute([$sender_id, $receiver_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['count'];
    } catch (Exception $e) {
        error_log("Error in getUnreadCount: " . $e->getMessage());
        return 0;
    }
}


function formatMessageTime($timestamp) {
    $time = strtotime($timestamp);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return "الآن";
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return "قبل " . $minutes . " دقيقة";
    } elseif ($diff < 86400) {
        return date("h:i A", $time);
    } elseif ($diff < 604800) {
        $days = [
            'Sunday' => 'الأحد',
            'Monday' => 'الاثنين',
            'Tuesday' => 'الثلاثاء',
            'Wednesday' => 'الأربعاء',
            'Thursday' => 'الخميس',
            'Friday' => 'الجمعة',
            'Saturday' => 'السبت'
        ];
        return $days[date('l', $time)];
    } else {
        return date("Y/m/d", $time);
    }
}


function getMessages($user1_id, $user2_id) {
    $pdo = getPDO();
    if (!$pdo) return [];
    
    try {
        $update_stmt = $pdo->prepare("
            UPDATE messages 
            SET is_read = 1 
            WHERE sender_id = ? 
            AND receiver_id = ? 
            AND is_read = 0
        ");
        $update_stmt->execute([$user2_id, $user1_id]);

        $stmt = $pdo->prepare("
            SELECT * FROM messages 
            WHERE (sender_id = ? AND receiver_id = ?)
            OR (sender_id = ? AND receiver_id = ?)
            ORDER BY created_at ASC
        ");
        $stmt->execute([$user1_id, $user2_id, $user2_id, $user1_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error in getMessages: " . $e->getMessage());
        return [];
    }
}

function updateUserActivity($user_id) {
    $pdo = getPDO();
    if (!$pdo) return;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE users 
            SET last_activity = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$user_id]);
    } catch (Exception $e) {
        error_log("Error in updateUserActivity: " . $e->getMessage());
    }
}

if (isset($_SESSION['user_id'])) {
    updateUserActivity($_SESSION['user_id']);
}


function formatDateTime($datetime, $format = 'Y-m-d H:i') {
    $date = new DateTime($datetime);
    return $date->format($format);
}


function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'منذ لحظات';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return 'منذ ' . $minutes . ' ' . ($minutes > 10 ? 'دقيقة' : 'دقائق');
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return 'منذ ' . $hours . ' ' . ($hours > 10 ? 'ساعة' : 'ساعات');
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return 'منذ ' . $days . ' ' . ($days > 10 ? 'يوم' : 'أيام');
    } elseif ($diff < 2592000) {
        $weeks = floor($diff / 604800);
        return 'منذ ' . $weeks . ' ' . ($weeks > 10 ? 'أسبوع' : 'أسابيع');
    } elseif ($diff < 31536000) {
        $months = floor($diff / 2592000);
        return 'منذ ' . $months . ' ' . ($months > 10 ? 'شهر' : 'أشهر');
    } else {
        $years = floor($diff / 31536000);
        return 'منذ ' . $years . ' ' . ($years > 10 ? 'سنة' : 'سنوات');
    }
}


function formatNumber($number) {
    if ($number >= 1000000) {
        return round($number / 1000000, 1) . 'M';
    } elseif ($number >= 1000) {
        return round($number / 1000, 1) . 'K';
    }
    return $number;
}

function isAllowedFileType($file, $types) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    return in_array($mime_type, $types);
}

function generateUniqueFileName($file, $prefix = '') {
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    return $prefix . uniqid() . '_' . time() . '.' . $extension;
}

function uploadFile($file, $directory, $allowed_types, $max_size) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('حدث خطأ أثناء رفع الملف');
    }
    
    if (!isAllowedFileType($file, $allowed_types)) {
        throw new Exception('نوع الملف غير مدعوم');
    }
    
    if ($file['size'] > $max_size) {
        throw new Exception('حجم الملف كبير جداً');
    }
    
    if (!is_dir($directory)) {
        mkdir($directory, 0777, true);
    }
    
    $filename = generateUniqueFileName($file);
    $filepath = $directory . '/' . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('فشل في رفع الملف');
    }
    
    return $filename;
}

function deleteFile($filepath) {
    if (file_exists($filepath)) {
        unlink($filepath);
    }
}


function extractUrls($text) {
    $pattern = '/(https?:\/\/[^\s]+)/';
    preg_match_all($pattern, $text, $matches);
    return $matches[0];
}


function extractHashtags($text) {
    $pattern = '/#([^\s#]+)/';
    preg_match_all($pattern, $text, $matches);
    return $matches[1];
}


function extractMentions($text) {
    $pattern = '/@([^\s@]+)/';
    preg_match_all($pattern, $text, $matches);
    return $matches[1];
}


function linkify($text) {
    $text = preg_replace('/(https?:\/\/[^\s]+)/', '<a href="$1" target="_blank" class="text-blue-500 hover:underline">$1</a>', $text);
    
    $text = preg_replace('/#([^\s#]+)/', '<a href="hashtag.php?tag=$1" class="text-blue-500 hover:underline">#$1</a>', $text);
    
    $text = preg_replace('/@([^\s@]+)/', '<a href="u.php?username=$1" class="text-blue-500 hover:underline">@$1</a>', $text);
    
    return $text;
}

function userExists($username) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute([$username]);
    return $stmt->fetchColumn() > 0;
}

function isFollowing($follower_id, $followed_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE follower_id = ? AND followed_id = ?");
    $stmt->execute([$follower_id, $followed_id]);
    return $stmt->fetchColumn() > 0;
}


function isLiked($user_id, $post_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE user_id = ? AND post_id = ?");
    $stmt->execute([$user_id, $post_id]);
    return $stmt->fetchColumn() > 0;
}

function isBookmarked($user_id, $post_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookmarks WHERE user_id = ? AND post_id = ?");
    $stmt->execute([$user_id, $post_id]);
    return $stmt->fetchColumn() > 0;
}


function getUserInfo($userId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT id, username, first_name, last_name, profile_picture, avatar_url FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting user info: " . $e->getMessage());
        return false;
    }
}


function getUserStats($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $posts_count = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE followed_id = ?");
    $stmt->execute([$user_id]);
    $followers_count = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE follower_id = ?");
    $stmt->execute([$user_id]);
    $following_count = $stmt->fetchColumn();
    
    return [
        'posts' => $posts_count,
        'followers' => $followers_count,
        'following' => $following_count
    ];
}


function getUserPosts($user_id, $page = 1, $limit = 10) {
    global $pdo;
    $offset = ($page - 1) * $limit;
    
    $stmt = $pdo->prepare("
        SELECT 
            p.*,
            u.username,
            u.avatar,
            COUNT(DISTINCT l.id) as likes_count,
            COUNT(DISTINCT c.id) as comments_count,
            EXISTS(SELECT 1 FROM likes WHERE post_id = p.id AND user_id = ?) as is_liked,
            EXISTS(SELECT 1 FROM bookmarks WHERE post_id = p.id AND user_id = ?) as is_bookmarked
        FROM posts p
        JOIN users u ON p.user_id = u.id
        LEFT JOIN likes l ON p.id = l.post_id
        LEFT JOIN comments c ON p.id = c.post_id
        WHERE p.user_id = ?
        GROUP BY p.id
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?
    ");
    
    $stmt->execute([$user_id, $user_id, $user_id, $limit, $offset]);
    return $stmt->fetchAll();
}

function formatTime($timestamp) {
    $now = new DateTime();
    $time = new DateTime($timestamp);
    $diff = $now->diff($time);
    
    if ($diff->d > 7) {
        return $time->format('Y-m-d');
    } elseif ($diff->d > 0) {
        return $diff->d . ' يوم' . ($diff->d > 2 ? ' مضت' : ' مضى');
    } else {
        return $time->format('H:i');
    }
}

function getUnreadMessagesCount($userId, $otherUserId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE sender_id = ? AND receiver_id = ? AND is_read = 0");
        $stmt->execute([$otherUserId, $userId]);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error getting unread messages count: " . $e->getMessage());
        return 0;
    }
}

/**
 * @param string 
 * @return string
 */
if (!function_exists("formatTimeAgo")) {
    function formatTimeAgo($datetime) {
        $time = time() - strtotime($datetime);
        
        if ($time < 60) return "منذ لحظات";
        if ($time < 3600) return "منذ " . floor($time/60) . " دقيقة";
        if ($time < 86400) return "منذ " . floor($time/3600) . " ساعة";
        if ($time < 2592000) return "منذ " . floor($time/86400) . " يوم";
        if ($time < 31536000) return "منذ " . floor($time/2592000) . " شهر";
        
        return "منذ " . floor($time/31536000) . " سنة";
    }
}
