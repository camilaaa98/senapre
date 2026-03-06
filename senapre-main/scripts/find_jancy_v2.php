<?php
require_once __DIR__ . '/api/config/Database.php';
$db = Database::getInstance();
$conn = $db->getConnection();

echo "--- BUSCANDO EN APRENDICES ---\n";
$stmt = $conn->prepare('SELECT documento, nombre, apellido, correo FROM aprendices WHERE nombre LIKE :nom OR apellido LIKE :nom');
$stmt->execute([':nom' => '%ancy%']);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($results, JSON_PRETTY_PRINT) . "\n\n";

echo "--- BUSCANDO EN USUARIOS ---\n";
$stmt = $conn->prepare('SELECT id_usuario, nombre, apellido, correo, rol FROM usuarios WHERE nombre LIKE :nom OR apellido LIKE :nom');
$stmt->execute([':nom' => '%ancy%']);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($results, JSON_PRETTY_PRINT) . "\n";
?>
