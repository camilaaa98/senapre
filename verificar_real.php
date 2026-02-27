<?php
/**
 * Verificación REAL de datos en la base de datos
 */

require_once __DIR__ . '/api/config/Database.php';

$db = new Database();
$conn = $db->getConnection();

echo "=== VERIFICACIÓN REAL DE DATOS ===\n\n";

// 1. Verificar correos y celulares
echo "MUESTRA DE APRENDICES CON CORREO Y CELULAR:\n";
echo str_repeat("-", 80) . "\n";
$stmt = $conn->query("SELECT documento, nombre, apellido, correo, celular 
                       FROM aprendices 
                       WHERE (correo IS NOT NULL AND correo != '') 
                       OR (celular IS NOT NULL AND celular != '')
                       LIMIT 10");
$ejemplos = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($ejemplos as $a) {
    echo "{$a['documento']} - {$a['nombre']} {$a['apellido']}\n";
    echo "  Correo: " . ($a['correo'] ?: '(vacío)') . "\n";
    echo "  Celular: " . ($a['celular'] ?: '(vacío)') . "\n\n";
}

// 2. Estadísticas
$total = $conn->query("SELECT COUNT(*) FROM aprendices")->fetchColumn();
$conCorreo = $conn->query("SELECT COUNT(*) FROM aprendices WHERE correo IS NOT NULL AND correo != ''")->fetchColumn();
$conCelular = $conn->query("SELECT COUNT(*) FROM aprendices WHERE celular IS NOT NULL AND celular != ''")->fetchColumn();

echo "ESTADÍSTICAS:\n";
echo "  Total: $total\n";
echo "  Con correo: $conCorreo\n";
echo "  Con celular: $conCelular\n\n";

// 3. Verificar estados actuales
echo "ESTADOS ACTUALES:\n";
$stmt = $conn->query("SELECT DISTINCT estado FROM aprendices WHERE estado IS NOT NULL ORDER BY estado");
$estados = $stmt->fetchAll(PDO::FETCH_COLUMN);
foreach ($estados as $e) {
    $count = $conn->query("SELECT COUNT(*) FROM aprendices WHERE estado = '$e'")->fetchColumn();
    echo "  $e: $count\n";
}

// 4. Verificar voceros en fichas
echo "\nVOCEROS EN FICHAS (primeras 10):\n";
$stmt = $conn->query("SELECT numero_ficha, vocero_principal, vocero_suplente FROM fichas LIMIT 10");
$fichas = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($fichas as $f) {
    echo "  Ficha {$f['numero_ficha']}: Principal={$f['vocero_principal']}, Suplente={$f['vocero_suplente']}\n";
}
?>
