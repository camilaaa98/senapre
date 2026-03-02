<?php
/**
 * Importación COMPLETA - Aprendices y Voceros
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

function procesarArchivo($conn, $archivo, $nombre) {
    echo "\n" . str_repeat("=", 70) . "\n";
    echo "PROCESANDO: $nombre\n";
    echo str_repeat("=", 70) . "\n";
    
    $datos = leerExcel($archivo);
    $headers = $datos[0];
    
    // Buscar columnas
    $colDoc = false;
    $colCorreo = false;
    $colCel = false;
    
    foreach ($headers as $idx => $h) {
        $h_clean = trim(mb_strtolower($h));
        
        if (strpos($h_clean, 'documento') !== false || strpos($h_clean, 'identificación') !== false ||
            strpos($h_clean, 'identificacion') !== false || strpos($h_clean, 'cédula') !== false) {
            $colDoc = $idx;
        }
        if (strpos($h_clean, 'correo') !== false || strpos($h_clean, 'email') !== false || strpos($h_clean, 'mail') !== false) {
            $colCorreo = $idx;
        }
        if (strpos($h_clean, 'celular') !== false || strpos($h_clean, 'teléfono') !== false || 
            strpos($h_clean, 'telefono') !== false || strpos($h_clean, 'móvil') !== false || strpos($h_clean, 'cel') !== false) {
            $colCel = $idx;
        }
    }
    
    echo "Columnas: Doc=$colDoc, Correo=$colCorreo, Cel=$colCel\n";
    echo "Procesando " . (count($datos) - 1) . " filas...\n\n";
    
    $actualizados = 0;
    
    for ($i = 1; $i < count($datos); $i++) {
        $fila = $datos[$i];
        
        $doc = trim($fila[$colDoc] ?? '');
        $correo = $colCorreo !== false ? trim($fila[$colCorreo] ?? '') : '';
        $celular = $colCel !== false ? trim($fila[$colCel] ?? '') : '';
        
        if (empty($doc) || (empty($correo) && empty($celular))) continue;
        
        // Verificar existencia
        $stmt = $conn->prepare("SELECT correo, celular FROM aprendices WHERE documento = :doc");
        $stmt->execute([':doc' => $doc]);
        $aprendiz = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$aprendiz) continue;
        
        // Actualizar solo campos vacíos
        $updates = [];
        $params = [':doc' => $doc];
        
        if (!empty($correo) && empty($aprendiz['correo'])) {
            $updates[] = "correo = :correo";
            $params[':correo'] = $correo;
        }
        
        if (!empty($celular) && empty($aprendiz['celular'])) {
            $updates[] = "celular = :celular";
            $params[':celular'] = $celular;
        }
        
        if (!empty($updates)) {
            $sql = "UPDATE aprendices SET " . implode(', ', $updates) . " WHERE documento = :doc";
            $stmtUpdate = $conn->prepare($sql);
            $stmtUpdate->execute($params);
            $actualizados++;
            
            if ($actualizados <= 5) {
                echo "  ✓ $doc actualizado\n";
            }
        }
    }
    
    if ($actualizados > 5) echo "  ... y " . ($actualizados - 5) . " más\n";
    echo "\nTotal actualizados: $actualizados\n";
    
    return $actualizados;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "=== IMPORTACIÓN COMPLETA DE CONTACTOS ===\n";
    
    $total1 = procesarArchivo($conn, __DIR__ . '/database/Aprendices.xlsx', 'Aprendices.xlsx');
    $total2 = procesarArchivo($conn, __DIR__ . '/database/voceros.xlsx', 'voceros.xlsx');
    
    // Estadísticas finales
    $total = $conn->query("SELECT COUNT(*) FROM aprendices")->fetchColumn();
    $conCorreo = $conn->query("SELECT COUNT(*) FROM aprendices WHERE correo IS NOT NULL AND correo != ''")->fetchColumn();
    $conCelular = $conn->query("SELECT COUNT(*) FROM aprendices WHERE celular IS NOT NULL AND celular != ''")->fetchColumn();
    
    echo "\n" . str_repeat("=", 70) . "\n";
    echo "RESUMEN FINAL\n";
    echo str_repeat("=", 70) . "\n";
    echo "Total actualizados: " . ($total1 + $total2) . "\n";
    echo "Aprendices con correo: $conCorreo (" . round(($conCorreo/$total)*100, 1) . "%)\n";
    echo "Aprendices con celular: $conCelular (" . round(($conCelular/$total)*100, 1) . "%)\n";
    echo "\n✓ IMPORTACIÓN COMPLETADA\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
