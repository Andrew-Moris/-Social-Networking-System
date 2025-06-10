<?php
$filename = 'u.php';

copy($filename, $filename . '.bak');
echo "Created backup: {$filename}.bak\n";

$content = file_get_contents($filename);

$lines = explode("\n", $content);

$problematicLines = [
    1351 => '                    <?php } // End of foreach ?>',
    1353 => '                <?php } // End of if ?>'
];

foreach ($problematicLines as $lineNum => $lineContent) {
    $actualLineNum = $lineNum - 1;
    
    if (isset($lines[$actualLineNum]) && trim($lines[$actualLineNum]) === trim($lineContent)) {
        $lines[$actualLineNum] = '                    <!-- Removed unmatched PHP closing block -->';
        echo "Fixed line {$lineNum}\n";
    } else {
        echo "Warning: Line {$lineNum} doesn't match expected content.\n";
        echo "Expected: " . trim($lineContent) . "\n";
        echo "Found: " . (isset($lines[$actualLineNum]) ? trim($lines[$actualLineNum]) : "Line not found") . "\n";
    }
}

$fixedContent = implode("\n", $lines);

file_put_contents($filename, $fixedContent);
echo "Fixed file saved to {$filename}\n";

$output = [];
$return_var = 0;
exec("php -l {$filename}", $output, $return_var);

if ($return_var === 0) {
    echo "Success! The PHP syntax is now valid.\n";
} else {
    echo "Warning: There may still be syntax errors in the file:\n";
    echo implode("\n", $output) . "\n";
}
?>
