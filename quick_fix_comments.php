<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = 11");
        $stmt->execute();
        $user = $stmt->fetch();
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
        }
    } catch (Exception $e) {
        echo "خطأ في تسجيل الدخول: " . $e->getMessage();
        exit;
    }
}

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'><title>إصلاح سريع للتعليقات</title>";
echo "<style>
body{font-family:Arial;padding:20px;background:#1a1a1a;color:white;} 
.container{max-width:800px;margin:0 auto;background:#2a2a2a;padding:20px;border-radius:10px;} 
.success{color:#4CAF50;} 
.error{color:#f44336;} 
.info{color:#2196F3;} 
.btn{padding:10px 20px;background:#007bff;color:white;border:none;border-radius:5px;cursor:pointer;margin:5px;} 
.btn:hover{background:#0056b3;}
</style>";
echo "</head><body>";

echo "<div class='container'>";
echo "<h1>🔧 إصلاح سريع للتعليقات</h1>";

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM comments");
    $stmt->execute();
    $total_comments = $stmt->fetchColumn();
    
    echo "<p class='info'>📊 إجمالي التعليقات في النظام: $total_comments</p>";
    
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE user_id = 11 ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($post) {
        $post_id = $post['id'];
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE post_id = ?");
        $stmt->execute([$post_id]);
        $post_comments = $stmt->fetchColumn();
        
        echo "<p class='info'>💬 تعليقات المنشور $post_id: $post_comments</p>";
        
        if ($post_comments == 0) {
            $test_comments = [
                "🎉 تعليق رائع! أحب هذا المنشور",
                "👍 موافق تماماً مع ما قلته", 
                "💯 محتوى ممتاز، شكراً للمشاركة"
            ];
            
            foreach ($test_comments as $comment) {
                $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$post_id, 11, $comment]);
            }
            
            echo "<p class='success'>✅ تم إضافة " . count($test_comments) . " تعليقات تجريبية</p>";
        }
        
        echo "<h2>🔗 اختبار API:</h2>";
        echo "<button onclick='testAPI($post_id)' class='btn'>اختبار جلب التعليقات</button>";
        echo "<div id='result'></div>";
        
        echo "<script>
        async function testAPI(postId) {
            const resultDiv = document.getElementById('result');
            resultDiv.innerHTML = '<p>🔄 جاري الاختبار...</p>';
            
            try {
                const response = await fetch(`api/social.php?action=get_comments&post_id=\${postId}`);
                const result = await response.json();
                
                console.log('API Response:', result);
                
                if (result.success) {
                    const comments = result.data.comments || [];
                    resultDiv.innerHTML = `
                        <div style='color:#4CAF50;margin:10px 0;'>
                            ✅ API يعمل بنجاح!<br>
                            عدد التعليقات: \${comments.length}
                        </div>
                        <div style='background:#333;padding:10px;border-radius:5px;margin:10px 0;'>
                            <pre>\${JSON.stringify(result, null, 2)}</pre>
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div style='color:#f44336;margin:10px 0;'>
                            ❌ خطأ في API: \${result.message || 'خطأ غير معروف'}
                        </div>
                        <div style='background:#333;padding:10px;border-radius:5px;margin:10px 0;'>
                            <pre>\${JSON.stringify(result, null, 2)}</pre>
                        </div>
                    `;
                }
            } catch (error) {
                resultDiv.innerHTML = `
                    <div style='color:#f44336;margin:10px 0;'>
                        ❌ خطأ في الاتصال: \${error.message}
                    </div>
                `;
                console.error('Error:', error);
            }
        }
        
        // اختبار تلقائي
        setTimeout(() => testAPI($post_id), 1000);
        </script>";
        
    } else {
        echo "<p class='error'>❌ لا توجد منشورات للاختبار</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ خطأ: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>🔗 روابط مفيدة:</h2>";
echo "<p><a href='u.php' target='_blank' style='color:#007bff;'>🔗 افتح صفحة u.php</a></p>";
echo "<p><a href='debug_u_comments.php' target='_blank' style='color:#007bff;'>🔗 صفحة التشخيص المتقدمة</a></p>";

echo "</div>";
echo "</body></html>";
?> 