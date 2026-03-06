<?php
require_once __DIR__ . '/api/config/Database.php';

try {
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    // Añadir columna municipio a la tabla fichas si no existe
    $isPg = strpos($database->getDbPath(), 'PostgreSQL') !== false;
    
    if (!$isPg) {
        // SQLite
        $checkColumn = $conn->query("PRAGMA table_info(fichas)");
        $columns = $checkColumn->fetchAll(PDO::FETCH_ASSOC);
        $existingCols = array_column($columns, 'name');
        
        if (!in_array('municipio', $existingCols)) {
            $conn->exec("ALTER TABLE fichas ADD COLUMN municipio TEXT DEFAULT 'Florencia'");
            echo "Columna 'municipio' añadida exitosamente a la tabla 'fichas'.\n";
        } else {
            echo "La columna 'municipio' ya existe.\n";
        }
    } else {
        // PostgreSQL
        $conn->exec("ALTER TABLE fichas ADD COLUMN IF NOT EXISTS municipio TEXT DEFAULT 'Florencia'");
        echo "Columna 'municipio' (PostgreSQL) verificada/añadida.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
