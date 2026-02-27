<?php

/**
 * Parse Excel XML
 */

$tempDir = __DIR__ . '/temp_xlsx';
$sharedStringsFile = $tempDir . '/xl/sharedStrings.xml';
$sheet1File = $tempDir . '/xl/worksheets/sheet1.xml';

// 1. Leer Shared Strings
$strings = [];
if (file_exists($sharedStringsFile)) {
    $xml = simplexml_load_file($sharedStringsFile);
    foreach ($xml->si as $si) {
        $strings[] = (string)$si->t;
    }
}

// 2. Leer Sheet 1
if (!file_exists($sheet1File)) {
    die("No se encontró sheet1.xml");
}

$xml = simplexml_load_file($sheet1File);
$rows = [];

foreach ($xml->sheetData->row as $row) {
    $rowData = [];
    $rowIndex = (int)$row['r'];

    foreach ($row->c as $cell) {
        $cellRef = (string)$cell['r']; // Ej: A1
        $type = (string)$cell['t']; // s = shared string, n = number (default)
        $value = (string)$cell->v;

        // Extraer columna (letras)
        preg_match('/([A-Z]+)/', $cellRef, $matches);
        $col = $matches[1];

        if ($type == 's') {
            $val = isset($strings[(int)$value]) ? $strings[(int)$value] : '';
        } else {
            $val = $value;
        }

        $rowData[$col] = $val;
    }
    $rows[] = $rowData;
}

// Mostrar primeros 5 registros
echo "Total filas encontradas: " . count($rows) . "\n\n";
echo "Primeras 5 filas:\n";

for ($i = 0; $i < min(5, count($rows)); $i++) {
    echo "Fila " . ($i + 1) . ": " . json_encode($rows[$i], JSON_UNESCAPED_UNICODE) . "\n";
}
