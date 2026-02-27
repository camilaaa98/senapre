<?php

/**
 * Complete Dashboard Debug
 */

require_once __DIR__ . '/../api/config/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    echo "=== DEBUG COMPLETO DEL DASHBOARD ===\n\n";

    // 1. Verificar fecha del servidor
    echo "1. FECHA DEL SERVIDOR:\n";
    echo "   Zona horaria: " . date_default_timezone_get() . "\n";
    echo "   Fecha actual: " . date('Y-m-d H:i:s') . "\n";
    echo "   date('Y-m-d'): " . date('Y-m-d') . "\n\n";

    // 2. Ver todas las asistencias
    echo "2. TODAS LAS ASISTENCIAS:\n";
    $stmt = $conn->query("SELECT id_asistencia, id_ficha, fecha, tipo FROM asistencias ORDER BY id_asistencia DESC LIMIT 10");
    $todas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "   Total en DB: " . count($todas) . " (mostrando últimas 10)\n";
    foreach ($todas as $a) {
        echo "   - ID: {$a['id_asistencia']}, Ficha: {$a['id_ficha']}, Fecha: {$a['fecha']}, Tipo: {$a['tipo']}\n";
    }

    // 3. Asistencias de hoy
    $hoy = date('Y-m-d');
    echo "\n3. ASISTENCIAS DE HOY ($hoy):\n";
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM asistencias WHERE fecha = ?");
    $stmt->execute([$hoy]);
    $totalHoy = $stmt->fetch()['total'];
    echo "   Total: $totalHoy\n";

    if ($totalHoy > 0) {
        $stmt = $conn->prepare("SELECT id_ficha, COUNT(*) as count FROM asistencias WHERE fecha = ? GROUP BY id_ficha");
        $stmt->execute([$hoy]);
        $porFicha = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($porFicha as $f) {
            echo "   - Ficha {$f['id_ficha']}: {$f['count']} registros\n";
        }
    }

    // 4. Asistencias de los últimos 7 días
    $hace7Dias = date('Y-m-d', strtotime('-7 days'));
    echo "\n4. ASISTENCIAS ÚLTIMOS 7 DÍAS ($hace7Dias a $hoy):\n";
    $stmt = $conn->prepare("SELECT fecha, COUNT(*) as total FROM asistencias WHERE fecha >= ? AND fecha <= ? GROUP BY fecha ORDER BY fecha DESC");
    $stmt->execute([$hace7Dias, $hoy]);
    $ultimos7 = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "   Total de días con asistencias: " . count($ultimos7) . "\n";
    foreach ($ultimos7 as $d) {
        echo "   - {$d['fecha']}: {$d['total']} registros\n";
    }

    // 5. Verificar instructor 1
    $id_instructor = 1;
    echo "\n5. FICHAS ASIGNADAS AL INSTRUCTOR $id_instructor:\n";
    $stmt = $conn->prepare("
        SELECT f.id_ficha, f.numero_ficha 
        FROM asignaciones_instructor_ficha a
        INNER JOIN fichas f ON a.id_ficha = f.id_ficha
        WHERE a.id_instructor = ?
    ");
    $stmt->execute([$id_instructor]);
    $fichasAsignadas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "   Total fichas: " . count($fichasAsignadas) . "\n";
    $numerosFichas = array_column($fichasAsignadas, 'numero_ficha');
    echo "   Números: " . implode(', ', array_slice($numerosFichas, 0, 5)) . "...\n";

    // 6. Asistencias del instructor en los últimos 7 días
    echo "\n6. ASISTENCIAS DEL INSTRUCTOR EN ÚLTIMOS 7 DÍAS:\n";
    if (count($numerosFichas) > 0) {
        $placeholders = implode(',', array_fill(0, count($numerosFichas), '?'));
        $params = array_merge($numerosFichas, [$hace7Dias, $hoy]);

        $stmt = $conn->prepare("
            SELECT id_ficha, fecha, tipo, COUNT(*) as total
            FROM asistencias 
            WHERE id_ficha IN ($placeholders)
              AND fecha >= ?
              AND fecha <= ?
            GROUP BY id_ficha, fecha, tipo
            ORDER BY fecha DESC, id_ficha
        ");
        $stmt->execute($params);
        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "   Total de registros: " . count($resultado) . "\n";
        foreach ($resultado as $r) {
            echo "   - Ficha {$r['id_ficha']}, Fecha {$r['fecha']}, Tipo {$r['tipo']}: {$r['total']} registros\n";
        }

        if (count($resultado) === 0) {
            echo "\n   ❌ NO HAY DATOS PARA MOSTRAR EN EL DASHBOARD\n";
            echo "   Verificando por qué...\n\n";

            // Verificar si hay asistencias con esos números de ficha
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM asistencias WHERE id_ficha IN ($placeholders)");
            $stmt->execute($numerosFichas);
            $totalConFichas = $stmt->fetch()['total'];
            echo "   - Asistencias con fichas asignadas (sin filtro de fecha): $totalConFichas\n";

            if ($totalConFichas > 0) {
                echo "   - Problema: Las fechas no coinciden con el rango de 7 días\n";
                $stmt = $conn->prepare("SELECT DISTINCT fecha FROM asistencias WHERE id_ficha IN ($placeholders) ORDER BY fecha DESC");
                $stmt->execute($numerosFichas);
                $fechas = $stmt->fetchAll(PDO::FETCH_COLUMN);
                echo "   - Fechas disponibles: " . implode(', ', $fechas) . "\n";
            } else {
                echo "   - Problema: No hay asistencias para las fichas asignadas\n";
            }
        }
    } else {
        echo "   ❌ No hay fichas asignadas al instructor\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
