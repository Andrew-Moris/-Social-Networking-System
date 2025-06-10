<?php

require_once 'config.php';

function output_message($message, $is_error = false) {
    $style = $is_error ? 'color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb;' : 
                        'color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb;';
    echo "<div style='padding: 15px; margin: 10px 0; border-radius: 5px; {$style}'>{$message}</div>";
}

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS `follows` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `follower_id` INT NOT NULL,
        `followed_id` INT NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `unique_follow` (`follower_id`, `followed_id`),
        FOREIGN KEY (`follower_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`followed_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    output_message("✅ Tabla 'follows' verificada/creada exitosamente.");
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'followers'");
    if (!$stmt->fetch()) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `followers` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `follower_id` INT NOT NULL,
            `followed_id` INT NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `unique_follow` (`follower_id`, `followed_id`),
            FOREIGN KEY (`follower_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`followed_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        output_message("✅ Tabla 'followers' verificada/creada exitosamente.");
    } else {
        output_message("✅ Tabla 'followers' ya existe.");
    }
    
    echo "<p style='margin-top: 20px;'>✅ Configuración de las tablas de seguimiento completada exitosamente.</p>";
    echo "<p><a href='u.php' style='display: inline-block; background-color: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Ir a la página de perfil</a></p>";
    
} catch (PDOException $e) {
    output_message("❌ Error: " . $e->getMessage(), true);
}
?>
