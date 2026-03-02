<?php
/**
 * Verificar si hay algún backup o forma de recuperar estados originales
 */

require_once __DIR__ . '/api/config/Database.php';

$db = new Database();
$conn = $db->getConnection();

echo "=== VERIFICACIÓN DE ESTADOS PERDIDOS ===\n\n";

// Ver distribución actual
echo "DISTRIBUCIÓN ACTUAL:\n";
$stmt = $conn->query("SELECT estado, COUNT(*) as total FROM aprendices GROUP BY estado ORDER BY estado");
$dist = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($dist as $d) {
    echo "  {$d['estado']}: {$d['total']}\n";
}

echo "\n¿Hay algún campo que pueda indicar el estado original?\n";
echo "Mostrando estructura de la tabla aprendices:\n\n";

$stmt = $conn->query("DESCRIBE aprendices");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($columns as $col) {
    echo "  {$col['Field']} ({$col['Type']})\n";
}

// Verificar si hay algún patrón
echo "\n\nBUSCANDO PATRONES:\n";
echo "Aprendices que podrían ser RETIRO o CANCELADA:\n";
$stmt = $conn->query("SELECT documento, nombre, apellido, estado, fecha_registro FROM aprendices WHERE estado = 'LECTIVA' LIMIT 20");
$ejemplos = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($ejemplos as $a) {
    echo "  {$a['documento']} - {$a['nombre']} - Estado: {$a['estado']} - Fecha: {$a['fecha_registro']}\n";
}
?>
