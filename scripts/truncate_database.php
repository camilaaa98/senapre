<?php

/**
 * Truncate Database
 * DANGER: This script deletes ALL data from ALL tables.
 */

require_once __DIR__ . '/../api/config/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    echo "=== VACIADO DE BASE DE DATOS ===\n";
    echo "⚠️  ADVERTENCIA: Esto eliminará TODOS los datos.\n\n";

    // Desactivar foreign keys para evitar errores de restricción durante el borrado
    $conn->exec("PRAGMA foreign_keys = OFF");

    // Obtener lista de tablas
    $stmt = $conn->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $conn->beginTransaction();

    foreach ($tables as $table) {
        echo "→ Vaciando tabla '$table'...";
        $count = $conn->exec("DELETE FROM \"$table\"");
        echo " ($count registros eliminados) ✓\n";
    }

    // Resetear secuencias de autoincrement
    echo "\n→ Reseteando contadores de autoincrement...";
    $conn->exec("DELETE FROM sqlite_sequence");
    echo " ✓\n";

    $conn->commit();

    // Reactivar foreign keys
    $conn->exec("PRAGMA foreign_keys = ON");

    echo "\n✅ Base de datos vaciada completamente.\n";
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
