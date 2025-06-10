<?php
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'wep_db';

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

$alterQueries = [
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS birthdate DATE DEFAULT NULL",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS interests TEXT DEFAULT NULL",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_completed TINYINT(1) DEFAULT 0"
];

$errors = [];
$success = true;

foreach ($alterQueries as $query) {
    if (!$conn->query($query)) {
        $errors[] = "خطأ في تنفيذ الاستعلام: " . $query . " - " . $conn->error;
        $success = false;
    }
}

if ($success) {
    echo "تم تحديث هيكل قاعدة البيانات بنجاح!";
} else {
    echo "حدثت أخطاء أثناء تحديث قاعدة البيانات:<br>";
    foreach ($errors as $error) {
        echo "- " . $error . "<br>";
    }
}

$conn->close();
?>
