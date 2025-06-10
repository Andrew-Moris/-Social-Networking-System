<?php


session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

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
    echo "<h2>تم الاتصال بقاعدة البيانات بنجاح</h2>";
} catch (PDOException $e) {
    die("<h2>فشل الاتصال بقاعدة البيانات: " . $e->getMessage() . "</h2>");
}

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100) UNIQUE,
        first_name VARCHAR(50),
        last_name VARCHAR(50),
        avatar_url VARCHAR(255),
        last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<p>تم التحقق من جدول المستخدمين</p>";
} catch (PDOException $e) {
    echo "<p>خطأ في إنشاء جدول المستخدمين: " . $e->getMessage() . "</p>";
}

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        content TEXT NOT NULL,
        is_read BOOLEAN DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sender_id) REFERENCES users(id),
        FOREIGN KEY (receiver_id) REFERENCES users(id)
    )");
    echo "<p>تم التحقق من جدول الرسائل</p>";
} catch (PDOException $e) {
    echo "<p>خطأ في إنشاء جدول الرسائل: " . $e->getMessage() . "</p>";
}

try {
    $users = $pdo->query("SELECT id, username, first_name, last_name FROM users")->fetchAll();
    echo "<h3>المستخدمون الحاليون:</h3>";
    echo "<ul>";
    foreach ($users as $user) {
        echo "<li>ID: {$user['id']} - {$user['username']} ({$user['first_name']} {$user['last_name']})</li>";
    }
    echo "</ul>";
} catch (PDOException $e) {
    echo "<p>خطأ في استرجاع المستخدمين: " . $e->getMessage() . "</p>";
}

if (count($users) < 2) {
    try {
        echo "<h3>إنشاء مستخدمين اختباريين...</h3>";
        
        $pdo->exec("DELETE FROM messages");
        $pdo->exec("DELETE FROM users");
        
        $pdo->exec("ALTER TABLE users AUTO_INCREMENT = 1");
        $pdo->exec("ALTER TABLE messages AUTO_INCREMENT = 1");
        
        $password1 = password_hash('user1pass', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, first_name, last_name) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['user1', $password1, 'user1@example.com', 'مستخدم', 'أول']);
        $user1_id = $pdo->lastInsertId();
        echo "<p>تم إنشاء المستخدم الأول (ID: {$user1_id})</p>";
        
        $password2 = password_hash('user2pass', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, first_name, last_name) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['user2', $password2, 'user2@example.com', 'مستخدم', 'ثاني']);
        $user2_id = $pdo->lastInsertId();
        echo "<p>تم إنشاء المستخدم الثاني (ID: {$user2_id})</p>";
        
        $messages = [
            [$user1_id, $user2_id, 'مرحباً! كيف حالك؟'],
            [$user2_id, $user1_id, 'أهلاً! أنا بخير، شكراً لسؤالك. وأنت؟'],
            [$user1_id, $user2_id, 'أنا أيضاً بخير. هل تعمل المحادثة الآن؟'],
            [$user2_id, $user1_id, 'نعم، تبدو أنها تعمل بشكل جيد!'],
            [$user1_id, $user2_id, 'رائع! لقد نجحنا في إصلاح المشكلة.'],
        ];
        
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, content) VALUES (?, ?, ?)");
        foreach ($messages as $i => $msg) {
            $stmt->execute($msg);
            echo "<p>تم إنشاء الرسالة " . ($i + 1) . "</p>";
        }
        
        echo "<h3>تم إنشاء المستخدمين والرسائل بنجاح!</h3>";
    } catch (PDOException $e) {
        echo "<p>خطأ في إنشاء المستخدمين الاختباريين: " . $e->getMessage() . "</p>";
    }
}

try {
    $messages = $pdo->query("
        SELECT m.id, m.sender_id, m.receiver_id, m.content, m.created_at, 
               s.username as sender_name, r.username as receiver_name
        FROM messages m
        JOIN users s ON m.sender_id = s.id
        JOIN users r ON m.receiver_id = r.id
        ORDER BY m.created_at ASC
    ")->fetchAll();
    
    echo "<h3>الرسائل الحالية:</h3>";
    echo "<table border='1' style='width: 100%; border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>المرسل</th><th>المستقبل</th><th>المحتوى</th><th>التاريخ</th></tr>";
    
    foreach ($messages as $msg) {
        echo "<tr>";
        echo "<td>{$msg['id']}</td>";
        echo "<td>{$msg['sender_name']} (ID: {$msg['sender_id']})</td>";
        echo "<td>{$msg['receiver_name']} (ID: {$msg['receiver_id']})</td>";
        echo "<td>{$msg['content']}</td>";
        echo "<td>{$msg['created_at']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} catch (PDOException $e) {
    echo "<p>خطأ في استرجاع الرسائل: " . $e->getMessage() . "</p>";
}

echo "<h3>تسجيل الدخول كمستخدم اختباري:</h3>";
echo "<form method='post' action='login_test.php'>";
echo "<select name='username'>";
foreach ($users as $user) {
    echo "<option value='{$user['username']}'>{$user['username']}</option>";
}
echo "</select>";
echo "<input type='hidden' name='password' value='user1pass'>";
echo "<button type='submit'>تسجيل الدخول</button>";
echo "</form>";

$login_test_content = <<<'EOT'
<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=wep_db;charset=utf8mb4", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // تسجيل الدخول مباشرة للاختبار
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            
            header('Location: chat.php');
            exit;
        }
    } catch (PDOException $e) {
        echo "خطأ في تسجيل الدخول: " . $e->getMessage();
    }
}

header('Location: chat.php');
EOT;

file_put_contents('login_test.php', $login_test_content);
echo "<p>تم إنشاء ملف login_test.php بنجاح</p>";

// إضافة زر للعودة إلى صفحة المحادثة
echo "<p><a href='chat.php' style='display: inline-block; padding: 10px 20px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 5px;'>العودة إلى صفحة المحادثة</a></p>";
?>
