<?php
require_once 'config.php';

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("
        ALTER TABLE messages 
        ADD COLUMN IF NOT EXISTS image_path VARCHAR(255) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS message_type ENUM('text', 'image') DEFAULT 'text'
    ");

    echo "تم تحديث جدول الرسائل بنجاح!";
} catch (Exception $e) {
    echo "حدث خطأ: " . $e->getMessage();
} 