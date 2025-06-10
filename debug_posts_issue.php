<?php

session_start();
require_once 'config.php';

echo "<h1>🔍 فحص مشكلة البوستات</h1>";

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>1. التحقق من جدول البوستات:</h2>";
    $check_posts_table = $pdo->query("SHOW TABLES LIKE 'posts'");
    if ($check_posts_table->rowCount() > 0) {
        echo "✅ جدول البوستات موجود<br>";
        
        $describe = $pdo->query("DESCRIBE posts");
        echo "<h3>هيكل جدول البوستات:</h3>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($row = $describe->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>{$row['Field']}</td>";
            echo "<td>{$row['Type']}</td>";
            echo "<td>{$row['Null']}</td>";
            echo "<td>{$row['Key']}</td>";
            echo "<td>{$row['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        $count_stmt = $pdo->query("SELECT COUNT(*) FROM posts");
        $posts_count = $count_stmt->fetchColumn();
        echo "<p><strong>عدد البوستات الكلي:</strong> {$posts_count}</p>";
        
        if ($posts_count > 0) {
            echo "<h3>أول 5 بوستات:</h3>";
            $posts_stmt = $pdo->query("
                SELECT p.id, p.content, p.image_url, p.created_at, p.user_id,
                       u.username, u.first_name, u.last_name
                FROM posts p 
                LEFT JOIN users u ON p.user_id = u.id 
                ORDER BY p.created_at DESC 
                LIMIT 5
            ");
            $posts = $posts_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; width: 100%;'>";
            echo "<tr><th>ID</th><th>User</th><th>Content</th><th>Image</th><th>Created</th></tr>";
            foreach ($posts as $post) {
                echo "<tr>";
                echo "<td>{$post['id']}</td>";
                echo "<td>{$post['username']} ({$post['first_name']} {$post['last_name']})</td>";
                echo "<td>" . substr($post['content'], 0, 50) . "...</td>";
                echo "<td>" . ($post['image_url'] ? 'Yes' : 'No') . "</td>";
                echo "<td>{$post['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: red;'>❌ لا توجد بوستات في قاعدة البيانات</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ جدول البوستات غير موجود</p>";
    }
    
    echo "<h2>2. التحقق من المستخدم الحالي:</h2>";
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        echo "<p>✅ المستخدم مسجل دخول - ID: {$user_id}</p>";
        
        $user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $user_stmt->execute([$user_id]);
        $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "<p>✅ بيانات المستخدم: {$user['username']} ({$user['first_name']} {$user['last_name']})</p>";
            
            $user_posts_stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
            $user_posts_stmt->execute([$user_id]);
            $user_posts_count = $user_posts_stmt->fetchColumn();
            echo "<p>عدد بوستات المستخدم: {$user_posts_count}</p>";
            
            $following_stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE follower_id = ?");
            $following_stmt->execute([$user_id]);
            $following_count = $following_stmt->fetchColumn();
            echo "<p>عدد المتابعين: {$following_count}</p>";
            
        } else {
            echo "<p style='color: red;'>❌ لا يمكن العثور على بيانات المستخدم</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ المستخدم غير مسجل دخول</p>";
    }
    
    echo "<h2>3. اختبار استعلام البوستات:</h2>";
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        
        $posts_stmt = $pdo->prepare("
            SELECT p.id, p.content, p.image_url, p.created_at,
                   u.username, u.first_name, u.last_name, u.avatar_url as user_avatar
            FROM posts p 
            JOIN users u ON p.user_id = u.id 
            WHERE p.user_id = ? OR p.user_id IN (
                SELECT followed_id FROM followers WHERE follower_id = ?
            )
            ORDER BY p.created_at DESC 
            LIMIT 10
        ");
        $posts_stmt->execute([$user_id, $user_id]);
        $posts = $posts_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>عدد البوستات المسترجعة: " . count($posts) . "</p>";
        
        if (count($posts) > 0) {
            echo "<h3>البوستات المسترجعة:</h3>";
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; width: 100%;'>";
            echo "<tr><th>ID</th><th>User</th><th>Content</th><th>Created</th></tr>";
            foreach ($posts as $post) {
                echo "<tr>";
                echo "<td>{$post['id']}</td>";
                echo "<td>{$post['username']}</td>";
                echo "<td>" . substr($post['content'], 0, 100) . "...</td>";
                echo "<td>{$post['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: orange;'>⚠️ لا توجد بوستات للعرض (لا توجد بوستات للمستخدم أو للمتابعين)</p>";
            
            echo "<h3>اختبار عرض جميع البوستات:</h3>";
            $all_posts_stmt = $pdo->query("
                SELECT p.id, p.content, p.created_at, u.username
                FROM posts p 
                JOIN users u ON p.user_id = u.id 
                ORDER BY p.created_at DESC 
                LIMIT 5
            ");
            $all_posts = $all_posts_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($all_posts) > 0) {
                echo "<p>✅ يمكن استرجاع البوستات بشكل عام</p>";
                foreach ($all_posts as $post) {
                    echo "<p>- {$post['username']}: " . substr($post['content'], 0, 50) . "...</p>";
                }
            } else {
                echo "<p style='color: red;'>❌ لا يمكن استرجاع أي بوستات</p>";
            }
        }
    }
    
    echo "<h2>4. التحقق من جدول المتابعين:</h2>";
    $check_followers_table = $pdo->query("SHOW TABLES LIKE 'followers'");
    if ($check_followers_table->rowCount() > 0) {
        echo "✅ جدول المتابعين موجود<br>";
        
        $followers_count = $pdo->query("SELECT COUNT(*) FROM followers")->fetchColumn();
        echo "<p>عدد علاقات المتابعة: {$followers_count}</p>";
        
        if ($followers_count > 0) {
            $followers_stmt = $pdo->query("
                SELECT f.*, 
                       u1.username as follower_username,
                       u2.username as followed_username
                FROM followers f
                JOIN users u1 ON f.follower_id = u1.id
                JOIN users u2 ON f.followed_id = u2.id
                LIMIT 5
            ");
            $followers = $followers_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h3>أول 5 علاقات متابعة:</h3>";
            foreach ($followers as $follow) {
                echo "<p>- {$follow['follower_username']} يتابع {$follow['followed_username']}</p>";
            }
        }
    } else {
        echo "<p style='color: red;'>❌ جدول المتابعين غير موجود</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ خطأ: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>🔧 الحلول المقترحة:</h2>";
echo "<ol>";
echo "<li>إذا لم تكن هناك بوستات، قم بإنشاء بوست جديد</li>";
echo "<li>إذا كان هناك بوستات لكن لا تظهر، تحقق من علاقات المتابعة</li>";
echo "<li>إذا كانت المشكلة في العرض، تحقق من CSS في home.php</li>";
echo "<li>للمحادثة، تحقق من وجود جدول messages وAPI المحادثة</li>";
echo "</ol>";

echo "<p><a href='home.php'>← العودة للصفحة الرئيسية</a></p>";
?> 