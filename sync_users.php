<?php
header('Content-Type: text/plain');
require_once __DIR__ . '/api/config/Database.php';

$db = new Database();
$conn = $db->getConnection();

echo "SYNCING 'usuarios' WITH 'administrador' DATA...\n";

// Update usuarios from administrador
$sql = "UPDATE usuarios 
        SET nombre = (SELECT nombres FROM administrador WHERE administrador.id_usuario = usuarios.id_usuario),
            apellido = (SELECT apellidos FROM administrador WHERE administrador.id_usuario = usuarios.id_usuario),
            correo = (SELECT correo FROM administrador WHERE administrador.id_usuario = usuarios.id_usuario)
        WHERE id_usuario IN (SELECT id_usuario FROM administrador)";

$affected = $conn->exec($sql);
echo "Affected rows (Admin): $affected\n";

echo "SYNCING 'usuarios' WITH 'instructores' DATA...\n";
// Update usuarios from instructores
$sql = "UPDATE usuarios 
        SET nombre = (SELECT nombres FROM instructores WHERE instructores.id_usuario = usuarios.id_usuario),
            apellido = (SELECT apellidos FROM instructores WHERE instructores.id_usuario = usuarios.id_usuario),
            correo = (SELECT correo FROM instructores WHERE instructores.id_usuario = usuarios.id_usuario)
        WHERE id_usuario IN (SELECT id_usuario FROM instructores)";

$affected = $conn->exec($sql);
echo "Affected rows (Instructors): $affected\n";
