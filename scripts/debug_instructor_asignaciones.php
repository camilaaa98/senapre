<?php

/**
 * Debug Instructor Asignaciones
 */

require_once __DIR__ . '/../api/config/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    echo "=== Debug de Asignaciones de Instructor ===\n\n";

    $id_instructor = 1;

    echo "→ Consultando asignaciones para instructor $id_instructor:\n";
    $stmt = $conn->prepare("SELECT * FROM asignaciones_instructor_ficha WHERE id_instructor = ?");
    $stmt->execute([$id_instructor]);
    $asignaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Asignaciones encontradas: " . count($asignaciones) . "\n";
    foreach ($asignaciones as $a) {
        echo "  - id_instructor: {$a['id_instructor']}, id_ficha: {$a['id_ficha']}\n";
    }

    echo "\n→ Consultando fichas con JOIN:\n";
    $query = "SELECT DISTINCT f.* 
              FROM fichas f
              JOIN asignaciones_instructor_ficha a ON f.id_ficha = a.id_ficha
              WHERE a.id_instructor = ?
              ORDER BY f.numero_ficha";

    $stmt = $conn->prepare($query);
    $stmt->execute([$id_instructor]);
    $fichas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Fichas encontradas: " . count($fichas) . "\n";
    foreach (array_slice($fichas, 0, 5) as $f) {
        echo "  - ID: {$f['id_ficha']}, Número: {$f['numero_ficha']}, Programa: {$f['nombre_programa']}\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
