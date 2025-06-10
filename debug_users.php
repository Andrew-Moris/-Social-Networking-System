<?php
require_once 'config.php';
session_start();

echo "<h1>Debug Users & Chat</h1>";

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
    
    echo "<h2>1. Session Info</h2>";
    if (isset($_SESSION['user_id'])) {
        echo "<p style='color: green;'>✓ Logged in as User ID: " . $_SESSION['user_id'] . "</p>";
        $current_user_id = $_SESSION['user_id'];
    } else {
        echo "<p style='color: red;'>❌ Not logged in!</p>";
        exit;
    }
    
    echo "<h2>2. All Users in Database</h2>";
    $stmt = $pdo->query("SELECT id, username, first_name, last_name, email FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Total users: " . count($users) . "</p>";
    
    if (count($users) > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Username</th><th>Name</th><th>Email</th><th>Action</th></tr>";
        foreach ($users as $user) {
            $style = ($user['id'] == $current_user_id) ? "style='background-color: #f0f0f0;'" : "";
            echo "<tr $style>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['username']}</td>";
            echo "<td>{$user['first_name']} {$user['last_name']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>";
            if ($user['id'] != $current_user_id) {
                echo "<a href='chat.php?user_id={$user['id']}' style='color: blue;'>Start Chat</a>";
            } else {
                echo "(Current User)";
            }
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h2>3. Test Query (Same as chat.php)</h2>";
    $stmt = $pdo->prepare("
        SELECT u.*, NULL as last_message_content, NULL as last_message_time, 
               'text' as last_message_type, 0 as unread_count, 0 as i_sent_last
        FROM users u
        WHERE u.id != ?
        ORDER BY u.username ASC
        LIMIT 20
    ");
    $stmt->execute([$current_user_id]);
    $test_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Users returned by chat query: " . count($test_users) . "</p>";
    
    if (count($test_users) == 0) {
        echo "<p style='color: red;'>❌ No users returned! This is the problem.</p>";
        
        if (count($users) == 1) {
            echo "<p style='color: orange;'>⚠️ There's only one user in the database. You need to create more users!</p>";
            echo "<p><a href='register.php' style='color: blue;'>→ Create a new user account</a></p>";
        }
    } else {
        echo "<p style='color: green;'>✓ Query is working correctly</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 10px 0; }
th, td { padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
h2 { color: #333; margin-top: 20px; }
</style> 