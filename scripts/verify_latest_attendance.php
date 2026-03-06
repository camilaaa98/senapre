<?php

/**
 * Verify Latest Attendance Records
 */

require_once __DIR__ . '/../api/config/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    echo "=== Verificación de Registros Más Recientes ===\n\n";

    // 1. Fecha actual del servidor
    echo "1. FECHA Y HORA DEL SERVIDOR:\n";
    echo "   Zona horaria: " . date_default_timezone_get() . "\n";
    echo "   Fecha y hora actual: " . date('Y-m-d H:i:s') . "\n";
    echo "   Fecha (Y-m-d): " . date('Y-m-d') . "\n\n";

    // 2. Total de asistencias
    $stmt = $conn->query("SELECT COUNT(*) as total FROM asistencias");
    $total = $stmt->fetch()['total'];
    echo "2. TOTAL DE ASISTENCIAS EN LA BASE DE DATOS: $total\n\n";

    // 3. Últimas 20 asistencias registradas
    echo "3. ÚLTIMAS 20 ASISTENCIAS REGISTRADAS:\n";
    $stmt = $conn->query("
        SELECT id_asistencia, id_aprendiz, id_ficha, fecha, hora_entrada, tipo 
        FROM asistencias 
        ORDER BY id_asistencia DESC 
        LIMIT 20
    ");
    $ultimas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($ultimas as $a) {
        echo "   ID: {$a['id_asistencia']}, Ficha: {$a['id_ficha']}, Fecha: {$a['fecha']}, Hora: {$a['hora_entrada']}, Tipo: {$a['tipo']}\n";
    }

    // 4. Resumen por fecha
    echo "\n4. RESUMEN POR FECHA:\n";
    $stmt = $conn->query("
        SELECT fecha, COUNT(*) as total 
        FROM asistencias 
        GROUP BY fecha 
        ORDER BY fecha DESC
    ");
    $porFecha = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($porFecha as $f) {
        $marcador = ($f['fecha'] === date('Y-m-d')) ? ' ← HOY' : '';
        echo "   {$f['fecha']}: {$f['total']} registros$marcador\n";
    }

    // 5. Verificar si hay registros con fecha incorrecta
    echo "\n5. VERIFICACIÓN DE FECHAS:\n";
    $manana = date('Y-m-d', strtotime('+1 day'));
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM asistencias WHERE fecha = ?");
    $stmt->execute([$manana]);
    $totalManana = $stmt->fetch()['total'];

    if ($totalManana > 0) {
        echo "   ❌ ADVERTENCIA: Hay $totalManana registros con fecha de mañana ($manana)\n";
    } else {
        echo "   ✅ CORRECTO: No hay registros con fecha de mañana\n";
    }

    $hoy = date('Y-m-d');
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM asistencias WHERE fecha = ?");
    $stmt->execute([$hoy]);
    $totalHoy = $stmt->fetch()['total'];
    echo "   ✅ Registros con fecha de hoy ($hoy): $totalHoy\n";

    // 6. Últimas asistencias por ficha
    echo "\n6. ÚLTIMAS ASISTENCIAS POR FICHA:\n";
    $stmt = $conn->query("
        SELECT id_ficha, fecha, COUNT(*) as total, MAX(id_asistencia) as ultimo_id
        FROM asistencias 
        GROUP BY id_ficha, fecha
        ORDER BY ultimo_id DESC
        LIMIT 10
    ");
    $porFicha = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($porFicha as $f) {
        echo "   Ficha {$f['id_ficha']}, Fecha {$f['fecha']}: {$f['total']} registros (último ID: {$f['ultimo_id']})\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
