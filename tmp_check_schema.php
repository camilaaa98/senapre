<?php
require_once __DIR__ . '/api/config/Database.php';
try {
    $database = Database::getInstance();
    $conn = $database->getConnection();
    echo "COLUMNS FOR TABLE 'fichas' (SQLite):\n";
    $stmt = $conn->query("PRAGMA table_info(fichas)");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- " . $row['name'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
