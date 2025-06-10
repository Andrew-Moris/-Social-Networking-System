<?php
$pg_host = 'localhost';
$pg_port = '5432';
$pg_dbname = 'socialmedia';
$pg_user = 'postgres';
$pg_password = '20043110';

try {
    $dsn = "pgsql:host=$pg_host;port=$pg_port;dbname=$pg_dbname";
    $pdo = new PDO($dsn, $pg_user, $pg_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h3>✅ تم الاتصال بقاعدة البيانات PostgreSQL بنجاح</h3>";
    
    $stmt = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'users' AND column_name = 'is_admin'");
    $is_admin_exists = $stmt->fetch();
    
    if (!$is_admin_exists) {
        $pdo->exec("ALTER TABLE users ADD COLUMN is_admin BOOLEAN DEFAULT FALSE");
        echo "<p>✅ تم إضافة حقل is_admin إلى جدول المستخدمين بنجاح</p>";
    } else {
        echo "<p>ℹ️ حقل is_admin موجود بالفعل في جدول المستخدمين</p>";
    }
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute(['admin']);
    $admin_exists = $stmt->fetch();
    
    if ($admin_exists) {
        $hashed_password = password_hash('admin', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ?, is_admin = TRUE WHERE username = ?");
        $stmt->execute([$hashed_password, 'admin']);
        echo "<p>✅ تم تحديث بيانات المستخدم admin بنجاح</p>";
    } else {
        $hashed_password = password_hash('admin', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, first_name, last_name, is_admin) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute(['admin', 'admin@wep.com', $hashed_password, 'مدير', 'النظام', true]);
        echo "<p>✅ تم إنشاء مستخدم admin جديد بنجاح</p>";
    }
    
    echo "<div style='margin-top: 20px; padding: 10px; background-color: #f0f8ff; border: 1px solid #4682b4;'>";
    echo "<h3>بيانات الدخول للمسؤول:</h3>";
    echo "<p><strong>اسم المستخدم:</strong> admin</p>";
    echo "<p><strong>كلمة المرور:</strong> admin</p>";
    echo "</div>";
    
    echo "<p style='margin-top: 20px;'><a href='login.php' style='color: blue;'>انتقل إلى صفحة تسجيل الدخول</a></p>";
    
} catch (PDOException $e) {
    echo "<h3 style='color: red;'>❌ خطأ في الاتصال بقاعدة البيانات</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    
    echo "<h4>حلول محتملة:</h4>";
    echo "<ol>";
    echo "<li>تأكد من تشغيل خدمة PostgreSQL</li>";
    echo "<li>تحقق من صحة بيانات الاتصال (اسم المستخدم، كلمة المرور، اسم قاعدة البيانات)</li>";
    echo "<li>تأكد من وجود قاعدة البيانات 'socialmedia'</li>";
    echo "</ol>";
}
?>
