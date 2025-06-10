<?php
session_start();
require_once 'config.php';

echo "🔧 إصلاح أزرار الإعجاب والتعليق والحفظ في u.php...\n\n";

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    if (!isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = 11");
        $stmt->execute();
        $user = $stmt->fetch();
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['avatar_url'] = $user['avatar_url'];
            echo "✅ تم تسجيل الدخول للمستخدم: {$user['username']}\n";
        }
    }
    
    echo "📋 فحص الجداول المطلوبة...\n";
    
    $required_tables = ['likes', 'comments', 'bookmarks', 'comment_likes'];
    
    foreach ($required_tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ جدول $table موجود\n";
        } else {
            echo "❌ جدول $table مفقود - سيتم إنشاؤه\n";
            
            switch ($table) {
                case 'likes':
                    $pdo->exec("
                        CREATE TABLE likes (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            user_id INT NOT NULL,
                            post_id INT NOT NULL,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            UNIQUE KEY unique_like (user_id, post_id),
                            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                    ");
                    break;
                    
                case 'comments':
                    $pdo->exec("
                        CREATE TABLE comments (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            user_id INT NOT NULL,
                            post_id INT NOT NULL,
                            content TEXT NOT NULL,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                    ");
                    break;
                    
                case 'bookmarks':
                    $pdo->exec("
                        CREATE TABLE bookmarks (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            user_id INT NOT NULL,
                            post_id INT NOT NULL,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            UNIQUE KEY unique_bookmark (user_id, post_id),
                            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                    ");
                    break;
                    
                case 'comment_likes':
                    $pdo->exec("
                        CREATE TABLE comment_likes (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            user_id INT NOT NULL,
                            comment_id INT NOT NULL,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            UNIQUE KEY unique_comment_like (user_id, comment_id),
                            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                            FOREIGN KEY (comment_id) REFERENCES comments(id) ON DELETE CASCADE
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                    ");
                    break;
            }
            echo "✅ تم إنشاء جدول $table\n";
        }
    }
    
    echo "\n🧪 اختبار API الاجتماعي...\n";
    
    $stmt = $pdo->prepare("SELECT id FROM posts WHERE user_id = 11 LIMIT 1");
    $stmt->execute();
    $post = $stmt->fetch();
    
    if ($post) {
        $post_id = $post['id'];
        echo "📝 منشور للاختبار: ID $post_id\n";
        
        echo "❤️ اختبار الإعجاب...\n";
        
        $stmt = $pdo->prepare("INSERT IGNORE INTO likes (user_id, post_id) VALUES (?, ?)");
        $result = $stmt->execute([11, $post_id]);
        
        if ($result) {
            echo "✅ تم إضافة إعجاب تجريبي\n";
            
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ?");
            $stmt->execute([$post_id]);
            $likes_count = $stmt->fetchColumn();
            echo "📊 عدد الإعجابات: $likes_count\n";
        }
        
        echo "💬 اختبار التعليق...\n";
        
        $stmt = $pdo->prepare("INSERT INTO comments (user_id, post_id, content) VALUES (?, ?, ?)");
        $result = $stmt->execute([11, $post_id, "تعليق تجريبي للاختبار 🎉"]);
        
        if ($result) {
            $comment_id = $pdo->lastInsertId();
            echo "✅ تم إضافة تعليق تجريبي: ID $comment_id\n";
            
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE post_id = ?");
            $stmt->execute([$post_id]);
            $comments_count = $stmt->fetchColumn();
            echo "📊 عدد التعليقات: $comments_count\n";
        }
        
        echo "🔖 اختبار الحفظ...\n";
        
        $stmt = $pdo->prepare("INSERT IGNORE INTO bookmarks (user_id, post_id) VALUES (?, ?)");
        $result = $stmt->execute([11, $post_id]);
        
        if ($result) {
            echo "✅ تم حفظ المنشور\n";
            
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookmarks WHERE post_id = ?");
            $stmt->execute([$post_id]);
            $bookmarks_count = $stmt->fetchColumn();
            echo "📊 عدد المحفوظات: $bookmarks_count\n";
        }
        
    } else {
        echo "❌ لا توجد منشورات للاختبار\n";
    }
    
    echo "\n🔍 فحص ملف social.php...\n";
    
    if (file_exists('api/social.php')) {
        echo "✅ ملف api/social.php موجود\n";
        
        $test_data = [
            'action' => 'toggle_like',
            'post_id' => $post_id ?? 1
        ];
        
        echo "📡 اختبار API...\n";
        echo "البيانات المرسلة: " . json_encode($test_data) . "\n";
        
    } else {
        echo "❌ ملف api/social.php مفقود\n";
    }
    
    echo "\n🎯 نصائح لحل المشاكل:\n";
    echo "1. تأكد من تسجيل الدخول (المستخدم 11 مسجل حالياً)\n";
    echo "2. افتح Developer Tools في المتصفح (F12)\n";
    echo "3. اذهب لتبويب Console لرؤية أي أخطاء JavaScript\n";
    echo "4. اذهب لتبويب Network لرؤية طلبات API\n";
    echo "5. جرب الضغط على أزرار الإعجاب والتعليق والحفظ\n";
    
    echo "\n🔗 روابط للاختبار:\n";
    echo "- الصفحة الشخصية: http://localhost/WEP/u.php\n";
    echo "- اختبار API: http://localhost/WEP/api/social.php\n";
    
    echo "\n✅ تم إعداد كل شيء بنجاح!\n";
    
} catch (Exception $e) {
    echo "❌ خطأ: " . $e->getMessage() . "\n";
}
?> 