<?php
/**
 * auditoria_rutas.php
 * Verifica EXACTAMENTE qué imágenes sueltas de la raíz son referenciadas
 * en los archivos HTML y en qué rutas.
 */
$base = __DIR__;
$imagenesRaiz = ['asi.png','logosena.png','LGBTI.png','afrodedescendientes.png'];
$htmlFiles    = glob($base . '/*.html');

echo "=== REFERENCIAS DE IMÁGENES EN HTML ===\n\n";

foreach ($imagenesRaiz as $img) {
    echo "Imagen: $img\n";
    $refs = [];
    foreach ($htmlFiles as $file) {
        $content = file_get_contents($file);
        // Buscar todas las formas en que se puede referenciar
        if (preg_match_all('/["\']([^"\']*' . preg_quote($img, '/') . ')["\']/', $content, $m)) {
            foreach (array_unique($m[1]) as $ref) {
                $refs[] = ['archivo' => basename($file), 'ruta' => $ref];
            }
        }
    }
    if (empty($refs)) {
        echo "   Sin referencias en HTML\n";
    } else {
        foreach ($refs as $r) {
            echo "   [{$r['archivo']}] → '{$r['ruta']}'\n";
        }
    }
    echo "\n";
}

echo "=== IMÁGENES EXISTENTES EN /assets/img/ ===\n";
foreach (glob($base . '/assets/img/*') as $f) {
    echo "  " . basename($f) . "\n";
}

echo "\n=== ARCHIVOS PHP EN RAÍZ QUE NO SON DE SISTEMA ===\n";
$phpSistema = ['auditoria_solid.php', 'auditoria_rutas.php', 'health.php'];
foreach (glob($base . '/*.php') as $f) {
    $n = basename($f);
    if (!in_array($n, $phpSistema)) {
        echo "  $n\n";
    }
}
