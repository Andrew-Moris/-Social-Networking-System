<?php
$backup_dir = __DIR__ . '/../backups';

$files = glob($backup_dir . '/wep_db_*.sql');
if (empty($files)) {
    die("No backup files found in $backup_dir\n");
}

usort($files, function($a, $b) {
    return filemtime($b) - filemtime($a);
});

echo "Available backups:\n";
foreach ($files as $i => $file) {
    echo sprintf("[%d] %s (%s)\n", 
        $i + 1, 
        basename($file),
        date('Y-m-d H:i:s', filemtime($file))
    );
}

echo "\nEnter backup number to restore (or 'q' to quit): ";
$choice = trim(fgets(STDIN));

if ($choice === 'q') {
    die("Restore cancelled\n");
}

$index = (int)$choice - 1;
if (!isset($files[$index])) {
    die("Invalid backup number\n");
}

$backup_file = $files[$index];

echo "\nAre you sure you want to restore from " . basename($backup_file) . "? (y/n): ";
$confirm = trim(fgets(STDIN));

if ($confirm !== 'y') {
    die("Restore cancelled\n");
}

$command = sprintf(
    'C:\xampp\mysql\bin\mysql -u root -pStrongRoot@123 wep_db < %s',
    escapeshellarg($backup_file)
);

exec($command, $output, $return_var);

if ($return_var === 0) {
    echo "Database restored successfully from " . basename($backup_file) . "\n";
} else {
    echo "Restore failed\n";
}
?> 