<?php
require_once 'config.php';

echo '<h2>Database Tables Check</h2>';
echo '<pre>';

try {
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ];
    
    $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
    echo "✅ Database connection successful!\n";
    echo "Connected to: {$dsn}\n\n";
    
    $stmt = $pdo->query("SELECT to_regclass('public.users') IS NOT NULL as exists");
    $exists = $stmt->fetch()['exists'];
    
    if ($exists) {
        echo "✅ Users table exists\n";
        
        $stmt = $pdo->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'users'");
        $columns = $stmt->fetchAll();
        
        echo "   Columns in users table:\n";
        foreach ($columns as $column) {
            echo "   - {$column['column_name']} ({$column['data_type']})\n";
        }
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM users");
        $count = $stmt->fetchColumn();
        echo "   Total users: {$count}\n";
    } else {
        echo "❌ Users table does not exist!\n";
        echo "   Creating users table...\n";
        
        $pdo->exec("
            CREATE TABLE users (
                id SERIAL PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                email VARCHAR(100) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                first_name VARCHAR(50),
                last_name VARCHAR(50),
                profile_picture VARCHAR(255),
                is_verified BOOLEAN DEFAULT FALSE,
                is_private BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        echo "✅ Users table created successfully\n";
    }
    
    $stmt = $pdo->query("SELECT to_regclass('public.followers') IS NOT NULL as exists");
    $exists = $stmt->fetch()['exists'];
    
    if ($exists) {
        echo "✅ Followers table exists\n";
    } else {
        echo "❌ Followers table does not exist!\n";
        echo "   Creating followers table...\n";
        
        $pdo->exec("
            CREATE TABLE followers (
                id SERIAL PRIMARY KEY,
                follower_id INTEGER NOT NULL REFERENCES users(id),
                following_id INTEGER NOT NULL REFERENCES users(id),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(follower_id, following_id)
            )
        ");
        
        echo "✅ Followers table created successfully\n";
    }
    
    $stmt = $pdo->query("SELECT to_regclass('public.posts') IS NOT NULL as exists");
    $exists = $stmt->fetch()['exists'];
    
    if ($exists) {
        echo "✅ Posts table exists\n";
    } else {
        echo "❌ Posts table does not exist!\n";
        echo "   Creating posts table...\n";
        
        $pdo->exec("
            CREATE TABLE posts (
                id SERIAL PRIMARY KEY,
                user_id INTEGER NOT NULL REFERENCES users(id),
                content TEXT NOT NULL,
                media_url VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        echo "✅ Posts table created successfully\n";
    }
    
    $stmt = $pdo->query("SELECT to_regclass('public.user_settings') IS NOT NULL as exists");
    $exists = $stmt->fetch()['exists'];
    
    if ($exists) {
        echo "✅ User settings table exists\n";
    } else {
        echo "❌ User settings table does not exist!\n";
        echo "   Creating user_settings table...\n";
        
        $pdo->exec("
            CREATE TABLE user_settings (
                id SERIAL PRIMARY KEY,
                user_id INTEGER NOT NULL REFERENCES users(id),
                theme VARCHAR(20) DEFAULT 'dark',
                notifications BOOLEAN DEFAULT TRUE,
                private_account BOOLEAN DEFAULT FALSE,
                language VARCHAR(10) DEFAULT 'ar',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(user_id)
            )
        ");
        
        echo "✅ User settings table created successfully\n";
    }
    
} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo '</pre>';
echo '<p><a href="frontend/register.html">Go to Registration Page</a></p>';
?>
