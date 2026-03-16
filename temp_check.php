<?php
require_once 'api/config/Database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Verificar tabla voceros_enfoque
    $stmt = $conn->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'voceros_enfoque'");
    $exists = $stmt->fetch();
    
    if ($exists) {
        echo "✅ Tabla voceros_enfoque existe\n";
        
        // Verificar estructura
        $stmt = $conn->query("DESCRIBE voceros_enfoque");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "Estructura:\n";
        foreach ($columns as $col) {
            echo "- {$col['Field']}: {$col['Type']}\n";
        }
    } else {
        echo "❌ Tabla voceros_enfoque NO existe\n";
    }
    
    // Verificar tabla representantes
    $stmt = $conn->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'representantes'");
    $exists = $stmt->fetch();
    
    if ($exists) {
        echo "✅ Tabla representantes existe\n";
    } else {
        echo "❌ Tabla representantes NO existe\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
