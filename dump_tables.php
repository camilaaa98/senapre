<?php
require_once __DIR__ . '/api/config/Database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $output = "=== Listado de Tablas ===\n";
    
    $stmt = $conn->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        $countStmt = $conn->query("SELECT COUNT(*) FROM `$table`");
        $count = $countStmt->fetchColumn();
        $output .= "Tabla: [$table] - Registros: $count\n";
    }
    
    file_put_contents('tables_dump.txt', $output);
    echo "Dump created.";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
