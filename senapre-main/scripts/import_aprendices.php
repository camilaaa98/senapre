<?php

/**
 * Import Aprendices from Excel
 */

require_once __DIR__ . '/../api/config/Database.php';

$tempDir = __DIR__ . '/temp_xlsx';
$sharedStringsFile = $tempDir . '/xl/sharedStrings.xml';
$sheet1File = $tempDir . '/xl/worksheets/sheet1.xml';

try {
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

        foreach ($row->c as $cell) {
            $cellRef = (string)$cell['r'];
            $type = (string)$cell['t'];
            $value = (string)$cell->v;

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

    echo "=== Importación de Aprendices ===\n\n";
    echo "Total filas encontradas: " . count($rows) . "\n";

    // 3. Conectar a la base de datos
    $db = new Database();
    $conn = $db->getConnection();

    // 4. Preparar statement
    $stmt = $conn->prepare("
        INSERT INTO aprendices (id_aprendiz, tipo_identificacion, documento, nombre, apellido, correo, celular, id_ficha, estado)
        VALUES (:id_aprendiz, :tipo_identificacion, :documento, :nombre, :apellido, :correo, :celular, :id_ficha, :estado)
    ");

    $conn->beginTransaction();

    $imported = 0;
    $skipped = 0;

    // 5. Importar datos (saltar la primera fila que es el encabezado)
    for ($i = 1; $i < count($rows); $i++) {
        $row = $rows[$i];

        // Validar que tenga datos mínimos
        if (empty($row['A']) || empty($row['C'])) {
            $skipped++;
            continue;
        }

        try {
            $stmt->execute([
                ':id_aprendiz' => (int)$row['A'],
                ':tipo_identificacion' => $row['B'] ?? 'CC',
                ':documento' => (int)$row['C'],
                ':nombre' => $row['D'] ?? '',
                ':apellido' => $row['E'] ?? '',
                ':correo' => $row['F'] ?? '',
                ':celular' => !empty($row['G']) ? (int)$row['G'] : null,
                ':id_ficha' => (int)$row['H'],
                ':estado' => $row['I'] ?? 'EN FORMACION'
            ]);
            $imported++;

            if ($imported % 100 == 0) {
                echo "Importados: $imported...\n";
            }
        } catch (Exception $e) {
            echo "Error en fila " . ($i + 1) . ": " . $e->getMessage() . "\n";
            $skipped++;
        }
    }

    $conn->commit();

    echo "\n✅ Importación completada.\n";
    echo "Registros importados: $imported\n";
    echo "Registros omitidos: $skipped\n";
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
