<?php
$archivos = [
    'admin-fichas.html'          => 'fichas',
    'admin-programas.html'       => 'programas',
    'admin-reportes.html'        => 'reportes',
    'admin-asignaciones.html'    => 'asignaciones',
    'admin-asistencias.html'     => 'asistencias',
    'admin-usuarios.html'        => 'usuarios',
    'admin-aprendices.html'      => 'aprendices',
    'admin-aprendices-crear.html'=> 'aprendices-crear',
    'admin-bienestar-dashboard.html'  => 'bienestar-dashboard',
    'admin-bienestar-asignacion.html' => 'bienestar-asignacion',
    'admin-bienestar-historico.html'  => 'bienestar-historico',
    'admin-poblacion-detalle.html'    => 'poblacion-detalle',
];

$cambios = [];

foreach ($archivos as $archivo => $modulo) {
    $path = __DIR__ . '/../' . $archivo;
    if (!file_exists($path)) {
        echo "⚠️  NO ENCONTRADO: $archivo\n";
        continue;
    }

    $original = file_get_contents($path);
    $content  = $original;

    // 1. Logo del sidebar: quitar style inline
    $content = preg_replace(
        '/(<img\s+src="assets\/img\/asi\.png"[^>]*)\s+style="[^"]*"([^>]*>)/i',
        '<img src="assets/img/asi.png" alt="SenApre Logo" class="logo-principal"$2',
        $content
    );

    // 2. sidebar-subtitle: quitar style inline
    $content = preg_replace(
        '/(<div class="sidebar-subtitle"[^>]*)\s+style="[^"]*"([^>]*>)/i',
        '$1$2',
        $content
    );

    // 3. sidebar-footer: quitar style inline del div
    $content = preg_replace(
        '/<div class="sidebar-footer"\s+style="[^"]*">/i',
        '<div class="sidebar-footer">',
        $content
    );

    // 4. Logo SENA en footer: quitar style inline
    $content = preg_replace(
        '/(<img src="assets\/img\/logosena\.png"[^>]*)\s+style="[^"]*"([^>]*>)/i',
        '<img src="assets/img/logosena.png" alt="SENA Logo"$2',
        $content
    );

    // 5. content-header: quitar style inline del div
    $content = preg_replace(
        '/<div class="content-header"\s+style="[^"]*">/i',
        '<div class="content-header">',
        $content
    );

    // 6. Eliminar <script> de guarda embebido (los que solo verifican isAdmin)
    $content = preg_replace(
        '/<script>\s*(?:const\s+user\s*=.*?;\s*)?if\s*\(!authSystem\.isAuthenticated\(\).*?<\/script>/is',
        '',
        $content
    );

    // 7. Añadir css/admin.css si no está ya incluido
    if (strpos($content, 'css/admin.css') === false) {
        $content = str_replace(
            '<link rel="stylesheet" href="css/main.css">',
            '<link rel="stylesheet" href="css/main.css">'."\n    ".'<link rel="stylesheet" href="css/admin.css">',
            $content
        );
    }

    // 8. Actualizar versión de main.js
    $content = preg_replace('/js\/main\.js(?:\?v=[^"]*)?/', 'js/main.js?v=1.0.6', $content);

    // 9. Eliminar Tailwind CDN si existe
    $content = preg_replace('/<script src="https:\/\/cdn\.tailwindcss\.com"><\/script>\s*/i', '', $content);

    // 10. Actualizar título
    $content = str_replace('AsistNet SENA', 'SenApre', $content);

    // Verificar cambios
    if ($content !== $original) {
        file_put_contents($path, $content);
        $cambios[] = $archivo;
        echo "✅ Refactorizado: $archivo\n";
    } else {
        echo "⚪ Sin cambios:   $archivo\n";
    }
}

echo "\nTotal refactorizados: " . count($cambios) . "/" . count($archivos) . "\n";
