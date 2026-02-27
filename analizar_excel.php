<?php
/**
 * Script de Análisis y Cruce de Información
 * Lee fichas.xlsx y compara con la base de datos
 */

require_once __DIR__ . '/api/config/Database.php';

// Función para leer archivo XLSX usando SimpleXML
function leerExcel($archivo) {
    if (!file_exists($archivo)) {
        die("Error: El archivo $archivo no existe.\n");
    }

    $zip = new ZipArchive();
    if ($zip->open($archivo) !== TRUE) {
        die("Error: No se pudo abrir el archivo Excel.\n");
    }

    // Leer el contenido de la hoja principal (sheet1.xml)
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
                // Si es tipo 's' (string compartido), buscar en el array de strings
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
    echo "=== ANÁLISIS DE CRUCE DE INFORMACIÓN ===\n\n";
    
    // Conectar a la base de datos
    $db = new Database();
    $conn = $db->getConnection();

    // Leer archivo Excel
    $archivoExcel = __DIR__ . '/database/fichas.xlsx';
    echo "Leyendo archivo: $archivoExcel\n";
    $datosExcel = leerExcel($archivoExcel);

    if (empty($datosExcel)) {
        die("Error: No se pudieron leer datos del archivo Excel.\n");
    }

    echo "Total de filas leídas: " . count($datosExcel) . "\n\n";

    // Mostrar primeras filas para entender la estructura
    echo "=== ESTRUCTURA DEL ARCHIVO (Primeras 5 filas) ===\n";
    for ($i = 0; $i < min(5, count($datosExcel)); $i++) {
        echo "Fila $i: " . implode(" | ", $datosExcel[$i]) . "\n";
    }
    echo "\n";

    // Obtener fichas de la base de datos
    $stmtFichas = $conn->query("SELECT numero_ficha FROM fichas");
    $fichasDB = $stmtFichas->fetchAll(PDO::FETCH_COLUMN);
    echo "Fichas en base de datos: " . count($fichasDB) . "\n";

    // Obtener aprendices de la base de datos
    $stmtAprendices = $conn->query("SELECT documento, nombre, apellido, correo, celular FROM aprendices");
    $aprendicesDB = $stmtAprendices->fetchAll(PDO::FETCH_ASSOC);
    echo "Aprendices en base de datos: " . count($aprendicesDB) . "\n\n";

    // Análisis de datos faltantes
    $sinCorreo = [];
    $sinCelular = [];
    
    foreach ($aprendicesDB as $aprendiz) {
        if (empty($aprendiz['correo'])) {
            $sinCorreo[] = $aprendiz;
        }
        if (empty($aprendiz['celular'])) {
            $sinCelular[] = $aprendiz;
        }
    }

    echo "=== RESUMEN DE ANÁLISIS ===\n";
    echo "Aprendices sin correo: " . count($sinCorreo) . "\n";
    echo "Aprendices sin celular: " . count($sinCelular) . "\n\n";

    // Mostrar detalles
    if (!empty($sinCorreo)) {
        echo "=== APRENDICES SIN CORREO (Primeros 10) ===\n";
        for ($i = 0; $i < min(10, count($sinCorreo)); $i++) {
            $a = $sinCorreo[$i];
            echo "- {$a['documento']}: {$a['nombre']} {$a['apellido']}\n";
        }
        echo "\n";
    }

    if (!empty($sinCelular)) {
        echo "=== APRENDICES SIN CELULAR (Primeros 10) ===\n";
        for ($i = 0; $i < min(10, count($sinCelular)); $i++) {
            $a = $sinCelular[$i];
            echo "- {$a['documento']}: {$a['nombre']} {$a['apellido']}\n";
        }
        echo "\n";
    }

    // Guardar reporte completo
    $reporte = "=== REPORTE COMPLETO DE ANÁLISIS ===\n\n";
    $reporte .= "Fecha: " . date('Y-m-d H:i:s') . "\n\n";
    $reporte .= "FICHAS EN BASE DE DATOS: " . count($fichasDB) . "\n";
    $reporte .= "APRENDICES EN BASE DE DATOS: " . count($aprendicesDB) . "\n\n";
    $reporte .= "APRENDICES SIN CORREO: " . count($sinCorreo) . "\n";
    $reporte .= "APRENDICES SIN CELULAR: " . count($sinCelular) . "\n\n";

    if (!empty($sinCorreo)) {
        $reporte .= "=== LISTADO COMPLETO - SIN CORREO ===\n";
        foreach ($sinCorreo as $a) {
            $reporte .= "{$a['documento']}\t{$a['nombre']}\t{$a['apellido']}\n";
        }
        $reporte .= "\n";
    }

    if (!empty($sinCelular)) {
        $reporte .= "=== LISTADO COMPLETO - SIN CELULAR ===\n";
        foreach ($sinCelular as $a) {
            $reporte .= "{$a['documento']}\t{$a['nombre']}\t{$a['apellido']}\n";
        }
        $reporte .= "\n";
    }

    file_put_contents(__DIR__ . '/reporte_cruce_datos.txt', $reporte);
    echo "Reporte guardado en: reporte_cruce_datos.txt\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
