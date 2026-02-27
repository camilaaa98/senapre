<?php
/**
 * Script simple para mostrar encabezados del Excel
 */

$archivo = __DIR__ . '/database/fichas.xlsx';

if (!file_exists($archivo)) {
    die("Archivo no encontrado: $archivo\n");
}

$zip = new ZipArchive();
if ($zip->open($archivo) !== TRUE) {
    die("No se pudo abrir el archivo\n");
}

$sheetData = $zip->getFromName('xl/worksheets/sheet1.xml');
$sharedStrings = $zip->getFromName('xl/sharedStrings.xml');
$zip->close();

// Parsear strings
$stringsXml = simplexml_load_string($sharedStrings);
$strings = [];
foreach ($stringsXml->si as $si) {
    $strings[] = (string)$si->t;
}

// Parsear primera fila
$xml = simplexml_load_string($sheetData);
$firstRow = $xml->sheetData->row[0];

echo "ENCABEZADOS DEL EXCEL:\n";
echo str_repeat("=", 60) . "\n\n";

$colIndex = 0;
foreach ($firstRow->c as $cell) {
    $value = '';
    if (isset($cell->v)) {
        if (isset($cell['t']) && (string)$cell['t'] === 's') {
            $index = (int)$cell->v;
            $value = $strings[$index] ?? '';
        } else {
            $value = (string)$cell->v;
        }
    }
    
    if (!empty($value)) {
        echo "Columna $colIndex: $value\n";
    }
    $colIndex++;
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "Total de columnas: $colIndex\n";
?>
