<?php

/**
 * Debug Data Mismatch
 */

require_once __DIR__ . '/../api/config/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    echo "=== Diagnóstico de Coincidencia de Datos ===\n\n";

    // 1. Obtener instructor 5 (que sabemos que tiene asignaciones)
    $id_instructor = 5;
    echo "→ Instructor ID: $id_instructor\n";

    // 2. Obtener sus fichas asignadas
    echo "\n→ Fichas asignadas (desde DB):\n";
    $stmt = $conn->prepare("
        SELECT f.id_ficha, f.numero_ficha 
        FROM fichas f
        JOIN asignaciones_instructor_ficha a ON f.id_ficha = a.id_ficha
        WHERE a.id_instructor = ?
    ");
    $stmt->execute([$id_instructor]);
    $fichas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $numeros_ficha = [];
    foreach ($fichas as $f) {
        echo "  - ID Interno: {$f['id_ficha']} | Número Ficha: {$f['numero_ficha']}\n";
        $numeros_ficha[] = $f['numero_ficha'];
    }

    // 3. Obtener asistencias de hoy
    echo "\n→ Asistencias de hoy (" . date('Y-m-d') . "):\n";
    $hoy = date('Y-m-d');
    $stmt = $conn->prepare("SELECT id_asistencia, id_ficha FROM asistencias WHERE fecha = ?");
    $stmt->execute([$hoy]);
    $asistencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Total encontradas: " . count($asistencias) . "\n";

    foreach ($asistencias as $a) {
        $valor_ficha = $a['id_ficha'];
        $match = in_array($valor_ficha, $numeros_ficha);

        echo "  - Asistencia #{$a['id_asistencia']} | Valor en columna id_ficha: '$valor_ficha'";
        echo " | ¿Está en asignaciones? " . ($match ? "✅ SÍ" : "❌ NO") . "\n";

        if (!$match) {
            echo "    (Comparando '$valor_ficha' con [" . implode(', ', $numeros_ficha) . "])\n";

            // Verificar tipos de datos
            echo "    Tipo de dato en DB: " . gettype($valor_ficha) . "\n";
            echo "    Tipo de dato en array: " . gettype($numeros_ficha[0]) . "\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
