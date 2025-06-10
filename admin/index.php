<?php
session_start();
require_once '../config.php';
require_once '../functions.php';

if (isLoggedIn() && isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم المسؤول | SUT Premium</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Cairo', sans-serif; }
        body { background: #0a0f1c; color: #ffffff; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md">
        <div class="bg-[#1a1f2e] rounded-2xl p-8 text-center">
            <div class="mb-8">
                <h1 class="text-3xl font-bold mb-2">لوحة تحكم المسؤول</h1>
                <p class="text-gray-400">SUT Premium</p>
            </div>
            
            <div class="space-y-4">
                <a href="login.php" class="block w-full bg-gradient-to-r from-blue-500 to-purple-600 text-white py-3 rounded-xl font-semibold">
                    تسجيل الدخول كمسؤول
                </a>
                
                <a href="../index.php" class="block w-full bg-gray-700 hover:bg-gray-600 text-white py-3 rounded-xl font-semibold">
                    العودة إلى الموقع الرئيسي
                </a>
            </div>
        </div>
    </div>
</body>
</html>
