<?php
require_once __DIR__ . '/api/config/Database.php';
$db = Database::getInstance();
$conn = $db->getConnection();

$ficha = '2995479';
echo "=== AUDITORÍA FICHA $ficha ===\n";

$sql = "SELECT f.numero_ficha, f.vocero_principal, 
               (SELECT COUNT(*) FROM aprendices WHERE numero_ficha = f.numero_ficha) as total_aprendices,
               (SELECT COUNT(*) FROM aprendices WHERE numero_ficha = f.numero_ficha AND estado NOT IN ('RETIRADO', 'CANCELADO', 'FINALIZADO', 'TRASLADO', 'APLAZADO')) as activos
        FROM fichas f 
        WHERE f.numero_ficha = :ficha";

$stmt = $conn->prepare($sql);
$stmt->execute([':ficha' => $ficha]);
$res = $stmt->fetch(PDO::FETCH_ASSOC);

print_r($res);

echo "\nDISTRIBUCIÓN DE ESTADOS EN ESTA FICHA:\n";
$stmt = $conn->prepare("SELECT estado, COUNT(*) as cant FROM aprendices WHERE numero_ficha = :ficha GROUP BY estado");
$stmt->execute([':ficha' => $ficha]);
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\nVOCERO PRINCIPAL DATA:\n";
$stmt = $conn->prepare("SELECT documento, nombre, apellido, estado FROM aprendices WHERE documento = :doc");
$stmt->execute([':doc' => $res['vocero_principal']]);
print_r($stmt->fetch(PDO::FETCH_ASSOC));
?>
