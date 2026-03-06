<?php

/**
 * Debug Dashboard Query
 */

require_once __DIR__ . '/../api/config/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    echo "=== Debug de Query del Dashboard ===\n\n";

    $id_instructor = 1; // Cambiar según el instructor que esté probando

    // 1. Obtener fichas asignadas
    echo "→ Paso 1: Obtener fichas asignadas al instructor $id_instructor:\n";
    $stmt = $conn->prepare("
        SELECT f.numero_ficha 
        FROM asignaciones_instructor_ficha a
        INNER JOIN fichas f ON a.id_ficha = f.id_ficha
        WHERE a.id_instructor = ?
    ");
    $stmt->execute([$id_instructor]);
    $fichasAsignadas = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "  Fichas asignadas: " . count($fichasAsignadas) . "\n";
    echo "  Números: " . implode(', ', array_slice($fichasAsignadas, 0, 5)) . "\n";
    if (count($fichasAsignadas) > 5) {
        echo "  ... y " . (count($fichasAsignadas) - 5) . " más\n";
    }

    // 2. Ver asistencias
    echo "\n→ Paso 2: Obtener todas las asistencias:\n";
    $stmt = $conn->query("SELECT COUNT(*) as total FROM asistencias");
    $total = $stmt->fetch()['total'];
    echo "  Total de asistencias en DB: $total\n";

    // 3. Filtrar por fichas asignadas
    echo "\n→ Paso 3: Filtrar asistencias por fichas asignadas:\n";
    if (count($fichasAsignadas) > 0) {
        $placeholders = implode(',', array_fill(0, count($fichasAsignadas), '?'));
        $stmt = $conn->prepare("
            SELECT id_ficha, fecha, COUNT(*) as total
            FROM asistencias 
            WHERE id_ficha IN ($placeholders)
            GROUP BY id_ficha, fecha
            ORDER BY fecha DESC
        ");
        $stmt->execute($fichasAsignadas);
        $asistenciasFiltradas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "  Asistencias filtradas: " . count($asistenciasFiltradas) . " sesiones\n";
        foreach ($asistenciasFiltradas as $a) {
            echo "    - Ficha {$a['id_ficha']}, Fecha {$a['fecha']}: {$a['total']} registros\n";
        }
    } else {
        echo "  ❌ No hay fichas asignadas para filtrar\n";
    }

    // 4. Simular filtro de 7 días
    echo "\n→ Paso 4: Aplicar filtro de últimos 7 días:\n";
    $hoy = date('Y-m-d');
    $hace7Dias = date('Y-m-d', strtotime('-7 days'));
    echo "  Rango: $hace7Dias a $hoy\n";

    if (count($fichasAsignadas) > 0) {
        $placeholders = implode(',', array_fill(0, count($fichasAsignadas), '?'));
        $params = array_merge($fichasAsignadas, [$hace7Dias, $hoy]);

        $stmt = $conn->prepare("
            SELECT id_ficha, fecha, COUNT(*) as total
            FROM asistencias 
            WHERE id_ficha IN ($placeholders)
              AND fecha >= ?
              AND fecha <= ?
            GROUP BY id_ficha, fecha
            ORDER BY fecha DESC
        ");
        $stmt->execute($params);
        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "  Resultado final: " . count($resultado) . " sesiones\n";
        foreach ($resultado as $r) {
            echo "    - Ficha {$r['id_ficha']}, Fecha {$r['fecha']}: {$r['total']} registros\n";
        }

        if (count($resultado) === 0) {
            echo "\n  ❌ NO HAY DATOS PARA MOSTRAR\n";
            echo "  Posibles causas:\n";
            echo "    1. Las fichas asignadas no coinciden con las fichas en asistencias\n";
            echo "    2. Las fechas están fuera del rango de 7 días\n";
            echo "    3. No hay asistencias registradas para este instructor\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
