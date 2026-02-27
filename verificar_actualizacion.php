<?php
/**
 * Verificar datos actualizados - Muestra ejemplos
 */

require_once __DIR__ . '/api/config/Database.php';

$db = new Database();
$conn = $db->getConnection();

echo "=== VERIFICACIÓN DE DATOS ACTUALIZADOS ===\n\n";

// Mostrar 10 aprendices CON correo y celular
echo "APRENDICES CON CORREO Y CELULAR (10 ejemplos):\n";
echo str_repeat("-", 70) . "\n";
$stmt = $conn->query("SELECT documento, nombre, apellido, correo, celular 
                       FROM aprendices 
                       WHERE correo IS NOT NULL AND correo != '' 
                       AND celular IS NOT NULL AND celular != ''
                       LIMIT 10");
$conDatos = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($conDatos as $a) {
    echo "{$a['documento']} - {$a['nombre']} {$a['apellido']}\n";
    echo "  Correo: {$a['correo']}\n";
    echo "  Celular: {$a['celular']}\n\n";
}

// Estadísticas
$total = $conn->query("SELECT COUNT(*) FROM aprendices")->fetchColumn();
$conCorreo = $conn->query("SELECT COUNT(*) FROM aprendices WHERE correo IS NOT NULL AND correo != ''")->fetchColumn();
$conCelular = $conn->query("SELECT COUNT(*) FROM aprendices WHERE celular IS NOT NULL AND celular != ''")->fetchColumn();
$conAmbos = $conn->query("SELECT COUNT(*) FROM aprendices WHERE (correo IS NOT NULL AND correo != '') AND (celular IS NOT NULL AND celular != '')")->fetchColumn();

echo str_repeat("=", 70) . "\n";
echo "RESUMEN:\n";
echo "  Total: $total\n";
echo "  Con correo: $conCorreo (" . round(($conCorreo/$total)*100, 1) . "%)\n";
echo "  Con celular: $conCelular (" . round(($conCelular/$total)*100, 1) . "%)\n";
echo "  Con ambos: $conAmbos (" . round(($conAmbos/$total)*100, 1) . "%)\n";
?>
