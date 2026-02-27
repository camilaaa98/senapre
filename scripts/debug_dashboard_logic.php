<?php

/**
 * Debug Dashboard Logic
 * Simulates the data fetching and filtering logic of the dashboard
 */

require_once __DIR__ . '/../api/config/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    echo "=== Diagnóstico de Lógica del Dashboard ===\n\n";

    // 1. Obtener ID de instructor para el usuario 36579528 (del log anterior)
    $id_usuario = 36579528; // ID del usuario que registró la asistencia
    echo "→ Buscando instructor para id_usuario: $id_usuario\n";

    $stmt = $conn->prepare("SELECT * FROM instructores WHERE id_usuario = ?");
    $stmt->execute([$id_usuario]);
    $instructor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$instructor) {
        // Intentar buscar por id_instructor directo si no coincide
        echo "⚠️  No se encontró por id_usuario. Buscando todos los instructores:\n";
        $stmt = $conn->query("SELECT * FROM instructores");
        $all_instructores = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($all_instructores as $inst) {
            echo "  - ID: {$inst['id_instructor']}, ID Usuario: {$inst['id_usuario']}, Nombre: {$inst['nombre']}\n";
        }

        // Asumir el primer instructor para la prueba si falla
        if (count($all_instructores) > 0) {
            $instructor = $all_instructores[0];
            echo "⚠️  Usando instructor ID: {$instructor['id_instructor']} para prueba.\n";
        } else {
            die("❌ No hay instructores en la base de datos.\n");
        }
    } else {
        echo "✓ Instructor encontrado: ID {$instructor['id_instructor']}, Nombre: {$instructor['nombre']}\n";
    }

    $id_instructor = $instructor['id_instructor'];

    // 2. Obtener asignaciones (fichas)
    echo "\n→ Obteniendo asignaciones para instructor $id_instructor...\n";

    // Simular api/instructor-asignaciones.php
    $query = "
        SELECT f.id_ficha, f.numero_ficha, f.nombre_programa 
        FROM asignaciones a
        INNER JOIN fichas f ON a.id_ficha = f.id_ficha
        WHERE a.id_instructor = :id_instructor
    ";

    $stmt = $conn->prepare($query);
    $stmt->execute([':id_instructor' => $id_instructor]);
    $asignaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Asignaciones encontradas: " . count($asignaciones) . "\n";
    $fichasAsignadas = [];
    foreach ($asignaciones as $asig) {
        echo "  - Ficha ID: {$asig['id_ficha']}, Número: {$asig['numero_ficha']}, Programa: {$asig['nombre_programa']}\n";
        $fichasAsignadas[] = $asig['numero_ficha'];
    }

    // 3. Obtener asistencias (simular api/test-asistencias.php)
    echo "\n→ Obteniendo asistencias recientes...\n";
    $stmt = $conn->query("SELECT * FROM asistencias ORDER BY fecha DESC LIMIT 50");
    $asistencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Total asistencias recuperadas: " . count($asistencias) . "\n";

    // 4. Simular filtro de JavaScript
    echo "\n→ Simulando filtro JS (Fecha hoy: " . date('Y-m-d') . ")...\n";

    $hoy = date('Y-m-d');
    $filtradas = 0;

    foreach ($asistencias as $asistencia) {
        $matchFicha = in_array($asistencia['id_ficha'], $fichasAsignadas);
        $matchFecha = ($asistencia['fecha'] === $hoy);

        if ($matchFicha && $matchFecha) {
            $filtradas++;
            echo "  ✓ MATCH: ID {$asistencia['id_asistencia']} | Fecha: {$asistencia['fecha']} | Ficha: {$asistencia['id_ficha']}\n";
        } else {
            // Mostrar por qué falló el primero
            if ($filtradas == 0 && $asistencia['fecha'] == $hoy) {
                echo "  ❌ NO MATCH: ID {$asistencia['id_asistencia']} | Fecha: {$asistencia['fecha']} | Ficha: {$asistencia['id_ficha']}\n";
                echo "     - Match Ficha: " . ($matchFicha ? 'SÍ' : 'NO') . " (Buscando '{$asistencia['id_ficha']}' en [" . implode(', ', $fichasAsignadas) . "])\n";
                echo "     - Match Fecha: " . ($matchFecha ? 'SÍ' : 'NO') . "\n";
            }
        }
    }

    echo "\nTotal filtradas (visibles en dashboard): $filtradas\n";

    if ($filtradas == 0) {
        echo "\n⚠️  DIAGNÓSTICO: El dashboard no muestra nada porque el filtro descarta todas las asistencias.\n";
        if (count($fichasAsignadas) == 0) {
            echo "   CAUSA PROBABLE: El instructor no tiene fichas asignadas en la tabla 'asignaciones'.\n";
        } else {
            echo "   CAUSA PROBABLE: El 'id_ficha' en la tabla 'asistencias' no coincide con 'numero_ficha' de las asignaciones.\n";
        }
    } else {
        echo "\n✅ DIAGNÓSTICO: El dashboard DEBERÍA mostrar $filtradas registros.\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
