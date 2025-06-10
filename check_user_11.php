<?php
session_start();
require_once 'config.php';

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'><title>فحص المستخدم 11</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f0f2f5;} .container{max-width:800px;margin:0 auto;background:white;padding:20px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);} .success{color:#28a745;} .error{color:#dc3545;} .info{color:#007bff;} .section{margin:20px 0;padding:15px;border:1px solid #ddd;border-radius:8px;} .btn{padding:10px 20px;background:#007bff;color:white;border:none;border-radius:5px;cursor:pointer;margin:5px;} .btn:hover{background:#0056b3;}</style>";
echo "</head><body>";

echo "<div class='container'>";
echo "<h1>🔍 فحص المستخدم 11 (yoyo1)</h1>";

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div class='section'>";
    echo "<h2>👤 بيانات المستخدم:</h2>";
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = 11");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "<div class='success'>✅ المستخدم موجود</div>";
        echo "<p><strong>ID:</strong> {$user['id']}</p>";
        echo "<p><strong>Username:</strong> {$user['username']}</p>";
        echo "<p><strong>Email:</strong> {$user['email']}</p>";
        echo "<p><strong>First Name:</strong> " . ($user['first_name'] ?: 'غير محدد') . "</p>";
        echo "<p><strong>Last Name:</strong> " . ($user['last_name'] ?: 'غير محدد') . "</p>";
    } else {
        echo "<div class='error'>❌ المستخدم غير موجود</div>";
        exit;
    }
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h2>📝 المنشورات:</h2>";
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = 11");
    $stmt->execute();
    $posts_count = $stmt->fetchColumn();
    
    echo "<p><strong>عدد المنشورات:</strong> $posts_count</p>";
    
    if ($posts_count == 0) {
        echo "<div class='info'>💡 لا توجد منشورات - سيتم إنشاء منشورات تجريبية</div>";
        
        $sample_posts = [
            "مرحباً! هذا أول منشور لي 🎉",
            "أحب البرمجة والتطوير 💻",
            "يوم جميل للتعلم والإبداع ✨",
            "شاركوني أفكاركم وتجاربكم 💭",
            "الحياة جميلة مع الأصدقاء ❤️"
        ];
        
        foreach ($sample_posts as $content) {
            $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, created_at) VALUES (?, ?, ?)");
            $created_at = date('Y-m-d H:i:s', strtotime("-" . rand(1, 10) . " hours"));
            $stmt->execute([11, $content, $created_at]);
        }
        
        echo "<div class='success'>✅ تم إنشاء 5 منشورات تجريبية</div>";
        $posts_count = 5;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE user_id = 11 ORDER BY created_at DESC LIMIT 3");
    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>آخر المنشورات:</h3>";
    foreach ($posts as $post) {
        echo "<div style='border:1px solid #ddd;padding:10px;margin:5px 0;border-radius:5px;background:#f9f9f9;'>";
        echo "<p>" . htmlspecialchars($post['content']) . "</p>";
        echo "<small>تاريخ النشر: " . $post['created_at'] . "</small>";
        echo "</div>";
    }
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h2>👥 المتابعون:</h2>";
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE followed_id = 11");
    $stmt->execute();
    $followers_count = $stmt->fetchColumn();
    
    echo "<p><strong>عدد المتابعين:</strong> $followers_count</p>";
    
    if ($followers_count == 0) {
        echo "<div class='info'>💡 لا يوجد متابعون - سيتم إنشاء متابعين تجريبيين</div>";
        
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id != 11 LIMIT 3");
        $stmt->execute();
        $other_users = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($other_users)) {
            foreach ($other_users as $follower_id) {
                try {
                    $stmt = $pdo->prepare("INSERT IGNORE INTO followers (follower_id, followed_id) VALUES (?, ?)");
                    $stmt->execute([$follower_id, 11]);
                } catch (Exception $e) {
                }
            }
            echo "<div class='success'>✅ تم إنشاء متابعين تجريبيين</div>";
            $followers_count = count($other_users);
        }
    }
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h2>➡️ المتابَعون:</h2>";
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE follower_id = 11");
    $stmt->execute();
    $following_count = $stmt->fetchColumn();
    
    echo "<p><strong>عدد المتابَعين:</strong> $following_count</p>";
    
    if ($following_count == 0) {
        echo "<div class='info'>💡 لا يتابع أحداً - سيتم إنشاء متابعات تجريبية</div>";
        
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id != 11 LIMIT 2");
        $stmt->execute();
        $other_users = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($other_users)) {
            foreach ($other_users as $followed_id) {
                try {
                    $stmt = $pdo->prepare("INSERT IGNORE INTO followers (follower_id, followed_id) VALUES (?, ?)");
                    $stmt->execute([11, $followed_id]);
                } catch (Exception $e) {
                }
            }
            echo "<div class='success'>✅ تم إنشاء متابعات تجريبية</div>";
            $following_count = count($other_users);
        }
    }
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h2>📊 الإحصائيات النهائية:</h2>";
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = 11");
    $stmt->execute();
    $final_posts = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE followed_id = 11");
    $stmt->execute();
    $final_followers = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE follower_id = 11");
    $stmt->execute();
    $final_following = $stmt->fetchColumn();
    
    echo "<div style='display:flex;gap:20px;'>";
    echo "<div style='text-align:center;padding:15px;background:#e3f2fd;border-radius:8px;'>";
    echo "<h3 style='margin:0;color:#1976d2;'>$final_posts</h3>";
    echo "<p style='margin:5px 0;'>منشورات</p>";
    echo "</div>";
    
    echo "<div style='text-align:center;padding:15px;background:#e8f5e8;border-radius:8px;'>";
    echo "<h3 style='margin:0;color:#388e3c;'>$final_followers</h3>";
    echo "<p style='margin:5px 0;'>متابعون</p>";
    echo "</div>";
    
    echo "<div style='text-align:center;padding:15px;background:#fff3e0;border-radius:8px;'>";
    echo "<h3 style='margin:0;color:#f57c00;'>$final_following</h3>";
    echo "<p style='margin:5px 0;'>متابَعون</p>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h2>🔧 اختبار API:</h2>";
    echo "<button class='btn' onclick='testAPI()'>اختبار API</button>";
    echo "<div id='api-results' style='margin-top:10px;'></div>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ خطأ: " . $e->getMessage() . "</div>";
}

