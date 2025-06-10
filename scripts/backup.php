<?php
$backup_dir = __DIR__ . '/../backups';
$date = date('Ymd_His');
$backup_file = $backup_dir . '/wep_db_' . $date . '.sql';

if (!file_exists($backup_dir)) {
    mkdir($backup_dir, 0777, true);
}

$command = sprintf(
    'C:\xampp\mysql\bin\mysqldump -u root -pStrongRoot@123 --routines --events --single-transaction wep_db > %s',
    escapeshellarg($backup_file)
);

exec($command, $output, $return_var);

if ($return_var === 0) {
    echo "Backup created successfully: $backup_file\n";
} else {
    echo "Backup failed\n";
}

$files = glob($backup_dir . '/wep_db_*.sql');
if (count($files) > 7) {
    usort($files, function($a, $b) {
        return filemtime($a) - filemtime($b);
    });
    
    $delete_count = count($files) - 7;
    for ($i = 0; $i < $delete_count; $i++) {
        unlink($files[$i]);
        echo "Deleted old backup: " . basename($files[$i]) . "\n";
    }
}
?> 