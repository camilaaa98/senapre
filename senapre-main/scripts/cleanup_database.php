<?php

/**
 * Database Cleanup: Remove orphaned temp table references
 * This script checks for and cleans up any orphaned temporary tables
 */

require_once __DIR__ . '/../api/config/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    echo "=== Limpieza de Base de Datos ===\n\n";

    // Listar todas las tablas
    echo "→ Verificando tablas en la base de datos...\n";
    $stmt = $conn->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "\nTablas encontradas:\n";
    foreach ($tables as $table) {
        echo "  - $table\n";
    }

    // Verificar si existe aprendices_temp
    if (in_array('aprendices_temp', $tables)) {
        echo "\n⚠️  Tabla temporal 'aprendices_temp' encontrada. Eliminando...\n";
        $conn->exec("DROP TABLE aprendices_temp");
        echo "✓ Tabla temporal eliminada.\n";
    } else {
        echo "\n✓ No se encontró la tabla temporal 'aprendices_temp'.\n";
    }

    // Verificar la estructura de la tabla asistencias
    echo "\n→ Verificando estructura de tabla 'asistencias'...\n";
    $stmt = $conn->query("PRAGMA table_info(asistencias)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "\nColumnas de 'asistencias':\n";
    foreach ($columns as $column) {
        echo "  - {$column['name']} ({$column['type']})";
        if ($column['notnull']) echo " NOT NULL";
        if ($column['dflt_value']) echo " DEFAULT {$column['dflt_value']}";
        echo "\n";
    }

    // Verificar si hay foreign keys o triggers problemáticos
    echo "\n→ Verificando foreign keys...\n";
    $stmt = $conn->query("PRAGMA foreign_key_list(asistencias)");
    $fks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($fks)) {
        echo "✓ No hay foreign keys en la tabla asistencias.\n";
    } else {
        echo "Foreign keys encontradas:\n";
        foreach ($fks as $fk) {
            echo "  - {$fk['from']} -> {$fk['table']}.{$fk['to']}\n";
        }
    }

    // Verificar triggers
    echo "\n→ Verificando triggers...\n";
    $stmt = $conn->query("SELECT name FROM sqlite_master WHERE type='trigger' AND tbl_name='asistencias'");
    $triggers = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($triggers)) {
        echo "✓ No hay triggers en la tabla asistencias.\n";
    } else {
        echo "Triggers encontrados:\n";
        foreach ($triggers as $trigger) {
            echo "  - $trigger\n";
        }
    }

    // Intentar un INSERT de prueba
    echo "\n→ Probando INSERT en tabla asistencias...\n";

    try {
        $conn->beginTransaction();

        $testStmt = $conn->prepare("INSERT INTO asistencias (id_aprendiz, id_usuario, id_ficha, fecha, hora_entrada, tipo, observaciones) 
                                     VALUES (:id_aprendiz, :id_usuario, :id_ficha, :fecha, :hora_entrada, :tipo, :observaciones)");

        $testStmt->execute([
            ':id_aprendiz' => 999999,
            ':id_usuario' => 999999,
            ':id_ficha' => 'TEST',
            ':fecha' => date('Y-m-d'),
            ':hora_entrada' => date('H:i:s'),
            ':tipo' => 'entrada',
            ':observaciones' => 'TEST'
        ]);

        // Eliminar el registro de prueba
        $conn->exec("DELETE FROM asistencias WHERE id_aprendiz = 999999");

        $conn->commit();

        echo "✓ INSERT de prueba exitoso.\n";
    } catch (Exception $e) {
        $conn->rollBack();
        echo "❌ Error en INSERT de prueba: " . $e->getMessage() . "\n";

        // Si el error menciona aprendices_temp, intentar solucionarlo
        if (strpos($e->getMessage(), 'aprendices_temp') !== false) {
            echo "\n→ Detectado problema con aprendices_temp. Intentando solución...\n";

            // Verificar si hay un trigger o foreign key que referencia aprendices_temp
            $stmt = $conn->query("SELECT sql FROM sqlite_master WHERE sql LIKE '%aprendices_temp%'");
            $problematic = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($problematic)) {
                echo "\nObjetos problemáticos encontrados:\n";
                foreach ($problematic as $obj) {
                    echo "SQL: " . $obj['sql'] . "\n\n";
                }
            }
        }
    }

    echo "\n✅ Limpieza completada.\n";
} catch (Exception $e) {
    echo "❌ Error durante la limpieza: " . $e->getMessage() . "\n";
    exit(1);
}
