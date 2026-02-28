<?php
require_once __DIR__ . '/api/config/Database.php';

header('Content-Type: text/plain');

try {
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    echo "--- CORRIGIENDO ESQUEMA POSTGRESQL ---\n";

    // 1. Agregar columna telefono a administrador si falta
    $stmt = $conn->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'administrador' AND column_name = 'telefono'");
    if (!$stmt->fetch()) {
        echo "Añadiendo columna 'telefono' a 'administrador'...\n";
        $conn->exec("ALTER TABLE administrador ADD COLUMN telefono TEXT");
        echo "Columna añadida con éxito.\n";
    } else {
        echo "La columna 'telefono' ya existe en 'administrador'.\n";
    }

    // 2. Sincronizar nombres/apellidos si están vacíos
    echo "Sincronizando datos desde 'usuarios'...\n";
    $conn->exec("UPDATE administrador a SET 
                nombres = u.nombre, 
                apellidos = u.apellido, 
                correo = u.correo 
                FROM usuarios u 
                WHERE a.id_usuario = u.id_usuario AND (a.nombres IS NULL OR a.nombres = '')");
    echo "Sincronización completada.\n";

    echo "\n--- VERIFICACIÓN FINAL ---\n";
    $stmt = $conn->query("SELECT * FROM administrador LIMIT 1");
    print_r($stmt->fetch(PDO::FETCH_ASSOC));

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
