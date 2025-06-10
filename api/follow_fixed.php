<?php


header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start();

require_once dirname(__DIR__) . '/config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in first'
    ]);
    exit;
}

$current_user_id = $_SESSION['user_id'];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS followers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        follower_id INT NOT NULL,
        followed_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (followed_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_follow (follower_id, followed_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    $method = $_SERVER['REQUEST_METHOD'];
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    error_log("Follow API Request - Method: $method, Data: " . json_encode($data));
    
    if (!$data || !isset($data['action']) || !isset($data['followed_id'])) {
        throw new Exception('Incomplete request data. Action and followed_id are required.');
    }
    
    $action = $data['action'];
    $followed_id = intval($data['followed_id']);
    
    if ($followed_id <= 0) {
        throw new Exception('Invalid user ID');
    }
    
    if ($current_user_id == $followed_id) {
        throw new Exception('You cannot follow yourself');
    }
    
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE id = ?");
    $stmt->execute([$followed_id]);
    $target_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$target_user) {
        throw new Exception('Target user not found');
    }
    
    $response = ['success' => false, 'message' => '', 'data' => []];
    
    switch ($action) {
        case 'follow':
            $stmt = $pdo->prepare("SELECT id FROM followers WHERE follower_id = ? AND followed_id = ?");
            $stmt->execute([$current_user_id, $followed_id]);
            
            if ($stmt->fetch()) {
                $response = [
                    'success' => false,
                    'message' => 'You are already following this user'
                ];
                break;
            }
            
            $stmt = $pdo->prepare("INSERT INTO followers (follower_id, followed_id) VALUES (?, ?)");
            $result = $stmt->execute([$current_user_id, $followed_id]);
            
            if ($result) {
                $stmt = $pdo->prepare("SELECT COUNT(*) as followers_count FROM followers WHERE followed_id = ?");
                $stmt->execute([$followed_id]);
                $followers_count = $stmt->fetchColumn();
                
                $response = [
                    'success' => true,
                    'message' => 'Successfully started following user',
                    'data' => [
                        'followed_user' => $target_user['username'],
                        'followers_count' => $followers_count,
                        'is_following' => true
                    ]
                ];
                
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO notifications (user_id, type, title, message, data, created_at) 
                        VALUES (?, 'follow', 'New Follower', ?, ?, NOW())
                    ");
                    $stmt->execute([
                        $followed_id,
                        'You have a new follower',
                        json_encode(['follower_id' => $current_user_id])
                    ]);
                } catch (Exception $e) {
                    error_log("Notification error: " . $e->getMessage());
                }
            } else {
                throw new Exception('Failed to create follow relationship');
            }
            break;
            
        case 'unfollow':
            error_log("UNFOLLOW ATTEMPT - Current User: $current_user_id, Target User: $followed_id");
            
            $stmt = $pdo->prepare("SELECT id FROM followers WHERE follower_id = ? AND followed_id = ?");
            $stmt->execute([$current_user_id, $followed_id]);
            $follow_record = $stmt->fetch();
            
            error_log("UNFOLLOW CHECK - Follow record found: " . ($follow_record ? "YES (ID: " . $follow_record['id'] . ")" : "NO"));
            
            if (!$follow_record) {
                $response = [
                    'success' => false,
                    'message' => 'You are not following this user'
                ];
                error_log("UNFOLLOW FAILED - User not following target");
                break;
            }
            
            $stmt = $pdo->prepare("DELETE FROM followers WHERE follower_id = ? AND followed_id = ?");
            $result = $stmt->execute([$current_user_id, $followed_id]);
            
            error_log("UNFOLLOW DELETE - SQL Execute result: " . ($result ? "SUCCESS" : "FAILED"));
            
            if ($result) {
                $affected_rows = $stmt->rowCount();
                error_log("UNFOLLOW DELETE - Affected rows: $affected_rows");
                
                $stmt = $pdo->prepare("SELECT COUNT(*) as followers_count FROM followers WHERE followed_id = ?");
                $stmt->execute([$followed_id]);
                $followers_count = $stmt->fetchColumn();
                
                error_log("UNFOLLOW SUCCESS - New followers count: $followers_count");
                
                $response = [
                    'success' => true,
                    'message' => 'Successfully unfollowed user',
                    'data' => [
                        'unfollowed_user' => $target_user['username'],
                        'followers_count' => $followers_count,
                        'is_following' => false,
                        'affected_rows' => $affected_rows
                    ]
                ];
            } else {
                error_log("UNFOLLOW FAILED - SQL execute returned false");
                throw new Exception('Failed to unfollow user');
            }
            break;
            
        case 'toggle':
            $stmt = $pdo->prepare("SELECT id FROM followers WHERE follower_id = ? AND followed_id = ?");
            $stmt->execute([$current_user_id, $followed_id]);
            $is_following = (bool)$stmt->fetch();
            
            if ($is_following) {
                $stmt = $pdo->prepare("DELETE FROM followers WHERE follower_id = ? AND followed_id = ?");
                $stmt->execute([$current_user_id, $followed_id]);
                $new_status = false;
                $message = 'Successfully unfollowed user';
            } else {
                $stmt = $pdo->prepare("INSERT INTO followers (follower_id, followed_id) VALUES (?, ?)");
                $stmt->execute([$current_user_id, $followed_id]);
                $new_status = true;
                $message = 'Successfully started following user';
            }
            
            $stmt = $pdo->prepare("SELECT COUNT(*) as followers_count FROM followers WHERE followed_id = ?");
            $stmt->execute([$followed_id]);
            $followers_count = $stmt->fetchColumn();
            
            $response = [
                'success' => true,
                'message' => $message,
                'data' => [
                    'target_user' => $target_user['username'],
                    'followers_count' => $followers_count,
                    'is_following' => $new_status
                ]
            ];
            break;
            
        case 'check':
            $stmt = $pdo->prepare("SELECT id FROM followers WHERE follower_id = ? AND followed_id = ?");
            $stmt->execute([$current_user_id, $followed_id]);
            $is_following = (bool)$stmt->fetch();
            
            $stmt = $pdo->prepare("SELECT COUNT(*) as followers_count FROM followers WHERE followed_id = ?");
            $stmt->execute([$followed_id]);
            $followers_count = $stmt->fetchColumn();
            
            $stmt = $pdo->prepare("SELECT COUNT(*) as following_count FROM followers WHERE follower_id = ?");
            $stmt->execute([$followed_id]);
            $following_count = $stmt->fetchColumn();
            
            $response = [
                'success' => true,
                'message' => 'Follow status retrieved successfully',
                'data' => [
                    'target_user' => $target_user['username'],
                    'is_following' => $is_following,
                    'followers_count' => $followers_count,
                    'following_count' => $following_count
                ]
            ];
            break;
            
        default:
            throw new Exception('Unsupported action: ' . $action);
    }
    
    error_log("Follow API Success - Action: $action, User: $followed_id, Result: " . json_encode($response));
    
} catch (PDOException $e) {
    error_log("Database error in follow_fixed.php: " . $e->getMessage());
    $response = [
        'success' => false,
        'message' => 'Database error occurred'
    ];
    http_response_code(500);
} catch (Exception $e) {
    error_log("Error in follow_fixed.php: " . $e->getMessage());
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
    http_response_code(400);
}

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?> 