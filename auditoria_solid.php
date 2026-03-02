<?php
/**
 * auditoria_solid.php
 * Auditoría completa de principios SOLID: CSS/JS embebido, organización de archivos
 */

$base = __DIR__;

// ── 1. Archivos HTML − detectar <style> y <script> embebidos ──────────────
$htmlFiles = glob($base . '/*.html');
$violaciones = [];

foreach ($htmlFiles as $file) {
    $name    = basename($file);
    $content = file_get_contents($file);
    
    // Contar bloques <style>
    $styles  = preg_match_all('/<style[\s>]/i', $content);
    // Contar bloques <script> con código real (no solo src=)
    $scripts = preg_match_all('/<script(?![^>]*src=)[^>]*>[\s\S]*?<\/script>/i', $content, $m);
    // Contar atributos style="" con contenido
    $inlines = preg_match_all('/\sstyle="[^"]{10,}"/i', $content);
    
    if ($styles > 0 || $scripts > 0 || $inlines > 0) {
        $violaciones[] = [
            'archivo'  => $name,
            'styles'   => $styles,
            'scripts'  => $scripts,
            'inlines'  => $inlines,
        ];
    }
}

// ── 2. Archivos raíz − que no deberían estar ahí ──────────────────────────
$archivosRaiz = [];
$phpSueltos = glob($base . '/*.php');
$imgsSueltas = glob($base . '/*.{png,jpg,jpeg,gif,svg}', GLOB_BRACE);
$txtSueltos  = glob($base . '/*.{txt,log,json,yaml}', GLOB_BRACE);

// ── 3. Carpetas existentes ──────────────────────────────────────────────
$carpetas = array_filter(glob($base . '/*'), 'is_dir');
$carpetasNombre = array_map('basename', $carpetas);
sort($carpetasNombre);

// ── 4. Archivos JS − verificar que todos están en /js ──────────────────
$jsRaiz = glob($base . '/*.js');

// ── 5. Archivos CSS − verificar que están en /css ───────────────────────
$cssRaiz = glob($base . '/*.css');

echo "══════════════════════════════════════════════════════════════════\n";
echo "  AUDITORÍA SOLID — PLATAFORMA SENAPRE\n";
echo "══════════════════════════════════════════════════════════════════\n\n";

echo "📁 CARPETAS EXISTENTES:\n";
foreach ($carpetasNombre as $c) {
    if (!in_array($c, ['.git','.venv','node_modules'])) echo "  ✓ /$c\n";
}

echo "\n🔴 VIOLACIONES SOLID — CSS/JS EMBEBIDO EN HTML:\n";
echo str_repeat("─", 65) . "\n";
if (empty($violaciones)) {
    echo "  ✅ Sin violaciones encontradas.\n";
} else {
    foreach ($violaciones as $v) {
        echo "  ❌ {$v['archivo']}\n";
        if ($v['styles']  > 0) echo "       <style> inline   : {$v['styles']} bloque(s)\n";
        if ($v['scripts'] > 0) echo "       <script> inline  : {$v['scripts']} bloque(s)\n";
        if ($v['inlines'] > 0) echo "       style=\"...\"attr  : {$v['inlines']} atributo(s)\n";
    }
}

echo "\n⚠️  ARCHIVOS PHP SUELTOS EN RAÍZ (deberían estar en /api o /scripts):\n";
echo str_repeat("─", 65) . "\n";
$phpIgnorar = ['health.php','index.php'];
foreach ($phpSueltos as $f) {
    $n = basename($f);
    if (!in_array($n, $phpIgnorar)) echo "  📄 $n\n";
}

echo "\n⚠️  IMÁGENES SUELTAS EN RAÍZ (deberían estar en /assets/img):\n";
echo str_repeat("─", 65) . "\n";
foreach ($imgsSueltas as $f) echo "  🖼️  " . basename($f) . "\n";

echo "\n⚠️  JS EN RAÍZ (deberían estar en /js):\n";
echo str_repeat("─", 65) . "\n";
if (empty($jsRaiz)) {
    echo "  ✅ Sin JS en raíz.\n";
} else {
    foreach ($jsRaiz as $f) echo "  📜 " . basename($f) . "\n";
}

echo "\n⚠️  CSS EN RAÍZ (deberían estar en /css):\n";
echo str_repeat("─", 65) . "\n";
if (empty($cssRaiz)) {
    echo "  ✅ Sin CSS en raíz.\n";
} else {
    foreach ($cssRaiz as $f) echo "  🎨 " . basename($f) . "\n";
}

echo "\n📊 RESUMEN:\n";
echo str_repeat("─", 65) . "\n";
echo "  HTML con violaciones SOLID : " . count($violaciones) . "/" . count($htmlFiles) . "\n";
echo "  PHP sueltos en raíz        : " . count($phpSueltos) . "\n";
echo "  Imágenes sueltas en raíz   : " . count($imgsSueltas) . "\n";
echo "══════════════════════════════════════════════════════════════════\n";
