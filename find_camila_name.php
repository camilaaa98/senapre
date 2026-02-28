<?php
require_once __DIR__ . '/api/config/Database.php';
$db = Database::getInstance();
$conn = $db->getConnection();

$doc = '1004417452';
$stmt = $conn->prepare('SELECT nombre, apellido FROM aprendices WHERE documento = :doc');
$stmt->execute([':doc' => $doc]);
$res = $stmt->fetch(PDO::FETCH_ASSOC);

if ($res) {
    echo "Nombre: " . $res['nombre'] . " " . $res['apellido'] . "\n";
} else {
    echo "Aprendiz no encontrada.\n";
}
?>
