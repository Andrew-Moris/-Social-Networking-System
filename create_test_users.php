<?php
require_once 'config.php';

echo "<h1>Create Test Users</h1>";

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $user_count = $stmt->fetchColumn();
    echo "<p>Current number of users: $user_count</p>";
    
    if (isset($_POST['create'])) {
        $test_users = [
            ['username' => 'john_doe', 'email' => 'john@test.com', 'first_name' => 'John', 'last_name' => 'Doe'],
            ['username' => 'jane_smith', 'email' => 'jane@test.com', 'first_name' => 'Jane', 'last_name' => 'Smith'],
            ['username' => 'mike_wilson', 'email' => 'mike@test.com', 'first_name' => 'Mike', 'last_name' => 'Wilson'],
            ['username' => 'sarah_jones', 'email' => 'sarah@test.com', 'first_name' => 'Sarah', 'last_name' => 'Jones'],
            ['username' => 'alex_brown', 'email' => 'alex@test.com', 'first_name' => 'Alex', 'last_name' => 'Brown']
        ];
        
        $created_count = 0;
        $default_password = password_hash('password123', PASSWORD_DEFAULT);
        
        foreach ($test_users as $user) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$user['username'], $user['email']]);
            
            if (!$stmt->fetch()) {
                $stmt = $pdo->prepare("
                    INSERT INTO users (username, email, password, first_name, last_name, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $user['username'], 
                    $user['email'], 
                    $default_password,
                    $user['first_name'],
                    $user['last_name']
                ]);
                $created_count++;
                echo "<p style='color: green;'>✓ Created user: {$user['username']}</p>";
            } else {
                echo "<p style='color: orange;'>⚠️ User already exists: {$user['username']}</p>";
            }
        }
        
        echo "<h3 style='color: green;'>Created $created_count new users!</h3>";
        echo "<p>Default password for all test users: <strong>password123</strong></p>";
        echo "<p><a href='chat.php' style='color: blue; font-size: 18px;'>→ Go to Chat</a></p>";
        
    } else {
        ?>
        <form method="POST">
            <p>This will create 5 test users with default passwords.</p>
            <button type="submit" name="create" style="background: #667eea; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;">
                Create Test Users
            </button>
        </form>
        
        <h3>Test Users to be created:</h3>
        <ul>
            <li>john_doe (John Doe)</li>
            <li>jane_smith (Jane Smith)</li>
            <li>mike_wilson (Mike Wilson)</li>
            <li>sarah_jones (Sarah Jones)</li>
            <li>alex_brown (Alex Brown)</li>
        </ul>
        <p><em>All with password: password123</em></p>
        <?php
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1, h3 { color: #333; }
button:hover { opacity: 0.9; }
</style> 