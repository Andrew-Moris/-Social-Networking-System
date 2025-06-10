<?php
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'wep_db';

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

echo "<h1>تحديث قاعدة البيانات</h1>";

$alterQueries = [
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS birthdate DATE DEFAULT NULL",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS interests TEXT DEFAULT NULL",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_completed TINYINT(1) DEFAULT 0"
];

echo "<h2>إضافة الحقول الجديدة</h2>";
echo "<ul>";

foreach ($alterQueries as $query) {
    if ($conn->query($query)) {
        echo "<li style='color: green;'>تم تنفيذ الاستعلام بنجاح: " . htmlspecialchars($query) . "</li>";
    } else {
        echo "<li style='color: red;'>خطأ في تنفيذ الاستعلام: " . htmlspecialchars($query) . " - " . $conn->error . "</li>";
    }
}

echo "</ul>";

$createPostsTable = "CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($createPostsTable)) {
    echo "<p style='color: green;'>تم إنشاء جدول المنشورات بنجاح!</p>";
} else {
    echo "<p style='color: red;'>خطأ في إنشاء جدول المنشورات: " . $conn->error . "</p>";
}

echo "<h2>إضافة بيانات اختبار</h2>";

$checkUser = $conn->prepare("SELECT id FROM users WHERE username = ?");
$testUsername = "ahmedramadan";
$checkUser->bind_param("s", $testUsername);
$checkUser->execute();
$result = $checkUser->get_result();

if ($result->num_rows == 0) {
    $createUser = $conn->prepare("INSERT INTO users (username, email, password, first_name, last_name, birthdate, bio, interests, profile_completed) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
    
    $email = "ahmed.ramadan@sut.edu.eg";
    $password = password_hash("password123", PASSWORD_DEFAULT);
    $firstName = "أحمد";
    $lastName = "رمضان";
    $birthdate = "1995-05-15";
    $bio = "طالب هندسة ذكاء. عاشق للبرمجة والتصميم أبحث عن الإبداع دائمًا!";
    $interests = "البرمجة، الذكاء الاصطناعي، التصميم";
    
    $createUser->bind_param("ssssssss", $testUsername, $email, $password, $firstName, $lastName, $birthdate, $bio, $interests);
    
    if ($createUser->execute()) {
        $userId = $conn->insert_id;
        echo "<p style='color: green;'>تم إنشاء مستخدم اختبار بنجاح: <strong>ahmedramadan</strong> (كلمة المرور: password123)</p>";
        
        $posts = [
            "شاركت في مسابقة البرمجة الجامعية وحصلت على المركز الأول! 🏆 سعيد جداً بهذا الإنجاز وأشكر كل من دعمني خلال فترة التدريب.",
            "تم الإعلان عن موعد الامتحانات النهائية! هل أنتم مستعدون؟ 📚✨",
            "اليوم حضرت محاضرة رائعة عن الذكاء الاصطناعي وتعلمت الكثير من المفاهيم الجديدة. أنصح الجميع بالاطلاع على هذا المجال المثير!"
        ];
        
        $postStmt = $conn->prepare("INSERT INTO posts (user_id, content) VALUES (?, ?)");
        
        foreach ($posts as $post) {
            $postStmt->bind_param("is", $userId, $post);
            $postStmt->execute();
        }
        
        echo "<p style='color: green;'>تم إضافة " . count($posts) . " منشورات للمستخدم</p>";
    } else {
        echo "<p style='color: red;'>خطأ في إنشاء مستخدم اختبار: " . $createUser->error . "</p>";
    }
} else {
    echo "<p>المستخدم 'ahmedramadan' موجود بالفعل في قاعدة البيانات</p>";
    
    $userId = $result->fetch_assoc()['id'];
    $updateUser = $conn->prepare("UPDATE users SET 
        first_name = ?,
        last_name = ?,
        birthdate = ?,
        bio = ?,
        interests = ?,
        profile_completed = 1
        WHERE id = ?");
    
    $firstName = "أحمد";
    $lastName = "رمضان";
    $birthdate = "1995-05-15";
    $bio = "طالب هندسة ذكاء. عاشق للبرمجة والتصميم أبحث عن الإبداع دائمًا!";
    $interests = "البرمجة، الذكاء الاصطناعي، التصميم";
    
    $updateUser->bind_param("sssssi", $firstName, $lastName, $birthdate, $bio, $interests, $userId);
    
    if ($updateUser->execute()) {
        echo "<p style='color: green;'>تم تحديث بيانات المستخدم بنجاح</p>";
    } else {
        echo "<p style='color: red;'>خطأ في تحديث بيانات المستخدم: " . $updateUser->error . "</p>";
    }
    
    $checkPosts = $conn->prepare("SELECT COUNT(*) as post_count FROM posts WHERE user_id = ?");
    $checkPosts->bind_param("i", $userId);
    $checkPosts->execute();
    $postCount = $checkPosts->get_result()->fetch_assoc()['post_count'];
    
    if ($postCount == 0) {
        $posts = [
            "شاركت في مسابقة البرمجة الجامعية وحصلت على المركز الأول! 🏆 سعيد جداً بهذا الإنجاز وأشكر كل من دعمني خلال فترة التدريب.",
            "تم الإعلان عن موعد الامتحانات النهائية! هل أنتم مستعدون؟ 📚✨",
            "اليوم حضرت محاضرة رائعة عن الذكاء الاصطناعي وتعلمت الكثير من المفاهيم الجديدة. أنصح الجميع بالاطلاع على هذا المجال المثير!"
        ];
        
        $postStmt = $conn->prepare("INSERT INTO posts (user_id, content) VALUES (?, ?)");
        
        foreach ($posts as $post) {
            $postStmt->bind_param("is", $userId, $post);
            $postStmt->execute();
        }
        
        echo "<p style='color: green;'>تم إضافة " . count($posts) . " منشورات للمستخدم</p>";
    } else {
        echo "<p>يوجد بالفعل " . $postCount . " منشورات للمستخدم</p>";
    }
}

echo "<div style='margin-top: 20px;'>";
echo "<a href='/WEP/frontend/login.html' style='padding: 10px 20px; background: linear-gradient(135deg, #f9f871 0%, #00bfff 100%); color: white; text-decoration: none; border-radius: 5px; font-weight: bold;'>العودة إلى صفحة تسجيل الدخول</a>";
echo "</div>";

$conn->close();
?>
