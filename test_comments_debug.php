<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'admin';
    $_SESSION['first_name'] = 'Admin';
    $_SESSION['last_name'] = 'User';
}

require_once 'config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Test Comments Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 20px; border: 1px solid #ddd; }
        .success { color: green; }
        .error { color: red; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Comments Debug Test</h1>";

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div class='test-section'>
        <h2>1. Database Connection</h2>
        <p class='success'>✅ Database connected successfully</p>
    </div>";
    
    echo "<div class='test-section'>
        <h2>2. Comments Table Check</h2>";
    
    try {
        $stmt = $pdo->query("DESCRIBE comments");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p class='success'>✅ Comments table exists</p>";
        echo "<h3>Table Structure:</h3><pre>";
        foreach ($columns as $column) {
            echo $column['Field'] . " - " . $column['Type'] . " - " . $column['Null'] . " - " . $column['Default'] . "\n";
        }
        echo "</pre>";
    } catch (Exception $e) {
        echo "<p class='error'>❌ Comments table error: " . $e->getMessage() . "</p>";
    }
    
    echo "</div>";
    
    echo "<div class='test-section'>
        <h2>3. Available Posts</h2>";
    
    try {
        $stmt = $pdo->query("SELECT id, content, user_id FROM posts ORDER BY created_at DESC LIMIT 5");
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($posts)) {
            echo "<p class='error'>❌ No posts found</p>";
        } else {
            echo "<p class='success'>✅ Found " . count($posts) . " posts</p>";
            echo "<h3>Recent Posts:</h3><pre>";
            foreach ($posts as $post) {
                echo "ID: {$post['id']} - User: {$post['user_id']} - Content: " . substr($post['content'], 0, 50) . "...\n";
            }
            echo "</pre>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Posts query error: " . $e->getMessage() . "</p>";
    }
    
    echo "</div>";
    
    echo "<div class='test-section'>
        <h2>4. Test Add Comment</h2>";
    
    if (!empty($posts)) {
        $test_post_id = $posts[0]['id'];
        echo "<form id='commentForm'>
            <input type='hidden' name='post_id' value='{$test_post_id}'>
            <textarea name='content' placeholder='Enter test comment...' rows='3' style='width: 100%; margin: 10px 0;'></textarea>
            <button type='submit'>Test Add Comment</button>
        </form>
        <div id='commentResult'></div>";
    } else {
        echo "<p class='error'>❌ No posts available for testing</p>";
    }
    
    echo "</div>";
    
    echo "<div class='test-section'>
        <h2>5. Existing Comments</h2>";
    
    try {
        $stmt = $pdo->query("
            SELECT c.*, u.username 
            FROM comments c 
            JOIN users u ON c.user_id = u.id 
            ORDER BY c.created_at DESC 
            LIMIT 10
        ");
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($comments)) {
            echo "<p>No comments found</p>";
        } else {
            echo "<p class='success'>✅ Found " . count($comments) . " comments</p>";
            echo "<h3>Recent Comments:</h3><pre>";
            foreach ($comments as $comment) {
                echo "ID: {$comment['id']} - Post: {$comment['post_id']} - User: {$comment['username']} - Content: " . substr($comment['content'], 0, 50) . "...\n";
            }
            echo "</pre>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Comments query error: " . $e->getMessage() . "</p>";
    }
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='test-section'>
        <h2>Database Error</h2>
        <p class='error'>❌ " . $e->getMessage() . "</p>
    </div>";
}

echo "
    <div class='test-section'>
        <h2>6. Links</h2>
        <a href='u.php?username=admin'>Go to U.php Profile</a> |
        <a href='test_u_post_simple.php'>Go to Post Test</a>
    </div>

    <script>
        document.getElementById('commentForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = {
                action: 'add_comment',
                post_id: parseInt(formData.get('post_id')),
                content: formData.get('content')
            };
            
            const resultDiv = document.getElementById('commentResult');
            resultDiv.innerHTML = '<p>Testing...</p>';
            
            try {
                const response = await fetch('api/social.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    resultDiv.innerHTML = '<p class=\"success\">✅ Comment added successfully!</p><pre>' + JSON.stringify(result, null, 2) + '</pre>';
                    this.reset();
                } else {
                    resultDiv.innerHTML = '<p class=\"error\">❌ Failed: ' + result.message + '</p><pre>' + JSON.stringify(result, null, 2) + '</pre>';
                }
            } catch (error) {
                resultDiv.innerHTML = '<p class=\"error\">❌ Error: ' + error.message + '</p>';
                console.error('Error:', error);
            }
        });
    </script>
</body>
</html>";
?> 