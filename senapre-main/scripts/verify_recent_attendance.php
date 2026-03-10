<?php

/**
 * Verify Recent Attendance Records
 */

require_once __DIR__ . '/../api/config/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    echo "=== Verificación de Asistencias Recientes ===\n\n";

    // 1. Ver últimas asistencias registradas
    echo "→ Últimas 10 asistencias registradas:\n";
    $stmt = $conn->query("
        SELECT id_asistencia, id_aprendiz, id_usuario, id_ficha, fecha, hora_entrada, tipo 
        FROM asistencias 
        ORDER BY id_asistencia DESC 
        LIMIT 10
    ");
    $asistencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($asistencias) === 0) {
        echo "  ❌ No hay asistencias registradas en la base de datos\n";
    } else {
        foreach ($asistencias as $a) {
            echo "  ID: {$a['id_asistencia']}, Ficha: {$a['id_ficha']}, Fecha: {$a['fecha']}, Tipo: {$a['tipo']}\n";
        }
    }

    // 2. Ver asistencias de hoy
    echo "\n→ Asistencias de hoy:\n";
    $hoy = date('Y-m-d');
    echo "  Fecha actual del servidor: $hoy\n";

    $stmt = $conn->prepare("
        SELECT COUNT(*) as total, id_ficha, fecha
        FROM asistencias 
        WHERE fecha = ?
        GROUP BY id_ficha, fecha
    ");
    $stmt->execute([$hoy]);
    $hoyAsistencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($hoyAsistencias) === 0) {
        echo "  ❌ No hay asistencias registradas para hoy ($hoy)\n";
    } else {
        echo "  Total de sesiones hoy: " . count($hoyAsistencias) . "\n";
        foreach ($hoyAsistencias as $h) {
            echo "    - Ficha {$h['id_ficha']}: {$h['total']} registros\n";
        }
    }

    // 3. Ver asistencias de los últimos 7 días
    echo "\n→ Asistencias de los últimos 7 días:\n";
    $hace7Dias = date('Y-m-d', strtotime('-7 days'));

    $stmt = $conn->prepare("
        SELECT fecha, COUNT(*) as total
        FROM asistencias 
        WHERE fecha >= ? AND fecha <= ?
        GROUP BY fecha
        ORDER BY fecha DESC
    ");
    $stmt->execute([$hace7Dias, $hoy]);
    $ultimas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($ultimas) === 0) {
        echo "  ❌ No hay asistencias en los últimos 7 días\n";
    } else {
        foreach ($ultimas as $u) {
            echo "  {$u['fecha']}: {$u['total']} registros\n";
        }
    }

    // 4. Verificar estructura de datos
    echo "\n→ Verificando estructura de datos:\n";
    $stmt = $conn->query("SELECT * FROM asistencias LIMIT 1");
    $sample = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($sample) {
        echo "  Columnas disponibles: " . implode(', ', array_keys($sample)) . "\n";
        echo "  Ejemplo de registro:\n";
        foreach ($sample as $key => $value) {
            echo "    $key: $value\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
