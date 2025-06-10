<?php


session_start();
require_once 'config.php';

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = 5");
    $stmt->execute();
    $user = $stmt->fetch();
    
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['avatar_url'] = $user['avatar_url'];
        
        echo "<h1>✅ تم تسجيل الدخول بنجاح!</h1>";
        echo "<p>مرحباً " . htmlspecialchars($user['username']) . "</p>";
        echo "<p>ID: " . $user['id'] . "</p>";
        echo "<p>Email: " . htmlspecialchars($user['email']) . "</p>";
        
        echo '<div style="margin: 20px;">';
        echo '<a href="u.php" style="display: inline-block; padding: 10px 30px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; font-size: 18px; margin: 5px;">الذهاب إلى الملف الشخصي</a>';
        echo '<a href="home.php" style="display: inline-block; padding: 10px 30px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; font-size: 18px; margin: 5px;">الصفحة الرئيسية</a>';
        echo '<a href="debug_u_page.php" style="display: inline-block; padding: 10px 30px; background: #17a2b8; color: white; text-decoration: none; border-radius: 5px; font-size: 18px; margin: 5px;">تشخيص المشاكل</a>';
        echo '</div>';
        
    } else {
        echo "<h1>❌ المستخدم غير موجود</h1>";
        echo "<p>لا يمكن العثور على المستخدم بالمعرف 5</p>";
    }
    
} catch (Exception $e) {
    echo "<h1>❌ خطأ</h1>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل دخول سريع</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 400px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .login-form {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        button {
            width: 100%;
            padding: 12px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        .error {
            color: #dc3545;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .users-list {
            margin-top: 20px;
            padding: 15px;
            background-color: #e9ecef;
            border-radius: 5px;
        }
        .users-list h3 {
            margin-top: 0;
        }
        .user-account {
            margin: 5px 0;
            padding: 5px;
            background-color: white;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="login-form">
        <h2>تسجيل دخول سريع</h2>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">اسم المستخدم:</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">كلمة المرور:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" name="login">تسجيل الدخول</button>
        </form>
        
        <div class="users-list">
            <h3>حسابات تجريبية متاحة:</h3>
            <div class="user-account">
                <strong>admin</strong> - كلمة المرور: password
            </div>
            <div class="user-account">
                <strong>user1</strong> - كلمة المرور: password
            </div>
            <div class="user-account">
                <strong>user2</strong> - كلمة المرور: password
            </div>
            <div class="user-account">
                <strong>user3</strong> - كلمة المرور: password
            </div>
        </div>
    </div>
</body>
</html> 