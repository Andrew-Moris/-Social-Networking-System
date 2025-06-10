<?php

session_start();

header('Content-Type: text/html; charset=utf-8');

$host = 'localhost';
$dbname = 'wep_db';
$user = 'root';
$password = '';
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];
$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $password, $options);
    echo "<h1>سكريبت إصلاح مشكلة المستخدمين</h1>";
    echo "<p>تم الاتصال بقاعدة البيانات بنجاح</p>";
    
    $tables = $pdo->query("SHOW TABLES LIKE 'users'")->fetchAll();
    if (count($tables) === 0) {
        echo "<p style='color:red'>جدول المستخدمين غير موجود. سيتم إنشاؤه الآن...</p>";
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            first_name VARCHAR(50),
            last_name VARCHAR(50),
            bio TEXT,
            avatar_url VARCHAR(255),
            cover_url VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            last_active TIMESTAMP NULL,
            is_active TINYINT(1) DEFAULT 1,
            role ENUM('user', 'admin') DEFAULT 'user'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        echo "<p style='color:green'>تم إنشاء جدول المستخدمين بنجاح</p>";
    } else {
        echo "<p style='color:green'>جدول المستخدمين موجود</p>";
    }
    
    $users_count = $pdo->query("SELECT COUNT(*) as count FROM users")->fetch()['count'];
    echo "<p>عدد المستخدمين في قاعدة البيانات: {$users_count}</p>";
    
    $users = $pdo->query("SELECT id, username, first_name, last_name, email FROM users")->fetchAll();
    if (count($users) > 0) {
        echo "<h2>المستخدمون الموجودون:</h2>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>المعرف</th><th>اسم المستخدم</th><th>الاسم الأول</th><th>الاسم الأخير</th><th>البريد الإلكتروني</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['username']}</td>";
            echo "<td>{$user['first_name']}</td>";
            echo "<td>{$user['last_name']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:red'>لا يوجد مستخدمين في قاعدة البيانات</p>";
    }
    
    if ($users_count < 2) {
        echo "<h2>إنشاء مستخدمين اختباريين</h2>";
        
        if (!isset($_SESSION['user_id'])) {
            echo "<p style='color:red'>لا يوجد مستخدم حالي. سيتم إنشاء مستخدم جديد...</p>";
            
            $check_admin = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $check_admin->execute(['admin']);
            $admin_user = $check_admin->fetch();
            
            if (!$admin_user) {
                $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
                $insert_admin = $pdo->prepare("INSERT INTO users (username, password, email, first_name, last_name, role) VALUES (?, ?, ?, ?, ?, ?)");
                $insert_admin->execute(['admin', $admin_password, 'admin@example.com', 'مدير', 'النظام', 'admin']);
                $admin_id = $pdo->lastInsertId();
                
                echo "<p style='color:green'>تم إنشاء المستخدم الرئيسي بنجاح. المعرف: {$admin_id}</p>";
                
                $_SESSION['user_id'] = $admin_id;
                $_SESSION['username'] = 'admin';
            } else {
                echo "<p style='color:green'>المستخدم الرئيسي موجود بالفعل. المعرف: {$admin_user['id']}</p>";
                
                $_SESSION['user_id'] = $admin_user['id'];
                $_SESSION['username'] = 'admin';
            }
        } else {
            echo "<p style='color:green'>المستخدم الحالي موجود. المعرف: {$_SESSION['user_id']}</p>";
        }
        
        $test_users = [
            ['test_user1', 'test123', 'test1@example.com', 'مستخدم', 'اختباري 1'],
            ['test_user2', 'test123', 'test2@example.com', 'مستخدم', 'اختباري 2'],
            ['ahmed', 'ahmed123', 'ahmed@example.com', 'أحمد', 'محمد'],
            ['sara', 'sara123', 'sara@example.com', 'سارة', 'أحمد']
        ];
        
        foreach ($test_users as $user_data) {
            $check_user = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $check_user->execute([$user_data[0]]);
            $test_user = $check_user->fetch();
            
            if (!$test_user) {
                $test_password = password_hash($user_data[1], PASSWORD_DEFAULT);
                $insert_user = $pdo->prepare("INSERT INTO users (username, password, email, first_name, last_name) VALUES (?, ?, ?, ?, ?)");
                $insert_user->execute([$user_data[0], $test_password, $user_data[2], $user_data[3], $user_data[4]]);
                $user_id = $pdo->lastInsertId();
                
                echo "<p style='color:green'>تم إنشاء المستخدم {$user_data[0]} بنجاح. المعرف: {$user_id}</p>";
            } else {
                echo "<p>المستخدم {$user_data[0]} موجود بالفعل. المعرف: {$test_user['id']}</p>";
            }
        }
    }
    
    $tables = $pdo->query("SHOW TABLES LIKE 'messages'")->fetchAll();
    if (count($tables) === 0) {
        echo "<p style='color:red'>جدول الرسائل غير موجود. سيتم إنشاؤه الآن...</p>";
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sender_id INT NOT NULL,
            receiver_id INT NOT NULL,
            content TEXT NOT NULL,
            media_url VARCHAR(255) NULL,
            media_type ENUM('image', 'video', 'audio', 'file') NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        echo "<p style='color:green'>تم إنشاء جدول الرسائل بنجاح</p>";
    } else {
        echo "<p style='color:green'>جدول الرسائل موجود</p>";
    }
    
    if (isset($_SESSION['user_id'])) {
        $current_user_id = $_SESSION['user_id'];
        
        $other_user = $pdo->prepare("SELECT id FROM users WHERE id != ? LIMIT 1");
        $other_user->execute([$current_user_id]);
        $other_user_data = $other_user->fetch();
        
        if ($other_user_data) {
            $other_user_id = $other_user_data['id'];
            
            $messages_count = $pdo->query("SELECT COUNT(*) as count FROM messages")->fetch()['count'];
            echo "<p>عدد الرسائل في قاعدة البيانات: {$messages_count}</p>";
            
            if ($messages_count < 2) {
                $test_messages = [
                    [$current_user_id, $other_user_id, 'مرحباً! كيف حالك؟'],
                    [$other_user_id, $current_user_id, 'أهلاً! أنا بخير، شكراً لسؤالك.'],
                    [$current_user_id, $other_user_id, 'هل يمكنني مساعدتك في شيء ما؟'],
                    [$other_user_id, $current_user_id, 'نعم، أريد معلومات عن المشروع الجديد.']
                ];
                
                foreach ($test_messages as $message_data) {
                    $insert_message = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, content) VALUES (?, ?, ?)");
                    $insert_message->execute($message_data);
                    $message_id = $pdo->lastInsertId();
                    
                    echo "<p style='color:green'>تم إضافة رسالة اختبارية بنجاح. المعرف: {$message_id}</p>";
                }
            }
        }
    }
    
    echo "<div style='margin-top: 20px;'>";
    echo "<h2>روابط مفيدة:</h2>";
    echo "<ul>";
    echo "<li><a href='chat.php' target='_blank'>فتح صفحة المحادثة</a></li>";
    echo "<li><a href='login.php' target='_blank'>تسجيل الدخول</a></li>";
    echo "</ul>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<h1 style='color:red'>خطأ في الاتصال بقاعدة البيانات</h1>";
    echo "<p>{$e->getMessage()}</p>";
}
?>
