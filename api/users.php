<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

require_once __DIR__ . '/auth_middleware.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

if (!isset($conn) || !function_exists('jsonError')) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        'error' => 'Critical configuration error: config.php did not load correctly or essential components are missing. This is likely due to a missing vendor/autoload.php. Please run "composer install" in your project root (C:\\xampp\\htdocs\\WEP\\) and ensure config.php is correctly set up.',
        'message_ar' => 'خطأ فادح في الإعدادات: لم يتم تحميل ملف config.php بشكل صحيح أو أن المكونات الأساسية مفقودة. هذا على الأرجح بسبب عدم وجود ملف vendor/autoload.php. يرجى تشغيل "composer install" في المجلد الرئيسي لمشروعك (C:\\xampp\\htdocs\\WEP\\) والتأكد من أن ملف config.php مُعد بشكل صحيح.'
    ]);
    exit;
}

if (!function_exists('authenticateRequest')) {
    jsonError('Critical authentication middleware error: authenticateRequest function is missing. Ensure auth_middleware.php is loaded correctly.', 500);
}


$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['username'], $data['email'], $data['password'])) {
            jsonError('Missing required fields: username, email, password'); 
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            jsonError('Invalid email format'); 
        }

        $check_query = "SELECT id FROM users WHERE username = ? OR email = ?";
        $check_stmt = $conn->prepare($check_query);
        if (!$check_stmt) {
            jsonError('Database prepare statement failed: ' . $conn->error); 
        }
        $check_stmt->bind_param("ss", $data['username'], $data['email']);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows > 0) {
            jsonError('Username or email already exists'); 
        }
        $check_stmt->close();

     
        $username = $data['username'];
        $email = $data['email'];
        $password = password_hash($data['password'], PASSWORD_DEFAULT);

        $query = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            jsonError('Database prepare statement failed: ' . $conn->error); 
        }
        $stmt->bind_param("sss", $username, $email, $password);

        if ($stmt->execute()) {
            $user_id = $conn->insert_id; 
            if (!function_exists('generateJWT')) {
                 jsonError('JWT generation function (generateJWT) not found. Check config.php.', 500);
            }
            $token = generateJWT($user_id); 

            jsonResponse([
                'message' => 'User registered successfully', 
                'user_id' => $user_id,
                'token' => $token
            ]);
        } else {
            jsonError('Registration failed: ' . $stmt->error); 
        }
        $stmt->close();
        break;

    case 'GET':
        
        $user_id = null;
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            try {
                $token = str_replace('Bearer ', '', $headers['Authorization']);
                $decoded = JWT::decode($token, new Key(JWT_SECRET_KEY, 'HS256'));
                $user_id = $decoded->user_id;
            } catch (Exception $e) {
            }
        }
        
        if ($user_id === null) {
            $query = "SELECT id, username, email, created_at FROM users LIMIT 10";
            $result = $conn->query($query);
            
            if ($result) {
                $users = [];
                while ($row = $result->fetch_assoc()) {
                    $users[] = $row;
                }
                jsonResponse(['users' => $users, 'note' => 'Development mode: No token provided, showing list of users instead']);
            } else {
                jsonError('Failed to fetch users');
            }
            exit;
        }

        $query = "SELECT id, username, email, first_name, last_name, profile_picture, 
                         cover_photo, bio, location, website, phone, is_verified, 
                         is_private, last_active, created_at 
                  FROM users WHERE id = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            jsonError('Database prepare statement failed: ' . $conn->error); 
        }
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            jsonResponse($user); 
        } else {
            jsonError('User not found', 404); 
        }
        $stmt->close();
        break;

    case 'PUT':
        $user_id = authenticateRequest(); 
        $data = json_decode(file_get_contents('php://input'), true);

        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            jsonError('Invalid JSON input for PUT request: ' . json_last_error_msg(), 400); 
        }
        
        $allowed_fields = ['first_name', 'last_name', 'bio', 'location', 'website', 'phone', 'is_private'];
        $updates = []; 
        $types = '';  
        $values = [];  

        foreach ($data as $key => $value) {
            if (in_array($key, $allowed_fields)) {
                $updates[] = "`$key` = ?";
                if ($key === 'is_private') {
                    $types .= "i"; 
                    $values[] = (int)(bool)$value;
                } else {
                    $types .= "s"; 
                    $values[] = $value;
                }
            }
        }

        if (empty($updates)) {
            jsonError('No valid fields to update or no data provided'); 
        }

        $types .= "i";
        $values[] = $user_id;

        $query = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            jsonError('Database prepare statement failed for update: ' . $conn->error); 
        }
        
        $stmt->bind_param($types, ...$values); 

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                jsonResponse(['message' => 'Profile updated successfully']); 
            } else {
                jsonResponse(['message' => 'No changes were made to the profile or user not found.']);
            }
        } else {
            jsonError('Update failed: ' . $stmt->error); 
        }
        $stmt->close();
        break;

    default:
        jsonError('Method not allowed', 405);
        break;
}

?>
