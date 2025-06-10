<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'admin';
    $_SESSION['first_name'] = 'Admin';
    $_SESSION['last_name'] = 'User';
}

echo "<!DOCTYPE html>
<html>
<head>
    <title>Test U.php Posts</title>
    <script>
        console.log('Testing u.php posts functionality...');
        
        // Test form submission
        function testPostCreation() {
            const formData = new FormData();
            formData.append('action', 'create_post');
            formData.append('content', 'Test post from u.php - ' + new Date().toLocaleString());
            
            fetch('api/posts_fixed.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                console.log('Post creation result:', result);
                if (result.success) {
                    alert('Post created successfully!');
                } else {
                    alert('Failed to create post: ' + result.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error creating post');
            });
        }
        
        // Test when page loads
        window.onload = function() {
            console.log('Page loaded, testing post creation...');
            testPostCreation();
        };
    </script>
</head>
<body>
    <h1>Testing U.php Posts</h1>
    <p>Check console for results...</p>
    <button onclick='testPostCreation()'>Test Post Creation</button>
    <br><br>
    <a href='u.php?username=admin'>Go to Admin Profile</a>
</body>
</html>";
?> 