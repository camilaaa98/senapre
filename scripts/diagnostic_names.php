<?php
require_once __DIR__ . '/api/config/Database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $usuarios = $conn->query("SELECT id_usuario, nombre, apellido FROM usuarios WHERE rol = 'instructor' LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "--- TABLA USUARIOS ---\n";
    foreach ($usuarios as $u) {
        echo "ID: {$u['id_usuario']} | Nombre: [{$u['nombre']}] | Apellido: [{$u['apellido']}]\n";
    }
    
    echo "\n--- TABLA INSTRUCTORES ---\n";
    $instructores = $conn->query("SELECT id_usuario, nombres, apellidos FROM instructores LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($instructores as $i) {
        echo "ID_USR: {$i['id_usuario']} | Nombres: [{$i['nombres']}] | Apellidos: [{$i['apellidos']}]\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
