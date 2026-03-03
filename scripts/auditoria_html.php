<?php
$base = __DIR__;
$htmlFiles = glob($base . '/*.html');
usort($htmlFiles, fn($a,$b) => strcmp(basename($a), basename($b)));

echo "ARCHIVO                              <style>  <script_inline>  style_attrs\n";
echo str_repeat("-", 75) . "\n";
foreach ($htmlFiles as $file) {
    $name    = basename($file);
    $content = file_get_contents($file);
    $styles  = substr_count($content, '<style');
    // Scripts inline = scripts SIN src=
    $scripts = preg_match_all('/<script(?![^>]*src=)[^>]*>[\s\S]*?<\/script>/i', $content, $m);
    $inlines = preg_match_all('/\sstyle="[^"]{5,}"/i', $content);
    $tot = $styles + $scripts + $inlines;
    $icon = $tot > 0 ? "❌" : "✅";
    printf("%-40s %5d  %14d  %10d  %s\n", $name, $styles, $scripts, $inlines, $icon);
}
