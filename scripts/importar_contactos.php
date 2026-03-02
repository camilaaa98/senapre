<?php
/**
 * Importación de Datos de Contacto desde Excel
 * Lee Aprendices.xlsx y voceros.xlsx para actualizar correos y celulares
 */

require_once __DIR__ . '/api/config/Database.php';

function leerExcel($archivo) {
    if (!file_exists($archivo)) {
        throw new Exception("Archivo no encontrado: $archivo");
    }

    $zip = new ZipArchive();
    if ($zip->open($archivo) !== TRUE) {
        throw new Exception("No se pudo abrir el archivo Excel");
    }

    $sheetData = $zip->getFromName('xl/worksheets/sheet1.xml');
    $sharedStrings = $zip->getFromName('xl/sharedStrings.xml');
    $zip->close();

    if (!$sheetData || !$sharedStrings) {
        throw new Exception("No se pudo leer el contenido del Excel");
    }

    // Parsear strings compartidos
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

    return $rows;
}

try {
    echo "=== IMPORTACIÓN DE DATOS DE CONTACTO ===\n\n";
    
    $db = new Database();
    $conn = $db->getConnection();
    
    // ========== PASO 1: APRENDICES.XLSX ==========
    echo "PASO 1: Procesando Aprendices.xlsx\n";
    echo str_repeat("-", 60) . "\n";
    
    $archivoAprendices = __DIR__ . '/database/Aprendices.xlsx';
    $datosAprendices = leerExcel($archivoAprendices);
    
    $headersAprendices = $datosAprendices[0];
    echo "Columnas encontradas: " . count($headersAprendices) . "\n";
    
    // Mostrar encabezados
    echo "\nEncabezados:\n";
    foreach ($headersAprendices as $idx => $h) {
        if (!empty($h)) {
            echo "  $idx: $h\n";
        }
    }
    
    // Identificar columnas clave
    $colDocumento = false;
    $colCorreo = false;
    $colCelular = false;
    
    foreach ($headersAprendices as $idx => $h) {
        $h_lower = mb_strtolower($h);
        if (strpos($h_lower, 'documento') !== false || strpos($h_lower, 'identificación') !== false) {
            $colDocumento = $idx;
        }
        if (strpos($h_lower, 'correo') !== false || strpos($h_lower, 'email') !== false || strpos($h_lower, 'e-mail') !== false) {
            $colCorreo = $idx;
        }
        if (strpos($h_lower, 'celular') !== false || strpos($h_lower, 'teléfono') !== false || strpos($h_lower, 'telefono') !== false) {
            $colCelular = $idx;
        }
    }
    
    echo "\nColumnas identificadas:\n";
    echo "  Documento: " . ($colDocumento !== false ? "Col $colDocumento" : "NO ENCONTRADA") . "\n";
    echo "  Correo: " . ($colCorreo !== false ? "Col $colCorreo" : "NO ENCONTRADA") . "\n";
    echo "  Celular: " . ($colCelular !== false ? "Col $colCelular" : "NO ENCONTRADA") . "\n\n";
    
    // Actualizar aprendices
    $actualizadosAprendices = 0;
    $stmtUpdate = $conn->prepare("UPDATE aprendices SET correo = :correo, celular = :celular WHERE documento = :documento");
    
    for ($i = 1; $i < count($datosAprendices); $i++) {
        $fila = $datosAprendices[$i];
        
        $documento = $colDocumento !== false ? trim($fila[$colDocumento] ?? '') : '';
        $correo = $colCorreo !== false ? trim($fila[$colCorreo] ?? '') : '';
        $celular = $colCelular !== false ? trim($fila[$colCelular] ?? '') : '';
        
        if (!empty($documento) && (!empty($correo) || !empty($celular))) {
            // Verificar si el aprendiz existe
            $stmtCheck = $conn->prepare("SELECT correo, celular FROM aprendices WHERE documento = :documento");
            $stmtCheck->execute([':documento' => $documento]);
            $aprendiz = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            
            if ($aprendiz) {
                // Solo actualizar si el campo está vacío en la BD
                $nuevoCorreo = empty($aprendiz['correo']) && !empty($correo) ? $correo : $aprendiz['correo'];
                $nuevoCelular = empty($aprendiz['celular']) && !empty($celular) ? $celular : $aprendiz['celular'];
                
                if ($nuevoCorreo != $aprendiz['correo'] || $nuevoCelular != $aprendiz['celular']) {
                    $stmtUpdate->execute([
                        ':correo' => $nuevoCorreo,
                        ':celular' => $nuevoCelular,
                        ':documento' => $documento
                    ]);
                    $actualizadosAprendices++;
                }
            }
        }
    }
    
    echo "Aprendices actualizados: $actualizadosAprendices\n\n";
    
    // ========== PASO 2: VOCEROS.XLSX ==========
    echo "PASO 2: Procesando voceros.xlsx\n";
    echo str_repeat("-", 60) . "\n";
    
    $archivoVoceros = __DIR__ . '/database/voceros.xlsx';
    $datosVoceros = leerExcel($archivoVoceros);
    
    $headersVoceros = $datosVoceros[0];
    echo "Columnas encontradas: " . count($headersVoceros) . "\n";
    
    // Mostrar encabezados
    echo "\nEncabezados:\n";
    foreach ($headersVoceros as $idx => $h) {
        if (!empty($h)) {
            echo "  $idx: $h\n";
        }
    }
    
    // Identificar columnas
    $colFicha = false;
    $colDocVocero = false;
    $colCorreoVocero = false;
    $colCelularVocero = false;
    $colTipo = false; // Principal o Suplente
    
    foreach ($headersVoceros as $idx => $h) {
        $h_lower = mb_strtolower($h);
        if (strpos($h_lower, 'ficha') !== false) {
            $colFicha = $idx;
        }
        if (strpos($h_lower, 'documento') !== false || strpos($h_lower, 'identificación') !== false) {
            $colDocVocero = $idx;
        }
        if (strpos($h_lower, 'correo') !== false || strpos($h_lower, 'email') !== false) {
            $colCorreoVocero = $idx;
        }
        if (strpos($h_lower, 'celular') !== false || strpos($h_lower, 'teléfono') !== false) {
            $colCelularVocero = $idx;
        }
        if (strpos($h_lower, 'tipo') !== false || strpos($h_lower, 'rol') !== false) {
            $colTipo = $idx;
        }
    }
    
    echo "\nColumnas identificadas:\n";
    echo "  Ficha: " . ($colFicha !== false ? "Col $colFicha" : "NO ENCONTRADA") . "\n";
    echo "  Documento: " . ($colDocVocero !== false ? "Col $colDocVocero" : "NO ENCONTRADA") . "\n";
    echo "  Correo: " . ($colCorreoVocero !== false ? "Col $colCorreoVocero" : "NO ENCONTRADA") . "\n";
    echo "  Celular: " . ($colCelularVocero !== false ? "Col $colCelularVocero" : "NO ENCONTRADA") . "\n";
    echo "  Tipo: " . ($colTipo !== false ? "Col $colTipo" : "NO ENCONTRADA") . "\n\n";
    
    // Actualizar voceros
    $actualizadosVoceros = 0;
    
    for ($i = 1; $i < count($datosVoceros); $i++) {
        $fila = $datosVoceros[$i];
        
        $ficha = $colFicha !== false ? trim($fila[$colFicha] ?? '') : '';
        $documento = $colDocVocero !== false ? trim($fila[$colDocVocero] ?? '') : '';
        $correo = $colCorreoVocero !== false ? trim($fila[$colCorreoVocero] ?? '') : '';
        $celular = $colCelularVocero !== false ? trim($fila[$colCelularVocero] ?? '') : '';
        
        if (!empty($documento) && (!empty($correo) || !empty($celular))) {
            // Actualizar en aprendices
            $stmtCheck = $conn->prepare("SELECT correo, celular FROM aprendices WHERE documento = :documento");
            $stmtCheck->execute([':documento' => $documento]);
            $vocero = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            
            if ($vocero) {
                $nuevoCorreo = empty($vocero['correo']) && !empty($correo) ? $correo : $vocero['correo'];
                $nuevoCelular = empty($vocero['celular']) && !empty($celular) ? $celular : $vocero['celular'];
                
                if ($nuevoCorreo != $vocero['correo'] || $nuevoCelular != $vocero['celular']) {
                    $stmtUpdate->execute([
                        ':correo' => $nuevoCorreo,
                        ':celular' => $nuevoCelular,
                        ':documento' => $documento
                    ]);
                    $actualizadosVoceros++;
                }
            }
        }
    }
    
    echo "Voceros actualizados: $actualizadosVoceros\n\n";
    
    // ========== RESUMEN FINAL ==========
    echo str_repeat("=", 60) . "\n";
    echo "RESUMEN DE IMPORTACIÓN\n";
    echo str_repeat("=", 60) . "\n";
    echo "Aprendices actualizados: $actualizadosAprendices\n";
    echo "Voceros actualizados: $actualizadosVoceros\n";
    echo "Total de registros actualizados: " . ($actualizadosAprendices + $actualizadosVoceros) . "\n\n";
    
    // Verificar datos faltantes después de la importación
    $stmtSinCorreo = $conn->query("SELECT COUNT(*) FROM aprendices WHERE correo IS NULL OR correo = ''");
    $sinCorreo = $stmtSinCorreo->fetchColumn();
    
    $stmtSinCelular = $conn->query("SELECT COUNT(*) FROM aprendices WHERE celular IS NULL OR celular = ''");
    $sinCelular = $stmtSinCelular->fetchColumn();
    
    echo "DATOS FALTANTES DESPUÉS DE LA IMPORTACIÓN:\n";
    echo "  Aprendices sin correo: $sinCorreo\n";
    echo "  Aprendices sin celular: $sinCelular\n";
    
    echo "\n✓ Importación completada exitosamente\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
?>
