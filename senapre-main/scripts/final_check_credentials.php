<?php
require_once __DIR__ . '/api/config/Database.php';
$db = Database::getInstance();
$conn = $db->getConnection();

echo "--- DATOS DE CAMILA (1004417452) ---\n";
$id = '1004417452';
$stmt = $conn->prepare('SELECT nombre, apellido, correo, rol, vocero_scopes FROM usuarios WHERE id_usuario = :id');
$stmt->execute([':id' => $id]);
$u = $stmt->fetch(PDO::FETCH_ASSOC);
if ($u) {
    echo "Nombre: " . $u['nombre'] . " " . $u['apellido'] . "\n";
    echo "Correo: " . $u['correo'] . "\n";
    echo "Rol: " . $u['rol'] . "\n";
    echo "Scopes: " . $u['vocero_scopes'] . "\n";
} else {
    echo "Usuario 1004417452 no encontrado en la tabla 'usuarios'.\n";
}

echo "\n--- DATOS DE JANCY (111784566) ---\n";
$idJ = '111784566';
$stmtJ = $conn->prepare('SELECT nombre, apellido, correo, rol FROM usuarios WHERE id_usuario = :id');
$stmtJ->execute([':id' => $idJ]);
$uJ = $stmtJ->fetch(PDO::FETCH_ASSOC);
if ($uJ) {
    echo "Nombre: " . $uJ['nombre'] . " " . $uJ['apellido'] . "\n";
    echo "Correo: " . $uJ['correo'] . "\n";
    echo "Rol: " . $uJ['rol'] . "\n";
}
?>
