<?php
require_once __DIR__ . '/api/config/Database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "--- INSPECCIÓN DE TABLA 'usuarios' EN POSTGRESQL ---\n";
    
    // Listar todos los constraints de la tabla usuarios
    $sql = "SELECT conname, pg_get_constraintdef(c.oid) 
            FROM pg_constraint c 
            JOIN pg_namespace n ON n.oid = c.connamespace 
            WHERE contype = 'c' 
            AND conrelid = 'usuarios'::regclass";
            
    $stmt = $conn->query($sql);
    $constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($constraints)) {
        echo "No se encontraron CHECK constraints en la tabla 'usuarios'.\n";
    } else {
        foreach ($constraints as $c) {
            echo "Constraint: " . $c['conname'] . "\n";
            echo "Definition: " . $c['pg_get_constraintdef'] . "\n\n";
        }
    }
    
    // Ver definición de columnas por si acaso
    echo "--- COLUMNAS ---\n";
    $stmt = $conn->query("SELECT column_name, data_type, is_nullable 
                         FROM information_schema.columns 
                         WHERE table_name = 'usuarios' 
                         ORDER BY ordinal_position");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['column_name']} ({$row['data_type']}) | Nullable: {$row['is_nullable']}\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
