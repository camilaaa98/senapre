<?php
/**
 * PROCESAMIENTO COMPLETO Y CORRECTO DE VOCEROS
 * Análisis detallado del archivo
 */

require_once __DIR__ . '/api/config/Database.php';

function leerExcel($archivo) {
    $zip = new ZipArchive();
    if (!$zip->open($archivo)) {
        die("No se pudo abrir el archivo Excel\n");
    }
    
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

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "=== PROCESAMIENTO COMPLETO DE VOCEROS ===\n\n";
    
    $datos = leerExcel(__DIR__ . '/database/voceros.xlsx');
    
    echo "Total filas en Excel: " . count($datos) . "\n";
    echo "Encabezados (primeras 20 columnas):\n";
    for ($i = 0; $i < min(20, count($datos[0])); $i++) {
        if (!empty($datos[0][$i])) {
            echo "  [$i] {$datos[0][$i]}\n";
        }
    }
    
    // Detectar columnas automáticamente
    $colFicha = false;
    $colDocumento = false;
    
    foreach ($datos[0] as $idx => $h) {
        $h_lower = mb_strtolower(trim($h));
        
        // Buscar columna de ficha
        if (strpos($h_lower, 'ficha') !== false || 
            strpos($h_lower, 'número') !== false ||
            strpos($h_lower, 'numero') !== false) {
            if ($colFicha === false) { // Solo tomar la primera
                $colFicha = $idx;
                echo "\n✓ Columna FICHA detectada: [$idx] $h\n";
            }
        }
        
        // Buscar columna de documento
        if (strpos($h_lower, 'documento') !== false || 
            strpos($h_lower, 'identificación') !== false ||
            strpos($h_lower, 'identificacion') !== false ||
            strpos($h_lower, 'cédula') !== false ||
            strpos($h_lower, 'cedula') !== false) {
            if ($colDocumento === false) { // Solo tomar la primera
                $colDocumento = $idx;
                echo "✓ Columna DOCUMENTO detectada: [$idx] $h\n";
            }
        }
    }
    
    if ($colFicha === false || $colDocumento === false) {
        echo "\n❌ ERROR: No se encontraron las columnas necesarias\n";
        echo "Ficha: " . ($colFicha !== false ? "Sí" : "NO") . "\n";
        echo "Documento: " . ($colDocumento !== false ? "Sí" : "NO") . "\n";
        exit(1);
    }
    
    echo "\nProcesando datos...\n\n";
    
    // Agrupar por ficha
    $vocerosPorFicha = [];
    $filasValidas = 0;
    
    for ($i = 1; $i < count($datos); $i++) {
        $ficha = trim($datos[$i][$colFicha] ?? '');
        $documento = trim($datos[$i][$colDocumento] ?? '');
        
        if (empty($ficha) || empty($documento)) continue;
        
        $filasValidas++;
        
        if (!isset($vocerosPorFicha[$ficha])) {
            $vocerosPorFicha[$ficha] = [];
        }
        
        $vocerosPorFicha[$ficha][] = $documento;
    }
    
    echo "Filas válidas procesadas: $filasValidas\n";
    echo "Fichas únicas encontradas: " . count($vocerosPorFicha) . "\n\n";
    
    // Actualizar fichas
    $actualizados = 0;
    $noEncontradas = 0;
    
    foreach ($vocerosPorFicha as $numFicha => $documentos) {
        // Verificar si la ficha existe
        $stmt = $conn->prepare("SELECT numero_ficha FROM fichas WHERE numero_ficha = :ficha");
        $stmt->execute([':ficha' => $numFicha]);
        
        if (!$stmt->fetch()) {
            $noEncontradas++;
            continue;
        }
        
        $principal = $documentos[0] ?? null;
        $suplente = isset($documentos[1]) ? $documentos[1] : null;
        
        if ($principal) {
            $updates = ["vocero_principal = :principal"];
            $params = [':ficha' => $numFicha, ':principal' => $principal];
            
            if ($suplente) {
                $updates[] = "vocero_suplente = :suplente";
                $params[':suplente'] = $suplente;
            }
            
            $sql = "UPDATE fichas SET " . implode(', ', $updates) . " WHERE numero_ficha = :ficha";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            
            $actualizados++;
            if ($actualizados <= 10) {
                echo "✓ Ficha $numFicha: Principal=$principal" . ($suplente ? ", Suplente=$suplente" : "") . "\n";
            }
        }
    }
    
    if ($actualizados > 10) {
        echo "  ... y " . ($actualizados - 10) . " más\n";
    }
    
    echo "\n" . str_repeat("=", 70) . "\n";
    echo "RESUMEN:\n";
    echo "  Fichas actualizadas: $actualizados\n";
    echo "  Fichas no encontradas en BD: $noEncontradas\n";
    echo "\n✓ PROCESAMIENTO COMPLETADO\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
?>
