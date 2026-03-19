<?php
/**
 * Script para crear tabla de biometría de líderes
 * Estructura idéntica a biometria_aprendices del panel instructor
 */

require_once __DIR__ . '/config/Database.php';

try {
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    // Verificar si la tabla ya existe
    $checkTable = $conn->query("SELECT name FROM sqlite_master WHERE type='table' AND name='biometria_lideres'");
    
    if ($checkTable->fetch()) {
        echo "Tabla biometria_lideres ya existe.\n";
    } else {
        // Crear tabla con estructura idéntica a biometria_aprendices
        $sql = "CREATE TABLE biometria_lideres (
            id_biometria INTEGER PRIMARY KEY AUTOINCREMENT,
            documento TEXT NOT NULL UNIQUE,
            embedding_facial BLOB NOT NULL,
            ultima_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $conn->exec($sql);
        
        // Crear índice para búsquedas rápidas
        $conn->exec("CREATE INDEX idx_biometria_lideres_documento ON biometria_lideres(documento)");
        
        echo "Tabla biometria_lideres creada exitosamente.\n";
        echo "Estructura idéntica a biometria_aprendices del panel instructor.\n";
    }
    
    // Verificar tabla biometria_aprendices existe (referencia del instructor)
    $checkAprendices = $conn->query("SELECT name FROM sqlite_master WHERE type='table' AND name='biometria_aprendices'");
    
    if ($checkAprendices->fetch()) {
        echo "Tabla biometria_aprendices existe (sistema del instructor).\n";
        
        // Mostrar estructura para confirmar son idénticas
        $schema = $conn->query("PRAGMA table_info(biometria_aprendices)");
        echo "\nEstructura de biometria_aprendices (referencia):\n";
        while ($col = $schema->fetch(PDO::FETCH_ASSOC)) {
            echo "  - {$col['name']}: {$col['type']}\n";
        }
        
        $schemaLideres = $conn->query("PRAGMA table_info(biometria_lideres)");
        echo "\nEstructura de biometria_lideres (creada):\n";
        while ($col = $schemaLideres->fetch(PDO::FETCH_ASSOC)) {
            echo "  - {$col['name']}: {$col['type']}\n";
        }
        
    } else {
        echo "ADVERTENCIA: Tabla biometria_aprendices no existe.\n";
        echo "El sistema del instructor no está configurado.\n";
    }
    
    echo "\n✅ Configuración completada.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
