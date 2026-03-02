<?php
require_once __DIR__ . '/api/config/Database.php';
$db = Database::getInstance();
$conn = $db->getConnection();

$ficha = '2995479';
echo "=== AUDITORÍA FINAL FICHA $ficha ===\n";

// Simular parámetros de la API corregida
$where = " WHERE numero_ficha = :ficha";
$params = [':ficha' => $ficha];

// Obtener aprendices como lo haría la API ahora
$sql = "SELECT documento, nombre, apellido, estado FROM aprendices $where";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$aprendices = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Total aprendices encontrados para esta ficha con la nueva lógica: " . count($aprendices) . "\n";
foreach ($aprendices as $a) {
    echo "- {$a['documento']}: {$a['nombre']} {$a['apellido']} ({$a['estado']})\n";
}

if (count($aprendices) === 0) {
    echo "ERROR: Siguen sin aparecer aprendices.\n";
} else {
    echo "ÉXITO: Los aprendices son ahora visibles para la consulta de ficha.\n";
}
?>
