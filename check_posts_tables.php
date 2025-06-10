<?php
require_once 'config.php';

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
    
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Tables in database:\n";
    foreach ($tables as $table) {
        echo "- $table\n";
        
        $columns = $pdo->query("DESCRIBE $table")->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('user_id', $columns) && (in_array('content', $columns) || in_array('text', $columns))) {
            echo "  This table might contain posts data!\n";
            
            $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            echo "  Number of rows: $count\n";
            
            $rows = $pdo->query("SELECT * FROM $table LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
            echo "  Sample data:\n";
            foreach ($rows as $row) {
                print_r($row);
            }
        }
        echo "\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 