<?php

/**
 * Debug Table Existence
 */

require_once __DIR__ . '/../api/config/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    echo "=== Verificación de Tablas ===\n\n";

    // Listar todas las tablas
    $stmt = $conn->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
    $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Tablas encontradas:\n";
    foreach ($tables as $t) {
        echo "  - {$t['name']}\n";
    }

    // Verificar asignaciones_instructor_ficha
    echo "\n→ Verificando contenido de 'asignaciones_instructor_ficha'...\n";

    $check = $conn->query("SELECT name FROM sqlite_master WHERE type='table' AND name='asignaciones_instructor_ficha'");
    if ($check->fetch()) {
        $stmt = $conn->query("SELECT * FROM asignaciones_instructor_ficha");
        $asignaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "Registros encontrados: " . count($asignaciones) . "\n";
        foreach ($asignaciones as $a) {
            echo "  - ID Instructor: {$a['id_instructor']} | ID Ficha: {$a['id_ficha']}\n";
        }
    } else {
        echo "❌ La tabla 'asignaciones_instructor_ficha' NO existe.\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
