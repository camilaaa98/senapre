<?php
require_once __DIR__ . '/api/config/Database.php';
$db = Database::getInstance();
$conn = $db->getConnection();

$doc = '1004417452';
$stmt = $conn->prepare("SELECT id_usuario, correo, rol, nombre, apellido, estado FROM usuarios WHERE id_usuario = :doc");
$stmt->execute([':doc' => $doc]);
$res = $stmt->fetch(PDO::FETCH_ASSOC);

echo "=== DETALLE USUARIO $doc ===\n";
print_r($res);

echo "\n¿Hay más de un usuario con este correo?\n";
$stmt = $conn->prepare("SELECT id_usuario, correo, rol FROM usuarios WHERE correo = (SELECT correo FROM usuarios WHERE id_usuario = :doc)");
$stmt->execute([':doc' => $doc]);
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n¿Hay más de un usuario con este nombre?\n";
$stmt = $conn->prepare("SELECT id_usuario, correo, rol FROM usuarios WHERE nombre = :nombre AND apellido = :apellido");
$stmt->execute([':nombre' => $res['nombre'], ':apellido' => $res['apellido']]);
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
