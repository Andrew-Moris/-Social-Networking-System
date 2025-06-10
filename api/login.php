<?php
ini_set('display_errors', 0);
error_reporting(0);

ob_start();

require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $isJson = true;
    
    try {
        $postData = json_decode($input, true, 512, JSON_THROW_ON_ERROR);
    } catch (Exception $e) {
        $postData = $_POST;
        $isJson = false;
    }
    
    $redirect = isset($postData['redirect']) ? $postData['redirect'] : '../u.php';
    
    if (empty($postData['username']) || empty($postData['password'])) {
        if ($isJson) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'اسم المستخدم وكلمة المرور مطلوبان']);
        } else {
            header('Location: ../frontend/login.html?error=missing_fields');
        }
        exit;
    }

    try {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ];
        
        error_log("Login attempt: Connecting to database with credentials from config.php");
        error_log("DB_HOST: " . DB_HOST . ", DB_NAME: " . DB_NAME . ", DB_USER: " . DB_USER);
        
        global $pdo;
        
        if (!isset($pdo)) {
            if (defined('DB_CHARSET')) {
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            } 
            else {
                $dsn = "pgsql:host=" . DB_HOST . ";port=" . (defined('DB_PORT') ? DB_PORT : '5432') . ";dbname=" . DB_NAME;
            }
            
            error_log("DSN: " . $dsn);
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        }
        
        try {
            $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ?");
            $stmt->execute([$postData['username']]);
            $user = $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error querying user: " . $e->getMessage());
            throw $e; 
        }
        
        if (!$user) {
            if ($isJson) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'اسم المستخدم غير موجود']);
            } else {
                header('Location: ../frontend/login.html?error=invalid_username');
            }
            exit;
        }
        
        if (!password_verify($postData['password'], $user['password'])) {
            if ($isJson) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'كلمة المرور غير صحيحة']);
            } else {
                header('Location: ../frontend/login.html?error=invalid_password');
            }
            exit;
        }
        
        unset($user['password']);
        
        session_start();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        
        try {
            $tableInfo = $pdo->query("DESCRIBE users");
            $columns = $tableInfo->fetchAll(PDO::FETCH_COLUMN);
            error_log("Available columns in users table: " . implode(", ", $columns));
            
            if (in_array('last_active', $columns)) {
                $stmt = $pdo->prepare("UPDATE users SET last_active = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$user['id']]);
                error_log("Updated last_active timestamp for user ID: " . $user['id']);
            } else {
                error_log("Skipped updating last_active - column does not exist");
            }
        } catch (PDOException $e) {
            try {
                $tableInfo = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'users'");
                $columns = $tableInfo->fetchAll(PDO::FETCH_COLUMN);
                
                if (in_array('last_active', $columns)) {
                    $stmt = $pdo->prepare("UPDATE users SET last_active = CURRENT_TIMESTAMP WHERE id = ?");
                    $stmt->execute([$user['id']]);
                    error_log("Updated last_active timestamp for user ID: " . $user['id']);
                } else {
                    error_log("Skipped updating last_active - column does not exist (PostgreSQL check)");
                }
            } catch (PDOException $e2) {
                error_log("Could not determine table structure: " . $e2->getMessage());
            }
        }
        
        if ($isJson) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'تم تسجيل الدخول بنجاح',
                'user' => $user
            ]);
        } else {
            header('Location: ' . $redirect);
        }
        exit;
        
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        
        if ($isJson) {
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'فشل تسجيل الدخول',
                'details' => $e->getMessage()
            ]);
        } else {
            header('Location: ../frontend/login.html?error=server_error');
        }
        exit;
    }
} else {
    header('Location: ../frontend/login.html');
    exit;
}
?>
