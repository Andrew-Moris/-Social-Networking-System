<?php

require_once 'config.php';

function output_message($message, $is_error = false) {
    $style = $is_error ? 'color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb;' : 
                        'color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb;';
    echo "<div style='padding: 15px; margin: 10px 0; border-radius: 5px; {$style}'>{$message}</div>";
}

try {
    $root_dsn = "mysql:host=$db_host;charset=utf8mb4";
    $pdo = new PDO($root_dsn, $db_user, $db_pass, $pdo_options);
    
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    output_message("✅ Base de datos '$db_name' verificada/creada exitosamente.");
    
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
    
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `email` VARCHAR(100) NOT NULL UNIQUE,
        `display_name` VARCHAR(100),
        `bio` TEXT,
        `avatar` VARCHAR(255),
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    output_message("✅ Tabla 'users' verificada/creada exitosamente.");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS `posts` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `content` TEXT NOT NULL,
        `image` VARCHAR(255),
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    output_message("✅ Tabla 'posts' verificada/creada exitosamente.");
    
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
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS `comments` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `post_id` INT NOT NULL,
        `user_id` INT NOT NULL,
        `content` TEXT NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`post_id`) REFERENCES `posts`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    output_message("✅ Tabla 'comments' verificada/creada exitosamente.");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS `likes` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `post_id` INT NOT NULL,
        `user_id` INT NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `unique_like` (`post_id`, `user_id`),
        FOREIGN KEY (`post_id`) REFERENCES `posts`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    output_message("✅ Tabla 'likes' verificada/creada exitosamente.");
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM `users`");
    $user_count = $stmt->fetchColumn();
    
    if ($user_count == 0) {
        $username = 'admin';
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $email = 'admin@example.com';
        $display_name = 'Administrador';
        
        $stmt = $pdo->prepare("INSERT INTO `users` (`username`, `password`, `email`, `display_name`) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $password, $email, $display_name]);
        
        output_message("✅ Usuario de prueba creado con los siguientes datos:
            <br>- Usuario: admin
            <br>- Contraseña: admin123");
    }
    
    echo "<p style='margin-top: 20px;'>✅ Configuración de la base de datos completada exitosamente.</p>";
    echo "<p><a href='index.php' style='display: inline-block; background-color: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Ir a la página principal</a></p>";
    
} catch (PDOException $e) {
    output_message("❌ Error: " . $e->getMessage(), true);
}
?>
