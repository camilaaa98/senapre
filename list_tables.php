<?php
try {
    $db_path = __DIR__ . '/api/database.sqlite';
    $conn = new PDO("sqlite:$db_path");
    
    echo "=== TABLES ===\n";
    $sql = "SELECT name FROM sqlite_master WHERE type='table'";
    $stmt = $conn->query($sql);
    $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($tables as $table) {
       echo "- " . $table['name'] . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
