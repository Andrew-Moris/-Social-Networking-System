<?php
session_start();

if (isset($_SESSION['user_id']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    try {
        // إعدادات قاعدة البيانات PostgreSQL
        $pg_host = 'localhost';
        $pg_port = '5432';
        $pg_dbname = 'socialmedia';
        $pg_user = 'postgres';
        $pg_password = '20043110';
        
        // الاتصال بقاعدة البيانات PostgreSQL
        $dsn = "pgsql:host=$pg_host;port=$pg_port;dbname=$pg_dbname";
        $pdo = new PDO($dsn, $pg_user, $pg_password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($password, $admin['password']) && isset($admin['is_admin']) && $admin['is_admin']) {
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['username'] = $admin['username'];
            $_SESSION['is_admin'] = true;
            
            header('Location: dashboard.php');
            exit;
        }
        
        $error = 'اسم المستخدم أو كلمة المرور غير صحيحة أو ليس لديك صلاحيات المسؤول';
    } catch (PDOException $e) {
        $error = 'حدث خطأ في الاتصال بقاعدة البيانات: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل دخول المسؤول | SUT Premium</title>
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
                <h1 class="text-3xl font-bold mb-2">لوحة تحكم المسؤول</h1>
                <p class="text-gray-400">تسجيل الدخول كمسؤول</p>
            </div>
            
            <?php if ($error): ?>
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
                
                <button type="submit" id="login-btn" name="login-btn" class="w-full bg-gradient-to-r from-blue-500 to-purple-600 text-white py-3 rounded-xl font-semibold">
                    تسجيل الدخول
                </button>
            </form>
            
            <div class="mt-6 text-center text-gray-400">
                <a href="../login.php" class="text-blue-500">العودة إلى تسجيل الدخول العادي</a>
            </div>
        </div>
    </div>
</body>
</html>
