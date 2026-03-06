<?php
$dir = __DIR__ . '/api/';
$files = glob($dir . '*.php');
$subDir = $dir . 'utils/';
if (is_dir($subDir)) {
    $files = array_merge($files, glob($subDir . '*.php'));
}

$count = 0;
foreach ($files as $file) {
    $content = file_get_contents($file);
    if (strpos($content, 'new Database()') !== false) {
        $newContent = str_replace('new Database()', 'Database::getInstance()', $content);
        file_put_contents($file, $newContent);
        echo "Fixed: " . basename($file) . "\n";
        $count++;
    }
}

echo "\nTotal files fixed: $count\n";
?>
