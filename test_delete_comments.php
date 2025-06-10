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
    <title>Test Delete Comments</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 20px; border: 1px solid #ddd; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        .comment-item { background: #f9f9f9; padding: 10px; margin: 10px 0; border-radius: 5px; }
        .delete-btn { background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; }
        .delete-btn:hover { background: #c82333; }
    </style>
</head>
<body>
    <h1>Test Delete Comments</h1>";

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div class='test-section'>
        <h2>1. Database Connection</h2>
        <p class='success'>✅ Database connected successfully</p>
    </div>";
    
    echo "<div class='test-section'>
        <h2>2. Create Test Comment</h2>";
    
    $stmt = $pdo->query("SELECT id FROM posts ORDER BY created_at DESC LIMIT 1");
    $post = $stmt->fetch();
    
    if ($post) {
        $test_content = "Test comment for deletion - " . date('Y-m-d H:i:s');
        $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content, created_at) VALUES (?, 1, ?, NOW())");
        $stmt->execute([$post['id'], $test_content]);
        $test_comment_id = $pdo->lastInsertId();
        
        echo "<p class='success'>✅ Test comment created with ID: {$test_comment_id}</p>";
        echo "<p class='info'>Post ID: {$post['id']}, Content: {$test_content}</p>";
    } else {
        echo "<p class='error'>❌ No posts found for testing</p>";
    }
    
    echo "</div>";
    
    echo "<div class='test-section'>
        <h2>3. Current Comments</h2>";
    
    try {
        $stmt = $pdo->query("
            SELECT c.*, u.username, p.content as post_content
            FROM comments c 
            JOIN users u ON c.user_id = u.id 
            JOIN posts p ON c.post_id = p.id
            ORDER BY c.created_at DESC 
            LIMIT 10
        ");
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($comments)) {
            echo "<p>No comments found</p>";
        } else {
            echo "<p class='success'>✅ Found " . count($comments) . " comments</p>";
            
            foreach ($comments as $comment) {
                echo "<div class='comment-item'>
                    <strong>Comment ID:</strong> {$comment['id']} | 
                    <strong>Post ID:</strong> {$comment['post_id']} | 
                    <strong>User:</strong> {$comment['username']} | 
                    <strong>Date:</strong> {$comment['created_at']}
                    <br>
                    <strong>Content:</strong> " . htmlspecialchars($comment['content']) . "
                    <br>
                    <strong>Post:</strong> " . htmlspecialchars(substr($comment['post_content'], 0, 50)) . "...
                    <br>
                    <button class='delete-btn' onclick='deleteComment({$comment['id']})'>Delete Comment</button>
                </div>";
            }
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Comments query error: " . $e->getMessage() . "</p>";
    }
    
    echo "</div>";
    
    echo "<div class='test-section'>
        <h2>4. Test Direct Delete</h2>
        <form id='deleteForm'>
            <label>Comment ID to delete:</label>
            <input type='number' name='comment_id' placeholder='Enter comment ID...' required>
            <button type='submit'>Test Delete</button>
        </form>
        <div id='deleteResult'></div>
    </div>";
    
} catch (Exception $e) {
    echo "<div class='test-section'>
        <h2>Database Error</h2>
        <p class='error'>❌ " . $e->getMessage() . "</p>
    </div>";
}

echo "
    <div class='test-section'>
        <h2>5. Links</h2>
        <a href='u.php?username=admin'>Go to U.php Profile</a> |
        <a href='test_comments_debug.php'>Test Comments Debug</a> |
        <a href='fix_comments_system.php'>Fix Comments System</a>
    </div>

    <script>
        // Delete comment function
        async function deleteComment(commentId) {
            if (!confirm('Are you sure you want to delete comment ' + commentId + '?')) return;
            
            try {
                const response = await fetch('api/social.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'delete_comment',
                        comment_id: parseInt(commentId)
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Comment deleted successfully!');
                    location.reload(); // Reload to see changes
                } else {
                    alert('Failed to delete comment: ' + result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error deleting comment');
            }
        }
        
        // Form handler
        document.getElementById('deleteForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const commentId = parseInt(formData.get('comment_id'));
            
            const resultDiv = document.getElementById('deleteResult');
            resultDiv.innerHTML = '<p>Testing...</p>';
            
            try {
                const response = await fetch('api/social.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'delete_comment',
                        comment_id: commentId
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    resultDiv.innerHTML = '<p class=\"success\">✅ Comment deleted successfully!</p>';
                    this.reset();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    resultDiv.innerHTML = '<p class=\"error\">❌ Failed: ' + result.message + '</p>';
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