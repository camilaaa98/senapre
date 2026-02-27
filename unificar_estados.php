<?php
/**
 * UNIFICACIÓN DEFINITIVA DE ESTADOS
 * CANCELADA = CANCELADO
 * EN FORMACION = LECTIVA
 */

require_once __DIR__ . '/api/config/Database.php';

$db = new Database();
$conn = $db->getConnection();

echo "=== UNIFICACIÓN DE ESTADOS ===\n\n";

// 1. Unificar CANCELADA y CANCELADO -> CANCELADA
echo "1. Unificando CANCELADO -> CANCELADA...\n";
$stmt = $conn->query("SELECT COUNT(*) FROM aprendices WHERE estado = 'CANCELADO'");
$count = $stmt->fetchColumn();
if ($count > 0) {
    $conn->exec("UPDATE aprendices SET estado = 'CANCELADA' WHERE estado = 'CANCELADO'");
    echo "   ✓ $count aprendices actualizados\n";
} else {
    echo "   - No hay registros con CANCELADO\n";
}

// 2. Unificar EN FORMACION -> LECTIVA
echo "\n2. Unificando EN FORMACION -> LECTIVA...\n";
$stmt = $conn->query("SELECT COUNT(*) FROM aprendices WHERE estado = 'EN FORMACION'");
$count = $stmt->fetchColumn();
if ($count > 0) {
    $conn->exec("UPDATE aprendices SET estado = 'LECTIVA' WHERE estado = 'EN FORMACION'");
    echo "   ✓ $count aprendices actualizados\n";
} else {
    echo "   - No hay registros con EN FORMACION\n";
}

// 3. Eliminar estados duplicados de la tabla estados
echo "\n3. Limpiando tabla estados...\n";
$conn->exec("DELETE FROM estados WHERE nombre = 'CANCELADO'");
$conn->exec("DELETE FROM estados WHERE nombre = 'EN FORMACION'");
echo "   ✓ Estados duplicados eliminados\n";

// 4. Distribución final
echo "\n" . str_repeat("=", 70) . "\n";
echo "DISTRIBUCIÓN FINAL DE ESTADOS:\n";
echo str_repeat("=", 70) . "\n";
$stmt = $conn->query("SELECT estado, COUNT(*) as total FROM aprendices GROUP BY estado ORDER BY estado");
$dist = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($dist as $d) {
    echo "  {$d['estado']}: {$d['total']}\n";
}

// 5. Estados en tabla
echo "\nESTADOS EN TABLA 'estados':\n";
$stmt = $conn->query("SELECT nombre FROM estados ORDER BY nombre");
$estados = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "  " . implode(', ', $estados) . "\n";

echo "\n✓ UNIFICACIÓN COMPLETADA\n";
?>
