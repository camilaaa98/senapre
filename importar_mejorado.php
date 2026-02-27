<?php
/**
 * Importación Mejorada de Datos de Contacto
 * Con logging detallado y verificación
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
    
    echo "=== IMPORTACIÓN DE CONTACTOS (VERSIÓN MEJORADA) ===\n\n";
    
    // ========== APRENDICES.XLSX ==========
    echo "PASO 1: Procesando Aprendices.xlsx\n";
    echo str_repeat("-", 70) . "\n";
    
    $datos = leerExcel(__DIR__ . '/database/Aprendices.xlsx');
    $headers = $datos[0];
    
    // Buscar columnas (mostrando todas para debug)
    echo "Buscando columnas...\n";
    $colDoc = false;
    $colCorreo = false;
    $colCel = false;
    
    foreach ($headers as $idx => $h) {
        $h_clean = trim(mb_strtolower($h));
        
        // Documento
        if (strpos($h_clean, 'documento') !== false || 
            strpos($h_clean, 'identificación') !== false ||
            strpos($h_clean, 'identificacion') !== false ||
            strpos($h_clean, 'cédula') !== false ||
            strpos($h_clean, 'cedula') !== false) {
            $colDoc = $idx;
            echo "  ✓ Documento encontrado en columna $idx: '$h'\n";
        }
        
        // Correo
        if (strpos($h_clean, 'correo') !== false || 
            strpos($h_clean, 'email') !== false ||
            strpos($h_clean, 'e-mail') !== false ||
            strpos($h_clean, 'mail') !== false) {
            $colCorreo = $idx;
            echo "  ✓ Correo encontrado en columna $idx: '$h'\n";
        }
        
        // Celular
        if (strpos($h_clean, 'celular') !== false || 
            strpos($h_clean, 'teléfono') !== false ||
            strpos($h_clean, 'telefono') !== false ||
            strpos($h_clean, 'móvil') !== false ||
            strpos($h_clean, 'movil') !== false ||
            strpos($h_clean, 'cel') !== false) {
            $colCel = $idx;
            echo "  ✓ Celular encontrado en columna $idx: '$h'\n";
        }
    }
    
    if ($colDoc === false) {
        die("\nERROR: No se encontró columna de Documento\n");
    }
    
    echo "\nColumnas a usar:\n";
    echo "  Documento: Columna $colDoc\n";
    echo "  Correo: " . ($colCorreo !== false ? "Columna $colCorreo" : "NO ENCONTRADA") . "\n";
    echo "  Celular: " . ($colCel !== false ? "Columna $colCel" : "NO ENCONTRADA") . "\n\n";
    
    // Procesar filas
    $actualizados = 0;
    $noEncontrados = 0;
    $sinDatos = 0;
    
    echo "Procesando " . (count($datos) - 1) . " filas...\n\n";
    
    for ($i = 1; $i < count($datos); $i++) {
        $fila = $datos[$i];
        
        $doc = trim($fila[$colDoc] ?? '');
        $correo = $colCorreo !== false ? trim($fila[$colCorreo] ?? '') : '';
        $celular = $colCel !== false ? trim($fila[$colCel] ?? '') : '';
        
        if (empty($doc)) continue;
        
        // Verificar si tiene datos para actualizar
        if (empty($correo) && empty($celular)) {
            $sinDatos++;
            continue;
        }
        
        // Buscar en BD
        $stmt = $conn->prepare("SELECT documento, correo, celular FROM aprendices WHERE documento = :doc");
        $stmt->execute([':doc' => $doc]);
        $aprendiz = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$aprendiz) {
            $noEncontrados++;
            if ($noEncontrados <= 5) {
                echo "  ⚠ Documento $doc no encontrado en BD\n";
            }
            continue;
        }
        
        // Determinar qué actualizar
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
        
        if (empty($updates)) {
            continue; // Ya tiene los datos
        }
        
        // Actualizar
        $sql = "UPDATE aprendices SET " . implode(', ', $updates) . " WHERE documento = :doc";
        $stmtUpdate = $conn->prepare($sql);
        $stmtUpdate->execute($params);
        
        $actualizados++;
        if ($actualizados <= 10) {
            echo "  ✓ Actualizado: $doc";
            if (isset($params[':correo'])) echo " (correo)";
            if (isset($params[':celular'])) echo " (celular)";
            echo "\n";
        }
    }
    
    echo "\n";
    if ($actualizados > 10) {
        echo "  ... y " . ($actualizados - 10) . " más\n\n";
    }
    
    echo "RESUMEN APRENDICES:\n";
    echo "  ✓ Actualizados: $actualizados\n";
    echo "  ⚠ No encontrados en BD: $noEncontrados\n";
    echo "  - Sin datos para actualizar: $sinDatos\n\n";
    
    // Estadísticas finales
    $total = $conn->query("SELECT COUNT(*) FROM aprendices")->fetchColumn();
    $sinCorreo = $conn->query("SELECT COUNT(*) FROM aprendices WHERE correo IS NULL OR correo = ''")->fetchColumn();
    $sinCelular = $conn->query("SELECT COUNT(*) FROM aprendices WHERE celular IS NULL OR celular = ''")->fetchColumn();
    
    echo str_repeat("=", 70) . "\n";
    echo "ESTADÍSTICAS FINALES\n";
    echo str_repeat("=", 70) . "\n";
    echo "Total aprendices: $total\n";
    echo "Sin correo: $sinCorreo (" . round(($sinCorreo/$total)*100, 1) . "%)\n";
    echo "Sin celular: $sinCelular (" . round(($sinCelular/$total)*100, 1) . "%)\n";
    echo "\n✓ Proceso completado\n";
    
} catch (Exception $e) {
    echo "\nERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
?>
