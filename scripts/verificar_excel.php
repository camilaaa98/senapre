<?php
/**
 * Verificar estructura de archivos Excel y mostrar datos de muestra
 */

function leerExcel($archivo) {
    $zip = new ZipArchive();
    $zip->open($archivo);
    $sheetData = $zip->getFromName('xl/worksheets/sheet1.xml');
    $sharedStrings = $zip->getFromName('xl/sharedStrings.xml');
    $zip->close();

    $stringsXml = simplexml_load_string($sharedStrings);
    $strings = [];
    foreach ($stringsXml->si as $si) {
        $strings[] = (string)$si->t;
    }

    $xml = simplexml_load_string($sheetData);
    $rows = [];
    foreach ($xml->sheetData->row as $row) {
        $rowData = [];
        foreach ($row->c as $cell) {
            $value = '';
            if (isset($cell->v)) {
                if (isset($cell['t']) && (string)$cell['t'] === 's') {
                    $index = (int)$cell->v;
                    $value = $strings[$index] ?? '';
                } else {
                    $value = (string)$cell->v;
                }
            }
            $rowData[] = $value;
        }
        $rows[] = $rowData;
    }
    return $rows;
}

echo "=== VERIFICACIÓN DE ARCHIVOS EXCEL ===\n\n";

// Aprendices.xlsx
echo "ARCHIVO: Aprendices.xlsx\n";
echo str_repeat("-", 60) . "\n";
$datosAprendices = leerExcel(__DIR__ . '/database/Aprendices.xlsx');
echo "Encabezados:\n";
foreach ($datosAprendices[0] as $idx => $h) {
    if (!empty($h)) {
        echo "  [$idx] $h\n";
    }
}

echo "\nPrimeras 3 filas de datos:\n";
for ($i = 1; $i <= min(3, count($datosAprendices) - 1); $i++) {
    echo "\nFila $i:\n";
    foreach ($datosAprendices[$i] as $idx => $val) {
        if (!empty($val) && $idx < 10) {
            $header = $datosAprendices[0][$idx] ?? "Col_$idx";
            echo "  $header: $val\n";
        }
    }
}

echo "\n\n";

// voceros.xlsx
echo "ARCHIVO: voceros.xlsx\n";
echo str_repeat("-", 60) . "\n";
$datosVoceros = leerExcel(__DIR__ . '/database/voceros.xlsx');
echo "Encabezados:\n";
foreach ($datosVoceros[0] as $idx => $h) {
    if (!empty($h)) {
        echo "  [$idx] $h\n";
    }
}

echo "\nPrimeras 3 filas de datos:\n";
for ($i = 1; $i <= min(3, count($datosVoceros) - 1); $i++) {
    echo "\nFila $i:\n";
    foreach ($datosVoceros[$i] as $idx => $val) {
        if (!empty($val) && $idx < 10) {
            $header = $datosVoceros[0][$idx] ?? "Col_$idx";
            echo "  $header: $val\n";
        }
    }
}
?>
