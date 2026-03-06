<?php
$dir = __DIR__ . '/api/';
$files = glob($dir . '*.php');
$subDir = $dir . 'utils/';
if (is_dir($subDir)) {
    $files = array_merge($files, glob($subDir . '*.php'));
}

// También incluir scripts raíz que se usan en producción
$files[] = __DIR__ . '/sync_fix.php';
$files[] = __DIR__ . '/robust_sync.php';
$files[] = __DIR__ . '/direct_sync.php';
$files[] = __DIR__ . '/sync_voceros.php';

$count = 0;
foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);
    
    // Buscar patrones de PRAGMA busy_timeout o foreign_keys
    $pattern = '/\$conn->exec\(["\']PRAGMA busy_timeout = \d+["\']\);/';
    if (preg_match($pattern, $content)) {
        $newContent = preg_replace(
            '/(\$conn->exec\(["\']PRAGMA busy_timeout = \d+["\']\);)/', 
            'if (!getenv(\'DATABASE_URL\')) { $1 }', 
            $content
        );
        file_put_contents($file, $newContent);
        echo "Fixed (busy_timeout): " . basename($file) . "\n";
        $count++;
    }
    
    // También detectar PRAGMA table_info (usado para esquemas)
    $patternTableInfo = '/\$conn->query\(["\']PRAGMA table_info\([^)]+\)["\']\)/';
    if (preg_match($patternTableInfo, $content)) {
        // En este caso, si es Postgres, devolvemos un objeto mock o saltamos la lógica
        // Pero para simplificar, mejor envolver toda la sección en el check de SQLite
        // Aunque eso requiere análisis semántico manual.
        // Por ahora, solo buscaremos PRAGMA literal para alertar o envolver.
    }
    
    // Simplificación: envolver cualquier línea que contenga la cadena "PRAGMA" y un query/exec
    $lines = explode("\n", $content);
    $newLines = [];
    $changedFile = false;
    foreach ($lines as $line) {
        if (strpos($line, 'PRAGMA') !== false && (strpos($line, '->query(') !== false || strpos($line, '->exec(') !== false)) {
            if (strpos($line, 'if (!getenv(\'DATABASE_URL\'))') === false) {
                $line = '    if (!getenv(\'DATABASE_URL\')) { ' . trim($line) . ' }';
                $changedFile = true;
            }
        }
        $newLines[] = $line;
    }
    
    if ($changedFile) {
        file_put_contents($file, implode("\n", $newLines));
        echo "Fixed (Generic PRAGMA): " . basename($file) . "\n";
        $count++;
    }
}

echo "\nTotal files analyzed/fixed for PRAGMA compatibility: $count\n";
?>