echo "<div style='margin-top:20px;'>";
echo "<a href='u.php' class='btn'>الذهاب للصفحة الشخصية</a>";
echo "<a href='test_post_simple.php' class='btn'>اختبار إنشاء المنشورات</a>";
echo "</div>";

echo "</div>";

echo "<script>
async function testAPI() {
    const resultsDiv = document.getElementById('api-results');
    resultsDiv.innerHTML = '<p>🔄 جاري الاختبار...</p>';
    
    try {
        // اختبار posts_fixed.php
        const postsResponse = await fetch('api/posts_fixed.php?action=get_posts&user_id=11');
        const postsResult = await postsResponse.json();
        
        // اختبار social.php
        const socialResponse = await fetch('api/social.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'get_user_stats', user_id: 11})
        });
        const socialResult = await socialResponse.json();
        
        resultsDiv.innerHTML = `
            <h4>نتائج الاختبار:</h4>
            <p><strong>posts_fixed.php:</strong> ${postsResponse.ok ? '✅ يعمل' : '❌ لا يعمل'}</p>
            <p><strong>social.php:</strong> ${socialResponse.ok ? '✅ يعمل' : '❌ لا يعمل'}</p>
            <details>
                <summary>تفاصيل posts API</summary>
                <pre>${JSON.stringify(postsResult, null, 2)}</pre>
            </details>
            <details>
                <summary>تفاصيل social API</summary>
                <pre>${JSON.stringify(socialResult, null, 2)}</pre>
            </details>
        `;
    } catch (error) {
        resultsDiv.innerHTML = `<p style='color:red;'>❌ خطأ في الاختبار: ${error.message}</p>`;
    }
}
</script>";

echo "</body></html>";
?> 