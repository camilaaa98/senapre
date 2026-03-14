<?php
require_once 'api/config/Database.php';

$db = Database::getInstance()->getConnection();

echo "=== ESTRUCTURA TABLA USUARIOS ===\n";
$sql = 'PRAGMA table_info(usuarios)';
$stmt = $db->prepare($sql);
$stmt->execute();
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($columns as $column) {
    echo "- " . $column['name'] . " (" . $column['type'] . ")\n";
}
?>
