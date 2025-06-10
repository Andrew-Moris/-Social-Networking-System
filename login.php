<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

if (isLoggedIn()) {
    header('Location: home.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['avatar'] = $user['avatar'];
            
            header('Location: u.php');
            exit;
        }
        
        $error = 'اسم المستخدم أو كلمة المرور غير صحيحة';
    } catch (PDOException $e) {
        $error = 'حدث خطأ في الاتصال بقاعدة البيانات';
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول | WEP Social</title>
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
        <div class="bg-[#1a1f2e] rounded-2xl p-8">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold mb-2">WEP Social</h1>
                <p class="text-gray-400">أهلاً بك مجدداً!</p>
            </div>
            
            <?php if (isset($error)): ?>
            <div class="bg-red-500/10 border border-red-500/20 text-red-500 rounded-lg p-4 mb-6">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            
            <form method="post" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium mb-2">اسم المستخدم</label>
                    <input type="text" name="username" class="w-full bg-[#0a0f1c] border border-gray-700 rounded-xl p-3 text-white" required>
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">كلمة المرور</label>
                    <input type="password" name="password" class="w-full bg-[#0a0f1c] border border-gray-700 rounded-xl p-3 text-white" required>
                </div>
                
                <button type="submit" class="w-full bg-gradient-to-r from-blue-500 to-purple-600 text-white py-3 rounded-xl font-semibold">
                    تسجيل الدخول
                </button>
            </form>
            
            <div class="mt-6 text-center text-gray-400">
                ليس لديك حساب؟ <a href="register.php" class="text-blue-500">إنشاء حساب جديد</a>
            </div>
        </div>
    </div>
</body>
</html> 