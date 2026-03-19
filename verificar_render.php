<?php
require_once __DIR__ . '/api/config/Database.php';

try {
    // El .env ya tiene la nueva URL
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    echo "=== VERIFICANDO NUEVA BASE DE DATOS RENDER ===\n";
    
    $sql = "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'";
    $stmt = $conn->query($sql);
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "La base de datos está VACÍA. ¿Deseas ejecutar el esquema inicial?\n";
        echo "Tablas encontradas: 0\n";
    } else {
        echo "Tablas encontradas (" . count($tables) . "):\n";
        foreach ($tables as $table) {
            echo "- $table\n";
        }
    }
    
    // Verificar si hay usuarios (admin)
    if (in_array('usuarios', $tables)) {
        $stmt = $conn->query("SELECT COUNT(*) FROM usuarios");
        echo "Usuarios registrados: " . $stmt->fetchColumn() . "\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
