<?php

$profile_content = file_get_contents('profile.php');

$profile_content = preg_replace(
    '/if \(!isset\(\$_SESSION\[\'user_id\'\]\)\) \{/',
    '// تهيئة المصفوفات لتجنب أخطاء count()
if (!isset($posts) || !is_array($posts)) {
    $posts = [];
}

if (!isset($_SESSION[\'user_id\']) || empty($_SESSION[\'user_id\'])) {',
    $profile_content
);

$profile_content = str_replace('count($posts)', '(is_array($posts) ? count($posts) : 0)', $profile_content);

file_put_contents('profile.php', $profile_content);

echo "تم إصلاح ملف profile.php بنجاح";
?>
