<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $user_id = authenticateRequest();
        
        $query = "SELECT * FROM user_settings WHERE user_id = ?";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($settings = $result->fetch_assoc()) {
            jsonResponse($settings);
        } else {
            jsonError('Settings not found', 404);
        }
        break;

    case 'PUT':
        $user_id = authenticateRequest();
        $data = json_decode(file_get_contents('php://input'), true);
        
        $allowed_fields = [
            'email_notifications',
            'push_notifications',
            'dark_mode',
            'language'
        ];
        
        $updates = [];
        $types = '';
        $values = [];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowed_fields)) {
                $updates[] = "$key = ?";
                $types .= "s";
                $values[] = $value;
            }
        }
        
        if (empty($updates)) {
            jsonError('No valid fields to update');
        }
        
        $types .= "i";
        $values[] = $user_id;
        
        $query = "UPDATE user_settings SET " . implode(", ", $updates) . " WHERE user_id = ?";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param($types, ...$values);
        
        if ($stmt->execute()) {
            jsonResponse(['message' => 'Settings updated successfully']);
        } else {
            jsonError('Failed to update settings');
        }
        break;

    case 'POST':
        $user_id = authenticateRequest();
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['current_password']) || !isset($data['new_password'])) {
            jsonError('Current password and new password are required');
        }
        
        $stmt = $mysqli->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if (!password_verify($data['current_password'], $user['password'])) {
            jsonError('Current password is incorrect', 401);
        }
        
        $new_password = password_hash($data['new_password'], PASSWORD_DEFAULT);
        $stmt = $mysqli->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $new_password, $user_id);
        
        if ($stmt->execute()) {
            jsonResponse(['message' => 'Password changed successfully']);
        } else {
            jsonError('Failed to change password');
        }
        break;

    default:
        jsonError('Method not allowed', 405);
        break;
}
?> 