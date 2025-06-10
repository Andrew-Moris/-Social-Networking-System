<?php

require_once 'config.php';

if (isset($_GET['username'])) {
    $username = trim($_GET['username']);
    header("Location: u.php?username=$username");
    exit;
}

if (isset($_SESSION['user_id']) && isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
    header("Location: u.php?username=$username");
    exit;
}

header("Location: frontend/login.html");
exit;
?>
