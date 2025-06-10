<?php
require_once 'config.php';

echo "<h1>Setting up Chat System</h1>";

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
    
    echo "<h2>1. Updating Messages Table</h2>";
    
    $stmt = $pdo->query("SHOW COLUMNS FROM messages LIKE 'media_url'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE messages ADD COLUMN media_url VARCHAR(255) DEFAULT NULL");
        echo "<p style='color: green;'>✓ Added media_url column to messages table</p>";
    } else {
        echo "<p style='color: blue;'>ℹ media_url column already exists</p>";
    }
    
    $stmt = $pdo->query("SHOW COLUMNS FROM messages LIKE 'media_type'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE messages ADD COLUMN media_type ENUM('text', 'image', 'video') DEFAULT 'text'");
        echo "<p style='color: green;'>✓ Added media_type column to messages table</p>";
    } else {
        echo "<p style='color: blue;'>ℹ media_type column already exists</p>";
    }
    
    echo "<h2>2. Creating Upload Directories</h2>";
    
    $directories = [
        'uploads',
        'uploads/chat',
        'uploads/avatars',
        'uploads/posts'
    ];
    
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            if (mkdir($dir, 0755, true)) {
                echo "<p style='color: green;'>✓ Created directory: $dir</p>";
            } else {
                echo "<p style='color: red;'>✗ Failed to create directory: $dir</p>";
            }
        } else {
            echo "<p style='color: blue;'>ℹ Directory already exists: $dir</p>";
        }
        
        $htaccess_file = $dir . '/.htaccess';
        if (!file_exists($htaccess_file)) {
            $htaccess_content = "Options -Indexes\n";
            $htaccess_content .= "<Files \"*.php\">\n";
            $htaccess_content .= "Order Allow,Deny\n";
            $htaccess_content .= "Deny from all\n";
            $htaccess_content .= "</Files>\n";
            
            if (file_put_contents($htaccess_file, $htaccess_content)) {
                echo "<p style='color: green;'>✓ Created security file: $htaccess_file</p>";
            }
        }
    }
    
    echo "<h2>3. Testing Database Connection</h2>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM messages");
    $result = $stmt->fetch();
    echo "<p style='color: green;'>✓ Messages table accessible. Current message count: {$result['count']}</p>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "<p style='color: green;'>✓ Users table accessible. Current user count: {$result['count']}</p>";
    
    echo "<h2>4. Creating Test Users (if needed)</h2>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $user_count = $stmt->fetch()['count'];
    
    if ($user_count < 2) {
        echo "<p style='color: orange;'>⚠ Less than 2 users found. Creating test users...</p>";
        
        $test_users = [
            [
                'username' => 'testuser1',
                'email' => 'test1@example.com',
                'password' => password_hash('password123', PASSWORD_DEFAULT),
                'first_name' => 'John',
                'last_name' => 'Doe'
            ],
            [
                'username' => 'testuser2',
                'email' => 'test2@example.com',
                'password' => password_hash('password123', PASSWORD_DEFAULT),
                'first_name' => 'Jane',
                'last_name' => 'Smith'
            ]
        ];
        
        foreach ($test_users as $user) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO users (username, email, password, first_name, last_name) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $user['username'],
                    $user['email'],
                    $user['password'],
                    $user['first_name'],
                    $user['last_name']
                ]);
                echo "<p style='color: green;'>✓ Created test user: {$user['username']} (password: password123)</p>";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    echo "<p style='color: blue;'>ℹ Test user {$user['username']} already exists</p>";
                } else {
                    echo "<p style='color: red;'>✗ Error creating user {$user['username']}: " . $e->getMessage() . "</p>";
                }
            }
        }
    } else {
        echo "<p style='color: green;'>✓ Sufficient users exist for testing</p>";
    }
    
    echo "<h2>5. Setup Complete!</h2>";
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>✅ Chat System Setup Successful!</h3>";
    echo "<p><strong>You can now access the chat system at:</strong></p>";
    echo "<p><a href='chat_simple.php' style='color: #155724; font-weight: bold;'>http://localhost/WEP/chat_simple.php</a></p>";
    echo "<p><strong>Features available:</strong></p>";
    echo "<ul>";
    echo "<li>✓ Private messaging between users</li>";
    echo "<li>✓ Image sharing (JPEG, PNG, GIF, WebP)</li>";
    echo "<li>✓ Video sharing (MP4, AVI, MOV, WMV)</li>";
    echo "<li>✓ Real-time message updates</li>";
    echo "<li>✓ Unread message indicators</li>";
    echo "<li>✓ User search functionality</li>";
    echo "<li>✓ Mobile responsive design</li>";
    echo "<li>✓ Secure file uploads</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>📝 Important Notes:</h3>";
    echo "<ul>";
    echo "<li>Only logged-in users can access the chat</li>";
    echo "<li>Messages are private between two users only</li>";
    echo "<li>File uploads are limited to 10MB</li>";
    echo "<li>Supported image formats: JPEG, PNG, GIF, WebP</li>";
    echo "<li>Supported video formats: MP4, AVI, MOV, WMV</li>";
    echo "<li>Messages auto-refresh every 3 seconds</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    margin: 20px;
    background: #f5f5f5;
}

h1, h2 {
    color: #333;
}

p {
    margin: 5px 0;
}

ul {
    margin: 10px 0;
}

li {
    margin: 5px 0;
}
</style> 