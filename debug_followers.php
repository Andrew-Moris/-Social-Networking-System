<?php
require_once 'config.php';
session_start();

echo "<h1>Debug Followers & Chat Issue</h1>";

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
    
    if (!isset($_SESSION['user_id'])) {
        echo "<p style='color: red;'>❌ Not logged in!</p>";
        exit;
    }
    
    $current_user_id = $_SESSION['user_id'];
    echo "<p><strong>Current User ID:</strong> $current_user_id</p>";
    
    echo "<h2>1. Followers Table Check</h2>";
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'followers'");
        if ($stmt->rowCount() > 0) {
            echo "<p style='color: green;'>✓ Followers table exists</p>";
            
            $stmt = $pdo->query("DESCRIBE followers");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<h3>Table Structure:</h3>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th></tr>";
            foreach ($columns as $col) {
                echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td></tr>";
            }
            echo "</table>";
            
            $stmt = $pdo->query("SELECT COUNT(*) FROM followers");
            $total_follows = $stmt->fetchColumn();
            echo "<p><strong>Total follow relationships:</strong> $total_follows</p>";
            
            if ($total_follows > 0) {
                echo "<h3>All Follow Relationships:</h3>";
                $stmt = $pdo->query("
                    SELECT f.*, 
                           u1.username as follower_username, 
                           u2.username as followed_username
                    FROM followers f
                    JOIN users u1 ON f.follower_id = u1.id
                    JOIN users u2 ON f.followed_id = u2.id
                    ORDER BY f.created_at DESC
                ");
                $all_follows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<table border='1' style='border-collapse: collapse;'>";
                echo "<tr><th>Follower</th><th>Following</th><th>Date</th></tr>";
                foreach ($all_follows as $follow) {
                    $highlight = ($follow['follower_id'] == $current_user_id) ? "style='background-color: #e8f5e8;'" : "";
                    echo "<tr $highlight>";
                    echo "<td>{$follow['follower_username']} (ID: {$follow['follower_id']})</td>";
                    echo "<td>{$follow['followed_username']} (ID: {$follow['followed_id']})</td>";
                    echo "<td>{$follow['created_at']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
            
        } else {
            echo "<p style='color: red;'>❌ Followers table does not exist!</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error checking followers table: " . $e->getMessage() . "</p>";
    }
    
    echo "<h2>2. Current User's Following</h2>";
    try {
        $stmt = $pdo->prepare("
            SELECT u.id, u.username, u.first_name, u.last_name, f.created_at as follow_date
            FROM followers f
            JOIN users u ON f.followed_id = u.id
            WHERE f.follower_id = ?
            ORDER BY f.created_at DESC
        ");
        $stmt->execute([$current_user_id]);
        $following = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p><strong>You are following " . count($following) . " users:</strong></p>";
        
        if (!empty($following)) {
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>User</th><th>Name</th><th>Follow Date</th><th>Chat Link</th></tr>";
            foreach ($following as $user) {
                echo "<tr>";
                echo "<td>{$user['username']} (ID: {$user['id']})</td>";
                echo "<td>{$user['first_name']} {$user['last_name']}</td>";
                echo "<td>{$user['follow_date']}</td>";
                echo "<td><a href='chat.php?user_id={$user['id']}' style='color: blue;'>Start Chat</a></td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: orange;'>⚠️ You are not following anyone!</p>";
            echo "<p><a href='friends.php' style='color: blue;'>→ Go to Friends page to follow users</a></p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error getting following list: " . $e->getMessage() . "</p>";
    }
    
    echo "<h2>3. Test Chat Query for Followed Users</h2>";
    try {
        $stmt = $pdo->prepare("
            SELECT u.*, NULL as last_message_content, NULL as last_message_time, 
                   'text' as last_message_type, 0 as unread_count, 0 as i_sent_last
            FROM users u
            JOIN followers f ON u.id = f.followed_id
            WHERE f.follower_id = ?
            ORDER BY u.username ASC
        ");
        $stmt->execute([$current_user_id]);
        $chat_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p><strong>Users returned for chat:</strong> " . count($chat_users) . "</p>";
        
        if (!empty($chat_users)) {
            echo "<p style='color: green;'>✓ Query working! These users should appear in chat:</p>";
            echo "<ul>";
            foreach ($chat_users as $user) {
                echo "<li>{$user['username']} - {$user['first_name']} {$user['last_name']}</li>";
            }
            echo "</ul>";
        } else {
            echo "<p style='color: red;'>❌ No users returned by chat query!</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error testing chat query: " . $e->getMessage() . "</p>";
    }
    
    echo "<h2>4. Quick Actions</h2>";
    echo "<div style='margin: 20px 0;'>";
    echo "<a href='friends.php' style='display: inline-block; background: #667eea; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>Go to Friends Page</a>";
    echo "<a href='chat.php' style='display: inline-block; background: #10b981; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>Go to Chat</a>";
    echo "<a href='create_test_users.php' style='display: inline-block; background: #f59e0b; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>Create Test Users</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Database Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1, h2, h3 { color: #333; }
table { margin: 10px 0; width: 100%; }
th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
th { background-color: #f2f2f2; font-weight: bold; }
.highlight { background-color: #fff3cd; }
</style> 