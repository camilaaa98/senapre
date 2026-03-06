<?php
/**
 * Normalizar nombres de estados en la base de datos
 * RETIRO -> RETIRO, RETIRADO -> RETIRO
 * CANCELADA -> CANCELADA, CANCELADO -> CANCELADA
 */

require_once __DIR__ . '/api/config/Database.php';

$db = new Database();
$conn = $db->getConnection();

echo "=== NORMALIZACIÓN DE ESTADOS ===\n\n";

// Normalizar RETIRADO -> RETIRO
$stmt = $conn->query("SELECT COUNT(*) FROM aprendices WHERE estado = 'RETIRADO'");
$retirados = $stmt->fetchColumn();

if ($retirados > 0) {
    echo "Normalizando $retirados aprendices: RETIRADO -> RETIRO\n";
    $conn->exec("UPDATE aprendices SET estado = 'RETIRO' WHERE estado = 'RETIRADO'");
    echo "✓ Completado\n\n";
}

// Normalizar CANCELADO -> CANCELADA
$stmt = $conn->query("SELECT COUNT(*) FROM aprendices WHERE estado = 'CANCELADO'");
$cancelados = $stmt->fetchColumn();

if ($cancelados > 0) {
    echo "Normalizing $cancelados aprendices: CANCELADO -> CANCELADA\n";
    $conn->exec("UPDATE aprendices SET estado = 'CANCELADA' WHERE estado = 'CANCELADO'");
    echo "✓ Completado\n\n";
}

// Normalizar TRASLADADO -> TRASLADO (si existe)
$stmt = $conn->query("SELECT COUNT(*) FROM aprendices WHERE estado = 'TRASLADADO'");
$trasladados = $stmt->fetchColumn();

if ($trasladados > 0) {
    echo "Normalizando $trasladados aprendices: TRASLADADO -> TRASLADO\n";
    $conn->exec("UPDATE aprendices SET estado = 'TRASLADO' WHERE estado = 'TRASLADADO'");
    echo "✓ Completado\n\n";
}

// Mostrar distribución final
echo "DISTRIBUCIÓN FINAL DE ESTADOS:\n";
$stmt = $conn->query("SELECT estado, COUNT(*) as total FROM aprendices GROUP BY estado ORDER BY total DESC");
$distribucion = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($distribucion as $d) {
    echo "  {$d['estado']}: {$d['total']}\n";
}

echo "\n✓ Normalización completada\n";
?>
