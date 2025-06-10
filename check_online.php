<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_GET['user'])) {
    echo json_encode(['error' => true]);
    exit;
}

$user_id = (int)$_GET['user'];
$is_online = isUserOnline($user_id);

echo json_encode([
    'error' => false,
    'online' => $is_online
]); 