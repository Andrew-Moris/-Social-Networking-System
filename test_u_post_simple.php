<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'admin';
    $_SESSION['first_name'] = 'Admin';
    $_SESSION['last_name'] = 'User';
}

if ($_POST['test_post'] ?? false) {
    $formData = [
        'action' => 'create_post',
        'content' => 'Test post from u.php - ' . date('Y-m-d H:i:s')
    ];
    
    $_POST = $formData;
    
    ob_start();
    include 'api/posts_fixed.php';
    $result = ob_get_clean();
    
    echo "<h3>Result:</h3><pre>" . htmlspecialchars($result) . "</pre>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Test U.php Post Creation</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 20px; border: 1px solid #ddd; }
        button { padding: 10px 20px; margin: 10px; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <h1>Test U.php Post Creation</h1>
    
    <div class="test-section">
        <h2>1. Test Direct API Call</h2>
        <form method="post">
            <input type="hidden" name="test_post" value="1">
            <button type="submit">Test Direct Post Creation</button>
        </form>
    </div>
    
    <div class="test-section">
        <h2>2. Test JavaScript Form Submission</h2>
        <form id="testForm">
            <textarea name="content" placeholder="Enter test content..." rows="3" style="width: 100%; margin: 10px 0;"></textarea>
            <button type="submit">Test JavaScript Post</button>
        </form>
        <div id="result"></div>
    </div>
    
    <div class="test-section">
        <h2>3. Test Session Info</h2>
        <p><strong>User ID:</strong> <?php echo $_SESSION['user_id'] ?? 'Not set'; ?></p>
        <p><strong>Username:</strong> <?php echo $_SESSION['username'] ?? 'Not set'; ?></p>
    </div>
    
    <div class="test-section">
        <h2>4. Links</h2>
        <a href="u.php?username=admin">Go to U.php Profile</a> |
        <a href="test_u_posts.php">Go to Original Test</a>
    </div>

    <script>
        document.getElementById('testForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'create_post');
            
            const resultDiv = document.getElementById('result');
            resultDiv.innerHTML = '<p>Testing...</p>';
            
            try {
                const response = await fetch('api/posts_fixed.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    resultDiv.innerHTML = '<p class="success">✅ Post created successfully!</p><pre>' + JSON.stringify(result, null, 2) + '</pre>';
                } else {
                    resultDiv.innerHTML = '<p class="error">❌ Failed: ' + result.message + '</p><pre>' + JSON.stringify(result, null, 2) + '</pre>';
                }
            } catch (error) {
                resultDiv.innerHTML = '<p class="error">❌ Error: ' + error.message + '</p>';
                console.error('Error:', error);
            }
        });
    </script>
</body>
</html> 