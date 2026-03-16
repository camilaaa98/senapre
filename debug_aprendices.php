<?php
require_once __DIR__ . '/api/config/Database.php';

try {
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    $stmt = $conn->query("SELECT COUNT(*) as total FROM aprendices");
    $total = $stmt->fetch()['total'];
    echo "Total aprendices: " . $total . "\n";
    
    $stmt = $conn->query("SELECT estado, COUNT(*) as count FROM aprendices GROUP BY estado");
    $estados = $stmt->fetchAll();
    echo "Estados:\n";
    foreach ($estados as $e) {
        echo "- " . $e['estado'] . ": " . $e['count'] . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
