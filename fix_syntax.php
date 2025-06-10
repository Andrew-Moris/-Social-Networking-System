<?php
$file = 'u.php';

copy($file, $file . '.bak.' . date('Y-m-d-H-i-s'));
echo "Backup created.\n";

$content = file_get_contents($file);

$replacements = [
    '<?php if (empty($posts)): ?>' => '<?php if (empty($posts)) { ?>',
    '<?php else: ?>' => '<?php } else { ?>',
    '<?php foreach ($posts as $post): ?>' => '<?php foreach ($posts as $post) { ?>',
    '<?php if ($is_current_user_profile): ?>' => '<?php if ($is_current_user_profile) { ?>',
    '<?php endforeach; ?>' => '<?php } // End of foreach ?>',
    '<?php endif; ?>' => '<?php } // End of if ?>',
];

foreach ($replacements as $search => $replace) {
    $content = str_replace($search, $replace, $content);
}

file_put_contents($file, $content);

$output = [];
$return_var = 0;
exec('php -l ' . $file, $output, $return_var);

if ($return_var === 0) {
    echo "Syntax errors fixed successfully!\n";
} else {
    echo "Warning: Syntax errors may still exist. Please check manually.\n";
    echo implode("\n", $output);
}
?>
