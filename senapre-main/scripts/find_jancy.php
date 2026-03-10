<?php
require_once __DIR__ . '/api/config/Database.php';
$db = Database::getInstance();
$conn = $db->getConnection();
$stmt = $conn->prepare('SELECT documento, nombre, apellido, correo FROM aprendices WHERE nombre LIKE :nom OR apellido LIKE :nom');
$stmt->execute([':nom' => '%Jancy%']);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($results, JSON_PRETTY_PRINT);
?>
