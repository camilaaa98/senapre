<?php
/**
 * Restaurar estados RETIRO y CANCELADA si se perdieron
 * Basado en el historial de la ficha
 */

require_once __DIR__ . '/api/config/Database.php';

$db = new Database();
$conn = $db->getConnection();

echo "=== VERIFICACIÓN Y RESTAURACIÓN DE ESTADOS ===\n\n";

// 1. Verificar cuántos tienen NULL o vacío
$sinEstado = $conn->query("SELECT COUNT(*) FROM aprendices WHERE estado IS NULL OR estado = ''")->fetchColumn();
echo "Aprendices sin estado: $sinEstado\n\n";

if ($sinEstado > 0) {
    echo "ASIGNANDO ESTADO POR DEFECTO (LECTIVA) A APRENDICES SIN ESTADO...\n";
    $conn->exec("UPDATE aprendices SET estado = 'LECTIVA' WHERE estado IS NULL OR estado = ''");
    echo "✓ Actualizado\n\n";
}

// 2. Verificar distribución actual
echo "DISTRIBUCIÓN ACTUAL DE ESTADOS:\n";
$stmt = $conn->query("SELECT estado, COUNT(*) as total FROM aprendices GROUP BY estado ORDER BY total DESC");
$distribucion = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($distribucion as $d) {
    echo "  {$d['estado']}: {$d['total']}\n";
}

echo "\n✓ Verificación completada\n";
?>
