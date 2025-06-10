<?php
require_once 'config.php';

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("
        ALTER TABLE users 
        ADD COLUMN IF NOT EXISTS last_activity TIMESTAMP 
        DEFAULT CURRENT_TIMESTAMP 
        ON UPDATE CURRENT_TIMESTAMP
    ");

    echo "تم تحديث جدول المستخدمين بنجاح!";
} catch (Exception $e) {
    echo "حدث خطأ: " . $e->getMessage();
} 