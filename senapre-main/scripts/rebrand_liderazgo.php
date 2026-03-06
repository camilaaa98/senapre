<?php
$dir = __DIR__;
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

$replacements = [
    'liderazgo' => 'liderazgo',
    'admin-liderazgo' => 'liderazgo',
    'liderazgo' => 'liderazgo',
    'Panel de Liderazgo' => 'Panel de Liderazgo',
    'Liderazgo Estudiantil' => 'Liderazgo Estudiantil',
    'liderazgo.php' => 'liderazgo.php' // I also rename the API for consistency
];

foreach ($files as $file) {
    if ($file->isDir()) continue;
    $path = $file->getRealPath();
    if (strpos($path, '.git') !== false) continue;
    if (strpos($path, 'node_modules') !== false) continue;
    if (pathinfo($path, PATHINFO_EXTENSION) === 'php' || pathinfo($path, PATHINFO_EXTENSION) === 'html' || pathinfo($path, PATHINFO_EXTENSION) === 'js') {
        $content = file_get_contents($path);
        $newContent = str_replace(array_keys($replacements), array_values($replacements), $content);
        if ($content !== $newContent) {
            file_put_contents($path, $newContent);
            echo "Updated: " . $file->getFilename() . "\n";
        }
    }
}

// Rename the API file too
if (file_exists('api/liderazgo.php')) {
    rename('api/liderazgo.php', 'api/liderazgo.php');
    echo "API Renamed to liderazgo.php\n";
}
