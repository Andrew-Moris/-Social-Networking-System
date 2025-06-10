<?php
require_once 'config.php';

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
    echo "Database connected successfully\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $users_count = $stmt->fetchColumn();
    echo "Total users: $users_count\n";
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = 11");
    $stmt->execute();
    $user = $stmt->fetch();
    
    if ($user) {
        echo "User 11 found: {$user['username']}\n";
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = 11");
        $stmt->execute();
        $posts = $stmt->fetchColumn();
        echo "Posts for user 11: $posts\n";
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE followed_id = 11");
        $stmt->execute();
        $followers = $stmt->fetchColumn();
        echo "Followers for user 11: $followers\n";
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE follower_id = 11");
        $stmt->execute();
        $following = $stmt->fetchColumn();
        echo "Following by user 11: $following\n";
        
        if ($posts == 0) {
            echo "Creating test posts...\n";
            $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, created_at) VALUES (11, ?, NOW())");
            $stmt->execute(["Test post 1 🎉"]);
            $stmt->execute(["Test post 2 💻"]);
            $stmt->execute(["Test post 3 ✨"]);
            echo "Created 3 test posts\n";
        }
        
        if ($followers == 0) {
            echo "Creating test followers...\n";
            $stmt = $pdo->query("SELECT id FROM users WHERE id != 11 LIMIT 2");
            $other_users = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($other_users as $follower_id) {
                $stmt = $pdo->prepare("INSERT IGNORE INTO followers (follower_id, followed_id) VALUES (?, 11)");
                $stmt->execute([$follower_id]);
            }
            echo "Created test followers\n";
        }
        
    } else {
        echo "User 11 not found\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 