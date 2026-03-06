<?php
/**
 * Verificación final de todo lo corregido
 */

require_once __DIR__ . '/api/config/Database.php';

$db = new Database();
$conn = $db->getConnection();

echo "=== VERIFICACIÓN FINAL ===\n\n";

// 1. Estados en tabla estados
echo "ESTADOS EN TABLA 'estados':\n";
$stmt = $conn->query("SELECT nombre, color FROM estados ORDER BY nombre");
$estados = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($estados as $e) {
    echo "  - {$e['nombre']} (Color: {$e['color']})\n";
}

// 2. Distribución de estados en aprendices
echo "\nDISTRIBUCIÓN DE ESTADOS EN APRENDICES:\n";
$stmt = $conn->query("SELECT estado, COUNT(*) as total FROM aprendices GROUP BY estado ORDER BY total DESC");
$dist = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($dist as $d) {
    echo "  {$d['estado']}: {$d['total']}\n";
}

// 3. Voceros asignados
echo "\nVOCEROS ASIGNADOS:\n";
$conVoceros = $conn->query("SELECT COUNT(*) FROM fichas WHERE vocero_principal IS NOT NULL OR vocero_suplente IS NOT NULL")->fetchColumn();
echo "  Fichas con voceros: $conVoceros\n";

// 4. Datos de contacto
echo "\nDATOS DE CONTACTO:\n";
$total = $conn->query("SELECT COUNT(*) FROM aprendices")->fetchColumn();
$conCorreo = $conn->query("SELECT COUNT(*) FROM aprendices WHERE correo IS NOT NULL AND correo != ''")->fetchColumn();
$conCelular = $conn->query("SELECT COUNT(*) FROM aprendices WHERE celular IS NOT NULL AND celular != ''")->fetchColumn();
echo "  Total aprendices: $total\n";
echo "  Con correo: $conCorreo (" . round(($conCorreo/$total)*100, 1) . "%)\n";
echo "  Con celular: $conCelular (" . round(($conCelular/$total)*100, 1) . "%)\n";

echo "\n✓ VERIFICACIÓN COMPLETADA\n";
?>
