<?php
/**
 * scripts/forzar_cache_css.php
 * Añade/actualiza ?v= en todos los links de css/main.css en los HTML
 */
$version  = '2.0.1';
$archivos = glob(__DIR__ . '/../*.html');
$updated  = 0;

foreach ($archivos as $file) {
    $original = file_get_contents($file);
    // Reemplazar cualquier versión de main.css (con o sin ?v=)
    $nuevo = preg_replace(
        '/href="css\/main\.css(?:\?v=[^"]*)?"/i',
        'href="css/main.css?v=' . $version . '"',
        $original
    );
    // También actualizar admin.css, vocero.css, bienestar.css, instructor.css
    $nuevo = preg_replace(
        '/href="css\/(admin|vocero|bienestar|instructor)\.css(?:\?v=[^"]*)?"/i',
        'href="css/$1.css?v=' . $version . '"',
        $nuevo
    );
    if ($nuevo !== $original) {
        file_put_contents($file, $nuevo);
        echo "✅ " . basename($file) . "\n";
        $updated++;
    } else {
        echo "⚪ " . basename($file) . "\n";
    }
}
echo "\nActualizados: $updated/" . count($archivos) . "\n";
