<?php
require_once __DIR__ . '/api/config/Database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $tables = ['usuarios', 'instructores', 'administrador'];
    
    $output = "";
    foreach ($tables as $table) {
        $stmt = $conn->prepare("SELECT sql FROM sqlite_master WHERE type='table' AND name = ?");
        $stmt->execute([$table]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $output .= "--- TABLE: $table ---\n";
            $output .= $row['sql'] . "\n\n";
        } else {
            $output .= "--- TABLE: $table (NOT FOUND) ---\n\n";
        }
    }
    file_put_contents('schema_results.txt', $output);
    echo "Esquema guardado en schema_results.txt\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
