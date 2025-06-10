<?php
require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['action'])) {
            jsonError('Action is required');
        }

        switch ($data['action']) {
            case 'login':
                if (!isset($data['username']) || !isset($data['password'])) {
                    jsonError('Username and password are required');
                }

                $username = $mysqli->real_escape_string($data['username']);
                $password = $data['password'];
                
                $query = "SELECT id, username, password, email, first_name, last_name, 
                                profile_picture, is_verified, is_private
                         FROM users 
                         WHERE username = ? OR email = ?";
                $stmt = $mysqli->prepare($query);
                $stmt->bind_param("ss", $username, $username);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                
                if (!$user || !password_verify($password, $user['password'])) {
                    jsonError('Invalid username or password', 401);
                }
                
                $stmt = $mysqli->prepare("UPDATE users SET last_active = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->bind_param("i", $user['id']);
                $stmt->execute();
                
                $token = generateJWT($user);
                
                unset($user['password']);
                
                jsonResponse([
                    'message' => 'Login successful',
                    'token' => $token,
                    'user' => $user
                ]);
                break;
                
            case 'register':
                if (!isset($data['username']) || !isset($data['email']) || !isset($data['password'])) {
                    jsonError('Username, email and password are required');
                }

                $username = $mysqli->real_escape_string($data['username']);
                $email = $mysqli->real_escape_string($data['email']);
                $password = password_hash($data['password'], PASSWORD_DEFAULT);
                
                $stmt = $mysqli->prepare("SELECT 1 FROM users WHERE username = ? OR email = ?");
                $stmt->bind_param("ss", $username, $email);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->fetch_assoc()) {
                    jsonError('Username or email already exists');
                }
                
                $query = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
                $stmt = $mysqli->prepare($query);
                $stmt->bind_param("sss", $username, $email, $password);
                
                if ($stmt->execute()) {
                    $user_id = $mysqli->insert_id;
                    
                    $stmt = $mysqli->prepare("INSERT INTO user_settings (user_id) VALUES (?)");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    
                    $user = [
                        'id' => $user_id,
                        'username' => $username,
                        'email' => $email
                    ];
                    $token = generateJWT($user);
                    
                    jsonResponse([
                        'message' => 'Registration successful',
                        'token' => $token,
                        'user' => $user
                    ]);
                } else {
                    jsonError('Registration failed');
                }
                break;
                
            case 'logout':
                jsonResponse(['message' => 'Logout successful']);
                break;
                
            default:
                jsonError('Invalid action');
                break;
        }
        break;

    default:
        jsonError('Method not allowed', 405);
        break;
}

function generateJWT($user) {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload = json_encode([
        'user_id' => $user['id'],
        'username' => $user['username'],
        'exp' => time() + (60 * 60 * 24) 
    ]);
    
    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    
    $signature = hash_hmac('sha256', 
        $base64UrlHeader . "." . $base64UrlPayload, 
        JWT_SECRET_KEY, 
        true
    );
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}
?> 