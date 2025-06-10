<?php
require_once 'config.php';

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== تحديث جدول المحادثة المطور ===" . PHP_EOL;
    
    try {
        $pdo->exec('ALTER TABLE messages_enhanced ADD COLUMN audio_url VARCHAR(500) AFTER image_url');
        echo '✅ Audio URL column added successfully!' . PHP_EOL;
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo '✅ Audio URL column already exists.' . PHP_EOL;
        } else {
            throw $e;
        }
    }
    
    try {
        $pdo->exec('ALTER TABLE messages_enhanced MODIFY COLUMN message_type ENUM("text", "image", "audio", "mixed") DEFAULT "text"');
        echo '✅ Message type enum updated successfully!' . PHP_EOL;
    } catch (Exception $e) {
        echo '❌ Error updating message type: ' . $e->getMessage() . PHP_EOL;
    }
    
    $stmt = $pdo->query('DESCRIBE messages_enhanced');
    echo PHP_EOL . 'Final table structure:' . PHP_EOL;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo '  ' . $row['Field'] . ' - ' . $row['Type'] . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo '❌ Error: ' . $e->getMessage() . PHP_EOL;
}
?> 