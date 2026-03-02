<?php
/**
 * PROCESAMIENTO CORRECTO DE VOCEROS
 * Sin modificar estados existentes
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
    
    echo "=== PROCESAMIENTO CORRECTO DE VOCEROS ===\n\n";
    
    $datos = leerExcel(__DIR__ . '/database/voceros.xlsx');
    $headers = $datos[0];
    
    echo "ENCABEZADOS ENCONTRADOS:\n";
    foreach ($headers as $idx => $h) {
        if (!empty($h)) {
            echo "  [$idx] $h\n";
        }
    }
    echo "\n";
    
    // Buscar columnas de forma flexible
    $colFicha = false;
    $colDocumento = false;
    $colCorreo = false;
    $colCelular = false;
    
    foreach ($headers as $idx => $h) {
        $h_clean = trim(mb_strtolower($h));
        
        if (strpos($h_clean, 'ficha') !== false || strpos($h_clean, 'número') !== false) {
            $colFicha = $idx;
        }
        if (strpos($h_clean, 'documento') !== false || strpos($h_clean, 'identificación') !== false) {
            $colDocumento = $idx;
        }
        if (strpos($h_clean, 'correo') !== false || strpos($h_clean, 'email') !== false) {
            $colCorreo = $idx;
        }
        if (strpos($h_clean, 'celular') !== false || strpos($h_clean, 'teléfono') !== false || strpos($h_clean, 'telefono') !== false) {
            $colCelular = $idx;
        }
    }
    
    echo "COLUMNAS DETECTADAS:\n";
    echo "  Ficha: " . ($colFicha !== false ? "Columna $colFicha" : "NO ENCONTRADA") . "\n";
    echo "  Documento: " . ($colDocumento !== false ? "Columna $colDocumento" : "NO ENCONTRADA") . "\n";
    echo "  Correo: " . ($colCorreo !== false ? "Columna $colCorreo" : "NO ENCONTRADA") . "\n";
    echo "  Celular: " . ($colCelular !== false ? "Columna $colCelular" : "NO ENCONTRADA") . "\n\n";
    
    if ($colFicha === false || $colDocumento === false) {
        die("ERROR: No se encontraron las columnas necesarias\n");
    }
    
    // Agrupar voceros por ficha
    $vocerosPorFicha = [];
    $totalFilas = 0;
    
    for ($i = 1; $i < count($datos); $i++) {
        $fila = $datos[$i];
        $ficha = trim($fila[$colFicha] ?? '');
        $documento = trim($fila[$colDocumento] ?? '');
        
        if (empty($ficha) || empty($documento)) continue;
        
        $totalFilas++;
        
        if (!isset($vocerosPorFicha[$ficha])) {
            $vocerosPorFicha[$ficha] = [];
        }
        
        $vocerosPorFicha[$ficha][] = [
            'documento' => $documento,
            'correo' => $colCorreo !== false ? trim($fila[$colCorreo] ?? '') : '',
            'celular' => $colCelular !== false ? trim($fila[$colCelular] ?? '') : ''
        ];
    }
    
    echo "RESUMEN DE DATOS:\n";
    echo "  Total filas procesadas: $totalFilas\n";
    echo "  Fichas con voceros: " . count($vocerosPorFicha) . "\n\n";
    
    // Actualizar fichas (asignar primer vocero como principal, segundo como suplente)
    $actualizados = 0;
    
    foreach ($vocerosPorFicha as $numFicha => $voceros) {
        $principal = $voceros[0]['documento'] ?? null;
        $suplente = isset($voceros[1]) ? $voceros[1]['documento'] : null;
        
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
    
    echo "\n✓ Total fichas actualizadas: $actualizados\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
