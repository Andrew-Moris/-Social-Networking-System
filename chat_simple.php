<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

$host = 'localhost';
$dbname = 'wep_db';
$username = 'root';
$password = '';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; 
}

$current_user_id = (int)$_SESSION['user_id'];
$db_error = false;
$error_message = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$current_user_id]);
    $current_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error_message = "خطأ في قاعدة البيانات: " . $e->getMessage();
    $db_error = true;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>صفحة المحادثة المبسطة</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        pre {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>صفحة المحادثة المبسطة</h1>
        
        <?php if ($db_error): ?>
            <div class="error">
                <h3>خطأ في الاتصال بقاعدة البيانات</h3>
                <p><?php echo $error_message; ?></p>
            </div>
        <?php else: ?>
            <div class="success">
                <h3>تم الاتصال بقاعدة البيانات بنجاح</h3>
                <?php if ($current_user): ?>
                    <p>مرحباً، <?php echo htmlspecialchars($current_user['username']); ?></p>
                <?php else: ?>
                    <p>لم يتم العثور على المستخدم الحالي (ID: <?php echo $current_user_id; ?>)</p>
                <?php endif; ?>
            </div>
            
            <h2>معلومات المستخدم الحالي</h2>
            <?php if ($current_user): ?>
                <pre><?php print_r($current_user); ?></pre>
            <?php else: ?>
                <p>لا توجد معلومات متاحة.</p>
            <?php endif; ?>
            
            <h2>معلومات الاتصال بقاعدة البيانات</h2>
            <ul>
                <li>المضيف: <?php echo $host; ?></li>
                <li>اسم قاعدة البيانات: <?php echo $dbname; ?></li>
                <li>اسم المستخدم: <?php echo $username; ?></li>
            </ul>
            
            <h2>روابط مفيدة</h2>
            <ul>
                <li><a href="chat.php">صفحة المحادثة الأصلية</a></li>
                <li><a href="add_last_active_column.php">إضافة عمود last_active</a></li>
                <li><a href="check_tables_structure.php">التحقق من هيكل الجداول</a></li>
            </ul>
        <?php endif; ?>
    </div>
</body>
</html>
