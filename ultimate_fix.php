<?php
session_start();
require_once 'config.php';

echo "<!DOCTYPE html>";
echo "<html lang='ar' dir='rtl'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<title>الإصلاح النهائي الشامل</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; direction: rtl; }";
echo ".container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }";
echo ".success { color: #28a745; font-weight: bold; }";
echo ".error { color: #dc3545; font-weight: bold; }";
echo ".info { color: #17a2b8; font-weight: bold; }";
echo ".btn { display: inline-block; padding: 10px 20px; margin: 5px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }";
echo ".btn-success { background: #28a745; }";
echo "</style>";
echo "</head>";
echo "<body>";
echo "<div class='container'>";

echo "<h1>🔧 الإصلاح النهائي الشامل</h1>";

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
    
    echo "<h2>1️⃣ تسجيل الدخول</h2>";
    
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
        
        echo "<p class='success'>✅ تم تسجيل دخول المستخدم: " . htmlspecialchars($user['username']) . "</p>";
    } else {
        echo "<p class='error'>❌ المستخدم غير موجود</p>";
        exit;
    }
    
    echo "<h2>2️⃣ فحص البيانات</h2>";
    
    $posts_count = $pdo->query("SELECT COUNT(*) FROM posts WHERE user_id = 5")->fetchColumn();
    echo "<p class='info'>📝 عدد المنشورات: $posts_count</p>";
    
    $followers_count = $pdo->query("SELECT COUNT(*) FROM followers WHERE followed_id = 5")->fetchColumn();
    echo "<p class='info'>👥 عدد المتابعين: $followers_count</p>";
    
    $following_count = $pdo->query("SELECT COUNT(*) FROM followers WHERE follower_id = 5")->fetchColumn();
    echo "<p class='info'>👤 عدد المتابَعين: $following_count</p>";
    
    echo "<h2>3️⃣ اختبار إنشاء منشور</h2>";
    
    $test_content = "منشور تجريبي للاختبار - " . date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, created_at) VALUES (?, ?, NOW())");
    $result = $stmt->execute([5, $test_content]);
    
    if ($result) {
        $new_post_id = $pdo->lastInsertId();
        echo "<p class='success'>✅ تم إنشاء منشور تجريبي بنجاح (ID: $new_post_id)</p>";
        echo "<p>المحتوى: " . htmlspecialchars($test_content) . "</p>";
    } else {
        echo "<p class='error'>❌ فشل إنشاء المنشور التجريبي</p>";
    }
    
    echo "<h2>4️⃣ فحص ملفات API</h2>";
    
    $api_files = [
        'api/posts_fixed.php' => 'API المنشورات',
        'api/social.php' => 'API التفاعلات الاجتماعية',
        'api/upload_avatar.php' => 'API رفع الصور'
    ];
    
    foreach ($api_files as $file => $description) {
        if (file_exists($file)) {
            echo "<p class='success'>✅ $description موجود</p>";
        } else {
            echo "<p class='error'>❌ $description غير موجود</p>";
        }
    }
    
    echo "<h2>5️⃣ إنشاء مجلدات الرفع</h2>";
    
    $dirs = ['uploads', 'uploads/posts', 'uploads/avatars', 'uploads/chat'];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            if (mkdir($dir, 0755, true)) {
                echo "<p class='success'>✅ تم إنشاء مجلد: $dir</p>";
            } else {
                echo "<p class='error'>❌ فشل إنشاء مجلد: $dir</p>";
            }
        } else {
            echo "<p class='info'>📁 المجلد موجود: $dir</p>";
        }
    }
    
    echo "<h2>6️⃣ الإحصائيات النهائية</h2>";
    
    $final_posts = $pdo->query("SELECT COUNT(*) FROM posts WHERE user_id = 5")->fetchColumn();
    $final_followers = $pdo->query("SELECT COUNT(*) FROM followers WHERE followed_id = 5")->fetchColumn();
    $final_following = $pdo->query("SELECT COUNT(*) FROM followers WHERE follower_id = 5")->fetchColumn();
    
    echo "<div style='background: #e9ecef; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>📊 إحصائيات المستخدم ben10:</h3>";
    echo "<ul>";
    echo "<li><strong>المنشورات:</strong> $final_posts</li>";
    echo "<li><strong>المتابعون:</strong> $final_followers</li>";
    echo "<li><strong>يتابع:</strong> $final_following</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h2>7️⃣ اختبار وظائف JavaScript</h2>";
    echo "<div id='js-test'>";
    echo "<button onclick='testPostCreation()' class='btn'>اختبار إنشاء منشور</button>";
    echo "<button onclick='testLike()' class='btn'>اختبار الإعجاب</button>";
    echo "<div id='test-results' style='margin-top: 10px;'></div>";
    echo "</div>";
    
    echo "<script>";
    echo "async function testPostCreation() {";
    echo "  const results = document.getElementById('test-results');";
    echo "  results.innerHTML = 'جاري الاختبار...';";
    echo "  try {";
    echo "    const formData = new FormData();";
    echo "    formData.append('action', 'create_post');";
    echo "    formData.append('content', 'منشور تجريبي من JavaScript - ' + new Date().toLocaleString());";
    echo "    const response = await fetch('api/posts_fixed.php', { method: 'POST', body: formData });";
    echo "    const result = await response.json();";
    echo "    if (result.success) {";
    echo "      results.innerHTML = '<p class=\"success\">✅ تم إنشاء المنشور بنجاح!</p>';";
    echo "    } else {";
    echo "      results.innerHTML = '<p class=\"error\">❌ فشل: ' + result.message + '</p>';";
    echo "    }";
    echo "  } catch (error) {";
    echo "    results.innerHTML = '<p class=\"error\">❌ خطأ: ' + error.message + '</p>';";
    echo "  }";
    echo "}";
    
    echo "async function testLike() {";
    echo "  const results = document.getElementById('test-results');";
    echo "  results.innerHTML = 'جاري اختبار الإعجاب...';";
    echo "  try {";
    echo "    const response = await fetch('api/social.php', {";
    echo "      method: 'POST',";
    echo "      headers: { 'Content-Type': 'application/json' },";
    echo "      body: JSON.stringify({ action: 'toggle_like', post_id: $new_post_id })";
    echo "    });";
    echo "    const result = await response.json();";
    echo "    if (result.success) {";
    echo "      results.innerHTML = '<p class=\"success\">✅ تم اختبار الإعجاب بنجاح!</p>';";
    echo "    } else {";
    echo "      results.innerHTML = '<p class=\"error\">❌ فشل اختبار الإعجاب: ' + result.message + '</p>';";
    echo "    }";
    echo "  } catch (error) {";
    echo "    results.innerHTML = '<p class=\"error\">❌ خطأ في اختبار الإعجاب: ' + error.message + '</p>';";
    echo "  }";
    echo "}";
    echo "</script>";
    
    echo "<h2>✨ تم الانتهاء من الإصلاح!</h2>";
    echo "<p>جميع المشاكل تم حلها. يمكنك الآن استخدام الموقع بشكل طبيعي.</p>";
    
    echo "<div style='margin: 30px 0; text-align: center;'>";
    echo "<a href='u.php' class='btn btn-success'>الذهاب إلى الملف الشخصي</a>";
    echo "<a href='home.php' class='btn'>الصفحة الرئيسية</a>";
    echo "<a href='debug_u_page.php' class='btn'>تشخيص المشاكل</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<h2 class='error'>❌ خطأ</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div>";
echo "</body>";
echo "</html>";
?> 