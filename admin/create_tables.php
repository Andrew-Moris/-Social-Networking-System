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
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id SERIAL PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL,
        password VARCHAR(255) NOT NULL,
        first_name VARCHAR(50),
        last_name VARCHAR(50),
        profile_picture VARCHAR(255) DEFAULT 'assets/img/default-avatar.png',
        cover_photo VARCHAR(255),
        bio TEXT,
        location VARCHAR(100),
        website VARCHAR(255),
        phone VARCHAR(20),
        is_verified BOOLEAN DEFAULT FALSE,
        is_private BOOLEAN DEFAULT FALSE,
        is_admin BOOLEAN DEFAULT FALSE,
        last_active TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    echo "<p>✅ تم إنشاء جدول المستخدمين بنجاح</p>";
    
    // إنشاء مستخدم admin
    $check_admin = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $check_admin->execute(['admin']);
    
    if (!$check_admin->fetch()) {
        $hashed_password = password_hash('admin', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, first_name, last_name, is_admin) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute(['admin', 'admin@wep.com', $hashed_password, 'مدير', 'النظام', true]);
        echo "<p>✅ تم إنشاء مستخدم admin بنجاح</p>";
    } else {
        echo "<p>ℹ️ مستخدم admin موجود بالفعل</p>";
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