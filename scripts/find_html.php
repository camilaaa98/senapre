<?php
function rsearch($folder, $pattern) {
    $dir = new RecursiveDirectoryIterator($folder);
    $ite = new RecursiveIteratorIterator($dir);
    $files = new RegexIterator($ite, $pattern, RegexIterator::GET_MATCH);
    $fileList = array();
    foreach($files as $name => $object) {
        $fileList[] = $name;
    }
    return $fileList;
}

echo "=== BUSCANDO ARCHIVOS HTML EN EL PROYECTO ===\n";
$htmlFiles = rsearch(__DIR__, '/.*\.html$/');
foreach($htmlFiles as $f) {
    echo "$f\n";
}
?>
