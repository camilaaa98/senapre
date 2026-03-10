<?php
/**
 * Análisis de Fichas - Filtro: Oferta Abierta en Florencia
 * Extrae fichas del Excel que cumplan ambos criterios
 */

require_once __DIR__ . '/api/config/Database.php';

function leerExcel($archivo) {
    if (!file_exists($archivo)) {
        die("Error: El archivo $archivo no existe.\n");
    }

    $zip = new ZipArchive();
    if ($zip->open($archivo) !== TRUE) {
        die("Error: No se pudo abrir el archivo Excel.\n");
    }

    $sheetData = $zip->getFromName('xl/worksheets/sheet1.xml');
    $sharedStrings = $zip->getFromName('xl/sharedStrings.xml');
    $zip->close();

    if (!$sheetData || !$sharedStrings) {
        die("Error: No se pudo leer el contenido del archivo Excel.\n");
    }

    // Parsear strings compartidos
    $stringsXml = simplexml_load_string($sharedStrings);
    $strings = [];
    foreach ($stringsXml->si as $si) {
        $strings[] = (string)$si->t;
    }

    // Parsear datos de la hoja
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

try {
    echo "=== ANÁLISIS DE FICHAS: OFERTA ABIERTA EN FLORENCIA ===\n\n";
    
    $archivoExcel = __DIR__ . '/database/fichas.xlsx';
    echo "Leyendo archivo: $archivoExcel\n\n";
    $datosExcel = leerExcel($archivoExcel);

    if (empty($datosExcel)) {
        die("Error: No se pudieron leer datos del archivo Excel.\n");
    }

    echo "Total de filas leídas: " . count($datosExcel) . "\n\n";

    // Mostrar encabezados para identificar columnas
    echo "=== ESTRUCTURA DEL ARCHIVO (Primera fila - Encabezados) ===\n";
    if (!empty($datosExcel[0])) {
        foreach ($datosExcel[0] as $index => $header) {
            echo "Columna $index: $header\n";
        }
    }
    echo "\n";

    // Mostrar algunas filas de ejemplo para entender la estructura
    echo "=== MUESTRA DE DATOS (Filas 2-6) ===\n";
    for ($i = 1; $i <= min(5, count($datosExcel) - 1); $i++) {
        echo "\nFila $i:\n";
        foreach ($datosExcel[$i] as $index => $value) {
            if (!empty($value)) {
                $header = $datosExcel[0][$index] ?? "Col_$index";
                echo "  $header: $value\n";
            }
        }
    }
    echo "\n";

    // Identificar índices de columnas relevantes
    $headers = $datosExcel[0];
    $colNumeroFicha = array_search('Número Ficha', $headers);
    $colTipoOferta = array_search('Tipo Oferta', $headers);
    $colMunicipio = array_search('Municipio', $headers);
    $colPrograma = false;
    
    // Buscar variaciones del nombre de columna
    foreach ($headers as $idx => $header) {
        if (stripos($header, 'ficha') !== false && $colNumeroFicha === false) {
            $colNumeroFicha = $idx;
        }
        if (stripos($header, 'oferta') !== false && $colTipoOferta === false) {
            $colTipoOferta = $idx;
        }
        if (stripos($header, 'municipio') !== false && $colMunicipio === false) {
            $colMunicipio = $idx;
        }
        if (stripos($header, 'programa') !== false) {
            $colPrograma = $idx;
        }
    }

    echo "=== ÍNDICES DE COLUMNAS IDENTIFICADOS ===\n";
    echo "Número Ficha: " . ($colNumeroFicha !== false ? "Columna $colNumeroFicha" : "NO ENCONTRADA") . "\n";
    echo "Tipo Oferta: " . ($colTipoOferta !== false ? "Columna $colTipoOferta" : "NO ENCONTRADA") . "\n";
    echo "Municipio: " . ($colMunicipio !== false ? "Columna $colMunicipio" : "NO ENCONTRADA") . "\n";
    echo "Programa: " . ($colPrograma !== false ? "Columna $colPrograma" : "NO ENCONTRADA") . "\n\n";

    // Filtrar fichas
    $fichasFiltradas = [];
    
    for ($i = 1; $i < count($datosExcel); $i++) {
        $fila = $datosExcel[$i];
        
        $numeroFicha = $colNumeroFicha !== false ? trim($fila[$colNumeroFicha] ?? '') : '';
        $tipoOferta = $colTipoOferta !== false ? trim($fila[$colTipoOferta] ?? '') : '';
        $municipio = $colMunicipio !== false ? trim($fila[$colMunicipio] ?? '') : '';
        $programa = $colPrograma !== false ? trim($fila[$colPrograma] ?? '') : '';
        
        // Filtro: Oferta Abierta Y Florencia
        if (stripos($tipoOferta, 'abierta') !== false && 
            stripos($municipio, 'florencia') !== false &&
            !empty($numeroFicha)) {
            
            $fichasFiltradas[] = [
                'numero' => $numeroFicha,
                'tipo_oferta' => $tipoOferta,
                'municipio' => $municipio,
                'programa' => $programa
            ];
        }
    }

    echo "=== RESULTADOS DEL FILTRO ===\n";
    echo "Fichas encontradas: " . count($fichasFiltradas) . "\n\n";

    if (!empty($fichasFiltradas)) {
        echo "LISTADO DE FICHAS (Oferta Abierta en Florencia):\n";
        echo str_repeat("-", 80) . "\n";
        
        foreach ($fichasFiltradas as $ficha) {
            echo "Ficha: {$ficha['numero']}\n";
            echo "  Tipo Oferta: {$ficha['tipo_oferta']}\n";
            echo "  Municipio: {$ficha['municipio']}\n";
            if (!empty($ficha['programa'])) {
                echo "  Programa: {$ficha['programa']}\n";
            }
            echo "\n";
        }
    } else {
        echo "No se encontraron fichas que cumplan ambos criterios.\n";
    }

    // Guardar reporte
    $reporte = "=== FICHAS: OFERTA ABIERTA EN FLORENCIA ===\n\n";
    $reporte .= "Fecha: " . date('Y-m-d H:i:s') . "\n";
    $reporte .= "Total encontradas: " . count($fichasFiltradas) . "\n\n";
    
    foreach ($fichasFiltradas as $ficha) {
        $reporte .= "Ficha: {$ficha['numero']}\n";
        $reporte .= "Tipo Oferta: {$ficha['tipo_oferta']}\n";
        $reporte .= "Municipio: {$ficha['municipio']}\n";
        $reporte .= "Programa: {$ficha['programa']}\n";
        $reporte .= str_repeat("-", 50) . "\n";
    }
    
    file_put_contents(__DIR__ . '/fichas_abiertas_florencia.txt', $reporte);
    echo "\nReporte guardado en: fichas_abiertas_florencia.txt\n";

    // Comparar con base de datos
    $db = new Database();
    $conn = $db->getConnection();
    $stmtFichas = $conn->query("SELECT numero_ficha FROM fichas");
    $fichasDB = $stmtFichas->fetchAll(PDO::FETCH_COLUMN);
    
    echo "\n=== COMPARACIÓN CON BASE DE DATOS ===\n";
    $nuevas = [];
    $existentes = [];
    
    foreach ($fichasFiltradas as $ficha) {
        if (in_array($ficha['numero'], $fichasDB)) {
            $existentes[] = $ficha['numero'];
        } else {
            $nuevas[] = $ficha['numero'];
        }
    }
    
    echo "Fichas ya en BD: " . count($existentes) . "\n";
    echo "Fichas nuevas (no en BD): " . count($nuevas) . "\n";
    
    if (!empty($nuevas)) {
        echo "\nFichas nuevas para importar:\n";
        foreach ($nuevas as $num) {
            echo "  - $num\n";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
?>
