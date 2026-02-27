<?php

/**
 * Verificar registros de asistencia en la base de datos
 */

require_once __DIR__ . '/../api/config/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    echo "=== Verificación de Asistencias en Base de Datos ===\n\n";

    // Contar total de asistencias
    $stmt = $conn->query("SELECT COUNT(*) as total FROM asistencias");
    $total = $stmt->fetch()['total'];

    echo "→ Total de registros en asistencias: $total\n\n";

    if ($total > 0) {
        // Mostrar los últimos 10 registros
        echo "→ Últimos 10 registros de asistencia:\n\n";

        $stmt = $conn->query("
            SELECT 
                a.id_asistencia,
                a.id_aprendiz,
                a.id_usuario,
                a.id_ficha,
                a.fecha,
                a.hora_entrada,
                a.tipo,
                a.observaciones,
                ap.nombre || ' ' || ap.apellido as nombre_aprendiz
            FROM asistencias a
            LEFT JOIN aprendices ap ON a.id_aprendiz = ap.id_aprendiz
            ORDER BY a.id_asistencia DESC
            LIMIT 10
        ");

        $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($registros as $i => $reg) {
            echo "Registro #" . ($i + 1) . ":\n";
            echo "  ID Asistencia: {$reg['id_asistencia']}\n";
            echo "  Aprendiz: {$reg['nombre_aprendiz']} (ID: {$reg['id_aprendiz']})\n";
            echo "  ID Usuario: {$reg['id_usuario']}\n";
            echo "  ID Ficha: {$reg['id_ficha']}\n";
            echo "  Fecha: {$reg['fecha']}\n";
            echo "  Hora Entrada: {$reg['hora_entrada']}\n";
            echo "  Tipo: {$reg['tipo']}\n";
            echo "  Observaciones: " . ($reg['observaciones'] ?: 'N/A') . "\n";
            echo "\n";
        }

        // Mostrar resumen por fecha
        echo "→ Resumen por fecha:\n\n";

        $stmt = $conn->query("
            SELECT 
                fecha,
                id_ficha,
                COUNT(*) as total,
                SUM(CASE WHEN tipo IN ('entrada', 'completa') THEN 1 ELSE 0 END) as presentes,
                SUM(CASE WHEN tipo = 'falla' THEN 1 ELSE 0 END) as ausentes
            FROM asistencias
            GROUP BY fecha, id_ficha
            ORDER BY fecha DESC, id_ficha
            LIMIT 10
        ");

        $resumen = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($resumen as $r) {
            $porcentaje = $r['total'] > 0 ? round(($r['presentes'] / $r['total']) * 100, 1) : 0;
            echo "  Fecha: {$r['fecha']} | Ficha: {$r['id_ficha']}\n";
            echo "    Total: {$r['total']} | Presentes: {$r['presentes']} | Ausentes: {$r['ausentes']} | Asistencia: {$porcentaje}%\n\n";
        }

        // Verificar registros de hoy
        $hoy = date('Y-m-d');
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM asistencias WHERE fecha = ?");
        $stmt->execute([$hoy]);
        $totalHoy = $stmt->fetch()['total'];

        echo "→ Registros de hoy ($hoy): $totalHoy\n";

        if ($totalHoy > 0) {
            echo "\n✅ Hay asistencias registradas hoy.\n";
        } else {
            echo "\n⚠️  No hay asistencias registradas hoy.\n";
        }
    } else {
        echo "⚠️  No hay registros de asistencia en la base de datos.\n";
    }

    echo "\n✅ Verificación completada.\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
