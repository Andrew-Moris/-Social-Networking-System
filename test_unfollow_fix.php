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
    <title>Test Unfollow Fix</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { 
            font-family: Arial; 
            background: #1a1f2e; 
            color: white; 
            padding: 20px; 
        }
        .test-card { 
            background: rgba(255,255,255,0.1); 
            padding: 30px; 
            margin: 20px auto; 
            border-radius: 10px; 
            max-width: 500px;
            text-align: center;
        }
        .action-button { 
            padding: 15px 30px; 
            margin: 10px; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer; 
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-primary { background: #4CAF50; color: white; }
        .btn-danger { background: #f44336; color: white; }
        .btn-disabled { background: #666; color: #ccc; cursor: not-allowed; }
        .log { 
            background: #000; 
            padding: 15px; 
            margin: 20px 0; 
            border-radius: 5px; 
            font-family: monospace; 
            font-size: 12px;
            max-height: 300px; 
            overflow-y: auto; 
            text-align: left;
        }
        .success { color: #4caf50; }
        .error { color: #f44336; }
        .warning { color: #ff9800; }
        .info { color: #2196f3; }
        
        .simple-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 10000;
        }
        .simple-modal.show {
            display: flex;
        }
        .modal-content {
            background: #2c3e50;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            max-width: 400px;
        }
        .modal-btn {
            padding: 10px 20px;
            margin: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
        }
        .modal-btn-danger { background: #f44336; color: white; }
        .modal-btn-secondary { background: #666; color: white; }
    </style>
</head>
<body>
    <h1>🔧 Test Unfollow Fix</h1>
    
    <div class="test-card">
        <h2>Testing User: <?php echo htmlspecialchars($user['username']); ?></h2>
        <p>Current Status: <strong><?php echo $user['i_follow'] ? 'Following' : 'Not Following'; ?></strong></p>
        
        <div>
            <button id="testButton" 
                    class="action-button <?php echo $user['i_follow'] ? 'btn-danger' : 'btn-primary'; ?>"
                    onclick="<?php echo $user['i_follow'] ? 'testUnfollow(' . $user['id'] . ')' : 'testFollow(' . $user['id'] . ')'; ?>">
                <i class="bi <?php echo $user['i_follow'] ? 'bi-person-dash' : 'bi-person-plus'; ?>"></i>
                <?php echo $user['i_follow'] ? 'Unfollow' : 'Follow'; ?>
            </button>
        </div>
        
        <p style="margin-top: 20px; font-size: 14px; color: #aaa;">
            This test uses the same logic as friends.php but with simpler UI
        </p>
    </div>
    
    <div id="log" class="log">
        <div class="info">Ready to test...</div>
    </div>
    
    <div id="confirmModal" class="simple-modal">
        <div class="modal-content">
            <h3>Confirm Unfollow</h3>
            <p>Are you sure you want to unfollow this user?</p>
            <div>
                <button class="modal-btn modal-btn-danger" onclick="confirmUnfollow()">
                    Yes, Unfollow
                </button>
                <button class="modal-btn modal-btn-secondary" onclick="hideConfirmModal()">
                    Cancel
                </button>
            </div>
        </div>
    </div>
    
    <p style="text-align: center;">
        <a href="friends.php" style="color: #4CAF50;">← Back to Friends Page</a>
    </p>

    <script>
        let pendingUnfollowUserId = null;
        let pendingUnfollowButton = null;
        
        function log(message, level = 'info') {
            const logDiv = document.getElementById('log');
            const time = new Date().toLocaleTimeString();
            logDiv.innerHTML += `<br><span class="${level}">[${time}] ${message}</span>`;
            logDiv.scrollTop = logDiv.scrollHeight;
        }
        
        function testFollow(userId) {
            let button = null;
            
            if (event && event.target) {
                button = event.target.closest('.action-button') || 
                         event.target.closest('button') ||
                         event.target;
                
                if (button && !button.classList.contains('action-button')) {
                    const parentButton = button.parentElement;
                    if (parentButton && parentButton.classList.contains('action-button')) {
                        button = parentButton;
                    }
                }
            }
            
            log(`Follow - Button detection: ${button ? 'SUCCESS' : 'FAILED'}`, button ? 'success' : 'error');
            
            if (!button) {
                log('ERROR: Could not detect button element', 'error');
                return;
            }
            
            const originalText = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<span>Following...</span>';
            
            fetch('api/follow_fixed.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'follow', followed_id: userId }),
            })
            .then(response => response.json())
            .then(data => {
                log(`Follow API Response: ${JSON.stringify(data)}`, data.success ? 'success' : 'error');
                
                if (data.success) {
                    button.innerHTML = '<i class="bi bi-person-dash"></i> Unfollow';
                    button.className = 'action-button btn-danger';
                    button.onclick = function(e) {
                        e.preventDefault();
                        testUnfollow(userId);
                    };
                    log('✅ Follow successful - Button updated', 'success');
                } else {
                    log(`❌ Follow failed: ${data.message}`, 'error');
                }
            })
            .catch(error => {
                log(`💥 Follow error: ${error.message}`, 'error');
            })
            .finally(() => {
                button.disabled = false;
                if (button.innerHTML.includes('Following...')) {
                    button.innerHTML = originalText;
                }
            });
        }
        
        function testUnfollow(userId) {
            let button = null;
            
            if (event && event.target) {
                button = event.target.closest('.action-button') || 
                         event.target.closest('button') ||
                         event.target;
                
                if (button && !button.classList.contains('action-button')) {
                    const parentButton = button.parentElement;
                    if (parentButton && parentButton.classList.contains('action-button')) {
                        button = parentButton;
                    }
                }
            }
            
            log(`Unfollow - Button detection: ${button ? 'SUCCESS' : 'FAILED'}`, button ? 'success' : 'error');
            log(`Button details: ${button ? button.innerHTML : 'null'}`, 'info');
            
            if (!button) {
                log('ERROR: Could not detect button element', 'error');
                return;
            }
            
            showConfirmModal(userId, button);
        }
        
        function showConfirmModal(userId, button) {
            if (!button) {
                log('ERROR: showConfirmModal - button is null!', 'error');
                return;
            }
            
            log(`Modal - Storing button: ${button.innerHTML}`, 'info');
            
            pendingUnfollowUserId = userId;
            pendingUnfollowButton = button;
            
            document.getElementById('confirmModal').classList.add('show');
        }
        
        function hideConfirmModal() {
            document.getElementById('confirmModal').classList.remove('show');
            
            log('Modal hidden - keeping variables for performUnfollow', 'info');
        }
        
        function confirmUnfollow() {
            log(`Confirm - userId: ${pendingUnfollowUserId}, button: ${pendingUnfollowButton ? 'EXISTS' : 'NULL'}`, 'warning');
            
            if (pendingUnfollowButton) {
                log(`Button details: ${pendingUnfollowButton.tagName}, innerHTML: ${pendingUnfollowButton.innerHTML}, id: ${pendingUnfollowButton.id}`, 'info');
                log(`Button parent: ${pendingUnfollowButton.parentElement ? pendingUnfollowButton.parentElement.tagName : 'null'}`, 'info');
                log(`Button isConnected: ${pendingUnfollowButton.isConnected}`, 'info');
            }
            
            if (pendingUnfollowUserId && pendingUnfollowButton) {
                hideConfirmModal();
                
                setTimeout(() => {
                    log(`Before performUnfollow - button check: ${pendingUnfollowButton ? 'EXISTS' : 'NULL'}`, 'warning');
                    if (pendingUnfollowButton) {
                        log(`Button still exists: ${pendingUnfollowButton.innerHTML}`, 'info');
                        performUnfollow(pendingUnfollowUserId, pendingUnfollowButton);
                    } else {
                        log('ERROR: Button lost after modal close!', 'error');
                        const button = document.getElementById('testButton');
                        if (button) {
                            log('Found button by ID, retrying...', 'warning');
                            performUnfollow(pendingUnfollowUserId, button);
                        } else {
                            log('Could not recover button', 'error');
                        }
                    }
                }, 100);
            } else {
                log('ERROR: Missing userId or button', 'error');
                hideConfirmModal();
            }
        }
        
        function performUnfollow(userId, button) {
            if (!button) {
                log('ERROR: performUnfollow - button is null!', 'error');
                return;
            }
            
            if (!button.innerHTML) {
                log('ERROR: performUnfollow - button.innerHTML is null!', 'error');
                return;
            }
            
            const originalText = button.innerHTML;
            log(`Unfollow - Starting with button: ${originalText}`, 'warning');
            
            button.disabled = true;
            button.innerHTML = '<span>Unfollowing...</span>';
            
            fetch('api/follow_fixed.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'unfollow', followed_id: userId }),
            })
            .then(response => response.json())
            .then(data => {
                log(`Unfollow API Response: ${JSON.stringify(data)}`, data.success ? 'success' : 'error');
                
                if (data.success) {
                    button.innerHTML = '<i class="bi bi-person-plus"></i> Follow';
                    button.className = 'action-button btn-primary';
                    button.onclick = function(e) {
                        e.preventDefault();
                        testFollow(userId);
                    };
                    log('✅ Unfollow successful - Button updated to Follow', 'success');
                } else {
                    log(`❌ Unfollow failed: ${data.message}`, 'error');
                }
            })
            .catch(error => {
                log(`💥 Unfollow error: ${error.message}`, 'error');
            })
            .finally(() => {
                button.disabled = false;
                if (button.innerHTML.includes('Unfollowing...')) {
                    button.innerHTML = originalText;
                }
                
                pendingUnfollowUserId = null;
                pendingUnfollowButton = null;
                log('Global variables cleared after unfollow operation', 'info');
            });
        }
        
        log('🔧 Test page loaded - Same logic as friends.php', 'success');
    </script>
</body>
</html> 