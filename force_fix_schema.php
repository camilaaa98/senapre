<?php
require_once __DIR__ . '/api/config/Database.php';

header('Content-Type: text/plain');

try {
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    echo "--- FORZANDO AGREGAR COLUMNA --- \n";

    // Intentar agregar directamente
    try {
        $conn->exec("ALTER TABLE administrador ADD COLUMN IF NOT EXISTS telefono TEXT");
        echo "Comando ejecutado: ALTER TABLE administrador ADD COLUMN IF NOT EXISTS telefono TEXT\n";
    } catch (Exception $e) {
        echo "Error al agregar: " . $e->getMessage() . "\n";
    }

    echo "\n--- VERIFICACIÓN DE COLUMNAS EN ADMINISTRADOR ---\n";
    $stmt = $conn->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'administrador' AND table_schema = 'public'");
    print_r($stmt->fetchAll(PDO::FETCH_COLUMN));

} catch (Exception $e) {
    echo "ERROR CRÍTICO: " . $e->getMessage() . "\n";
}
?>
