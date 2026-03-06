<?php

/**
 * Debug Attendance Query
 */

require_once __DIR__ . '/../api/config/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    echo "=== Diagnóstico de Consulta de Asistencias ===\n\n";

    // 1. Ver asistencias recientes
    echo "→ Asistencias recientes (últimas 5):\n";
    $stmt = $conn->query("SELECT id_asistencia, id_aprendiz, id_usuario, id_ficha, fecha, tipo FROM asistencias ORDER BY id_asistencia DESC LIMIT 5");
    $asistencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($asistencias as $a) {
        echo "  ID: {$a['id_asistencia']}, Ficha: {$a['id_ficha']}, Fecha: {$a['fecha']}, Tipo: {$a['tipo']}\n";
    }

    // 2. Ver fichas y sus números
    echo "\n→ Fichas (primeras 5):\n";
    $stmt = $conn->query("SELECT id_ficha, numero_ficha FROM fichas LIMIT 5");
    $fichas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($fichas as $f) {
        echo "  ID: {$f['id_ficha']}, Número: {$f['numero_ficha']}\n";
    }

    // 3. Ver asignaciones
    echo "\n→ Asignaciones (primeras 5):\n";
    $stmt = $conn->query("SELECT id_instructor, id_ficha FROM asignaciones_instructor_ficha LIMIT 5");
    $asignaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($asignaciones as $a) {
        echo "  Instructor: {$a['id_instructor']}, Ficha: {$a['id_ficha']}\n";
    }

    // 4. Simular query del dashboard
    echo "\n→ Simulando query del dashboard para instructor ID 1:\n";

    // Obtener fichas asignadas
    $stmt = $conn->prepare("
        SELECT f.numero_ficha 
        FROM asignaciones_instructor_ficha a
        INNER JOIN fichas f ON a.id_ficha = f.id_ficha
        WHERE a.id_instructor = ?
    ");
    $stmt->execute([1]);
    $fichasAsignadas = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "Fichas asignadas al instructor 1: " . implode(', ', $fichasAsignadas) . "\n";

    // Ver si hay asistencias con esos números de ficha
    if (count($fichasAsignadas) > 0) {
        $placeholders = implode(',', array_fill(0, count($fichasAsignadas), '?'));
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM asistencias WHERE id_ficha IN ($placeholders)");
        $stmt->execute($fichasAsignadas);
        $total = $stmt->fetch()['total'];

        echo "Asistencias encontradas con esos números de ficha: $total\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
