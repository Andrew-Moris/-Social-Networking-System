<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo "Please login first: <a href='quick_login.php'>Quick Login</a>";
    exit;
}

if (isset($_GET['clear']) && $_GET['clear'] === '1') {
    $error_log_path = ini_get('error_log');
    if ($error_log_path && file_exists($error_log_path)) {
        file_put_contents($error_log_path, '');
        header('Location: view_logs.php?cleared=1');
        exit;
    }
}

$error_log_path = ini_get('error_log');
if (!$error_log_path) {
    $possible_paths = [
        'C:/xampp/php/logs/php_error_log',
        'C:/xampp/apache/logs/error.log',
        '/var/log/php_errors.log',
        '/tmp/php_errors.log'
    ];
    
    foreach ($possible_paths as $path) {
        if (file_exists($path)) {
            $error_log_path = $path;
            break;
        }
    }
}

$logs = '';
$log_size = 0;
if ($error_log_path && file_exists($error_log_path)) {
    $log_size = filesize($error_log_path);
    if ($log_size > 0) {
        $logs = shell_exec("tail -50 \"$error_log_path\"");
        if (!$logs) {
            $logs = file_get_contents($error_log_path);
            $lines = explode("\n", $logs);
            $logs = implode("\n", array_slice($lines, -50));
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>PHP Error Logs Viewer</title>
    <style>
        body { 
            font-family: Arial; 
            background: #1a1f2e; 
            color: white; 
            padding: 20px; 
        }
        .log-container {
            background: #000;
            padding: 20px;
            border-radius: 10px;
            font-family: monospace;
            font-size: 12px;
            max-height: 500px;
            overflow-y: auto;
            white-space: pre-wrap;
            border: 1px solid #333;
        }
        .controls {
            margin-bottom: 20px;
        }
        .btn {
            padding: 10px 20px;
            margin: 5px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-refresh { background: #4CAF50; color: white; }
        .btn-clear { background: #f44336; color: white; }
        .btn-back { background: #2196F3; color: white; }
        .info {
            background: rgba(33, 150, 243, 0.1);
            border: 1px solid rgba(33, 150, 243, 0.3);
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .follow-logs { color: #ff9800; }
        .unfollow-logs { color: #f44336; }
        .success-logs { color: #4caf50; }
        .error-logs { color: #f44336; }
    </style>
</head>
<body>
    <h1>📋 PHP Error Logs Viewer</h1>
    
    <div class="info">
        <p><strong>Log File:</strong> <?php echo $error_log_path ?: 'Not found'; ?></p>
        <p><strong>File Size:</strong> <?php echo $log_size ? number_format($log_size) . ' bytes' : '0 bytes'; ?></p>
        <p><strong>Last Updated:</strong> <?php echo $error_log_path && file_exists($error_log_path) ? date('Y-m-d H:i:s', filemtime($error_log_path)) : 'N/A'; ?></p>
    </div>
    
    <div class="controls">
        <a href="view_logs.php" class="btn btn-refresh">🔄 Refresh</a>
        <a href="view_logs.php?clear=1" class="btn btn-clear" 
           onclick="return confirm('Are you sure you want to clear all logs?')">🗑️ Clear Logs</a>
        <a href="test_database_unfollow.php" class="btn btn-back">🧪 Database Test</a>
        <a href="friends.php" class="btn btn-back">👥 Friends Page</a>
    </div>
    
    <?php if (isset($_GET['cleared'])): ?>
        <div style="background: rgba(76, 175, 80, 0.1); border: 1px solid rgba(76, 175, 80, 0.3); padding: 15px; border-radius: 5px; margin-bottom: 20px;">
            ✅ Logs cleared successfully
        </div>
    <?php endif; ?>
    
    <h2>📄 Recent Logs (Last 50 lines):</h2>
    <div class="log-container" id="logContainer">
        <?php if ($logs): ?>
            <?php 
            $highlighted_logs = $logs;
            $highlighted_logs = preg_replace('/FOLLOW ATTEMPT.*/', '<span class="follow-logs">$0</span>', $highlighted_logs);
            $highlighted_logs = preg_replace('/UNFOLLOW ATTEMPT.*/', '<span class="unfollow-logs">$0</span>', $highlighted_logs);
            $highlighted_logs = preg_replace('/UNFOLLOW SUCCESS.*/', '<span class="success-logs">$0</span>', $highlighted_logs);
            $highlighted_logs = preg_replace('/UNFOLLOW FAILED.*/', '<span class="error-logs">$0</span>', $highlighted_logs);
            $highlighted_logs = preg_replace('/Follow API Success.*/', '<span class="success-logs">$0</span>', $highlighted_logs);
            echo $highlighted_logs;
            ?>
        <?php else: ?>
            <em>No logs found or log file is empty</em>
        <?php endif; ?>
    </div>
    
    <script>
        let autoRefresh = setInterval(function() {
            if (document.hidden) return; 
            
            fetch('view_logs.php')
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newLogContent = doc.getElementById('logContainer');
                    if (newLogContent) {
                        document.getElementById('logContainer').innerHTML = newLogContent.innerHTML;
                    }
                })
                .catch(error => console.log('Auto-refresh failed:', error));
        }, 10000);
        
        document.getElementById('logContainer').scrollTop = document.getElementById('logContainer').scrollHeight;
    </script>
</body>
</html> 