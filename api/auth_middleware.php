<?php
require_once __DIR__ . '/../config.php';

function authenticateRequest() {
    $headers = getallheaders();
    $auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : '';

    if (empty($auth_header) || !preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
        jsonError('No authorization token provided', 401);
    }

    $token = $matches[1];

    try {
        $key = new \Firebase\JWT\Key(JWT_SECRET_KEY, 'HS256');
        $decoded = \Firebase\JWT\JWT::decode($token, $key);
        
        return $decoded->user_id;
    } catch (Exception $e) {
        jsonError('Invalid or expired token: ' . $e->getMessage(), 401);
    }
} 