<?php
require_once 'api/config/Database.php';
try {
    $conn = Database::getInstance()->getConnection();
    
    $aprCount = $conn->query("SELECT COUNT(*) FROM aprendices")->fetchColumn();
    $ficCount = $conn->query("SELECT COUNT(*) FROM fichas")->fetchColumn();
    $progCount = $conn->query("SELECT COUNT(*) FROM programas_formacion")->fetchColumn();
    
    echo "=== DATABASE STATUS ===\n";
    echo "Aprendices: $aprCount\n";
    echo "Fichas: $ficCount\n";
    echo "Programas: $progCount\n";
    
    echo "\n=== LAST 5 FICHAS ===\n";
    $stmt = $conn->query("SELECT * FROM fichas ORDER BY rowid DESC LIMIT 5");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        print_r($row);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
