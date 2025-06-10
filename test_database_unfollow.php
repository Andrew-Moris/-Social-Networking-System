<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo "Please login first: <a href='quick_login.php'>Quick Login</a>";
    exit;
}

$current_user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'check_db') {
    header('Content-Type: application/json');
    
    try {
        $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
        $user_id = intval($_POST['user_id']);
        
        $stmt = $pdo->prepare("SELECT * FROM followers WHERE follower_id = ? AND followed_id = ?");
        $stmt->execute([$current_user_id, $user_id]);
        $follows_record = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM followers WHERE followed_id = ?");
        $stmt->execute([$user_id]);
        $followers_count = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("
            SELECT f.*, u.username 
            FROM followers f 
            JOIN users u ON f.follower_id = u.id 
            WHERE f.followed_id = ?
        ");
        $stmt->execute([$user_id]);
        $all_followers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'is_following' => $follows_record ? true : false,
            'follows_record' => $follows_record,
            'followers_count' => $followers_count,
            'all_followers' => $all_followers,
            'current_user_id' => $current_user_id,
            'target_user_id' => $user_id
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

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
    <title>Database Unfollow Test</title>
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
            border: 1px solid rgba(255,255,255,0.2);
        }
        .btn { 
            padding: 10px 20px; 
            margin: 5px; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            font-size: 14px;
        }
        .btn-follow { background: #4CAF50; color: white; }
        .btn-unfollow { background: #f44336; color: white; }
        .btn-check { background: #2196F3; color: white; }
        .btn-disabled { background: #666; color: #ccc; cursor: not-allowed; }
        .log { 
            background: #000; 
            padding: 15px; 
            margin: 10px 0; 
            border-radius: 5px; 
            font-family: monospace; 
            font-size: 12px;
            max-height: 300px; 
            overflow-y: auto; 
        }
        .db-status {
            background: rgba(0,0,255,0.1);
            border: 1px solid rgba(0,0,255,0.3);
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .success { color: #4caf50; }
        .error { color: #f44336; }
        .warning { color: #ff9800; }
        .info { color: #2196f3; }
    </style>
</head>
<body>
    <h1>🗄️ Database Unfollow Test</h1>
    <p>Current User ID: <strong><?php echo $current_user_id; ?></strong></p>
    
    <div id="log" class="log">
        <div class="info">Ready to test database operations...</div>
    </div>
    
    <?php foreach ($users as $user): ?>
        <div class="test-card">
            <h3><?php echo htmlspecialchars($user['username']); ?> (ID: <?php echo $user['id']; ?>)</h3>
            <p>Initial Status: <strong><?php echo $user['i_follow'] ? 'Following' : 'Not Following'; ?></strong></p>
            
            <div>
                <button class="btn btn-follow" onclick="performFollow(<?php echo $user['id']; ?>)">
                    Follow
                </button>
                
                <button class="btn btn-unfollow" onclick="performUnfollow(<?php echo $user['id']; ?>)">
                    Unfollow
                </button>
                
                <button class="btn btn-check" onclick="checkDatabase(<?php echo $user['id']; ?>)">
                    Check Database
                </button>
            </div>
            
            <div id="status_<?php echo $user['id']; ?>" class="db-status" style="display: none;">
            </div>
        </div>
    <?php endforeach; ?>
    
    <p><a href="friends.php" style="color: #4CAF50;">← Back to Friends Page</a></p>

    <script>
        function log(message, level = 'info') {
            const logDiv = document.getElementById('log');
            const time = new Date().toLocaleTimeString();
            const colors = {
                info: '#2196f3',
                success: '#4caf50',
                error: '#f44336',
                warning: '#ff9800'
            };
            
            logDiv.innerHTML += `<br><span class="${level}">[${time}] ${message}</span>`;
            logDiv.scrollTop = logDiv.scrollHeight;
        }
        
        function performFollow(userId) {
            log(`🚀 Starting FOLLOW for user ${userId}`, 'info');
            
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
                log(`📡 Follow HTTP Status: ${response.status}`, response.ok ? 'success' : 'error');
                return response.json();
            })
            .then(data => {
                log(`📥 Follow API Response: ${JSON.stringify(data)}`, data.success ? 'success' : 'error');
                
                setTimeout(() => checkDatabase(userId), 500);
            })
            .catch(error => {
                log(`💥 Follow Error: ${error.message}`, 'error');
            });
        }
        
        function performUnfollow(userId) {
            log(`🚀 Starting UNFOLLOW for user ${userId}`, 'warning');
            
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
                log(`📡 Unfollow HTTP Status: ${response.status}`, response.ok ? 'success' : 'error');
                return response.json();
            })
            .then(data => {
                log(`📥 Unfollow API Response: ${JSON.stringify(data)}`, data.success ? 'success' : 'error');
                
                setTimeout(() => checkDatabase(userId), 500);
            })
            .catch(error => {
                log(`💥 Unfollow Error: ${error.message}`, 'error');
            });
        }
        
        function checkDatabase(userId) {
            log(`🔍 Checking database for user ${userId}...`, 'info');
            
            fetch('test_database_unfollow.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=check_db&user_id=${userId}`
            })
            .then(response => response.json())
            .then(data => {
                log(`📊 Database Check: ${JSON.stringify(data)}`, 'info');
                
                const statusDiv = document.getElementById(`status_${userId}`);
                statusDiv.style.display = 'block';
                
                if (data.success) {
                    statusDiv.innerHTML = `
                        <h4>📊 Database Status for User ${userId}:</h4>
                        <p><strong>Is Following:</strong> ${data.is_following ? '✅ YES' : '❌ NO'}</p>
                        <p><strong>Followers Count:</strong> ${data.followers_count}</p>
                        <p><strong>Current User ID:</strong> ${data.current_user_id}</p>
                        <p><strong>Target User ID:</strong> ${data.target_user_id}</p>
                        <p><strong>Follow Record:</strong> ${data.follows_record ? JSON.stringify(data.follows_record) : 'NULL'}</p>
                        <h5>All Followers:</h5>
                        <pre style="background: rgba(0,0,0,0.3); padding: 10px; border-radius: 5px; font-size: 11px;">${JSON.stringify(data.all_followers, null, 2)}</pre>
                    `;
                } else {
                    statusDiv.innerHTML = `<p class="error">Database Check Failed: ${data.error}</p>`;
                }
            })
            .catch(error => {
                log(`💥 Database Check Error: ${error.message}`, 'error');
            });
        }
        
        log('🗄️ Database test page loaded', 'success');
        log('Use buttons to test Follow/Unfollow and check actual database state', 'info');
    </script>
</body>
</html> 