<?php

/**
 * Verify user authentication for API requests
 * @param bool $public_access Whether this endpoint allows public access without token
 * @return array|bool User data if authenticated, false if not
 */
function verify_api_auth($public_access = false) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if ($public_access && $_SERVER['REQUEST_METHOD'] === 'GET') {
        return true;
    }
    
    if (isset($_SESSION['user_id']) && isset($_SESSION['username'])) {
        require_once dirname(__DIR__) . '/config.php';
        require_once dirname(__DIR__) . '/functions.php';
        
        try {
            $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
            $user = findUserByUsername($pdo, $_SESSION['username']);
            
            if ($user && $user['id'] == $_SESSION['user_id']) {
                return $user;
            }
        } catch (PDOException $e) {
            log_error("API Auth Error: " . $e->getMessage());
        }
    }
    
    $headers = getallheaders();
    $auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : '';
    
    if (preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
        $token = $matches[1];
        
        $user = verify_token($token);
        if ($user) {
            return $user;
        }
    }
    
    return false;
}

/**
 * Verify an API token
 * @param string $token The authorization token
 * @return array|bool User data if token is valid, false otherwise
 */
function verify_token($token) {
    require_once dirname(__DIR__) . '/config.php';
    
    try {
        $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
        
        $stmt = $pdo->prepare("
            SELECT u.* FROM users u 
            JOIN api_tokens t ON u.id = t.user_id 
            WHERE t.token = :token AND t.expires_at > NOW()
        ");
        
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: false;
    } catch (PDOException $e) {
        log_error("Token verification error: " . $e->getMessage());
        return false;
    }
}

/**
 * Send a JSON response
 * @param mixed $data The data to encode as JSON
 * @param int $status_code HTTP status code
 */
function send_json_response($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Verify CSRF token to prevent CSRF attacks
 * @param string $token The submitted token
 * @return bool True if token is valid, false otherwise
 */
function verify_csrf_token($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token']) || $_SESSION['csrf_token'] !== $token) {
        return false;
    }
    
    return true;
}

/**
 * Process uploaded images safely
 * @param array $file The file from $_FILES
 * @param string $folder Folder to store in
 * @param int $user_id User ID for filename
 * @return string|bool URL to saved image or false on failure
 */
function process_uploaded_image($file, $folder, $user_id) {
    error_log("Processing uploaded image: " . json_encode([
        'name' => $file['name'] ?? 'undefined',
        'type' => $file['type'] ?? 'undefined',
        'size' => $file['size'] ?? 0,
        'error' => $file['error'] ?? 'undefined'
    ]));
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error_message = 'حدث خطأ أثناء تحميل الملف: ';
        switch ($file['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $error_message .= 'حجم الملف أكبر من الحد المسموح به';
                break;
            case UPLOAD_ERR_PARTIAL:
                $error_message .= 'تم تحميل جزء من الملف فقط';
                break;
            case UPLOAD_ERR_NO_FILE:
                $error_message .= 'لم يتم تحميل أي ملف';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $error_message .= 'المجلد المؤقت مفقود';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $error_message .= 'فشل في كتابة الملف على القرص';
                break;
            case UPLOAD_ERR_EXTENSION:
                $error_message .= 'تم إيقاف التحميل بواسطة إضافة';
                break;
            default:
                $error_message .= 'خطأ غير معروف';
        }
        error_log("Upload error: " . $error_message);
        return false;
    }
    
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', ''];
    $max_size = 10 * 1024 * 1024; 
    
    if (!in_array($file['type'], $allowed_types) && !empty($file['type'])) {
        error_log("Invalid file type: {$file['type']}");
        return false;
    }
    
    if ($file['size'] > $max_size) {
        error_log("File too large: {$file['size']} bytes");
        return false;
    }
    
    if (!file_exists($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        error_log("Temp file does not exist or is not an uploaded file: {$file['tmp_name']}");
        return false;
    }
    
    $upload_dir = dirname(__DIR__) . "/uploads/{$folder}/";
    error_log("Upload directory: {$upload_dir}");
    
    if (!is_dir($upload_dir)) {
        $parent_dir = dirname(__DIR__) . "/uploads/";
        if (!is_dir($parent_dir)) {
            if (!@mkdir($parent_dir, 0777, true)) {
                $error = error_get_last();
                error_log("Failed to create parent uploads directory: " . ($error ? json_encode($error) : 'Unknown error'));
                return false;
            }
        }
        if (!@mkdir($upload_dir, 0777, true)) {
            $error = error_get_last();
            error_log("Failed to create uploads/{$folder} directory: " . ($error ? json_encode($error) : 'Unknown error'));
            return false;
        }
    }
    
    if (!is_writable($upload_dir)) {
        error_log("Upload directory is not writable: {$upload_dir}");
        @chmod($upload_dir, 0777);
        if (!is_writable($upload_dir)) {
            error_log("Failed to make upload directory writable even after chmod");
            return false;
        }
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    if (empty($extension)) $extension = 'jpg'; 
    $filename = $user_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $filepath = $upload_dir . $filename;
    
    error_log("Attempting to save file to: {$filepath}");
    
    if (copy($file['tmp_name'], $filepath) || move_uploaded_file($file['tmp_name'], $filepath)) {
        error_log("File uploaded successfully to: {$filepath}");
        
        if (!file_exists($filepath)) {
            error_log("File does not exist after upload: {$filepath}");
            return false;
        }
        
        return "uploads/{$folder}/{$filename}";
    }
    
    $error = error_get_last();
    error_log("Failed to upload file: " . ($error ? json_encode($error) : 'Unknown error'));
    return false;
}
