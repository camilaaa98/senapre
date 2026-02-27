<?php

/**
 * Migration: Add id_ficha column to asistencias table
 * This script adds the missing id_ficha column to the asistencias table
 */

require_once __DIR__ . '/../api/config/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    echo "=== Migración: Agregar columna id_ficha a tabla asistencias ===\n\n";

    // Verificar si la columna ya existe
    $stmt = $conn->query("PRAGMA table_info(asistencias)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $columnExists = false;
    foreach ($columns as $column) {
        if ($column['name'] === 'id_ficha') {
            $columnExists = true;
            break;
        }
    }

    if ($columnExists) {
        echo "✓ La columna id_ficha ya existe en la tabla asistencias.\n";
        echo "No se requiere migración.\n";
        exit(0);
    }

    echo "→ La columna id_ficha NO existe. Procediendo con la migración...\n\n";

    // Agregar la columna id_ficha
    $conn->exec("ALTER TABLE asistencias ADD COLUMN id_ficha TEXT");

    echo "✓ Columna id_ficha agregada exitosamente.\n\n";

    // Actualizar registros existentes con id_ficha desde la tabla aprendices
    echo "→ Actualizando registros existentes...\n";

    $updateQuery = "
        UPDATE asistencias 
        SET id_ficha = (
            SELECT id_ficha 
            FROM aprendices 
            WHERE aprendices.id_aprendiz = asistencias.id_aprendiz
        )
        WHERE id_ficha IS NULL
    ";

    $conn->exec($updateQuery);

    $rowsUpdated = $conn->query("SELECT COUNT(*) FROM asistencias WHERE id_ficha IS NOT NULL")->fetchColumn();

    echo "✓ Actualización completada. Registros con id_ficha: $rowsUpdated\n\n";

    // Verificar la estructura final
    echo "→ Verificando estructura final de la tabla...\n";
    $stmt = $conn->query("PRAGMA table_info(asistencias)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "\nColumnas de la tabla asistencias:\n";
    foreach ($columns as $column) {
        echo "  - {$column['name']} ({$column['type']})\n";
    }

    echo "\n✅ Migración completada exitosamente.\n";
} catch (Exception $e) {
    echo "❌ Error durante la migración: " . $e->getMessage() . "\n";
    exit(1);
}
