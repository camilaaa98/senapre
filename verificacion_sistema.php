<?php
/**
 * VERIFICACIÓN REAL DEL SISTEMA
 */

require_once __DIR__ . '/api/config/Database.php';

$db = new Database();
$conn = $db->getConnection();

echo "=== VERIFICACIÓN REAL DEL SISTEMA ===\n\n";

// 1. Estados actuales en aprendices
echo "1. DISTRIBUCIÓN ACTUAL DE ESTADOS EN APRENDICES:\n";
echo str_repeat("-", 70) . "\n";
$stmt = $conn->query("SELECT estado, COUNT(*) as total FROM aprendices GROUP BY estado ORDER BY estado");
$dist = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($dist as $d) {
    $estado = $d['estado'] ?: '(NULL)';
    echo "   $estado: {$d['total']}\n";
}

// 2. Estados en tabla estados
echo "\n2. ESTADOS EN TABLA 'estados':\n";
echo str_repeat("-", 70) . "\n";
$stmt = $conn->query("SELECT id, nombre, color FROM estados ORDER BY nombre");
$estados = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($estados as $e) {
    echo "   [{$e['id']}] {$e['nombre']} - Color: {$e['color']}\n";
}

// 3. Voceros asignados
echo "\n3. VOCEROS EN FICHAS:\n";
echo str_repeat("-", 70) . "\n";
$stmt = $conn->query("SELECT COUNT(*) FROM fichas WHERE vocero_principal IS NOT NULL OR vocero_suplente IS NOT NULL");
$conVoceros = $stmt->fetchColumn();
echo "   Fichas con voceros asignados: $conVoceros\n";

// Mostrar ejemplos
$stmt = $conn->query("SELECT numero_ficha, vocero_principal, vocero_suplente FROM fichas WHERE vocero_principal IS NOT NULL LIMIT 5");
$ejemplos = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "\n   Ejemplos:\n";
foreach ($ejemplos as $f) {
    echo "   - Ficha {$f['numero_ficha']}: Principal={$f['vocero_principal']}, Suplente={$f['vocero_suplente']}\n";
}

// 4. Contactos
echo "\n4. DATOS DE CONTACTO:\n";
echo str_repeat("-", 70) . "\n";
$total = $conn->query("SELECT COUNT(*) FROM aprendices")->fetchColumn();
$conCorreo = $conn->query("SELECT COUNT(*) FROM aprendices WHERE correo IS NOT NULL AND correo != ''")->fetchColumn();
$conCelular = $conn->query("SELECT COUNT(*) FROM aprendices WHERE celular IS NOT NULL AND celular != ''")->fetchColumn();

echo "   Total aprendices: $total\n";
echo "   Con correo: $conCorreo (" . round(($conCorreo/$total)*100, 1) . "%)\n";
echo "   Con celular: $conCelular (" . round(($conCelular/$total)*100, 1) . "%)\n";

echo "\n" . str_repeat("=", 70) . "\n";
echo "VERIFICACIÓN COMPLETADA\n";
echo str_repeat("=", 70) . "\n";
?>
