<?php
require_once 'config.php';

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== فحص جداول المحادثة ===" . PHP_EOL;
    
    $stmt = $pdo->query('SHOW TABLES LIKE "messages"');
    $messages_exists = $stmt->rowCount() > 0;
    
    $stmt = $pdo->query('SHOW TABLES LIKE "messages_enhanced"');
    $enhanced_exists = $stmt->rowCount() > 0;
    
    echo 'Messages table exists: ' . ($messages_exists ? 'YES' : 'NO') . PHP_EOL;
    echo 'Messages enhanced table exists: ' . ($enhanced_exists ? 'YES' : 'NO') . PHP_EOL;
    
    if ($messages_exists) {
        $stmt = $pdo->query('DESCRIBE messages');
        echo PHP_EOL . 'Messages table structure:' . PHP_EOL;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo '  ' . $row['Field'] . ' - ' . $row['Type'] . PHP_EOL;
        }
    }
    
    if ($enhanced_exists) {
        $stmt = $pdo->query('DESCRIBE messages_enhanced');
        echo PHP_EOL . 'Messages enhanced table structure:' . PHP_EOL;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo '  ' . $row['Field'] . ' - ' . $row['Type'] . PHP_EOL;
        }
    }
    
    if (!$enhanced_exists) {
        echo PHP_EOL . "Creating messages_enhanced table..." . PHP_EOL;
        $pdo->exec("
            CREATE TABLE messages_enhanced (
                id INT AUTO_INCREMENT PRIMARY KEY,
                sender_id INT NOT NULL,
                receiver_id INT NOT NULL,
                content TEXT,
                image_url VARCHAR(500),
                audio_url VARCHAR(500),
                message_type ENUM('text', 'image', 'audio', 'mixed') DEFAULT 'text',
                is_delivered BOOLEAN DEFAULT FALSE,
                is_read BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_conversation (sender_id, receiver_id, created_at),
                INDEX idx_unread (receiver_id, is_read)
            )
        ");
        echo "✅ Messages enhanced table created successfully!" . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
?> 