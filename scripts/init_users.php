<?php
/**
 * Script para inicializar la tabla de usuarios
 * Ejecutar una sola vez para crear usuarios de prueba
 */

require_once __DIR__ . '/../api/config/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "Conectado a la base de datos exitosamente.\n";
    
    // Crear tabla de usuarios
    $db->ensureUsersTable();
    
    echo "Tabla de usuarios creada/verificada exitosamente.\n";
    echo "\nUsuarios de prueba:\n";
    echo "- Admin: admin@sena.edu.co / admin123\n";
    echo "- Instructor: instructor@sena.edu.co / instructor123\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
