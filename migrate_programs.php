<?php
require_once __DIR__ . '/api/config/Database.php';
try {
    $db = Database::getInstance()->getConnection();
    
    // Lista de columnas actuales
    $result = $db->query("PRAGMA table_info(programas_formacion)");
    $columns = $result->fetchAll(PDO::FETCH_COLUMN, 1);
    
    if (!in_array('hora_entrada', $columns)) {
        $db->exec("ALTER TABLE programas_formacion ADD COLUMN hora_entrada TEXT");
        echo "Added hora_entrada\n";
    }
    if (!in_array('hora_salida', $columns)) {
        $db->exec("ALTER TABLE programas_formacion ADD COLUMN hora_salida TEXT");
        echo "Added hora_salida\n";
    }
    if (!in_array('tipo_oferta', $columns)) {
        $db->exec("ALTER TABLE programas_formacion ADD COLUMN tipo_oferta TEXT DEFAULT 'Abierta'");
        echo "Added tipo_oferta\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
