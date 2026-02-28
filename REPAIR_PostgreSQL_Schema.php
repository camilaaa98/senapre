<?php
require_once __DIR__ . '/api/config/Database.php';

header('Content-Type: text/plain');

try {
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    echo "--- REPARACIÓN DE ESQUEMA POSTGRESQL --- \n";

    // Intentar agregar directamente
    try {
        $conn->exec("ALTER TABLE administrador ADD COLUMN IF NOT EXISTS telefono TEXT");
        echo "Comando ejecutado con éxito: ALTER TABLE administrador ADD COLUMN IF NOT EXISTS telefono TEXT\n";
    } catch (Exception $e) {
        echo "Error al agregar columna (puede que ya exista): " . $e->getMessage() . "\n";
    }

    // Sincronizar datos
    $conn->exec("UPDATE administrador a SET 
                nombres = u.nombre, 
                apellidos = u.apellido, 
                correo = u.correo 
                FROM usuarios u 
                WHERE a.id_usuario = u.id_usuario AND (a.nombres IS NULL OR a.nombres = '')");
    echo "Sincronización de datos completada.\n";

    echo "\n--- VERIFICACIÓN DE COLUMNAS ---\n";
    $stmt = $conn->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'administrador' AND table_schema = 'public'");
    print_r($stmt->fetchAll(PDO::FETCH_COLUMN));

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
