<?php
/**
 * CORRECCIÓN COMPLETA DE ESTADOS Y PROCESAMIENTO DE VOCEROS
 */

require_once __DIR__ . '/api/config/Database.php';

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

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "=== CORRECCIÓN COMPLETA ===\n\n";
    
    // PASO 1: Normalizar estados (solo RETIRO y CANCELADA)
    echo "PASO 1: Normalizando estados...\n";
    $conn->exec("UPDATE aprendices SET estado = 'RETIRO' WHERE estado IN ('RETIRADO', 'RETIROS')");
    $conn->exec("UPDATE aprendices SET estado = 'CANCELADA' WHERE estado IN ('CANCELADO', 'CANCELADAS')");
    $conn->exec("UPDATE aprendices SET estado = 'TRASLADO' WHERE estado IN ('TRASLADADO', 'TRASLADOS')");
    echo "✓ Estados normalizados\n\n";
    
    // PASO 2: Actualizar tabla estados (agregar TRASLADO y APLAZADO)
    echo "PASO 2: Actualizando tabla estados...\n";
    
    // Verificar si existen
    $existeTraslado = $conn->query("SELECT COUNT(*) FROM estados WHERE nombre = 'TRASLADO'")->fetchColumn();
    $existeAplazado = $conn->query("SELECT COUNT(*) FROM estados WHERE nombre = 'APLAZADO'")->fetchColumn();
    
    if (!$existeTraslado) {
        $conn->exec("INSERT INTO estados (nombre, color) VALUES ('TRASLADO', '#6366f1')");
        echo "✓ Estado TRASLADO agregado\n";
    }
    
    if (!$existeAplazado) {
        $conn->exec("INSERT INTO estados (nombre, color) VALUES ('APLAZADO', '#f59e0b')");
        echo "✓ Estado APLAZADO agregado\n";
    }
    
    echo "\n";
    
    // PASO 3: Procesar voceros.xlsx
    echo "PASO 3: Procesando voceros.xlsx...\n";
    $datosVoceros = leerExcel(__DIR__ . '/database/voceros.xlsx');
    $headers = $datosVoceros[0];
    
    // Buscar columnas
    $colFicha = false;
    $colDocPrincipal = false;
    $colDocSuplente = false;
    $colTipo = false;
    
    foreach ($headers as $idx => $h) {
        $h_clean = trim(mb_strtolower($h));
        if (strpos($h_clean, 'ficha') !== false) $colFicha = $idx;
        if (strpos($h_clean, 'documento') !== false && strpos($h_clean, 'principal') !== false) $colDocPrincipal = $idx;
        if (strpos($h_clean, 'documento') !== false && strpos($h_clean, 'suplente') !== false) $colDocSuplente = $idx;
        if (strpos($h_clean, 'tipo') !== false || strpos($h_clean, 'rol') !== false) $colTipo = $idx;
    }
    
    echo "Columnas: Ficha=$colFicha, Principal=$colDocPrincipal, Suplente=$colDocSuplente\n\n";
    
    $vocerosPorFicha = [];
    
    for ($i = 1; $i < count($datosVoceros); $i++) {
        $fila = $datosVoceros[$i];
        $ficha = trim($fila[$colFicha] ?? '');
        $tipo = trim(mb_strtolower($fila[$colTipo] ?? ''));
        
        if (empty($ficha)) continue;
        
        if (!isset($vocerosPorFicha[$ficha])) {
            $vocerosPorFicha[$ficha] = ['principal' => null, 'suplente' => null];
        }
        
        if (strpos($tipo, 'principal') !== false && $colDocPrincipal !== false) {
            $vocerosPorFicha[$ficha]['principal'] = trim($fila[$colDocPrincipal] ?? '');
        } elseif (strpos($tipo, 'suplente') !== false && $colDocSuplente !== false) {
            $vocerosPorFicha[$ficha]['suplente'] = trim($fila[$colDocSuplente] ?? '');
        }
    }
    
    // Actualizar fichas con voceros
    $actualizados = 0;
    foreach ($vocerosPorFicha as $numFicha => $voceros) {
        $updates = [];
        $params = [':ficha' => $numFicha];
        
        if (!empty($voceros['principal'])) {
            $updates[] = "vocero_principal = :principal";
            $params[':principal'] = $voceros['principal'];
        }
        
        if (!empty($voceros['suplente'])) {
            $updates[] = "vocero_suplente = :suplente";
            $params[':suplente'] = $voceros['suplente'];
        }
        
        if (!empty($updates)) {
            $sql = "UPDATE fichas SET " . implode(', ', $updates) . " WHERE numero_ficha = :ficha";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $actualizados++;
            
            if ($actualizados <= 5) {
                echo "✓ Ficha $numFicha: Principal={$voceros['principal']}, Suplente={$voceros['suplente']}\n";
            }
        }
    }
    
    if ($actualizados > 5) echo "  ... y " . ($actualizados - 5) . " más\n";
    echo "\nTotal fichas actualizadas con voceros: $actualizados\n\n";
    
    // RESUMEN
    echo str_repeat("=", 70) . "\n";
    echo "RESUMEN FINAL\n";
    echo str_repeat("=", 70) . "\n";
    
    $stmt = $conn->query("SELECT nombre FROM estados ORDER BY nombre");
    $estadosFinales = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Estados disponibles: " . implode(', ', $estadosFinales) . "\n\n";
    
    $vocerosFichas = $conn->query("SELECT COUNT(*) FROM fichas WHERE vocero_principal IS NOT NULL OR vocero_suplente IS NOT NULL")->fetchColumn();
    echo "Fichas con voceros asignados: $vocerosFichas\n";
    
    echo "\n✓ CORRECCIÓN COMPLETADA\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
