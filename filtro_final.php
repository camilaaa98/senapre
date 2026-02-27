<?php
/**
 * Filtro Final: Fichas con Oferta Abierta en Florencia
 */

$archivo = __DIR__ . '/database/fichas.xlsx';

$zip = new ZipArchive();
$zip->open($archivo);
$sheetData = $zip->getFromName('xl/worksheets/sheet1.xml');
$sharedStrings = $zip->getFromName('xl/sharedStrings.xml');
$zip->close();

// Parsear strings
$stringsXml = simplexml_load_string($sharedStrings);
$strings = [];
foreach ($stringsXml->si as $si) {
    $strings[] = (string)$si->t;
}

// Parsear filas
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

// Mostrar encabezados
$headers = $rows[0];
echo "=== ENCABEZADOS (Total: " . count($headers) . ") ===\n";
foreach ($headers as $idx => $h) {
    if (!empty($h)) {
        echo "$idx: $h\n";
    }
}
echo "\n";

// Buscar columnas clave
$colFicha = false;
$colOferta = false;
$colMunicipio = false;
$colPrograma = false;

foreach ($headers as $idx => $h) {
    $h_lower = mb_strtolower($h);
    if (strpos($h_lower, 'ficha') !== false && strpos($h_lower, 'número') !== false) {
        $colFicha = $idx;
    }
    if (strpos($h_lower, 'oferta') !== false && strpos($h_lower, 'tipo') !== false) {
        $colOferta = $idx;
    }
    if (strpos($h_lower, 'municipio') !== false) {
        $colMunicipio = $idx;
    }
    if (strpos($h_lower, 'programa') !== false && strpos($h_lower, 'formación') !== false) {
        $colPrograma = $idx;
    }
}

echo "=== COLUMNAS IDENTIFICADAS ===\n";
echo "Número Ficha: " . ($colFicha !== false ? "Col $colFicha ({$headers[$colFicha]})" : "NO ENCONTRADA") . "\n";
echo "Tipo Oferta: " . ($colOferta !== false ? "Col $colOferta ({$headers[$colOferta]})" : "NO ENCONTRADA") . "\n";
echo "Municipio: " . ($colMunicipio !== false ? "Col $colMunicipio ({$headers[$colMunicipio]})" : "NO ENCONTRADA") . "\n";
echo "Programa: " . ($colPrograma !== false ? "Col $colPrograma ({$headers[$colPrograma]})" : "NO ENCONTRADA") . "\n\n";

// Filtrar
$fichasFiltradas = [];
for ($i = 1; $i < count($rows); $i++) {
    $row = $rows[$i];
    
    $ficha = $colFicha !== false ? trim($row[$colFicha] ?? '') : '';
    $oferta = $colOferta !== false ? mb_strtolower(trim($row[$colOferta] ?? '')) : '';
    $municipio = $colMunicipio !== false ? mb_strtolower(trim($row[$colMunicipio] ?? '')) : '';
    $programa = $colPrograma !== false ? trim($row[$colPrograma] ?? '') : '';
    
    // Filtro: Oferta Abierta Y Florencia
    if (strpos($oferta, 'abierta') !== false && 
        strpos($municipio, 'florencia') !== false &&
        !empty($ficha)) {
        
        $fichasFiltradas[] = [
            'numero' => $ficha,
            'oferta' => $row[$colOferta] ?? '',
            'municipio' => $row[$colMunicipio] ?? '',
            'programa' => $programa
        ];
    }
}

echo "=== RESULTADOS ===\n";
echo "Fichas encontradas: " . count($fichasFiltradas) . "\n\n";

if (!empty($fichasFiltradas)) {
    foreach ($fichasFiltradas as $f) {
        echo "Ficha: {$f['numero']}\n";
        echo "  Oferta: {$f['oferta']}\n";
        echo "  Municipio: {$f['municipio']}\n";
        echo "  Programa: {$f['programa']}\n";
        echo str_repeat("-", 60) . "\n";
    }
    
    // Guardar
    $reporte = "FICHAS: OFERTA ABIERTA EN FLORENCIA\n\n";
    foreach ($fichasFiltradas as $f) {
        $reporte .= "Ficha: {$f['numero']}\n";
        $reporte .= "Programa: {$f['programa']}\n\n";
    }
    file_put_contents(__DIR__ . '/fichas_florencia_final.txt', $reporte);
    echo "\nReporte guardado en: fichas_florencia_final.txt\n";
} else {
    echo "No se encontraron fichas que cumplan ambos criterios.\n";
    echo "Verifique que existan fichas con:\n";
    echo "  - Tipo de Oferta = 'Abierta'\n";
    echo "  - Municipio = 'Florencia'\n";
}
?>
