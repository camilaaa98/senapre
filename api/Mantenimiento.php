<?php
require_once __DIR__ . '/config/Database.php';

header('Content-Type: text/plain');

try {
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    echo "--- Mantenimiento API ---\n";
    $conn->exec("ALTER TABLE administrador ADD COLUMN IF NOT EXISTS telefono TEXT");
    echo "Columna agregada.\n";

    $conn->exec("UPDATE administrador a SET 
                nombres = u.nombre, 
                apellidos = u.apellido, 
                correo = u.correo 
                FROM usuarios u 
                WHERE a.id_usuario = u.id_usuario AND (a.nombres IS NULL OR a.nombres = '')");
    echo "Datos sincronizados.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
