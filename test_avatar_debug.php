<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'admin';
}

require_once 'config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Test Avatar Upload Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 20px; border: 1px solid #ddd; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        .upload-form { background: #f9f9f9; padding: 20px; border-radius: 5px; }
        .result { margin-top: 20px; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>🔍 Avatar Upload Debug</h1>";

echo "<div class='test-section'>
    <h2>1. PHP Upload Settings</h2>";

$upload_max_filesize = ini_get('upload_max_filesize');
$post_max_size = ini_get('post_max_size');
$max_file_uploads = ini_get('max_file_uploads');
$file_uploads = ini_get('file_uploads');

echo "<p><strong>file_uploads:</strong> " . ($file_uploads ? 'Enabled' : 'Disabled') . "</p>";
echo "<p><strong>upload_max_filesize:</strong> $upload_max_filesize</p>";
echo "<p><strong>post_max_size:</strong> $post_max_size</p>";
echo "<p><strong>max_file_uploads:</strong> $max_file_uploads</p>";

if (!$file_uploads) {
    echo "<p class='error'>❌ File uploads are disabled in PHP!</p>";
} else {
    echo "<p class='success'>✅ File uploads are enabled</p>";
}

echo "</div>";

echo "<div class='test-section'>
    <h2>2. Directory Check</h2>";

$upload_dir = 'uploads/avatars/';
$full_path = __DIR__ . '/' . $upload_dir;

echo "<p><strong>Upload directory:</strong> $upload_dir</p>";
echo "<p><strong>Full path:</strong> $full_path</p>";

if (!is_dir($upload_dir)) {
    echo "<p class='error'>❌ Directory does not exist</p>";
    if (mkdir($upload_dir, 0755, true)) {
        echo "<p class='success'>✅ Directory created successfully</p>";
    } else {
        echo "<p class='error'>❌ Failed to create directory</p>";
    }
} else {
    echo "<p class='success'>✅ Directory exists</p>";
}

if (is_writable($upload_dir)) {
    echo "<p class='success'>✅ Directory is writable</p>";
} else {
    echo "<p class='error'>❌ Directory is not writable</p>";
    chmod($upload_dir, 0755);
    if (is_writable($upload_dir)) {
        echo "<p class='success'>✅ Directory permissions fixed</p>";
    }
}

echo "</div>";

echo "<div class='test-section'>
    <h2>3. PHP Extensions</h2>";

$required_extensions = ['gd', 'fileinfo'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<p class='success'>✅ $ext extension is loaded</p>";
    } else {
        echo "<p class='error'>❌ $ext extension is missing</p>";
    }
}

echo "</div>";

echo "<div class='test-section'>
    <h2>4. Database Check</h2>";

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
    echo "<p class='success'>✅ Database connection successful</p>";
    
    $stmt = $pdo->prepare("SELECT id, username, avatar_url FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "<p class='success'>✅ User found: {$user['username']}</p>";
        echo "<p><strong>Current avatar:</strong> " . ($user['avatar_url'] ?: 'None') . "</p>";
    } else {
        echo "<p class='error'>❌ User not found</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Database error: " . $e->getMessage() . "</p>";
}

echo "</div>";

echo "<div class='test-section'>
    <h2>5. Test Upload Form</h2>
    <div class='upload-form'>
        <form id='testForm' enctype='multipart/form-data'>
            <p>Select an image file (max 2MB):</p>
            <input type='file' name='avatar' accept='image/*' required>
            <br><br>
            <button type='submit'>Test Upload</button>
        </form>
        <div id='result' class='result' style='display: none;'></div>
    </div>
</div>";

echo "<div class='test-section'>
    <h2>6. Links</h2>
    <a href='u.php?username=admin'>Go to Profile</a> |
    <a href='api/upload_avatar.php'>Direct API Test</a>
</div>";

echo "
<script>
document.getElementById('testForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const resultDiv = document.getElementById('result');
    
    resultDiv.style.display = 'block';
    resultDiv.innerHTML = '<p>Uploading...</p>';
    
    try {
        const response = await fetch('api/upload_avatar.php', {
            method: 'POST',
            body: formData
        });
        
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        const text = await response.text();
        console.log('Response text:', text);
        
        let result;
        try {
            result = JSON.parse(text);
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            resultDiv.innerHTML = '<p class=\"error\">❌ Invalid JSON response: ' + text + '</p>';
            return;
        }
        
        if (result.success) {
            resultDiv.innerHTML = '<p class=\"success\">✅ ' + result.message + '</p><p>Avatar URL: ' + result.avatar_url + '</p>';
        } else {
            resultDiv.innerHTML = '<p class=\"error\">❌ ' + result.message + '</p>';
        }
    } catch (error) {
        console.error('Upload error:', error);
        resultDiv.innerHTML = '<p class=\"error\">❌ Network error: ' + error.message + '</p>';
    }
});
</script>
</body>
</html>";
?> 