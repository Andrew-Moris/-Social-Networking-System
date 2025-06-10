<?php
$root_mysqli = new mysqli('127.0.0.1', 'root', 'StrongRoot@123', '', '3306');

if ($root_mysqli->connect_error) {
    die("Connection failed: " . $root_mysqli->connect_error);
}

$sql = "CREATE DATABASE IF NOT EXISTS wep_db
        CHARACTER SET utf8mb4
        COLLATE utf8mb4_unicode_ci";

if ($root_mysqli->query($sql) === TRUE) {
    echo "Database created successfully\n";
} else {
    echo "Error creating database: " . $root_mysqli->error . "\n";
}

$sql = "CREATE USER IF NOT EXISTS 'wep_user'@'localhost' IDENTIFIED BY 'WepP@ss456'";
if ($root_mysqli->query($sql) === TRUE) {
    echo "User created successfully\n";
} else {
    echo "Error creating user: " . $root_mysqli->error . "\n";
}

$sql = "GRANT ALL PRIVILEGES ON wep_db.* TO 'wep_user'@'localhost'";
if ($root_mysqli->query($sql) === TRUE) {
    echo "Privileges granted successfully\n";
} else {
    echo "Error granting privileges: " . $root_mysqli->error . "\n";
}

$sql = "FLUSH PRIVILEGES";
if ($root_mysqli->query($sql) === TRUE) {
    echo "Privileges flushed successfully\n";
} else {
    echo "Error flushing privileges: " . $root_mysqli->error . "\n";
}

$root_mysqli->select_db('wep_db');
$schema = file_get_contents(__DIR__ . '/../database/schema.sql');
if ($root_mysqli->multi_query($schema)) {
    echo "Schema imported successfully\n";
} else {
    echo "Error importing schema: " . $root_mysqli->error . "\n";
}

$root_mysqli->close();
?> 