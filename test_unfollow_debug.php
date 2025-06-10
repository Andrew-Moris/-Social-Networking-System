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
        SELECT u.*, 
               CASE WHEN f.follower_id IS NOT NULL THEN 1 ELSE 0 END as i_follow
        FROM users u
        LEFT JOIN followers f ON u.id = f.followed_id AND f.follower_id = ?
        WHERE u.id != ? 
        LIMIT 5
    ");
    $stmt->execute([$current_user_id, $current_user_id]);
    $test_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage();
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unfollow Debug Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #1a1f2e;
            color: white;
        }
        .test-card {
            background: rgba(255,255,255,0.1);
            padding: 20px;
            margin: 10px;
            border-radius: 10px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        .test-button {
            padding: 10px 20px;
            margin: 5px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-follow {
            background: #667eea;
            color: white;
        }
        .btn-unfollow {
            background: #fa709a;
            color: white;
        }
        .btn-follow:hover {
            background: #5a6fd8;
        }
        .btn-unfollow:hover {
            background: #f85d92;
        }
        .log {
            background: #000;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            font-family: monospace;
            font-size: 12px;
            max-height: 200px;
            overflow-y: auto;
        }
        .success { color: #4facfe; }
        .error { color: #fa709a; }
        .info { color: #43e97b; }
    </style>
</head>
<body>
    <h1>🔧 Unfollow Debug Test</h1>
    <p>Current User ID: <strong><?php echo $current_user_id; ?></strong></p>
    
    <div id="log" class="log">
        <div class="info">Debug log will appear here...</div>
    </div>
    
    <h2>Test Users:</h2>
    <?php foreach ($test_users as $user): ?>
        <div class="test-card">
            <h3><?php echo htmlspecialchars($user['username']); ?> (ID: <?php echo $user['id']; ?>)</h3>
            <p>Status: <?php echo $user['i_follow'] ? 'Following' : 'Not Following'; ?></p>
            
            <button class="test-button btn-follow" onclick="testFollow(<?php echo $user['id']; ?>, this)">
                <i class="bi bi-person-plus"></i> Follow
            </button>
            
            <button class="test-button btn-unfollow" onclick="testUnfollow(<?php echo $user['id']; ?>, this)">
                <i class="bi bi-person-dash"></i> Unfollow
            </button>
            
            <button class="test-button" onclick="checkStatus(<?php echo $user['id']; ?>)" style="background: #43e97b;">
                <i class="bi bi-search"></i> Check Status
            </button>
        </div>
    <?php endforeach; ?>
    
    <div style="margin-top: 30px;">
        <a href="friends.php" style="color: #667eea;">← Back to Friends Page</a>
    </div>

    <script>
        function log(message, type = 'info') {
            const logDiv = document.getElementById('log');
            const time = new Date().toLocaleTimeString();
            const entry = document.createElement('div');
            entry.className = type;
            entry.innerHTML = `[${time}] ${message}`;
            logDiv.appendChild(entry);
            logDiv.scrollTop = logDiv.scrollHeight;
        }
        
        function testFollow(userId, button) {
            log(`Testing FOLLOW for user ${userId}...`, 'info');
            
            button.disabled = true;
            button.innerHTML = '<i class="bi bi-arrow-repeat"></i> Following...';
            
            fetch('api/follow_fixed.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'follow',
                    followed_id: userId
                }),
            })
            .then(response => {
                log(`Response status: ${response.status}`, 'info');
                return response.json();
            })
            .then(data => {
                log(`Follow response: ${JSON.stringify(data)}`, data.success ? 'success' : 'error');
                
                if (data.success) {
                    button.innerHTML = '<i class="bi bi-check"></i> Followed!';
                    button.style.background = '#4facfe';
                } else {
                    button.innerHTML = '<i class="bi bi-x"></i> Failed';
                    button.style.background = '#fa709a';
                }
            })
            .catch(error => {
                log(`Follow error: ${error.message}`, 'error');
                button.innerHTML = '<i class="bi bi-x"></i> Error';
                button.style.background = '#fa709a';
            })
            .finally(() => {
                setTimeout(() => {
                    button.disabled = false;
                    button.innerHTML = '<i class="bi bi-person-plus"></i> Follow';
                    button.style.background = '#667eea';
                }, 2000);
            });
        }
        
        function testUnfollow(userId, button) {
            log(`Testing UNFOLLOW for user ${userId}...`, 'info');
            
            button.disabled = true;
            button.innerHTML = '<i class="bi bi-arrow-repeat"></i> Unfollowing...';
            
            fetch('api/follow_fixed.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'unfollow',
                    followed_id: userId
                }),
            })
            .then(response => {
                log(`Response status: ${response.status}`, 'info');
                return response.json();
            })
            .then(data => {
                log(`Unfollow response: ${JSON.stringify(data)}`, data.success ? 'success' : 'error');
                
                if (data.success) {
                    button.innerHTML = '<i class="bi bi-check"></i> Unfollowed!';
                    button.style.background = '#4facfe';
                } else {
                    button.innerHTML = '<i class="bi bi-x"></i> Failed';
                    button.style.background = '#fa709a';
                }
            })
            .catch(error => {
                log(`Unfollow error: ${error.message}`, 'error');
                button.innerHTML = '<i class="bi bi-x"></i> Error';
                button.style.background = '#fa709a';
            })
            .finally(() => {
                setTimeout(() => {
                    button.disabled = false;
                    button.innerHTML = '<i class="bi bi-person-dash"></i> Unfollow';
                    button.style.background = '#fa709a';
                }, 2000);
            });
        }
        
        function checkStatus(userId) {
            log(`Checking follow status for user ${userId}...`, 'info');
            
            fetch('api/follow_fixed.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'check',
                    followed_id: userId
                }),
            })
            .then(response => response.json())
            .then(data => {
                log(`Status check response: ${JSON.stringify(data)}`, 'success');
            })
            .catch(error => {
                log(`Status check error: ${error.message}`, 'error');
            });
        }
        
        log('Debug test page loaded successfully', 'success');
    </script>
</body>
</html> 