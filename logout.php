<?php


session_start();

if (isset($_COOKIE['remember_token'])) {
    require_once 'config.php';
    try {
        $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
        $stmt = $pdo->prepare("DELETE FROM remember_tokens WHERE token = ?");
        $stmt->execute([$_COOKIE['remember_token']]);
    } catch (PDOException $e) {
    }
    
    setcookie('remember_token', '', time() - 3600, '/', '', true, true);
}

$_SESSION = array();

if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

session_destroy();

header('Location: frontend/login.html?success=' . urlencode('تم تسجيل الخروج بنجاح'));
exit;
