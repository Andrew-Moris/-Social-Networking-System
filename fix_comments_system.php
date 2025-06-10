<?php
require_once 'config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Fix Comments System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Fix Comments System</h1>";

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p class='success'>✅ Database connected successfully</p>";
    
    echo "<h2>1. Checking Comments Table</h2>";
    try {
        $pdo->query("SELECT 1 FROM comments LIMIT 1");
        echo "<p class='success'>✅ Comments table exists</p>";
    } catch (Exception $e) {
        echo "<p class='error'>❌ Comments table missing, creating...</p>";
        $sql = "CREATE TABLE comments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            user_id INT NOT NULL,
            content TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_post_id (post_id),
            INDEX idx_user_id (user_id),
            INDEX idx_created_at (created_at)
        )";
        $pdo->exec($sql);
        echo "<p class='success'>✅ Comments table created</p>";
    }
    
    echo "<h2>2. Checking Comment Likes Table</h2>";
    try {
        $pdo->query("SELECT 1 FROM comment_likes LIMIT 1");
        echo "<p class='success'>✅ Comment_likes table exists</p>";
    } catch (Exception $e) {
        echo "<p class='error'>❌ Comment_likes table missing, creating...</p>";
        $sql = "CREATE TABLE comment_likes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            comment_id INT NOT NULL,
            user_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_comment_like (comment_id, user_id),
            FOREIGN KEY (comment_id) REFERENCES comments(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_comment_id (comment_id),
            INDEX idx_user_id (user_id)
        )";
        $pdo->exec($sql);
        echo "<p class='success'>✅ Comment_likes table created</p>";
    }
    
    echo "<h2>3. Checking Comment Dislikes Table</h2>";
    try {
        $pdo->query("SELECT 1 FROM comment_dislikes LIMIT 1");
        echo "<p class='success'>✅ Comment_dislikes table exists</p>";
    } catch (Exception $e) {
        echo "<p class='error'>❌ Comment_dislikes table missing, creating...</p>";
        $sql = "CREATE TABLE comment_dislikes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            comment_id INT NOT NULL,
            user_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_comment_dislike (comment_id, user_id),
            FOREIGN KEY (comment_id) REFERENCES comments(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_comment_id (comment_id),
            INDEX idx_user_id (user_id)
        )";
        $pdo->exec($sql);
        echo "<p class='success'>✅ Comment_dislikes table created</p>";
    }
    
    echo "<h2>4. Checking Post Dislikes Table</h2>";
    try {
        $pdo->query("SELECT 1 FROM post_dislikes LIMIT 1");
        echo "<p class='success'>✅ Post_dislikes table exists</p>";
    } catch (Exception $e) {
        echo "<p class='error'>❌ Post_dislikes table missing, creating...</p>";
        $sql = "CREATE TABLE post_dislikes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            user_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_post_dislike (post_id, user_id),
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_post_id (post_id),
            INDEX idx_user_id (user_id)
        )";
        $pdo->exec($sql);
        echo "<p class='success'>✅ Post_dislikes table created</p>";
    }
    
    echo "<h2>5. Test Comment Creation</h2>";
    
    $stmt = $pdo->query("SELECT id FROM posts ORDER BY created_at DESC LIMIT 1");
    $post = $stmt->fetch();
    
    if ($post) {
        $test_content = "Test comment - " . date('Y-m-d H:i:s');
        $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content, created_at) VALUES (?, 1, ?, NOW())");
        $stmt->execute([$post['id'], $test_content]);
        echo "<p class='success'>✅ Test comment created successfully</p>";
        echo "<p class='info'>Post ID: {$post['id']}, Content: {$test_content}</p>";
    } else {
        echo "<p class='error'>❌ No posts found for testing</p>";
    }
    
    echo "<h2>6. Statistics</h2>";
    
    $tables = ['comments', 'comment_likes', 'comment_dislikes', 'posts', 'users'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch()['count'];
            echo "<p class='info'>$table: $count records</p>";
        } catch (Exception $e) {
            echo "<p class='error'>$table: Error - " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<h2>7. Test Links</h2>";
    echo "<a href='test_comments_debug.php'>Test Comments Debug</a> | ";
    echo "<a href='u.php?username=admin'>Go to U.php Profile</a>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?> 