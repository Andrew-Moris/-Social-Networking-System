<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo "Please login first: <a href='quick_login.php'>Quick Login</a>";
    exit;
}

$current_user_id = $_SESSION['user_id'];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
    
    $stmt = $pdo->prepare("
        SELECT u.id, u.username,
               CASE WHEN f.follower_id IS NOT NULL THEN 1 ELSE 0 END as i_follow
        FROM users u
        LEFT JOIN followers f ON u.id = f.followed_id AND f.follower_id = ?
        WHERE u.id != ? 
        LIMIT 3
    ");
    $stmt->execute([$current_user_id, $current_user_id]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple Follow/Unfollow Test</title>
    <style>
        body { 
            font-family: Arial; 
            background: #1a1f2e; 
            color: white; 
            padding: 20px; 
        }
        .test-card { 
            background: rgba(255,255,255,0.1); 
            padding: 20px; 
            margin: 10px; 
            border-radius: 10px; 
        }
        .btn { 
            padding: 10px 20px; 
            margin: 5px; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
        }
        .btn-follow { background: #4CAF50; color: white; }
        .btn-unfollow { background: #f44336; color: white; }
        .btn-disabled { background: #666; color: #ccc; cursor: not-allowed; }
        .log { 
            background: #000; 
            padding: 10px; 
            margin: 10px 0; 
            border-radius: 5px; 
            font-family: monospace; 
            max-height: 200px; 
            overflow-y: auto; 
        }
    </style>
</head>
<body>
    <h1>🧪 Simple Follow/Unfollow Test</h1>
    <p>Current User: <?php echo $current_user_id; ?></p>
    
    <div id="log" class="log">Ready...</div>
    
    <?php foreach ($users as $user): ?>
        <div class="test-card">
            <h3><?php echo htmlspecialchars($user['username']); ?> (ID: <?php echo $user['id']; ?>)</h3>
            <p>Currently: <?php echo $user['i_follow'] ? 'Following' : 'Not Following'; ?></p>
            
            <button id="btn_<?php echo $user['id']; ?>" 
                    class="btn <?php echo $user['i_follow'] ? 'btn-unfollow' : 'btn-follow'; ?>"
                    onclick="toggleFollow(<?php echo $user['id']; ?>)">
                <?php echo $user['i_follow'] ? 'Unfollow' : 'Follow'; ?>
            </button>
        </div>
    <?php endforeach; ?>
    
    <p><a href="friends.php" style="color: #4CAF50;">← Back to Friends Page</a></p>

    <script>
        function log(message) {
            const logDiv = document.getElementById('log');
            const time = new Date().toLocaleTimeString();
            logDiv.innerHTML += `<br>[${time}] ${message}`;
            logDiv.scrollTop = logDiv.scrollHeight;
        }
        
        function toggleFollow(userId) {
            const button = document.getElementById(`btn_${userId}`);
            const isCurrentlyFollowing = button.textContent.trim() === 'Unfollow';
            const action = isCurrentlyFollowing ? 'unfollow' : 'follow';
            
            log(`Starting ${action} for user ${userId}`);
            log(`Button state: ${button.textContent} | Class: ${button.className}`);
            
            button.disabled = true;
            button.className = 'btn btn-disabled';
            button.textContent = action === 'follow' ? 'Following...' : 'Unfollowing...';
            
            fetch('api/follow_fixed.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: action,
                    followed_id: userId
                }),
            })
            .then(response => {
                log(`HTTP Status: ${response.status}`);
                return response.json();
            })
            .then(data => {
                log(`API Response: ${JSON.stringify(data)}`);
                
                if (data.success) {
                    if (action === 'follow') {
                        button.textContent = 'Unfollow';
                        button.className = 'btn btn-unfollow';
                        log('✅ Follow successful - Button updated to Unfollow');
                    } else {
                        button.textContent = 'Follow';
                        button.className = 'btn btn-follow';
                        log('✅ Unfollow successful - Button updated to Follow');
                    }
                } else {
                    log(`❌ Error: ${data.message}`);
                    button.textContent = isCurrentlyFollowing ? 'Unfollow' : 'Follow';
                    button.className = isCurrentlyFollowing ? 'btn btn-unfollow' : 'btn btn-follow';
                }
            })
            .catch(error => {
                log(`❌ Network Error: ${error.message}`);
                button.textContent = isCurrentlyFollowing ? 'Unfollow' : 'Follow';
                button.className = isCurrentlyFollowing ? 'btn btn-unfollow' : 'btn btn-follow';
            })
            .finally(() => {
                button.disabled = false;
                log(`Operation completed for user ${userId}`);
                log('---');
            });
        }
        
        log('Simple test page loaded');
    </script>
</body>
</html> 