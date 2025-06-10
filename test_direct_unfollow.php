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
        LIMIT 1
    ");
    $stmt->execute([$current_user_id, $current_user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    exit;
}

if (!$user) {
    echo "No test user found.";
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Direct Unfollow Test</title>
    <style>
        body { 
            font-family: Arial; 
            background: #1a1f2e; 
            color: white; 
            padding: 20px; 
        }
        .btn { 
            padding: 15px 30px; 
            margin: 10px; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            font-size: 16px;
        }
        .btn-follow { background: #4CAF50; color: white; }
        .btn-unfollow { background: #f44336; color: white; }
        .btn-disabled { background: #666; color: #ccc; cursor: not-allowed; }
        .log { 
            background: #000; 
            padding: 15px; 
            margin: 20px 0; 
            border-radius: 5px; 
            font-family: monospace; 
            font-size: 14px;
            max-height: 400px; 
            overflow-y: auto; 
        }
    </style>
</head>
<body>
    <h1>🎯 Direct Unfollow Test (No Modal)</h1>
    <p>Testing: <strong><?php echo htmlspecialchars($user['username']); ?></strong> (ID: <?php echo $user['id']; ?>)</p>
    <p>Current Status: <strong><?php echo $user['i_follow'] ? 'Following' : 'Not Following'; ?></strong></p>
    
    <div>
        <button id="testButton" 
                class="btn <?php echo $user['i_follow'] ? 'btn-unfollow' : 'btn-follow'; ?>"
                onclick="directUnfollow(<?php echo $user['id']; ?>)">
            <?php echo $user['i_follow'] ? 'UNFOLLOW (Direct)' : 'FOLLOW (Direct)'; ?>
        </button>
    </div>
    
    <div id="log" class="log">
        <strong>Debug Log:</strong><br>
        Ready to test...
    </div>
    
    <p><a href="friends.php" style="color: #4CAF50;">← Back to Friends Page</a></p>

    <script>
        function log(message, level = 'info') {
            const logDiv = document.getElementById('log');
            const time = new Date().toLocaleTimeString();
            const colors = {
                info: '#00bcd4',
                success: '#4caf50',
                error: '#f44336',
                warning: '#ff9800'
            };
            
            logDiv.innerHTML += `<br><span style="color: ${colors[level] || '#00bcd4'}">[${time}] ${message}</span>`;
            logDiv.scrollTop = logDiv.scrollHeight;
        }
        
        function directUnfollow(userId) {
            const button = document.getElementById('testButton');
            const isCurrentlyFollowing = button.textContent.includes('UNFOLLOW');
            const action = isCurrentlyFollowing ? 'unfollow' : 'follow';
            
            log(`🚀 Starting DIRECT ${action.toUpperCase()} for user ${userId}`, 'info');
            log(`Button: ${button.textContent} | Class: ${button.className}`, 'info');
            
            button.disabled = true;
            button.className = 'btn btn-disabled';
            button.textContent = action === 'follow' ? 'FOLLOWING...' : 'UNFOLLOWING...';
            
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
                log(`📡 HTTP Status: ${response.status}`, response.ok ? 'success' : 'error');
                return response.json();
            })
            .then(data => {
                log(`📥 API Response: ${JSON.stringify(data)}`, data.success ? 'success' : 'error');
                
                if (data.success) {
                    if (action === 'follow') {
                        button.textContent = 'UNFOLLOW (Direct)';
                        button.className = 'btn btn-unfollow';
                        log('✅ FOLLOW successful - Button updated to UNFOLLOW', 'success');
                    } else {
                        button.textContent = 'FOLLOW (Direct)';
                        button.className = 'btn btn-follow';
                        log('✅ UNFOLLOW successful - Button updated to FOLLOW', 'success');
                    }
                    
                    log('🔄 Button state after update:', 'info');
                    log(`   Text: ${button.textContent}`, 'info');
                    log(`   Class: ${button.className}`, 'info');
                    log(`   Disabled: ${button.disabled}`, 'info');
                    
                } else {
                    log(`❌ Error: ${data.message}`, 'error');
                    button.textContent = isCurrentlyFollowing ? 'UNFOLLOW (Direct)' : 'FOLLOW (Direct)';
                    button.className = isCurrentlyFollowing ? 'btn btn-unfollow' : 'btn btn-follow';
                }
            })
            .catch(error => {
                log(`💥 Network Error: ${error.message}`, 'error');
                button.textContent = isCurrentlyFollowing ? 'UNFOLLOW (Direct)' : 'FOLLOW (Direct)';
                button.className = isCurrentlyFollowing ? 'btn btn-unfollow' : 'btn btn-follow';
            })
            .finally(() => {
                button.disabled = false;
                log(`🏁 Operation completed for user ${userId}`, 'info');
                log('=' * 50, 'info');
            });
        }
        
        log('🎯 Direct test page loaded successfully', 'success');
        log('Click the button to test Follow/Unfollow without modal interference', 'info');
    </script>
</body>
</html> 