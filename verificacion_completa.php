<?php
/**
 * VERIFICACIÓN COMPLETA Y HONESTA DEL SISTEMA
 */

require_once __DIR__ . '/api/config/Database.php';

$db = new Database();
$conn = $db->getConnection();

echo "=== VERIFICACIÓN COMPLETA DEL SISTEMA ===\n\n";

// 1. Estados REALES en aprendices
echo "1. ESTADOS REALES EN APRENDICES:\n";
echo str_repeat("-", 80) . "\n";
$stmt = $conn->query("SELECT estado, COUNT(*) as total FROM aprendices GROUP BY estado ORDER BY total DESC");
$dist = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalAprendices = 0;
foreach ($dist as $d) {
    $estado = $d['estado'] ?: '(NULL/VACÍO)';
    $totalAprendices += $d['total'];
    echo sprintf("   %-20s: %d\n", $estado, $d['total']);
}
echo "   " . str_repeat("-", 76) . "\n";
echo sprintf("   %-20s: %d\n", "TOTAL", $totalAprendices);

// 2. Verificar si hay aprendices con RETIRO o CANCELADA que aparecen como LECTIVA
echo "\n2. VERIFICANDO INCONSISTENCIAS:\n";
echo str_repeat("-", 80) . "\n";
$stmt = $conn->query("SELECT COUNT(*) FROM aprendices WHERE estado = 'LECTIVA'");
$lectiva = $stmt->fetchColumn();
echo "   Aprendices en LECTIVA: $lectiva\n";

$stmt = $conn->query("SELECT COUNT(*) FROM aprendices WHERE estado = 'RETIRO'");
$retiro = $stmt->fetchColumn();
echo "   Aprendices en RETIRO: $retiro\n";

$stmt = $conn->query("SELECT COUNT(*) FROM aprendices WHERE estado = 'CANCELADA'");
$cancelada = $stmt->fetchColumn();
echo "   Aprendices en CANCELADA: $cancelada\n";

// 3. Voceros en fichas
echo "\n3. VOCEROS EN FICHAS:\n";
echo str_repeat("-", 80) . "\n";
$stmt = $conn->query("SELECT COUNT(*) FROM fichas");
$totalFichas = $stmt->fetchColumn();

$stmt = $conn->query("SELECT COUNT(*) FROM fichas WHERE vocero_principal IS NOT NULL");
$conPrincipal = $stmt->fetchColumn();

$stmt = $conn->query("SELECT COUNT(*) FROM fichas WHERE vocero_suplente IS NOT NULL");
$conSuplente = $stmt->fetchColumn();

echo "   Total fichas: $totalFichas\n";
echo "   Fichas con vocero principal: $conPrincipal\n";
echo "   Fichas con vocero suplente: $conSuplente\n";

// Mostrar ejemplos
echo "\n   Ejemplos de fichas CON voceros:\n";
$stmt = $conn->query("SELECT numero_ficha, vocero_principal, vocero_suplente FROM fichas WHERE vocero_principal IS NOT NULL LIMIT 5");
$ejemplos = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($ejemplos as $f) {
    $principal = $f['vocero_principal'] ?: '(vacío)';
    $suplente = $f['vocero_suplente'] ?: '(vacío)';
    echo "      Ficha {$f['numero_ficha']}: Principal=$principal, Suplente=$suplente\n";
}

echo "\n   Ejemplos de fichas SIN voceros:\n";
$stmt = $conn->query("SELECT numero_ficha, vocero_principal, vocero_suplente FROM fichas WHERE vocero_principal IS NULL LIMIT 5");
$ejemplos = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($ejemplos as $f) {
    echo "      Ficha {$f['numero_ficha']}: SIN VOCEROS\n";
}

// 4. Datos de contacto
echo "\n4. DATOS DE CONTACTO:\n";
echo str_repeat("-", 80) . "\n";
$conCorreo = $conn->query("SELECT COUNT(*) FROM aprendices WHERE correo IS NOT NULL AND correo != ''")->fetchColumn();
$conCelular = $conn->query("SELECT COUNT(*) FROM aprendices WHERE celular IS NOT NULL AND celular != ''")->fetchColumn();

echo "   Con correo: $conCorreo de $totalAprendices (" . round(($conCorreo/$totalAprendices)*100, 1) . "%)\n";
echo "   Con celular: $conCelular de $totalAprendices (" . round(($conCelular/$totalAprendices)*100, 1) . "%)\n";

// Mostrar ejemplos
echo "\n   Ejemplos CON datos:\n";
$stmt = $conn->query("SELECT documento, nombre, apellido, correo, celular FROM aprendices WHERE correo IS NOT NULL AND correo != '' LIMIT 3");
$ejemplos = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($ejemplos as $a) {
    echo "      {$a['documento']} - {$a['nombre']} {$a['apellido']}\n";
    echo "         Correo: {$a['correo']}\n";
    echo "         Celular: {$a['celular']}\n";
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "VERIFICACIÓN COMPLETADA\n";
echo str_repeat("=", 80) . "\n";
?>
