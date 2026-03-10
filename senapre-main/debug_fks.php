<?php
require_once 'api/config/Database.php';
try {
    $conn = Database::getInstance()->getConnection();
    $tables = ['aprendices', 'fichas'];
    foreach ($tables as $table) {
        echo "=== FOREIGN KEYS FOR: $table ===\n";
        $stmt = $conn->query("PRAGMA foreign_key_list($table)");
        var_dump($stmt->fetchAll(PDO::FETCH_ASSOC));
        echo "\n";
    }
    $stmt = $conn->query("PRAGMA foreign_keys");
    echo "Foreign Keys status: " . $stmt->fetchColumn() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
