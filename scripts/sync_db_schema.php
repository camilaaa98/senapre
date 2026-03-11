<?php
require_once __DIR__ . '/../api/config/Database.php';

echo "Iniciando migración de base de datos...\n";

try {
    $conn = Database::getInstance()->getConnection();
    $dbType = Database::getInstance()->getDbPath();
    echo "Conectado a: $dbType\n";

    $requiredColumns = [
        'mujer' => 'INTEGER DEFAULT 0',
        'indigena' => 'INTEGER DEFAULT 0',
        'narp' => 'INTEGER DEFAULT 0',
        'campesino' => 'INTEGER DEFAULT 0',
        'lgbtiq' => 'INTEGER DEFAULT 0',
        'discapacidad' => 'INTEGER DEFAULT 0'
    ];

    foreach ($requiredColumns as $col => $type) {
        try {
            // Intentar agregar la columna. Si ya existe, fallará el comando pero el script continuará
            echo "Intentando agregar columna '$col'...";
            
            if (strpos($dbType, 'PostgreSQL') !== false) {
                // Sintaxis PostgreSQL
                $conn->exec("ALTER TABLE aprendices ADD COLUMN IF NOT EXISTS $col $type");
            } else {
                // Sintaxis SQLite (no soporta IF NOT EXISTS en ADD COLUMN directamente en versiones viejas)
                $conn->exec("ALTER TABLE aprendices ADD COLUMN $col $type");
            }
            
            echo " [OK/EXISTE]\n";
        } catch (Exception $e2) {
            echo " [INFO] " . $e2->getMessage() . "\n";
        }
    }

    echo "\nMigración finalizada con éxito.\n";

} catch (Exception $e) {
    echo "\n[ERROR CRITICO] " . $e->getMessage() . "\n";
}
